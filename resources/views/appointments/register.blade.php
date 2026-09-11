<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="page-title">ثبت ویزیت</h2>
            <a href="{{ route('dashboard') }}" class="btn-ghost !text-xs !px-2.5 !py-1.5">داشبورد</a>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('appointments.register.store') }}" class="panel fade-up space-y-6 p-5 sm:p-8" id="visit-register-form">
                @csrf

                <x-patient-identity-fields :with-secondary-mobile="true" />

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

                    <x-booking-datetime kind="visit" mode="select" :date="old('scheduled_date')" />

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
                    <button type="submit" class="btn-primary">ثبت ویزیت</button>
                    <a href="{{ route('dashboard') }}" class="btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('visit-register-form')?.addEventListener('submit', function (e) {
            var noCode = this.querySelector('input[name="no_national_code"]');
            var national = this.querySelector('#national_code');
            if (noCode && noCode.checked) {
                if (national) national.value = '';
                return;
            }
            var digits = String(national?.value || '')
                .replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); })
                .replace(/[٠-٩]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); })
                .replace(/\D+/g, '');
            if (national) national.value = digits.slice(0, 10);
            if (digits.length !== 10) {
                e.preventDefault();
                alert('کد ملی باید دقیقاً ۱۰ رقم باشد');
                national?.focus();
            }
        });
    </script>
</x-app-layout>
