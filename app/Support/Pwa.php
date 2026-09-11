<?php

namespace App\Support;

class Pwa
{
    public static function shortName(): string
    {
        $name = trim((string) config('app.name', 'آرشیو بیمار'));

        return mb_strlen($name) > 12 ? 'آرشیو بیمار' : $name;
    }

    public static function name(): string
    {
        return trim((string) config('app.name', 'آرشیو بیمار')) ?: 'آرشیو بیمار';
    }

    public static function description(): string
    {
        return 'سامانه پرونده الکترونیک — نوبت، پرونده بیمار و مدیریت مطب';
    }

    public static function themeColor(): string
    {
        return '#2f5f8c';
    }

    public static function backgroundColor(): string
    {
        return '#f0f6fc';
    }

    public static function startUrl(): string
    {
        return url('/dashboard').'?source=pwa';
    }

    public static function scope(): string
    {
        return '/';
    }

    /**
     * @return array<string, mixed>
     */
    public static function manifest(): array
    {
        return [
            'id' => url('/'),
            'name' => self::name(),
            'short_name' => self::shortName(),
            'description' => self::description(),
            'lang' => 'fa',
            'dir' => 'rtl',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui', 'browser'],
            'orientation' => 'any',
            'theme_color' => self::themeColor(),
            'background_color' => self::backgroundColor(),
            'prefer_related_applications' => false,
            'categories' => ['medical', 'health', 'productivity'],
            'icons' => self::icons(),
            'shortcuts' => [
                [
                    'name' => 'داشبورد',
                    'short_name' => 'داشبورد',
                    'url' => '/dashboard',
                    'icons' => [['src' => '/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png']],
                ],
                [
                    'name' => 'نوبت‌ها',
                    'short_name' => 'نوبت‌ها',
                    'url' => '/appointments/board',
                    'icons' => [['src' => '/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png']],
                ],
                [
                    'name' => 'ثبت عمل',
                    'short_name' => 'ثبت عمل',
                    'url' => '/surgery/register',
                    'icons' => [['src' => '/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png']],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function icons(): array
    {
        $sizes = [72, 96, 128, 144, 152, 180, 192, 384, 512];
        $out = [];

        foreach ($sizes as $size) {
            $out[] = [
                'src' => "/pwa/icon-{$size}.png",
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
                'purpose' => 'any',
            ];
            if (in_array($size, [192, 512], true)) {
                $out[] = [
                    'src' => "/pwa/icon-{$size}.png",
                    'sizes' => "{$size}x{$size}",
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ];
            }
        }

        return $out;
    }
}
