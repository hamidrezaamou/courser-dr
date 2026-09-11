<?php

namespace App\Http\Controllers;

use App\Models\PatientSurgeryChecklist;
use App\Models\PatientSurgeryChecklistItem;
use App\Models\SurgeryAppointment;
use App\Models\SurgeryChecklistTemplate;
use App\Models\SurgeryChecklistTemplateItem;
use App\Models\SurgerySubtype;
use App\Models\SurgeryType;
use App\Support\SurgeryChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurgeryChecklistTemplateController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        if (! SurgeryChecklist::isAvailable()) {
            return response()->json(['items' => []]);
        }

        $typeId = $request->integer('surgery_type_id');
        $subtypeId = $request->filled('surgery_subtype_id') ? $request->integer('surgery_subtype_id') : null;

        if (! $typeId) {
            return response()->json(['items' => []]);
        }

        $template = SurgeryChecklistTemplate::query()
            ->with('items')
            ->where('surgery_type_id', $typeId)
            ->when(
                $subtypeId,
                fn ($q) => $q->where('surgery_subtype_id', $subtypeId),
                fn ($q) => $q->whereNull('surgery_subtype_id')
            )
            ->first();

        return response()->json([
            'items' => ($template?->items ?? collect())->map(fn ($i) => [
                'id' => $i->id,
                'label' => $i->label,
                'sort_order' => $i->sort_order,
            ])->values(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        if (! SurgeryChecklist::isAvailable()) {
            return response()->json(['message' => 'چک‌لیست هنوز فعال نشده.'], 503);
        }

        $validated = $request->validate([
            'surgery_type_id' => ['required', 'integer', 'exists:surgery_types,id'],
            'surgery_subtype_id' => ['nullable', 'integer', 'exists:surgery_subtypes,id'],
            'items' => ['nullable', 'array'],
            'items.*.label' => ['required', 'string', 'max:255'],
        ]);

        $typeId = (int) $validated['surgery_type_id'];
        $subtypeId = $validated['surgery_subtype_id'] ?? null;

        if ($subtypeId) {
            $subtype = SurgerySubtype::query()->findOrFail($subtypeId);
            if ((int) $subtype->surgery_type_id !== $typeId) {
                return response()->json(['message' => 'زیرگروه با نوع عمل هم‌خوان نیست.'], 422);
            }
        }

        $labels = collect($validated['items'] ?? [])
            ->map(fn ($row) => trim((string) ($row['label'] ?? '')))
            ->filter()
            ->values();

        DB::transaction(function () use ($typeId, $subtypeId, $labels) {
            $query = SurgeryChecklistTemplate::query()->where('surgery_type_id', $typeId);
            if ($subtypeId) {
                $query->where('surgery_subtype_id', $subtypeId);
            } else {
                $query->whereNull('surgery_subtype_id');
            }

            $template = $query->first();
            if (! $template) {
                $template = SurgeryChecklistTemplate::create([
                    'surgery_type_id' => $typeId,
                    'surgery_subtype_id' => $subtypeId,
                ]);
            }

            $template->items()->delete();

            foreach ($labels as $index => $label) {
                SurgeryChecklistTemplateItem::create([
                    'template_id' => $template->id,
                    'label' => $label,
                    'sort_order' => $index,
                ]);
            }
        });

        return response()->json(['ok' => true, 'message' => 'چک‌لیست ذخیره شد.']);
    }
}
