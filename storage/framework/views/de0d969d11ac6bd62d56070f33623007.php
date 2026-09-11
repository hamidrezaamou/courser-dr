<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['creator' => null, 'editor' => null, 'createdAt' => null, 'updatedAt' => null, 'wasEdited' => false]));

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

foreach (array_filter((['creator' => null, 'editor' => null, 'createdAt' => null, 'updatedAt' => null, 'wasEdited' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $showEdited = $wasEdited || ($editor && $updatedAt && $createdAt && $updatedAt != $createdAt);
?>

<?php if($creator || $showEdited): ?>
    <div class="tg-audit-meta text-[10px]" style="color: var(--muted);">
        <?php if($creator): ?>
            <span>ثبت: <?php echo e($creator->name ?? '—'); ?></span>
        <?php endif; ?>
        <?php if($showEdited): ?>
            <span <?php if($creator): ?> class="mx-1" <?php endif; ?>>
                ویرایش: <span dir="ltr"><?php echo e(jalali($updatedAt, 'Y/m/d H:i')); ?></span>
                <?php if($editor): ?> · <?php echo e($editor->name); ?> <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\audit-meta.blade.php ENDPATH**/ ?>