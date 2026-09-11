@props(['active' => false])

<a {{ $attributes->merge(['class' => 'nav-link'.(($active ?? false) ? ' is-active' : '')]) }}>
    {{ $slot }}
</a>
