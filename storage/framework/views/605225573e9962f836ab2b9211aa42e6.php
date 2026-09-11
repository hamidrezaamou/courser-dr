
<?php
    $section = $settingsSection ?? 'times';
?>

<nav class="settings-dock" aria-label="منوی تنظیمات">
    <a href="<?php echo e(route('settings.times')); ?>" class="settings-dock__btn <?php echo e($section === 'times' ? 'is-active' : ''); ?>">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
        <span class="settings-dock__label">تایم‌ها</span>
    </a>
    <a href="<?php echo e(route('settings.hospitals')); ?>" class="settings-dock__btn <?php echo e($section === 'hospitals' ? 'is-active' : ''); ?>">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M6 21V5a1 1 0 011-1h10a1 1 0 011 1v16M9 8h.01M12 8h.01M15 8h.01M9 12h.01M12 12h.01M15 12h.01M9 16h.01M12 16h.01M15 16h.01"/></svg>
        <span class="settings-dock__label">بیمارستان</span>
    </a>
    <a href="<?php echo e(route('settings.surgery-types')); ?>" class="settings-dock__btn <?php echo e($section === 'surgery-types' ? 'is-active' : ''); ?>">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
        <span class="settings-dock__label">انواع عمل</span>
    </a>
    <a href="<?php echo e(route('settings.drugs')); ?>" class="settings-dock__btn <?php echo e($section === 'drugs' ? 'is-active' : ''); ?>">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
        <span class="settings-dock__label">داروها</span>
    </a>
</nav>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\settings-dock.blade.php ENDPATH**/ ?>