<?php $__env->startSection('title', 'تعیین لنز IOL Master'); ?>

<?php $__env->startSection('styles'); ?>
    .letter { font-size: 16px; line-height: 2.4; text-align: justify; margin-top: 1rem; }
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-spacer"></div>
    <div class="header-date"><strong>تاریخ:</strong> <?php echo e(fa_digits($todayJalali)); ?></div>
    <div class="letter">
        <p><strong>همکار محترم اپتومتری</strong></p>
        <p>
            آقای/خانم <strong><?php echo e(fa_digits($surgery->patient_name)); ?></strong>
            با کد ملی <strong><?php echo e(fa_digits($surgery->national_code)); ?></strong>
            جهت تعیین لنز (IOL Master) برای تاریخ عمل
            <strong><?php echo e(fa_digits($surgeryJalali)); ?></strong>
            معرفی می‌گردد.
        </p>
    </div>
    <div class="sig">
        <div class="box">
            <strong><?php echo e($doctorName); ?></strong>
            <div class="line"></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('prints.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\prints\templates\iol_master.blade.php ENDPATH**/ ?>