@if($checklist && $checklist->saved_at)
    <div class="scl-record">
        <div class="scl-record__head">
            <span class="scl-record__kicker">چک‌لیست عمل</span>
            <strong class="scl-record__title">{{ $checklist->title }}</strong>
            <span class="scl-record__saved" dir="ltr">{{ jalali($checklist->saved_at, 'Y/m/d H:i') }}</span>
        </div>
        <ul class="scl-record__list">
            @forelse($checklist->items as $clItem)
                <li class="scl-record__item {{ $clItem->checked_at ? 'is-done' : '' }}">
                    <span class="scl-record__check" aria-hidden="true">{{ $clItem->checked_at ? '✓' : '○' }}</span>
                    <span class="scl-record__label">{{ $clItem->label }}</span>
                    @if($clItem->checked_at)
                        <span class="scl-record__stamp"
                              title="{{ ($clItem->checker?->name ? $clItem->checker->name.' · ' : '').jalali($clItem->checked_at, 'Y/m/d H:i') }}">
                            {{ trim(($clItem->checker?->name ? $clItem->checker->name.' · ' : '').jalali($clItem->checked_at, 'H:i')) }}
                        </span>
                    @endif
                </li>
            @empty
                <li class="scl-record__item">
                    <span class="scl-record__label" style="color:var(--muted)">موردی ثبت نشده.</span>
                </li>
            @endforelse
        </ul>
    </div>
@endif
