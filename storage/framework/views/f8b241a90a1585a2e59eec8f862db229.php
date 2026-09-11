<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'type' => null,
    'dismiss' => 4000,
    'showErrors' => true,
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
    'type' => null,
    'dismiss' => 4000,
    'showErrors' => true,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $icons = [
        'success' => 'M5 13l4 4L19 7',
        'error' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z',
        'warn' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z',
        'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ];

    // With no explicit type the component reports whatever is in the session,
    // so a page only needs a single <x-flash /> near the top.
    $messages = [];

    if (! $type) {
        if (session('success')) {
            $messages[] = ['success', session('success')];
        }
        if (session('error')) {
            $messages[] = ['error', session('error')];
        }
        if ($showErrors && $errors->any()) {
            $messages[] = ['error', $errors->first()];
        }
    }
?>

<?php if($type): ?>
    <div
        <?php echo e($attributes->merge(['class' => 'flash flash--'.$type])); ?>

        role="<?php echo e($type === 'error' ? 'alert' : 'status'); ?>"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo e($icons[$type] ?? $icons['info']); ?>" />
        </svg>
        <div class="flash__body"><?php echo e($slot); ?></div>
    </div>
<?php else: ?>
    <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$level, $text]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div
            <?php echo e($attributes->merge(['class' => 'flash flash--'.$level])); ?>

            <?php if($level === 'success' && $dismiss): ?> data-auto-dismiss="<?php echo e($dismiss); ?>" <?php endif; ?>
            role="<?php echo e($level === 'error' ? 'alert' : 'status'); ?>"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo e($icons[$level]); ?>" />
            </svg>
            <div class="flash__body"><?php echo e($text); ?></div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\flash.blade.php ENDPATH**/ ?>