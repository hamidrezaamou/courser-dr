<?php $__env->startSection('title', 'معرفی به پزشک بیهوشی'); ?>

<?php $__env->startSection('styles'); ?>
    .letter { font-size: 16px; line-height: 2.2; text-align: justify; margin-top: 1rem; }
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-spacer"></div>
    <div class="header-date"><strong>تاریخ:</strong> <?php echo e(fa_digits($todayJalali)); ?></div>
    <div class="letter">
        <p>
            احتراماً آقای/خانم <strong><?php echo e(fa_digits($surgery->patient_name)); ?></strong>
            به کد ملی <strong><?php echo e(fa_digits($surgery->national_code)); ?></strong>
            جهت عمل <strong><?php echo e(fa_digits($surgery->surgery_type ?: '...........')); ?></strong>
            در تاریخ <strong><?php echo e(fa_digits($surgeryJalali)); ?></strong>
            و تست GA به حضورتان معرفی می‌گردد.
        </p>
    </div>
    <div class="sig">
        <div class="box">
            <strong><?php echo e($doctorName); ?></strong>
            <div class="line"></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('prints.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\prints\templates\anesthesiologist.blade.php ENDPATH**/ ?>