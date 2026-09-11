<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class SurgeryChecklist
{
    public static function isAvailable(): bool
    {
        return FeatureFlags::enabled('features.surgery_checklist')
            && Schema::hasTable('patient_surgery_checklists')
            && Schema::hasTable('surgery_checklist_templates');
    }

    /**
     * @return array{type_id: ?int, subtype_id: ?int}
     */
    public static function resolveTypeIds(\App\Models\SurgeryAppointment $appointment): array
    {
        if (! self::isAvailable() || ! Schema::hasColumn('surgery_appointments', 'surgery_type_id')) {
            return ['type_id' => null, 'subtype_id' => null];
        }

        if ($appointment->surgery_type_id) {
            return [
                'type_id' => (int) $appointment->surgery_type_id,
                'subtype_id' => $appointment->surgery_subtype_id ? (int) $appointment->surgery_subtype_id : null,
            ];
        }

        $type = \App\Models\SurgeryType::query()
            ->where('name', $appointment->surgery_type)
            ->first();

        return [
            'type_id' => $type?->id,
            'subtype_id' => null,
        ];
    }

    public static function findTemplate(?int $typeId, ?int $subtypeId): ?\App\Models\SurgeryChecklistTemplate
    {
        if (! self::isAvailable() || ! $typeId) {
            return null;
        }

        if ($subtypeId) {
            $exact = \App\Models\SurgeryChecklistTemplate::query()
                ->with('items')
                ->where('surgery_type_id', $typeId)
                ->where('surgery_subtype_id', $subtypeId)
                ->first();
            if ($exact) {
                return $exact;
            }
        }

        return \App\Models\SurgeryChecklistTemplate::query()
            ->with('items')
            ->where('surgery_type_id', $typeId)
            ->whereNull('surgery_subtype_id')
            ->first();
    }

    public static function ensureForAppointment(\App\Models\SurgeryAppointment $appointment, ?int $userId = null): \App\Models\PatientSurgeryChecklist
    {
        if (! self::isAvailable()) {
            throw new \RuntimeException('چک‌لیست هنوز روی سرور فعال نشده. migrate را اجرا کنید.');
        }

        $ids = self::resolveTypeIds($appointment);
        $typeId = $ids['type_id'];
        $subtypeId = $ids['subtype_id'];

        $existing = \App\Models\PatientSurgeryChecklist::query()
            ->with(['items.checker', 'surgeryType', 'surgerySubtype'])
            ->where('surgery_appointment_id', $appointment->id)
            ->first();

        if ($existing) {
            try {
                self::syncWithTemplate($existing, $typeId, $subtypeId);
            } catch (\Throwable $error) {
                report($error);
            }

            return $existing->fresh(['items.checker', 'surgeryType', 'surgerySubtype']);
        }

        $template = self::findTemplate($typeId, $subtypeId);

        $type = $typeId ? \App\Models\SurgeryType::find($typeId) : null;
        $subtype = $subtypeId ? \App\Models\SurgerySubtype::find($subtypeId) : null;
        $title = trim(collect([$type?->name, $subtype?->name])->filter()->implode(' · '))
            ?: ($appointment->surgery_type ?: 'چک‌لیست عمل');

        return \Illuminate\Support\Facades\DB::transaction(function () use ($appointment, $typeId, $subtypeId, $template, $title, $userId) {
            $checklist = \App\Models\PatientSurgeryChecklist::create([
                'patient_id' => $appointment->patient_id,
                'surgery_appointment_id' => $appointment->id,
                'surgery_type_id' => $typeId,
                'surgery_subtype_id' => $subtypeId,
                'title' => $title,
                'created_by' => $userId,
            ]);

            $sort = 0;
            foreach ($template?->items ?? [] as $tplItem) {
                \App\Models\PatientSurgeryChecklistItem::create([
                    'checklist_id' => $checklist->id,
                    'label' => $tplItem->label,
                    'sort_order' => $sort++,
                    'is_custom' => false,
                ]);
            }

            return $checklist->load(['items.checker', 'surgeryType', 'surgerySubtype']);
        });
    }

    /**
     * Add new template items to an existing patient checklist (keeps checked/custom items).
     */
    public static function syncWithTemplate(
        \App\Models\PatientSurgeryChecklist $checklist,
        ?int $typeId,
        ?int $subtypeId
    ): void {
        $template = self::findTemplate($typeId, $subtypeId);
        if (! $template || $template->items->isEmpty()) {
            return;
        }

        $checklist->loadMissing('items');
        $existingLabels = $checklist->items
            ->pluck('label')
            ->map(fn ($l) => trim((string) $l))
            ->filter()
            ->all();

        $sort = (int) $checklist->items->max('sort_order');

        foreach ($template->items as $tplItem) {
            $label = trim((string) $tplItem->label);
            if ($label === '' || in_array($label, $existingLabels, true)) {
                continue;
            }

            \App\Models\PatientSurgeryChecklistItem::create([
                'checklist_id' => $checklist->id,
                'label' => $label,
                'sort_order' => ++$sort,
                'is_custom' => false,
            ]);
            $existingLabels[] = $label;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function toPayload(\App\Models\PatientSurgeryChecklist $checklist): array
    {
        $viewerId = auth()->id();
        $items = $checklist->items->map(fn (\App\Models\PatientSurgeryChecklistItem $item) => [
            'id' => $item->id,
            'label' => $item->label,
            'sort_order' => $item->sort_order,
            'is_custom' => $item->is_custom,
            'checked' => $item->checked_at !== null,
            'checked_at' => $item->checked_at?->toIso8601String(),
            'checked_at_jalali' => $item->checked_at ? Jalali::format($item->checked_at, 'Y/m/d H:i') : null,
            'checked_by' => $item->checker?->name,
            'checked_by_id' => $item->checked_by ? (int) $item->checked_by : null,
            'checked_by_me' => $item->checked_by !== null && $viewerId !== null && (int) $item->checked_by === (int) $viewerId,
            'checked_time_short' => $item->checked_at ? Jalali::format($item->checked_at, 'H:i') : null,
            'checked_stamp' => $item->checked_at
                ? trim(($item->checker?->name ? $item->checker->name.' · ' : '').Jalali::format($item->checked_at, 'H:i'))
                : null,
        ])->values()->all();

        $unchecked = collect($items)->where('checked', false)->count();

        return [
            'id' => $checklist->id,
            'title' => $checklist->title,
            'saved_at' => $checklist->saved_at?->toIso8601String(),
            'saved_at_jalali' => $checklist->saved_at ? Jalali::format($checklist->saved_at, 'Y/m/d H:i') : null,
            'unchecked_count' => $unchecked,
            'viewer_id' => $viewerId ? (int) $viewerId : null,
            'items' => $items,
        ];
    }

    public static function markSaved(\App\Models\PatientSurgeryChecklist $checklist): void
    {
        $checklist->update(['saved_at' => now()]);
    }
}
