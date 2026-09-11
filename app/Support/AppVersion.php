<?php

namespace App\Support;

class AppVersion
{
    public static function current(): string
    {
        $path = base_path('VERSION');

        if (! is_file($path)) {
            return '0.0.0';
        }

        $raw = trim((string) file_get_contents($path));

        return $raw !== '' ? $raw : '0.0.0';
    }

    public static function lastUpdatedAt(): ?string
    {
        $path = storage_path('app/.last-update');

        if (! is_file($path)) {
            return null;
        }

        $raw = trim((string) file_get_contents($path));

        return $raw !== '' ? $raw : null;
    }

    public static function markUpdated(): void
    {
        $dir = storage_path('app');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(storage_path('app/.last-update'), now()->toIso8601String());
    }
}
