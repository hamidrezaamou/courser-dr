<?php $__env->startSection('title', 'معرفی به بیمارستان'); ?>

<?php $__env->startSection('styles'); ?>
    .hospital-title { text-align: center; font-size: 19px; font-weight: 800; padding-bottom: 14px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; margin-bottom: 14px; }
    .info-item { display: flex; gap: 6px; align-items: baseline; }
    .info-item .label { font-weight: 400; }
    .info-item .value { font-weight: 800; }
    .divider { border-bottom: 2px solid #000; margin: 14px 0; }
    .surgery-box { border: 1px dashed #000; padding: 12px; margin-top: 10px; font-size: 15px; }
    .surgery-box .row { display: flex; flex-wrap: wrap; gap: 28px; }
    .note { margin: 18px 0; padding: 10px; border-right: 3px solid #000; line-height: 1.8; }
    .address-line { margin-top: 12px; line-height: 1.7; }
    .manager-panel { max-width: 600px; margin: 10px auto 16px; background: #fff; border: 1px solid #ccc; border-radius: 6px; }
    .manager-header { background: #f8f9fa; padding: 12px; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; }
    .manager-body { display: none; padding: 14px; }
    .manager-body.open { display: block; }
    .form-row { display: flex; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
    .form-row input { flex: 1; min-width: 140px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; }
    .btn-add { width: 100%; background: #15803d; color: #fff; border: 0; padding: 8px; border-radius: 4px; font-family: inherit; font-weight: 700; cursor: pointer; }
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="manager-panel no-print">
        <div class="manager-header" onclick="document.getElementById('mgr').classList.toggle('open')">
            <span>مدیریت آدرس و تلفن بیمارستان‌ها (ذخیره در دیتابیس)</span>
            <span>▼</span>
        </div>
        <form method="POST" action="<?php echo e(route('surgery-appointments.prints.hospital-meta', $surgery)); ?>" class="manager-body" id="mgr">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <input type="text" value="<?php echo e($surgery->hospital?->name); ?>" readonly>
                <input type="text" name="address" id="h-address" placeholder="آدرس" value="<?php echo e(old('address', $surgery->hospital?->address)); ?>">
            </div>
            <div class="form-row">
                <input type="text" name="phone" id="h-phone" placeholder="تلفن" value="<?php echo e(old('phone', $surgery->hospital?->phone)); ?>">
                <input type="text" name="print_note" id="h-desc" placeholder="توضیحات اختیاری" value="<?php echo e(old('print_note', $surgery->hospital?->print_note)); ?>">
            </div>
            <button type="submit" class="btn-add">ذخیره برای این بیمارستان</button>
        </form>
    </div>

    <div class="header-spacer"></div>
    <div class="hospital-title"><?php echo e(fa_digits($surgery->hospital?->name ?: 'بیمارستان مقصد')); ?></div>

    <div class="info-grid">
        <div class="info-item"><span class="label">نام بیمار:</span><span class="value"><?php echo e(fa_digits($surgery->patient_name)); ?></span></div>
        <div class="info-item"><span class="label">کد ملی:</span><span class="value"><?php echo e(fa_digits($surgery->national_code)); ?></span></div>
        <div class="info-item" style="grid-column: span 2;"><span class="label">شماره تماس:</span><span class="value" dir="ltr"><?php echo e(fa_digits($surgery->mobile)); ?></span></div>
    </div>

    <div class="divider"></div>

    <div class="info-item"><span class="label">تاریخ عمل:</span><span class="value"><?php echo e(fa_digits($surgeryJalali)); ?></span></div>

    <div class="surgery-box">
        <div class="row">
            <div><span class="label">نوع عمل جراحی:</span> <span class="value"><?php echo e(fa_digits($surgery->surgery_type ?: 'تعیین نشده')); ?></span></div>
            <div><span class="label">نوع چشم:</span> <span class="value"><?php echo e(fa_digits($surgery->eye_side ?: '—')); ?></span></div>
        </div>
    </div>

    <div class="address-line">
        آدرس:
        <?php echo e(fa_digits($surgery->hospital?->address ?: '...........')); ?>

        <?php if($surgery->hospital?->phone): ?>
            | تلفن: <?php echo e(fa_digits($surgery->hospital?->phone)); ?>

        <?php endif; ?>
    </div>
    <?php if($surgery->hospital?->print_note): ?>
        <div class="address-line">توضیحات: <?php echo e(fa_digits($surgery->hospital->print_note)); ?></div>
    <?php endif; ?>

    <div class="note">
        <strong>نکته مهم:</strong> لطفاً قبل از تاریخ عمل، قرارداد بیمه تکمیلی خود را با بیمارستان استعلام بگیرید.
    </div>

    <div class="sig">
        <div class="box">
            <strong><?php echo e($doctorName); ?></strong>
            <div class="line"></div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('prints.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\prints\templates\hospital.blade.php ENDPATH**/ ?>