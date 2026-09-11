<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\SurgeryAppointment;

class AppointmentSms
{
    public const DEFAULT = '{name} عزیز، {slotNumber} شما برای {date} در {hospital} ساعت {time} ثبت شد.';

    /** @var array<string, ClinicSchedule|null> */
    private static array $scheduleCache = [];

    /**
     * @return list<array{id: string, label: string, body: string}>
     */
    public static function presets(): array
    {
        return [
            [
                'id' => 'confirm',
                'label' => 'تأیید ثبت نوبت',
                'body' => '{name} عزیز، {slotNumber} شما برای {date} در {hospital} ساعت {time} ثبت شد.',
            ],
            [
                'id' => 'remind',
                'label' => 'یادآوری نوبت',
                'body' => '{name} عزیز، یادآوری نوبت شما: {date} ساعت {time} — {hospital}',
            ],
            [
                'id' => 'surgery',
                'label' => 'تأیید عمل',
                'body' => '{name} عزیز، نوبت عمل {surgeryType} شما برای {date} در {hospital} ({slotNumber}، ساعت {time}) ثبت شد.',
            ],
            [
                'id' => 'visit',
                'label' => 'تأیید ویزیت',
                'body' => '{name} عزیز، نوبت ویزیت شما برای {date} ساعت {time} ثبت شد.',
            ],
        ];
    }

    public static function defaultFor(string $kind): string
    {
        return $kind === 'surgery'
            ? '{name} عزیز، نوبت عمل {surgeryType} شما برای {date} در {hospital} ({slotNumber}، ساعت {time}) ثبت شد.'
            : '{name} عزیز، نوبت ویزیت شما برای {date} ساعت {time} ثبت شد.';
    }

    public static function forItem(Appointment|SurgeryAppointment $item, string $kind): string
    {
        $schedule = self::scheduleFor($item, $kind);
        $template = $schedule?->smsText() ?: self::defaultFor($kind);

        return self::render($template, self::vars($item, $kind));
    }

    /**
     * @param  array<string, string>  $vars
     */
    public static function render(?string $template, array $vars): string
    {
        $text = trim((string) $template);
        if ($text === '') {
            $text = self::DEFAULT;
        }

        foreach ($vars as $key => $value) {
            $text = str_replace('{'.$key.'}', $value, $text);
        }

        return $text;
    }

    /**
     * @return array<string, string>
     */
    public static function vars(Appointment|SurgeryAppointment $item, string $kind): array
    {
        $date = Jalali::format($item->scheduled_date, 'Y/m/d');
        $rawTime = (string) ($item->scheduled_time ?? '');
        $time = $rawTime !== '' ? SlotLabel::display($rawTime) : '—';
        $slotNumber = $time;
        $hospital = 'مطب';
        $surgeryType = '';
        $eyeType = '';

        if ($item instanceof SurgeryAppointment) {
            $hospital = $item->hospital?->name ?: '—';
            $surgeryType = (string) ($item->surgery_type ?: 'عمل');
            $eyeType = (string) ($item->eye_side ?: '');
        } elseif ($item instanceof Appointment) {
            $surgeryType = (string) ($item->visit_type ?: 'ویزیت');
        }

        return [
            'name' => (string) $item->patient_name,
            'date' => $date,
            'slotNumber' => $slotNumber,
            'hospital' => $hospital,
            'time' => $time,
            'surgeryType' => $surgeryType,
            'eyeType' => $eyeType,
            'description' => (string) ($item->notes ?? ''),
        ];
    }

    public static function scheduleFor(Appointment|SurgeryAppointment $item, string $kind): ?ClinicSchedule
    {
        $dateKey = Jalali::format($item->scheduled_date, 'Y/m/d');
        $hospitalId = $item instanceof SurgeryAppointment ? (int) ($item->hospital_id ?: 0) : 0;
        $typeId = $item instanceof SurgeryAppointment ? (int) ($item->surgery_type_id ?: 0) : 0;
        $subtypeId = $item instanceof SurgeryAppointment ? (int) ($item->surgery_subtype_id ?: 0) : 0;
        $cacheKey = implode('|', [$kind, $dateKey, $hospitalId, $typeId, $subtypeId]);

        if (array_key_exists($cacheKey, self::$scheduleCache)) {
            return self::$scheduleCache[$cacheKey];
        }

        $query = ClinicSchedule::query()
            ->with(['hospital', 'surgeryType'])
            ->where('kind', $kind === 'surgery' ? 'surgery' : 'visit')
            ->where('date_key', $dateKey);

        if ($kind === 'surgery' && $hospitalId) {
            $query->where('hospital_id', $hospitalId);
        } else {
            $query->whereNull('hospital_id');
        }

        $rows = $query->get();
        $match = null;

        if ($item instanceof SurgeryAppointment) {
            if ($typeId > 0) {
                $typedRows = $rows->where('surgery_type_id', $typeId);
                $match = $typedRows->first(function (ClinicSchedule $row) use ($subtypeId) {
                    $rowSubtypeId = (int) ($row->surgery_subtype_id ?: 0);

                    return $rowSubtypeId === $subtypeId && filled($row->smsText());
                });

                // A general template of the same operation is a safe fallback for its subtypes.
                if (! $match && $subtypeId > 0) {
                    $match = $typedRows->first(function (ClinicSchedule $row) {
                        return ! $row->surgery_subtype_id && filled($row->smsText());
                    });
                }
            } else {
                // Legacy/HIS rows may not have catalog IDs; only match the same operation name.
                $typeName = trim((string) $item->surgery_type);
                $typedRows = $rows->filter(function (ClinicSchedule $row) use ($typeName) {
                    return $typeName !== '' && $row->surgeryType?->name === $typeName;
                });
                $match = $typedRows->first(fn (ClinicSchedule $row) => filled($row->smsText()));
            }
        } else {
            $match = $rows->first(fn (ClinicSchedule $row) => filled($row->smsText()))
                ?: $rows->first();
        }

        self::$scheduleCache[$cacheKey] = $match;

        return $match;
    }
}
