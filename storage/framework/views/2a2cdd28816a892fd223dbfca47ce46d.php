<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['active']));

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

foreach (array_filter((['active']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$classes = ($active ?? false)
            ? 'block w-full rounded-xl px-3 py-3 text-start text-sm font-bold transition'
            : 'block w-full rounded-xl px-3 py-3 text-start text-sm font-semibold transition';
$style = ($active ?? false)
            ? 'background:var(--brand-soft);color:var(--brand-dark)'
            : 'color:var(--ink)';
?>

<a <?php echo e($attributes->merge(['class' => $classes, 'style' => $style])); ?>>
    <?php echo e($slot); ?>

</a>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\responsive-nav-link.blade.php ENDPATH**/ ?>