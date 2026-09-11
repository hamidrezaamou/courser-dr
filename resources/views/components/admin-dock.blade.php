@php
    $section = $adminSection ?? 'overview';
    $items = [
        ['key' => 'overview', 'label' => 'نمای کلی', 'route' => route('admin.index'), 'match' => 'admin.index'],
        ['key' => 'users', 'label' => 'کاربران', 'route' => route('admin.users.index'), 'match' => 'admin.users.*'],
        ['key' => 'comms', 'label' => 'ارتباطات', 'route' => route('admin.settings.communications'), 'match' => 'admin.settings.communications*'],
        ['key' => 'brand', 'label' => 'برند و چاپ', 'route' => route('admin.settings.brand'), 'match' => 'admin.settings.brand*'],
        ['key' => 'system', 'label' => 'سامانه', 'route' => route('admin.settings.system'), 'match' => 'admin.settings.system*,admin.settings.backup*,admin.settings.privacy*'],
        ['key' => 'support', 'label' => 'پشتیبانی', 'route' => route('admin.settings.support'), 'match' => 'admin.settings.support*'],
        ['key' => 'features', 'label' => 'قابلیت‌ها', 'route' => route('admin.settings.features'), 'match' => 'admin.settings.features*'],
        ['key' => 'quick-links', 'label' => 'لینک‌های ویژه', 'route' => route('admin.settings.quick-links'), 'match' => 'admin.settings.quick-links*'],
    ];
    if (config('his.enabled')) {
        $items[] = ['key' => 'his', 'label' => 'HIS', 'route' => route('admin.his.index'), 'match' => 'admin.his.*'];
    }
    if (\App\Support\ModuleRegistry::anyEnabled()) {
        $items[] = ['key' => 'modules', 'label' => 'ماژول‌ها', 'route' => route('modules.hub'), 'match' => 'modules.*,admin.modules'];
    }
    $items = array_merge($items, [
        ['key' => 'catalog', 'label' => 'فهرست کلینیک', 'route' => route('settings.index'), 'match' => 'settings.*,times.*,hospitals.*,surgery-types.*,drugs.*,patients.manage,patients.edit'],
        ['key' => 'audit', 'label' => 'ممیزی', 'route' => route('activity-logs.index'), 'match' => 'activity-logs.*'],
        ['key' => 'reports', 'label' => 'گزارشات', 'route' => route('reports.index'), 'match' => 'reports.*'],
    ]);
@endphp

<nav class="admin-dock" aria-label="منوی مدیریت کل سایت">
    @foreach ($items as $item)
        @php
            $active = $section === $item['key'] || request()->routeIs(...explode(',', $item['match']));
        @endphp
        <a href="{{ $item['route'] }}" class="admin-dock__btn {{ $active ? 'is-active' : '' }}">
            <span class="admin-dock__label">{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
