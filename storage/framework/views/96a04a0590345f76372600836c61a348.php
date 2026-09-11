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
        <h2 class="page-title">صورتحساب و بیمه</h2>
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

            <div class="grid gap-4 lg:grid-cols-2">
                <form method="POST" action="<?php echo e(route('modules.billing.tariffs.store')); ?>" class="panel space-y-3 p-4">
                    <?php echo csrf_field(); ?>
                    <h3 class="text-sm font-bold" style="color: var(--ink);">تعرفه جدید</h3>
                    <input type="text" name="name" class="field-input w-full" placeholder="نام خدمت" required maxlength="160">
                    <div class="grid grid-cols-2 gap-2">
                        <select name="kind" class="field-input w-full" required>
                            <option value="visit">ویزیت</option>
                            <option value="surgery">عمل</option>
                        </select>
                        <input type="number" name="amount" class="field-input w-full" placeholder="مبلغ (تومان)" min="0" required dir="ltr">
                    </div>
                    <div>
                        <label class="text-xs font-bold" style="color: var(--muted);">پوشش بیمه (%)</label>
                        <input type="number" name="insurance_coverage" class="field-input mt-1 w-full" min="0" max="100" value="0" required dir="ltr">
                    </div>
                    <button type="submit" class="btn-primary w-full !py-2">ذخیره تعرفه</button>
                </form>

                <form method="POST" action="<?php echo e(route('modules.billing.records.store')); ?>" class="panel space-y-3 p-4">
                    <?php echo csrf_field(); ?>
                    <h3 class="text-sm font-bold" style="color: var(--ink);">ثبت صورتحساب</h3>
                    <select name="billable_type" class="field-input w-full" required>
                        <option value="visit">ویزیت</option>
                        <option value="surgery">عمل</option>
                    </select>
                    <select name="billable_id" class="field-input w-full" required>
                        <option value="">انتخاب نوبت…</option>
                        <optgroup label="ویزیت‌ها">
                            <?php $__currentLoopData = $recentVisits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($a->id); ?>">#<?php echo e($a->id); ?> · <?php echo e($a->patient_name); ?> · <?php echo e(jalali($a->scheduled_date, 'Y/m/d')); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </optgroup>
                        <optgroup label="عمل‌ها">
                            <?php $__currentLoopData = $recentSurgeries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($s->id); ?>">#<?php echo e($s->id); ?> · <?php echo e($s->patient_name); ?> · <?php echo e(jalali($s->scheduled_date, 'Y/m/d')); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </optgroup>
                    </select>
                    <select name="service_tariff_id" class="field-input w-full" required>
                        <option value="">تعرفه…</option>
                        <?php $__currentLoopData = $tariffs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($t->id); ?>"><?php echo e($t->name); ?> — <?php echo e(number_format($t->amount)); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <select name="settlement_status" class="field-input w-full" required>
                        <option value="open">باز</option>
                        <option value="partial">جزئی</option>
                        <option value="paid">تسویه</option>
                    </select>
                    <button type="submit" class="btn-primary w-full !py-2">ثبت صورتحساب</button>
                </form>
            </div>

            <div class="panel p-4">
                <h3 class="mb-3 text-sm font-bold" style="color: var(--ink);">تعرفه‌های فعال</h3>
                <div class="mb-4 flex flex-wrap gap-2">
                    <?php $__empty_1 = true; $__currentLoopData = $tariffs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <span class="rounded-full border px-3 py-1 text-xs font-bold" style="border-color: var(--line);">
                            <?php echo e($t->name); ?> · <?php echo e(number_format($t->amount)); ?> · بیمه <?php echo e($t->insurance_coverage); ?>%
                        </span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-xs" style="color: var(--muted);">تعرفه‌ای ثبت نشده.</p>
                    <?php endif; ?>
                </div>

                <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">آخرین صورتحساب‌ها</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr style="color: var(--muted);">
                                <th class="py-2 text-right">بیمار</th>
                                <th class="py-2 text-right">تعرفه</th>
                                <th class="py-2 text-right">سهم بیمار</th>
                                <th class="py-2 text-right">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="border-t" style="border-color: var(--line);">
                                    <td class="py-2"><?php echo e($r->patient?->name); ?></td>
                                    <td class="py-2"><?php echo e($r->tariff?->name ?? '—'); ?></td>
                                    <td class="py-2 font-mono" dir="ltr"><?php echo e(number_format($r->patient_share)); ?></td>
                                    <td class="py-2"><?php echo e(match($r->settlement_status) { 'paid' => 'تسویه', 'partial' => 'جزئی', default => 'باز' }); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="4" class="py-4 text-center" style="color: var(--muted);">صورتحسابی ثبت نشده.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\modules\billing\index.blade.php ENDPATH**/ ?>