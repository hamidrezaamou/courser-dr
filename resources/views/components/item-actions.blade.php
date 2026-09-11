@props([
    'label' => 'عملیات',
])

{{-- Answer-app inspired action bar: one pencil chip → colorful action row --}}
<details {{ $attributes->class(['aa-actions']) }}>
    <summary class="aa-actions__trigger" aria-label="{{ $label }}" title="{{ $label }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
        </svg>
        <span>{{ $label }}</span>
    </summary>
    <div class="aa-actions__bar" role="menu">
        {{ $slot }}
    </div>
</details>
