<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SiteSettings
{
    public const CACHE_KEY = 'site_settings.map';

    /**
     * @return list<string>
     */
    public static function managedKeys(): array
    {
        return [
            'reminders.enabled',
            'reminders.days_ahead',
            'reminders.telegram.enabled',
            'reminders.telegram.bot_token',
            'reminders.telegram.chat_id',
            'reminders.sms.enabled',
            'reminders.sms.driver',
            'reminders.sms.smsir.api_key',
            'reminders.sms.smsir.line_number',
            'reminders.sms.visit_on_booking',
            'reminders.send_time',
            'print.custom_forms',
            'print.templates.hospital',
            'print.templates.anesthesiologist',
            'print.templates.laboratory',
            'print.templates.iol_master',
            'print.templates.prescription',
            'services.website_api.key',
            'services.website_api.clinic_label',
            'clinic.doctor_name',
            'clinic.phone',
            'clinic.print_header_spacer',
            'clinic.logo_path',
            'clinic.signature_path',
            'clinic.stamp_path',
            'features.reports_export',
            'features.audit_export',
            'features.ready_answers',
            'features.public_registration',
            'features.clinic_floor',
            'features.surgery_checklist',
            'features.whiteboard',
            'features.followup_reminders',
            'features.board_reminders',
            'features.accounting',
            'features.billing_insurance',
            'features.consent_forms',
            'features.patient_portal',
            'features.waiting_list',
            'features.online_booking_approval',
            'features.quality_dashboard',
            'features.eye_chart',
            'features.structured_prescriptions',
            'features.suite_unlocked',
            'reminders.blocked_dates',
            'support.telegram',
            'support.phone',
            'support.email',
            'support.sla_hours',
            'support.notes',
            'privacy.activity_log_days',
            'privacy.qr_cache_days',
            'privacy.retention_note',
        ];
    }

    /**
     * @return list<string>
     */
    public static function secretKeys(): array
    {
        return [
            'reminders.telegram.bot_token',
            'reminders.sms.smsir.api_key',
            'services.website_api.key',
        ];
    }

    /**
     * @return list<string>
     */
    public static function booleanKeys(): array
    {
        return [
            'reminders.enabled',
            'reminders.telegram.enabled',
            'reminders.sms.enabled',
            'reminders.sms.visit_on_booking',
            'features.reports_export',
            'features.audit_export',
            'features.ready_answers',
            'features.public_registration',
            'features.clinic_floor',
            'features.surgery_checklist',
            'features.whiteboard',
            'features.followup_reminders',
            'features.board_reminders',
            'features.accounting',
            'features.billing_insurance',
            'features.consent_forms',
            'features.patient_portal',
            'features.waiting_list',
            'features.online_booking_approval',
            'features.quality_dashboard',
            'features.eye_chart',
            'features.structured_prescriptions',
        ];
    }

    /**
     * @return list<string>
     */
    public static function integerKeys(): array
    {
        return [
            'reminders.days_ahead',
            'clinic.print_header_spacer',
            'support.sla_hours',
            'privacy.activity_log_days',
            'privacy.qr_cache_days',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        try {
            if (! Schema::hasTable('site_settings')) {
                return [];
            }
        } catch (Throwable) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, function () {
            return SiteSetting::query()
                ->pluck('value', 'key')
                ->map(fn ($value, $key) => self::castKey((string) $key, $value))
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function put(string $key, mixed $value): void
    {
        SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => self::encode($value)]
        );
        self::flush();
    }

    /**
     * @param  array<string, mixed>  $pairs
     */
    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => self::encode($value)]
            );
        }
        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function applyToConfig(): void
    {
        foreach (self::all() as $key => $value) {
            if (! in_array($key, self::managedKeys(), true)) {
                continue;
            }
            config([$key => $value]);
        }
    }

    public static function effective(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return config($key, $default);
    }

    public static function maskSecret(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return '********';
    }

    public static function isMasked(?string $value): bool
    {
        return $value === null || $value === '' || $value === '********';
    }

    private static function encode(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) ($value ?? '');
    }

    private static function castKey(string $key, mixed $value): mixed
    {
        if (in_array($key, self::booleanKeys(), true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (in_array($key, self::integerKeys(), true)) {
            return (int) $value;
        }

        return $value === null ? null : (string) $value;
    }
}
