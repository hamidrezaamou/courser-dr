<?php $__env->startSection('title', 'معرفی به آزمایشگاه'); ?>

<?php $__env->startSection('styles'); ?>
    .letter { font-size: 16px; line-height: 2.1; text-align: justify; margin-top: 1rem; }
    .tests { margin: 1.25rem 0 1.25rem 1.5rem; line-height: 2; }
    .tracking { margin-top: 1.5rem; font-size: 15px; }
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-spacer"></div>
    <div class="header-date"><strong>تاریخ:</strong> <?php echo e(fa_digits($todayJalali)); ?></div>
    <div class="letter">
        <p><strong>مدیریت محترم آزمایشگاه</strong></p>
        <p>
            احتراماً آقای/خانم <strong><?php echo e(fa_digits($surgery->patient_name)); ?></strong>
            به کد ملی <strong><?php echo e(fa_digits($surgery->national_code)); ?></strong>
            جهت انجام آزمایشات زیر به حضورتان معرفی می‌گردد:
        </p>
    </div>
    <ul class="tests">
        <li>CBC - Diff</li>
        <li>BUN - Cr</li>
        <li>FBS</li>
    </ul>
    <div class="tracking">کد رهگیری: <strong>......................</strong></div>
    <div class="sig">
        <div class="box">
            <strong><?php echo e($doctorName); ?></strong>
            <div class="line"></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('prints.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\prints\templates\laboratory.blade.php ENDPATH**/ ?>