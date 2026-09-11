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
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">بیمارستان‌ها</h2>
            <a href="<?php echo e(route('dashboard')); ?>" class="btn-ghost hidden sm:inline-flex">داشبورد</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="settings-page-body">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal8a7b4fd4c5e3c720c3975a07cc89c510 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8a7b4fd4c5e3c720c3975a07cc89c510 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.settings-dock','data' => ['settingsSection' => 'hospitals']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('settings-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['settings-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('hospitals')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8a7b4fd4c5e3c720c3975a07cc89c510)): ?>
<?php $attributes = $__attributesOriginal8a7b4fd4c5e3c720c3975a07cc89c510; ?>
<?php unset($__attributesOriginal8a7b4fd4c5e3c720c3975a07cc89c510); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8a7b4fd4c5e3c720c3975a07cc89c510)): ?>
<?php $component = $__componentOriginal8a7b4fd4c5e3c720c3975a07cc89c510; ?>
<?php unset($__componentOriginal8a7b4fd4c5e3c720c3975a07cc89c510); ?>
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

            <div class="panel overflow-hidden p-0 hospitals-hero"
                 style="background: linear-gradient(135deg, var(--brand-dark), var(--brand)); color: #fff;">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:gap-4 sm:px-7 sm:py-6">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 sm:h-12 sm:w-12">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15v18h-15V3zM9 7.5h1.5M9 11.25h1.5M9 15h1.5M13.5 7.5H15M13.5 11.25H15M13.5 15H15" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold sm:text-lg">مراکز ثبت‌شده</h3>
                            <p class="mt-0.5 hidden text-sm text-white/85 sm:block">نام بیمارستان هنگام ثبت نوبت عمل و تنظیم تایم نمایش داده می‌شود.</p>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-white/15 px-4 py-2 text-center ring-1 ring-white/20 sm:px-5 sm:py-3">
                        <div class="text-xl font-extrabold leading-none sm:text-2xl"><?php echo e($hospitals->count()); ?></div>
                        <div class="mt-1 text-[10px] text-white/90 sm:text-xs">مرکز</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-1 xl:grid-cols-[380px_minmax(0,1fr)]">
                <section class="panel p-5 sm:p-6">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold" style="color: var(--ink);">
                        <svg class="h-4 w-4" style="color: var(--brand);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        افزودن بیمارستان جدید
                    </h3>
                    <form method="POST" action="<?php echo e(route('hospitals.store')); ?>" class="space-y-4">
                        <?php echo csrf_field(); ?>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'name','value' => 'نام بیمارستان / مرکز جراحی']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'name','value' => 'نام بیمارستان / مرکز جراحی']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'name','name' => 'name','type' => 'text','class' => 'mt-1 block w-full','value' => old('name'),'required' => true,'autocomplete' => 'off','placeholder' => 'مثال: بیمارستان دی']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'name','name' => 'name','type' => 'text','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('name')),'required' => true,'autocomplete' => 'off','placeholder' => 'مثال: بیمارستان دی']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                            <p class="mt-1.5 text-[11px]" style="color: var(--muted);">این نام در ثبت نوبت و گزارش‌ها دیده می‌شود.</p>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['class' => 'mt-2','messages' => $errors->get('name')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-2','messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('name'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                        </div>
                        <button type="submit" class="btn-primary w-full !py-3">ذخیره در سیستم</button>
                    </form>
                </section>

                <section class="panel overflow-hidden p-0">
                    <div class="flex items-center justify-between border-b px-5 py-4" style="border-color: var(--line);">
                        <h3 class="text-sm font-bold" style="color: var(--ink);">لیست مراکز طرف قرارداد</h3>
                        <span class="rounded-full px-3 py-1 text-xs font-bold" style="background: var(--brand-soft); color: var(--brand-dark);">
                            <?php echo e($hospitals->count()); ?> مرکز
                        </span>
                    </div>

                    <?php if($hospitals->isEmpty()): ?>
                        <div class="pp-empty">
                            هنوز هیچ بیمارستانی ثبت نشده است.<br>
                            از فرم کنار صفحه اولین مرکز را اضافه کنید.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-right text-xs" style="color: var(--muted); background: var(--panel-soft);">
                                        <th class="px-5 py-3 font-semibold">کد</th>
                                        <th class="px-5 py-3 font-semibold">نام مرکز</th>
                                        <th class="px-5 py-3 font-semibold">نوبت‌ها</th>
                                        <th class="px-5 py-3 font-semibold text-center">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $hospitals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospital): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr class="border-t transition hover:bg-[var(--panel-soft)]" style="border-color: var(--line);">
                                            <td class="px-5 py-3">
                                                <span class="inline-flex min-w-[2rem] items-center justify-center rounded-lg px-2 py-1 text-xs font-bold" style="background: var(--panel-soft); color: var(--muted);">
                                                    <?php echo e($hospital->id); ?>

                                                </span>
                                            </td>
                                            <td class="px-5 py-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" style="background: color-mix(in srgb, var(--brand) 14%, transparent); color: var(--brand-dark);">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15v18h-15V3zM9 7.5h1.5M9 11.25h1.5M9 15h1.5M13.5 7.5H15M13.5 11.25H15M13.5 15H15" />
                                                        </svg>
                                                    </span>
                                                    <span class="font-bold" style="color: var(--ink);"><?php echo e($hospital->name); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3" style="color: var(--muted);">
                                                <?php echo e($hospital->surgery_appointments_count); ?> نوبت
                                            </td>
                                            <td class="px-5 py-3 text-center">
                                                <form method="POST" action="<?php echo e(route('hospitals.destroy', $hospital)); ?>"
                                                      onsubmit="return confirm('آیا از حذف این بیمارستان مطمئن هستید؟');">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="touch-action text-red-700"
                                                            style="background: color-mix(in srgb, #ef4444 12%, transparent);">
                                                        حذف
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\hospitals\index.blade.php ENDPATH**/ ?>