<?php
    $latestBackup = $widgetData['system']['latestBackup'] ?? null;
?>
<section class="admin-panel admin-dash-panel" id="system">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">سامانه</h3>
        <a href="<?php echo e(route('admin.settings.system')); ?>" class="admin-panel__link">جزئیات</a>
    </div>
    <div class="admin-status-list">
        <div class="admin-status-row">
            <span>آخرین بک‌آپ</span>
            <strong>
                <?php if($latestBackup): ?>
                    <?php echo e(jalali(\Carbon\Carbon::createFromTimestamp($latestBackup['at']), 'Y/m/d H:i')); ?>

                <?php else: ?>
                    —
                <?php endif; ?>
            </strong>
        </div>
        <div class="admin-status-row">
            <span>فایل بک‌آپ</span>
            <strong class="ltr-data" dir="ltr"><?php echo e($latestBackup['name'] ?? '—'); ?></strong>
        </div>
    </div>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\system.blade.php ENDPATH**/ ?>