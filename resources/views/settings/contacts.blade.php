<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">خروجی مخاطب</h2>
        </div>
    </x-slot>

    <div class="settings-page-body">
        <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-settings-dock :settings-section="'contacts'" />
            <x-flash />

            <div class="panel p-5 sm:p-6 space-y-4">
                <h3 class="text-sm font-bold" style="color: var(--ink);">انتقال شماره بیماران به گوشی</h3>
                <p class="text-sm leading-7" style="color: var(--muted);">
                    نام و شمارهٔ همهٔ بیماران فعال به مخاطبین گوشی اضافه می‌شود.
                    اگر شماره‌ای از قبل روی گوشی باشد دست نمی‌خورد؛ فقط شماره‌های جدید اضافه می‌شوند.
                </p>

                @if($isClinicApp ?? false)
                    <a href="{{ url('/settings/contacts-sync') }}" class="btn-primary">خروجی مخاطب به گوشی</a>
                @else
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('settings.contacts.vcf') }}" class="btn-primary">دانلود فایل مخاطب</a>
                    </div>
                    <p class="text-xs leading-6" style="color: var(--muted);">
                        افزودن مستقیم به مخاطبین گوشی فقط داخل اپ انجام می‌شود. از مرورگر می‌توانید فایل را دانلود و وارد کنید.
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
