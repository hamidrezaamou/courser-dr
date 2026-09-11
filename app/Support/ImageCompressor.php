<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shrink uploaded photos before they hit disk.
 * Phone cameras often send 3–8 MB files; this stores a ~1600px JPEG instead.
 */
class ImageCompressor
{
    public const MAX_EDGE = 1600;

    public const JPEG_QUALITY = 72;

    public static function storeUploaded(UploadedFile $file, string $directory, string $disk = 'public', int $maxEdge = self::MAX_EDGE, int $quality = self::JPEG_QUALITY): string
    {
        $directory = trim($directory, '/');

        if (! self::isCompressible($file)) {
            return $file->store($directory, $disk);
        }

        $path = $file->getRealPath();
        $binary = is_string($path) && $path !== ''
            ? self::compressPath($path, (int) $file->getSize(), $maxEdge, $quality)
            : null;

        if ($binary === null) {
            return $file->store($directory, $disk);
        }

        $stored = $directory.'/'.Str::uuid().'.jpg';
        Storage::disk($disk)->put($stored, $binary);

        return $stored;
    }

    public static function storeBinary(string $binary, string $directory, string $disk = 'public', string $fallbackExt = 'png'): string
    {
        $directory = trim($directory, '/');
        $compressed = self::compressBinary($binary, strlen($binary));

        if ($compressed !== null) {
            $stored = $directory.'/'.Str::uuid().'.jpg';
            Storage::disk($disk)->put($stored, $compressed);

            return $stored;
        }

        $stored = $directory.'/'.Str::uuid().'.'.ltrim($fallbackExt, '.');
        Storage::disk($disk)->put($stored, $binary);

        return $stored;
    }

    /**
     * Rotate a stored file in place. Positive degrees are clockwise (90 = portrait ↔ landscape).
     */
    public static function rotateStored(string $relativePath, int $clockwiseDegrees, string $disk = 'public'): bool
    {
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '' || ! Storage::disk($disk)->exists($relativePath)) {
            return false;
        }

        $raw = Storage::disk($disk)->get($relativePath);
        if (! is_string($raw) || $raw === '') {
            return false;
        }

        $binary = self::rotateBinary($raw, $clockwiseDegrees, $relativePath);
        if ($binary === null) {
            return false;
        }

        return Storage::disk($disk)->put($relativePath, $binary);
    }

    private static function isCompressible(UploadedFile $file): bool
    {
        $mime = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));
        if ($mime === '' || str_contains($mime, 'pdf') || str_contains($mime, 'svg')) {
            return false;
        }

        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true);
    }

    private static function compressPath(string $path, int $originalSize, int $maxEdge = self::MAX_EDGE, int $quality = self::JPEG_QUALITY): ?string
    {
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        return self::compressBinary($raw, $originalSize, null, $maxEdge, $quality);
    }

    private static function compressBinary(string $raw, int $originalSize, ?int $orientation = null, int $maxEdge = self::MAX_EDGE, int $quality = self::JPEG_QUALITY): ?string
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        self::bumpMemory();

        $source = @imagecreatefromstring($raw);
        if ($source === false) {
            return null;
        }

        $orientation = $orientation ?? self::readOrientationFromBinary($raw);
        $source = self::applyOrientation($source, $orientation);

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width < 1 || $height < 1) {
            imagedestroy($source);

            return null;
        }

        $scale = min(1, $maxEdge / max($width, $height));
        $newW = max(1, (int) round($width * $scale));
        $newH = max(1, (int) round($height * $scale));

        if ($scale < 1) {
            $canvas = imagecreatetruecolor($newW, $newH);
            if ($canvas === false) {
                imagedestroy($source);

                return null;
            }
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $newW, $newH, $white);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($source);
            $source = $canvas;
        } else {
            $source = self::flattenOnWhite($source);
        }

        if (function_exists('imageinterlace')) {
            imageinterlace($source, true);
        }

        ob_start();
        $ok = imagejpeg($source, null, $quality);
        $jpeg = ob_get_clean();
        imagedestroy($source);

        if (! $ok || ! is_string($jpeg) || $jpeg === '') {
            return null;
        }

        // Keep the original when compression did not help a small file,
        // unless EXIF orientation had to be baked in.
        if ($orientation === 1 && $originalSize > 0 && strlen($jpeg) >= $originalSize && $originalSize < 180000 && $scale >= 1) {
            return null;
        }

        return $jpeg;
    }

    private static function flattenOnWhite($source)
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            return $source;
        }
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);
        imagedestroy($source);

        return $canvas;
    }

    /**
     * GD imagerotate() uses counter-clockwise degrees; avoid negative angles (some builds ignore them).
     */
    private static function turn($image, int $clockwiseDegrees)
    {
        $clockwiseDegrees %= 360;
        if ($clockwiseDegrees < 0) {
            $clockwiseDegrees += 360;
        }
        if ($clockwiseDegrees === 0 || ! function_exists('imagerotate')) {
            return $image;
        }

        $gdAngle = (360 - $clockwiseDegrees) % 360;
        $turned = imagerotate($image, $gdAngle, 0);
        if (! $turned) {
            return $image;
        }

        imagedestroy($image);

        return $turned;
    }

    private static function rotateBinary(string $raw, int $clockwiseDegrees, string $relativePath): ?string
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        self::bumpMemory();

        $source = @imagecreatefromstring($raw);
        if ($source === false) {
            return null;
        }

        $source = self::flattenOnWhite($source);
        $source = self::turn($source, $clockwiseDegrees);

        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $isPng = $ext === 'png';

        ob_start();
        $ok = $isPng && function_exists('imagepng')
            ? imagepng($source)
            : imagejpeg($source, null, self::JPEG_QUALITY);
        $out = ob_get_clean();
        imagedestroy($source);

        if (! $ok || ! is_string($out) || $out === '') {
            return null;
        }

        return $out;
    }

    private static function applyOrientation($image, int $orientation)
    {
        return match ($orientation) {
            2 => self::flip($image, IMG_FLIP_HORIZONTAL),
            3 => self::turn($image, 180),
            4 => self::flip($image, IMG_FLIP_VERTICAL),
            5 => self::flip(self::turn($image, 90), IMG_FLIP_HORIZONTAL),
            6 => self::turn($image, 90),
            7 => self::flip(self::turn($image, 270), IMG_FLIP_HORIZONTAL),
            8 => self::turn($image, 270),
            default => $image,
        };
    }

    private static function flip($image, int $mode)
    {
        if (function_exists('imageflip')) {
            imageflip($image, $mode);

            return $image;
        }

        return $image;
    }

    private static function readOrientationFromBinary(string $raw): int
    {
        if (! function_exists('exif_read_data') || $raw === '') {
            return 1;
        }

        $tmp = @tempnam(sys_get_temp_dir(), 'exif');
        if ($tmp === false) {
            return 1;
        }

        @file_put_contents($tmp, $raw);
        $exif = @exif_read_data($tmp);
        @unlink($tmp);

        $orientation = (int) ($exif['Orientation'] ?? 1);

        return $orientation >= 1 && $orientation <= 8 ? $orientation : 1;
    }

    private static function bumpMemory(): void
    {
        $current = ini_get('memory_limit');
        if (! is_string($current) || $current === '' || $current === '-1') {
            return;
        }

        $bytes = self::memoryToBytes($current);
        if ($bytes > 0 && $bytes < 256 * 1024 * 1024) {
            @ini_set('memory_limit', '256M');
        }
    }

    private static function memoryToBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }
}
