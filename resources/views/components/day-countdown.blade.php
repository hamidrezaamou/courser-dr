@props([
    'date' => null,
])

@php
    $target = null;
    if ($date) {
        try {
            $target = \Illuminate\Support\Carbon::parse($date)->startOfDay();
        } catch (\Throwable $e) {
            $target = null;
        }
    }

    $days = $target ? (int) round(\Illuminate\Support\Carbon::now()->startOfDay()->diffInDays($target, false)) : null;

    [$tone, $text] = match (true) {
        $days === null => [null, null],
        $days === 0 => ['today', 'امروز'],
        $days === 1 => ['soon', 'فردا'],
        $days > 1 && $days <= 7 => ['soon', $days.' روز مانده'],
        $days > 7 => ['later', $days.' روز مانده'],
        $days === -1 => ['past', 'دیروز'],
        default => ['past', abs($days).' روز پیش'],
    };
@endphp

@if($text)
    <span {{ $attributes->class(['evt-count', 'evt-count--'.$tone]) }}>
        @if($tone === 'today')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" d="M12 7.5V12l3 1.8" />
            </svg>
        @endif
        {{ $text }}
    </span>
@endif
