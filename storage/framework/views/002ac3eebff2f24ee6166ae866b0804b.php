<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name' => 'date',
    'value' => null,
    'label' => 'تاریخ شمسی',
    'required' => true,
    'allowPast' => true,
    'allowFriday' => true,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'name' => 'date',
    'value' => null,
    'label' => 'تاریخ شمسی',
    'required' => true,
    'allowPast' => true,
    'allowFriday' => true,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $dateValue = old($name, $value);
?>

<div
    class="jalali-date-field"
    data-jalali-date
    data-allow-past="<?php echo e($allowPast ? '1' : '0'); ?>"
    data-allow-friday="<?php echo e($allowFriday ? '1' : '0'); ?>"
>
    <?php if($label): ?>
        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => $label]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label)]); ?>
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
    <?php endif; ?>
    <input
        type="text"
        name="<?php echo e($name); ?>"
        data-jalali-date-input
        class="field-input mt-1 cursor-pointer font-mono"
        dir="ltr"
        placeholder="کلیک برای انتخاب تاریخ..."
        readonly
        <?php if($required): ?> required <?php endif; ?>
        value="<?php echo e($dateValue); ?>"
        <?php echo e($attributes->except(['name', 'value', 'label', 'required', 'allowPast', 'allowFriday'])); ?>

    >
    <div class="booking-calendar-modal" data-jalali-date-modal>
        <div class="booking-calendar-content">
            <div class="booking-calendar-header">
                <button type="button" data-next-month aria-label="ماه بعد">‹</button>
                <strong data-month-label></strong>
                <button type="button" data-prev-month aria-label="ماه قبل">›</button>
            </div>
            <div class="booking-calendar-grid" data-calendar-grid></div>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\jalali-date-input.blade.php ENDPATH**/ ?>