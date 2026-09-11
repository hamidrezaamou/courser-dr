<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\PatientFollowUp;
use App\Models\SurgeryAppointment;

class ToolboxPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function fromBoardRow(array $row): array
    {
        $item = $row['item'];
        $isSurgery = $row['type'] === 'surgery';
        $time = $item->scheduled_time ? SlotLabel::display((string) $item->scheduled_time) : '—';
        $dateJalali = jalali($item->scheduled_date, 'Y/m/d');

        if ($isSurgery) {
            $place = $item->hospital?->name ?: '—';
            $kindLine = trim(($item->surgery_type ?: 'عمل').($item->eye_side ? ' · '.$item->eye_side : ''));
            $meta = trim($kindLine.' · '.$place.' · '.$dateJalali.' '.$time);
            $smsBody = AppointmentSms::forItem($item, 'surgery');
            $editUrl = route('surgery-appointments.edit', $item);
            $printUrl = route('surgery-appointments.prints', $item);
            $subjectType = 'surgery';
            $ids = SurgeryChecklist::resolveTypeIds($item);
        } else {
            $kindLine = trim(($item->visit_type ?: 'ویزیت').($item->reason ? ' · '.$item->reason : ''));
            $meta = trim($kindLine.' · '.$dateJalali.' '.$time);
            $smsBody = AppointmentSms::forItem($item, 'visit');
            $editUrl = route('appointments.edit', $item);
            $printUrl = null;
            $subjectType = 'visit';
            $ids = ['type_id' => null, 'subtype_id' => null];
        }

        $digits = preg_replace('/\D+/', '', (string) $item->mobile) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }
        $telHref = preg_match('/^09\d{9}$/', $digits) ? 'tel:+98'.substr($digits, 1) : null;
        $smsHref = preg_match('/^09\d{9}$/', $digits)
            ? 'sms:+98'.substr($digits, 1).($smsBody ? '?body='.rawurlencode($smsBody) : '')
            : null;

        $canChangeStatus = BookingStatus::staffCanChange(auth()->user());
        $mobileSecondary = trim((string) ($item->mobile_secondary ?? $item->patient?->mobile_secondary ?? ''));

        return [
            'name' => $item->patient_name,
            'mobile' => $item->mobile,
            'mobileSecondary' => $mobileSecondary !== '' ? $mobileSecondary : null,
            'nationalCode' => $item->national_code,
            'meta' => $meta,
            'patientUrl' => route('patients.show', $item->patient_id),
            'editUrl' => $editUrl,
            'printUrl' => $printUrl,
            'telHref' => $telHref,
            'smsHref' => $smsHref,
            'smsBody' => $smsBody,
            'smsEnabled' => (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir',
            'readyAnswersEnabled' => FeatureFlags::enabled('features.ready_answers'),
            'subjectType' => $subjectType,
            'subjectId' => $item->id,
            'patientId' => $item->patient_id,
            'dateLabel' => $dateJalali,
            'surgeryAppointmentId' => $isSurgery ? $item->id : null,
            'surgeryTypeId' => $ids['type_id'],
            'surgerySubtypeId' => $ids['subtype_id'],
            'hasSurgeryChecklist' => $isSurgery && SurgeryChecklist::isAvailable(),
            'canChangeStatus' => $canChangeStatus,
            'status' => (string) $item->status,
            'statusLabel' => BookingStatus::label((string) $item->status),
            'statusUrl' => $isSurgery
                ? route('surgery-appointments.status', $item)
                : route('appointments.status', $item),
            'statusActions' => $canChangeStatus ? BookingStatus::toolboxActionsFor((string) $item->status) : [],
            'answerBooking' => MessageTags::bookingPayload($item, $isSurgery ? 'surgery' : 'visit'),
        ];
    }

    /**
     * Build the same toolbox payload used by appointment/report rows.
     *
     * @return array<string, mixed>
     */
    public static function fromFollowUp(PatientFollowUp $followUp): array
    {
        $followUp->loadMissing([
            'patient',
            'hospital',
            'appointment',
            'surgeryAppointment.hospital',
        ]);

        if ($followUp->surgeryAppointment) {
            $payload = self::fromBoardRow([
                'type' => 'surgery',
                'item' => $followUp->surgeryAppointment,
            ]);
        } elseif ($followUp->appointment) {
            $payload = self::fromBoardRow([
                'type' => 'visit',
                'item' => $followUp->appointment,
            ]);
        } else {
            $patient = $followUp->patient;
            $mobile = (string) ($patient?->mobile ?? '');
            $digits = preg_replace('/\D+/', '', $mobile) ?? '';
            if (str_starts_with($digits, '98') && strlen($digits) === 12) {
                $digits = '0'.substr($digits, 2);
            }
            if (str_starts_with($digits, '9') && strlen($digits) === 10) {
                $digits = '0'.$digits;
            }

            $smsBody = trim(($patient?->name ? $patient->name.' عزیز، ' : '').$followUp->title);
            $mobileSecondary = trim((string) ($patient?->mobile_secondary ?? ''));
            $canEdit = $patient
                && auth()->user()?->canEditPatient()
                && ! str_starts_with((string) $patient->national_code, 'DEL-');

            $payload = [
                'name' => $patient?->name,
                'mobile' => $mobile,
                'mobileSecondary' => $mobileSecondary !== '' ? $mobileSecondary : null,
                'nationalCode' => $patient?->national_code,
                'meta' => collect([
                    $followUp->hospital?->name,
                    'سررسید: '.$followUp->dueJalali(),
                ])->filter()->implode(' · '),
                'patientUrl' => $patient ? route('patients.show', $patient) : null,
                'editUrl' => $canEdit ? route('patients.edit', ['patient' => $patient, 'from_file' => 1]) : null,
                'printUrl' => null,
                'telHref' => preg_match('/^09\d{9}$/', $digits) ? 'tel:+98'.substr($digits, 1) : null,
                'smsHref' => preg_match('/^09\d{9}$/', $digits)
                    ? 'sms:+98'.substr($digits, 1).'?body='.rawurlencode($smsBody)
                    : null,
                'smsBody' => $smsBody,
                'smsEnabled' => (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir',
                'readyAnswersEnabled' => FeatureFlags::enabled('features.ready_answers'),
                'subjectType' => null,
                'subjectId' => null,
                'patientId' => $patient?->id,
                'dateLabel' => $followUp->dueJalali(),
                'surgeryAppointmentId' => null,
                'surgeryTypeId' => null,
                'surgerySubtypeId' => null,
                'hasSurgeryChecklist' => false,
                'canChangeStatus' => false,
                'status' => null,
                'statusLabel' => null,
                'statusUrl' => null,
                'statusActions' => [],
            ];
        }

        $payload['sheetMode'] = 'followup';
        $payload['canClinical'] = (bool) auth()->user()?->canManageClinical();
        $payload['meta'] = collect([
            $payload['meta'] ?? null,
            'پیگیری: '.$followUp->title,
        ])->filter()->implode(' · ');

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function encode(array $payload): string
    {
        return base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
