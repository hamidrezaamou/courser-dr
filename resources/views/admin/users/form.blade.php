<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">{{ $mode === 'create' ? 'کاربر جدید' : 'ویرایش کاربر' }}</h2>
            <a href="{{ route('admin.users.index') }}" class="btn-ghost">بازگشت به فهرست</a>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'users'" />
            <x-flash />

            <form
                method="POST"
                action="{{ $mode === 'create' ? route('admin.users.store') : route('admin.users.update', $user) }}"
                class="admin-panel mx-auto max-w-2xl space-y-4"
            >
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div>
                    <x-input-label value="نام و نام خانوادگی" />
                    <x-text-input name="name" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label value="کد ملی (نام کاربری ورود)" />
                        <x-text-input name="national_code" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('national_code', $user->national_code)" required />
                        <x-input-error :messages="$errors->get('national_code')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="موبایل" />
                        <x-text-input name="mobile" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('mobile', $user->mobile)" />
                        <x-input-error :messages="$errors->get('mobile')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label value="نقش" />
                    <select name="role" class="field-input mt-1" required>
                        @foreach ($roleLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="password" :value="$mode === 'create' ? 'رمز عبور' : 'رمز عبور جدید (اختیاری)'" />
                        <x-text-input
                            id="password"
                            name="password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            :required="$mode === 'create'"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="تکرار رمز عبور" />
                        <x-text-input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            :required="$mode === 'create'"
                        />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary !py-2 !px-4 !text-sm">انصراف</a>
                    <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">
                        {{ $mode === 'create' ? 'ایجاد کاربر' : 'ذخیره تغییرات' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
