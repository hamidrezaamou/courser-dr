<?php
    $visits = (int) ($widgetData['visit_surgery']['visits'] ?? 0);
    $surgeries = (int) ($widgetData['visit_surgery']['surgeries'] ?? 0);
    $total = max(1, (int) ($widgetData['visit_surgery']['total'] ?? ($visits + $surgeries)));
?>
<section class="admin-panel admin-stat-card">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">ویزیت در برابر عمل</h3>
        <span class="admin-panel__hint">امروز</span>
    </div>
    <div class="admin-split-stat">
        <div>
            <span class="admin-split-stat__label">ویزیت</span>
            <strong class="admin-split-stat__value"><?php echo e(number_format($visits)); ?></strong>
            <div class="admin-split-stat__track"><span style="width: <?php echo e(round(($visits / $total) * 100)); ?>%"></span></div>
        </div>
        <div>
            <span class="admin-split-stat__label">عمل</span>
            <strong class="admin-split-stat__value"><?php echo e(number_format($surgeries)); ?></strong>
            <div class="admin-split-stat__track admin-split-stat__track--warm"><span style="width: <?php echo e(round(($surgeries / $total) * 100)); ?>%"></span></div>
        </div>
    </div>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\visit_surgery.blade.php ENDPATH**/ ?>