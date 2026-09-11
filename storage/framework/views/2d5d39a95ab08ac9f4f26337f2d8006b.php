<section class="admin-panel admin-stat-card">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">عمل‌های پیش‌رو</h3>
        <a href="<?php echo e(route('appointments.board')); ?>" class="admin-panel__link">بُرد</a>
    </div>
    <div class="admin-mini-kpis admin-mini-kpis--2">
        <article>
            <span>از امروز به بعد</span>
            <strong><?php echo e(number_format($widgetData['upcoming_surgeries']['total'] ?? 0)); ?></strong>
        </article>
        <article>
            <span>۷ روز آینده</span>
            <strong><?php echo e(number_format($widgetData['upcoming_surgeries']['week'] ?? 0)); ?></strong>
        </article>
    </div>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\upcoming_surgeries.blade.php ENDPATH**/ ?>