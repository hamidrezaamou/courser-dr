@php $g = $widgetData['patients_growth']['growth'] ?? []; @endphp
<section class="admin-panel admin-stat-card">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">رشد پرونده‌ها</h3>
    </div>
    <div class="admin-mini-kpis">
        <article>
            <span>امروز</span>
            <strong>{{ number_format($g['today'] ?? 0) }}</strong>
        </article>
        <article>
            <span>این هفته</span>
            <strong>{{ number_format($g['week'] ?? 0) }}</strong>
        </article>
        <article>
            <span>این ماه</span>
            <strong>{{ number_format($g['month'] ?? 0) }}</strong>
        </article>
        <article>
            <span>کل</span>
            <strong>{{ number_format($g['total'] ?? 0) }}</strong>
        </article>
    </div>
</section>
