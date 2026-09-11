@php $c = $widgetData['catalog']['catalog'] ?? []; @endphp
<section class="admin-panel admin-stat-card">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">فهرست کلینیک</h3>
        <a href="{{ route('settings.index') }}" class="admin-panel__link">تنظیمات</a>
    </div>
    <div class="admin-mini-kpis">
        <article>
            <span>بیمارستان</span>
            <strong>{{ number_format($c['hospitals'] ?? 0) }}</strong>
        </article>
        <article>
            <span>دارو</span>
            <strong>{{ number_format($c['drugs'] ?? 0) }}</strong>
        </article>
        <article>
            <span>نوع عمل</span>
            <strong>{{ number_format($c['surgery_types'] ?? 0) }}</strong>
        </article>
        <article>
            <span>ویزیت ثبت‌شده</span>
            <strong>{{ number_format($c['visits_total'] ?? 0) }}</strong>
        </article>
    </div>
</section>
