<?php
    $roleCounts = $widgetData['roles']['roleCounts'] ?? [];
    $staffTotal = $widgetData['roles']['staff_total'] ?? 0;
?>
<section class="admin-panel admin-dash-panel">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">ترکیب کاربران</h3>
        <a href="<?php echo e(route('admin.users.index')); ?>" class="admin-panel__link">مدیریت</a>
    </div>
    <div class="admin-roles">
        <div class="admin-role"><span>مدیر</span><strong><?php echo e(number_format($roleCounts['admin'] ?? 0)); ?></strong></div>
        <div class="admin-role"><span>پزشک</span><strong><?php echo e(number_format($roleCounts['doctor'] ?? 0)); ?></strong></div>
        <div class="admin-role"><span>منشی</span><strong><?php echo e(number_format($roleCounts['assistant'] ?? 0)); ?></strong></div>
        <div class="admin-role"><span>حساب بیمار</span><strong><?php echo e(number_format($roleCounts['patient'] ?? 0)); ?></strong></div>
    </div>
    <p class="admin-panel__hint">پرسنل فعال: <?php echo e(number_format($staffTotal)); ?> نفر</p>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\roles.blade.php ENDPATH**/ ?>