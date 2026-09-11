<?php if($isStaff && \App\Support\FeatureFlags::enabled('features.consent_forms')): ?>
<?php
    $consents = $patient->consents ?? collect();
    $consentTemplates = \App\Models\ConsentTemplate::query()->where('is_active', true)->orderBy('title')->get();
    $focusSurgeryId = (int) request('surgery', 0) ?: null;
?>
<div class="tg-side__card space-y-2" id="patient-consent-panel">
    <div class="flex items-center justify-between gap-2">
        <h3 class="text-sm font-bold" style="color: var(--ink);">رضایت‌نامه‌ها</h3>
        <span class="text-[11px] font-bold" style="color: var(--muted);"><?php echo e($consents->count()); ?></span>
    </div>

    <?php if($canClinical && $consentTemplates->isNotEmpty()): ?>
    <form method="POST" action="<?php echo e(route('modules.consent.records.store')); ?>" class="space-y-2 rounded-lg border p-2.5 text-xs" style="border-color: var(--line);">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="patient_id" value="<?php echo e($patient->id); ?>">
        <?php if($focusSurgeryId): ?>
            <input type="hidden" name="subject_type" value="surgery">
            <input type="hidden" name="subject_id" value="<?php echo e($focusSurgeryId); ?>">
        <?php endif; ?>
        <select name="consent_template_id" class="field-input w-full !text-xs" required>
            <option value="">قالب رضایت…</option>
            <?php $__currentLoopData = $consentTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tpl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($tpl->id); ?>"><?php echo e($tpl->title); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <input type="text" name="signed_by_name" class="field-input w-full !text-xs" placeholder="نام امضاکننده" required>
        <button type="submit" class="btn-primary w-full !py-1.5 !text-xs">ثبت رضایت</button>
    </form>
    <?php endif; ?>

    <div class="space-y-2 max-h-48 overflow-auto">
        <?php $__empty_1 = true; $__currentLoopData = $consents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="rounded-lg border px-2.5 py-2 text-[11px]" style="border-color: var(--line);">
                <div class="font-bold"><?php echo e($c->template?->title); ?></div>
                <div style="color: var(--muted);"><?php echo e($c->signed_by_name); ?> · <?php echo e(jalali($c->signed_at, 'Y/m/d')); ?></div>
                <a href="<?php echo e(route('modules.consent.print', $c)); ?>" target="_blank" class="mt-1 inline-block font-bold" style="color: var(--brand-dark);">چاپ</a>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-xs" style="color: var(--muted);">رضایت‌نامه ثبت نشده.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\patients\partials\consent-panel.blade.php ENDPATH**/ ?>