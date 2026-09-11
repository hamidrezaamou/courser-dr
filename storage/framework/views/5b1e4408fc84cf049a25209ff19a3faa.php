<?php
    $smsValue = $schedule->smsText() ?: \App\Support\AppointmentSms::defaultFor($schedule->kind);
?>
<details class="times-sms-edit" onclick="event.stopPropagation()">
    <summary class="times-sms-edit__toggle">✉️ پیامک</summary>
    <form method="POST" action="<?php echo e(route('times.update-sms', $schedule)); ?>" class="times-sms-edit__form">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PATCH'); ?>
        <textarea name="sms_text" rows="3" class="field-input !text-[11px] !py-1.5" maxlength="1000"><?php echo e(old('sms_text', $smsValue)); ?></textarea>
        <button type="submit" class="times-sms-edit__save">ذخیره پیامک</button>
    </form>
</details>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\times\partials\sms-edit.blade.php ENDPATH**/ ?>