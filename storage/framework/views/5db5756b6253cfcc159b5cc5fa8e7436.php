<?php if($checklist && $checklist->saved_at): ?>
    <div class="scl-record">
        <div class="scl-record__head">
            <span class="scl-record__kicker">چک‌لیست عمل</span>
            <strong class="scl-record__title"><?php echo e($checklist->title); ?></strong>
            <span class="scl-record__saved" dir="ltr"><?php echo e(jalali($checklist->saved_at, 'Y/m/d H:i')); ?></span>
        </div>
        <ul class="scl-record__list">
            <?php $__empty_1 = true; $__currentLoopData = $checklist->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <li class="scl-record__item <?php echo e($clItem->checked_at ? 'is-done' : ''); ?>">
                    <span class="scl-record__check" aria-hidden="true"><?php echo e($clItem->checked_at ? '✓' : '○'); ?></span>
                    <span class="scl-record__label"><?php echo e($clItem->label); ?></span>
                    <?php if($clItem->checked_at): ?>
                        <span class="scl-record__stamp"
                              title="<?php echo e(($clItem->checker?->name ? $clItem->checker->name.' · ' : '').jalali($clItem->checked_at, 'Y/m/d H:i')); ?>">
                            <?php echo e(trim(($clItem->checker?->name ? $clItem->checker->name.' · ' : '').jalali($clItem->checked_at, 'H:i'))); ?>

                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <li class="scl-record__item">
                    <span class="scl-record__label" style="color:var(--muted)">موردی ثبت نشده.</span>
                </li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\patients\partials\surgery-checklist.blade.php ENDPATH**/ ?>