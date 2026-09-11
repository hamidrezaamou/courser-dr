<?php

namespace App\Support;

class ModuleRegistry
{
    /**
     * @return array<string, array{label: string, route: string, feature: string, section: string}>
     */
    public static function definitions(): array
    {
        return [
            'accounting' => [
                'label' => 'حسابداری',
                'route' => 'modules.accounting.index',
                'feature' => 'features.accounting',
                'section' => 'accounting',
            ],
            'billing' => [
                'label' => 'صورتحساب و بیمه',
                'route' => 'modules.billing.index',
                'feature' => 'features.billing_insurance',
                'section' => 'billing',
            ],
            'consent' => [
                'label' => 'رضایت‌نامه',
                'route' => 'modules.consent.index',
                'feature' => 'features.consent_forms',
                'section' => 'consent',
            ],
            'portal' => [
                'label' => 'پرتال بیمار',
                'route' => 'modules.portal.index',
                'feature' => 'features.patient_portal',
                'section' => 'portal',
            ],
            'waiting' => [
                'label' => 'لیست انتظار',
                'route' => 'modules.waiting.index',
                'feature' => 'features.waiting_list',
                'section' => 'waiting',
            ],
            'approval' => [
                'label' => 'تأیید نوبت آنلاین',
                'route' => 'modules.approval.index',
                'feature' => 'features.online_booking_approval',
                'section' => 'approval',
            ],
            'quality' => [
                'label' => 'داشبورد کیفیت',
                'route' => 'modules.quality.index',
                'feature' => 'features.quality_dashboard',
                'section' => 'quality',
            ],
            'eye_chart' => [
                'label' => 'جدول بینایی',
                'route' => 'modules.eye-chart.index',
                'feature' => 'features.eye_chart',
                'section' => 'eye-chart',
            ],
            'rx_print' => [
                'label' => 'نسخه چاپی',
                'route' => 'modules.prescriptions.index',
                'feature' => 'features.structured_prescriptions',
                'section' => 'prescriptions',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, route: string, feature: string, section: string}>
     */
    public static function enabled(): array
    {
        $out = [];
        foreach (self::definitions() as $key => $meta) {
            if (FeatureFlags::enabled($meta['feature'])) {
                $out[$key] = $meta;
            }
        }

        return $out;
    }

    public static function anyEnabled(): bool
    {
        return self::enabled() !== [];
    }
}
