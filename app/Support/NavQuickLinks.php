<?php

namespace App\Support;

/**
 * Custom header shortcuts configured in admin settings.
 * Stored as JSON in site_settings under nav.quick_links.
 *
 * @phpstan-type Link array{title: string, url: string}
 */
class NavQuickLinks
{
    public const KEY = 'nav.quick_links';

    public const MAX = 20;

    /**
     * @return list<Link>
     */
    public static function all(): array
    {
        $raw = SiteSettings::get(self::KEY, '[]');
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $links = [];
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            $url = trim((string) ($row['url'] ?? ''));
            if ($title === '' || $url === '' || ! self::isAllowedUrl($url)) {
                continue;
            }
            $links[] = ['title' => mb_substr($title, 0, 80), 'url' => mb_substr($url, 0, 500)];
            if (count($links) >= self::MAX) {
                break;
            }
        }

        return $links;
    }

    /**
     * @param  list<array{title?: mixed, url?: mixed}>  $rows
     * @return list<Link>
     */
    public static function normalize(array $rows): array
    {
        $links = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            $url = trim((string) ($row['url'] ?? ''));
            if ($title === '' && $url === '') {
                continue;
            }
            if ($title === '' || $url === '') {
                continue;
            }
            if (! self::isAllowedUrl($url)) {
                continue;
            }
            $links[] = [
                'title' => mb_substr($title, 0, 80),
                'url' => mb_substr($url, 0, 500),
            ];
            if (count($links) >= self::MAX) {
                break;
            }
        }

        return $links;
    }

    /**
     * @param  list<Link>  $links
     */
    public static function save(array $links): void
    {
        SiteSettings::put(self::KEY, json_encode(array_values($links), JSON_UNESCAPED_UNICODE));
    }

    public static function isAllowedUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//') && ! str_contains($url, "\n");
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    public static function isExternal(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }
}
