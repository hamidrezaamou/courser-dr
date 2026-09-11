<?php
    $item = $row['item'];
    $isSurgery = $row['type'] === 'surgery';
    $time = $item->scheduled_time ? \App\Support\SlotLabel::display((string) $item->scheduled_time) : '—';
    $kind = $isSurgery
        ? trim(($item->surgery_type ?: 'عمل').($item->eye_side ? ' · '.$item->eye_side : ''))
        : trim(($item->visit_type ?: 'ویزیت').($item->reason ? ' · '.$item->reason : ''));
    $typeLabel = $isSurgery ? 'surgery' : 'visit';
?>
<article class="floor-card <?php echo e($isSurgery ? 'floor-card--surgery' : 'floor-card--visit'); ?>">
    <div class="floor-card__top">
        <span class="floor-card__time" dir="ltr"><?php echo e($time); ?></span>
        <span class="floor-card__type <?php echo e($isSurgery ? 'floor-card__type--surgery' : 'floor-card__type--visit'); ?>">
            <?php echo e($isSurgery ? 'عمل' : 'ویزیت'); ?>

        </span>
    </div>
    <a href="<?php echo e(route('patients.show', $item->patient_id)); ?>" class="floor-card__name"><?php echo e($item->patient_name); ?></a>
    <p class="floor-card__kind">
        <?php echo e($kind); ?>

        <?php if($isSurgery && $item->hospital): ?>
            <span class="floor-card__hospital"> · <?php echo e($item->hospital->name); ?></span>
        <?php endif; ?>
    </p>
    <p class="floor-card__status"><?php echo e(\App\Support\BookingStatus::label($item->status)); ?></p>

    <?php if (isset($component)) { $__componentOriginal8d75880d81198efb280b50e0bab6c1de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8d75880d81198efb280b50e0bab6c1de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.booking-status-actions','data' => ['model' => $item,'type' => $typeLabel,'variant' => 'floor','showStatus' => false,'patientUrl' => route('patients.show', $item->patient_id)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('booking-status-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item),'type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($typeLabel),'variant' => 'floor','show-status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'patient-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('patients.show', $item->patient_id))]); ?>
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
</article>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\clinic\partials\floor-card.blade.php ENDPATH**/ ?>