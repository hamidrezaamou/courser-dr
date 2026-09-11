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
        <h2 class="page-title">داشبورد کیفیت</h2>
     <?php $__env->endSlot(); ?>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal62b620b00b7a9e8b56ff6c9127baa696 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal62b620b00b7a9e8b56ff6c9127baa696 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modules-dock','data' => ['moduleSection' => $moduleSection]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modules-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($moduleSection)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal62b620b00b7a9e8b56ff6c9127baa696)): ?>
<?php $attributes = $__attributesOriginal62b620b00b7a9e8b56ff6c9127baa696; ?>
<?php unset($__attributesOriginal62b620b00b7a9e8b56ff6c9127baa696); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal62b620b00b7a9e8b56ff6c9127baa696)): ?>
<?php $component = $__componentOriginal62b620b00b7a9e8b56ff6c9127baa696; ?>
<?php unset($__componentOriginal62b620b00b7a9e8b56ff6c9127baa696); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.flash','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flash'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $attributes = $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $component = $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>

            <p class="text-sm" style="color: var(--muted);">آمار ماه <?php echo e($monthLabel); ?></p>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: var(--ink);"><?php echo e($stats['total']); ?></div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">کل نوبت‌ها</div>
                </div>
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: #047857;"><?php echo e($stats['done_rate']); ?>%</div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">انجام‌شده (<?php echo e($stats['done']); ?>)</div>
                </div>
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: var(--warn);"><?php echo e($stats['no_show_rate']); ?>%</div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">عدم حضور (<?php echo e($stats['no_show']); ?>)</div>
                </div>
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: var(--ink);"><?php echo e($stats['today_total']); ?></div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">نوبت امروز</div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="panel p-4">
                    <h3 class="text-sm font-bold" style="color: var(--ink);">لغوها</h3>
                    <div class="mt-2 text-3xl font-bold" style="color: var(--muted);"><?php echo e($stats['cancelled']); ?></div>
                </div>
                <?php if($stats['checklist_total'] > 0 || \App\Support\SurgeryChecklist::isAvailable()): ?>
                <div class="panel p-4">
                    <h3 class="text-sm font-bold" style="color: var(--ink);">چک‌لیست عمل</h3>
                    <div class="mt-2 text-3xl font-bold" style="color: var(--brand-dark);"><?php echo e($stats['checklist_rate']); ?>%</div>
                    <p class="mt-1 text-xs" style="color: var(--muted);"><?php echo e($stats['checklist_saved']); ?> از <?php echo e($stats['checklist_total']); ?> ذخیره شده</p>
                </div>
                <?php endif; ?>
            </div>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\modules\quality\index.blade.php ENDPATH**/ ?>