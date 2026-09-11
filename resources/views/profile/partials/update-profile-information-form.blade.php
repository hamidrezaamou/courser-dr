<section>
    <header class="section-head">
        <h2 class="section-head__title">اطلاعات حساب</h2>
        <p class="section-head__desc">نام و نشانی ایمیل حساب خود را به‌روز کنید.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="grid gap-4">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="نام و نام خانوادگی" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="ایمیل" />
            <x-text-input id="email" name="email" type="email" dir="ltr" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="form-hint">
                    ایمیل شما هنوز تایید نشده است.
                    <button form="send-verification" class="auth-link">ارسال دوباره ایمیل تایید</button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="form-hint" style="color: var(--success); font-weight: 700;">
                        لینک تایید تازه‌ای به ایمیل شما ارسال شد.
                    </p>
                @endif
            @endif
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button>ذخیره تغییرات</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-sm font-bold"
                    style="color: var(--success);"
                >ذخیره شد.</p>
            @endif
        </div>
    </form>
</section>
