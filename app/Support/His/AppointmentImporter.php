<?php

namespace App\Support\His;

use App\Models\Appointment;
use App\Support\SlotLabel;
use Carbon\Carbon;
use RuntimeException;
use Throwable;

class AppointmentImporter extends HisImporter
{
    protected function importRow(array $row): string
    {
        $hisId = $this->text($row['his_id'] ?? null);
        if ($hisId === null) {
            throw new RuntimeException('his_id is required.');
        }

        $date = $this->text($row['scheduled_date'] ?? null);
        if ($date === null) {
            throw new RuntimeException('scheduled_date is required.');
        }

        try {
            $scheduledDate = Carbon::parse($date)->toDateString();
        } catch (Throwable) {
            throw new RuntimeException("scheduled_date is not a valid date: {$date}");
        }

        $patient = HisPatientMatcher::resolve($row);

        $appointment = Appointment::query()->where('his_appointment_id', $hisId)->first();
        $existed = $appointment !== null;

        $attributes = [
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'national_code' => $patient->national_code,
            'mobile' => $patient->mobile,
            'age' => $patient->age,
            'visit_type' => $this->text($row['visit_type'] ?? null),
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $this->slot($row['scheduled_time'] ?? null),
            'reason' => $this->text($row['reason'] ?? null, 255),
            'notes' => $this->text($row['notes'] ?? null),
            'status' => HisStatus::forAppointment($row['status'] ?? null),
            'his_appointment_id' => $hisId,
            'source' => 'his',
            'his_synced_at' => now(),
        ];

        if ($existed) {
            $appointment->fill($attributes)->save();
        } else {
            Appointment::create($attributes);
        }

        return $existed ? 'updated' : 'created';
    }

    /**
     * `scheduled_time` is a VARCHAR that also carries queue tokens (Q01..Q99),
     * so everything has to go through the same normaliser the UI uses.
     */
    private function slot(mixed $value): ?string
    {
        $raw = $this->text($value);
        if ($raw === null) {
            return null;
        }

        try {
            return SlotLabel::normalize($raw);
        } catch (Throwable) {
            // An unparseable time should not cost us the appointment.
            return null;
        }
    }
}
