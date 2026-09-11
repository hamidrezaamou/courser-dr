<?php

namespace App\Support;

class Digits
{
    public static function toEnglish(string $value): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace([...$persian, ...$arabic], [...$english, ...$english], $value);
    }

    public static function only(string $value): string
    {
        return preg_replace('/\D+/', '', self::toEnglish($value)) ?? '';
    }

    /**
     * Group an Iranian mobile for print/readability: 000   000   00 00
     */
    public static function groupedMobile(?string $raw): string
    {
        $digits = self::only((string) $raw);
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 11) {
            return substr($digits, 0, 4).'   '.substr($digits, 4, 3).'   '.substr($digits, 7, 2).' '.substr($digits, 9, 2);
        }

        if (strlen($digits) === 10) {
            return substr($digits, 0, 3).'   '.substr($digits, 3, 3).'   '.substr($digits, 6, 2).' '.substr($digits, 8, 2);
        }

        return $digits;
    }

    /**
     * Normalize an Iranian phone for matching/export: 09xxxxxxxxx when possible.
     */
    public static function iranMobile(?string $raw): string
    {
        $digits = self::only((string) $raw);
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        if (strlen($digits) < 8) {
            return '';
        }

        return $digits;
    }
}
