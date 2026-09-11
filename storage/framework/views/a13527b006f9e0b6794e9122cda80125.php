<?php
    $smsEnabled = $widgetData['comms']['smsEnabled'] ?? false;
    $smsDriver = $widgetData['comms']['smsDriver'] ?? '';
    $telegramEnabled = $widgetData['comms']['telegramEnabled'] ?? false;
?>
<section class="admin-panel admin-dash-panel" id="comms">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">وضعیت ارتباطات</h3>
        <a href="<?php echo e(route('admin.settings.communications')); ?>" class="admin-panel__link">ویرایش</a>
    </div>
    <div class="admin-status-list">
        <div class="admin-status-row">
            <span>پیامک</span>
            <strong class="<?php echo e($smsEnabled && $smsDriver === 'smsir' ? 'is-on' : 'is-off'); ?>">
                <?php echo e($smsEnabled ? ('روشن · '.$smsDriver) : 'خاموش'); ?>

            </strong>
        </div>
        <div class="admin-status-row">
            <span>تلگرام یادآوری</span>
            <strong class="<?php echo e($telegramEnabled ? 'is-on' : 'is-off'); ?>">
                <?php echo e($telegramEnabled ? 'روشن' : 'خاموش'); ?>

            </strong>
        </div>
    </div>
</section>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\widgets\comms.blade.php ENDPATH**/ ?>