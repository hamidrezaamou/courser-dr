@props([
    'name',
    'mobile',
    'mobileSecondary' => null,
    'nationalCode' => null,
    'meta' => null,
    'patientUrl' => null,
    'editUrl' => null,
    'printUrl' => null,
    'smsBody' => null,
    'triggerLabel' => 'ابزار',
    'sheetMode' => null,
    'canClinical' => false,
    'subjectType' => null,
    'subjectId' => null,
    'patientId' => null,
    'dateLabel' => null,
    'surgeryAppointmentId' => null,
    'surgeryTypeId' => null,
    'surgerySubtypeId' => null,
    'hasSurgeryChecklist' => false,
    'openChecklistOnOpen' => false,
    'canChangeStatus' => false,
    'status' => null,
    'statusLabel' => null,
    'statusUrl' => null,
    'statusActions' => [],
    'answerBooking' => null,
])

@php
    $digits = preg_replace('/\D+/', '', (string) $mobile) ?? '';
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

    $payload = [
        'name' => $name,
        'mobile' => $mobile,
        'mobileSecondary' => $mobileSecondary ?: null,
        'nationalCode' => $nationalCode,
        'meta' => $meta,
        'patientUrl' => $patientUrl,
        'editUrl' => $editUrl,
        'printUrl' => $printUrl,
        'telHref' => $telHref,
        'smsHref' => $smsHref,
        'smsBody' => $smsBody,
        'smsEnabled' => (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir',
        'readyAnswersEnabled' => \App\Support\FeatureFlags::enabled('features.ready_answers'),
        'sheetMode' => $sheetMode,
        'canClinical' => (bool) $canClinical,
        'subjectType' => $subjectType,
        'subjectId' => $subjectId,
        'patientId' => $patientId,
        'dateLabel' => $dateLabel,
        'surgeryAppointmentId' => $surgeryAppointmentId,
        'surgeryTypeId' => $surgeryTypeId,
        'surgerySubtypeId' => $surgerySubtypeId,
        'hasSurgeryChecklist' => (bool) $hasSurgeryChecklist,
        'openChecklistOnOpen' => (bool) $openChecklistOnOpen,
        'canChangeStatus' => (bool) $canChangeStatus,
        'status' => $status,
        'statusLabel' => $statusLabel,
        'statusUrl' => $statusUrl,
        'statusActions' => $statusActions ?: [],
        'answerBooking' => $answerBooking,
    ];
@endphp

<button
    type="button"
    {{ $attributes->class('row-toolbox-trigger touch-action !min-h-8 !px-2.5 !py-1 !text-[11px]') }}
    style="background:var(--panel-soft);color:var(--ink)"
    data-toolbox-trigger
    data-toolbox-b64="{{ base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
>
    {{ $triggerLabel }}
</button>
