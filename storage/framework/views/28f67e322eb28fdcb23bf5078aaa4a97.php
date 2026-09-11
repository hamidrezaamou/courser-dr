<?php $__env->startSection('title', 'نسخه پزشکی'); ?>

<?php $__env->startSection('styles'); ?>
    .rx-title { text-align: center; font-size: 18px; font-weight: 800; margin-bottom: 1rem; }
    .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; margin-bottom: 1rem; }
    .meta div { display: flex; gap: 6px; }
    .meta .label { color: #444; }
    .meta .value { font-weight: 800; }
    .rx-box {
        min-height: 220px;
        border: 1px solid #000;
        border-radius: 6px;
        padding: 14px;
        margin-top: 10px;
    }
    .rx-hint { color: #666; font-size: 12px; margin-top: 8px; }
    .rx-items { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
    .rx-items th, .rx-items td { border: 1px solid #bbb; padding: 6px 8px; text-align: right; vertical-align: top; }
    .rx-items th { background: #f3f4f6; font-size: 12px; }
    .rx-foot { margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; }
    .rx-qr img { width: 96px; height: 96px; display: block; }
    .rx-qr span { display: block; font-size: 10px; color: #666; margin-top: 4px; }
    .rx-sign { text-align: center; min-width: 170px; }
    .rx-sign img.signature { max-height: 64px; max-width: 160px; object-fit: contain; display: block; margin: 0 auto; }
    .rx-sign img.stamp { max-height: 72px; max-width: 72px; object-fit: contain; opacity: .85; display: block; margin: 4px auto 0; }
    .rx-sign .line { width: 160px; border-bottom: 1px solid #000; margin: 6px auto 0; }
    .rx-sign .name { font-weight: 800; margin-top: 4px; }
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-spacer"></div>
    <div class="rx-title">نسخه پزشکی</div>
    <div class="header-date"><strong>تاریخ:</strong> <?php echo e(fa_digits($todayJalali)); ?></div>
    <div class="meta">
        <div><span class="label">نام بیمار:</span><span class="value"><?php echo e(fa_digits($surgery->patient_name)); ?></span></div>
        <div><span class="label">کد ملی:</span><span class="value"><?php echo e(fa_digits($surgery->national_code)); ?></span></div>
        <div><span class="label">موبایل:</span><span class="value" dir="ltr"><?php echo e(fa_digits($surgery->mobile)); ?></span></div>
        <div><span class="label">تاریخ عمل:</span><span class="value"><?php echo e(fa_digits($surgeryJalali)); ?></span></div>
        <div style="grid-column: span 2;"><span class="label">نوع عمل:</span><span class="value"><?php echo e(fa_digits($surgery->surgery_type ?: '—')); ?><?php if($surgery->eye_side): ?> · <?php echo e(fa_digits($surgery->eye_side)); ?><?php endif; ?></span></div>
    </div>
    <div class="rx-box">
        <strong>Rx</strong>
        <?php if($latestPrescription && $latestPrescription->items->isNotEmpty()): ?>
            <table class="rx-items">
                <thead>
                    <tr>
                        <th>دارو</th>
                        <th>دوز / دفعات</th>
                        <th>زمان / مدت</th>
                        <th>دستور</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $latestPrescription->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><strong><?php echo e(fa_digits($item->drug_name)); ?></strong><?php if($item->usage_type): ?><br><small><?php echo e($item->usage_type); ?></small><?php endif; ?></td>
                            <td><?php echo e(fa_digits(trim(($item->dosage ?: '').' '.($item->frequency ?: ''))) ?: '—'); ?></td>
                            <td><?php echo e(fa_digits(trim(($item->meal_timing ? $item->mealTimingLabel() : '').($item->duration ? ' · '.$item->duration : ''))) ?: '—'); ?></td>
                            <td><?php echo e(fa_digits($item->instructions ?: '—')); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
            <?php if($latestPrescription->notes): ?>
                <p class="rx-hint"><strong>یادداشت:</strong> <?php echo e(fa_digits($latestPrescription->notes)); ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p class="rx-hint">اقلام نسخه را اینجا بنویسید یا پس از چاپ تکمیل کنید.</p>
        <?php endif; ?>
    </div>

    <div class="rx-foot">
        <div class="rx-qr">
            <?php if(!empty($qrDataUri)): ?>
                <img src="<?php echo e($qrDataUri); ?>" alt="کد QR نسخه">
            <?php endif; ?>
            <span dir="ltr">RX-<?php echo e($surgery->id); ?></span>
        </div>
        <div class="rx-sign">
            <?php if(!empty($signatureUrl)): ?>
                <img src="<?php echo e($signatureUrl); ?>" class="signature" alt="امضای پزشک">
            <?php endif; ?>
            <?php if(!empty($stampUrl)): ?>
                <img src="<?php echo e($stampUrl); ?>" class="stamp" alt="مهر مطب">
            <?php endif; ?>
            <div class="line"></div>
            <div class="name"><?php echo e($doctorName); ?></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('prints.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\prints\templates\prescription.blade.php ENDPATH**/ ?>