<?php
    $alerts = $widgetData['alerts']['alerts'] ?? [];
?>
<?php if(!empty($alerts)): ?>
    <div class="admin-alerts">
        <?php $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e($alert['href']); ?>" class="admin-alert admin-alert--<?php echo e($alert['tone']); ?>">
                <span><?php echo e($alert['text']); ?></span>
                <span class="admin-alert__go">مشاهده</span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php else: ?>
    <div class="admin-dash-empty-widget">
        <p class="admin-empty">هشدار فعالی نیست — همه چیز مرتب به‌نظر می‌رسد.</p>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\alerts.blade.php ENDPATH**/ ?>