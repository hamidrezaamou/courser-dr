<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ClinicBrand
{
    public static function doctorName(): string
    {
        return (string) SiteSettings::effective('clinic.doctor_name', 'پزشک');
    }

    public static function phone(): string
    {
        return (string) SiteSettings::effective('clinic.phone', '');
    }

    public static function headerSpacer(): int
    {
        return (int) SiteSettings::effective('clinic.print_header_spacer', 120);
    }

    public static function logoUrl(): ?string
    {
        return self::assetUrl('clinic.logo_path');
    }

    public static function signatureUrl(): ?string
    {
        return self::assetUrl('clinic.signature_path');
    }

    public static function stampUrl(): ?string
    {
        return self::assetUrl('clinic.stamp_path');
    }

    /**
     * Print views embed the images instead of linking them, so a broken
     * storage symlink or an offline browser cannot silently drop the
     * signature and stamp from a prescription.
     */
    public static function logoDataUri(): ?string
    {
        return self::assetDataUri('clinic.logo_path');
    }

    public static function signatureDataUri(): ?string
    {
        return self::assetDataUri('clinic.signature_path');
    }

    public static function stampDataUri(): ?string
    {
        return self::assetDataUri('clinic.stamp_path');
    }

    /**
     * Absolute path of a stored brand file, or null when it is missing.
     *
     * Files uploaded before the public disk was pointed at public/storage may
     * still sit under storage/app/public, so both roots are consulted.
     */
    public static function resolvePath(string $relative): ?string
    {
        return PublicStorage::resolve(ltrim(trim($relative), '/'));
    }

    private static function assetUrl(string $settingKey): ?string
    {
        $relative = trim((string) SiteSettings::effective($settingKey, ''));
        if (self::resolvePath($relative) === null) {
            return null;
        }

        return Storage::disk('public')->url($relative);
    }

    private static function assetDataUri(string $settingKey): ?string
    {
        $relative = trim((string) SiteSettings::effective($settingKey, ''));
        $absolute = self::resolvePath($relative);

        if ($absolute === null) {
            return null;
        }

        $contents = @file_get_contents($absolute);
        if ($contents === false || $contents === '') {
            return null;
        }

        return 'data:'.self::mimeFor($absolute).';base64,'.base64_encode($contents);
    }

    private static function mimeFor(string $absolute): string
    {
        return match (strtolower(File::extension($absolute))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/jpeg',
        };
    }
}
