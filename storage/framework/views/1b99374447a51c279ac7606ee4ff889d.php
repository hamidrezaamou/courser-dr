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
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="page-title">لاگ تغییرات</h2>
            <div class="flex flex-wrap items-center gap-2">
                <?php if($canExport): ?>
                    <a href="<?php echo e(route('activity-logs.export', request()->query())); ?>" class="btn-secondary btn-primary--compact">
                        خروجی CSV
                    </a>
                <?php endif; ?>
                <a href="<?php echo e(route('admin.index')); ?>" class="btn-ghost hidden sm:inline-flex">مدیریت کل سایت</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal0f5b24ef0b91ecd95fa5272f30464615 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-dock','data' => ['adminSection' => 'audit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['admin-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('audit')]); ?>
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

            <form method="GET" action="<?php echo e(route('activity-logs.index')); ?>" class="panel grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'نوع رویداد']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'نوع رویداد']); ?>
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
                    <select name="action" class="field-input mt-1">
                        <option value="">همه</option>
                        <option value="created" <?php if($filters['action'] === 'created'): echo 'selected'; endif; ?>>ثبت</option>
                        <option value="updated" <?php if($filters['action'] === 'updated'): echo 'selected'; endif; ?>>ویرایش</option>
                        <option value="status_changed" <?php if($filters['action'] === 'status_changed'): echo 'selected'; endif; ?>>تغییر وضعیت</option>
                        <option value="deleted" <?php if($filters['action'] === 'deleted'): echo 'selected'; endif; ?>>حذف</option>
                        <option value="viewed" <?php if($filters['action'] === 'viewed'): echo 'selected'; endif; ?>>مشاهده پرونده</option>
                        <option value="login" <?php if($filters['action'] === 'login'): echo 'selected'; endif; ?>>ورود</option>
                        <option value="logout" <?php if($filters['action'] === 'logout'): echo 'selected'; endif; ?>>خروج</option>
                        <option value="secure_erased" <?php if($filters['action'] === 'secure_erased'): echo 'selected'; endif; ?>>حذف امن</option>
                        <option value="reminder_sent" <?php if($filters['action'] === 'reminder_sent'): echo 'selected'; endif; ?>>یادآوری</option>
                        <option value="exported" <?php if($filters['action'] === 'exported'): echo 'selected'; endif; ?>>خروجی‌گیری</option>
                    </select>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'کاربر']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'کاربر']); ?>
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
                    <select name="user_id" class="field-input mt-1">
                        <option value="">همه</option>
                        <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($user->id); ?>" <?php if($filters['user_id'] === (string) $user->id): echo 'selected'; endif; ?>><?php echo e($user->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'موضوع']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'موضوع']); ?>
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
                    <select name="subject_type" class="field-input mt-1">
                        <option value="">همه</option>
                        <?php $__currentLoopData = $subjectTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($type); ?>" <?php if($filters['subject_type'] === $type): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.jalali-date-input','data' => ['name' => 'from','value' => $filters['from'],'label' => 'از تاریخ','allowPast' => true,'allowFriday' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('jalali-date-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'from','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['from']),'label' => 'از تاریخ','allow-past' => true,'allow-friday' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a)): ?>
<?php $attributes = $__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a; ?>
<?php unset($__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a)): ?>
<?php $component = $__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a; ?>
<?php unset($__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a); ?>
<?php endif; ?>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.jalali-date-input','data' => ['name' => 'to','value' => $filters['to'],'label' => 'تا تاریخ','allowPast' => true,'allowFriday' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('jalali-date-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'to','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['to']),'label' => 'تا تاریخ','allow-past' => true,'allow-friday' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a)): ?>
<?php $attributes = $__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a; ?>
<?php unset($__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a)): ?>
<?php $component = $__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a; ?>
<?php unset($__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a); ?>
<?php endif; ?>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'جستجو']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'جستجو']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['name' => 'q','class' => 'mt-1 block w-full','value' => $filters['q'],'placeholder' => 'IP، JSON، …']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'q','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['q']),'placeholder' => 'IP، JSON، …']); ?>
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
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3 xl:col-span-6">
                    <button type="submit" class="btn-primary !py-2.5">فیلتر</button>
                    <a href="<?php echo e(route('activity-logs.index')); ?>" class="btn-ghost !py-2.5">پاک کردن</a>
                </div>
            </form>

            <section class="panel overflow-hidden p-0">
                <?php if($logs->isEmpty()): ?>
                    <div class="pp-empty">لاگی با این فیلتر پیدا نشد.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-right text-xs" style="color: var(--muted); background: var(--panel-soft);">
                                    <th class="px-4 py-3 font-semibold">زمان</th>
                                    <th class="px-4 py-3 font-semibold">کاربر</th>
                                    <th class="px-4 py-3 font-semibold">رویداد</th>
                                    <th class="px-4 py-3 font-semibold">موضوع</th>
                                    <th class="px-4 py-3 font-semibold">IP</th>
                                    <th class="px-4 py-3 font-semibold">جزئیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="border-t align-top" style="border-color: var(--line);" x-data="{ open: false }">
                                        <td class="px-4 py-3 font-mono text-xs ltr-data"><?php echo e(jalali($log->created_at, 'Y/m/d H:i')); ?></td>
                                        <td class="px-4 py-3"><?php echo e($log->user?->name ?? 'سیستم'); ?></td>
                                        <td class="px-4 py-3 font-bold"><?php echo e($log->actionLabel()); ?></td>
                                        <td class="px-4 py-3 text-xs" style="color: var(--muted);">
                                            <?php echo e(class_basename($log->subject_type)); ?> #<?php echo e($log->subject_id); ?>

                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs ltr-data" dir="ltr"><?php echo e($log->ip_address ?? '—'); ?></td>
                                        <td class="max-w-[14rem] px-3 py-3 text-xs sm:max-w-xs sm:px-4" style="color: var(--muted);">
                                            <?php if($log->old_values || $log->new_values): ?>
                                                <button type="button" class="btn-ghost !px-2 !py-1 !text-[11px]" @click="open = !open">
                                                    <span x-text="open ? 'بستن' : 'نمایش'"></span>
                                                </button>
                                                <div x-show="open" x-cloak class="mt-2 space-y-1">
                                                    <?php if($log->old_values): ?>
                                                        <div class="break-all">قبل: <span dir="ltr"><?php echo e(json_encode($log->old_values, JSON_UNESCAPED_UNICODE)); ?></span></div>
                                                    <?php endif; ?>
                                                    <?php if($log->new_values): ?>
                                                        <div class="break-all">بعد: <span dir="ltr"><?php echo e(json_encode($log->new_values, JSON_UNESCAPED_UNICODE)); ?></span></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t px-4 py-3" style="border-color: var(--line);">
                        <?php echo e($logs->links()); ?>

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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\activity-logs\index.blade.php ENDPATH**/ ?>