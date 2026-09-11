@props([
    'kind' => 'visit', // visit|surgery
    'excludeId' => null,
    'date' => null,
    'time' => null,
    'mode' => 'calendar', // calendar|select
])

@php
    $dateValue = old('scheduled_date', $date);
    $timeValue = old('scheduled_time', $time);
    $useSelect = $mode === 'select';
@endphp

<div
    class="booking-picker"
    data-booking-picker
    data-kind="{{ $kind }}"
    data-mode="{{ $useSelect ? 'select' : 'calendar' }}"
    data-slots-url="{{ route('appointments.slots') }}"
    @if($kind === 'visit') data-calendar-url="{{ route('appointments.visit-calendar') }}" @endif
    @if($excludeId) data-exclude-id="{{ $excludeId }}" @endif
>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label value="تاریخ نوبت" />
            @if ($useSelect)
                <select
                    name="scheduled_date"
                    data-date-select
                    data-initial="{{ $dateValue }}"
                    class="field-input"
                    required
                >
                    <option value="">-- انتخاب تاریخ --</option>
                </select>
            @else
                <input
                    type="text"
                    name="scheduled_date"
                    data-date-input
                    class="field-input cursor-pointer"
                    placeholder="کلیک برای انتخاب تاریخ شمسی..."
                    readonly
                    required
                    value="{{ $dateValue }}"
                >
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('scheduled_date')" />
        </div>
        <div>
            <x-input-label value="ساعت انتخاب‌شده" />
            <input
                type="text"
                name="scheduled_time"
                data-time-input
                class="field-input"
                placeholder="از گرید زیر انتخاب کنید"
                readonly
                required
                value="{{ $timeValue }}"
            >
            <x-input-error class="mt-2" :messages="$errors->get('scheduled_time')" />
        </div>
    </div>

    <div class="booking-time-section mt-4" data-time-section @if(!$dateValue) style="display:none;" @endif>
        <div class="booking-slot-info">
            تایم‌های آزاد برای تاریخ
            <strong data-selected-date-text>{{ $dateValue }}</strong>
        </div>
        <div class="booking-time-grid" data-time-grid></div>
        <label class="mt-3 flex items-center gap-2 text-xs" style="color: var(--muted);">
            <input type="checkbox" data-hide-booked>
            مخفی کردن تایم‌های پر شده
        </label>
    </div>
</div>

@unless ($useSelect)
<div class="booking-calendar-modal" data-calendar-modal>
    <div class="booking-calendar-content">
        <div class="booking-calendar-header">
            <button type="button" data-next-month aria-label="ماه بعد">‹</button>
            <strong data-month-label></strong>
            <button type="button" data-prev-month aria-label="ماه قبل">›</button>
        </div>
        <div class="booking-calendar-grid" data-calendar-grid></div>
    </div>
</div>
@endunless
