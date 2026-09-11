<?php

namespace App\Support;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PatientJsonImporter
{
    /**
     * @return array{created:int,skipped:int,invalid:int,errors:list<string>}
     */
    public static function import(array $rows): array
    {
        $stats = [
            'created' => 0,
            'skipped' => 0,
            'invalid' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                $stats['invalid']++;
                $stats['errors'][] = 'ردیف '.($index + 1).': ساختار نامعتبر.';

                continue;
            }

            try {
                $result = self::importRow($row);
                $stats[$result]++;
            } catch (\Throwable $error) {
                $stats['invalid']++;
                $stats['errors'][] = 'ردیف '.($index + 1).': '.$error->getMessage();
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return 'created'|'skipped'
     */
    private static function importRow(array $row): string
    {
        $name = self::resolveName($row);
        $nationalCode = self::normalizeNationalCodeFromImport(
            $row['NationalCode'] ?? $row['national_code'] ?? $row['nationalCode'] ?? null
        );
        $mobile = IranianId::normalizeMobile(
            (string) ($row['MobileTel'] ?? $row['mobile'] ?? $row['Mobile'] ?? '')
        );

        if ($name === null || $name === '') {
            throw new RuntimeException('نام بیمار موجود نیست.');
        }

        if ($nationalCode === null) {
            throw new RuntimeException('کد ملی نامعتبر است.');
        }

        if ($mobile === null) {
            throw new RuntimeException('شماره موبایل نامعتبر است.');
        }

        if (
            Patient::query()->where('national_code', $nationalCode)->exists()
            || User::query()->where('national_code', $nationalCode)->exists()
            || User::query()->where('mobile', $mobile)->exists()
        ) {
            return 'skipped';
        }

        DB::transaction(function () use ($name, $nationalCode, $mobile): void {
            Patient::create([
                'name' => $name,
                'national_code' => $nationalCode,
                'mobile' => $mobile,
            ]);

            User::create([
                'name' => $name,
                'national_code' => $nationalCode,
                'mobile' => $mobile,
                'role' => User::ROLE_PATIENT,
                'password' => Hash::make($mobile),
            ]);
        });

        return 'created';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function resolveName(array $row): ?string
    {
        $full = trim((string) ($row['FullName'] ?? $row['full_name'] ?? $row['name'] ?? ''));
        if ($full !== '') {
            return preg_replace('/\s+/u', ' ', $full) ?: null;
        }

        $first = trim((string) ($row['FirstName'] ?? $row['first_name'] ?? ''));
        $last = trim((string) ($row['LastName'] ?? $row['last_name'] ?? ''));
        $combined = trim($first.' '.$last);

        return $combined !== '' ? preg_replace('/\s+/u', ' ', $combined) : null;
    }

    public static function normalizeNationalCodeFromImport(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = Digits::only((string) $value);
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 9) {
            $digits = '0'.$digits;
        }

        return IranianId::normalizeNationalCode($digits);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function decodePayload(string $raw): array
    {
        $raw = self::normalizeJsonText($raw);

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('فایل JSON معتبر نیست. '.json_last_error_msg());
        }

        if ($decoded === []) {
            return [];
        }

        if (array_is_list($decoded)) {
            return $decoded;
        }

        if (isset($decoded['data']) && is_array($decoded['data'])) {
            return array_is_list($decoded['data']) ? $decoded['data'] : [$decoded['data']];
        }

        if (isset($decoded['patients']) && is_array($decoded['patients'])) {
            return array_is_list($decoded['patients']) ? $decoded['patients'] : [$decoded['patients']];
        }

        return [$decoded];
    }

    private static function normalizeJsonText(string $raw): string
    {
        // PowerShell / Windows often exports UTF-8 with BOM — breaks json_decode.
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        return trim($raw);
    }
}
