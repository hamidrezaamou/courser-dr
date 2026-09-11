@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-xl px-3 py-3 text-start text-sm font-bold transition'
            : 'block w-full rounded-xl px-3 py-3 text-start text-sm font-semibold transition';
$style = ($active ?? false)
            ? 'background:var(--brand-soft);color:var(--brand-dark)'
            : 'color:var(--ink)';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'style' => $style]) }}>
    {{ $slot }}
</a>
