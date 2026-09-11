<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نسخه #<?php echo e($prescription->id); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Tahoma, Arial, sans-serif; margin: 0; padding: 24px; color: #111; background: #fff; }
        .rx-sheet { max-width: 720px; margin: 0 auto; border: 2px solid #111; padding: 24px; }
        .rx-head { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; border-bottom: 1px solid #ccc; padding-bottom: 12px; margin-bottom: 16px; }
        .rx-brand { display: flex; gap: 12px; align-items: center; }
        .rx-brand img { max-height: 64px; max-width: 120px; object-fit: contain; }
        .rx-title { font-size: 20px; font-weight: bold; }
        .rx-meta { font-size: 12px; line-height: 1.8; color: #444; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; vertical-align: top; }
        th { background: #f5f5f5; }
        .rx-foot { margin-top: 24px; display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; }
        .qr-box img { width: 120px; height: 120px; display: block; }
        .sign-area { text-align: center; min-width: 180px; }
        .sign-area img { max-height: 70px; max-width: 160px; object-fit: contain; display: block; margin: 0 auto 6px; }
        .sign-line { border-top: 1px solid #111; width: 180px; margin: 8px auto 0; padding-top: 4px; font-size: 12px; }
        .stamp { max-height: 80px; max-width: 80px; opacity: .85; margin-top: 6px; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="max-width:720px;margin:0 auto 12px;text-align:left;">
        <button onclick="window.print()" style="padding:8px 16px;cursor:pointer;">چاپ</button>
    </div>

    <div class="rx-sheet">
        <div class="rx-head">
            <div class="rx-brand">
                <?php if($logoUrl): ?>
                    <img src="<?php echo e($logoUrl); ?>" alt="لوگو">
                <?php endif; ?>
                <div>
                    <div class="rx-title">نسخه پزشکی</div>
                    <div class="rx-meta"><?php echo e($doctorName); ?><?php if($clinicPhone): ?> · <?php echo e($clinicPhone); ?><?php endif; ?></div>
                    <div class="rx-meta">شماره: <?php echo e($prescription->id); ?></div>
                </div>
            </div>
            <div class="rx-meta" dir="ltr"><?php echo e(jalali($prescription->created_at, 'Y/m/d H:i')); ?></div>
        </div>

        <div class="rx-meta" style="margin-bottom:16px;">
            <strong>بیمار:</strong> <?php echo e($prescription->patient?->name); ?><br>
            <strong>کد ملی:</strong> <span dir="ltr"><?php echo e($prescription->patient?->national_code ?: '—'); ?></span><br>
            <strong>موبایل:</strong> <span dir="ltr"><?php echo e($prescription->patient?->mobile); ?></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>دارو</th>
                    <th>دوز / دفعات</th>
                    <th>زمان / مدت</th>
                    <th>دستور</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $prescription->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><strong><?php echo e($item->drug_name); ?></strong><?php if($item->usage_type): ?><br><small><?php echo e($item->usage_type); ?></small><?php endif; ?></td>
                        <td><?php echo e(trim(($item->dosage ?: '') . ' ' . ($item->frequency ?: '')) ?: '—'); ?></td>
                        <td><?php echo e($item->meal_timing ? $item->mealTimingLabel() : ''); ?><?php echo e($item->duration ? ' · '.$item->duration : ''); ?></td>
                        <td><?php echo e($item->instructions ?: '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <?php if($prescription->notes): ?>
            <p style="margin-top:12px;font-size:13px;"><strong>یادداشت:</strong> <?php echo e($prescription->notes); ?></p>
        <?php endif; ?>

        <div class="rx-foot">
            <div class="qr-box">
                <?php if($qrDataUri): ?>
                    <img src="<?php echo e($qrDataUri); ?>" alt="QR">
                <?php endif; ?>
                <div style="font-size:10px;color:#666;margin-top:4px;" dir="ltr"><?php echo e($qrPayload); ?></div>
            </div>
            <div class="sign-area">
                <?php if($signatureUrl): ?>
                    <img src="<?php echo e($signatureUrl); ?>" alt="امضا">
                <?php endif; ?>
                <?php if($stampUrl): ?>
                    <img src="<?php echo e($stampUrl); ?>" alt="مهر" class="stamp">
                <?php endif; ?>
                <div class="sign-line"><?php echo e($doctorName); ?></div>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\modules\prescriptions\print.blade.php ENDPATH**/ ?>