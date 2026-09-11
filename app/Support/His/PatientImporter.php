<?php

namespace App\Support\His;

use App\Models\Patient;
use RuntimeException;

class PatientImporter extends HisImporter
{
    protected function importRow(array $row): string
    {
        $hisId = $this->text($row['his_id'] ?? null);
        if ($hisId === null) {
            throw new RuntimeException('his_id is required.');
        }

        $existing = Patient::query()->where('his_patient_id', $hisId)->exists();

        // The matcher already handles linking by national code and updating.
        HisPatientMatcher::resolve($row + ['his_patient_id' => $hisId]);

        return $existing ? 'updated' : 'created';
    }
}
