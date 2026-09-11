<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
