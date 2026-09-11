<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <h2 class="page-title">راهنمای سریع</h2>
     <?php $__env->endSlot(); ?>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
            <section class="panel space-y-3 p-4">
                <h3 class="text-sm font-bold" style="color: var(--ink);">برای منشی (۲ دقیقه)</h3>
                <ol class="list-decimal space-y-2 pe-5 text-sm leading-7" style="color: var(--muted);">
                    <li>بیمار را از داشبورد جستجو کنید؛ اگر نبود «ثبت بیمار».</li>
                    <li>از پرونده یا منو، «ثبت ویزیت» / «ثبت عمل» را بزنید.</li>
                    <li>در «نوبت‌ها» وضعیت را به‌روز کنید و در صورت نیاز پیامک بفرستید.</li>
                    <li>اگر تأیید آنلاین روشن است، درخواست‌های بنفش را تأیید/رد کنید.</li>
                </ol>
            </section>

            <section class="panel space-y-3 p-4">
                <h3 class="text-sm font-bold" style="color: var(--ink);">برای پزشک (۲ دقیقه)</h3>
                <ol class="list-decimal space-y-2 pe-5 text-sm leading-7" style="color: var(--muted);">
                    <li>از «صف مطب» بیمار ارجاع‌شده را باز کنید.</li>
                    <li>معاینه، وایت‌برد و نسخه را در پرونده ثبت کنید.</li>
                    <li>برای عمل، چک‌لیست و پرینت‌های بیمارستان را از ابزار ردیف بگیرید.</li>
                    <li>خروجی گزارش و ممیزی در مدیریت در دسترس است.</li>
                </ol>
            </section>

            <section class="panel space-y-3 p-4">
                <h3 class="text-sm font-bold" style="color: var(--ink);">پشتیبان‌گیری و امنیت</h3>
                <ul class="list-disc space-y-2 pe-5 text-sm leading-7" style="color: var(--muted);">
                    <li>بک‌آپ روزانه خودکار ساعت ۰۲:۳۰؛ دستی از «مدیریت → سامانه».</li>
                    <li>بازیابی فقط برای مدیر؛ قبل از restore یک بک‌آپ تازه گرفته می‌شود.</li>
                    <li>مشاهده پرونده در ممیزی ثبت می‌شود.</li>
                    <li>حذف امن پرونده: فقط مدیر، با تأیید نام بیمار.</li>
                </ul>
                <p class="text-xs leading-6" style="color: var(--muted);"><?php echo e($retentionNote); ?></p>
            </section>

            <section class="panel space-y-3 p-4">
                <h3 class="text-sm font-bold" style="color: var(--ink);">پشتیبانی</h3>
                <div class="space-y-2 text-sm" style="color: var(--muted);">
                    <?php if($support['telegram']): ?>
                        <div>تلگرام: <span class="font-bold" style="color: var(--ink);" dir="ltr"><?php echo e($support['telegram']); ?></span></div>
                    <?php endif; ?>
                    <?php if($support['phone']): ?>
                        <div>تلفن: <span class="font-bold ltr-data" style="color: var(--ink);" dir="ltr"><?php echo e($support['phone']); ?></span></div>
                    <?php endif; ?>
                    <?php if($support['email']): ?>
                        <div>ایمیل: <span class="font-bold ltr-data" style="color: var(--ink);" dir="ltr"><?php echo e($support['email']); ?></span></div>
                    <?php endif; ?>
                    <div>SLA پاسخ: حداکثر <?php echo e($support['sla_hours']); ?> ساعت کاری</div>
                    <?php if($support['notes']): ?>
                        <p class="leading-7"><?php echo e($support['notes']); ?></p>
                    <?php endif; ?>
                    <?php if(! $support['telegram'] && ! $support['phone'] && ! $support['email']): ?>
                        <p>کانال پشتیبانی هنوز تنظیم نشده — مدیر از «مدیریت → پشتیبانی» پر کند.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\help\index.blade.php ENDPATH**/ ?>