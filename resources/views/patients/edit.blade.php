@php
    $fromFile = $fromFile ?? request()->boolean('from_file');
    $canManageHub = auth()->user()?->canManageSettings();
    $backUrl = $fromFile || ! $canManageHub
        ? route('patients.show', $patient)
        : route('settings.patients', ['q' => request('q')]);
    $backLabel = $fromFile || ! $canManageHub ? 'بازگشت به پرونده' : 'بازگشت به فهرست';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">ویرایش بیمار</h2>
            <a href="{{ $backUrl }}" class="btn-ghost">{{ $backLabel }}</a>
        </div>
    </x-slot>

    <div class="settings-page-body">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if($canManageHub && ! $fromFile)
                <x-settings-dock :settings-section="'patients'" />
            @endif
            <x-flash />

            <form method="POST" action="{{ route('patients.update', $patient) }}" class="admin-panel mx-auto max-w-2xl space-y-4">
                @csrf
                @method('PUT')
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                @if($fromFile)
                    <input type="hidden" name="from_file" value="1">
                @endif

                <div>
                    <x-input-label for="name" value="نام و نام خانوادگی" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $patient->name)" required autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="national_code" value="کد ملی" />
                        <x-text-input id="national_code" name="national_code" type="text" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('national_code', $patient->national_code)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('national_code')" />
                    </div>
                    <div>
                        <x-input-label for="mobile" value="شماره موبایل" />
                        <x-text-input id="mobile" name="mobile" type="text" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('mobile', $patient->mobile)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('mobile')" />
                    </div>
                    <div>
                        <x-input-label for="mobile_secondary" value="شماره موبایل دوم (اختیاری)" />
                        <x-text-input id="mobile_secondary" name="mobile_secondary" type="text" inputmode="tel" maxlength="11" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('mobile_secondary', $patient->mobile_secondary)" />
                        <x-input-error class="mt-2" :messages="$errors->get('mobile_secondary')" />
                    </div>
                    <div>
                        <x-input-label for="age" value="سن (اختیاری)" />
                        <x-text-input id="age" name="age" type="text" class="mt-1 block w-full" :value="old('age', $patient->age)" />
                        <x-input-error class="mt-2" :messages="$errors->get('age')" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
                    <a href="{{ $backUrl }}" class="btn-secondary !py-2 !px-4 !text-sm">انصراف</a>
                    <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
