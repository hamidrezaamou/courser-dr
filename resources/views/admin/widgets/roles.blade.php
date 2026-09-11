@php
    $roleCounts = $widgetData['roles']['roleCounts'] ?? [];
    $staffTotal = $widgetData['roles']['staff_total'] ?? 0;
@endphp
<section class="admin-panel admin-dash-panel">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">ترکیب کاربران</h3>
        <a href="{{ route('admin.users.index') }}" class="admin-panel__link">مدیریت</a>
    </div>
    <div class="admin-roles">
        <div class="admin-role"><span>مدیر</span><strong>{{ number_format($roleCounts['admin'] ?? 0) }}</strong></div>
        <div class="admin-role"><span>پزشک</span><strong>{{ number_format($roleCounts['doctor'] ?? 0) }}</strong></div>
        <div class="admin-role"><span>منشی</span><strong>{{ number_format($roleCounts['assistant'] ?? 0) }}</strong></div>
        <div class="admin-role"><span>حساب بیمار</span><strong>{{ number_format($roleCounts['patient'] ?? 0) }}</strong></div>
    </div>
    <p class="admin-panel__hint">پرسنل فعال: {{ number_format($staffTotal) }} نفر</p>
</section>
