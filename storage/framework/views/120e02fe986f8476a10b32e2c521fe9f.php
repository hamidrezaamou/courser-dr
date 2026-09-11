<?php
    /** @var \Illuminate\Support\Collection $rows */
?>
<section class="floor-col floor-col--<?php echo e($tone); ?>">
    <header class="floor-col__head">
        <div>
            <h3 class="floor-col__title"><?php echo e($title); ?></h3>
            <p class="floor-col__hint"><?php echo e($hint); ?></p>
        </div>
        <span class="floor-col__count"><?php echo e($rows->count()); ?></span>
    </header>

    <div class="floor-col__list">
        <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php echo $__env->make('clinic.partials.floor-card', ['row' => $row], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="floor-col__empty">خالی</p>
        <?php endif; ?>
    </div>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\clinic\partials\floor-column.blade.php ENDPATH**/ ?>