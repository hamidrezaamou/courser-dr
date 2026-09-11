<?php

use App\Support\Jalali;

if (! function_exists('jalali')) {
    /**
     * Format a date in Jalali (Shamsi) calendar.
     */
    function jalali(\DateTimeInterface|string|null $date, string $format = 'Y/m/d H:i'): string
    {
        return Jalali::format($date, $format);
    }
}

if (! function_exists('jalali_weekday')) {
    function jalali_weekday(\DateTimeInterface|string|null $date): string
    {
        return Jalali::weekdayName($date);
    }
}

if (! function_exists('grouped_mobile')) {
    function grouped_mobile(?string $raw): string
    {
        return \App\Support\Digits::groupedMobile($raw);
    }
}

if (! function_exists('fa_digits')) {
    /**
     * Convert Western digits to Persian digits for print forms.
     */
    function fa_digits(mixed $value): string
    {
        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }
}
