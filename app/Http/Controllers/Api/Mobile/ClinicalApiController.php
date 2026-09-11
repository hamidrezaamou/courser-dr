<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Visit;
use App\Support\ActivityLogger;
use App\Support\EyeSide;
use App\Support\His\HisLock;
use App\Support\MobilePayload;
use App\Support\StaffNoteAlerts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicalApiController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function examRules(): array
    {
        return [
            'history' => ['nullable', 'string'],
            'examination' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'next_instruction' => ['nullable', 'string'],
            'eye_side' => ['nullable', 'string', 'in:OD,OS,OU,راست,چپ,دو طرفه,دوطرفه'],
            'va_right' => ['nullable', 'string', 'max:50'],
            'va_left' => ['nullable', 'string', 'max:50'],
            'iop_right' => ['nullable', 'string', 'max:50'],
            'iop_left' => ['nullable', 'string', 'max:50'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'surgery_appointment_id' => ['nullable', 'integer', 'exists:surgery_appointments,id'],
        ];
    }

    public function storeExam(Request $request, Patient $patient): JsonResponse
    {
        abort_unless($request->user()?->canManageClinical(), 403);

        $validated = $request->validate($this->examRules());
        if (array_key_exists('eye_side', $validated)) {
            $validated['eye_side'] = EyeSide::normalize($validated['eye_side'] ?? null);
        }

        $visit = $patient->visits()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        ActivityLogger::log($visit, 'created', null, $validated);

        return response()->json([
            'ok' => true,
            'message' => 'معاینه ثبت شد.',
            'visit' => MobilePayload::visit($visit->load('creator'), true),
        ], 201);
    }

    public function storeNote(Request $request, Patient $patient): JsonResponse
    {
        abort_unless($request->user()?->canManageClinical(), 403);

        $validated = $request->validate([
            'note' => ['required', 'string'],
        ]);

        $note = $patient->internalNotes()->create([
            'user_id' => $request->user()->id,
            'note' => $validated['note'],
        ]);

        StaffNoteAlerts::notifyOthers($note);

        return response()->json([
            'ok' => true,
            'message' => 'یادداشت داخلی ثبت شد.',
            'note' => [
                'id' => $note->id,
                'note' => $note->note,
                'creator_name' => $request->user()->name,
            ],
        ], 201);
    }

    public function updateExam(Request $request, Patient $patient, Visit $visit): JsonResponse
    {
        abort_unless($request->user()?->canManageClinical(), 403);
        abort_unless($visit->patient_id === $patient->id, 404);
        HisLock::guard($visit);

        $validated = $request->validate($this->examRules());
        if (array_key_exists('eye_side', $validated)) {
            $validated['eye_side'] = EyeSide::normalize($validated['eye_side'] ?? null);
        }

        $visit->update([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'معاینه به‌روزرسانی شد.',
            'visit' => MobilePayload::visit($visit->fresh()->load('creator'), true),
        ]);
    }

    public function destroyExam(Request $request, Patient $patient, Visit $visit): JsonResponse
    {
        abort_unless($request->user()?->canManageClinical(), 403);
        abort_unless($visit->patient_id === $patient->id, 404);
        HisLock::guard($visit);
        $visit->delete();

        return response()->json(['ok' => true, 'message' => 'معاینه حذف شد.']);
    }

    public function destroyNote(Request $request, Patient $patient, \App\Models\InternalNote $note): JsonResponse
    {
        abort_unless($request->user()?->canManageClinical(), 403);
        abort_unless($note->patient_id === $patient->id, 404);
        $note->delete();

        return response()->json(['ok' => true, 'message' => 'یادداشت حذف شد.']);
    }
}
