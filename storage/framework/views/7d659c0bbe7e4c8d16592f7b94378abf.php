<?php
    $stats = $stats ?? ['total' => $patients->total(), 'new_today' => 0, 'upcoming_surgery' => 0];
?>

<div class="patient-stats">
    <div class="patient-stat">
        <span class="patient-stat__value"><?php echo e(number_format($stats['total'])); ?></span>
        <span class="patient-stat__label">پرونده</span>
    </div>
    <div class="patient-stat patient-stat--accent">
        <span class="patient-stat__value"><?php echo e(number_format($stats['new_today'])); ?></span>
        <span class="patient-stat__label">پرونده جدید امروز</span>
    </div>
    <div class="patient-stat">
        <span class="patient-stat__value"><?php echo e(number_format($stats['upcoming_surgery'])); ?></span>
        <span class="patient-stat__label">عمل پیش‌رو</span>
    </div>
</div>

<?php if($search !== ''): ?>
    <p class="patient-results__query mt-2 text-xs font-bold" style="color: var(--brand-dark);">
        نتیجه جستجو · «<?php echo e($search); ?>»
        <span id="patient-visible-count" class="sr-only"><?php echo e($patients->total()); ?></span>
    </p>
<?php else: ?>
    <span id="patient-visible-count" class="sr-only"><?php echo e($patients->total()); ?></span>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\dashboard\partials\results-head.blade.php ENDPATH**/ ?>