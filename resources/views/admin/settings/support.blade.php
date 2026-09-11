<x-app-layout body-class="is-admin">
    <x-slot name="header">
        <div class="admin-header">
            <div>
                <p class="admin-header__eyebrow">مدیریت کل سایت</p>
                <h2 class="admin-header__title">پشتیبانی و SLA</h2>
            </div>
            <div class="admin-header__actions">
                <a href="{{ route('help.index') }}" class="btn-secondary btn-primary--compact">پیش‌نمایش راهنما</a>
            </div>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'support'" />
            <x-flash />

            <form method="POST" action="{{ route('admin.settings.support.update') }}" class="admin-panel mx-auto max-w-2xl space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label value="آیدی تلگرام پشتیبانی" />
                    <x-text-input name="telegram" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('telegram', $values['telegram'])" placeholder="@clinic_support" />
                </div>
                <div>
                    <x-input-label value="تلفن پشتیبانی" />
                    <x-text-input name="phone" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('phone', $values['phone'])" />
                </div>
                <div>
                    <x-input-label value="ایمیل" />
                    <x-text-input name="email" type="email" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('email', $values['email'])" />
                </div>
                <div>
                    <x-input-label value="SLA پاسخ (ساعت)" />
                    <x-text-input name="sla_hours" type="number" min="1" max="168" class="mt-1 block w-32" :value="old('sla_hours', $values['sla_hours'])" required />
                    <p class="admin-panel__hint">تعهد ساده به مطب: حداکثر زمان پاسخ در ساعات کاری.</p>
                </div>
                <div>
                    <x-input-label value="متن راهنمای پشتیبانی" />
                    <textarea name="notes" rows="4" class="field-input mt-1 w-full">{{ old('notes', $values['notes']) }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
