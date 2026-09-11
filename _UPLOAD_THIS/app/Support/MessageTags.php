<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\SurgeryAppointment;

class MessageTags
{
    /**
     * Chips shown in the ready-answers composer.
     *
     * @return array{person: list<array{token: string, label: string}>, booking: list<array{token: string, label: string}>}
     */
    public static function chips(): array
    {
        return [
            'person' => [
                ['token' => '{نام}', 'label' => 'نام'],
                ['token' => '{موبایل}', 'label' => 'موبایل'],
                ['token' => '{موبایل۲}', 'label' => 'موبایل ۲'],
                ['token' => '{کدملی}', 'label' => 'کد ملی'],
                ['token' => '{امروز}', 'label' => 'امروز'],
                ['token' => '{مطب}', 'label' => 'مطب'],
            ],
            'booking' => [
                ['token' => '{تاریخ}', 'label' => 'تاریخ نوبت'],
                ['token' => '{ساعت}', 'label' => 'ساعت نوبت'],
                ['token' => '{نوبت}', 'label' => 'شماره نوبت'],
                ['token' => '{بیمارستان}', 'label' => 'بیمارستان'],
                ['token' => '{آدرس بیمارستان}', 'label' => 'آدرس بیمارستان'],
                ['token' => '{نوع عمل}', 'label' => 'نوع عمل'],
                ['token' => '{نوع ویزیت}', 'label' => 'نوع ویزیت'],
                ['token' => '{چشم}', 'label' => 'چشم'],
            ],
        ];
    }

    /**
     * Token names that come from a visit/surgery rather than the live clock.
     *
     * @return list<string>
     */
    public static function bookingKeys(): array
    {
        return [
            'تاریخ', 'ساعت', 'نوبت', 'بیمارستان', 'آدرس بیمارستان', 'نوع عمل', 'نوع ویزیت', 'چشم', 'توضیحات',
            'date', 'time', 'slotNumber', 'hospital', 'hospitalAddress', 'surgeryType', 'eyeType', 'description',
        ];
    }

    /**
     * Structured booking used by the ready-answers panel (toolbox + picker).
     *
     * @return array{kind: string, id: int, label: string, meta: string, vars: array<string, string>}
     */
    public static function bookingPayload(Appointment|SurgeryAppointment $item, string $kind): array
    {
        if ($item instanceof SurgeryAppointment) {
            $item->loadMissing('hospital');
        }

        $sms = AppointmentSms::vars($item, $kind);
        $clinic = (string) config('app.name', 'مطب');
        $mobileSecondary = trim((string) ($item->mobile_secondary ?? $item->patient?->mobile_secondary ?? ''));
        $isSurgery = $kind === 'surgery' || $item instanceof SurgeryAppointment;
        $typeLabel = (string) $sms['surgeryType'];
        $hospitalAddress = '';
        if ($item instanceof SurgeryAppointment) {
            $hospitalAddress = trim((string) ($item->hospital?->address ?? ''));
        }

        $vars = [
            'نام' => (string) $sms['name'],
            'name' => (string) $sms['name'],
            'موبایل' => (string) ($item->mobile ?? ''),
            'موبایل۲' => $mobileSecondary,
            'کدملی' => (string) ($item->national_code ?? ''),
            'تاریخ' => (string) $sms['date'],
            'date' => (string) $sms['date'],
            'ساعت' => (string) $sms['time'],
            'time' => (string) $sms['time'],
            'نوبت' => (string) $sms['slotNumber'],
            'slotNumber' => (string) $sms['slotNumber'],
            'بیمارستان' => (string) $sms['hospital'],
            'hospital' => (string) $sms['hospital'],
            'آدرس بیمارستان' => $hospitalAddress,
            'hospitalAddress' => $hospitalAddress,
            'نوع عمل' => $isSurgery ? $typeLabel : '',
            'نوع ویزیت' => $isSurgery ? '' : $typeLabel,
            'surgeryType' => $typeLabel,
            'چشم' => (string) $sms['eyeType'],
            'eyeType' => (string) $sms['eyeType'],
            'توضیحات' => (string) $sms['description'],
            'description' => (string) $sms['description'],
            'مطب' => $clinic,
        ];

        $kindLabel = $isSurgery ? 'عمل' : 'ویزیت';
        $title = $isSurgery
            ? trim($typeLabel.($sms['eyeType'] ? ' · '.$sms['eyeType'] : ''))
            : ($typeLabel !== '' ? $typeLabel : 'ویزیت');
        $place = $isSurgery ? (string) $sms['hospital'] : $clinic;

        return [
            'kind' => $isSurgery ? 'surgery' : 'visit',
            'id' => (int) $item->id,
            'label' => trim($kindLabel.($title !== '' ? ' · '.$title : '')),
            'meta' => trim($place.' · '.$sms['date'].' '.$sms['time']),
            'vars' => $vars,
        ];
    }
}
