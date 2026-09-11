<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use Illuminate\Support\Facades\Schema;

/**
 * Keep denormalized name/mobile/national_code snapshots on bookings in sync
 * when the patient chart identity fields change.
 */
class PatientIdentitySync
{
    public static function syncFromPatient(Patient $patient): void
    {
        $payload = [
            'patient_name' => $patient->name,
            'mobile' => $patient->mobile,
            'national_code' => $patient->national_code,
        ];

        if (array_key_exists('age', $patient->getAttributes()) && $patient->age !== null) {
            $payload['age'] = $patient->age;
        }

        $today = now()->toDateString();
        $baseVisit = [
            'patient_name' => $payload['patient_name'],
            'mobile' => $payload['mobile'],
            'national_code' => $payload['national_code'],
        ];
        if (isset($payload['age'])) {
            $baseVisit['age'] = $payload['age'];
        }

        $visitPayload = $baseVisit;
        $surgeryPayload = $baseVisit;
        if (Schema::hasColumn('appointments', 'mobile_secondary')) {
            $visitPayload['mobile_secondary'] = $patient->mobile_secondary;
        }
        if (Schema::hasColumn('surgery_appointments', 'mobile_secondary')) {
            $surgeryPayload['mobile_secondary'] = $patient->mobile_secondary;
        }

        Appointment::query()
            ->where('patient_id', $patient->id)
            ->whereDate('scheduled_date', '>=', $today)
            ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::DONE, BookingStatus::NO_SHOW])
            ->update($visitPayload);

        SurgeryAppointment::query()
            ->where('patient_id', $patient->id)
            ->whereDate('scheduled_date', '>=', $today)
            ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::DONE, BookingStatus::NO_SHOW])
            ->update($surgeryPayload);
    }
}
