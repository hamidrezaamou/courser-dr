@php
    $days = $widgetData['week_bookings']['days'] ?? [];
    $totals = array_column($days, 'total');
    $max = max(1, $totals ? max($totals) : 1);
@endphp
<section class="admin-panel admin-stat-card">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">نوبت ۷ روز اخیر</h3>
    </div>
    <div class="admin-week-bars">
        @foreach ($days as $day)
            @php $pct = (int) round(($day['total'] / $max) * 100); @endphp
            <div class="admin-week-bars__item" title="{{ $day['total'] }} نوبت">
                <div class="admin-week-bars__col">
                    <span class="admin-week-bars__fill" style="height: {{ max(8, $pct) }}%"></span>
                </div>
                <span class="admin-week-bars__label">{{ $day['label'] }}</span>
                <strong class="admin-week-bars__value">{{ number_format($day['total']) }}</strong>
            </div>
        @endforeach
    </div>
</section>
