<?php
    use App\Models\Patient;
    $patients = $patients ?? Patient::query()->orderBy('name')->limit(100)->get(['id', 'name', 'mobile']);
?>

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
        <h2 class="page-title">لیست انتظار</h2>
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

            <div class="grid gap-4 lg:grid-cols-3">
                <form method="POST" action="<?php echo e(route('modules.waiting.store')); ?>" class="panel space-y-3 p-4 lg:col-span-1">
                    <?php echo csrf_field(); ?>
                    <h3 class="text-sm font-bold" style="color: var(--ink);">افزودن به صف</h3>
                    <select name="patient_id" class="field-input w-full">
                        <option value="">بیمار موجود (اختیاری)</option>
                        <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($p->id); ?>"><?php echo e($p->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <input type="text" name="patient_name" class="field-input w-full" placeholder="نام" required maxlength="160">
                    <input type="text" name="mobile" class="field-input w-full" placeholder="موبایل" required maxlength="20" dir="ltr">
                    <input type="text" name="national_code" class="field-input w-full" placeholder="کد ملی" maxlength="20" dir="ltr">
                    <select name="kind" class="field-input w-full" required>
                        <option value="visit">ویزیت</option>
                        <option value="surgery">عمل</option>
                    </select>
                    <input type="text" name="preferred_date" class="field-input w-full" placeholder="تاریخ ترجیحی (شمسی)" dir="ltr">
                    <input type="number" name="priority" class="field-input w-full" placeholder="اولویت (1=بالا)" min="1" max="999" value="50">
                    <textarea name="notes" rows="2" class="field-input w-full" placeholder="یادداشت"></textarea>
                    <button type="submit" class="btn-primary w-full !py-2">ثبت در صف</button>
                </form>

                <div class="panel p-4 lg:col-span-2">
                    <h3 class="mb-3 text-sm font-bold" style="color: var(--ink);">صف فعال</h3>
                    <div class="space-y-2">
                        <?php $__empty_1 = true; $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border px-3 py-2 text-xs" style="border-color: var(--line);">
                                <div>
                                    <div class="font-bold"><?php echo e($entry->patient_name); ?></div>
                                    <div style="color: var(--muted);"><?php echo e($entry->mobile); ?> · <?php echo e($entry->kind === 'surgery' ? 'عمل' : 'ویزیت'); ?> · اولویت <?php echo e($entry->priority); ?></div>
                                    <?php if($entry->preferred_date): ?>
                                        <div dir="ltr"><?php echo e(jalali($entry->preferred_date, 'Y/m/d')); ?></div>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" action="<?php echo e(route('modules.waiting.status', $entry)); ?>" class="flex flex-wrap gap-1">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <input type="hidden" name="status" value="contacted">
                                    <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">تماس شد</button>
                                </form>
                                <form method="POST" action="<?php echo e(route('modules.waiting.convert', $entry)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn-primary !px-2 !py-1 !text-[10px]">ساخت نوبت</button>
                                </form>
                                <form method="POST" action="<?php echo e(route('modules.waiting.status', $entry)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">لغو</button>
                                </form>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-xs" style="color: var(--muted);">صف خالی است.</p>
                        <?php endif; ?>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\modules\waiting\index.blade.php ENDPATH**/ ?>