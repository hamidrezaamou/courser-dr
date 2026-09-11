@php
    $smsEnabled = $widgetData['comms']['smsEnabled'] ?? false;
    $smsDriver = $widgetData['comms']['smsDriver'] ?? '';
    $telegramEnabled = $widgetData['comms']['telegramEnabled'] ?? false;
@endphp
<section class="admin-panel admin-dash-panel" id="comms">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">وضعیت ارتباطات</h3>
        <a href="{{ route('admin.settings.communications') }}" class="admin-panel__link">ویرایش</a>
    </div>
    <div class="admin-status-list">
        <div class="admin-status-row">
            <span>پیامک</span>
            <strong class="{{ $smsEnabled && $smsDriver === 'smsir' ? 'is-on' : 'is-off' }}">
                {{ $smsEnabled ? ('روشن · '.$smsDriver) : 'خاموش' }}
            </strong>
        </div>
        <div class="admin-status-row">
            <span>تلگرام یادآوری</span>
            <strong class="{{ $telegramEnabled ? 'is-on' : 'is-off' }}">
                {{ $telegramEnabled ? 'روشن' : 'خاموش' }}
            </strong>
        </div>
    </div>
</section>
