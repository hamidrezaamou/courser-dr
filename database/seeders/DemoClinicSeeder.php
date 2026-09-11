<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Drug;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\SurgeryType;
use App\Models\User;
use App\Support\BookingStatus;
use App\Support\SiteSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoClinicSeeder extends Seeder
{
    public function run(): void
    {
        $doctor = User::query()->updateOrCreate(
            ['national_code' => '1234567890'],
            [
                'name' => 'دکتر نمونه',
                'mobile' => '09120000001',
                'role' => User::ROLE_DOCTOR,
                'password' => Hash::make('Demo@1234'),
            ]
        );

        User::query()->updateOrCreate(
            ['national_code' => '1234567891'],
            [
                'name' => 'منشی نمونه',
                'mobile' => '09120000002',
                'role' => User::ROLE_ASSISTANT,
                'password' => Hash::make('Demo@1234'),
            ]
        );

        User::query()->updateOrCreate(
            ['national_code' => '0011223344'],
            [
                'name' => 'مدیر سامانه',
                'mobile' => '09120000000',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('Demo@1234'),
            ]
        );

        SiteSettings::putMany([
            'clinic.doctor_name' => 'دکتر نمونه',
            'clinic.phone' => '02191000000',
            'support.telegram' => '@clinic_support_demo',
            'support.phone' => '02191000000',
            'support.sla_hours' => 24,
            'support.notes' => 'پشتیبانی دمو: پاسخ در ساعات کاری.',
            'privacy.activity_log_days' => 365,
            'privacy.qr_cache_days' => 30,
            'privacy.retention_note' => 'پرونده‌ها تا درخواست کتبی بیمار یا پزشک نگه داشته می‌شوند. حذف امن از پرونده بیمار در دسترس مدیر است.',
        ]);

        $hospital = Hospital::query()->firstOrCreate(
            ['name' => 'بیمارستان نمونه'],
            []
        );

        SurgeryType::query()->firstOrCreate(
            ['name' => 'کاتاراکت'],
            ['is_active' => true, 'sort_order' => 1]
        );

        foreach (['قطره آنتی‌بیوتیک', 'اشک مصنوعی', 'پماد چشمی'] as $i => $name) {
            Drug::query()->firstOrCreate(
                ['name' => $name],
                ['is_active' => true, 'sort_order' => $i + 1]
            );
        }

        $patients = [
            ['name' => 'علی رضایی', 'national_code' => '0012345678', 'mobile' => '09121111111', 'age' => 45],
            ['name' => 'مریم احمدی', 'national_code' => '0012345679', 'mobile' => '09122222222', 'age' => 38],
            ['name' => 'حسین کریمی', 'national_code' => '0012345680', 'mobile' => '09123333333', 'age' => 62],
        ];

        foreach ($patients as $row) {
            $patient = Patient::query()->firstOrCreate(
                ['national_code' => $row['national_code']],
                $row
            );

            Appointment::query()->firstOrCreate(
                [
                    'patient_id' => $patient->id,
                    'scheduled_date' => now()->toDateString(),
                    'scheduled_time' => '09:00:00',
                ],
                [
                    'created_by' => $doctor->id,
                    'patient_name' => $patient->name,
                    'national_code' => $patient->national_code,
                    'mobile' => $patient->mobile,
                    'visit_type' => 'ویزیت دمو',
                    'status' => BookingStatus::SCHEDULED,
                ]
            );
        }

        $this->command?->info('دمو آماده شد.');
        $this->command?->info('ورود مدیر: 0011223344 / Demo@1234');
        $this->command?->info('ورود پزشک: 1234567890 / Demo@1234');
        $this->command?->info('ورود منشی: 1234567891 / Demo@1234');
    }
}
