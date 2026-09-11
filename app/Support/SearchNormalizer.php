<?php

namespace App\Support;

class SearchNormalizer
{
    public static function digits(string $value): string
    {
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        return strtr(trim($value), $map);
    }

    public static function term(string $value): string
    {
        return preg_replace('/\s+/u', ' ', self::digits($value)) ?? '';
    }
}
