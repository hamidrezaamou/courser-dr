<?php

namespace App\Support;

class FeatureFlags
{
    /**
     * @return array<string, array{label: string, hint: string, default: bool}>
     */
    public static function definitions(): array
    {
        return [
            'features.reports_export' => [
                'label' => 'خروجی CSV گزارشات',
                'hint' => 'دکمهٔ دانلود فایل CSV در صفحهٔ گزارش نوبت‌ها',
                'group' => 'general',
                'default' => true,
            ],
            'features.audit_export' => [
                'label' => 'خروجی CSV ممیزی',
                'hint' => 'دانلود لاگ تغییرات با همان فیلترهای صفحهٔ ممیزی',
                'group' => 'general',
                'default' => true,
            ],
            'features.ready_answers' => [
                'label' => 'پاسخ‌های آماده',
                'hint' => 'پنل پاسخ آماده در ابزارک ردیف و API مربوطه',
                'group' => 'general',
                'default' => true,
            ],
            'features.public_registration' => [
                'label' => 'ثبت‌نام عمومی',
                'hint' => 'صفحهٔ /register برای ساخت حساب جدید',
                'group' => 'general',
                'default' => true,
            ],
            'features.clinic_floor' => [
                'label' => 'صف مطب (زنده)',
                'hint' => 'منوی «صف مطب» و صفحهٔ نمای زندهٔ حضور و ارجاع',
                'group' => 'clinic',
                'default' => true,
            ],
            'features.surgery_checklist' => [
                'label' => 'چک‌لیست عمل',
                'hint' => 'چک‌لیست قبل/بعد عمل برای هر نوع جراحی — قابل خاموش کردن بدون حذف داده‌های قبلی',
                'group' => 'clinic',
                'featured' => true,
                'default' => true,
                'scopes' => [
                    'قالب در انواع عمل',
                    'ابزار بُرد و گزارش',
                    'ثبت در پرونده',
                    'چاپ برگه عمل',
                ],
                'off_note' => 'داده‌های چک‌لیست قبلی در دیتابیس می‌مانند؛ فقط نمایش و دکمه‌ها مخفی می‌شوند.',
            ],
            'features.whiteboard' => [
                'label' => 'وایت‌برد پرونده',
                'hint' => 'رسم و ذخیرهٔ طرح چشم در پرونده بیمار',
                'group' => 'clinic',
                'default' => true,
            ],
            'features.followup_reminders' => [
                'label' => 'پیگیری بیمار',
                'hint' => 'داشبورد پیگیری، الگوهای قبل/بعد عمل، پیگیری دستی و یادآوری مراجعه بعدی',
                'group' => 'clinic',
                'featured' => true,
                'default' => true,
                'scopes' => [
                    'داشبورد پیگیری',
                    'الگو روی نوع/زیرگروه عمل',
                    'ایجاد خودکار هنگام ثبت عمل',
                    'ثبت نتیجه و پیگیری مجدد',
                    'یادآوری مراجعه بعدی و پیامک',
                ],
                'off_note' => 'پیگیری‌های قبلی در دیتابیس می‌مانند؛ فقط منو و تولید خودکار مخفی می‌شود.',
            ],
            'features.board_reminders' => [
                'label' => 'پنل یادآوری در بُرد نوبت',
                'hint' => 'ستون یادآوری، ارسال دستی و کنترل توقف پیامک برای هر روز',
                'group' => 'clinic',
                'default' => true,
            ],
            'features.accounting' => [
                'label' => 'حسابداری',
                'hint' => 'صندوق، دریافت و پرداخت، بدهی بیمار و گزارش مالی روزانه',
                'group' => 'advanced',
                'featured' => true,
                'featured_group' => 'advanced',
                'default' => true,
                'scopes' => ['صندوق', 'بدهی بیمار', 'گزارش درآمد', 'خروجی Excel'],
            ],
            'features.billing_insurance' => [
                'label' => 'صورتحساب و بیمه',
                'hint' => 'تعرفه، پوشش بیمه تکمیلی و وضعیت تسویه هر نوبت/عمل',
                'group' => 'advanced',
                'default' => true,
                'scopes' => ['تعرفه خدمات', 'بیمه تکمیلی', 'سهم بیمار'],
            ],
            'features.consent_forms' => [
                'label' => 'رضایت‌نامه دیجیتال',
                'hint' => 'قالب رضایت عمل/ویزیت، امضا و ثبت در پرونده',
                'group' => 'advanced',
                'default' => true,
                'scopes' => ['قالب رضایت', 'امضای بیمار', 'چاپ و آرشیو'],
            ],
            'features.patient_portal' => [
                'label' => 'پرتال بیمار',
                'hint' => 'دسترسی محدود بیمار به نوبت، نتیجه و پیام کلینیک',
                'group' => 'advanced',
                'default' => true,
                'scopes' => ['نوبت‌های من', 'پیام کلینیک', 'مدارک'],
            ],
            'features.waiting_list' => [
                'label' => 'لیست انتظار',
                'hint' => 'صف جایگزین وقتی ظرفیت روز پر است؛ تماس خودکار هنگام آزاد شدن نوبت',
                'group' => 'advanced',
                'default' => true,
                'scopes' => ['ثبت در صف', 'اولویت تماس', 'تبدیل به نوبت'],
            ],
            'features.online_booking_approval' => [
                'label' => 'تأیید نوبت آنلاین',
                'hint' => 'درخواست‌های وب/فرم ابتدا پیش‌نویس؛ منشی تأیید یا رد می‌کند',
                'group' => 'advanced',
                'default' => true,
                'scopes' => ['صف تأیید منشی', 'رد/تأیید', 'اعلان بیمار'],
            ],
            'features.quality_dashboard' => [
                'label' => 'داشبورد کیفیت',
                'hint' => 'KPI عملکرد مطب: no-show، زمان انتظار، تکمیل چک‌لیست و …',
                'group' => 'advanced',
                'default' => true,
                'scopes' => ['شاخص‌های روزانه', 'مقایسه ماه', 'هشدار انحراف'],
            ],
            'features.eye_chart' => [
                'label' => 'جدول بینایی',
                'hint' => 'ثبت VA ساختاریافته با Snellen / LogMAR در معاینه',
                'group' => 'advanced',
                'default' => true,
                'scopes' => ['VA راست/چپ', 'LogMAR', 'نمودار روند'],
            ],
            'features.structured_prescriptions' => [
                'label' => 'نسخه چاپی ساختاریافته',
                'hint' => 'چاپ نسخه با قالب استاندارد، QR و امضای پزشک',
                'group' => 'advanced',
                'default' => true,
                'scopes' => ['قالب چاپ', 'QR نسخه', 'نسخه الکترونیک'],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            'clinic' => 'کلینیک و پرونده',
            'general' => 'عمومی',
            'advanced' => 'پیشرفته (اختیاری)',
        ];
    }

    /**
     * @return list<string>
     */
    public static function groupOrder(): array
    {
        return ['clinic', 'general', 'advanced'];
    }

    /**
     * @return list<string>
     */
    public static function featuredKeys(): array
    {
        return collect(self::definitions())
            ->filter(fn (array $meta) => ! empty($meta['featured']) && empty($meta['featured_group']))
            ->keys()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function featuredKeysForGroup(string $group): array
    {
        return collect(self::definitions())
            ->filter(function (array $meta) use ($group) {
                if (empty($meta['featured'])) {
                    return false;
                }

                return ($meta['featured_group'] ?? null) === $group
                    || ($group === 'clinic' && empty($meta['featured_group']) && ($meta['group'] ?? '') === 'clinic');
            })
            ->keys()
            ->all();
    }

    public static function isPlanned(string $key): bool
    {
        return ! empty(self::definitions()[$key]['planned']);
    }

    /**
     * @return list<string>
     */
    public static function enabledPlannedKeys(): array
    {
        return collect(self::definitions())
            ->filter(fn (array $meta, string $key) => ! empty($meta['planned']) && self::enabled($key))
            ->keys()
            ->all();
    }

    public static function enabled(string $key): bool
    {
        $defs = self::definitions();
        $default = $defs[$key]['default'] ?? false;

        return (bool) SiteSettings::effective($key, $default);
    }

    public static function unlockAdvancedOnce(): void
    {
        if (SiteSettings::get('features.suite_unlocked')) {
            return;
        }

        $pairs = ['features.suite_unlocked' => '1'];
        foreach (self::definitions() as $key => $meta) {
            if (($meta['group'] ?? '') === 'advanced') {
                $pairs[$key] = true;
            }
        }
        SiteSettings::putMany($pairs);
    }

    /**
     * @return array<string, bool>
     */
    public static function all(): array
    {
        $out = [];
        foreach (self::definitions() as $key => $meta) {
            $out[$key] = self::enabled($key);
        }

        return $out;
    }
}
