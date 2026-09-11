<section>
    <header class="section-head">
        <h2 class="section-head__title">حذف حساب کاربری</h2>
        <p class="section-head__desc">
            با حذف حساب، تمام داده‌های مربوط به آن برای همیشه پاک می‌شود. پیش از حذف، از اطلاعاتی که لازم دارید نسخه پشتیبان بگیرید.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >حذف حساب کاربری</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <header class="section-head">
                <h2 class="section-head__title">از حذف حساب مطمئن هستید؟</h2>
                <p class="section-head__desc">
                    این کار برگشت‌پذیر نیست. برای تایید، رمز عبور خود را وارد کنید.
                </p>
            </header>

            <div>
                <x-input-label for="password" value="رمز عبور" class="sr-only" />
                <x-text-input id="password" name="password" type="password" placeholder="رمز عبور" />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    انصراف
                </x-secondary-button>

                <x-danger-button>
                    حذف حساب
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
