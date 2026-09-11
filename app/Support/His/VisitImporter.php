<?php

namespace App\Support\His;

use App\Models\Appointment;
use App\Models\Visit;
use Carbon\Carbon;
use RuntimeException;
use Throwable;

class VisitImporter extends HisImporter
{
    protected function importRow(array $row): string
    {
        $hisId = $this->text($row['his_id'] ?? null);
        if ($hisId === null) {
            throw new RuntimeException('his_id is required.');
        }

        $patient = HisPatientMatcher::resolve($row);

        $visit = Visit::query()->where('his_admission_id', $hisId)->first();
        $existed = $visit !== null;

        $attributes = [
            'patient_id' => $patient->id,
            'history' => $this->text($row['history'] ?? null),
            'examination' => $this->text($row['examination'] ?? null),
            'diagnosis' => $this->text($row['diagnosis'] ?? null),
            'treatment' => $this->text($row['treatment'] ?? null),
            'next_instruction' => $this->text($row['next_instruction'] ?? null),
            'eye_side' => $this->text($row['eye_side'] ?? null, 10),
            'va_right' => $this->text($row['va_right'] ?? null, 50),
            'va_left' => $this->text($row['va_left'] ?? null, 50),
            'iop_right' => $this->text($row['iop_right'] ?? null, 50),
            'iop_left' => $this->text($row['iop_left'] ?? null, 50),
            'his_admission_id' => $hisId,
            'source' => 'his',
            'his_synced_at' => now(),
        ];

        // Link to the appointment this encounter came from, when HIS tells us.
        $appointmentHisId = $this->text($row['his_appointment_id'] ?? null);
        if ($appointmentHisId !== null) {
            $appointment = Appointment::query()
                ->where('his_appointment_id', $appointmentHisId)
                ->first();

            if ($appointment && $appointment->patient_id === $patient->id) {
                $attributes['appointment_id'] = $appointment->id;
            }
        }

        if ($existed) {
            $visit->fill($attributes)->save();
        } else {
            $visit = Visit::create($attributes);
        }

        // `visits` has no encounter date of its own; created_at is what the
        // patient file orders by, so it has to reflect the HIS visit date.
        $visitedAt = $this->timestamp($row['visited_at'] ?? null);
        if ($visitedAt !== null && ! $visit->created_at?->equalTo($visitedAt)) {
            $visit->forceFill(['created_at' => $visitedAt])->save();
        }

        return $existed ? 'updated' : 'created';
    }

    private function timestamp(mixed $value): ?Carbon
    {
        $raw = $this->text($value);
        if ($raw === null) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (Throwable) {
            return null;
        }
    }
}
