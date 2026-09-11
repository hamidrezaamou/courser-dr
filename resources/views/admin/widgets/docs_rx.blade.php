@php $d = $widgetData['docs_rx']['docs'] ?? []; @endphp
<section class="admin-panel admin-stat-card">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">اسناد و نسخه‌ها</h3>
    </div>
    <div class="admin-mini-kpis">
        <article>
            <span>مدارک</span>
            <strong>{{ number_format($d['documents'] ?? 0) }}</strong>
        </article>
        <article>
            <span>مدارک هفته</span>
            <strong>{{ number_format($d['documents_week'] ?? 0) }}</strong>
        </article>
        <article>
            <span>نسخه</span>
            <strong>{{ number_format($d['prescriptions'] ?? 0) }}</strong>
        </article>
        <article>
            <span>نسخه هفته</span>
            <strong>{{ number_format($d['prescriptions_week'] ?? 0) }}</strong>
        </article>
    </div>
</section>
