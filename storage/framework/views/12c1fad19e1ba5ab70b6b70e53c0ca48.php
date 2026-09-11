<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label' => 'عملیات',
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
    'label' => 'عملیات',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>


<details <?php echo e($attributes->class(['aa-actions'])); ?>>
    <summary class="aa-actions__trigger" aria-label="<?php echo e($label); ?>" title="<?php echo e($label); ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
        </svg>
        <span><?php echo e($label); ?></span>
    </summary>
    <div class="aa-actions__bar" role="menu">
        <?php echo e($slot); ?>

    </div>
</details>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\item-actions.blade.php ENDPATH**/ ?>