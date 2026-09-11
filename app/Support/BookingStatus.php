<?php

namespace App\Support;

class BookingStatus
{
    public const SCHEDULED = 'scheduled';

    public const CONFIRMED = 'confirmed';

    public const WAITING = 'waiting';

    public const READY = 'ready';

    public const IN_CONSULT = 'in_consult';

    public const DONE = 'done';

    public const CANCELLED = 'cancelled';

    public const NO_SHOW = 'no_show';

    public const PENDING_APPROVAL = 'pending_approval';

    /**
     * Statuses that still occupy a time slot.
     *
     * @return list<string>
     */
    public static function holding(): array
    {
        return [
            self::SCHEDULED,
            self::CONFIRMED,
            self::WAITING,
            self::READY,
            self::IN_CONSULT,
        ];
    }

    /**
     * Clinic floor queue (patient physically at the clinic).
     *
     * @return list<string>
     */
    public static function clinicQueue(): array
    {
        return [self::WAITING, self::READY, self::IN_CONSULT];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::SCHEDULED => 'ثبت‌شده',
            self::CONFIRMED => 'تأییدشده',
            self::WAITING => 'حضور / در صف',
            self::READY => 'ارجاع به پزشک',
            self::IN_CONSULT => 'نزد پزشک',
            self::DONE => 'انجام‌شده',
            self::CANCELLED => 'لغوشده',
            self::NO_SHOW => 'عدم حضور',
            self::PENDING_APPROVAL => 'در انتظار تأیید',
        ];
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::SCHEDULED,
            self::CONFIRMED,
            self::WAITING,
            self::READY,
            self::IN_CONSULT,
            self::DONE,
            self::CANCELLED,
            self::NO_SHOW,
            self::PENDING_APPROVAL,
        ];
    }

    /**
     * @return list<string>
     */
    public static function transitionsFrom(string $status): array
    {
        return match ($status) {
            self::SCHEDULED => [self::CONFIRMED, self::WAITING, self::DONE, self::NO_SHOW, self::CANCELLED],
            self::CONFIRMED => [self::WAITING, self::DONE, self::NO_SHOW, self::CANCELLED, self::SCHEDULED],
            self::WAITING => [self::READY, self::CONFIRMED, self::NO_SHOW, self::CANCELLED],
            self::READY => [self::IN_CONSULT, self::WAITING, self::DONE, self::NO_SHOW, self::CANCELLED],
            self::IN_CONSULT => [self::DONE, self::READY, self::CANCELLED],
            self::NO_SHOW => [self::SCHEDULED, self::CONFIRMED, self::WAITING, self::CANCELLED],
            self::CANCELLED => [self::SCHEDULED],
            self::PENDING_APPROVAL => [self::SCHEDULED, self::CANCELLED],
            default => [],
        };
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::transitionsFrom($from), true);
    }

    public static function actionLabel(string $to, ?string $from = null): string
    {
        if ($from === self::IN_CONSULT && $to === self::READY) {
            return 'بازگشت به ارجاع';
        }
        if ($from === self::READY && $to === self::WAITING) {
            return 'بازگشت به صف';
        }
        if ($from === self::WAITING && $to === self::CONFIRMED) {
            return 'خروج از صف';
        }

        return match ($to) {
            self::CONFIRMED => 'تأیید',
            self::WAITING => 'حضور بیمار',
            self::READY => 'ارجاع به پزشک',
            self::IN_CONSULT => 'شروع ویزیت',
            self::DONE => 'پایان / انجام شد',
            self::CANCELLED => 'لغو',
            self::SCHEDULED => 'بازگشت به ثبت‌شده',
            self::NO_SHOW => 'عدم حضور',
            default => self::label($to),
        };
    }

    public static function actionHint(string $to, ?string $from = null): string
    {
        if ($from === self::IN_CONSULT && $to === self::READY) {
            return 'بیمار به صف ارجاع برگردد؟';
        }
        if ($from === self::READY && $to === self::WAITING) {
            return 'بیمار به صف انتظار برگردد؟';
        }
        if ($from === self::WAITING && $to === self::CONFIRMED) {
            return 'بیمار از صف خارج شود؟';
        }

        return match ($to) {
            self::CONFIRMED => 'نوبت تأیید شود و برای بیمار قطعی گردد؟',
            self::WAITING => 'بیمار در مطب حضور دارد و وارد صف شود؟',
            self::READY => 'بیمار به پزشک ارجاع شود؟',
            self::IN_CONSULT => 'ویزیت نزد پزشک شروع شود؟',
            self::DONE => 'وضعیت به «انجام شد» تغییر کند؟',
            self::CANCELLED => 'نوبت لغو شود؟ این کار قابل بازگشت است ولی ظرفیت آزاد می‌شود.',
            self::SCHEDULED => 'نوبت به حالت ثبت‌شده برگردد؟',
            self::NO_SHOW => 'بیمار حاضر نشد و وضعیت «عدم حضور» ثبت شود؟ ظرفیت آزاد می‌شود.',
            default => 'این تغییر اعمال شود؟',
        };
    }

    /**
     * @return list<array{id:string,label:string,hint:string}>
     */
    public static function toolboxActionsFor(string $from): array
    {
        $out = [];
        foreach (self::transitionsFrom($from) as $next) {
            $out[] = [
                'id' => $next,
                'label' => self::actionLabel($next, $from),
                'hint' => self::actionHint($next, $from),
            ];
        }

        return $out;
    }

    public static function staffCanChange(?\App\Models\User $user): bool
    {
        return $user !== null && in_array($user->role, ['doctor', 'admin', 'assistant'], true);
    }
}
