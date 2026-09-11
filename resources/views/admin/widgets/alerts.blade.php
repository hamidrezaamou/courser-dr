@php
    $alerts = $widgetData['alerts']['alerts'] ?? [];
@endphp
@if (!empty($alerts))
    <div class="admin-alerts">
        @foreach ($alerts as $alert)
            <a href="{{ $alert['href'] }}" class="admin-alert admin-alert--{{ $alert['tone'] }}">
                <span>{{ $alert['text'] }}</span>
                <span class="admin-alert__go">مشاهده</span>
            </a>
        @endforeach
    </div>
@else
    <div class="admin-dash-empty-widget">
        <p class="admin-empty">هشدار فعالی نیست — همه چیز مرتب به‌نظر می‌رسد.</p>
    </div>
@endif
