<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="page-title">ثبت بیمار جدید</h2>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            @if(auth()->user()->canManageSettings())
                <div class="panel fade-up p-6 sm:p-8">
                    <h3 class="text-base font-extrabold mb-2" style="color: var(--ink);">واردسازی گروهی از JSON</h3>
                    <p class="text-sm mb-4" style="color: var(--muted);">
                        فایلی با فیلدهای
                        <span dir="ltr" class="font-mono text-xs">FullName</span>،
                        <span dir="ltr" class="font-mono text-xs">NationalCode</span>،
                        <span dir="ltr" class="font-mono text-xs">MobileTel</span>
                        (یا معادل camelCase) آپلود کنید. اگر کد ملی تکراری باشد رد می‌شود؛ فقط پرونده‌های جدید ساخته می‌شوند.
                    </p>
                    <form method="POST" action="{{ route('patients.import-json') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="json_file" value="فایل JSON" />
                            <input id="json_file" name="json_file" type="file" accept=".json,application/json" class="field-input mt-1 block w-full" required>
                            <x-input-error class="mt-2" :messages="$errors->get('json_file')" />
                        </div>
                        <button type="submit" class="btn-secondary !py-2 !px-4 !text-sm"
                                onclick="return confirm('پرونده‌های جدید از فایل JSON وارد شوند؟')">
                            وارد کردن به پرونده‌ها
                        </button>
                    </form>
                </div>
            @endif

            <div class="panel fade-up p-6 sm:p-8">
                <div class="mb-6 rounded-xl border border-teal-100 bg-teal-50/70 p-4 text-sm text-teal-900">
                    رمز ورود اولیه بیمار برابر با <strong>شماره موبایل</strong> او خواهد بود.
                </div>

                <form method="POST" action="{{ route('patients.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="name" value="نام و نام خانوادگی" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="national_code" value="کد ملی" />
                        <x-text-input id="national_code" name="national_code" type="text" class="mt-1 block w-full" :value="old('national_code')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('national_code')" />
                    </div>

                    <div>
                        <x-input-label for="mobile" value="شماره موبایل" />
                        <x-text-input id="mobile" name="mobile" type="text" class="mt-1 block w-full" :value="old('mobile')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('mobile')" />
                    </div>

                    <div>
                        <x-input-label for="age" value="سن (اختیاری)" />
                        <x-text-input id="age" name="age" type="text" class="mt-1 block w-full" :value="old('age')" />
                        <x-input-error class="mt-2" :messages="$errors->get('age')" />
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <x-primary-button>
                            ثبت بیمار
                        </x-primary-button>

                        <a href="{{ route('dashboard') }}" class="btn-ghost">
                            بازگشت
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
