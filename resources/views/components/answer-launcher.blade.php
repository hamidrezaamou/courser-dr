@props([
    'mobile' => '',
    'patientName' => '',
    'label' => 'پاسخ‌های آماده',
    'variant' => 'solid',
    'patientId' => null,
    'nationalCode' => null,
    'mobileSecondary' => null,
    'booking' => null,
])

@php
    // Full class names stay literal so Tailwind keeps them in the build.
    $variantClass = match ($variant) {
        'soft' => 'ans-launch--soft',
        'block' => 'ans-launch--block',
        default => '',
    };
    $bookingJson = $booking
        ? json_encode($booking, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
        : '';
@endphp

{{-- Opens the in-app ready answers panel (no external app) --}}
<button
    type="button"
    {{ $attributes->class(['ans-launch', $variantClass]) }}
    data-answer-launch
    data-answer-mobile="{{ $mobile }}"
    data-answer-name="{{ $patientName }}"
    data-answer-patient-id="{{ $patientId ?: '' }}"
    data-answer-national-code="{{ $nationalCode ?: '' }}"
    data-answer-mobile-secondary="{{ $mobileSecondary ?: '' }}"
    @if($bookingJson !== '')
        data-answer-booking="{{ $bookingJson }}"
    @endif
    title="{{ $label }}"
>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
    </svg>
    <span>{{ $label }}</span>
</button>
