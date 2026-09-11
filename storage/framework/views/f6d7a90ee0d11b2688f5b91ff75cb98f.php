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
        <h2 class="page-title">جدول بینایی</h2>
     <?php $__env->endSlot(); ?>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
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

            <div class="panel p-4">
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="text-xs font-bold" style="color: var(--muted);">فیلتر بیمار</label>
                        <select name="patient" class="field-input mt-1 min-w-[12rem]">
                            <option value="">همه</option>
                            <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($p->id); ?>" <?php if($patientFilter == $p->id): echo 'selected'; endif; ?>><?php echo e($p->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-secondary !py-2">اعمال</button>
                    <?php if($patientFilter): ?>
                        <a href="<?php echo e(route('modules.eye-chart.index')); ?>" class="btn-ghost !py-2 !text-xs">حذف فیلتر</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="panel p-4 lg:col-span-1">
                    <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">بیماران با VA</h3>
                    <div class="space-y-1 max-h-96 overflow-auto">
                        <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e(route('modules.eye-chart.index', ['patient' => $p->id])); ?>"
                               class="flex items-center justify-between rounded-lg px-2 py-1.5 text-xs <?php echo e($patientFilter == $p->id ? 'font-bold' : ''); ?>"
                               style="color: var(--ink); background: <?php echo e($patientFilter == $p->id ? 'var(--panel-soft)' : 'transparent'); ?>;">
                                <span><?php echo e($p->name); ?></span>
                                <span style="color: var(--muted);"><?php echo e($p->visits_count); ?></span>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <div class="panel p-4 lg:col-span-2">
                    <h3 class="mb-3 text-sm font-bold" style="color: var(--ink);">روند بینایی</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr style="color: var(--muted);">
                                    <th class="py-2 text-right">بیمار</th>
                                    <th class="py-2 text-right">تاریخ</th>
                                    <th class="py-2 text-right">VA راست</th>
                                    <th class="py-2 text-right">VA چپ</th>
                                    <th class="py-2 text-right">چشم</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $visits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr class="border-t" style="border-color: var(--line);">
                                        <td class="py-2">
                                            <a href="<?php echo e(route('patients.show', $v->patient_id)); ?>" class="font-bold hover:underline"><?php echo e($v->patient?->name); ?></a>
                                        </td>
                                        <td class="py-2" dir="ltr"><?php echo e(jalali($v->created_at, 'Y/m/d')); ?></td>
                                        <td class="py-2 font-mono" dir="ltr"><?php echo e($v->va_right ?: '—'); ?></td>
                                        <td class="py-2 font-mono" dir="ltr"><?php echo e($v->va_left ?: '—'); ?></td>
                                        <td class="py-2"><?php echo e($v->eye_side ?: '—'); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="5" class="py-4 text-center" style="color: var(--muted);">رکورد VA ثبت نشده.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\modules\eye-chart\index.blade.php ENDPATH**/ ?>