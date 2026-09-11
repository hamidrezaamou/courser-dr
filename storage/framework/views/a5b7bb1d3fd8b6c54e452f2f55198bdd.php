<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'paginator',
    'label' => 'مورد',
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
    'paginator',
    'label' => 'مورد',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $options = \App\Support\ListPagination::OPTIONS;
    $current = $paginator->perPage();
    $query = request()->except(['page', 'per_page']);
?>

<?php if($paginator->total() > 0): ?>
    <div <?php echo e($attributes->class('list-pager')); ?>>
        <form method="GET" class="list-pager__per">
            <?php $__currentLoopData = $query; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(is_array($value)): ?>
                    <?php $__currentLoopData = $value; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nested): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <input type="hidden" name="<?php echo e($key); ?>[]" value="<?php echo e($nested); ?>">
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php elseif($value !== null && $value !== ''): ?>
                    <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <label for="list-per-page">تعداد در صفحه</label>
            <select id="list-per-page" name="per_page" class="field-input" onchange="this.form.submit()">
                <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($n); ?>" <?php if($current === $n): echo 'selected'; endif; ?>><?php echo e($n); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </form>

        <div class="list-pager__meta">
            <?php echo e($paginator->firstItem()); ?>–<?php echo e($paginator->lastItem()); ?>

            از <?php echo e(number_format($paginator->total())); ?> <?php echo e($label); ?>

        </div>

        <div class="list-pager__links">
            <?php echo e($paginator->withQueryString()->onEachSide(1)->links()); ?>

        </div>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\list-pager.blade.php ENDPATH**/ ?>