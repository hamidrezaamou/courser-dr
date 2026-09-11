<?php

namespace App\Support;

class IranianId
{
    public static function normalizeNationalCode(?string $raw): ?string
    {
        $digits = Digits::only((string) $raw);

        return strlen($digits) === 10 ? $digits : null;
    }

    public static function isValidNationalCode(?string $raw): bool
    {
        // فقط ۱۰ رقم — بدون الگوریتم checksum (طبق نیاز مطب)
        return self::normalizeNationalCode($raw) !== null;
    }

    public static function normalizeMobile(?string $raw): ?string
    {
        $digits = Digits::only((string) $raw);
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return preg_match('/^09\d{9}$/', $digits) ? $digits : null;
    }

    public static function isValidMobile(?string $raw): bool
    {
        return self::normalizeMobile($raw) !== null;
    }
}
