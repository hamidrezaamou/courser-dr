@php
    $item = $row['item'];
    $isSurgery = $row['type'] === 'surgery';
    $time = $item->scheduled_time ? \App\Support\SlotLabel::display((string) $item->scheduled_time) : '—';
    $kind = $isSurgery
        ? trim(($item->surgery_type ?: 'عمل').($item->eye_side ? ' · '.$item->eye_side : ''))
        : trim(($item->visit_type ?: 'ویزیت').($item->reason ? ' · '.$item->reason : ''));
    $typeLabel = $isSurgery ? 'surgery' : 'visit';
@endphp
<article class="floor-card {{ $isSurgery ? 'floor-card--surgery' : 'floor-card--visit' }}">
    <div class="floor-card__top">
        <span class="floor-card__time" dir="ltr">{{ $time }}</span>
        <span class="floor-card__type {{ $isSurgery ? 'floor-card__type--surgery' : 'floor-card__type--visit' }}">
            {{ $isSurgery ? 'عمل' : 'ویزیت' }}
        </span>
    </div>
    <a href="{{ route('patients.show', $item->patient_id) }}" class="floor-card__name">{{ $item->patient_name }}</a>
    <p class="floor-card__kind">
        {{ $kind }}
        @if($isSurgery && $item->hospital)
            <span class="floor-card__hospital"> · {{ $item->hospital->name }}</span>
        @endif
    </p>
    <p class="floor-card__status">{{ \App\Support\BookingStatus::label($item->status) }}</p>

    <x-booking-status-actions
        :model="$item"
        :type="$typeLabel"
        variant="floor"
        :show-status="false"
        :patient-url="route('patients.show', $item->patient_id)"
    />
</article>
