<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

class QrImage
{
    /** Light margin around the symbol, in modules. Four is the spec minimum. */
    private const QUIET_ZONE = 4;

    /**
     * Inline QR image for print views. Never hits the network.
     */
    public static function dataUri(string $payload, int $size = 180): ?string
    {
        $payload = trim($payload);
        if ($payload === '') {
            return null;
        }

        $absolute = self::cachedPng($payload, $size);

        if ($absolute !== null) {
            $contents = @file_get_contents($absolute);
            if ($contents !== false && $contents !== '') {
                return 'data:image/png;base64,'.base64_encode($contents);
            }
        }

        // No GD on this host — an inline SVG prints just as well.
        $svg = self::svg($payload, $size);

        return $svg === null ? null : 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public static function publicUrl(string $payload, int $size = 180): ?string
    {
        $payload = trim($payload);
        if ($payload === '') {
            return null;
        }

        $relative = self::relativePath($payload, $size);

        return self::cachedPng($payload, $size) === null
            ? null
            : Storage::disk('public')->url($relative);
    }

    /**
     * Raw SVG markup, handy for screens where a data URI is unnecessary.
     */
    public static function svg(string $payload, int $size = 180): ?string
    {
        $payload = trim($payload);
        if ($payload === '') {
            return null;
        }

        try {
            $matrix = QrCode::matrix($payload);
        } catch (Throwable) {
            return null;
        }

        $modules = count($matrix) + (self::QUIET_ZONE * 2);
        $rects = '';

        foreach ($matrix as $row => $cells) {
            foreach ($cells as $col => $dark) {
                if ($dark) {
                    $rects .= '<rect x="'.($col + self::QUIET_ZONE).'" y="'.($row + self::QUIET_ZONE).'" width="1" height="1"/>';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'"'
            .' viewBox="0 0 '.$modules.' '.$modules.'" shape-rendering="crispEdges">'
            .'<rect width="'.$modules.'" height="'.$modules.'" fill="#ffffff"/>'
            .'<g fill="#000000">'.$rects.'</g></svg>';
    }

    private static function relativePath(string $payload, int $size): string
    {
        return 'qr-cache/'.md5($payload.'|'.$size.'|v2').'.png';
    }

    /**
     * Absolute path to the cached PNG, rendering it first if needed.
     */
    private static function cachedPng(string $payload, int $size): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $relative = self::relativePath($payload, $size);
        $existing = PublicStorage::resolve($relative);

        if ($existing !== null) {
            return $existing;
        }

        $absolute = PublicStorage::path($relative);

        try {
            self::renderPng($payload, $size, $absolute);
        } catch (Throwable) {
            return null;
        }

        return is_file($absolute) ? $absolute : null;
    }

    private static function renderPng(string $payload, int $size, string $absolute): void
    {
        $matrix = QrCode::matrix($payload);
        $modules = count($matrix) + (self::QUIET_ZONE * 2);

        // Snap to a whole number of pixels per module so the symbol stays sharp.
        $scale = max(1, (int) floor($size / $modules));
        $dimension = $modules * $scale;

        $image = imagecreatetruecolor($dimension, $dimension);
        if ($image === false) {
            return;
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $dimension - 1, $dimension - 1, $white);

        foreach ($matrix as $row => $cells) {
            foreach ($cells as $col => $dark) {
                if (! $dark) {
                    continue;
                }

                $x = ($col + self::QUIET_ZONE) * $scale;
                $y = ($row + self::QUIET_ZONE) * $scale;
                imagefilledrectangle($image, $x, $y, $x + $scale - 1, $y + $scale - 1, $black);
            }
        }

        File::ensureDirectoryExists(dirname($absolute));
        imagepng($image, $absolute);
        imagedestroy($image);
    }
}
