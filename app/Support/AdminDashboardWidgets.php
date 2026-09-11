<?php

namespace App\Support;

/**
 * Registry of admin overview widgets.
 * Add a new entry here (and a Blade partial) to extend the catalog.
 */
class AdminDashboardWidgets
{
    /**
     * @return array<string, array{id: string, label: string, description: string, span: string, icon: string}>
     */
    public static function definitions(): array
    {
        return [
            'alerts' => [
                'id' => 'alerts',
                'label' => 'هشدارها',
                'description' => 'پیگیری معوق، وضعیت پیامک و بک‌آپ',
                'span' => 'full',
                'icon' => 'alert',
            ],
            'kpis' => [
                'id' => 'kpis',
                'label' => 'شاخص‌های کلیدی',
                'description' => 'پرونده‌ها، نوبت امروز و پیگیری‌ها',
                'span' => 'full',
                'icon' => 'kpi',
            ],
            'week_bookings' => [
                'id' => 'week_bookings',
                'label' => 'نوبت ۷ روز اخیر',
                'description' => 'مجموع ویزیت و عمل در هفته گذشته',
                'span' => 'half',
                'icon' => 'week',
            ],
            'visit_surgery' => [
                'id' => 'visit_surgery',
                'label' => 'ویزیت در برابر عمل',
                'description' => 'تفکیک نوبت‌های امروز',
                'span' => 'half',
                'icon' => 'split',
            ],
            'funnel' => [
                'id' => 'funnel',
                'label' => 'قیف وضعیت امروز',
                'description' => 'ثبت‌شده تا لغو برای نوبت‌های امروز',
                'span' => 'half',
                'icon' => 'funnel',
            ],
            'roles' => [
                'id' => 'roles',
                'label' => 'ترکیب کاربران',
                'description' => 'تعداد مدیر، پزشک، منشی و بیمار',
                'span' => 'half',
                'icon' => 'roles',
            ],
            'patients_growth' => [
                'id' => 'patients_growth',
                'label' => 'رشد پرونده‌ها',
                'description' => 'پرونده جدید امروز، هفته و ماه',
                'span' => 'half',
                'icon' => 'growth',
            ],
            'upcoming_surgeries' => [
                'id' => 'upcoming_surgeries',
                'label' => 'عمل‌های پیش‌رو',
                'description' => 'عمل‌های تایید/ثبت‌شده از امروز به بعد',
                'span' => 'half',
                'icon' => 'surgery',
            ],
            'catalog' => [
                'id' => 'catalog',
                'label' => 'فهرست کلینیک',
                'description' => 'تعداد بیمارستان، دارو و نوع عمل',
                'span' => 'half',
                'icon' => 'catalog',
            ],
            'docs_rx' => [
                'id' => 'docs_rx',
                'label' => 'اسناد و نسخه‌ها',
                'description' => 'حجم مدارک پزشکی و نسخه‌های ثبت‌شده',
                'span' => 'half',
                'icon' => 'docs',
            ],
            'comms' => [
                'id' => 'comms',
                'label' => 'وضعیت ارتباطات',
                'description' => 'پیامک و تلگرام یادآوری',
                'span' => 'half',
                'icon' => 'comms',
            ],
            'system' => [
                'id' => 'system',
                'label' => 'سامانه و بک‌آپ',
                'description' => 'آخرین فایل پشتیبان',
                'span' => 'half',
                'icon' => 'system',
            ],
            'shortcuts' => [
                'id' => 'shortcuts',
                'label' => 'میان‌برهای مدیریت',
                'description' => 'دسترسی سریع به بخش‌های ادمین',
                'span' => 'full',
                'icon' => 'shortcuts',
            ],
            'activity' => [
                'id' => 'activity',
                'label' => 'فعالیت اخیر',
                'description' => 'آخرین رویدادهای ممیزی',
                'span' => 'full',
                'icon' => 'activity',
            ],
        ];
    }

    /**
     * @return list<array{id: string, span: string}>
     */
    public static function defaultLayout(): array
    {
        return [
            ['id' => 'alerts', 'span' => 'full'],
            ['id' => 'kpis', 'span' => 'full'],
            ['id' => 'week_bookings', 'span' => 'half'],
            ['id' => 'visit_surgery', 'span' => 'half'],
            ['id' => 'funnel', 'span' => 'half'],
            ['id' => 'roles', 'span' => 'half'],
            ['id' => 'comms', 'span' => 'half'],
            ['id' => 'system', 'span' => 'half'],
            ['id' => 'shortcuts', 'span' => 'full'],
            ['id' => 'activity', 'span' => 'full'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function ids(): array
    {
        return array_keys(self::definitions());
    }

    public static function exists(string $id): bool
    {
        return isset(self::definitions()[$id]);
    }

    public static function defaultSpan(string $id): string
    {
        $span = self::definitions()[$id]['span'] ?? 'half';

        return in_array($span, ['full', 'half'], true) ? $span : 'half';
    }

    /**
     * @param  mixed  $layout
     * @return list<array{id: string, span: string}>
     */
    public static function normalizeLayout(mixed $layout): array
    {
        if ($layout === null) {
            return self::defaultLayout();
        }

        if (! is_array($layout)) {
            return self::defaultLayout();
        }

        // Explicit empty board is allowed.
        if ($layout === []) {
            return [];
        }

        // New wrapped format: { order: [...], spans: {...} }
        if (array_key_exists('order', $layout) && is_array($layout['order'])) {
            $spans = is_array($layout['spans'] ?? null) ? $layout['spans'] : [];
            $normalized = [];
            foreach ($layout['order'] as $id) {
                if (! is_string($id) || ! self::exists($id)) {
                    continue;
                }
                if (collect($normalized)->contains(fn ($row) => $row['id'] === $id)) {
                    continue;
                }
                $span = $spans[$id] ?? self::defaultSpan($id);
                $normalized[] = [
                    'id' => $id,
                    'span' => in_array($span, ['full', 'half'], true) ? $span : self::defaultSpan($id),
                ];
            }

            return $normalized !== [] ? $normalized : self::defaultLayout();
        }

        $normalized = [];
        foreach ($layout as $row) {
            if (is_string($row)) {
                if (! self::exists($row)) {
                    continue;
                }
                if (collect($normalized)->contains(fn ($item) => $item['id'] === $row)) {
                    continue;
                }
                $normalized[] = ['id' => $row, 'span' => self::defaultSpan($row)];
                continue;
            }

            if (! is_array($row)) {
                continue;
            }

            $id = (string) ($row['id'] ?? '');
            if ($id === '' || ! self::exists($id)) {
                continue;
            }
            if (collect($normalized)->contains(fn ($item) => $item['id'] === $id)) {
                continue;
            }
            $span = (string) ($row['span'] ?? self::defaultSpan($id));
            $normalized[] = [
                'id' => $id,
                'span' => in_array($span, ['full', 'half'], true) ? $span : self::defaultSpan($id),
            ];
        }

        return $normalized !== [] ? $normalized : self::defaultLayout();
    }

    /**
     * @param  list<array{id: string, span: string}>  $layout
     * @return list<string>
     */
    public static function idsOf(array $layout): array
    {
        return array_values(array_map(fn (array $row) => $row['id'], $layout));
    }

    /**
     * @param  list<array{id: string, span: string}>  $layout
     * @return array<string, array{id: string, label: string, description: string, span: string, icon: string}>
     */
    public static function availableFor(array $layout): array
    {
        $onBoard = array_flip(self::idsOf($layout));

        return array_filter(
            self::definitions(),
            fn (array $def) => ! isset($onBoard[$def['id']])
        );
    }
}
