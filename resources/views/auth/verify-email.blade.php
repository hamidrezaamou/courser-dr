<x-guest-layout title="تایید ایمیل">
    <div class="auth-head">
        <h2 class="auth-head__title">تایید نشانی ایمیل</h2>
        <p class="auth-head__desc">
            پیش از شروع، لطفاً روی لینکی که به ایمیل شما فرستادیم کلیک کنید. اگر ایمیل را دریافت نکردید، دوباره برایتان می‌فرستیم.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="flash flash--success mb-4">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span class="flash__body">لینک تایید تازه‌ای به ایمیل ثبت‌شده شما ارسال شد.</span>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <x-primary-button class="w-full justify-center">
            ارسال دوباره ایمیل تایید
        </x-primary-button>
    </form>

    <div class="auth-foot">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="auth-link">خروج از حساب</button>
        </form>
    </div>
</x-guest-layout>
