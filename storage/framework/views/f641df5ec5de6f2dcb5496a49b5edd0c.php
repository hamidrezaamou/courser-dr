<?php
    $recentLogs = $widgetData['activity']['recentLogs'] ?? collect();
?>
<section class="admin-panel admin-dash-panel">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">فعالیت اخیر</h3>
        <a href="<?php echo e(route('activity-logs.index')); ?>" class="admin-panel__link">همه لاگ‌ها</a>
    </div>
    <?php if($recentLogs->isEmpty()): ?>
        <p class="admin-empty">هنوز رویدادی ثبت نشده.</p>
    <?php else: ?>
        <div class="admin-feed">
            <?php $__currentLoopData = $recentLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="admin-feed__item">
                    <div class="admin-feed__main">
                        <strong><?php echo e($log->actionLabel()); ?></strong>
                        <span><?php echo e(class_basename($log->subject_type)); ?> #<?php echo e($log->subject_id); ?></span>
                    </div>
                    <div class="admin-feed__meta">
                        <span><?php echo e($log->user?->name ?? 'سیستم'); ?></span>
                        <span dir="ltr"><?php echo e(jalali($log->created_at, 'Y/m/d H:i')); ?></span>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\activity.blade.php ENDPATH**/ ?>