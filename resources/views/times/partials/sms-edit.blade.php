@php
    $smsValue = $schedule->smsText() ?: \App\Support\AppointmentSms::defaultFor($schedule->kind);
@endphp
<details class="times-sms-edit" onclick="event.stopPropagation()">
    <summary class="times-sms-edit__toggle">✉️ پیامک</summary>
    <form method="POST" action="{{ route('times.update-sms', $schedule) }}" class="times-sms-edit__form">
        @csrf
        @method('PATCH')
        <textarea name="sms_text" rows="3" class="field-input !text-[11px] !py-1.5" maxlength="1000">{{ old('sms_text', $smsValue) }}</textarea>
        <button type="submit" class="times-sms-edit__save">ذخیره پیامک</button>
    </form>
</details>
