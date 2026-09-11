<section>
    <header class="section-head">
        <h2 class="section-head__title">تغییر رمز عبور</h2>
        <p class="section-head__desc">برای امنیت حساب، یک رمز طولانی و غیرقابل حدس انتخاب کنید.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="grid gap-4">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="رمز عبور فعلی" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="رمز عبور جدید" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="تکرار رمز عبور جدید" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button>ذخیره رمز عبور</x-primary-button>

            @if (session('status') === 'password-updated')
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
