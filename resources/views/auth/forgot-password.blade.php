<x-guest-layout title="بازیابی رمز عبور">
    <div class="auth-head">
        <h2 class="auth-head__title">بازیابی رمز عبور</h2>
        <p class="auth-head__desc">ایمیل خود را وارد کنید تا لینک ساخت رمز جدید برایتان ارسال شود.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf

        <div>
            <x-input-label for="email" value="ایمیل" />
            <x-text-input id="email" type="email" name="email" dir="ltr" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button class="w-full justify-center">
            ارسال لینک بازیابی
        </x-primary-button>
    </form>

    <p class="auth-foot">
        <a class="auth-link" href="{{ route('login') }}">بازگشت به صفحه ورود</a>
    </p>
</x-guest-layout>
