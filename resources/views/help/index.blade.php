<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">راهنمای سریع</h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
            <section class="panel space-y-3 p-4">
                <h3 class="text-sm font-bold" style="color: var(--ink);">برای منشی (۲ دقیقه)</h3>
                <ol class="list-decimal space-y-2 pe-5 text-sm leading-7" style="color: var(--muted);">
                    <li>بیمار را از داشبورد جستجو کنید؛ اگر نبود «ثبت بیمار».</li>
                    <li>از پرونده یا منو، «ثبت ویزیت» / «ثبت عمل» را بزنید.</li>
                    <li>در «نوبت‌ها» وضعیت را به‌روز کنید و در صورت نیاز پیامک بفرستید.</li>
                    <li>از منوی «پیگیری» کارهای امروز و عقب‌افتاده را انجام دهید و نتیجه ثبت کنید.</li>
                    <li>اگر تأیید آنلاین روشن است، درخواست‌های بنفش را تأیید/رد کنید.</li>
                </ol>
            </section>

            <section class="panel space-y-3 p-4">
                <h3 class="text-sm font-bold" style="color: var(--ink);">برای پزشک (۲ دقیقه)</h3>
                <ol class="list-decimal space-y-2 pe-5 text-sm leading-7" style="color: var(--muted);">
                    <li>از «صف مطب» بیمار ارجاع‌شده را باز کنید.</li>
                    <li>معاینه، وایت‌برد و نسخه را در پرونده ثبت کنید.</li>
                    <li>برای عمل، چک‌لیست و پرینت‌های بیمارستان را از ابزار ردیف بگیرید.</li>
                    <li>الگوی پیگیری هر نوع/زیرگروه عمل را از تنظیمات → پیگیری بسازید تا با ثبت نوبت عمل خودکار ایجاد شود.</li>
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
                <p class="text-xs leading-6" style="color: var(--muted);">{{ $retentionNote }}</p>
            </section>

            <section class="panel space-y-3 p-4">
                <h3 class="text-sm font-bold" style="color: var(--ink);">پشتیبانی</h3>
                <div class="space-y-2 text-sm" style="color: var(--muted);">
                    @if($support['telegram'])
                        <div>تلگرام: <span class="font-bold" style="color: var(--ink);" dir="ltr">{{ $support['telegram'] }}</span></div>
                    @endif
                    @if($support['phone'])
                        <div>تلفن: <span class="font-bold ltr-data" style="color: var(--ink);" dir="ltr">{{ $support['phone'] }}</span></div>
                    @endif
                    @if($support['email'])
                        <div>ایمیل: <span class="font-bold ltr-data" style="color: var(--ink);" dir="ltr">{{ $support['email'] }}</span></div>
                    @endif
                    <div>SLA پاسخ: حداکثر {{ $support['sla_hours'] }} ساعت کاری</div>
                    @if($support['notes'])
                        <p class="leading-7">{{ $support['notes'] }}</p>
                    @endif
                    @if(! $support['telegram'] && ! $support['phone'] && ! $support['email'])
                        <p>کانال پشتیبانی هنوز تنظیم نشده — مدیر از «مدیریت → پشتیبانی» پر کند.</p>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
