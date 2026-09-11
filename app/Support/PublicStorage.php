<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Resolves paths on the "public" disk.
 *
 * On this deployment the disk root is public/storage (no symlink needed on
 * cPanel), but older installs wrote to storage/app/public. Anything that
 * touches uploaded files should go through here so the two layouts cannot
 * drift apart again.
 */
class PublicStorage
{
    public static function root(): string
    {
        $root = (string) config('filesystems.disks.public.root', public_path('storage'));

        return rtrim($root, '/\\');
    }

    public static function legacyRoot(): string
    {
        return rtrim(storage_path('app/public'), '/\\');
    }

    public static function path(string $relative = ''): string
    {
        $relative = ltrim(trim($relative), '/\\');

        return $relative === '' ? self::root() : self::root().'/'.$relative;
    }

    public static function legacyPath(string $relative = ''): string
    {
        $relative = ltrim(trim($relative), '/\\');

        return $relative === '' ? self::legacyRoot() : self::legacyRoot().'/'.$relative;
    }

    public static function exists(string $relative): bool
    {
        return self::resolve($relative) !== null;
    }

    /**
     * Absolute path of an existing file on either root, or null.
     */
    public static function resolve(string $relative): ?string
    {
        if (trim($relative) === '') {
            return null;
        }

        foreach ([self::path($relative), self::legacyPath($relative)] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Remove a file from both roots so no stale copy survives.
     */
    public static function delete(string $relative): void
    {
        if (trim($relative) === '') {
            return;
        }

        foreach ([self::path($relative), self::legacyPath($relative)] as $candidate) {
            if (is_file($candidate)) {
                File::delete($candidate);
            }
        }
    }

    /**
     * Whether uploads can actually be written — surfaced in admin settings so a
     * misconfigured host is visible before someone tries to print a signature.
     */
    public static function writable(): bool
    {
        $root = self::root();

        if (! File::isDirectory($root)) {
            File::ensureDirectoryExists($root);
        }

        return File::isDirectory($root) && is_writable($root);
    }
}
