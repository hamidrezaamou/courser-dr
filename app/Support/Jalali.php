<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

class Jalali
{
    /**
     * Format a Gregorian date/time as Jalali (Shamsi).
     */
    public static function format(DateTimeInterface|string|null $date, string $format = 'Y/m/d H:i'): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = $date instanceof Carbon
            ? $date
            : Carbon::parse($date);

        [$jy, $jm, $jd] = self::toJalali(
            (int) $carbon->format('Y'),
            (int) $carbon->format('m'),
            (int) $carbon->format('d')
        );

        $replacements = [
            'Y' => sprintf('%04d', $jy),
            'y' => sprintf('%02d', $jy % 100),
            'm' => sprintf('%02d', $jm),
            'n' => (string) $jm,
            'd' => sprintf('%02d', $jd),
            'j' => (string) $jd,
            'H' => $carbon->format('H'),
            'i' => $carbon->format('i'),
            's' => $carbon->format('s'),
        ];

        $result = '';
        $length = strlen($format);

        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];
            $result .= $replacements[$char] ?? $char;
        }

        return $result;
    }

    /**
     * Convert Gregorian date to Jalali [year, month, day].
     *
     * @return array{0:int,1:int,2:int}
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666
            + (365 * $gy)
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            + $gd
            + $g_d_m[$gm - 1];

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    /**
     * Convert Jalali date to Gregorian [year, month, day].
     *
     * @return array{0:int,1:int,2:int}
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668
            + (365 * $jy)
            + intdiv($jy, 33) * 8
            + intdiv(($jy % 33) + 3, 4)
            + $jd
            + ($jm < 7 ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $sal_a = [0, 31, ($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 0;

        for ($gm = 1; $gm <= 12 && $gd > $sal_a[$gm]; $gm++) {
            $gd -= $sal_a[$gm];
        }

        return [$gy, $gm, $gd];
    }

    /**
     * Parse a Jalali date string (Y/m/d) into a Carbon instance (Gregorian).
     */
    public static function parseJalaliDate(string $jalaliDate): Carbon
    {
        $parts = preg_split('/[\/\-.]/', trim($jalaliDate));
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('فرمت تاریخ شمسی نامعتبر است.');
        }

        [$jy, $jm, $jd] = array_map('intval', $parts);
        [$gy, $gm, $gd] = self::toGregorian($jy, $jm, $jd);

        return Carbon::createFromDate($gy, $gm, $gd)->startOfDay();
    }

    /**
     * Days in a Jalali month.
     */
    public static function daysInMonth(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }
        if ($jm <= 11) {
            return 30;
        }

        return self::isLeapYear($jy) ? 30 : 29;
    }

    public static function isLeapYear(int $jy): bool
    {
        $breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
        $bl = count($breaks);
        $jp = $breaks[0];
        $jump = 0;

        for ($i = 1; $i < $bl; $i++) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;
            if ($jy < $jm) {
                break;
            }
            $jp = $jm;
        }

        $n = $jy - $jp;
        if ($jump - $n < 6) {
            $n = $n - $jump + intdiv($jump + 4, 33) * 33;
        }

        $leap = ((($n + 1) % 33) - 1) % 4;
        if ($leap === -1) {
            $leap = 4;
        }

        return $leap === 0;
    }

    /**
     * Persian weekday name for a Gregorian date (شنبه … جمعه).
     */
    public static function weekdayName(DateTimeInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = $date instanceof Carbon
            ? $date
            : Carbon::parse($date);

        return match ((int) $carbon->dayOfWeek) {
            Carbon::SATURDAY => 'شنبه',
            Carbon::SUNDAY => 'یکشنبه',
            Carbon::MONDAY => 'دوشنبه',
            Carbon::TUESDAY => 'سه‌شنبه',
            Carbon::WEDNESDAY => 'چهارشنبه',
            Carbon::THURSDAY => 'پنجشنبه',
            Carbon::FRIDAY => 'جمعه',
            default => '',
        };
    }
}
