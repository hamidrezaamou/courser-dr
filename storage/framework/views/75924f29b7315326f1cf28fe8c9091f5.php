<?php
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
?>

<?php if(count($items) > 1): ?>
<nav class="admin-dock modules-dock" aria-label="منوی ماژول‌های پیشرفته">
    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $active = $section === $item['key'] || request()->routeIs(...explode(',', $item['match']));
        ?>
        <a href="<?php echo e($item['route']); ?>" class="admin-dock__btn <?php echo e($active ? 'is-active' : ''); ?>">
            <span class="admin-dock__label"><?php echo e($item['label']); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\modules-dock.blade.php ENDPATH**/ ?>