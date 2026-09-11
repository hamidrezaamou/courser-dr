<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\SurgeryAppointment;
use Illuminate\Validation\ValidationException;

class SlotGuard
{
    public static function normalizeTime(string $time): string
    {
        return SlotLabel::normalize($time);
    }

    /**
     * Call inside DB::transaction() so lockForUpdate holds until create/update commits.
     */
    public static function assertVisitSlotFree(string $gregorianDate, string $time, ?int $ignoreId = null): void
    {
        $query = Appointment::query()
            ->whereDate('scheduled_date', $gregorianDate)
            ->where('scheduled_time', $time)
            ->whereIn('status', BookingStatus::holding())
            ->lockForUpdate();

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'این نوبت/ساعت قبلاً برای ویزیت رزرو شده است.',
            ]);
        }
    }

    /**
     * Call inside DB::transaction() so lockForUpdate holds until create/update commits.
     *
     * Queue tokens (نوبت ۱ / Q01) are exclusive only inside the same capacity pool:
     * a program group, or the same surgery type when ungrouped. Another group at the
     * same hospital and day may reuse نوبت ۱.
     *
     * Choice when $isException=true: intentionally bypass capacity exclusivity so an
     * out-of-schedule / overflow booking can share a clock time. We still lock the
     * row set for consistency, but do not reject on an existing holder.
     */
    public static function assertSurgerySlotFree(
        string $gregorianDate,
        string $time,
        int $hospitalId,
        ?int $ignoreId = null,
        bool $isException = false,
        ?int $typeId = null,
        ?int $subtypeId = null,
    ): void {
        $query = SurgeryAppointment::query()
            ->whereDate('scheduled_date', $gregorianDate)
            ->where('hospital_id', $hospitalId)
            ->whereIn('scheduled_time', self::slotTimeAliases($time))
            ->whereIn('status', BookingStatus::holding())
            ->lockForUpdate();

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($typeId) {
            SurgeryCapacityLimits::constrainAppointmentPool($query, $typeId, $subtypeId);
        }

        if ($isException) {
            return;
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'این نوبت در همین گروه برنامه قبلاً رزرو شده است.',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public static function slotTimeAliases(string $time): array
    {
        $norm = SlotLabel::normalize($time);
        $out = [$time, $norm];

        if (SlotLabel::isQueue($norm)) {
            $n = (int) preg_replace('/\D/', '', $norm);
            $out[] = sprintf('Q%02d', $n);
            $out[] = 'Q'.$n;
            $out[] = 'نوبت '.$n;
            $out[] = sprintf('00:%02d:00', $n);
        }

        return array_values(array_unique(array_filter($out, fn ($v) => $v !== '')));
    }
}
