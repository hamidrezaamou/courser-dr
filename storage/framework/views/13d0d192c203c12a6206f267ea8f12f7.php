<?php
    $emergencyOnly = $emergencyOnly ?? false;
    $sort = $sort ?? 'date';
    $dir = $dir ?? 'desc';
    $reportNotes = $reportNotes ?? collect();

    $sortUrl = function (string $column) use ($sort, $dir) {
        $nextDir = ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';
        if ($sort !== $column) {
            $nextDir = in_array($column, ['date', 'id'], true) ? 'desc' : 'asc';
        }

        return route('reports.index', array_merge(request()->except(['page']), [
            'sort' => $column,
            'dir' => $nextDir,
        ]));
    };

    $sortClass = function (string $column) use ($sort, $dir) {
        if ($sort !== $column) {
            return 'report-th-sort';
        }

        return 'report-th-sort is-active is-'.$dir;
    };
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
        <div class="flex flex-wrap items-center justify-between gap-2 report-no-print">
            <h2 class="page-title">گزارش نوبت‌ها</h2>
            <div class="flex flex-wrap gap-1.5 sm:gap-2">
                <?php if($canExport ?? false): ?>
                    <a href="<?php echo e(route('reports.export', request()->query())); ?>" class="btn-secondary !px-3 !py-1.5 !text-xs">خروجی CSV</a>
                <?php endif; ?>
                <a href="<?php echo e(route('prints.index')); ?>" class="btn-secondary !px-3 !py-1.5 !text-xs">پرینت‌ها</a>
                <button type="button" class="btn-primary !px-3 !py-1.5 !text-xs" onclick="window.print()">چاپ</button>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="py-4 sm:py-8 report-page"
        x-data="reportPage({
            storeUrl: <?php echo \Illuminate\Support\Js::from(route('reports.notes.store'))->toHtml() ?>,
            showUrl: <?php echo \Illuminate\Support\Js::from(route('reports.notes.show'))->toHtml() ?>,
            csrf: <?php echo \Illuminate\Support\Js::from(csrf_token())->toHtml() ?>,
            clinic: <?php echo \Illuminate\Support\Js::from(config('app.name', 'مطب'))->toHtml() ?>,
            today: <?php echo \Illuminate\Support\Js::from(\App\Support\Jalali::format(now(), 'Y/m/d'))->toHtml() ?>,
        })"
        @open-report-note.window="openNote($event.detail)"
    >
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <?php if(auth()->user()?->canManageSettings()): ?>
                <div class="report-no-print">
                    <?php if (isset($component)) { $__componentOriginal0f5b24ef0b91ecd95fa5272f30464615 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-dock','data' => ['adminSection' => 'reports']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['admin-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('reports')]); ?>
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
                </div>
            <?php endif; ?>

            <div class="report-no-print space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <?php if($emergencyOnly): ?>
                        <a href="<?php echo e(route('reports.index', request()->except(['emergency', 'page']))); ?>" class="btn-secondary !px-3 !py-1.5 !text-xs is-emergency-active">
                            اورژانسی · فعال
                        </a>
                    <?php else: ?>
                        <a href="<?php echo e(route('reports.index', array_merge(request()->except(['page']), ['emergency' => 1, 'kind' => 'surgery']))); ?>" class="btn-emergency-filter">
                            اورژانسی
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn-secondary w-full !py-2 sm:hidden" @click="filtersOpen = true">فیلترها</button>
                </div>

                <form method="GET" action="<?php echo e(route('reports.index')); ?>" id="report-filter-form" class="panel calendar-host filter-grid filter-grid--reports p-3 sm:p-4 report-filter-compact hidden sm:grid">
                    <?php if(request('per_page')): ?>
                        <input type="hidden" name="per_page" value="<?php echo e(request('per_page')); ?>">
                    <?php endif; ?>
                    <?php if($sort ?? false): ?>
                        <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
                    <?php endif; ?>
                    <?php if($dir ?? false): ?>
                        <input type="hidden" name="dir" value="<?php echo e($dir); ?>">
                    <?php endif; ?>
                    <?php if($emergencyOnly): ?>
                        <input type="hidden" name="emergency" value="1">
                    <?php endif; ?>
                    <div>
                        <?php if (isset($component)) { $__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.jalali-date-input','data' => ['name' => 'from','value' => $fromJalali,'label' => 'از تاریخ','allowPast' => true,'allowFriday' => true,'class' => '!min-h-[2.2rem] !py-1.5 text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('jalali-date-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'from','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fromJalali),'label' => 'از تاریخ','allow-past' => true,'allow-friday' => true,'class' => '!min-h-[2.2rem] !py-1.5 text-sm']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.jalali-date-input','data' => ['name' => 'to','value' => $toJalali,'label' => 'تا تاریخ','allowPast' => true,'allowFriday' => true,'class' => '!min-h-[2.2rem] !py-1.5 text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('jalali-date-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'to','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toJalali),'label' => 'تا تاریخ','allow-past' => true,'allow-friday' => true,'class' => '!min-h-[2.2rem] !py-1.5 text-sm']); ?>
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
                        <select name="kind" class="field-input mt-1 !min-h-[2.2rem] !py-1.5 text-sm" onchange="this.form.submit()" <?php if($emergencyOnly): echo 'disabled'; endif; ?>>
                            <option value="all" <?php if($kind === 'all'): echo 'selected'; endif; ?>>همه</option>
                            <option value="visit" <?php if($kind === 'visit'): echo 'selected'; endif; ?>>ویزیت</option>
                            <option value="surgery" <?php if($kind === 'surgery'): echo 'selected'; endif; ?>>عمل</option>
                        </select>
                    </div>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'وضعیت']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'وضعیت']); ?>
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
                        <select name="status" class="field-input mt-1 !min-h-[2.2rem] !py-1.5 text-sm" onchange="this.form.submit()">
                            <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if($status === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
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
                        <select name="hospital_id" class="field-input mt-1 !min-h-[2.2rem] !py-1.5 text-sm" onchange="this.form.submit()">
                            <option value="">همه مراکز</option>
                            <?php $__currentLoopData = $hospitals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospital): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($hospital->id); ?>" <?php if((string) $hospitalId === (string) $hospital->id): echo 'selected'; endif; ?>><?php echo e($hospital->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn-secondary flex-1 !py-1.5 !text-xs sm:!text-sm">اعمال</button>
                        <a href="<?php echo e(route('reports.index')); ?>" class="btn-ghost shrink-0 !py-1.5 !text-xs sm:!text-sm">حذف</a>
                    </div>
                </form>

                <div class="filter-sheet sm:hidden" x-show="filtersOpen" x-cloak @click.self="filtersOpen = false">
                    <div class="filter-sheet__panel">
                        <div class="filter-sheet__head">
                            <h3>فیلتر گزارش</h3>
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
                        <form method="GET" action="<?php echo e(route('reports.index')); ?>" class="space-y-3 report-filter-compact">
                            <?php if(request('per_page')): ?>
                                <input type="hidden" name="per_page" value="<?php echo e(request('per_page')); ?>">
                            <?php endif; ?>
                            <?php if($q ?? false): ?>
                                <input type="hidden" name="q" value="<?php echo e($q); ?>">
                            <?php endif; ?>
                            <?php if($emergencyOnly): ?>
                                <input type="hidden" name="emergency" value="1">
                            <?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.jalali-date-input','data' => ['name' => 'from','value' => $fromJalali,'label' => 'از تاریخ','allowPast' => true,'allowFriday' => true,'class' => '!min-h-[2.25rem] !py-1.5 text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('jalali-date-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'from','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fromJalali),'label' => 'از تاریخ','allow-past' => true,'allow-friday' => true,'class' => '!min-h-[2.25rem] !py-1.5 text-sm']); ?>
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
                            <?php if (isset($component)) { $__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.jalali-date-input','data' => ['name' => 'to','value' => $toJalali,'label' => 'تا تاریخ','allowPast' => true,'allowFriday' => true,'class' => '!min-h-[2.25rem] !py-1.5 text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('jalali-date-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'to','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toJalali),'label' => 'تا تاریخ','allow-past' => true,'allow-friday' => true,'class' => '!min-h-[2.25rem] !py-1.5 text-sm']); ?>
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
                                <select name="kind" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                    <option value="all" <?php if($kind === 'all'): echo 'selected'; endif; ?>>همه</option>
                                    <option value="visit" <?php if($kind === 'visit'): echo 'selected'; endif; ?>>ویزیت</option>
                                    <option value="surgery" <?php if($kind === 'surgery'): echo 'selected'; endif; ?>>عمل</option>
                                </select>
                            </div>
                            <div>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'وضعیت']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'وضعیت']); ?>
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
                                <select name="status" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                    <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value); ?>" <?php if($status === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
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
                                <select name="hospital_id" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                    <option value="">همه مراکز</option>
                                    <?php $__currentLoopData = $hospitals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospital): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($hospital->id); ?>" <?php if((string) $hospitalId === (string) $hospital->id): echo 'selected'; endif; ?>><?php echo e($hospital->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="flex gap-2 pt-1">
                                <button type="submit" class="btn-primary flex-1 !py-2 !text-xs">اعمال فیلتر</button>
                                <a href="<?php echo e(route('reports.index')); ?>" class="btn-ghost !py-2 !text-xs">حذف</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="report-print-header">
                <h1>گزارش نوبت‌ها · <?php echo e($statusLabels[$status] ?? $status); ?><?php if($emergencyOnly): ?> · اورژانسی<?php endif; ?></h1>
                <?php if($hasDateFilter): ?>
                    <div class="subtitle" dir="ltr"><?php echo e($fromJalali); ?> — <?php echo e($toJalali); ?></div>
                <?php else: ?>
                    <div class="subtitle">همه تاریخ‌ها</div>
                <?php endif; ?>
                <div class="print-date">تاریخ چاپ: <?php echo e(jalali(now(), 'Y/m/d H:i')); ?> · جمع <?php echo e($rows->count()); ?> مورد</div>
            </div>

            <div class="report-summary report-no-print">
                <div class="report-summary__card is-visit">
                    <strong><?php echo e($summary['visit']); ?></strong>
                    <span>ویزیت</span>
                </div>
                <div class="report-summary__card is-surgery">
                    <strong><?php echo e($summary['surgery']); ?></strong>
                    <span>عمل</span>
                </div>
                <div class="report-summary__card is-total">
                    <strong><?php echo e($rows->count()); ?></strong>
                    <span>جمع لیست</span>
                </div>
            </div>

            <?php if($hospitalStats->isNotEmpty()): ?>
                <section class="report-hosp-stats report-no-print panel overflow-hidden p-0">
                    <details>
                        <summary class="border-b px-4 py-3 text-sm font-bold cursor-pointer select-none" style="border-color: var(--line); color: var(--ink);">
                            آمار عمل به تفکیک بیمارستان (<?php echo e($hospitalStats->count()); ?> مرکز)
                        </summary>
                        <div class="overflow-x-auto">
                            <table class="report-mini-table">
                                <thead>
                                    <tr>
                                        <th>بیمارستان</th>
                                        <th>ثبت</th>
                                        <th>تأیید</th>
                                        <th>انجام</th>
                                        <th>لغو</th>
                                        <th>جمع</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $hospitalStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="font-bold"><?php echo e($stat['hospital']->name); ?></td>
                                            <td><?php echo e($stat['counts']['scheduled']); ?></td>
                                            <td><?php echo e($stat['counts']['confirmed']); ?></td>
                                            <td><?php echo e($stat['counts']['done']); ?></td>
                                            <td><?php echo e($stat['counts']['cancelled']); ?></td>
                                            <td class="font-extrabold"><?php echo e($stat['total']); ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </section>
            <?php endif; ?>

            <section class="report-table-panel">
                <div class="report-table-toolbar report-no-print">
                    <form method="GET" action="<?php echo e(route('reports.index')); ?>" class="flex min-w-0 flex-1 items-center gap-2">
                        <?php $__currentLoopData = request()->except(['q', 'page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if(is_array($value)): ?>
                                <?php $__currentLoopData = $value; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nested): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <input type="hidden" name="<?php echo e($key); ?>[]" value="<?php echo e($nested); ?>">
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php elseif($value !== null && $value !== ''): ?>
                                <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <input
                            type="search"
                            name="q"
                            value="<?php echo e($q); ?>"
                            class="field-input !py-2 text-sm"
                            placeholder="جستجو در نام، کد ملی، موبایل..."
                            autocomplete="off"
                        >
                        <button type="submit" class="btn-secondary !py-2 !px-3 !text-xs shrink-0">جستجو</button>
                    </form>
                    <span><?php echo e(number_format($rows->total())); ?> مورد</span>
                </div>

                <?php if($rows->isEmpty()): ?>
                    <div class="pp-empty">موردی یافت نشد.</div>
                <?php else: ?>
                    <div class="report-table-wrap">
                        <table class="report-table report-table--compact">
                            <thead>
                                <tr>
                                    <th class="col-num">#</th>
                                    <th class="col-id">
                                        <a class="<?php echo e($sortClass('id')); ?>" href="<?php echo e($sortUrl('id')); ?>">ID</a>
                                    </th>
                                    <th>
                                        <a class="<?php echo e($sortClass('type')); ?>" href="<?php echo e($sortUrl('type')); ?>">نوع</a>
                                    </th>
                                    <th>
                                        <a class="<?php echo e($sortClass('patient')); ?>" href="<?php echo e($sortUrl('patient')); ?>">بیمار</a>
                                    </th>
                                    <th>
                                        <a class="<?php echo e($sortClass('national_code')); ?>" href="<?php echo e($sortUrl('national_code')); ?>">کد ملی</a>
                                    </th>
                                    <th>
                                        <a class="<?php echo e($sortClass('center')); ?>" href="<?php echo e($sortUrl('center')); ?>">مرکز / جزئیات</a>
                                    </th>
                                    <th>
                                        <a class="<?php echo e($sortClass('date')); ?>" href="<?php echo e($sortUrl('date')); ?>">تاریخ</a>
                                    </th>
                                    <th>
                                        <a class="<?php echo e($sortClass('time')); ?>" href="<?php echo e($sortUrl('time')); ?>">نوبت/ساعت</a>
                                    </th>
                                    <th>
                                        <a class="<?php echo e($sortClass('mobile')); ?>" href="<?php echo e($sortUrl('mobile')); ?>">تلفن</a>
                                    </th>
                                    <th>
                                        <a class="<?php echo e($sortClass('status')); ?>" href="<?php echo e($sortUrl('status')); ?>">وضعیت</a>
                                    </th>
                                    <th class="col-note report-no-print">یادداشت</th>
                                    <th class="col-actions report-no-print">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $item = $row['item'];
                                        $time = $item->scheduled_time ? \App\Support\SlotLabel::display((string) $item->scheduled_time) : '—';
                                        $dateJalali = jalali($item->scheduled_date, 'Y/m/d');
                                        $rowStatus = $item->status;
                                        $statusLabel = $statusLabels[$rowStatus] ?? \App\Support\BookingStatus::label($rowStatus);
                                        $noteKey = $row['type'].':'.$item->id;
                                        $note = $reportNotes->get($noteKey);
                                        if ($row['type'] === 'surgery') {
                                            $detail = trim(($item->surgery_type ?: 'عمل').($item->eye_side ? ' · '.$item->eye_side : ''));
                                            $center = $row['hospital'] ?: '—';
                                            $meta = $detail.' · '.$dateJalali.' '.$time.($row['hospital'] ? ' · '.$row['hospital'] : '');
                                            $smsBody = \App\Support\AppointmentSms::forItem($item, 'surgery');
                                            $editUrl = route('surgery-appointments.edit', $item);
                                            $printUrl = route('surgery-appointments.prints', $item);
                                        } else {
                                            $detail = trim(($item->visit_type ?: 'ویزیت').($item->reason ? ' · '.$item->reason : ''));
                                            $center = 'ویزیت مطب';
                                            $meta = $detail.' · '.$dateJalali.' '.$time;
                                            $smsBody = \App\Support\AppointmentSms::forItem($item, 'visit');
                                            $editUrl = route('appointments.edit', $item);
                                            $printUrl = null;
                                        }
                                    ?>
                                    <tr>
                                        <td class="col-num"><?php echo e($rows->firstItem() + $index); ?></td>
                                        <td class="col-id ltr-data"><?php echo e($item->id); ?></td>
                                        <td>
                                            <span class="report-kind <?php echo e($row['type'] === 'surgery' ? 'is-surgery' : 'is-visit'); ?>">
                                                <?php echo e($row['type'] === 'surgery' ? 'عمل' : 'ویزیت'); ?>

                                            </span>
                                            <?php if($row['type'] === 'surgery' && $item->is_emergency): ?>
                                                <span class="report-emergency-badge">اورژانس</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-name">
                                            <?php $reportToolboxB64 = \App\Support\ToolboxPayload::encode(\App\Support\ToolboxPayload::fromBoardRow($row)); ?>
                                            <button type="button"
                                                    class="report-name-toolbox"
                                                    data-toolbox-b64="<?php echo e($reportToolboxB64); ?>"><?php echo e($item->patient_name); ?></button>
                                        </td>
                                        <td class="mono ltr-data"><?php echo e($item->national_code); ?></td>
                                        <td>
                                            <div class="col-detail-main"><?php echo e($center); ?></div>
                                            <div class="col-detail-sub"><?php echo e($detail); ?></div>
                                        </td>
                                        <td class="mono ltr-data"><?php echo e($dateJalali); ?></td>
                                        <td class="mono strong-slot ltr-data"><?php echo e($time); ?></td>
                                        <td class="mono ltr-data"><?php echo e($item->mobile); ?></td>
                                        <td>
                                            <span class="report-status is-<?php echo e($rowStatus); ?>"><?php echo e($statusLabel); ?></span>
                                        </td>
                                        <td class="col-note report-no-print">
                                            <?php if($note): ?>
                                                <button
                                                    type="button"
                                                    class="report-note-icon"
                                                    title="مشاهده یادداشت"
                                                    @click="openNote({
                                                        subjectType: <?php echo \Illuminate\Support\Js::from($row['type'])->toHtml() ?>,
                                                        subjectId: <?php echo e($item->id); ?>,
                                                        patientId: <?php echo e((int) $item->patient_id); ?>,
                                                        patientName: <?php echo \Illuminate\Support\Js::from($item->patient_name)->toHtml() ?>,
                                                        mobile: <?php echo \Illuminate\Support\Js::from($item->mobile)->toHtml() ?>,
                                                        nationalCode: <?php echo \Illuminate\Support\Js::from($item->national_code)->toHtml() ?>,
                                                        meta: <?php echo \Illuminate\Support\Js::from($meta)->toHtml() ?>,
                                                        date: <?php echo \Illuminate\Support\Js::from($dateJalali)->toHtml() ?>,
                                                        body: <?php echo \Illuminate\Support\Js::from($note->body)->toHtml() ?>,
                                                        readOnly: false,
                                                    })"
                                                >
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                                    </svg>
                                                </button>
                                            <?php else: ?>
                                                <span class="report-note-empty">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-actions report-no-print">
                                            <?php
                                                $reportTypeIds = $row['type'] === 'surgery'
                                                    ? \App\Support\SurgeryChecklist::resolveTypeIds($item)
                                                    : ['type_id' => null, 'subtype_id' => null];
                                                $reportHasChecklist = $row['type'] === 'surgery' && \App\Support\SurgeryChecklist::isAvailable();
                                            ?>
                                            <?php if (isset($component)) { $__componentOriginala024c575d9bc5bd40fe3d84386fcd6a3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala024c575d9bc5bd40fe3d84386fcd6a3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.row-toolbox','data' => ['name' => $item->patient_name,'mobile' => $item->mobile,'nationalCode' => $item->national_code,'meta' => $meta,'patientUrl' => route('patients.show', $item->patient_id),'editUrl' => $editUrl,'printUrl' => $printUrl,'smsBody' => $smsBody,'sheetMode' => 'report','subjectType' => $row['type'],'subjectId' => $item->id,'patientId' => $item->patient_id,'dateLabel' => $dateJalali,'surgeryAppointmentId' => $row['type'] === 'surgery' ? $item->id : null,'surgeryTypeId' => $reportTypeIds['type_id'] ?? null,'surgerySubtypeId' => $reportTypeIds['subtype_id'] ?? null,'hasSurgeryChecklist' => $reportHasChecklist]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('row-toolbox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->patient_name),'mobile' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->mobile),'national-code' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->national_code),'meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'patient-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('patients.show', $item->patient_id)),'edit-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editUrl),'print-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($printUrl),'sms-body' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($smsBody),'sheet-mode' => 'report','subject-type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row['type']),'subject-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->id),'patient-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->patient_id),'date-label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($dateJalali),'surgery-appointment-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row['type'] === 'surgery' ? $item->id : null),'surgery-type-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reportTypeIds['type_id'] ?? null),'surgery-subtype-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reportTypeIds['subtype_id'] ?? null),'has-surgery-checklist' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reportHasChecklist)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala024c575d9bc5bd40fe3d84386fcd6a3)): ?>
<?php $attributes = $__attributesOriginala024c575d9bc5bd40fe3d84386fcd6a3; ?>
<?php unset($__attributesOriginala024c575d9bc5bd40fe3d84386fcd6a3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala024c575d9bc5bd40fe3d84386fcd6a3)): ?>
<?php $component = $__componentOriginala024c575d9bc5bd40fe3d84386fcd6a3; ?>
<?php unset($__componentOriginala024c575d9bc5bd40fe3d84386fcd6a3); ?>
<?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (isset($component)) { $__componentOriginale24860a85af1806ba7250b6af72c4633 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale24860a85af1806ba7250b6af72c4633 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.list-pager','data' => ['paginator' => $rows,'class' => 'report-no-print']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('list-pager'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rows),'class' => 'report-no-print']); ?>
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
        </div>

        <div class="rn-overlay" x-show="open" x-cloak @click.self="closeNote()" @keydown.escape.window="if (open) closeNote()">
            <div class="rn-sheet" role="dialog" aria-modal="true">
                <header class="rn-head">
                    <div>
                        <h3>یادداشت گزارش</h3>
                        <p x-text="patientName || '—'"></p>
                    </div>
                    <button type="button" class="rn-close" @click="closeNote()" aria-label="بستن">×</button>
                </header>
                <div class="rn-meta" x-show="meta" x-text="meta"></div>
                <div class="rn-tags">
                    <template x-for="tag in tags" :key="tag.token">
                        <button type="button" class="rn-tag" @click="insertTag(tag.token)" x-text="tag.label"></button>
                    </template>
                </div>
                <textarea class="rn-body field-input" rows="8" x-model="body" x-ref="body" placeholder="متن یادداشت گزارش..."></textarea>
                <div class="rn-preview" x-show="body" x-cloak>
                    <span>پیش‌نمایش</span>
                    <p x-text="preview()"></p>
                </div>
                <div class="rn-actions">
                    <button type="button" class="btn-primary" :disabled="saving" @click="saveNote()" x-text="saving ? 'در حال ذخیره...' : 'ثبت یادداشت'"></button>
                    <button type="button" class="btn-secondary" @click="closeNote()">بستن</button>
                </div>
                <p class="rn-msg" x-show="message" x-text="message" x-cloak></p>
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

    <script>
        function reportPage(cfg) {
            return {
                filtersOpen: false,
                open: false,
                saving: false,
                message: '',
                subjectType: '',
                subjectId: null,
                patientId: null,
                patientName: '',
                mobile: '',
                nationalCode: '',
                meta: '',
                dateLabel: '',
                body: '',
                tags: [
                    { token: '{نام}', label: 'نام بیمار' },
                    { token: '{موبایل}', label: 'موبایل' },
                    { token: '{کدملی}', label: 'کد ملی' },
                    { token: '{تاریخ}', label: 'تاریخ نوبت' },
                    { token: '{امروز}', label: 'تاریخ امروز' },
                    { token: '{مطب}', label: 'نام مطب' },
                ],
                openNote(detail) {
                    detail = detail || {};
                    this.subjectType = detail.subjectType || '';
                    this.subjectId = detail.subjectId || null;
                    this.patientId = detail.patientId || null;
                    this.patientName = detail.patientName || '';
                    this.mobile = detail.mobile || '';
                    this.nationalCode = detail.nationalCode || '';
                    this.meta = detail.meta || '';
                    this.dateLabel = detail.date || '';
                    this.body = detail.body || '';
                    this.message = '';
                    this.open = true;
                    if (!detail.body && this.subjectType && this.subjectId) {
                        this.fetchNote();
                    }
                    this.$nextTick(() => {
                        if (this.$refs.body) this.$refs.body.focus();
                    });
                },
                closeNote() {
                    this.open = false;
                },
                preview() {
                    const map = {
                        'نام': this.patientName,
                        'موبایل': this.mobile,
                        'کدملی': this.nationalCode,
                        'تاریخ': this.dateLabel,
                        'امروز': cfg.today,
                        'مطب': cfg.clinic,
                    };
                    return String(this.body || '').replace(/\{([^}]+)\}/g, (match, key) => {
                        const value = map[String(key).trim()];
                        return value ? String(value) : match;
                    });
                },
                insertTag(token) {
                    const el = this.$refs.body;
                    const current = this.body || '';
                    if (!el) {
                        this.body = current + token;
                        return;
                    }
                    const start = el.selectionStart ?? current.length;
                    const end = el.selectionEnd ?? current.length;
                    this.body = current.slice(0, start) + token + current.slice(end);
                    this.$nextTick(() => {
                        el.focus();
                        const pos = start + token.length;
                        el.setSelectionRange(pos, pos);
                    });
                },
                async fetchNote() {
                    try {
                        const url = cfg.showUrl
                            + '?subject_type=' + encodeURIComponent(this.subjectType)
                            + '&subject_id=' + encodeURIComponent(this.subjectId);
                        const res = await fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const data = await res.json();
                        if (data.note && data.note.body) {
                            this.body = data.note.body;
                        }
                    } catch (e) {}
                },
                async saveNote() {
                    if (!this.subjectType || !this.subjectId) {
                        this.message = 'ردیف گزارش مشخص نیست.';
                        return;
                    }
                    if (!String(this.body || '').trim()) {
                        this.message = 'متن یادداشت را بنویسید.';
                        return;
                    }
                    this.saving = true;
                    this.message = '';
                    try {
                        const res = await fetch(cfg.storeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': cfg.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                subject_type: this.subjectType,
                                subject_id: this.subjectId,
                                patient_id: this.patientId,
                                body: this.preview(),
                            }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.message || 'ذخیره ناموفق بود');
                        this.message = 'یادداشت ثبت شد.';
                        setTimeout(() => window.location.reload(), 450);
                    } catch (e) {
                        this.message = e.message || 'خطا در ذخیره یادداشت';
                    } finally {
                        this.saving = false;
                    }
                },
            };
        }
    </script>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\reports\index.blade.php ENDPATH**/ ?>