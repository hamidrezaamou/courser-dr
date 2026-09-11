<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'mobile' => '',
    'patientName' => '',
    'label' => 'پاسخ‌های آماده',
    'variant' => 'solid',
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
    'mobile' => '',
    'patientName' => '',
    'label' => 'پاسخ‌های آماده',
    'variant' => 'solid',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    // Full class names stay literal so Tailwind keeps them in the build.
    $variantClass = match ($variant) {
        'soft' => 'ans-launch--soft',
        'block' => 'ans-launch--block',
        default => '',
    };
?>


<button
    type="button"
    <?php echo e($attributes->class(['ans-launch', $variantClass])); ?>

    data-answer-launch
    data-answer-mobile="<?php echo e($mobile); ?>"
    data-answer-name="<?php echo e($patientName); ?>"
    title="<?php echo e($label); ?>"
>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
    </svg>
    <span><?php echo e($label); ?></span>
</button>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\answer-launcher.blade.php ENDPATH**/ ?>