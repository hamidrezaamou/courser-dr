<?php

namespace App\Http\Controllers;

use App\Models\FollowUpReminder;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Services\PatientFollowUpService;
use App\Support\FeatureFlags;
use App\Support\Jalali;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FollowUpReminderController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        abort_unless(FeatureFlags::enabled('features.followup_reminders'), 404);

        $validated = $request->validate([
            'days_after' => ['nullable', 'integer', 'min:1', 'max:180'],
            'intervals' => ['nullable', 'array', 'min:1'],
            'intervals.*' => ['integer', 'min:1', 'max:180'],
            'surgery_appointment_id' => ['nullable', 'integer', 'exists:surgery_appointments,id'],
            'label_prefix' => ['nullable', 'string', 'max:80'],
        ]);

        $intervals = [];
        if (! empty($validated['intervals']) && is_array($validated['intervals'])) {
            $intervals = array_values(array_unique(array_map('intval', $validated['intervals'])));
        } elseif (! empty($validated['days_after'])) {
            $intervals = [(int) $validated['days_after']];
        }

        if ($intervals === []) {
            return back()->withErrors(['days_after' => 'بازه مراجعه بعدی را انتخاب کنید.']);
        }

        $surgeryId = $validated['surgery_appointment_id'] ?? null;
        if ($surgeryId) {
            $surgery = SurgeryAppointment::query()->findOrFail($surgeryId);
            abort_unless((int) $surgery->patient_id === (int) $patient->id, 404);
        }

        $prefix = trim((string) ($validated['label_prefix'] ?? ''));
        $created = 0;

        foreach ($intervals as $days) {
            $dueDate = now()->startOfDay()->addDays($days);
            $remindAt = (clone $dueDate)->subDays(min(2, max(0, $days - 1)));
            if ($remindAt->lt(now()->startOfDay())) {
                $remindAt = now()->startOfDay();
            }

            $dueJalali = Jalali::format($dueDate, 'Y/m/d');
            $label = $prefix !== ''
                ? "{$prefix} · پیگیری {$days} روزه ({$dueJalali})"
                : "پیگیری مراجعه · {$days} روز بعد ({$dueJalali})";

            $reminder = FollowUpReminder::create([
                'patient_id' => $patient->id,
                'created_by' => $request->user()->id,
                'due_date' => $dueDate->toDateString(),
                'remind_at' => $remindAt->toDateString(),
                'status' => 'pending',
                'message' => $label,
            ]);
            app(PatientFollowUpService::class)->syncReminder($reminder);
            $created++;
        }

        $msg = $created === 1
            ? 'مراجعه بعدی ثبت شد و در لیست یادآوری‌ها قرار گرفت.'
            : "{$created} یادآوری پیگیری ثبت شد.";

        return back()->with('success', $msg);
    }

    public function destroy(Patient $patient, FollowUpReminder $reminder): RedirectResponse
    {
        abort_unless(FeatureFlags::enabled('features.followup_reminders'), 404);

        abort_unless($reminder->patient_id === $patient->id, 404);
        $reminder->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        app(PatientFollowUpService::class)->syncReminder($reminder->fresh());

        return back()->with('success', 'یادآوری مراجعه بعدی لغو شد.');
    }
}
