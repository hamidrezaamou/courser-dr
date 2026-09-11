<?php
    $todayUrl = route('clinic.floor', ['date' => $todayJalali, 'kind' => $kindFilter ?? 'all']);
    $liveUrl = route('clinic.floor.live', ['date' => $dateJalali, 'kind' => $kindFilter ?? 'all']);
    $kindFilter = $kindFilter ?? 'all';
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
        <div class="floor-hero">
            <div>
                <h2 class="floor-hero__title">صف مطب</h2>
                <p class="floor-hero__sub">حضور → صف → ارجاع به پزشک → ویزیت → پایان · به‌روزرسانی خودکار</p>
            </div>
            <form method="GET" action="<?php echo e(route('clinic.floor')); ?>" class="floor-hero__date" onchange="this.submit()">
                <?php if (isset($component)) { $__componentOriginal7ffb200c3759605d3e9cc19c9c68f37a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7ffb200c3759605d3e9cc19c9c68f37a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.jalali-date-input','data' => ['name' => 'date','value' => $dateJalali,'label' => '','allowPast' => true,'allowFriday' => true,'class' => '!min-h-[2.2rem] !py-1.5 !text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('jalali-date-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'date','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($dateJalali),'label' => '','allow-past' => true,'allow-friday' => true,'class' => '!min-h-[2.2rem] !py-1.5 !text-sm']); ?>
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
                <input type="hidden" name="kind" value="<?php echo e($kindFilter); ?>">
                <a href="<?php echo e(route('clinic.floor', ['date' => $todayJalali, 'kind' => $kindFilter])); ?>" class="board-chip <?php echo e($dateJalali === $todayJalali ? 'is-active' : ''); ?>">امروز</a>
                <button type="submit" class="board-chip">اعمال</button>
            </form>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="floor-page"
        x-data="clinicFloorLive(<?php echo \Illuminate\Support\Js::from(['url' => $liveUrl, 'version' => $version, 'interval' => 2000])->toHtml() ?>)"
        @floor-changed.window="refresh(true)"
    >
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
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

            <div class="floor-kind-tabs" role="tablist" aria-label="فیلتر نوع نوبت">
                <a href="<?php echo e(route('clinic.floor', ['date' => $dateJalali, 'kind' => 'all'])); ?>"
                   class="floor-kind-tab <?php echo e($kindFilter === 'all' ? 'is-active' : ''); ?>">همه</a>
                <a href="<?php echo e(route('clinic.floor', ['date' => $dateJalali, 'kind' => 'visit'])); ?>"
                   class="floor-kind-tab <?php echo e($kindFilter === 'visit' ? 'is-active' : ''); ?>">ویزیت مطب</a>
                <a href="<?php echo e(route('clinic.floor', ['date' => $dateJalali, 'kind' => 'surgery'])); ?>"
                   class="floor-kind-tab <?php echo e($kindFilter === 'surgery' ? 'is-active' : ''); ?>">عمل بیمارستان</a>
            </div>

            <div class="floor-live-bar" aria-live="polite">
                <span class="floor-live-dot" :class="{ 'is-on': connected, 'is-pulse': refreshing }"></span>
                <span x-text="statusText"></span>
            </div>

            <div x-ref="board" class="floor-live-board space-y-5">
                <?php echo $__env->make('clinic.partials.floor-board', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\clinic\floor.blade.php ENDPATH**/ ?>