@php
    $recentLogs = $widgetData['activity']['recentLogs'] ?? collect();
@endphp
<section class="admin-panel admin-dash-panel">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">فعالیت اخیر</h3>
        <a href="{{ route('activity-logs.index') }}" class="admin-panel__link">همه لاگ‌ها</a>
    </div>
    @if ($recentLogs->isEmpty())
        <p class="admin-empty">هنوز رویدادی ثبت نشده.</p>
    @else
        <div class="admin-feed">
            @foreach ($recentLogs as $log)
                <div class="admin-feed__item">
                    <div class="admin-feed__main">
                        <strong>{{ $log->actionLabel() }}</strong>
                        <span>{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</span>
                    </div>
                    <div class="admin-feed__meta">
                        <span>{{ $log->user?->name ?? 'سیستم' }}</span>
                        <span dir="ltr">{{ jalali($log->created_at, 'Y/m/d H:i') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
