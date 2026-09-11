<?php

namespace App\Support\His;

use App\Models\Patient;
use App\Support\Digits;
use App\Support\IranianId;
use RuntimeException;

class HisPatientMatcher
{
    /**
     * Find or create the patient a HIS row belongs to.
     *
     * Order matters. The HIS id is the only truly stable key, so it wins.
     * National code is the fallback that links a patient the staff already
     * typed into the website by hand. An empty national code must never be
     * used to match: the column is nullable and MySQL allows many NULLs in a
     * unique index, so matching on it would merge unrelated people.
     *
     * @param  array<string, mixed>  $row
     */
    public static function resolve(array $row): Patient
    {
        $hisId = self::text($row['his_patient_id'] ?? null);
        $nationalCode = self::normalizeNationalCode($row['national_code'] ?? null);
        $name = self::text($row['patient_name'] ?? $row['name'] ?? null);
        $mobile = self::text($row['mobile'] ?? null);

        $patient = null;

        if ($hisId !== null) {
            $patient = Patient::query()->where('his_patient_id', $hisId)->first();
        }

        if ($patient === null && $nationalCode !== null) {
            $patient = Patient::query()->where('national_code', $nationalCode)->first();
        }

        // Finance/service rows often omit the name when the patient was synced
        // earlier. Only demand a name when we would have to create someone new.
        if ($patient === null && $name === null) {
            throw new RuntimeException('Patient name is missing.');
        }

        $attributes = array_filter([
            'name' => $name,
            'mobile' => $mobile,
            'age' => self::text($row['age'] ?? null),
        ], static fn ($value) => $value !== null);

        if ($patient !== null) {
            // HIS is the source of truth, so its values win on conflict.
            $attributes['source'] = 'his';
            $attributes['his_synced_at'] = now();
            if ($hisId !== null && $patient->his_patient_id === null) {
                $attributes['his_patient_id'] = $hisId;
            }
            if ($nationalCode !== null && (string) $patient->national_code === '') {
                $attributes['national_code'] = $nationalCode;
            }

            $patient->fill($attributes)->save();

            return $patient;
        }

        return Patient::create($attributes + [
            'national_code' => $nationalCode ?? self::syntheticNationalCode($hisId),
            'mobile' => $mobile ?? '',
            'his_patient_id' => $hisId,
            'source' => 'his',
            'his_synced_at' => now(),
        ]);
    }

    /**
     * Returns a valid 10 digit code, or null when the value is unusable.
     */
    public static function normalizeNationalCode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = Digits::only((string) $value);
        if (strlen($digits) !== 10) {
            return null;
        }

        return IranianId::normalizeNationalCode($digits);
    }

    /**
     * HIS patients with no national code still need a unique key, mirroring the
     * `wp` prefix the WordPress importer already uses.
     */
    public static function syntheticNationalCode(?string $hisId): ?string
    {
        if ($hisId === null) {
            return null;
        }

        $prefix = (string) config('his.synthetic_national_code_prefix', 'his');

        return substr($prefix.$hisId, 0, 20);
    }

    private static function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
