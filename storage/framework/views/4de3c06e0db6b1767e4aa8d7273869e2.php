<?php
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
        ['key' => 'catalog', 'label' => 'فهرست کلینیک', 'route' => route('settings.index'), 'match' => 'settings.*,times.*,hospitals.*,surgery-types.*,drugs.*'],
        ['key' => 'audit', 'label' => 'ممیزی', 'route' => route('activity-logs.index'), 'match' => 'activity-logs.*'],
        ['key' => 'reports', 'label' => 'گزارشات', 'route' => route('reports.index'), 'match' => 'reports.*'],
    ]);
?>

<nav class="admin-dock" aria-label="منوی مدیریت کل سایت">
    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $active = $section === $item['key'] || request()->routeIs(...explode(',', $item['match']));
        ?>
        <a href="<?php echo e($item['route']); ?>" class="admin-dock__btn <?php echo e($active ? 'is-active' : ''); ?>">
            <span class="admin-dock__label"><?php echo e($item['label']); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\admin-dock.blade.php ENDPATH**/ ?>