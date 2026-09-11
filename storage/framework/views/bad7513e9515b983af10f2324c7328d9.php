<?php $c = $widgetData['catalog']['catalog'] ?? []; ?>
<section class="admin-panel admin-stat-card">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">فهرست کلینیک</h3>
        <a href="<?php echo e(route('settings.index')); ?>" class="admin-panel__link">تنظیمات</a>
    </div>
    <div class="admin-mini-kpis">
        <article>
            <span>بیمارستان</span>
            <strong><?php echo e(number_format($c['hospitals'] ?? 0)); ?></strong>
        </article>
        <article>
            <span>دارو</span>
            <strong><?php echo e(number_format($c['drugs'] ?? 0)); ?></strong>
        </article>
        <article>
            <span>نوع عمل</span>
            <strong><?php echo e(number_format($c['surgery_types'] ?? 0)); ?></strong>
        </article>
        <article>
            <span>ویزیت ثبت‌شده</span>
            <strong><?php echo e(number_format($c['visits_total'] ?? 0)); ?></strong>
        </article>
    </div>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\catalog.blade.php ENDPATH**/ ?>