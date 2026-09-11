@props([
    'featureKey',
    'meta',
    'enabled' => false,
    'featured' => false,
])

@php
    $field = str_replace('.', '_', $featureKey);
    $scopes = $meta['scopes'] ?? [];
    $planned = ! empty($meta['planned']);
    $offNote = $meta['off_note'] ?? null;
    $onNote = $meta['on_note'] ?? null;
@endphp

<article @class([
    'admin-feature-card',
    'admin-feature-card--featured' => $featured,
    'admin-feature-card--planned' => $planned,
    'is-on' => $enabled,
    'is-off' => ! $enabled,
])>
    <div class="admin-feature-card__main">
        <div class="admin-feature-card__icon" aria-hidden="true">
            @if($planned)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
            @elseif($featured)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            @endif
        </div>

        <div class="admin-feature-card__body min-w-0">
            <div class="admin-feature-card__head">
                <h4 class="admin-feature-card__title">{{ $meta['label'] }}</h4>
                @if($planned)
                    <span class="admin-feature-card__badge">ماژول پیشرفته</span>
                @endif
                <span class="admin-feature-card__status">{{ $enabled ? 'فعال' : 'غیرفعال' }}</span>
            </div>
            <p class="admin-feature-card__hint">{{ $meta['hint'] }}</p>

            @if($scopes !== [])
                <ul class="admin-feature-scopes" aria-label="بخش‌های تحت تأثیر">
                    @foreach($scopes as $scope)
                        <li>{{ $scope }}</li>
                    @endforeach
                </ul>
            @endif

            @if($featured && ! $enabled && $offNote)
                <p class="admin-feature-card__note">{{ $offNote }}</p>
            @endif

            @if($planned && $enabled && $onNote)
                <p class="admin-feature-card__note admin-feature-card__note--info">{{ $onNote }}</p>
            @elseif($planned && $enabled)
                <p class="admin-feature-card__note admin-feature-card__note--info">
                    این ماژول روشن است و در به‌روزرسانی بعد به منوی مدیریت اضافه می‌شود.
                </p>
            @elseif($planned && ! $enabled)
                <p class="admin-feature-card__note">
                    پیش‌فرض خاموش است — پنل فعلی بدون این بخش کار می‌کند.
                </p>
            @endif
        </div>
    </div>

    <label class="admin-feature-switch" title="{{ $enabled ? 'غیرفعال کردن' : 'فعال کردن' }}">
        <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $enabled))>
        <span class="admin-feature-switch__track" aria-hidden="true">
            <span class="admin-feature-switch__thumb"></span>
        </span>
        <span class="sr-only">{{ $meta['label'] }}</span>
    </label>
</article>
