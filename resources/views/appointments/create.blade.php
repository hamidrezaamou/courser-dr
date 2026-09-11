<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="page-title">ثبت ویزیت</h2>
            <a href="{{ route('patients.show', $patient) }}" class="btn-ghost !text-xs !px-2.5 !py-1.5">پرونده</a>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8" x-data="{ noNationalCode: @js((bool) old('no_national_code', blank($patient->national_code))) }">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('appointments.store', $patient) }}" class="panel fade-up space-y-6 p-5 sm:p-8">
                @csrf

                <div>
                    <h3 class="text-sm font-bold" style="color: var(--ink);">مشخصات بیمار</h3>
                    <p class="mt-1 text-xs" style="color: var(--muted);">این فیلدها از پرونده بیمار آمده‌اند.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="patient_name" value="نام و نام خانوادگی" />
                        <x-text-input id="patient_name" name="patient_name" type="text" class="mt-1 block w-full" :value="old('patient_name', $patient->name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('patient_name')" />
                    </div>
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
                            :value="old('national_code', $patient->national_code)"
                            x-bind:required="!noNationalCode"
                            x-bind:disabled="noNationalCode"
                            x-on:input="$el.value = $el.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 10)"
                        />
                        <label class="mt-2 flex items-center gap-2 text-xs" style="color: var(--muted);">
                            <input type="checkbox" name="no_national_code" value="1" x-model="noNationalCode">
                            فاقد کد ملی
                        </label>
                        <x-input-error class="mt-2" :messages="$errors->get('national_code')" />
                    </div>
                    <div>
                        <x-input-label for="mobile" value="شماره تماس" />
                        <x-text-input id="mobile" name="mobile" type="text" inputmode="tel" maxlength="11" class="mt-1 block w-full font-mono" dir="ltr" :value="old('mobile', $patient->mobile)" required x-on:input="$el.value = $el.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 11)" />
                        <x-input-error class="mt-2" :messages="$errors->get('mobile')" />
                    </div>
                    <div>
                        <x-input-label for="mobile_secondary" value="شماره تماس دوم (اختیاری)" />
                        <x-text-input id="mobile_secondary" name="mobile_secondary" type="text" inputmode="tel" maxlength="11" class="mt-1 block w-full font-mono" dir="ltr" :value="old('mobile_secondary', $patient->mobile_secondary)" x-on:input="$el.value = $el.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 11)" />
                        <x-input-error class="mt-2" :messages="$errors->get('mobile_secondary')" />
                    </div>
                    <div>
                        <x-input-label for="age" value="سن" />
                        <x-text-input id="age" name="age" type="text" class="mt-1 block w-full" :value="old('age', $patient->age)" />
                        <x-input-error class="mt-2" :messages="$errors->get('age')" />
                    </div>
                </div>

                <div class="border-t pt-5" style="border-color: var(--line);">
                    <h3 class="mb-4 text-sm font-bold" style="color: var(--ink);">زمان‌بندی نوبت</h3>
                    <div class="mb-4">
                        <x-input-label for="visit_type" value="نوع ویزیت" />
                        <select id="visit_type" name="visit_type" class="field-input">
                            <option value="">انتخاب کنید</option>
                            @foreach (['معاینه عمومی', 'پیگیری', 'اورژانس', 'مشاوره', 'سایر'] as $type)
                                <option value="{{ $type }}" @selected(old('visit_type') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-booking-datetime kind="visit" mode="select" />

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="reason" value="علت مراجعه" />
                            <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" :value="old('reason')" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="notes" value="توضیحات" />
                            <textarea id="notes" name="notes" rows="3" class="field-input">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary">ثبت نوبت ویزیت</button>
                    <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
