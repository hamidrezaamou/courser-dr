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
            <h2 class="page-title">انواع عمل</h2>
            <a href="<?php echo e(route('settings.times', ['kind' => 'surgery'])); ?>" class="btn-ghost hidden sm:inline-flex">تایم‌ها</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="settings-page-body">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal8a7b4fd4c5e3c720c3975a07cc89c510 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8a7b4fd4c5e3c720c3975a07cc89c510 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.settings-dock','data' => ['settingsSection' => 'surgery-types']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('settings-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['settings-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('surgery-types')]); ?>
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

            <div class="grid gap-5 lg:grid-cols-1 xl:grid-cols-[340px_minmax(0,1fr)]">
                <section class="panel p-5">
                    <h3 class="mb-4 text-sm font-bold" style="color: var(--ink);">افزودن نوع عمل</h3>
                    <form method="POST" action="<?php echo e(route('surgery-types.store')); ?>" class="space-y-4">
                        <?php echo csrf_field(); ?>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'name','value' => 'نام نوع عمل']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'name','value' => 'نام نوع عمل']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'name','name' => 'name','type' => 'text','class' => 'mt-1 block w-full','value' => old('name'),'required' => true,'placeholder' => 'مثال: آب مروارید']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'name','name' => 'name','type' => 'text','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('name')),'required' => true,'placeholder' => 'مثال: آب مروارید']); ?>
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
                        <button type="submit" class="btn-primary w-full !py-3">ذخیره نوع</button>
                    </form>
                </section>

                <section class="panel overflow-hidden p-0">
                    <div class="flex items-center justify-between border-b px-5 py-4" style="border-color: var(--line);">
                        <h3 class="text-sm font-bold" style="color: var(--ink);">لیست انواع و زیرگروه‌ها</h3>
                        <span class="rounded-full px-3 py-1 text-xs font-bold" style="background: var(--brand-soft); color: var(--brand-dark);">
                            <?php echo e($types->count()); ?> نوع
                        </span>
                    </div>

                    <?php if($types->isEmpty()): ?>
                        <div class="pp-empty">هنوز نوعی ثبت نشده.</div>
                    <?php else: ?>
                        <div class="divide-y" style="border-color: var(--line);">
                            <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="space-y-3 p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <form method="POST" action="<?php echo e(route('surgery-types.update', $type)); ?>" class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PUT'); ?>
                                            <input type="text" name="name" value="<?php echo e(old('name', $type->name)); ?>" class="field-input min-w-0 flex-1" required>
                                            <label class="flex items-center gap-2 text-xs font-bold whitespace-nowrap" style="color: var(--muted);">
                                                <input type="checkbox" name="is_active" value="1" <?php if(old('is_active', $type->is_active)): echo 'checked'; endif; ?>>
                                                فعال
                                            </label>
                                            <button type="submit" class="touch-action shrink-0" style="background:var(--panel-soft);color:var(--ink)">ذخیره</button>
                                        </form>
                                        <form method="POST" action="<?php echo e(route('surgery-types.destroy', $type)); ?>" onsubmit="return confirm('حذف نوع و همه زیرگروه‌ها؟')" class="sm:shrink-0">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="touch-action w-full text-red-600 sm:w-auto" style="background:color-mix(in srgb, #fee2e2 80%, transparent)">حذف</button>
                                        </form>
                                    </div>

                                    <div class="rounded-xl border p-3" style="border-color:var(--line);background:var(--panel-soft)">
                                        <div class="mb-2 flex items-center justify-between gap-2">
                                            <p class="text-xs font-bold" style="color:var(--muted)">زیرگروه‌ها</p>
                                        </div>
                                        <ul class="mb-3 space-y-1">
                                            <?php $__empty_1 = true; $__currentLoopData = $type->subtypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subtype): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                <li class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm" style="background:var(--panel);color:var(--ink)">
                                                    <span><?php echo e($subtype->name); ?></span>
                                                    <form method="POST" action="<?php echo e(route('surgery-types.subtypes.destroy', $subtype)); ?>" onsubmit="return confirm('حذف زیرگروه؟')">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button type="submit" class="text-xs text-rose-500">حذف</button>
                                                    </form>
                                                </li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                <li class="text-xs" style="color:var(--muted)">هنوز زیرگروهی نیست.</li>
                                            <?php endif; ?>
                                        </ul>
                                        <form method="POST" action="<?php echo e(route('surgery-types.subtypes.store', $type)); ?>" class="flex gap-2">
                                            <?php echo csrf_field(); ?>
                                            <input type="text" name="name" class="field-input" placeholder="زیرگروه جدید + " required>
                                            <button type="submit" class="btn-secondary shrink-0 !px-3" title="افزودن">+</button>
                                        </form>

                                        <?php if(\App\Support\SurgeryChecklist::isAvailable()): ?>
                                        <?php if (isset($component)) { $__componentOriginaldf64ffa5fa17a5aed575884242e351bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf64ffa5fa17a5aed575884242e351bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.surgery-checklist-template-editor','data' => ['typeId' => $type->id,'label' => $type->name.' (عمومی)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('surgery-checklist-template-editor'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($type->id),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($type->name.' (عمومی)')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf64ffa5fa17a5aed575884242e351bc)): ?>
<?php $attributes = $__attributesOriginaldf64ffa5fa17a5aed575884242e351bc; ?>
<?php unset($__attributesOriginaldf64ffa5fa17a5aed575884242e351bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf64ffa5fa17a5aed575884242e351bc)): ?>
<?php $component = $__componentOriginaldf64ffa5fa17a5aed575884242e351bc; ?>
<?php unset($__componentOriginaldf64ffa5fa17a5aed575884242e351bc); ?>
<?php endif; ?>

                                        <?php $__currentLoopData = $type->subtypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subtype): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if (isset($component)) { $__componentOriginaldf64ffa5fa17a5aed575884242e351bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf64ffa5fa17a5aed575884242e351bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.surgery-checklist-template-editor','data' => ['typeId' => $type->id,'subtypeId' => $subtype->id,'label' => $type->name.' · '.$subtype->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('surgery-checklist-template-editor'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($type->id),'subtype-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($subtype->id),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($type->name.' · '.$subtype->name)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf64ffa5fa17a5aed575884242e351bc)): ?>
<?php $attributes = $__attributesOriginaldf64ffa5fa17a5aed575884242e351bc; ?>
<?php unset($__attributesOriginaldf64ffa5fa17a5aed575884242e351bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf64ffa5fa17a5aed575884242e351bc)): ?>
<?php $component = $__componentOriginaldf64ffa5fa17a5aed575884242e351bc; ?>
<?php unset($__componentOriginaldf64ffa5fa17a5aed575884242e351bc); ?>
<?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\surgery-types\index.blade.php ENDPATH**/ ?>