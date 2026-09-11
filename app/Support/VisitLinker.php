<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\SurgeryAppointment;
use App\Models\Visit;

/**
 * Ensures a clinical Visit exists when a booking enters consult / is completed,
 * so floor "Done / in_consult" is not orphaned from the patient chart.
 */
class VisitLinker
{
    public static function ensureForAppointment(Appointment $appointment, ?int $userId = null): ?Visit
    {
        if (! $appointment->patient_id) {
            return null;
        }

        $existing = Visit::query()
            ->where('appointment_id', $appointment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $visit = Visit::create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'created_by' => $userId,
        ]);

        ActivityLogger::log($visit, 'created', null, [
            'appointment_id' => $appointment->id,
            'source' => 'booking_link',
        ]);

        return $visit;
    }

    public static function ensureForSurgery(SurgeryAppointment $surgery, ?int $userId = null): ?Visit
    {
        if (! $surgery->patient_id) {
            return null;
        }

        $existing = Visit::query()
            ->where('surgery_appointment_id', $surgery->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $visit = Visit::create([
            'patient_id' => $surgery->patient_id,
            'surgery_appointment_id' => $surgery->id,
            'eye_side' => EyeSide::normalize($surgery->eye_side),
            'created_by' => $userId,
        ]);

        ActivityLogger::log($visit, 'created', null, [
            'surgery_appointment_id' => $surgery->id,
            'source' => 'booking_link',
        ]);

        return $visit;
    }

    public static function shouldLinkOnStatus(string $status): bool
    {
        return in_array($status, [BookingStatus::IN_CONSULT, BookingStatus::DONE], true);
    }
}
