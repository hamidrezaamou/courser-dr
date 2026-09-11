<?php
    $featuredKeys = \App\Support\FeatureFlags::featuredKeys();
    $advancedFeatured = \App\Support\FeatureFlags::featuredKeysForGroup('advanced');
    $groupOrder = \App\Support\FeatureFlags::groupOrder();
    $onCount = collect($values)->filter()->count();
    $totalCount = count($definitions);
    $modulesOn = count(\App\Support\ModuleRegistry::enabled());
?>

<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve(['bodyClass' => 'is-admin'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="admin-header">
            <div>
                <p class="admin-header__eyebrow">مدیریت کل سایت</p>
                <h2 class="admin-header__title">قابلیت‌های اختیاری</h2>
            </div>
            <div class="admin-header__actions">
                <a href="<?php echo e(route('admin.index')); ?>" class="btn-ghost !text-xs !px-3 !py-1.5">نمای کلی</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal0f5b24ef0b91ecd95fa5272f30464615 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-dock','data' => ['adminSection' => 'features']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['admin-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('features')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615)): ?>
<?php $attributes = $__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615; ?>
<?php unset($__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0f5b24ef0b91ecd95fa5272f30464615)): ?>
<?php $component = $__componentOriginal0f5b24ef0b91ecd95fa5272f30464615; ?>
<?php unset($__componentOriginal0f5b24ef0b91ecd95fa5272f30464615); ?>
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

            <form method="POST" action="<?php echo e(route('admin.settings.features.update')); ?>" class="mx-auto max-w-3xl space-y-4">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <section class="admin-feature-summary">
                    <div>
                        <h3 class="admin-feature-summary__title">سوئیچ ماژول‌ها</h3>
                        <p class="admin-feature-summary__hint">
                            بخش‌های کلینیک و عمومی آماده‌اند. ماژول‌های پیشرفته پیش‌فرض خاموش‌اند —
                            با روشن کردن هر کدام، منوی «ماژول‌ها» و صفحهٔ مربوطه فعال می‌شود.
                        </p>
                    </div>
                    <div class="admin-feature-summary__stats">
                        <span class="admin-feature-summary__pill is-on"><?php echo e($onCount); ?> فعال</span>
                        <span class="admin-feature-summary__pill is-off"><?php echo e($totalCount - $onCount); ?> خاموش</span>
                        <?php if($modulesOn > 0): ?>
                            <span class="admin-feature-summary__pill is-on"><?php echo e($modulesOn); ?> ماژول فعال</span>
                        <?php endif; ?>
                    </div>
                </section>

                <?php $__currentLoopData = $featuredKeys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(isset($definitions[$key])): ?>
                        <?php if (isset($component)) { $__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-feature-card','data' => ['featureKey' => $key,'meta' => $definitions[$key],'enabled' => $values[$key] ?? $definitions[$key]['default'],'featured' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-feature-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['feature-key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key),'meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($definitions[$key]),'enabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($values[$key] ?? $definitions[$key]['default']),'featured' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35)): ?>
<?php $attributes = $__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35; ?>
<?php unset($__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35)): ?>
<?php $component = $__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35; ?>
<?php unset($__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35); ?>
<?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php $__currentLoopData = $groupOrder; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupKey): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $groupLabel = $groups[$groupKey] ?? $groupKey;
                        $groupDefs = collect($definitions)->filter(function ($meta, $key) use ($groupKey, $featuredKeys, $advancedFeatured) {
                            if (($meta['group'] ?? 'general') !== $groupKey) {
                                return false;
                            }
                            if (in_array($key, $featuredKeys, true)) {
                                return false;
                            }
                            if ($groupKey === 'advanced' && in_array($key, $advancedFeatured, true)) {
                                return false;
                            }

                            return true;
                        });
                        $isAdvanced = $groupKey === 'advanced';
                    ?>

                    <?php if($groupDefs->isNotEmpty() || ($isAdvanced && $advancedFeatured !== [])): ?>
                        <section class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                            'admin-panel admin-feature-group',
                            'admin-feature-group--advanced' => $isAdvanced,
                        ]); ?>">
                            <?php if($isAdvanced): ?>
                                <div class="admin-feature-group__intro">
                                    <div>
                                        <h3 class="admin-panel__title admin-feature-group__title"><?php echo e($groupLabel); ?></h3>
                                        <p class="admin-feature-group__lead">
                                            ماژول‌های اضافه برای مطب‌های بزرگ‌تر. خاموش = همان پنل فعلی.
                                            روشن = آماده برای فعال‌سازی در به‌روزرسانی بعد.
                                        </p>
                                    </div>
                                    <span class="admin-feature-group__tag">اختیاری</span>
                                </div>

                                <?php $__currentLoopData = $advancedFeatured; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if(isset($definitions[$key])): ?>
                                        <?php if (isset($component)) { $__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-feature-card','data' => ['featureKey' => $key,'meta' => $definitions[$key],'enabled' => $values[$key] ?? $definitions[$key]['default'],'featured' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-feature-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['feature-key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key),'meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($definitions[$key]),'enabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($values[$key] ?? $definitions[$key]['default']),'featured' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35)): ?>
<?php $attributes = $__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35; ?>
<?php unset($__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35)): ?>
<?php $component = $__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35; ?>
<?php unset($__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35); ?>
<?php endif; ?>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                <?php if($groupDefs->isNotEmpty()): ?>
                                    <div class="admin-feature-grid <?php echo e($advancedFeatured !== [] ? 'mt-3' : ''); ?>">
                                        <?php $__currentLoopData = $groupDefs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if (isset($component)) { $__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-feature-card','data' => ['featureKey' => $key,'meta' => $meta,'enabled' => $values[$key] ?? $meta['default']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-feature-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['feature-key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key),'meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'enabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($values[$key] ?? $meta['default'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35)): ?>
<?php $attributes = $__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35; ?>
<?php unset($__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35)): ?>
<?php $component = $__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35; ?>
<?php unset($__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35); ?>
<?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <h3 class="admin-panel__title admin-feature-group__title"><?php echo e($groupLabel); ?></h3>
                                <div class="admin-feature-grid">
                                    <?php $__currentLoopData = $groupDefs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if (isset($component)) { $__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-feature-card','data' => ['featureKey' => $key,'meta' => $meta,'enabled' => $values[$key] ?? $meta['default']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-feature-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['feature-key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key),'meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'enabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($values[$key] ?? $meta['default'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35)): ?>
<?php $attributes = $__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35; ?>
<?php unset($__attributesOriginale0f4accc1a3a6312c2a9c280a4e5cf35); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35)): ?>
<?php $component = $__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35; ?>
<?php unset($__componentOriginale0f4accc1a3a6312c2a9c280a4e5cf35); ?>
<?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <div class="admin-feature-actions">
                    <button type="submit" class="btn-primary">ذخیره قابلیت‌ها</button>
                    <a href="<?php echo e(route('admin.settings.system')); ?>" class="btn-ghost !text-sm">وضعیت سامانه</a>
                </div>
            </form>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\settings\features.blade.php ENDPATH**/ ?>