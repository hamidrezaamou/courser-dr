<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="page-kicker">چاپ برگه‌ها</p>
                <h2 class="page-title">برگه‌های چاپ · <?php echo e($surgery->patient_name); ?></h2>
                <p class="page-desc">
                    شناسه #<?php echo e($surgery->id); ?>

                    · <?php echo e(jalali($surgery->scheduled_date, 'Y/m/d')); ?>

                    <?php if($surgery->hospital): ?> · <?php echo e($surgery->hospital->name); ?> <?php endif; ?>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?php echo e(route('patients.show', $surgery->patient_id)); ?>" class="btn-ghost">پرونده</a>
                <a href="<?php echo e(route('reports.index', ['kind' => 'surgery'])); ?>" class="btn-ghost">گزارشات</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-xl px-4 sm:px-6">
            <?php if(!empty($checklistWarning)): ?>
                <div class="mb-4 rounded-2xl border px-4 py-3 text-sm font-semibold"
                     style="border-color: color-mix(in srgb, #b45309 35%, var(--line)); background: color-mix(in srgb, #b45309 10%, var(--panel)); color: #92400e;">
                    <?php echo e($checklistWarning); ?>

                    <div class="mt-1 text-xs font-medium" style="color: var(--muted);">می‌توانید چاپ را ادامه دهید؛ این فقط هشدار نرم است.</div>
                </div>
            <?php endif; ?>
            <?php echo $__env->make('prints.partials.type-grid', [
                'surgery' => $surgery,
                'types' => $types,
                'checklistWarning' => $checklistWarning ?? null,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\prints\hub.blade.php ENDPATH**/ ?>