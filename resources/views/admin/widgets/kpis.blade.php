@php
    $kpis = $widgetData['kpis']['kpis'] ?? [];
    $deltaToday = $widgetData['kpis']['deltaToday'] ?? 0;
@endphp
<section class="admin-kpi-grid">
    <article class="admin-kpi">
        <span class="admin-kpi__label">کل پرونده‌ها</span>
        <strong class="admin-kpi__value">{{ number_format($kpis['patients_total'] ?? 0) }}</strong>
        <span class="admin-kpi__meta">+{{ number_format($kpis['patients_new_today'] ?? 0) }} امروز · {{ number_format($kpis['patients_new_week'] ?? 0) }} این هفته</span>
    </article>
    <article class="admin-kpi admin-kpi--accent">
        <span class="admin-kpi__label">نوبت‌های امروز</span>
        <strong class="admin-kpi__value">{{ number_format($kpis['today_total'] ?? 0) }}</strong>
        <span class="admin-kpi__meta">
            @if ($deltaToday > 0)
                {{ number_format($deltaToday) }} بیشتر از دیروز
            @elseif ($deltaToday < 0)
                {{ number_format(abs($deltaToday)) }} کمتر از دیروز
            @else
                برابر دیروز
            @endif
        </span>
    </article>
    <article class="admin-kpi admin-kpi--ok">
        <span class="admin-kpi__label">تایید شده امروز</span>
        <strong class="admin-kpi__value">{{ number_format($kpis['today_confirmed'] ?? 0) }}</strong>
        <span class="admin-kpi__meta">{{ number_format($kpis['today_cancelled'] ?? 0) }} لغو شده</span>
    </article>
    <article class="admin-kpi {{ ($kpis['followups_pending'] ?? 0) > 0 ? 'admin-kpi--warn' : '' }}">
        <span class="admin-kpi__label">پیگیری معوق</span>
        <strong class="admin-kpi__value">{{ number_format($kpis['followups_pending'] ?? 0) }}</strong>
        <span class="admin-kpi__meta">{{ number_format($kpis['followups_upcoming'] ?? 0) }} پیش‌رو</span>
    </article>
</section>
