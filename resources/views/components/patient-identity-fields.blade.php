@props([
    'withSecondaryMobile' => false,
    'lookupUrl' => null,
])

@php
    $lookupUrl = $lookupUrl ?: route('patients.lookup-national-code');
@endphp

<div
    class="space-y-4"
    x-data="patientIdentityLookup({
        lookupUrl: @js($lookupUrl),
        initial: {
            no_national_code: @js((bool) old('no_national_code')),
            national_code: @js(old('national_code', '')),
            name: @js(old('patient_name', '')),
            mobile: @js(old('mobile', '')),
            age: @js(old('age', '')),
            mobile_secondary: @js(old('mobile_secondary', '')),
        },
    })"
>
    <div>
        <h3 class="text-sm font-bold" style="color: var(--ink);">شناسه بیمار</h3>
        <p class="mt-1 text-xs" style="color: var(--muted);">
            ابتدا کد ملی را وارد کنید. اگر پرونده باشد مشخصات از پرونده پر می‌شود و قابل تغییر از این فرم نیست.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="national_code" value="کد ملی" />
            <x-text-input
                id="national_code"
                name="national_code"
                type="text"
                inputmode="numeric"
                maxlength="10"
                class="mt-1 block w-full font-mono"
                dir="ltr"
                x-model="nationalCode"
                x-bind:required="!noNationalCode"
                x-bind:disabled="noNationalCode"
                x-on:input="onNationalInput($event)"
            />
            <label class="mt-2 flex items-center gap-2 text-xs" style="color: var(--muted);">
                <input type="checkbox" name="no_national_code" value="1" x-model="noNationalCode" @change="onNoCodeChange()">
                فاقد کد ملی
            </label>
            <p class="mt-1 text-[11px]" style="color: var(--muted);" x-show="looking" x-cloak>در حال جستجوی پرونده…</p>
            <p class="mt-1 text-[11px] font-bold" style="color: var(--brand-dark);" x-show="status === 'found'" x-cloak>پرونده پیدا شد — مشخصات از پرونده پر شد.</p>
            <p class="mt-1 text-[11px] font-bold" style="color: var(--warn, #b45309);" x-show="status === 'new'" x-cloak>پرونده‌ای با این کد ملی نیست — مشخصات جدید را وارد کنید.</p>
            <p class="mt-1 text-[11px] text-rose-600" x-show="status === 'error'" x-cloak>خطا در جستجو. دوباره تلاش کنید.</p>
            <x-input-error class="mt-2" :messages="$errors->get('national_code')" />
        </div>

        <div>
            <x-input-label for="patient_name" value="نام و نام خانوادگی" />
            <x-text-input
                id="patient_name"
                name="patient_name"
                type="text"
                class="mt-1 block w-full"
                x-model="name"
                x-bind:readonly="nameLocked"
                x-bind:disabled="nameDisabled"
                x-bind:required="detailsOpen && !nameDisabled"
                x-bind:class="(nameLocked || nameDisabled) && 'opacity-80'"
            />
            <p class="mt-1 text-[11px]" style="color: var(--muted);" x-show="nameDisabled" x-cloak>بعد از ورود کد ملی، اگر پرونده نبود نام فعال می‌شود.</p>
            <p class="mt-1 text-[11px]" style="color: var(--muted);" x-show="nameLocked" x-cloak>نام از پرونده است و از اینجا عوض نمی‌شود.</p>
            <x-input-error class="mt-2" :messages="$errors->get('patient_name')" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2" x-show="detailsOpen" x-cloak x-transition>
        <div>
            <x-input-label for="mobile" value="شماره تماس" />
            <x-text-input
                id="mobile"
                name="mobile"
                type="text"
                inputmode="tel"
                maxlength="11"
                class="mt-1 block w-full font-mono"
                dir="ltr"
                x-model="mobile"
                x-bind:readonly="extrasLocked"
                x-bind:required="detailsOpen"
                x-on:input="if (!extrasLocked) mobile = normalizeDigits($event.target.value, 11)"
            />
            <x-input-error class="mt-2" :messages="$errors->get('mobile')" />
        </div>
        <div>
            <x-input-label for="age" value="سن" />
            <x-text-input
                id="age"
                name="age"
                type="text"
                class="mt-1 block w-full"
                x-model="age"
                x-bind:readonly="extrasLocked"
            />
            <x-input-error class="mt-2" :messages="$errors->get('age')" />
        </div>
        @if($withSecondaryMobile)
            <div class="sm:col-span-2">
                <x-input-label for="mobile_secondary" value="شماره تماس دوم (اختیاری)" />
                <x-text-input
                    id="mobile_secondary"
                    name="mobile_secondary"
                    type="text"
                    inputmode="tel"
                    maxlength="11"
                    class="mt-1 block w-full font-mono"
                    dir="ltr"
                    x-model="mobileSecondary"
                    x-bind:readonly="extrasLocked"
                    x-on:input="if (!extrasLocked) mobileSecondary = normalizeDigits($event.target.value, 11)"
                />
                <x-input-error class="mt-2" :messages="$errors->get('mobile_secondary')" />
            </div>
        @endif
    </div>
</div>
