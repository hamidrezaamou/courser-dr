<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'mobile',
    'label' => 'تماس',
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
    'mobile',
    'label' => 'تماس',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $digits = preg_replace('/\D+/', '', (string) $mobile) ?? '';
    if (str_starts_with($digits, '98') && strlen($digits) === 12) {
        $digits = '0'.substr($digits, 2);
    }
    if (str_starts_with($digits, '9') && strlen($digits) === 10) {
        $digits = '0'.$digits;
    }
    $href = preg_match('/^09\d{9}$/', $digits)
        ? 'tel:+98'.substr($digits, 1)
        : (strlen($digits) >= 8 ? 'tel:'.$digits : null);
?>

<?php if($href): ?>
    <a
        href="<?php echo e($href); ?>"
        dir="ltr"
        title="تماس با <?php echo e($mobile); ?>"
        <?php echo e($attributes->class('phone-call-btn touch-action inline-flex items-center justify-center gap-1.5')); ?>

    >
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
        </svg>
        <span><?php echo e($label); ?></span>
    </a>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\phone-call.blade.php ENDPATH**/ ?>