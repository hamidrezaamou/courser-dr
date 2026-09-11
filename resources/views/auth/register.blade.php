<x-guest-layout title="ثبت‌نام">
    <div class="auth-head">
        <h2 class="auth-head__title">ساخت حساب کاربری</h2>
        <p class="auth-head__desc">برای دسترسی به پرونده‌ها یک حساب بسازید.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf

        <div>
            <x-input-label for="name" value="نام و نام خانوادگی" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="ایمیل" />
            <x-text-input id="email" type="email" name="email" dir="ltr" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="رمز عبور" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="تکرار رمز عبور" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="w-full justify-center">
            ثبت‌نام
        </x-primary-button>
    </form>

    <p class="auth-foot">
        قبلاً حساب ساخته‌اید؟
        <a class="auth-link" href="{{ route('login') }}">ورود به سامانه</a>
    </p>
</x-guest-layout>
