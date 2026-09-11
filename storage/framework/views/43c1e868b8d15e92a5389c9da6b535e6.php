<?php
    $fmt = function (int $bytes): string {
        if ($bytes < 1024) return $bytes.' B';
        if ($bytes < 1048576) return number_format($bytes / 1024, 1).' KB';
        if ($bytes < 1073741824) return number_format($bytes / 1048576, 1).' MB';
        return number_format($bytes / 1073741824, 2).' GB';
    };
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
                <h2 class="admin-header__title">سامانه</h2>
            </div>
            <div class="admin-header__actions">
                <form method="POST" action="<?php echo e(route('admin.settings.backup')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn-primary btn-primary--compact" onclick="return confirm('بک‌آپ دیتابیس الان گرفته شود؟')">
                        اجرای بک‌آپ
                    </button>
                </form>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal0f5b24ef0b91ecd95fa5272f30464615 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-dock','data' => ['adminSection' => 'system']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['admin-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('system')]); ?>
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

            <div class="admin-split">
                <section class="admin-panel">
                    <div class="admin-panel__head">
                        <h3 class="admin-panel__title">سلامت سیستم</h3>
                    </div>
                    <div class="admin-status-list">
                        <div class="admin-status-row">
                            <span>اپلیکیشن</span>
                            <strong class="is-on">سالم</strong>
                        </div>
                        <div class="admin-status-row">
                            <span>دیتابیس (<?php echo e($driver); ?>)</span>
                            <strong class="<?php echo e($health['db'] ? 'is-on' : 'is-off'); ?>"><?php echo e($health['db'] ? 'متصل' : 'قطع'); ?></strong>
                        </div>
                        <div class="admin-status-row">
                            <span>صف انتظار (jobs)</span>
                            <strong><?php echo e($health['queue_pending'] === null ? '—' : number_format($health['queue_pending'])); ?></strong>
                        </div>
                        <div class="admin-status-row">
                            <span>جاب‌های ناموفق</span>
                            <strong class="<?php echo e(($health['queue_failed'] ?? 0) > 0 ? 'is-off' : 'is-on'); ?>">
                                <?php echo e($health['queue_failed'] === null ? '—' : number_format($health['queue_failed'])); ?>

                            </strong>
                        </div>
                    </div>
                    <p class="admin-panel__hint">کرون‌ها: یادآوری ۱۸:۰۰ · پیگیری ۱۰:۰۰ · بک‌آپ ۰۲:۳۰ · پاکسازی هفتگی ۰۳:۱۵</p>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__head">
                        <h3 class="admin-panel__title">فضای ذخیره‌سازی</h3>
                    </div>
                    <div class="admin-status-list">
                        <div class="admin-status-row">
                            <span>فایل‌های عمومی (عکس/ویس/...)</span>
                            <strong class="ltr-data" dir="ltr"><?php echo e($fmt($storage['documents'])); ?></strong>
                        </div>
                        <div class="admin-status-row">
                            <span>بک‌آپ‌ها</span>
                            <strong class="ltr-data" dir="ltr"><?php echo e($fmt($storage['backups'])); ?></strong>
                        </div>
                        <div class="admin-status-row">
                            <span>لاگ‌های Laravel</span>
                            <strong class="ltr-data" dir="ltr"><?php echo e($fmt($storage['logs'])); ?></strong>
                        </div>
                    </div>
                </section>
            </div>

            <section class="admin-panel space-y-3">
                <div class="admin-panel__head">
                    <h3 class="admin-panel__title">سیاست نگه‌داشت داده</h3>
                </div>
                <form method="POST" action="<?php echo e(route('admin.settings.privacy.update')); ?>" class="space-y-3">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'عمر لاگ ممیزی (روز)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'عمر لاگ ممیزی (روز)']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'activity_log_days','type' => 'number','min' => '30','max' => '3650','class' => 'mt-1 block w-full','value' => old('activity_log_days', $privacy['activity_log_days']),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'activity_log_days','type' => 'number','min' => '30','max' => '3650','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('activity_log_days', $privacy['activity_log_days'])),'required' => true]); ?>
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
                        </div>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'عمر کش QR (روز)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'عمر کش QR (روز)']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'qr_cache_days','type' => 'number','min' => '1','max' => '365','class' => 'mt-1 block w-full','value' => old('qr_cache_days', $privacy['qr_cache_days']),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'qr_cache_days','type' => 'number','min' => '1','max' => '365','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('qr_cache_days', $privacy['qr_cache_days'])),'required' => true]); ?>
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
                        </div>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'متن سیاست برای مطب']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'متن سیاست برای مطب']); ?>
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
                        <textarea name="retention_note" rows="3" class="field-input mt-1 w-full"><?php echo e(old('retention_note', $privacy['retention_note'])); ?></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">ذخیره سیاست</button>
                    </div>
                </form>
            </section>

            <section class="admin-panel">
                <div class="admin-panel__head">
                    <h3 class="admin-panel__title">انطباق و قابلیت‌ها</h3>
                    <a href="<?php echo e(route('admin.settings.features')); ?>" class="admin-panel__link">ویرایش</a>
                </div>
                <div class="admin-status-list">
                    <div class="admin-status-row">
                        <span>آخرین خروجی ممیزی</span>
                        <strong>
                            <?php if($lastAuditExport): ?>
                                <?php echo e(jalali($lastAuditExport->created_at, 'Y/m/d H:i')); ?>

                                <a href="<?php echo e(route('activity-logs.index', ['action' => 'exported'])); ?>" class="admin-panel__link ms-2">مشاهده</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </strong>
                    </div>
                    <?php $__currentLoopData = ($features ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $on): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="admin-status-row">
                            <span><?php echo e(\App\Support\FeatureFlags::definitions()[$key]['label'] ?? $key); ?></span>
                            <strong class="<?php echo e($on ? 'is-on' : 'is-off'); ?>"><?php echo e($on ? 'روشن' : 'خاموش'); ?></strong>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </section>

            <section class="admin-panel !p-0 overflow-hidden">
                <div class="admin-panel__head px-4 pt-4">
                    <h3 class="admin-panel__title">بک‌آپ‌های اخیر</h3>
                    <span class="admin-panel__hint !mt-0">دانلود برای همه مدیران · بازیابی فقط مدیر کل</span>
                </div>
                <?php if($backups->isEmpty()): ?>
                    <p class="admin-empty">هنوز بک‌آپی نیست.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>فایل</th>
                                    <th>حجم</th>
                                    <th>زمان</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $backups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $backup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="ltr-data" dir="ltr"><?php echo e($backup['name']); ?></td>
                                        <td class="ltr-data" dir="ltr"><?php echo e($fmt($backup['size'])); ?></td>
                                        <td class="ltr-data" dir="ltr"><?php echo e(jalali(\Carbon\Carbon::createFromTimestamp($backup['at']), 'Y/m/d H:i')); ?></td>
                                        <td>
                                            <div class="flex flex-wrap gap-2">
                                                <a href="<?php echo e(route('admin.settings.backup.download', $backup['name'])); ?>" class="btn-secondary !px-2 !py-1 !text-[10px]">دانلود</a>
                                                <?php if($isAdmin): ?>
                                                    <form method="POST" action="<?php echo e(route('admin.settings.backup.restore')); ?>" onsubmit="return confirm('دیتابیس فعلی جایگزین می‌شود. قبل از بازیابی یک بک‌آپ تازه گرفته می‌شود. مطمئنید؟')">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" name="file" value="<?php echo e($backup['name']); ?>">
                                                        <input type="hidden" name="confirm" value="RESTORE">
                                                        <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px] text-red-600">بازیابی</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\settings\system.blade.php ENDPATH**/ ?>