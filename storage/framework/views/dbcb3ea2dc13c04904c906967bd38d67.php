<?php
    $days = $widgetData['week_bookings']['days'] ?? [];
    $totals = array_column($days, 'total');
    $max = max(1, $totals ? max($totals) : 1);
?>
<section class="admin-panel admin-stat-card">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">نوبت ۷ روز اخیر</h3>
    </div>
    <div class="admin-week-bars">
        <?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $pct = (int) round(($day['total'] / $max) * 100); ?>
            <div class="admin-week-bars__item" title="<?php echo e($day['total']); ?> نوبت">
                <div class="admin-week-bars__col">
                    <span class="admin-week-bars__fill" style="height: <?php echo e(max(8, $pct)); ?>%"></span>
                </div>
                <span class="admin-week-bars__label"><?php echo e($day['label']); ?></span>
                <strong class="admin-week-bars__value"><?php echo e(number_format($day['total'])); ?></strong>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\week_bookings.blade.php ENDPATH**/ ?>