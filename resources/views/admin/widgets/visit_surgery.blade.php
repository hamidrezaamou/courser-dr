@php
    $visits = (int) ($widgetData['visit_surgery']['visits'] ?? 0);
    $surgeries = (int) ($widgetData['visit_surgery']['surgeries'] ?? 0);
    $total = max(1, (int) ($widgetData['visit_surgery']['total'] ?? ($visits + $surgeries)));
@endphp
<section class="admin-panel admin-stat-card">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">ویزیت در برابر عمل</h3>
        <span class="admin-panel__hint">امروز</span>
    </div>
    <div class="admin-split-stat">
        <div>
            <span class="admin-split-stat__label">ویزیت</span>
            <strong class="admin-split-stat__value">{{ number_format($visits) }}</strong>
            <div class="admin-split-stat__track"><span style="width: {{ round(($visits / $total) * 100) }}%"></span></div>
        </div>
        <div>
            <span class="admin-split-stat__label">عمل</span>
            <strong class="admin-split-stat__value">{{ number_format($surgeries) }}</strong>
            <div class="admin-split-stat__track admin-split-stat__track--warm"><span style="width: {{ round(($surgeries / $total) * 100) }}%"></span></div>
        </div>
    </div>
</section>
