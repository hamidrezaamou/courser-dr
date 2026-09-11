@php
    $funnel = $widgetData['funnel']['funnel'] ?? [];
    $funnelMax = max(1, max($funnel ?: [0]));
    $funnelLabels = [
        'scheduled' => 'ثبت‌شده',
        'confirmed' => 'تایید',
        'done' => 'انجام',
        'cancelled' => 'لغو',
    ];
@endphp
<section class="admin-panel admin-dash-panel">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">قیف وضعیت امروز</h3>
    </div>
    <div class="admin-funnel">
        @foreach ($funnelLabels as $key => $label)
            <div class="admin-funnel__row">
                <span class="admin-funnel__label">{{ $label }}</span>
                <div class="admin-funnel__track">
                    <span class="admin-funnel__bar admin-funnel__bar--{{ $key }}" style="width: {{ round((($funnel[$key] ?? 0) / $funnelMax) * 100) }}%"></span>
                </div>
                <strong class="admin-funnel__count">{{ number_format($funnel[$key] ?? 0) }}</strong>
            </div>
        @endforeach
    </div>
</section>
