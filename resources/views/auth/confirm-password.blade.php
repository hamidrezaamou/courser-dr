<x-guest-layout title="تایید رمز عبور">
    <div class="auth-head">
        <h2 class="auth-head__title">تایید رمز عبور</h2>
        <p class="auth-head__desc">این بخش محافظت‌شده است. برای ادامه رمز عبور خود را دوباره وارد کنید.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
        @csrf

        <div>
            <x-input-label for="password" value="رمز عبور" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" autofocus />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-primary-button class="w-full justify-center">
            تایید و ادامه
        </x-primary-button>
    </form>
</x-guest-layout>
