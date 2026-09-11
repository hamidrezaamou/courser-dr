<?php

namespace App\Support;

use App\Models\ClinicSchedule;
use App\Models\SurgeryAppointment;
use App\Models\SurgeryProgramGroup;
use App\Models\SurgeryProgramGroupDay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Per-subtype surgery capacity: restricted subtypes cost (1 + remove_count) capacity units
 * and remove remove_count slots from the end of the day.
 */
class SurgeryCapacityLimits
{
    /** @var array<string, array<int, int>> */
    private static array $limitsCache = [];

    /** @var array<string, Collection<int, SurgeryAppointment>> */
    private static array $appointmentsCache = [];

    /**
     * @return list<array{subtype_id: int, remove_count: int}>
     */
    public static function rulesFromSettings(?array $settings): array
    {
        if (! is_array($settings) || ! ($settings['capacityLimitsEnabled'] ?? false)) {
            return [];
        }

        $raw = $settings['capacityLimits'] ?? [];
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $subtypeId = (int) ($row['subtype_id'] ?? 0);
            $removeCount = (int) ($row['remove_count'] ?? 0);
            if ($subtypeId > 0 && $removeCount > 0) {
                $out[] = [
                    'subtype_id' => $subtypeId,
                    'remove_count' => min(99, $removeCount),
                ];
            }
        }

        return $out;
    }

    /**
     * @return array<int, int> subtype_id => remove_count
     */
    public static function limitsMapForDay(int $hospitalId, string $dateKey, int $typeId): array
    {
        $cacheKey = $hospitalId.'|'.$dateKey.'|'.$typeId;
        if (array_key_exists($cacheKey, self::$limitsCache)) {
            return self::$limitsCache[$cacheKey];
        }

        $schedules = ClinicSchedule::query()
            ->where('kind', 'surgery')
            ->where('hospital_id', $hospitalId)
            ->where('surgery_type_id', $typeId)
            ->where('date_key', $dateKey)
            ->get();

        $map = [];
        foreach ($schedules as $schedule) {
            foreach (self::rulesFromSettings($schedule->settings) as $rule) {
                $map[$rule['subtype_id']] = max(
                    $map[$rule['subtype_id']] ?? 0,
                    $rule['remove_count']
                );
            }
        }

        return self::$limitsCache[$cacheKey] = $map;
    }

    public static function findRepresentativeSchedule(
        int $hospitalId,
        string $dateKey,
        int $typeId,
        ?int $subtypeId
    ): ?ClinicSchedule {
        if ($subtypeId) {
            $schedule = self::findSchedule($hospitalId, $dateKey, $typeId, $subtypeId);
            if ($schedule) {
                return $schedule;
            }
        }

        $general = self::findSchedule($hospitalId, $dateKey, $typeId, null);
        if ($general) {
            return $general;
        }

        return ClinicSchedule::query()
            ->where('kind', 'surgery')
            ->where('hospital_id', $hospitalId)
            ->where('surgery_type_id', $typeId)
            ->where('date_key', $dateKey)
            ->first();
    }

    public static function findSchedule(
        int $hospitalId,
        string $dateKey,
        int $typeId,
        ?int $subtypeId
    ): ?ClinicSchedule {
        $query = ClinicSchedule::query()
            ->where('kind', 'surgery')
            ->where('hospital_id', $hospitalId)
            ->where('surgery_type_id', $typeId)
            ->where('date_key', $dateKey);

        if ($subtypeId) {
            $query->where('surgery_subtype_id', $subtypeId);
        } else {
            $query->whereNull('surgery_subtype_id');
        }

        return $query->first();
    }

    /**
     * Capacity units consumed by one booking (1 slot + tail removal for restricted subtypes).
     *
     * @param  array<int, int>  $limitsMap
     */
    public static function bookingCost(?int $subtypeId, array $limitsMap): int
    {
        if ($subtypeId && isset($limitsMap[$subtypeId])) {
            return 1 + $limitsMap[$subtypeId];
        }

        return 1;
    }

    public static function groupForSchedule(ClinicSchedule $schedule): ?SurgeryProgramGroup
    {
        if ($schedule->kind !== 'surgery' || ! $schedule->surgery_type_id) {
            return null;
        }

        return SurgeryProgramGroup::findFor(
            (int) $schedule->surgery_type_id,
            $schedule->surgery_subtype_id ? (int) $schedule->surgery_subtype_id : null
        );
    }

    /**
     * Limit an appointment query to the independent capacity pool of this type/subtype
     * (a named program group, or the same surgery type excluding other groups).
     */
    public static function constrainAppointmentPool(
        Builder $query,
        int $typeId,
        ?int $subtypeId,
    ): Builder {
        $group = SurgeryProgramGroup::findFor($typeId, $subtypeId);
        if ($group) {
            return $group->applyToAppointmentQuery($query);
        }

        $query->where('surgery_type_id', $typeId);
        $groupedSubtypeIds = SurgeryProgramGroup::groupedSubtypeIdsForType($typeId);
        if ($groupedSubtypeIds !== []) {
            $query->where(function (Builder $q) use ($groupedSubtypeIds) {
                $q->whereNull('surgery_subtype_id')
                    ->orWhereNotIn('surgery_subtype_id', $groupedSubtypeIds);
            });
        }

        return $query;
    }

    /**
     * @return Collection<int, SurgeryAppointment>
     */
    public static function poolAppointments(ClinicSchedule $schedule, ?int $excludeAppointmentId = null): Collection
    {
        if ($schedule->kind !== 'surgery' || ! $schedule->hospital_id || ! $schedule->surgery_type_id) {
            return collect();
        }

        try {
            $gregorian = Jalali::parseJalaliDate($schedule->date_key)->toDateString();
        } catch (\Throwable) {
            return collect();
        }

        $group = self::groupForSchedule($schedule);
        $poolKey = $group
            ? 'group:'.$group->id
            : 'type:'.$schedule->surgery_type_id;
        $cacheKey = implode('|', [
            $schedule->hospital_id,
            $schedule->date_key,
            $poolKey,
            $excludeAppointmentId ?: 0,
        ]);
        if (array_key_exists($cacheKey, self::$appointmentsCache)) {
            return self::$appointmentsCache[$cacheKey];
        }

        $query = SurgeryAppointment::query()
            ->whereDate('scheduled_date', $gregorian)
            ->where('hospital_id', $schedule->hospital_id)
            ->holdingSlot()
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId));

        self::constrainAppointmentPool(
            $query,
            (int) $schedule->surgery_type_id,
            $schedule->surgery_subtype_id ? (int) $schedule->surgery_subtype_id : null
        );

        return self::$appointmentsCache[$cacheKey] = $query
            ->get(['id', 'surgery_type_id', 'surgery_subtype_id']);
    }

    public static function baseTotal(ClinicSchedule $schedule): int
    {
        $fallback = count($schedule->slotOptions());
        $group = self::groupForSchedule($schedule);
        if (! $group || ! $schedule->hospital_id) {
            return $fallback;
        }

        $groupTotal = SurgeryProgramGroupDay::totalFor(
            (int) $group->id,
            (int) $schedule->hospital_id,
            $schedule->date_key
        );

        return $groupTotal !== null ? max(1, $groupTotal) : $fallback;
    }

    public static function usedCapacityUnits(ClinicSchedule $schedule, ?int $excludeAppointmentId = null): int
    {
        if ($schedule->kind !== 'surgery') {
            return 0;
        }

        $limitsMap = self::limitsMapForDay(
            (int) $schedule->hospital_id,
            $schedule->date_key,
            (int) $schedule->surgery_type_id
        );

        $appointments = self::poolAppointments($schedule, $excludeAppointmentId);
        if ($limitsMap === []) {
            return $appointments->count();
        }

        $used = 0;
        foreach ($appointments as $appointment) {
            $subtypeId = $appointment->surgery_subtype_id ? (int) $appointment->surgery_subtype_id : null;
            $used += self::bookingCost($subtypeId, $limitsMap);
        }

        return $used;
    }

    public static function remainingCapacityUnits(ClinicSchedule $schedule, ?int $excludeAppointmentId = null): int
    {
        return max(0, self::baseTotal($schedule) - self::usedCapacityUnits($schedule, $excludeAppointmentId));
    }

    public static function canBookSubtype(
        ClinicSchedule $schedule,
        ?int $subtypeId,
        ?int $excludeAppointmentId = null,
        bool $isException = false,
    ): bool {
        if ($isException) {
            return true;
        }

        $limitsMap = self::limitsMapForDay(
            (int) $schedule->hospital_id,
            $schedule->date_key,
            (int) $schedule->surgery_type_id
        );

        $cost = self::bookingCost($subtypeId, $limitsMap);

        return self::remainingCapacityUnits($schedule, $excludeAppointmentId) >= $cost;
    }

    public static function totalRemovedSlots(
        ClinicSchedule $schedule,
        ?int $excludeAppointmentId = null
    ): int {
        $typeId = (int) $schedule->surgery_type_id;
        $limitsMap = self::limitsMapForDay(
            (int) $schedule->hospital_id,
            $schedule->date_key,
            $typeId
        );
        if ($limitsMap === []) {
            return 0;
        }

        $appointments = self::poolAppointments($schedule, $excludeAppointmentId);
        $total = 0;
        foreach ($appointments as $appointment) {
            $subtypeId = (int) ($appointment->surgery_subtype_id ?? 0);
            if ($subtypeId > 0 && isset($limitsMap[$subtypeId])) {
                $total += $limitsMap[$subtypeId];
            }
        }

        return $total;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function effectiveSlotOptions(ClinicSchedule $schedule, ?int $excludeAppointmentId = null): array
    {
        $all = $schedule->slotOptions();
        $removed = self::totalRemovedSlots($schedule, $excludeAppointmentId);
        $keep = max(0, count($all) - $removed);

        return array_slice($all, 0, $keep);
    }

    public static function effectiveTotal(ClinicSchedule $schedule, ?int $excludeAppointmentId = null): int
    {
        return self::remainingCapacityUnits($schedule, $excludeAppointmentId);
    }

    /**
     * @return array<string, mixed>
     */
    public static function capacityMeta(
        ClinicSchedule $schedule,
        ?int $subtypeId,
        ?int $excludeAppointmentId = null,
        bool $isException = false,
    ): array {
        $limitsMap = self::limitsMapForDay(
            (int) $schedule->hospital_id,
            $schedule->date_key,
            (int) $schedule->surgery_type_id
        );
        $baseTotal = self::baseTotal($schedule);
        $remaining = self::remainingCapacityUnits($schedule, $excludeAppointmentId);
        $cost = self::bookingCost($subtypeId, $limitsMap);
        $group = self::groupForSchedule($schedule);

        return [
            'base_total' => $baseTotal,
            'remaining_units' => $remaining,
            'used_units' => $baseTotal - $remaining,
            'subtype_cost' => $cost,
            'subtype_allowed' => $isException || $remaining >= $cost,
            'limits' => $limitsMap,
            'program_group_id' => $group?->id,
            'program_group_name' => $group?->name,
        ];
    }

    public static function assertSlotBookable(
        ClinicSchedule $schedule,
        string $normalizedTime,
        ?int $bookingSubtypeId,
        ?int $excludeAppointmentId = null,
        bool $isException = false,
    ): void {
        if (! self::canBookSubtype($schedule, $bookingSubtypeId, $excludeAppointmentId, $isException)) {
            $limitsMap = self::limitsMapForDay(
                (int) $schedule->hospital_id,
                $schedule->date_key,
                (int) $schedule->surgery_type_id
            );
            $cost = self::bookingCost($bookingSubtypeId, $limitsMap);

            throw ValidationException::withMessages([
                'surgery_subtype_id' => "ظرفیت کافی برای این زیرگروه باقی نمانده (نیاز: {$cost} واحد). از نوبت استثنا استفاده کنید.",
            ]);
        }

        $norm = SlotLabel::normalize($normalizedTime);
        $effectiveValues = array_map(
            fn (array $opt) => SlotLabel::normalize($opt['value']),
            self::effectiveSlotOptions($schedule, $excludeAppointmentId)
        );
        $allValues = array_map(
            fn (array $opt) => SlotLabel::normalize($opt['value']),
            $schedule->slotOptions()
        );

        if (in_array($norm, $effectiveValues, true)) {
            return;
        }

        if ($isException && ! in_array($norm, $allValues, true)) {
            return;
        }

        if (in_array($norm, $allValues, true)) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'این نوبت به‌دلیل محدودیت ظرفیت زیرگروه از انتهای این روز حذف شده است.',
            ]);
        }

        if (! $isException) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'این نوبت در برنامه این روز تعریف نشده یا پر است.',
            ]);
        }
    }

    /**
     * @param  mixed  $raw
     * @return list<array{subtype_id: int, remove_count: int}>
     */
    public static function parseRequest(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $subtypeId = (int) ($row['subtype_id'] ?? 0);
            $removeCount = (int) ($row['remove_count'] ?? 0);
            if ($subtypeId > 0 && $removeCount > 0) {
                $out[] = [
                    'subtype_id' => $subtypeId,
                    'remove_count' => min(99, $removeCount),
                ];
            }
        }

        return $out;
    }

    /**
     * @param  list<array{subtype_id: int, remove_count: int}>  $rules
     */
    public static function mergeIntoSettings(array $settings, bool $enabled, array $rules): array
    {
        if ($enabled && $rules !== []) {
            $settings['capacityLimitsEnabled'] = true;
            $settings['capacityLimits'] = $rules;
        } else {
            unset($settings['capacityLimitsEnabled'], $settings['capacityLimits']);
        }

        return $settings;
    }
}
