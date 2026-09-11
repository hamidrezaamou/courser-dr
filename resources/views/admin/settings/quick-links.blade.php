<x-app-layout body-class="is-admin">
    <x-slot name="header">
        <div class="admin-header">
            <div>
                <p class="admin-header__eyebrow">مدیریت کل سایت</p>
                <h2 class="admin-header__title">لینک‌های ویژه هدر</h2>
            </div>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'quick-links'" />
            <x-flash />

            <div class="mx-auto max-w-2xl space-y-4">
            <div class="admin-panel space-y-2">
                <p class="text-sm" style="color: var(--muted);">
                    این عنوان‌ها و لینک‌ها در هدر، داخل دکمهٔ <strong>ویژه</strong> (بعد از ماژول‌ها) نمایش داده می‌شوند.
                    مسیر داخلی مثل <span dir="ltr">/reports</span> یا آدرس کامل <span dir="ltr">https://...</span> مجاز است.
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('admin.settings.quick-links.update') }}"
                class="admin-panel space-y-4"
                x-data="{
                    links: {{ \Illuminate\Support\Js::from(old('links', $links)) }},
                    max: {{ \App\Support\NavQuickLinks::MAX }},
                    add() {
                        if (this.links.length >= this.max) return;
                        this.links.push({ title: '', url: '' });
                    },
                    remove(index) {
                        if (this.links.length <= 1) return;
                        this.links.splice(index, 1);
                    },
                }"
            >
                @csrf
                @method('PUT')

                <x-input-error :messages="$errors->get('links')" class="mb-2" />

                <div class="space-y-3">
                    <template x-for="(row, index) in links" :key="index">
                        <div class="rounded-xl border p-3 space-y-2" style="border-color: var(--line); background: var(--panel-soft);">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold" style="color: var(--muted);" x-text="'ردیف ' + (index + 1)"></span>
                                <button type="button" class="btn-ghost !px-2 !py-1 !text-xs text-red-600" @click="remove(index)" x-show="links.length > 1">حذف</button>
                            </div>
                            <div>
                                <label class="text-xs font-bold" style="color: var(--muted);">عنوان</label>
                                <input type="text" class="field-input mt-1 w-full" maxlength="80"
                                       :name="'links['+index+'][title]'" x-model="row.title" placeholder="مثال: سامانه بیمه">
                            </div>
                            <div>
                                <label class="text-xs font-bold" style="color: var(--muted);">لینک</label>
                                <input type="text" class="field-input mt-1 w-full ltr-data" dir="ltr" maxlength="500"
                                       :name="'links['+index+'][url]'" x-model="row.url" placeholder="/reports یا https://example.com">
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <button type="button" class="btn-secondary !py-2 !px-3 !text-sm" @click="add()" :disabled="links.length >= max">
                        + افزودن لینک
                    </button>
                    <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">ذخیره</button>
                </div>
            </form>
            </div>
        </div>
    </div>
</x-app-layout>
