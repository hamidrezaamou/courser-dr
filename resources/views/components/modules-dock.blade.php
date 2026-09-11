@php
    $section = $moduleSection ?? 'hub';
    $items = [['key' => 'hub', 'label' => 'همه ماژول‌ها', 'route' => route('modules.hub'), 'match' => 'modules.hub,admin.modules']];

    foreach (\App\Support\ModuleRegistry::enabled() as $key => $meta) {
        $routePrefix = preg_replace('/\.index$/', '', $meta['route']);
        $items[] = [
            'key' => $meta['section'],
            'label' => $meta['label'],
            'route' => route($meta['route']),
            'match' => $routePrefix.'.*',
        ];
    }
@endphp

@if(count($items) > 1)
<nav class="admin-dock modules-dock" aria-label="منوی ماژول‌های پیشرفته">
    @foreach ($items as $item)
        @php
            $active = $section === $item['key'] || request()->routeIs(...explode(',', $item['match']));
        @endphp
        <a href="{{ $item['route'] }}" class="admin-dock__btn {{ $active ? 'is-active' : '' }}">
            <span class="admin-dock__label">{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
@endif
