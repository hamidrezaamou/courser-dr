<?php
    $kpis = $widgetData['kpis']['kpis'] ?? [];
    $deltaToday = $widgetData['kpis']['deltaToday'] ?? 0;
?>
<section class="admin-kpi-grid">
    <article class="admin-kpi">
        <span class="admin-kpi__label">کل پرونده‌ها</span>
        <strong class="admin-kpi__value"><?php echo e(number_format($kpis['patients_total'] ?? 0)); ?></strong>
        <span class="admin-kpi__meta">+<?php echo e(number_format($kpis['patients_new_today'] ?? 0)); ?> امروز · <?php echo e(number_format($kpis['patients_new_week'] ?? 0)); ?> این هفته</span>
    </article>
    <article class="admin-kpi admin-kpi--accent">
        <span class="admin-kpi__label">نوبت‌های امروز</span>
        <strong class="admin-kpi__value"><?php echo e(number_format($kpis['today_total'] ?? 0)); ?></strong>
        <span class="admin-kpi__meta">
            <?php if($deltaToday > 0): ?>
                <?php echo e(number_format($deltaToday)); ?> بیشتر از دیروز
            <?php elseif($deltaToday < 0): ?>
                <?php echo e(number_format(abs($deltaToday))); ?> کمتر از دیروز
            <?php else: ?>
                برابر دیروز
            <?php endif; ?>
        </span>
    </article>
    <article class="admin-kpi admin-kpi--ok">
        <span class="admin-kpi__label">تایید شده امروز</span>
        <strong class="admin-kpi__value"><?php echo e(number_format($kpis['today_confirmed'] ?? 0)); ?></strong>
        <span class="admin-kpi__meta"><?php echo e(number_format($kpis['today_cancelled'] ?? 0)); ?> لغو شده</span>
    </article>
    <article class="admin-kpi <?php echo e(($kpis['followups_pending'] ?? 0) > 0 ? 'admin-kpi--warn' : ''); ?>">
        <span class="admin-kpi__label">پیگیری معوق</span>
        <strong class="admin-kpi__value"><?php echo e(number_format($kpis['followups_pending'] ?? 0)); ?></strong>
        <span class="admin-kpi__meta"><?php echo e(number_format($kpis['followups_upcoming'] ?? 0)); ?> پیش‌رو</span>
    </article>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\kpis.blade.php ENDPATH**/ ?>