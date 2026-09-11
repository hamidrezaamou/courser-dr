@props([
    'type' => null,
    'dismiss' => 4000,
    'showErrors' => true,
])

@php
    $icons = [
        'success' => 'M5 13l4 4L19 7',
        'error' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z',
        'warn' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z',
        'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ];

    // With no explicit type the component reports whatever is in the session,
    // so a page only needs a single <x-flash /> near the top.
    $messages = [];

    if (! $type) {
        if (session('success')) {
            $messages[] = ['success', session('success')];
        }
        if (session('error')) {
            $messages[] = ['error', session('error')];
        }
        if ($showErrors && $errors->any()) {
            $messages[] = ['error', $errors->first()];
        }
    }
@endphp

@if ($type)
    <div
        {{ $attributes->merge(['class' => 'flash flash--'.$type]) }}
        role="{{ $type === 'error' ? 'alert' : 'status' }}"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$type] ?? $icons['info'] }}" />
        </svg>
        <div class="flash__body">{{ $slot }}</div>
    </div>
@else
    @foreach ($messages as [$level, $text])
        <div
            {{ $attributes->merge(['class' => 'flash flash--'.$level]) }}
            @if ($level === 'success' && $dismiss) data-auto-dismiss="{{ $dismiss }}" @endif
            role="{{ $level === 'error' ? 'alert' : 'status' }}"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$level] }}" />
            </svg>
            <div class="flash__body">{{ $text }}</div>
        </div>
    @endforeach
@endif
