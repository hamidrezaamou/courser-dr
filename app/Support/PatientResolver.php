<?php

namespace App\Support;

use App\Models\Patient;
use Illuminate\Validation\ValidationException;

class PatientResolver
{
    /**
     * Find or create a patient for booking forms.
     *
     * National code is the unique key. Existing files are never overwritten
     * from visit/surgery registration — identity edits belong on the patient file.
     *
     * @param  array<string, mixed>  $validated  Must include patient_name, mobile; national_code and no_national_code optional
     */
    public static function resolve(array $validated): Patient
    {
        $noNationalCode = (bool) ($validated['no_national_code'] ?? false);
        $nationalCode = $noNationalCode ? null : ($validated['national_code'] ?? null);

        if ($nationalCode) {
            $patient = Patient::query()->where('national_code', $nationalCode)->first();
            if ($patient) {
                return $patient;
            }
        }

        Patient::ensureMobileSecondaryColumn();

        $attrs = [
            'name' => $validated['patient_name'],
            'national_code' => $nationalCode,
            'mobile' => $validated['mobile'],
            'age' => $validated['age'] ?? null,
        ];
        if (array_key_exists('mobile_secondary', $validated)) {
            $attrs['mobile_secondary'] = $validated['mobile_secondary'] ?: null;
        }

        return Patient::create($attrs);
    }

    /**
     * Booking rows copy identity from the patient file after resolve,
     * so a mismatched typed name cannot stick on the appointment either.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function syncValidatedFromPatient(Patient $patient, array $validated): array
    {
        $validated['patient_name'] = $patient->name;
        $validated['national_code'] = $patient->national_code;
        $validated['mobile'] = $patient->mobile;
        $validated['age'] = $patient->age;
        $validated['mobile_secondary'] = $patient->mobile_secondary;
        $validated['no_national_code'] = blank($patient->national_code);

        return $validated;
    }

    /**
     * Normalize + validate national_code / no_national_code on a validated payload.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function applyNationalCodeRules(array $validated, bool $allowMissing = true): array
    {
        $noNationalCode = filter_var($validated['no_national_code'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($noNationalCode) {
            if (! $allowMissing) {
                throw ValidationException::withMessages([
                    'national_code' => 'کد ملی الزامی است.',
                ]);
            }
            $validated['no_national_code'] = true;
            $validated['national_code'] = null;

            return $validated;
        }

        $validated['no_national_code'] = false;
        $raw = $validated['national_code'] ?? null;
        $digits = Digits::only(is_string($raw) ? $raw : (string) $raw);
        $normalized = IranianId::normalizeNationalCode($digits !== '' ? $digits : null);

        if ($normalized === null || strlen($digits) !== 10) {
            throw ValidationException::withMessages([
                'national_code' => 'کد ملی باید دقیقاً ۱۰ رقم باشد.',
            ]);
        }

        $validated['national_code'] = $normalized;

        return $validated;
    }

    /**
     * @return array<int, mixed>
     */
    public static function nationalCodeValidationRules(bool $requiredWhenHasCode = true): array
    {
        return [
            'no_national_code' => ['nullable', 'boolean'],
            'national_code' => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail) use ($requiredWhenHasCode): void {
                    $noCode = filter_var(request()->input('no_national_code'), FILTER_VALIDATE_BOOLEAN);
                    if ($noCode) {
                        return;
                    }
                    if (! $requiredWhenHasCode && ($value === null || $value === '')) {
                        return;
                    }
                    $digits = Digits::only((string) $value);
                    if (strlen($digits) !== 10) {
                        $fail('کد ملی باید دقیقاً ۱۰ رقم باشد.');
                    }
                },
            ],
        ];
    }

    public static function displayNationalCode(?string $code): string
    {
        $code = trim((string) $code);

        return $code === '' ? 'فاقد کد ملی' : $code;
    }
}
