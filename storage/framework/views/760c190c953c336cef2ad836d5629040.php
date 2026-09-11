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
                <h2 class="admin-header__title">لینک‌های ویژه هدر</h2>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal0f5b24ef0b91ecd95fa5272f30464615 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-dock','data' => ['adminSection' => 'quick-links']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['admin-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('quick-links')]); ?>
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

            <div class="mx-auto max-w-2xl space-y-4">
            <div class="admin-panel space-y-2">
                <p class="text-sm" style="color: var(--muted);">
                    این عنوان‌ها و لینک‌ها در هدر، داخل دکمهٔ <strong>ویژه</strong> (بعد از ماژول‌ها) نمایش داده می‌شوند.
                    مسیر داخلی مثل <span dir="ltr">/reports</span> یا آدرس کامل <span dir="ltr">https://...</span> مجاز است.
                </p>
            </div>

            <form
                method="POST"
                action="<?php echo e(route('admin.settings.quick-links.update')); ?>"
                class="admin-panel space-y-4"
                x-data="{
                    links: <?php echo e(\Illuminate\Support\Js::from(old('links', $links))); ?>,
                    max: <?php echo e(\App\Support\NavQuickLinks::MAX); ?>,
                    add() {
                        if (this.links.length >= this.max) return;
                        this.links.push({ title: '', url: '' });
                    },
                    remove(index) {
                        if (this.links.length <= 1) return;
                        this.links.splice(index, 1);
                    },
                }"
            >
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('links'),'class' => 'mb-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('links')),'class' => 'mb-2']); ?>
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

                <div class="space-y-3">
                    <template x-for="(row, index) in links" :key="index">
                        <div class="rounded-xl border p-3 space-y-2" style="border-color: var(--line); background: var(--panel-soft);">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold" style="color: var(--muted);" x-text="'ردیف ' + (index + 1)"></span>
                                <button type="button" class="btn-ghost !px-2 !py-1 !text-xs text-red-600" @click="remove(index)" x-show="links.length > 1">حذف</button>
                            </div>
                            <div>
                                <label class="text-xs font-bold" style="color: var(--muted);">عنوان</label>
                                <input type="text" class="field-input mt-1 w-full" maxlength="80"
                                       :name="'links['+index+'][title]'" x-model="row.title" placeholder="مثال: سامانه بیمه">
                            </div>
                            <div>
                                <label class="text-xs font-bold" style="color: var(--muted);">لینک</label>
                                <input type="text" class="field-input mt-1 w-full ltr-data" dir="ltr" maxlength="500"
                                       :name="'links['+index+'][url]'" x-model="row.url" placeholder="/reports یا https://example.com">
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <button type="button" class="btn-secondary !py-2 !px-3 !text-sm" @click="add()" :disabled="links.length >= max">
                        + افزودن لینک
                    </button>
                    <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">ذخیره</button>
                </div>
            </form>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\settings\quick-links.blade.php ENDPATH**/ ?>