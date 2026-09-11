@props([
    'name' => 'date',
    'value' => null,
    'label' => 'تاریخ شمسی',
    'required' => true,
    'allowPast' => true,
    'allowFriday' => true,
    'placeholder' => 'کلیک برای انتخاب تاریخ...',
])

@php
    $dateValue = old($name, $value);
@endphp

<div
    class="jalali-date-field"
    data-jalali-date
    data-allow-past="{{ $allowPast ? '1' : '0' }}"
    data-allow-friday="{{ $allowFriday ? '1' : '0' }}"
>
    @if($label)
        <x-input-label :value="$label" />
    @endif
    <input
        type="text"
        name="{{ $name }}"
        data-jalali-date-input
        class="field-input mt-1 cursor-pointer font-mono"
        dir="ltr"
        placeholder="{{ $placeholder }}"
        readonly
        @if($required) required @endif
        value="{{ $dateValue }}"
        {{ $attributes->except(['name', 'value', 'label', 'required', 'allowPast', 'allowFriday', 'placeholder']) }}
    >
    <div class="booking-calendar-modal" data-jalali-date-modal>
        <div class="booking-calendar-content">
            <div class="booking-calendar-header">
                <button type="button" data-next-month aria-label="ماه بعد">‹</button>
                <strong data-month-label></strong>
                <button type="button" data-prev-month aria-label="ماه قبل">›</button>
            </div>
            <div class="booking-calendar-grid" data-calendar-grid></div>
        </div>
    </div>
</div>
