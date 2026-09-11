<x-guest-layout title="ورود">
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="auth-head">
        <h2 class="auth-head__title">ورود به سامانه</h2>
        <p class="auth-head__desc">کد ملی و رمز عبور خود را وارد کنید.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div>
            <x-input-label for="national_code" value="کد ملی" />
            <x-text-input id="national_code" type="text" name="national_code" dir="ltr" inputmode="numeric" :value="old('national_code')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('national_code')" />
        </div>

        <div>
            <x-input-label for="password" value="رمز عبور" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="form-check">
            <input id="remember_me" type="checkbox" name="remember">
            <span>مرا به خاطر بسپار</span>
        </label>

        <x-primary-button class="w-full justify-center">
            ورود به سامانه
        </x-primary-button>
    </form>

    @if (Route::has('password.request'))
        <p class="auth-foot">
            رمز عبور خود را فراموش کرده‌اید؟
            <a class="auth-link" href="{{ route('password.request') }}">بازیابی رمز عبور</a>
        </p>
    @endif
</x-guest-layout>
