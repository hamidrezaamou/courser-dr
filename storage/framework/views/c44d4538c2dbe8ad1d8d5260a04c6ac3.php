<div id="patient-ajax-head">
    <?php echo $__env->make('dashboard.partials.results-head', ['patients' => $patients, 'search' => $search, 'stats' => $stats], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<div id="patient-ajax-body">
    <?php echo $__env->make('dashboard.partials.results-body', ['patients' => $patients, 'search' => $search], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\dashboard\partials\results-ajax.blade.php ENDPATH**/ ?>