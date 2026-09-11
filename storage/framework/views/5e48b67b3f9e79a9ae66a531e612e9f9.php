<?php
    $filterQuery = array_filter([
        'kind' => $kind !== 'surgery' ? $kind : null,
        'hospital_id' => $hospitalId ?: null,
        'per_page' => request('per_page'),
    ], fn ($v) => $v !== null && $v !== '');
    $todayUrl = route('appointments.board', $filterQuery + ['date' => $todayJalali]);
    $tomorrowUrl = route('appointments.board', $filterQuery + ['date' => $tomorrowJalali]);
    $smsOn = config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir';
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
        <div class="board-hero">
            <h2 class="board-hero__title">نوبت‌های فعال</h2>
            <div class="board-stats">
                <div class="board-stat">
                    کل نوبت‌های <?php echo e($dateJalali === $todayJalali ? 'امروز' : 'این روز'); ?>:
                    <strong><?php echo e(number_format($stats['total'])); ?></strong>
                </div>
                <div class="board-stat board-stat--ok">
                    تایید شده:
                    <strong><?php echo e(number_format($stats['confirmed'])); ?></strong>
                </div>
                <div class="board-stat">
                    انجام‌شده:
                    <strong><?php echo e(number_format($stats['done'])); ?></strong>
                </div>
                <div class="board-stat board-stat--warn">
                    عدم حضور:
                    <strong><?php echo e(number_format($stats['no_show'])); ?></strong>
                </div>
                <div class="board-stat board-stat--danger">
                    لغو شده:
                    <strong><?php echo e(number_format($stats['cancelled'])); ?></strong>
                </div>
                <?php if(($approvalEnabled ?? false) && ($stats['pending_approval'] ?? 0) > 0): ?>
                <div class="board-stat board-stat--warn">
                    در انتظار تأیید:
                    <strong><?php echo e(number_format($stats['pending_approval'])); ?></strong>
                </div>
                <?php endif; ?>
            </div>
            <?php if(($approvalEnabled ?? false) && ($globalPendingApproval ?? 0) > 0): ?>
                <a href="<?php echo e(route('modules.approval.index')); ?>" class="btn-secondary !px-3 !py-1.5 !text-xs mt-2">
                    صف تأیید آنلاین (<?php echo e($globalPendingApproval); ?>)
                </a>
            <?php endif; ?>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="board-page" x-data="{
        filtersOpen: false,
        remindersOn: <?php echo \Illuminate\Support\Js::from($boardRemindersEnabled && $remindersEnabled)->toHtml() ?>,
        boardCards: {},
        toggleBoardCard(id) { this.boardCards[id] = !this.boardCards[id]; },
        isBoardCardOpen(id) { return !!this.boardCards[id]; }
    }">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
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

            <section class="board-panel board-panel--filters">
                <div class="board-mobile-bar sm:hidden">
                    <div class="board-mobile-bar__date">
                        <span class="board-mobile-bar__label" dir="ltr"><?php echo e($dateJalali); ?></span>
                        <div class="board-date-quick">
                            <a href="<?php echo e($todayUrl); ?>" class="board-chip <?php echo e($dateJalali === $todayJalali ? 'is-active' : ''); ?>">امروز</a>
                            <a href="<?php echo e($tomorrowUrl); ?>" class="board-chip <?php echo e($dateJalali === $tomorrowJalali ? 'is-active' : ''); ?>">فردا</a>
                        </div>
                    </div>
                    <button type="button" class="board-mobile-bar__filter" @click="filtersOpen = true">فیلترها</button>
                </div>

                <form method="GET" action="<?php echo e(route('appointments.board')); ?>" class="board-filters hidden sm:flex">
                    <?php if(request('per_page')): ?>
                        <input type="hidden" name="per_page" value="<?php echo e(request('per_page')); ?>">
                    <?php endif; ?>

                    <div class="board-filters__date">
                        <span class="board-filters__label">تاریخ شمسی</span>
                        <div class="board-filters__date-row">
                            <?php if (isset($component)) { $__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.jalali-date-input','data' => ['name' => 'date','value' => $dateJalali,'label' => '','allowPast' => true,'allowFriday' => true,'class' => '!min-h-[2.2rem] !py-1.5 !text-sm board-date-field']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('jalali-date-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'date','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($dateJalali),'label' => '','allow-past' => true,'allow-friday' => true,'class' => '!min-h-[2.2rem] !py-1.5 !text-sm board-date-field']); ?>
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
                            <a href="<?php echo e($todayUrl); ?>" class="board-chip <?php echo e($dateJalali === $todayJalali ? 'is-active' : ''); ?>">امروز</a>
                            <a href="<?php echo e($tomorrowUrl); ?>" class="board-chip <?php echo e($dateJalali === $tomorrowJalali ? 'is-active' : ''); ?>">فردا</a>
                        </div>
                    </div>

                    <div class="board-filters__field">
                        <span class="board-filters__label">نوع</span>
                        <select name="kind" class="field-input !min-h-[2.2rem] !py-1.5 text-sm" onchange="this.form.submit()">
                            <option value="surgery" <?php if($kind === 'surgery'): echo 'selected'; endif; ?>>عمل</option>
                            <option value="visit" <?php if($kind === 'visit'): echo 'selected'; endif; ?>>ویزیت</option>
                        </select>
                    </div>
                    <?php if($kind === 'surgery'): ?>
                    <div class="board-filters__field">
                        <span class="board-filters__label">بیمارستان</span>
                        <select name="hospital_id" class="field-input !min-h-[2.2rem] !py-1.5 text-sm" onchange="this.form.submit()">
                            <option value="">بیمارستان</option>
                            <?php $__currentLoopData = $hospitals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospital): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($hospital->id); ?>" <?php if((string) $hospitalId === (string) $hospital->id): echo 'selected'; endif; ?>><?php echo e($hospital->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="board-filters__actions">
                        <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">اعمال</button>
                        <a href="<?php echo e(route('appointments.board')); ?>" class="btn-secondary !py-2 !px-3 !text-sm">حذف</a>
                    </div>
                </form>
            </section>

            <div class="filter-sheet sm:hidden" x-show="filtersOpen" x-cloak @click.self="filtersOpen = false">
                <div class="filter-sheet__panel">
                    <div class="filter-sheet__head">
                        <h3>فیلتر نوبت‌ها</h3>
                        <button type="button" class="tg-tool" @click="filtersOpen = false"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3baa94417da9f5dcb14f5f04da758571 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon-close','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon-close'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $attributes = $__attributesOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $component = $__componentOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__componentOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?></button>
                    </div>
                    <form method="GET" action="<?php echo e(route('appointments.board')); ?>" class="space-y-3">
                        <?php if(request('per_page')): ?>
                            <input type="hidden" name="per_page" value="<?php echo e(request('per_page')); ?>">
                        <?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.jalali-date-input','data' => ['name' => 'date','value' => $dateJalali,'label' => 'تاریخ شمسی','allowPast' => true,'allowFriday' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('jalali-date-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'date','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($dateJalali),'label' => 'تاریخ شمسی','allow-past' => true,'allow-friday' => true]); ?>
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
                        <div class="board-date-quick">
                            <a href="<?php echo e($todayUrl); ?>" class="board-chip <?php echo e($dateJalali === $todayJalali ? 'is-active' : ''); ?>">امروز</a>
                            <a href="<?php echo e($tomorrowUrl); ?>" class="board-chip <?php echo e($dateJalali === $tomorrowJalali ? 'is-active' : ''); ?>">فردا</a>
                        </div>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'نوع']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'نوع']); ?>
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
                            <select name="kind" class="field-input mt-1">
                                <option value="surgery" <?php if($kind === 'surgery'): echo 'selected'; endif; ?>>عمل</option>
                                <option value="visit" <?php if($kind === 'visit'): echo 'selected'; endif; ?>>ویزیت</option>
                            </select>
                        </div>
                        <?php if($kind === 'surgery'): ?>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'بیمارستان']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'بیمارستان']); ?>
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
                            <select name="hospital_id" class="field-input mt-1">
                                <option value="">بیمارستان</option>
                                <?php $__currentLoopData = $hospitals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospital): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($hospital->id); ?>" <?php if((string) $hospitalId === (string) $hospital->id): echo 'selected'; endif; ?>><?php echo e($hospital->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="flex gap-2 pt-1">
                            <button type="submit" class="btn-primary flex-1 !py-2 !text-sm">اعمال</button>
                            <a href="<?php echo e(route('appointments.board')); ?>" class="btn-secondary !py-2 !px-3 !text-sm">حذف</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if($boardRemindersEnabled): ?>
            <section class="board-remind-bar">
                <div class="board-remind-bar__copy">
                    <div class="board-remind-bar__title-row">
                        <h3 class="board-remind-bar__title">یادآوری پیامک</h3>
                        <form method="POST" action="<?php echo e(route('reminders.sms-toggle')); ?>" class="inline-flex" x-ref="reminderToggleForm">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <label class="board-toggle" title="ارسال پیامک یادآوری برای همه روزها">
                                <input type="checkbox"
                                       <?php if($smsRemindersEnabled): echo 'checked'; endif; ?>
                                       @change="$refs.reminderToggleForm.submit()"
                                       class="sr-only">
                                <span class="board-toggle__track"></span>
                            </label>
                        </form>
                    </div>
                    <p class="board-remind-bar__hint">
                        <?php if($smsRemindersEnabled): ?>
                            ارسال پیامک یادآوری برای <strong>همه روزها</strong> فعال است.
                        <?php else: ?>
                            <span class="board-remind-bar__state">ارسال پیامک یادآوری برای همه روزها متوقف شده است.</span>
                        <?php endif; ?>
                        <?php if($smsOn): ?>
                            <span class="board-remind-bar__state is-on">درایور SMS.ir فعال است.</span>
                        <?php elseif(config('reminders.sms.enabled') && config('reminders.sms.driver') === 'log'): ?>
                            <span class="board-remind-bar__state is-log">حالت لاگ — ارسال واقعی خاموش.</span>
                        <?php endif; ?>
                        <?php if($remindersEnabled): ?>
                            <span class="board-remind-bar__state">زمان خودکار: <?php echo e(config('reminders.send_time', '18:00')); ?></span>
                        <?php else: ?>
                            <span class="board-remind-bar__state">یادآوری خودکار در تنظیمات غیرفعال است.</span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if($remindersEnabled): ?>
                <form method="POST" action="<?php echo e(route('reminders.send')); ?>" x-show="remindersOn" x-cloak>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="date" value="<?php echo e($dateJalali); ?>">
                    <button type="submit" class="btn-primary board-remind-bar__btn"
                            onclick="return confirm('یادآوری برای تاریخ <?php echo e($dateJalali); ?> ارسال شود؟')">
                        ارسال یادآوری دستی
                    </button>
                </form>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            
            <div class="board-split" :class="{ 'board-split--solo': !remindersOn || !<?php echo \Illuminate\Support\Js::from($boardRemindersEnabled)->toHtml() ?> }">
                <section class="board-panel board-col board-col--schedule">
                    <div class="board-panel__head">
                        <h3 class="board-panel__title">
                            برنامه
                            <span class="board-panel__date" dir="ltr"><?php echo e($dateJalali); ?></span>
                        </h3>
                        <span class="board-count"><?php echo e(number_format($rows->total())); ?></span>
                    </div>

                    <?php if($rows->isEmpty()): ?>
                        <p class="board-empty">برای این فیلتر نوبتی پیدا نشد.</p>
                    <?php else: ?>
                        <div class="board-schedule-list">
                            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php echo $__env->make('appointments.partials.board-card', ['row' => $row, 'turn' => $loop->iteration + (($rows->currentPage() - 1) * $rows->perPage())], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php if (isset($component)) { $__componentOriginale24860a85af1806ba7250b6af72c4633 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale24860a85af1806ba7250b6af72c4633 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.list-pager','data' => ['paginator' => $rows]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('list-pager'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rows)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale24860a85af1806ba7250b6af72c4633)): ?>
<?php $attributes = $__attributesOriginale24860a85af1806ba7250b6af72c4633; ?>
<?php unset($__attributesOriginale24860a85af1806ba7250b6af72c4633); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale24860a85af1806ba7250b6af72c4633)): ?>
<?php $component = $__componentOriginale24860a85af1806ba7250b6af72c4633; ?>
<?php unset($__componentOriginale24860a85af1806ba7250b6af72c4633); ?>
<?php endif; ?>
                    <?php endif; ?>
                </section>

                <?php if($boardRemindersEnabled): ?>
                <section class="board-panel board-col board-col--remind" x-show="remindersOn" x-cloak>
                    <div class="board-panel__head">
                        <h3 class="board-panel__title">مدیریت یادآوری‌ها</h3>
                        <span class="board-count"><?php echo e($reminderItems->count()); ?></span>
                    </div>
                    <?php if($reminderItems->isEmpty()): ?>
                        <p class="board-empty">موردی برای یادآوری در این روز نیست.</p>
                    <?php else: ?>
                        <div class="board-remind-list">
                            <?php $__currentLoopData = $reminderItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $remId = 'rem-'.$loop->index; ?>
                                <article class="board-remind-card" :class="{ 'is-open': isBoardCardOpen(<?php echo \Illuminate\Support\Js::from($remId)->toHtml() ?>) }">
                                    <div class="board-remind-card__bar" role="button" tabindex="0"
                                         @click="toggleBoardCard(<?php echo \Illuminate\Support\Js::from($remId)->toHtml() ?>)"
                                         @keydown.enter.prevent="toggleBoardCard(<?php echo \Illuminate\Support\Js::from($remId)->toHtml() ?>)"
                                         @keydown.space.prevent="toggleBoardCard(<?php echo \Illuminate\Support\Js::from($remId)->toHtml() ?>)"
                                         :aria-expanded="isBoardCardOpen(<?php echo \Illuminate\Support\Js::from($remId)->toHtml() ?>) ? 'true' : 'false'">
                                        <div class="board-remind-card__bar-main min-w-0">
                                            <span role="button"
                                                  tabindex="0"
                                                  class="board-remind-card__name board-card__name--toolbox"
                                                  data-toolbox-b64="<?php echo e($rem->toolbox_b64 ?? ''); ?>"
                                                  @click.stop>
                                                <?php echo e($rem->name); ?>

                                                <span class="board-remind-card__time" dir="ltr">(<?php echo e($rem->time); ?>)</span>
                                            </span>
                                            <span class="board-remind-card__note"><?php echo e($rem->label); ?><?php echo e($rem->kind ? ' · '.$rem->kind : ''); ?></span>
                                        </div>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="board-remind-card__chevron" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                        </svg>
                                    </div>
                                    <div class="board-remind-card__drawer" x-show="isBoardCardOpen(<?php echo \Illuminate\Support\Js::from($remId)->toHtml() ?>)" x-cloak>
                                        <p class="board-remind-card__meta ltr-data">
                                            <span dir="ltr"><?php echo e($rem->mobile ?: '—'); ?></span>
                                        </p>
                                        <div class="board-remind-card__actions">
                                            <?php if (isset($component)) { $__componentOriginaled60e996035c2b38ad3a5ffc286154b1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaled60e996035c2b38ad3a5ffc286154b1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.answer-launcher','data' => ['mobile' => (string) ($rem->mobile ?? ''),'patientName' => (string) ($rem->name ?? ''),'label' => $rem->label,'variant' => 'soft']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('answer-launcher'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mobile' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((string) ($rem->mobile ?? '')),'patient-name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((string) ($rem->name ?? '')),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rem->label),'variant' => 'soft']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaled60e996035c2b38ad3a5ffc286154b1)): ?>
<?php $attributes = $__attributesOriginaled60e996035c2b38ad3a5ffc286154b1; ?>
<?php unset($__attributesOriginaled60e996035c2b38ad3a5ffc286154b1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaled60e996035c2b38ad3a5ffc286154b1)): ?>
<?php $component = $__componentOriginaled60e996035c2b38ad3a5ffc286154b1; ?>
<?php unset($__componentOriginaled60e996035c2b38ad3a5ffc286154b1); ?>
<?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (isset($component)) { $__componentOriginal613f849e7314f037f8e781fcd5ad8d28 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal613f849e7314f037f8e781fcd5ad8d28 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.row-toolbox-modal','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('row-toolbox-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal613f849e7314f037f8e781fcd5ad8d28)): ?>
<?php $attributes = $__attributesOriginal613f849e7314f037f8e781fcd5ad8d28; ?>
<?php unset($__attributesOriginal613f849e7314f037f8e781fcd5ad8d28); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal613f849e7314f037f8e781fcd5ad8d28)): ?>
<?php $component = $__componentOriginal613f849e7314f037f8e781fcd5ad8d28; ?>
<?php unset($__componentOriginal613f849e7314f037f8e781fcd5ad8d28); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal6f8f92d3e0a08cbfb040316ff7e2f577 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6f8f92d3e0a08cbfb040316ff7e2f577 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.answer-panel','data' => ['mobile' => '','patientName' => '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('answer-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mobile' => '','patient-name' => '']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6f8f92d3e0a08cbfb040316ff7e2f577)): ?>
<?php $attributes = $__attributesOriginal6f8f92d3e0a08cbfb040316ff7e2f577; ?>
<?php unset($__attributesOriginal6f8f92d3e0a08cbfb040316ff7e2f577); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6f8f92d3e0a08cbfb040316ff7e2f577)): ?>
<?php $component = $__componentOriginal6f8f92d3e0a08cbfb040316ff7e2f577; ?>
<?php unset($__componentOriginal6f8f92d3e0a08cbfb040316ff7e2f577); ?>
<?php endif; ?>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\appointments\board.blade.php ENDPATH**/ ?>