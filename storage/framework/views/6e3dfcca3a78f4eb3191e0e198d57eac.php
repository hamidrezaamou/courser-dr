<?php
    $funnel = $widgetData['funnel']['funnel'] ?? [];
    $funnelMax = max(1, max($funnel ?: [0]));
    $funnelLabels = [
        'scheduled' => 'ثبت‌شده',
        'confirmed' => 'تایید',
        'done' => 'انجام',
        'cancelled' => 'لغو',
    ];
?>
<section class="admin-panel admin-dash-panel">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">قیف وضعیت امروز</h3>
    </div>
    <div class="admin-funnel">
        <?php $__currentLoopData = $funnelLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="admin-funnel__row">
                <span class="admin-funnel__label"><?php echo e($label); ?></span>
                <div class="admin-funnel__track">
                    <span class="admin-funnel__bar admin-funnel__bar--<?php echo e($key); ?>" style="width: <?php echo e(round((($funnel[$key] ?? 0) / $funnelMax) * 100)); ?>%"></span>
                </div>
                <strong class="admin-funnel__count"><?php echo e(number_format($funnel[$key] ?? 0)); ?></strong>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\funnel.blade.php ENDPATH**/ ?>