<?php

namespace App\Support;

class ToolboxPrefs
{
    public const SURFACE_TOOLBOX = 'toolbox';

    public const SURFACE_BOOKING = 'booking_success';

    /**
     * @return array{toolbox: array, booking_success: array}
     */
    public static function defaults(): array
    {
        $toolboxItems = ['call', 'sms', 'edit', 'change_status', 'print', 'checklist', 'surgery', 'visit', 'workspace', 'answers', 'panel_sms', 'patient_file'];
        if (! FeatureFlags::enabled('features.surgery_checklist')) {
            $toolboxItems = array_values(array_filter($toolboxItems, fn (string $id) => $id !== 'checklist'));
        }
        if (! FeatureFlags::enabled('features.ready_answers')) {
            $toolboxItems = array_values(array_filter($toolboxItems, fn (string $id) => $id !== 'answers'));
        }

        return [
            self::SURFACE_TOOLBOX => [
                'style' => 'soft',
                'accent' => '#4f86be',
                'items' => array_map(
                    fn (string $id) => ['id' => $id, 'kind' => 'builtin'],
                    $toolboxItems
                ),
            ],
            self::SURFACE_BOOKING => [
                'style' => 'soft',
                'accent' => '#4f86be',
                'items' => [
                    ['id' => 'open_print', 'kind' => 'builtin'],
                    ['id' => 'open_reports', 'kind' => 'builtin'],
                    ['id' => 'panel_sms', 'kind' => 'builtin'],
                    ['id' => 'open_patient', 'kind' => 'builtin'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, full?: bool}>
     */
    public static function toolboxBuiltins(): array
    {
        $builtins = [
            'call' => ['label' => 'تماس'],
            'sms' => ['label' => 'پیامک'],
            'edit' => ['label' => 'ویرایش نوبت'],
            'change_status' => ['label' => 'تغییر وضعیت', 'full' => true],
            'print' => ['label' => 'چاپ برگه‌ها'],
            'checklist' => ['label' => 'چک‌لیست عمل', 'full' => true],
            'surgery' => ['label' => 'ثبت عمل'],
            'visit' => ['label' => 'ثبت ویزیت'],
            'workspace' => ['label' => 'پرونده / گالری'],
            'answers' => ['label' => 'پاسخ‌های آماده', 'full' => true],
            'report_note' => ['label' => 'افزودن توضیحات', 'full' => true],
            'panel_sms' => ['label' => 'ارسال پیامک از پنل SMS.ir', 'full' => true],
            'panel_sms_person' => ['label' => 'پیامک پنل به شخص خاص', 'full' => true, 'needs_mobile' => true],
            'patient_file' => ['label' => 'پرونده بیمار', 'full' => true],
        ];

        if (! FeatureFlags::enabled('features.surgery_checklist')) {
            unset($builtins['checklist']);
        }
        if (! FeatureFlags::enabled('features.ready_answers')) {
            unset($builtins['answers']);
        }

        return $builtins;
    }

    /**
     * @return array<string, array{label: string, full?: bool, needs_mobile?: bool}>
     */
    public static function bookingBuiltins(): array
    {
        return [
            'open_patient' => ['label' => 'مشاهده پرونده', 'full' => true],
            'open_board' => ['label' => 'رفتن به نوبت‌ها'],
            'open_print' => ['label' => 'پرینت', 'full' => true],
            'open_reports' => ['label' => 'گزارشات', 'full' => true],
            'panel_sms' => ['label' => 'ارسال پیامک پنل', 'full' => true],
            'call_patient' => ['label' => 'تماس با بیمار'],
            'sms_patient' => ['label' => 'پیامک به بیمار'],
            'share_details' => ['label' => 'ارسال مشخصات به شماره…', 'full' => true, 'needs_mobile' => true],
            'panel_sms_person' => ['label' => 'پیامک پنل به شخص خاص', 'full' => true, 'needs_mobile' => true],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array{toolbox: array, booking_success: array}
     */
    public static function normalize(?array $stored): array
    {
        $defaults = self::defaults();
        if (! is_array($stored)) {
            return $defaults;
        }

        return [
            self::SURFACE_TOOLBOX => self::normalizeSurface(
                $stored[self::SURFACE_TOOLBOX] ?? null,
                $defaults[self::SURFACE_TOOLBOX],
                array_keys(self::toolboxBuiltins())
            ),
            self::SURFACE_BOOKING => self::normalizeSurface(
                $stored[self::SURFACE_BOOKING] ?? null,
                $defaults[self::SURFACE_BOOKING],
                array_keys(self::bookingBuiltins())
            ),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $surface
     * @param  array<string, mixed>  $fallback
     * @param  list<string>  $builtinIds
     * @return array{style: string, accent: string, items: list<array<string, mixed>>}
     */
    private static function normalizeSurface(?array $surface, array $fallback, array $builtinIds): array
    {
        $style = (string) ($surface['style'] ?? $fallback['style'] ?? 'soft');
        if (! in_array($style, ['soft', 'filled', 'outline'], true)) {
            $style = 'soft';
        }

        $accent = (string) ($surface['accent'] ?? $fallback['accent'] ?? '#4f86be');
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) {
            $accent = '#4f86be';
        }

        $rawItems = $surface['items'] ?? $fallback['items'] ?? [];
        if (! is_array($rawItems) || $rawItems === []) {
            $rawItems = $fallback['items'];
        }

        $items = [];
        $seen = [];
        foreach ($rawItems as $item) {
            if (! is_array($item)) {
                continue;
            }
            $normalized = self::normalizeItem($item, $builtinIds, $style, $accent);
            if ($normalized === null) {
                continue;
            }
            $key = $normalized['id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $items[] = $normalized;
        }

        return [
            'style' => $style,
            'accent' => $accent,
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $builtinIds
     * @return array<string, mixed>|null
     */
    private static function normalizeItem(array $item, array $builtinIds, string $fallbackStyle = 'soft', string $fallbackAccent = '#4f86be'): ?array
    {
        $kind = (string) ($item['kind'] ?? 'builtin');
        $id = trim((string) ($item['id'] ?? ''));

        if ($kind === 'builtin') {
            if ($id === '' || ! in_array($id, $builtinIds, true)) {
                return null;
            }

            $out = ['id' => $id, 'kind' => 'builtin'];
            if (! empty($item['mobile'])) {
                $out['mobile'] = self::normalizeMobile((string) $item['mobile']);
            }
            if (! empty($item['label'])) {
                $out['label'] = mb_substr(trim((string) $item['label']), 0, 60);
            }
            if (! empty($item['body'])) {
                $out['body'] = mb_substr(trim((string) $item['body']), 0, 400);
            }
            $out['style'] = self::normalizeStyle($item['style'] ?? $fallbackStyle);
            $out['accent'] = self::normalizeAccent($item['accent'] ?? $fallbackAccent) ?? $fallbackAccent;
            $out['span'] = self::normalizeSpan($item['span'] ?? null, $id);

            return $out;
        }

        if (! in_array($kind, ['tel', 'sms', 'panel_sms'], true)) {
            return null;
        }

        $mobile = self::normalizeMobile((string) ($item['mobile'] ?? ''));
        if ($mobile === null) {
            return null;
        }

        if ($id === '') {
            $id = $kind.'_'.substr(sha1($mobile.($item['label'] ?? '')), 0, 10);
        }

        $defaultLabel = $kind === 'tel' ? 'تماس' : ($kind === 'panel_sms' ? 'پیامک پنل' : 'پیامک');

        $out = [
            'id' => mb_substr($id, 0, 40),
            'kind' => $kind,
            'label' => mb_substr(trim((string) ($item['label'] ?? $defaultLabel)), 0, 60) ?: $defaultLabel,
            'mobile' => $mobile,
            'body' => mb_substr(trim((string) ($item['body'] ?? '')), 0, 400),
            'style' => self::normalizeStyle($item['style'] ?? $fallbackStyle),
            'accent' => self::normalizeAccent($item['accent'] ?? $fallbackAccent) ?? $fallbackAccent,
            'span' => self::normalizeSpan($item['span'] ?? null, $id),
        ];

        return $out;
    }

    public static function normalizeStyle(mixed $raw): string
    {
        $style = (string) $raw;

        return in_array($style, ['soft', 'filled', 'outline'], true) ? $style : 'soft';
    }

    public static function normalizeSpan(mixed $raw, ?string $builtinId = null): string
    {
        $span = (string) $raw;
        if (in_array($span, ['full', 'half'], true)) {
            return $span;
        }

        $builtins = array_merge(self::toolboxBuiltins(), self::bookingBuiltins());
        if ($builtinId && ! empty($builtins[$builtinId]['full'])) {
            return 'full';
        }

        return 'half';
    }

    public static function normalizeAccent(mixed $raw): ?string
    {
        $accent = trim((string) $raw);
        if ($accent === '' || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) {
            return null;
        }

        return strtolower($accent);
    }

    public static function normalizeMobile(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return preg_match('/^09\d{9}$/', $digits) ? $digits : null;
    }

    /**
     * @param  array<string, mixed>  $patient
     */
    public static function patientDetailsMessage(array $patient): string
    {
        $lines = [
            'مشخصات بیمار',
            'نام: '.($patient['name'] ?? '—'),
            'کد ملی: '.($patient['nationalCode'] ?? $patient['national_code'] ?? '—'),
            'موبایل: '.($patient['mobile'] ?? '—'),
        ];
        if (! empty($patient['mobileSecondary'])) {
            $lines[] = 'موبایل دوم: '.$patient['mobileSecondary'];
        }
        if (! empty($patient['meta'])) {
            $lines[] = 'جزئیات: '.$patient['meta'];
        }

        return mb_substr(implode("\n", $lines), 0, 900);
    }
}
