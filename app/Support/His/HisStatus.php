<?php

namespace App\Support\His;

use App\Support\BookingStatus;
use App\Support\Digits;

class HisStatus
{
    /**
     * Translate a raw HIS status into one of the website's booking statuses.
     *
     * The real HIS codes are not known until the discovery script has run, so
     * the table lives in config/his.php and can be corrected without a deploy.
     * Anything unrecognised falls back rather than being written through, which
     * keeps `appointments.status` inside the set the rest of the app expects.
     */
    public static function forAppointment(mixed $raw): string
    {
        $map = (array) config('his.status_map.appointments', []);
        $fallback = (string) config('his.status_map.default', BookingStatus::SCHEDULED);

        $key = self::key($raw);
        if ($key === '') {
            return $fallback;
        }

        // A HIS value that already matches one of ours needs no translation.
        if (in_array($key, BookingStatus::all(), true)) {
            return $key;
        }

        foreach ($map as $from => $to) {
            if (self::key($from) === $key) {
                return in_array($to, BookingStatus::all(), true) ? $to : $fallback;
            }
        }

        return $fallback;
    }

    /** Lowercase, strip separators, so "No Show", "no_show" and "NOSHOW" agree. */
    private static function key(mixed $raw): string
    {
        $text = mb_strtolower(trim((string) $raw));
        $text = str_replace([' ', '-', '_', '.'], '', $text);

        // Numeric HIS codes stay numeric so they can be mapped explicitly.
        return $text === '' ? '' : (ctype_digit($text) ? Digits::only($text) : $text);
    }
}
