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
        <h2 class="page-title">رضایت‌نامه دیجیتال</h2>
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
                <form method="POST" action="<?php echo e(route('modules.consent.templates.store')); ?>" class="panel space-y-3 p-4">
                    <?php echo csrf_field(); ?>
                    <h3 class="text-sm font-bold" style="color: var(--ink);">قالب جدید</h3>
                    <input type="text" name="title" class="field-input w-full" placeholder="عنوان" required maxlength="160">
                    <select name="kind" class="field-input w-full" required>
                        <option value="visit">ویزیت</option>
                        <option value="surgery">عمل</option>
                    </select>
                    <textarea name="body" rows="8" class="field-input w-full" placeholder="متن رضایت‌نامه" required maxlength="8000"></textarea>
                    <button type="submit" class="btn-primary w-full !py-2">ذخیره قالب</button>
                </form>

                <form method="POST" action="<?php echo e(route('modules.consent.records.store')); ?>" class="panel space-y-3 p-4">
                    <?php echo csrf_field(); ?>
                    <h3 class="text-sm font-bold" style="color: var(--ink);">ثبت امضا در پرونده</h3>
                    <select name="patient_id" class="field-input w-full" required>
                        <option value="">بیمار…</option>
                        <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($p->id); ?>"><?php echo e($p->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <select name="consent_template_id" class="field-input w-full" required>
                        <option value="">قالب…</option>
                        <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($t->id); ?>"><?php echo e($t->title); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <input type="text" name="signed_by_name" class="field-input w-full" placeholder="نام امضاکننده" required maxlength="160">
                    <button type="submit" class="btn-primary w-full !py-2">ثبت رضایت‌نامه</button>
                </form>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="panel p-4">
                    <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">قالب‌ها</h3>
                    <div class="space-y-2 max-h-80 overflow-auto">
                        <?php $__empty_1 = true; $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="rounded-lg border p-3 text-xs" style="border-color: var(--line);">
                                <div class="font-bold"><?php echo e($t->title); ?></div>
                                <div style="color: var(--muted);"><?php echo e($t->kind === 'surgery' ? 'عمل' : 'ویزیت'); ?></div>
                                <p class="mt-2 whitespace-pre-line leading-6"><?php echo e(\Illuminate\Support\Str::limit($t->body, 200)); ?></p>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-xs" style="color: var(--muted);">قالبی ثبت نشده.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="panel p-4">
                    <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">آخرین ثبت‌ها</h3>
                    <div class="space-y-2 max-h-80 overflow-auto">
                        <?php $__empty_1 = true; $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="rounded-lg border px-3 py-2 text-xs" style="border-color: var(--line);">
                                <div class="font-bold"><?php echo e($c->patient?->name); ?></div>
                                <div style="color: var(--muted);"><?php echo e($c->template?->title); ?> · <?php echo e($c->signed_by_name); ?></div>
                                <div dir="ltr"><?php echo e(jalali($c->signed_at, 'Y/m/d H:i')); ?></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-xs" style="color: var(--muted);">ثبت نشده.</p>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\modules\consent\index.blade.php ENDPATH**/ ?>