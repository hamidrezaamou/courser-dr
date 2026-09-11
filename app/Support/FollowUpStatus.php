<?php

namespace App\Support;

class FollowUpStatus
{
    public const PENDING = 'pending';

    public const IN_PROGRESS = 'in_progress';

    public const DONE = 'done';

    public const CANCELLED = 'cancelled';

    public const REJECTED = 'rejected';

    public const FAILED = 'failed';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::IN_PROGRESS,
            self::DONE,
            self::CANCELLED,
            self::REJECTED,
            self::FAILED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function open(): array
    {
        return [self::PENDING, self::IN_PROGRESS];
    }

    /**
     * @return list<string>
     */
    public static function terminal(): array
    {
        return [self::DONE, self::CANCELLED, self::REJECTED, self::FAILED];
    }

    public static function isOpen(string $status): bool
    {
        return in_array($status, self::open(), true);
    }

    public static function isTerminal(string $status): bool
    {
        return in_array($status, self::terminal(), true);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::PENDING => 'در انتظار',
            'due' => 'سررسید شده',
            'overdue' => 'عقب‌افتاده',
            self::IN_PROGRESS => 'در حال انجام',
            self::DONE => 'انجام شده',
            self::CANCELLED => 'لغو شده',
            self::REJECTED => 'رد شده',
            self::FAILED => 'ناموفق',
        ];
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }

    /**
     * Display status: stored value, or due/overdue when still open.
     */
    public static function display(string $status, $dueAt): string
    {
        if (! self::isOpen($status) || ! $dueAt) {
            return $status;
        }

        $due = $dueAt instanceof \Carbon\CarbonInterface
            ? $dueAt->copy()
            : \Carbon\Carbon::parse($dueAt);

        if ($due->lt(now()->startOfDay())) {
            return 'overdue';
        }
        if ($due->lte(now())) {
            return 'due';
        }

        return $status;
    }
}
