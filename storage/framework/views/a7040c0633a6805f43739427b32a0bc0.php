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
                <h2 class="admin-header__title">همگام‌سازی HIS</h2>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal0f5b24ef0b91ecd95fa5272f30464615 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-dock','data' => ['adminSection' => 'his']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['admin-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('his')]); ?>
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

            <div class="admin-panel space-y-2">
                <h3 class="text-sm font-extrabold">وضعیت اتصال</h3>

                <?php if(! $enabled): ?>
                    <p class="text-xs font-bold text-red-600">
                        همگام‌سازی خاموش است. تا وقتی <span class="ltr-data" dir="ltr">HIS_SYNC_ENABLED=true</span> نباشد، عامل نمی‌تواند داده بفرستد.
                    </p>
                <?php elseif(! $keyConfigured): ?>
                    <p class="text-xs font-bold text-red-600">
                        کلید عامل تنظیم نشده است؛ <span class="ltr-data" dir="ltr">HIS_AGENT_KEY</span> را در فایل env پر کنید.
                    </p>
                <?php else: ?>
                    <p class="text-xs text-emerald-700">همگام‌سازی روشن است و کلید عامل تنظیم شده.</p>
                <?php endif; ?>

                <p class="admin-panel__hint">
                    امضای درخواست‌ها:
                    <?php if($signed): ?>
                        <span class="font-bold text-emerald-700">فعال</span>
                    <?php else: ?>
                        غیرفعال — برای امنیت بیشتر <span class="ltr-data" dir="ltr">HIS_AGENT_SECRET</span> را هم تنظیم کنید.
                    <?php endif; ?>
                </p>
            </div>

            <div class="admin-panel space-y-3">
                <h3 class="text-sm font-extrabold">آخرین وضعیت هر بخش</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead class="text-slate-500">
                            <tr>
                                <th class="p-2">بخش</th>
                                <th class="p-2">آخرین موفقیت</th>
                                <th class="p-2">وضعیت</th>
                                <th class="p-2">ردیف واردشده</th>
                                <th class="p-2">از HIS / دستی</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="border-t border-slate-100">
                                    <td class="p-2 font-bold"><?php echo e($row['label']); ?></td>
                                    <td class="p-2 ltr-data" dir="ltr">
                                        <?php echo e($row['state']?->last_success_at?->diffForHumans() ?? '—'); ?>

                                    </td>
                                    <td class="p-2">
                                        <?php if($row['stale']): ?>
                                            <span class="font-bold text-red-600">بی‌خبر</span>
                                        <?php elseif($row['state']?->last_status === 'partial'): ?>
                                            <span class="font-bold text-amber-600">ناقص</span>
                                        <?php else: ?>
                                            <span class="font-bold text-emerald-700">سالم</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-2 ltr-data" dir="ltr"><?php echo e(number_format($row['imported'])); ?></td>
                                    <td class="p-2 ltr-data" dir="ltr">
                                        <?php echo e(number_format($counts[$row['resource']]['his'] ?? 0)); ?>

                                        /
                                        <?php echo e(number_format($counts[$row['resource']]['manual'] ?? 0)); ?>

                                    </td>
                                </tr>
                                <?php if($row['state']?->last_message): ?>
                                    <tr>
                                        <td colspan="5" class="px-2 pb-2 text-[11px] text-amber-700">
                                            <?php echo e($row['state']->last_message); ?>

                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <p class="admin-panel__hint">
                    اگر بیش از <?php echo e($staleMinutes); ?> دقیقه خبری نرسد، بخش «بی‌خبر» علامت می‌خورد؛ یعنی عامل روی سرور مطب متوقف شده است.
                </p>
            </div>

            <div class="admin-panel space-y-3">
                <h3 class="text-sm font-extrabold">آخرین بسته‌های دریافتی</h3>

                <?php if($logs->isEmpty()): ?>
                    <p class="admin-panel__hint">هنوز هیچ بسته‌ای دریافت نشده است.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead class="text-slate-500">
                                <tr>
                                    <th class="p-2">زمان</th>
                                    <th class="p-2">بخش</th>
                                    <th class="p-2">دریافت</th>
                                    <th class="p-2">جدید</th>
                                    <th class="p-2">به‌روز</th>
                                    <th class="p-2">خطا</th>
                                    <th class="p-2">مدت</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="border-t border-slate-100">
                                        <td class="p-2 ltr-data" dir="ltr"><?php echo e($log->created_at?->format('m-d H:i')); ?></td>
                                        <td class="p-2"><?php echo e(\App\Support\His\HisResources::label($log->resource)); ?></td>
                                        <td class="p-2 ltr-data" dir="ltr"><?php echo e($log->received); ?></td>
                                        <td class="p-2 ltr-data" dir="ltr"><?php echo e($log->created); ?></td>
                                        <td class="p-2 ltr-data" dir="ltr"><?php echo e($log->updated); ?></td>
                                        <td class="p-2 ltr-data <?php echo e($log->failed > 0 ? 'font-bold text-red-600' : ''); ?>" dir="ltr"><?php echo e($log->failed); ?></td>
                                        <td class="p-2 ltr-data" dir="ltr"><?php echo e($log->duration_ms); ?>ms</td>
                                    </tr>
                                    <?php if($log->failed > 0 && $log->errors): ?>
                                        <tr>
                                            <td colspan="7" class="px-2 pb-2">
                                                <ul class="space-y-1 text-[11px] text-red-700">
                                                    <?php $__currentLoopData = array_slice($log->errors, 0, 5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <li class="ltr-data" dir="ltr">
                                                            #<?php echo e($error['his_id'] ?? $error['row'] ?? '?'); ?> — <?php echo e($error['error'] ?? ''); ?>

                                                        </li>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\his\index.blade.php ENDPATH**/ ?>