<x-guest-layout title="رمز عبور جدید">
    <div class="auth-head">
        <h2 class="auth-head__title">انتخاب رمز عبور جدید</h2>
        <p class="auth-head__desc">رمز تازه‌ای برای حساب خود انتخاب کنید.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="auth-form">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="ایمیل" />
            <x-text-input id="email" type="email" name="email" dir="ltr" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="رمز عبور جدید" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="تکرار رمز عبور" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="w-full justify-center">
            ذخیره رمز عبور
        </x-primary-button>
    </form>
</x-guest-layout>
