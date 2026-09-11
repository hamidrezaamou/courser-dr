<?php

namespace App\Http\Controllers;

use App\Models\PatientSurgeryChecklist;
use App\Models\PatientSurgeryChecklistItem;
use App\Models\SurgeryAppointment;
use App\Support\SurgeryChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientSurgeryChecklistController extends Controller
{
    private function requireAvailable(): ?JsonResponse
    {
        if (! SurgeryChecklist::isAvailable()) {
            return response()->json(['message' => 'چک‌لیست هنوز فعال نشده.'], 503);
        }

        return null;
    }

    /**
     * Checked items are locked to the checker — others cannot edit, delete, or uncheck.
     */
    private function denyUnlessOwnCheckedItem(PatientSurgeryChecklistItem $item, string $action): ?JsonResponse
    {
        if (! $item->checked_at) {
            return null;
        }

        if ((int) $item->checked_by === (int) auth()->id()) {
            return null;
        }

        $who = $item->checker?->name ?: 'تیک‌زننده';

        return response()->json([
            'message' => "فقط {$who} می‌تواند این مورد را {$action}.",
        ], 403);
    }

    public function ensure(SurgeryAppointment $surgeryAppointment): JsonResponse
    {
        if ($blocked = $this->requireAvailable()) {
            return $blocked;
        }

        try {
            $checklist = SurgeryChecklist::ensureForAppointment($surgeryAppointment, auth()->id());
        } catch (\Throwable $error) {
            report($error);

            return response()->json([
                'message' => 'خطا در آماده‌سازی چک‌لیست: '.$error->getMessage(),
            ], 500);
        }

        return response()->json(SurgeryChecklist::toPayload($checklist->fresh(['items.checker'])));
    }

    public function show(PatientSurgeryChecklist $checklist): JsonResponse
    {
        if ($blocked = $this->requireAvailable()) {
            return $blocked;
        }

        $checklist->load(['items.checker']);

        return response()->json(SurgeryChecklist::toPayload($checklist));
    }

    public function toggle(Request $request, PatientSurgeryChecklist $checklist, PatientSurgeryChecklistItem $item): JsonResponse
    {
        if ($blocked = $this->requireAvailable()) {
            return $blocked;
        }

        abort_unless((int) $item->checklist_id === (int) $checklist->id, 404);

        if ($item->checked_at) {
            if ($denied = $this->denyUnlessOwnCheckedItem($item, 'برداشتن تیک کند')) {
                return $denied;
            }

            $item->update(['checked_at' => null, 'checked_by' => null]);
        } else {
            $item->update(['checked_at' => now(), 'checked_by' => auth()->id()]);
        }

        return response()->json(SurgeryChecklist::toPayload($checklist->fresh(['items.checker'])));
    }

    public function storeItem(Request $request, PatientSurgeryChecklist $checklist): JsonResponse
    {
        if ($blocked = $this->requireAvailable()) {
            return $blocked;
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        $max = (int) $checklist->items()->max('sort_order');

        PatientSurgeryChecklistItem::create([
            'checklist_id' => $checklist->id,
            'label' => trim($validated['label']),
            'sort_order' => $max + 1,
            'is_custom' => true,
        ]);

        return response()->json(SurgeryChecklist::toPayload($checklist->fresh(['items.checker'])));
    }

    public function updateItem(Request $request, PatientSurgeryChecklist $checklist, PatientSurgeryChecklistItem $item): JsonResponse
    {
        if ($blocked = $this->requireAvailable()) {
            return $blocked;
        }

        abort_unless((int) $item->checklist_id === (int) $checklist->id, 404);

        if ($denied = $this->denyUnlessOwnCheckedItem($item, 'ویرایش کند')) {
            return $denied;
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        $item->update(['label' => trim($validated['label'])]);

        return response()->json(SurgeryChecklist::toPayload($checklist->fresh(['items.checker'])));
    }

    public function destroyItem(PatientSurgeryChecklist $checklist, PatientSurgeryChecklistItem $item): JsonResponse
    {
        if ($blocked = $this->requireAvailable()) {
            return $blocked;
        }

        abort_unless((int) $item->checklist_id === (int) $checklist->id, 404);

        if ($denied = $this->denyUnlessOwnCheckedItem($item, 'حذف کند')) {
            return $denied;
        }

        $item->delete();

        return response()->json(SurgeryChecklist::toPayload($checklist->fresh(['items.checker'])));
    }

    public function confirm(PatientSurgeryChecklist $checklist): JsonResponse
    {
        if ($blocked = $this->requireAvailable()) {
            return $blocked;
        }

        SurgeryChecklist::markSaved($checklist);

        return response()->json([
            'ok' => true,
            'message' => 'چک‌لیست در پرونده ثبت شد.',
            'checklist' => SurgeryChecklist::toPayload($checklist->fresh(['items.checker'])),
        ]);
    }
}
