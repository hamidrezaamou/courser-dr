<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class FollowUpTiming
{
    /**
     * @return array<string, string>
     */
    public static function units(): array
    {
        return [
            'minute' => 'دقیقه',
            'hour' => 'ساعت',
            'day' => 'روز',
            'week' => 'هفته',
            'month' => 'ماه',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function directions(): array
    {
        return [
            'before' => 'قبل از رویداد',
            'after' => 'بعد از رویداد',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function referenceEvents(): array
    {
        return [
            'surgery_date' => 'تاریخ عمل',
            'appointment_date' => 'تاریخ نوبت',
            'visit_date' => 'تاریخ ویزیت',
            'patient_created' => 'تاریخ ایجاد پرونده',
            'custom' => 'تاریخ سفارشی',
        ];
    }

    public static function apply(
        CarbonInterface $reference,
        int $amount,
        string $unit,
        string $direction
    ): Carbon {
        $due = Carbon::parse($reference);
        $amount = max(0, $amount);
        $unit = self::units()[$unit] ?? null ? $unit : 'day';

        if ($amount === 0) {
            return $due;
        }

        $method = match ($unit) {
            'minute' => 'Minutes',
            'hour' => 'Hours',
            'week' => 'Weeks',
            'month' => 'Months',
            default => 'Days',
        };

        if ($direction === 'before') {
            return $due->{'sub'.$method}($amount);
        }

        return $due->{'add'.$method}($amount);
    }

    public static function summarize(int $amount, string $unit, string $direction): string
    {
        $unitLabel = self::units()[$unit] ?? $unit;
        $dirLabel = self::directions()[$direction] ?? $direction;
        if ($amount === 0) {
            return 'همان روز رویداد';
        }

        return fa_digits((string) $amount).' '.$unitLabel.' '.$dirLabel;
    }
}
