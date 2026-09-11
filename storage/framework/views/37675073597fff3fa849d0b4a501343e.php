<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'date' => null,
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
    'date' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $target = null;
    if ($date) {
        try {
            $target = \Illuminate\Support\Carbon::parse($date)->startOfDay();
        } catch (\Throwable $e) {
            $target = null;
        }
    }

    $days = $target ? (int) round(\Illuminate\Support\Carbon::now()->startOfDay()->diffInDays($target, false)) : null;

    [$tone, $text] = match (true) {
        $days === null => [null, null],
        $days === 0 => ['today', 'امروز'],
        $days === 1 => ['soon', 'فردا'],
        $days > 1 && $days <= 7 => ['soon', $days.' روز مانده'],
        $days > 7 => ['later', $days.' روز مانده'],
        $days === -1 => ['past', 'دیروز'],
        default => ['past', abs($days).' روز پیش'],
    };
?>

<?php if($text): ?>
    <span <?php echo e($attributes->class(['evt-count', 'evt-count--'.$tone])); ?>>
        <?php if($tone === 'today'): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="w-3 h-3" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" d="M12 7.5V12l3 1.8" />
            </svg>
        <?php endif; ?>
        <?php echo e($text); ?>

    </span>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\day-countdown.blade.php ENDPATH**/ ?>