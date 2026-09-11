<?php

namespace App\Console\Commands;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Models\SurgeryType;
use App\Support\BookingStatus;
use App\Support\Jalali;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportWpSurgeryAppointments extends Command
{
    protected $signature = 'import:wp-surgery
        {path? : مسیر فایل wp_sapp_appointments.sql}
        {--sql-only : فقط فایل SQL خروجی بساز، داخل دیتابیس نریز}
        {--fresh : نوبت‌های قبلی ایمپورت‌شده را پاک کن (بیماران با کد ملی wp-* )}';

    protected $description = 'تبدیل دیتای وردپرس sapp_appointments به patients + surgery_appointments';

    /** @var array<int, string> نام اختیاری بیمارستان‌ها بر اساس id وردپرس */
    private array $hospitalNames = [
        // اگر فایل hospitals داشتید اینجا یا با --map پر کنید
    ];

    public function handle(): int
    {
        $path = $this->argument('path')
            ?: 'c:/Users/elahe/Downloads/wp_sapp_appointments.sql';

        if (! is_file($path)) {
            $this->error("فایل پیدا نشد: {$path}");

            return self::FAILURE;
        }

        $this->info('در حال خواندن فایل...');
        $rows = $this->parseDump($path);
        $this->info('تعداد ردیف‌های معتبر: '.count($rows));

        if ($rows === []) {
            $this->error('هیچ ردیفی پارس نشد.');

            return self::FAILURE;
        }

        $built = $this->buildDataset($rows);
        $outSql = storage_path('app/imports/wp_surgery_import_'.date('Ymd_His').'.sql');
        File::ensureDirectoryExists(dirname($outSql));
        File::put($outSql, $this->renderMysql($built));
        $this->info("فایل SQL آماده ایمپورت: {$outSql}");

        if ($this->option('sql-only')) {
            $this->table(
                ['بخش', 'تعداد'],
                [
                    ['بیمارستان', count($built['hospitals'])],
                    ['نوع عمل', count($built['surgery_types'])],
                    ['بیمار', count($built['patients'])],
                    ['نوبت عمل', count($built['surgeries'])],
                ]
            );

            return self::SUCCESS;
        }

        $this->importToDatabase($built);
        $this->info('ایمپورت داخل دیتابیس فعلی انجام شد.');
        $this->line('بیماران: '.Patient::count().' | نوبت عمل: '.SurgeryAppointment::count().' | بیمارستان: '.Hospital::count());

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseDump(string $path): array
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            return [];
        }

        if (! preg_match_all('/INSERT INTO `wp_sapp_appointments`[^;]*VALUES\s*(.+?);/is', $sql, $blocks)) {
            return [];
        }

        $all = [];
        foreach ($blocks[1] as $valuesBlob) {
            foreach ($this->splitValueRows($valuesBlob) as $rowSql) {
                $fields = $this->parseFields($rowSql);
                if (count($fields) < 21) {
                    continue;
                }
                $all[] = [
                    'id' => (int) trim($fields[0]),
                    'hospital_id' => (int) trim($fields[1]),
                    'date_key' => trim($fields[2]),
                    'display_date' => trim($fields[3]),
                    'slot_number' => (int) trim($fields[4]),
                    'slot_display' => trim($fields[5]),
                    'surgery_type' => trim($fields[6]),
                    'eye_type' => trim($fields[7]),
                    'patient_name' => trim($fields[8]),
                    'phone' => trim($fields[9]),
                    'national_code' => trim($fields[10]),
                    'description' => trim($fields[11]),
                    'created_at' => trim($fields[12]) ?: now()->toDateTimeString(),
                    'note' => trim($fields[13]),
                    'status' => trim($fields[14]) ?: 'active',
                    'time' => trim($fields[15]),
                    'patient_photo' => trim($fields[16]),
                    'patient_documents' => trim($fields[17]),
                    'hide_full_dates' => (int) trim($fields[18]),
                    'is_emergency' => (int) trim($fields[19]),
                    'phone2' => trim($fields[20]),
                ];
            }
        }

        return $all;
    }

    /**
     * @return list<string>
     */
    private function splitValueRows(string $values): array
    {
        $rows = [];
        $buf = '';
        $inQuote = false;
        $escape = false;
        $len = strlen($values);

        for ($i = 0; $i < $len; $i++) {
            $ch = $values[$i];
            if ($escape) {
                $buf .= $ch;
                $escape = false;
                continue;
            }
            if ($ch === '\\') {
                $buf .= $ch;
                $escape = true;
                continue;
            }
            if ($ch === "'") {
                $inQuote = ! $inQuote;
                $buf .= $ch;
                continue;
            }
            if (! $inQuote && $ch === ')') {
                $buf .= ')';
                $trimmed = trim($buf);
                if (str_starts_with($trimmed, '(')) {
                    $rows[] = $trimmed;
                }
                $buf = '';
                while ($i + 1 < $len && (ctype_space($values[$i + 1]) || $values[$i + 1] === ',')) {
                    $i++;
                }
                continue;
            }
            $buf .= $ch;
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function parseFields(string $row): array
    {
        $row = trim($row);
        if (str_starts_with($row, '(')) {
            $row = substr($row, 1);
        }
        if (str_ends_with($row, ')')) {
            $row = substr($row, 0, -1);
        }

        $fields = [];
        $buf = '';
        $inQuote = false;
        $escape = false;
        $len = strlen($row);

        for ($i = 0; $i < $len; $i++) {
            $ch = $row[$i];
            if ($escape) {
                $buf .= $ch;
                $escape = false;
                continue;
            }
            if ($inQuote && $ch === '\\') {
                $next = $row[$i + 1] ?? '';
                if (in_array($next, ["'", '\\', 'n', 'r', 't', '0'], true)) {
                    $buf .= match ($next) {
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                        '0' => "\0",
                        default => $next,
                    };
                    $i++;
                    continue;
                }
                $buf .= $ch;
                continue;
            }
            if ($ch === "'") {
                if ($inQuote && ($row[$i + 1] ?? '') === "'") {
                    $buf .= "'";
                    $i++;
                    continue;
                }
                $inQuote = ! $inQuote;
                continue;
            }
            if (! $inQuote && $ch === ',') {
                $fields[] = $buf;
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        $fields[] = $buf;

        return $fields;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{hospitals: array<int, array>, surgery_types: array<int, array>, patients: array<string, array>, surgeries: list<array>}
     */
    private function buildDataset(array $rows): array
    {
        $hospitals = [];
        $types = [];
        $patients = [];
        $surgeries = [];

        foreach ($rows as $row) {
            $hid = (int) $row['hospital_id'];
            if (! isset($hospitals[$hid])) {
                $hospitals[$hid] = [
                    'id' => $hid,
                    'name' => $this->hospitalNames[$hid] ?? ('بیمارستان #'.$hid),
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
            }

            $typeName = $row['surgery_type'] !== '' ? $row['surgery_type'] : 'نامشخص';
            $typeKey = mb_strtolower($typeName);
            if (! isset($types[$typeKey])) {
                $types[$typeKey] = [
                    'name' => $typeName,
                    'is_active' => 1,
                    'sort_order' => count($types),
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
            }

            $mobile = $this->normalizeMobile((string) $row['phone']);
            $mobile2 = $this->normalizeMobile((string) $row['phone2']);
            $nc = $this->normalizeNationalCode((string) $row['national_code']);
            $name = $row['patient_name'] !== '' ? $row['patient_name'] : ('بیمار '.$row['id']);

            $patientKey = $nc !== ''
                ? 'nc:'.$nc
                : ($mobile !== '' ? 'mob:'.$mobile.'|'.mb_strtolower($name) : 'wp:'.$row['id']);

            if (! isset($patients[$patientKey])) {
                if ($nc === '') {
                    $nc = 'wp'.str_pad((string) $row['id'], 8, '0', STR_PAD_LEFT);
                    // keep unique but readable
                    if (strlen($nc) > 20) {
                        $nc = 'wp'.$row['id'];
                    }
                }
                $patients[$patientKey] = [
                    'key' => $patientKey,
                    'name' => $name,
                    'national_code' => $nc,
                    'mobile' => $mobile !== '' ? $mobile : ('09'.str_pad((string) ($row['id'] % 1000000000), 9, '0', STR_PAD_LEFT)),
                    'age' => null,
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['created_at'],
                ];
            } else {
                // enrich missing fields
                if ($patients[$patientKey]['name'] === '' && $name !== '') {
                    $patients[$patientKey]['name'] = $name;
                }
                if ($mobile !== '' && str_starts_with($patients[$patientKey]['mobile'], '09') === false) {
                    $patients[$patientKey]['mobile'] = $mobile;
                }
            }

            try {
                $scheduledDate = Jalali::parseJalaliDate($row['date_key'])->toDateString();
            } catch (\Throwable $e) {
                $this->warn("ردیف {$row['id']}: تاریخ نامعتبر «{$row['date_key']}» — رد شد");
                continue;
            }

            $notes = trim(implode("\n", array_filter([
                $row['description'] !== '' ? $row['description'] : null,
                $row['note'] !== '' ? $row['note'] : null,
                $row['patient_photo'] !== '' ? ('عکس‌ها: '.$row['patient_photo']) : null,
                $row['status'] === 'postponed' ? 'وضعیت وردپرس: به‌تعویق‌افتاده' : null,
                'منبع: وردپرس #'.$row['id'].' | '.$row['slot_display'],
            ])));

            $time = $row['time'] !== '' ? $row['time'] : ($row['slot_display'] !== '' ? $row['slot_display'] : null);

            $surgeries[] = [
                'wp_id' => $row['id'],
                'patient_key' => $patientKey,
                'hospital_id' => $hid,
                'patient_name' => $patients[$patientKey]['name'],
                'national_code' => $patients[$patientKey]['national_code'],
                'mobile' => $patients[$patientKey]['mobile'],
                'mobile_secondary' => $mobile2 !== '' ? $mobile2 : null,
                'age' => null,
                'surgery_type' => $typeName,
                'eye_side' => $row['eye_type'] !== '' ? $row['eye_type'] : null,
                'scheduled_date' => $scheduledDate,
                'scheduled_time' => $time,
                'surgeon_name' => null,
                'notes' => $notes !== '' ? $notes : null,
                'status' => $this->mapStatus($row['status']),
                'is_exception' => 0,
                'is_emergency' => $row['is_emergency'] ? 1 : 0,
                'created_at' => $row['created_at'],
                'updated_at' => $row['created_at'],
            ];
        }

        return [
            'hospitals' => $hospitals,
            'surgery_types' => array_values($types),
            'patients' => $patients,
            'surgeries' => $surgeries,
        ];
    }

    private function mapStatus(string $status): string
    {
        return match (mb_strtolower(trim($status))) {
            'done' => BookingStatus::DONE,
            'canceled', 'cancelled' => BookingStatus::CANCELLED,
            'postponed' => BookingStatus::SCHEDULED,
            default => BookingStatus::SCHEDULED,
        };
    }

    private function normalizeMobile(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $this->faToEn($raw)) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }
        if (preg_match('/^09\d{9}$/', $digits)) {
            return $digits;
        }

        return $digits;
    }

    private function normalizeNationalCode(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $this->faToEn($raw)) ?? '';
        if (strlen($digits) === 10) {
            return $digits;
        }

        return '';
    }

    private function faToEn(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    /**
     * @param  array{hospitals: array, surgery_types: array, patients: array, surgeries: list<array>}  $built
     */
    private function importToDatabase(array $built): void
    {
        DB::transaction(function () use ($built) {
            if ($this->option('fresh')) {
                SurgeryAppointment::query()
                    ->where('notes', 'like', '%منبع: وردپرس #%')
                    ->delete();
                Patient::query()
                    ->where('national_code', 'like', 'wp%')
                    ->whereDoesntHave('surgeryAppointments')
                    ->delete();
            }

            foreach ($built['hospitals'] as $hospital) {
                $exists = DB::table('hospitals')->where('id', $hospital['id'])->exists();
                if ($exists) {
                    DB::table('hospitals')->where('id', $hospital['id'])->update([
                        'name' => $hospital['name'],
                        'updated_at' => $hospital['updated_at'],
                    ]);
                } else {
                    DB::table('hospitals')->insert([
                        'id' => $hospital['id'],
                        'name' => $hospital['name'],
                        'created_at' => $hospital['created_at'],
                        'updated_at' => $hospital['updated_at'],
                    ]);
                }
            }

            foreach ($built['surgery_types'] as $type) {
                SurgeryType::query()->firstOrCreate(
                    ['name' => $type['name']],
                    [
                        'is_active' => true,
                        'sort_order' => $type['sort_order'],
                    ]
                );
            }

            $patientIds = [];
            foreach ($built['patients'] as $key => $patient) {
                $model = Patient::query()->updateOrCreate(
                    ['national_code' => $patient['national_code']],
                    [
                        'name' => $patient['name'],
                        'mobile' => $patient['mobile'],
                        'age' => $patient['age'],
                    ]
                );
                $patientIds[$key] = $model->id;
            }

            $bar = $this->output->createProgressBar(count($built['surgeries']));
            $bar->start();

            foreach ($built['surgeries'] as $surgery) {
                $patientId = $patientIds[$surgery['patient_key']] ?? null;
                if (! $patientId) {
                    $bar->advance();
                    continue;
                }

                // Avoid exact duplicate imports
                $exists = SurgeryAppointment::query()
                    ->where('patient_id', $patientId)
                    ->where('hospital_id', $surgery['hospital_id'])
                    ->whereDate('scheduled_date', $surgery['scheduled_date'])
                    ->where('surgery_type', $surgery['surgery_type'])
                    ->where('notes', 'like', '%منبع: وردپرس #'.$surgery['wp_id'].'%')
                    ->exists();

                if ($exists) {
                    $bar->advance();
                    continue;
                }

                SurgeryAppointment::query()->create([
                    'patient_id' => $patientId,
                    'hospital_id' => $surgery['hospital_id'],
                    'created_by' => null,
                    'patient_name' => $surgery['patient_name'],
                    'national_code' => $surgery['national_code'],
                    'mobile' => $surgery['mobile'],
                    'mobile_secondary' => $surgery['mobile_secondary'],
                    'age' => $surgery['age'],
                    'surgery_type' => $surgery['surgery_type'],
                    'eye_side' => $surgery['eye_side'],
                    'scheduled_date' => $surgery['scheduled_date'],
                    'scheduled_time' => $surgery['scheduled_time'],
                    'surgeon_name' => $surgery['surgeon_name'],
                    'notes' => $surgery['notes'],
                    'status' => $surgery['status'],
                    'is_exception' => (bool) $surgery['is_exception'],
                    'is_emergency' => (bool) $surgery['is_emergency'],
                    'created_at' => $surgery['created_at'],
                    'updated_at' => $surgery['updated_at'],
                ]);

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        });
    }

    /**
     * @param  array{hospitals: array, surgery_types: array, patients: array, surgeries: list<array>}  $built
     */
    private function renderMysql(array $built): string
    {
        $now = now()->toDateTimeString();
        $lines = [];
        $lines[] = '-- Generated from wp_sapp_appointments.sql for patient-archive';
        $lines[] = '-- '. $now;
        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = 'START TRANSACTION;';
        $lines[] = '';

        $lines[] = '-- Hospitals (IDs preserved from WordPress)';
        foreach ($built['hospitals'] as $h) {
            $lines[] = sprintf(
                "INSERT INTO `hospitals` (`id`, `name`, `created_at`, `updated_at`) VALUES (%d, %s, %s, %s)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `updated_at`=VALUES(`updated_at`);",
                $h['id'],
                $this->q($h['name']),
                $this->q($h['created_at']),
                $this->q($h['updated_at'])
            );
        }

        $lines[] = '';
        $lines[] = '-- Surgery types';
        foreach ($built['surgery_types'] as $t) {
            $lines[] = sprintf(
                "INSERT INTO `surgery_types` (`name`, `is_active`, `sort_order`, `created_at`, `updated_at`)
SELECT %s, 1, %d, %s, %s FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `surgery_types` WHERE `name` = %s);",
                $this->q($t['name']),
                (int) $t['sort_order'],
                $this->q($t['created_at']),
                $this->q($t['updated_at']),
                $this->q($t['name'])
            );
        }

        $lines[] = '';
        $lines[] = '-- Patients';
        foreach ($built['patients'] as $p) {
            $lines[] = sprintf(
                "INSERT INTO `patients` (`name`, `national_code`, `mobile`, `age`, `created_at`, `updated_at`)
SELECT %s, %s, %s, NULL, %s, %s FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `patients` WHERE `national_code` = %s);",
                $this->q($p['name']),
                $this->q($p['national_code']),
                $this->q($p['mobile']),
                $this->q($p['created_at']),
                $this->q($p['updated_at']),
                $this->q($p['national_code'])
            );
        }

        $lines[] = '';
        $lines[] = '-- Surgery appointments';
        foreach ($built['surgeries'] as $s) {
            $patientNc = $built['patients'][$s['patient_key']]['national_code'];
            $lines[] = sprintf(
                "INSERT INTO `surgery_appointments`
(`patient_id`, `hospital_id`, `created_by`, `patient_name`, `national_code`, `mobile`, `mobile_secondary`, `age`, `surgery_type`, `eye_side`, `scheduled_date`, `scheduled_time`, `surgeon_name`, `notes`, `status`, `is_exception`, `is_emergency`, `created_at`, `updated_at`)
SELECT p.id, %d, NULL, %s, %s, %s, %s, NULL, %s, %s, %s, %s, NULL, %s, %s, %d, %d, %s, %s
FROM `patients` p
WHERE p.national_code = %s
AND NOT EXISTS (
  SELECT 1 FROM `surgery_appointments` sa
  WHERE sa.patient_id = p.id
    AND sa.hospital_id = %d
    AND sa.scheduled_date = %s
    AND sa.surgery_type = %s
    AND sa.notes LIKE %s
);",
                (int) $s['hospital_id'],
                $this->q($s['patient_name']),
                $this->q($s['national_code']),
                $this->q($s['mobile']),
                $s['mobile_secondary'] === null ? 'NULL' : $this->q($s['mobile_secondary']),
                $this->q($s['surgery_type']),
                $s['eye_side'] === null ? 'NULL' : $this->q($s['eye_side']),
                $this->q($s['scheduled_date']),
                $s['scheduled_time'] === null ? 'NULL' : $this->q($s['scheduled_time']),
                $s['notes'] === null ? 'NULL' : $this->q($s['notes']),
                $this->q($s['status']),
                (int) $s['is_exception'],
                (int) $s['is_emergency'],
                $this->q($s['created_at']),
                $this->q($s['updated_at']),
                $this->q($patientNc),
                (int) $s['hospital_id'],
                $this->q($s['scheduled_date']),
                $this->q($s['surgery_type']),
                $this->q('%منبع: وردپرس #'.$s['wp_id'].'%')
            );
        }

        $lines[] = '';
        $lines[] = 'COMMIT;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function q(?string $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return "'".str_replace(["\\", "'"], ["\\\\", "''"], $value)."'";
    }
}
