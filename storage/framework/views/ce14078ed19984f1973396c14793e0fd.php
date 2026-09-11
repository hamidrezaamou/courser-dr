<section class="admin-panel admin-dash-panel">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">میان‌برهای مدیریت</h3>
    </div>
    <div class="admin-shortcuts">
        <a href="<?php echo e(route('admin.users.index')); ?>" class="admin-shortcut">کاربران و نقش‌ها</a>
        <a href="<?php echo e(route('admin.settings.communications')); ?>" class="admin-shortcut">ارتباطات</a>
        <a href="<?php echo e(route('admin.settings.brand')); ?>" class="admin-shortcut">برند و چاپ</a>
        <a href="<?php echo e(route('admin.settings.system')); ?>" class="admin-shortcut">سامانه / بک‌آپ</a>
        <a href="<?php echo e(route('admin.settings.features')); ?>" class="admin-shortcut">قابلیت‌ها</a>
        <a href="<?php echo e(route('admin.settings.quick-links')); ?>" class="admin-shortcut">لینک‌های ویژه هدر</a>
        <?php $__currentLoopData = \App\Support\ModuleRegistry::enabled(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route($module['route'])); ?>" class="admin-shortcut"><?php echo e($module['label']); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('settings.times')); ?>" class="admin-shortcut">تایم‌ها</a>
        <a href="<?php echo e(route('settings.hospitals')); ?>" class="admin-shortcut">بیمارستان‌ها</a>
        <a href="<?php echo e(route('settings.surgery-types')); ?>" class="admin-shortcut">انواع عمل</a>
        <a href="<?php echo e(route('settings.drugs')); ?>" class="admin-shortcut">داروها</a>
        <a href="<?php echo e(route('activity-logs.index')); ?>" class="admin-shortcut">لاگ ممیزی</a>
        <a href="<?php echo e(route('reports.index')); ?>" class="admin-shortcut">گزارشات</a>
        <a href="<?php echo e(route('appointments.board')); ?>" class="admin-shortcut">نوبت‌ها</a>
    </div>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\shortcuts.blade.php ENDPATH**/ ?>