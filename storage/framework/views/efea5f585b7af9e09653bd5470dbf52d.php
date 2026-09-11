<div class="panel space-y-4 p-5">
    <div class="rounded-2xl border p-4 text-center" style="border-color: var(--line); background: var(--panel-soft);">
        <div class="text-lg font-extrabold" style="color: var(--ink);"><?php echo e($surgery->patient_name); ?></div>
        <div class="mt-1 text-xs" style="color: var(--muted);">
            #<?php echo e($surgery->id); ?>

            · <?php echo e($surgery->hospital?->name ?: 'بدون بیمارستان'); ?>

            · <?php echo e(jalali($surgery->scheduled_date, 'Y/m/d')); ?>

            <?php if($surgery->scheduled_time): ?>
                · <?php echo e(\App\Support\SlotLabel::display((string) $surgery->scheduled_time)); ?>

            <?php endif; ?>
            · <?php echo e($surgery->surgery_type); ?>

        </div>
        <div class="mt-2 flex flex-wrap items-center justify-center gap-2">
            <span class="font-mono text-xs" dir="ltr" style="color: var(--muted);"><?php echo e($surgery->mobile); ?></span>
        </div>
    </div>

    <div class="grid gap-2">
        <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('surgery-appointments.print', [$surgery, $type['key']])); ?>"
               target="_blank"
               <?php if(!empty($checklistWarning)): ?>
                   onclick="return confirm(<?php echo \Illuminate\Support\Js::from(($checklistWarning ?? '').' ادامه چاپ؟')->toHtml() ?>)"
               <?php endif; ?>
               class="flex items-center justify-between gap-3 rounded-2xl border px-4 py-3 transition hover:border-[var(--brand)]"
               style="border-color: var(--line); background: var(--panel);">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="text-2xl"><?php echo e($type['icon']); ?></span>
                    <div class="min-w-0 text-right">
                        <div class="text-sm font-bold" style="color: var(--ink);"><?php echo e($type['title']); ?></div>
                        <div class="text-xs" style="color: var(--muted);"><?php echo e($type['desc']); ?></div>
                    </div>
                </div>
                <span class="shrink-0 rounded-full px-3 py-1 text-[11px] font-bold"
                      style="background: var(--brand-soft); color: var(--brand-dark);">پرینت</span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\prints\partials\type-grid.blade.php ENDPATH**/ ?>