<?php

namespace App\Support;

class ReminderPause
{
    private const KEY = 'reminders.blocked_dates';

    /**
     * @return list<string> Jalali dates Y/m/d
     */
    public static function blockedDates(): array
    {
        $raw = SiteSettings::get(self::KEY, '[]');
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded)
            ? array_values(array_filter(array_map('strval', $decoded)))
        : [];
    }

    public static function isBlocked(string $jalaliDate): bool
    {
        return in_array(trim($jalaliDate), self::blockedDates(), true);
    }

    public static function isActive(string $jalaliDate): bool
    {
        return ! self::isBlocked($jalaliDate);
    }

    public static function block(string $jalaliDate): void
    {
        $dates = self::blockedDates();
        $jalaliDate = trim($jalaliDate);

        if (! in_array($jalaliDate, $dates, true)) {
            $dates[] = $jalaliDate;
        }

        self::save($dates);
    }

    public static function unblock(string $jalaliDate): void
    {
        $jalaliDate = trim($jalaliDate);
        $dates = array_values(array_filter(
            self::blockedDates(),
            fn (string $d) => $d !== $jalaliDate
        ));

        self::save($dates);
    }

    /**
     * @param  list<string>  $dates
     */
    private static function save(array $dates): void
    {
        SiteSettings::put(self::KEY, json_encode(array_values(array_unique($dates)), JSON_UNESCAPED_UNICODE));
        SiteSettings::applyToConfig();
    }
}
