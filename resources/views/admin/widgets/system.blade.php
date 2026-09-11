@php
    $latestBackup = $widgetData['system']['latestBackup'] ?? null;
@endphp
<section class="admin-panel admin-dash-panel" id="system">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">سامانه</h3>
        <a href="{{ route('admin.settings.system') }}" class="admin-panel__link">جزئیات</a>
    </div>
    <div class="admin-status-list">
        <div class="admin-status-row">
            <span>آخرین بک‌آپ</span>
            <strong>
                @if ($latestBackup)
                    {{ jalali(\Carbon\Carbon::createFromTimestamp($latestBackup['at']), 'Y/m/d H:i') }}
                @else
                    —
                @endif
            </strong>
        </div>
        <div class="admin-status-row">
            <span>فایل بک‌آپ</span>
            <strong class="ltr-data" dir="ltr">{{ $latestBackup['name'] ?? '—' }}</strong>
        </div>
    </div>
</section>
