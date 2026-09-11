<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="page-title">ویرایش ویزیت</h2>
            <a href="{{ route('appointments.board') }}" class="btn-ghost !text-xs !px-2.5 !py-1.5">نوبت‌ها</a>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="panel fade-up space-y-6 p-5 sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="patient_name" value="نام و نام خانوادگی" />
                        <x-text-input id="patient_name" name="patient_name" type="text" class="mt-1 block w-full" :value="old('patient_name', $appointment->patient_name)" required />
                    </div>
                    <div>
                        <x-input-label for="national_code" value="کد ملی" />
                        <x-text-input id="national_code" name="national_code" type="text" class="mt-1 block w-full font-mono" dir="ltr" :value="old('national_code', $appointment->national_code)" required />
                    </div>
                    <div>
                        <x-input-label for="mobile" value="شماره تماس" />
                        <x-text-input id="mobile" name="mobile" type="text" class="mt-1 block w-full font-mono" dir="ltr" :value="old('mobile', $appointment->mobile)" required />
                    </div>
                    <div>
                        <x-input-label for="mobile_secondary" value="شماره تماس دوم (اختیاری)" />
                        <x-text-input id="mobile_secondary" name="mobile_secondary" type="text" inputmode="tel" maxlength="11" class="mt-1 block w-full font-mono" dir="ltr" :value="old('mobile_secondary', $appointment->mobile_secondary)" />
                        <x-input-error class="mt-2" :messages="$errors->get('mobile_secondary')" />
                    </div>
                    <div>
                        <x-input-label for="age" value="سن" />
                        <x-text-input id="age" name="age" type="text" class="mt-1 block w-full" :value="old('age', $appointment->age)" />
                    </div>
                </div>

                <div class="border-t pt-5" style="border-color: var(--line);">
                    <div class="mb-4">
                        <x-input-label for="visit_type" value="نوع ویزیت" />
                        <select id="visit_type" name="visit_type" class="field-input">
                            <option value="">انتخاب کنید</option>
                            @foreach (['معاینه عمومی', 'پیگیری', 'اورژانس', 'مشاوره', 'سایر'] as $type)
                                <option value="{{ $type }}" @selected(old('visit_type', $appointment->visit_type) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-booking-datetime
                        kind="visit"
                        :exclude-id="$appointment->id"
                        :date="jalali($appointment->scheduled_date, 'Y/m/d')"
                        :time="\Illuminate\Support\Str::of($appointment->scheduled_time)->substr(0, 5)->toString()"
                    />

                    <div class="mt-4 space-y-4">
                        <div>
                            <x-input-label for="reason" value="علت مراجعه" />
                            <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" :value="old('reason', $appointment->reason)" />
                        </div>
                        <div>
                            <x-input-label for="notes" value="توضیحات" />
                            <textarea id="notes" name="notes" rows="3" class="field-input">{{ old('notes', $appointment->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary">ذخیره تغییرات</button>
                    <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">پرونده بیمار</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
