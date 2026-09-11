<?php if($isDoctor && $current): ?>
    <?php
        $cur = $current['item'];
        $curType = $current['type'];
        $curTime = $cur->scheduled_time ? \App\Support\SlotLabel::display((string) $cur->scheduled_time) : '—';
    ?>
    <section class="floor-doctor">
        <div class="floor-doctor__label">
            <?php echo e($cur->status === \App\Support\BookingStatus::IN_CONSULT ? 'بیمار فعلی نزد شما' : 'نفر بعدی برای ویزیت'); ?>

        </div>
        <div class="floor-doctor__main">
            <div>
                <h3 class="floor-doctor__name"><?php echo e($cur->patient_name); ?></h3>
                <p class="floor-doctor__meta">
                    <span class="floor-card__type <?php echo e($curType === 'surgery' ? 'floor-card__type--surgery' : 'floor-card__type--visit'); ?>">
                        <?php echo e($curType === 'surgery' ? 'عمل' : 'ویزیت'); ?>

                    </span>
                    · <?php echo e($curType === 'surgery' ? ($cur->surgery_type ?: 'عمل') : ($cur->visit_type ?: 'ویزیت')); ?>

                    · <?php echo e($curTime); ?>

                </p>
            </div>
            <?php if (isset($component)) { $__componentOriginal8d75880d81198efb280b50e0bab6c1de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8d75880d81198efb280b50e0bab6c1de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.booking-status-actions','data' => ['model' => $cur,'type' => $curType,'variant' => 'floor','showStatus' => false,'patientUrl' => route('patients.show', $cur->patient_id)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('booking-status-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cur),'type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($curType),'variant' => 'floor','show-status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'patient-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('patients.show', $cur->patient_id))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8d75880d81198efb280b50e0bab6c1de)): ?>
<?php $attributes = $__attributesOriginal8d75880d81198efb280b50e0bab6c1de; ?>
<?php unset($__attributesOriginal8d75880d81198efb280b50e0bab6c1de); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8d75880d81198efb280b50e0bab6c1de)): ?>
<?php $component = $__componentOriginal8d75880d81198efb280b50e0bab6c1de; ?>
<?php unset($__componentOriginal8d75880d81198efb280b50e0bab6c1de); ?>
<?php endif; ?>
        </div>
    </section>
<?php elseif($isDoctor): ?>
    <section class="floor-doctor floor-doctor--empty">
        <p>فعلاً بیماری برای ویزیت در صف ارجاع نیست.</p>
    </section>
<?php endif; ?>

<div class="floor-cols">
    <?php echo $__env->make('clinic.partials.floor-column', [
        'title' => 'نوبت‌های روز',
        'hint' => 'هنوز نیامده‌اند',
        'tone' => 'upcoming',
        'rows' => $upcoming,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('clinic.partials.floor-column', [
        'title' => 'حضور / در صف',
        'hint' => 'منشی: ارجاع به پزشک',
        'tone' => 'waiting',
        'rows' => $waiting,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('clinic.partials.floor-column', [
        'title' => 'ارجاع به پزشک',
        'hint' => 'آماده ورود به اتاق',
        'tone' => 'ready',
        'rows' => $ready,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('clinic.partials.floor-column', [
        'title' => 'نزد پزشک',
        'hint' => 'در حال ویزیت',
        'tone' => 'consult',
        'rows' => $inConsult,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\clinic\partials\floor-board.blade.php ENDPATH**/ ?>