<?php

namespace App\Support;

use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Models\SurgerySubtype;
use App\Models\SurgeryType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class SurgeryCooldown
{
    /**
     * @return array{conflict: bool, days: int, scope: string, message: string, confirm_message: string, previous: array<string, mixed>|null}
     */
    public static function inspect(
        ?int $subtypeId,
        ?string $eyeSide,
        ?string $scheduledDate,
        ?int $patientId = null,
        ?string $nationalCode = null,
        ?int $exceptAppointmentId = null,
        ?int $typeId = null,
    ): array {
        SurgeryType::ensureCooldownColumn();
        SurgerySubtype::ensureCooldownColumn();

        $empty = [
            'conflict' => false,
            'days' => 0,
            'scope' => '',
            'message' => '',
            'confirm_message' => '',
            'previous' => null,
        ];

        $subtype = null;
        $type = null;
        if ($subtypeId) {
            $subtype = SurgerySubtype::query()->with('surgeryType')->find($subtypeId);
            $type = $subtype?->surgeryType;
        }
        if (! $type && $typeId) {
            $type = SurgeryType::query()->find($typeId);
        }

        $typeDays = Schema::hasColumn('surgery_types', 'cooldown_days')
            ? (int) ($type?->cooldown_days ?? 0)
            : 0;
        $subDays = Schema::hasColumn('surgery_subtypes', 'cooldown_days')
            ? (int) ($subtype?->cooldown_days ?? 0)
            : 0;

        if ($typeDays >= 1 && $type) {
            $scope = 'type';
            $days = $typeDays;
        } elseif ($subDays >= 1 && $subtype) {
            $scope = 'subtype';
            $days = $subDays;
        } else {
            return $empty;
        }

        $eye = EyeSide::normalize($eyeSide);
        if ($eye === null || ! $scheduledDate) {
            return $empty;
        }

        try {
            $target = Carbon::parse($scheduledDate)->startOfDay();
        } catch (\Throwable) {
            return $empty;
        }

        $nationalCode = Digits::toEnglish(trim((string) $nationalCode));
        if ($patientId) {
            $patient = Patient::query()->find($patientId);
            if ($patient && $nationalCode === '') {
                $nationalCode = (string) $patient->national_code;
            }
        }

        if (! $patientId && $nationalCode === '') {
            return $empty;
        }

        $rows = SurgeryAppointment::query()
            ->with(['hospital', 'surgerySubtype', 'surgeryType'])
            ->when($scope === 'type', function ($query) use ($type) {
                $subtypeIds = $type->subtypes()->pluck('id');
                $query->where(function ($inner) use ($type, $subtypeIds) {
                    $inner->where('surgery_type_id', $type->id);
                    if ($subtypeIds->isNotEmpty()) {
                        $inner->orWhereIn('surgery_subtype_id', $subtypeIds);
                    }
                    $name = trim((string) $type->name);
                    if ($name !== '') {
                        $inner->orWhere('surgery_type', $name)
                            ->orWhere('surgery_type', 'like', $name.' — %')
                            ->orWhere('surgery_type', 'like', $name.' - %');
                    }
                });
            }, function ($query) use ($subtypeId) {
                $query->where('surgery_subtype_id', $subtypeId);
            })
            ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::NO_SHOW])
            ->when($exceptAppointmentId, fn ($q) => $q->where('id', '!=', $exceptAppointmentId))
            ->where(function ($query) use ($patientId, $nationalCode) {
                $query->whereRaw('1 = 0');
                if ($patientId) {
                    $query->orWhere('patient_id', $patientId);
                }
                if ($nationalCode !== '') {
                    $query->orWhere('national_code', $nationalCode);
                }
            })
            ->orderByDesc('scheduled_date')
            ->orderByDesc('scheduled_time')
            ->get();

        $closest = null;
        $closestDiff = null;
        foreach ($rows as $row) {
            if (! EyeSide::overlaps($eye, $row->eye_side)) {
                continue;
            }
            $rowDate = $row->scheduled_date?->copy()?->startOfDay();
            if (! $rowDate) {
                continue;
            }
            $diff = (int) $target->diff($rowDate)->days;
            if ($diff >= $days) {
                continue;
            }
            if ($closestDiff === null || $diff < $closestDiff) {
                $closest = $row;
                $closestDiff = $diff;
            }
        }

        if (! $closest) {
            return $empty;
        }

        $typeName = $closest->surgeryType?->name ?: ($type?->name ?: ($closest->surgery_type ?: 'عمل'));
        $subName = $closest->surgerySubtype?->name
            ?: ($subtype?->name ?: '');
        $eyeLabel = EyeSide::label($closest->eye_side);
        $dateLabel = jalali($closest->scheduled_date, 'Y/m/d');
        $timeLabel = $closest->scheduled_time
            ? SlotLabel::display((string) $closest->scheduled_time)
            : '—';
        $hospital = $closest->hospital?->name ?: '—';
        $status = BookingStatus::label((string) $closest->status);
        $when = $closest->scheduled_date?->lt($target)
            ? $closestDiff.' روز پیش'
            : ($closestDiff === 0 ? 'همین روز' : $closestDiff.' روز بعد');

        if ($scope === 'type') {
            $message = 'برای همه زیرگروه‌های «'.$typeName.'» روی چشم '.$eyeLabel
                .' باید حداقل '.$days.' روز فاصله باشد. نوبت تداخلی'
                .($subName !== '' ? ' («'.$subName.'»)' : '')
                .' '.$when.' ثبت شده است.';
        } else {
            $message = 'برای «'.$typeName.($subName !== '' ? ' · '.$subName : '').'» روی چشم '.$eyeLabel
                .' باید حداقل '.$days.' روز فاصله باشد. نوبت تداخلی '.$when.' ثبت شده است.';
        }

        $confirm = $message."\n\n"
            .'نوبت قبلی/نزدیک: '.$dateLabel.' · '.$timeLabel."\n"
            .'مرکز: '.$hospital."\n"
            .'چشم: '.$eyeLabel.' · وضعیت: '.$status."\n\n"
            .'با اطلاع از این محدودیت ادامه می‌دهید؟';

        return [
            'conflict' => true,
            'days' => $days,
            'scope' => $scope,
            'message' => $message,
            'confirm_message' => $confirm,
            'previous' => [
                'id' => $closest->id,
                'date' => $dateLabel,
                'time' => $timeLabel,
                'hospital' => $hospital,
                'type' => $typeName,
                'subtype' => $subName,
                'eye' => $eyeLabel,
                'status' => $status,
                'when' => $when,
                'diff_days' => $closestDiff,
                'scope' => $scope,
            ],
        ];
    }
}
