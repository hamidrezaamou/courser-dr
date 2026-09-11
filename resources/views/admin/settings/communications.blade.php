<x-app-layout body-class="is-admin">
    <x-slot name="header">
        <div class="admin-header">
            <div>
                <p class="admin-header__eyebrow">مدیریت کل سایت</p>
                <h2 class="admin-header__title">ارتباطات</h2>
            </div>
            <div class="admin-header__actions">
                <a href="{{ route('admin.index') }}" class="btn-ghost !text-xs !px-3 !py-1.5">نمای کلی</a>
                <a href="{{ route('appointments.board') }}" class="btn-secondary btn-primary--compact">نوبت‌ها</a>
            </div>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'comms'" />
            <x-flash />

            <form method="POST" action="{{ route('admin.settings.communications.update') }}" class="admin-panel mx-auto max-w-3xl space-y-5">
                @csrf
                @method('PUT')

                <section class="space-y-3">
                    <h3 class="admin-panel__title">اتصال وب‌سایت (رزرو آنلاین)</h3>
                    <p class="admin-panel__hint">
                        سایت <span dir="ltr">dr-marziyehfotouhi.ir</span> از این API برای نمایش روزها و ساعت‌های ویزیت استفاده می‌کند.
                        کلید باید با <span dir="ltr">EHR_API_KEY</span> روی سایت یکسان باشد.
                    </p>
                    @unless($values['website_api_key_set'])
                        <p class="text-xs font-bold text-red-600">کلید API تنظیم نشده — رزرو آنلاین روی سایت کار نمی‌کند.</p>
                    @endunless
                    <div>
                        <x-input-label value="کلید API وب‌سایت (WEBSITE_API_KEY)" />
                        <x-text-input name="website_api_key" class="mt-1 block w-full ltr-data font-mono" dir="ltr" :value="old('website_api_key', $values['website_api_key'])" placeholder="dr-website-mramo-2026" autocomplete="off" />
                        <p class="admin-panel__hint">
                            @if($values['website_api_key_set'])
                                کلید ذخیره شده است. برای تغییر، مقدار جدید وارد کنید.
                            @else
                                مثال: <span dir="ltr">dr-website-mramo-2026</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <x-input-label value="عنوان مطب در فرم رزرو سایت" />
                        <x-text-input name="website_clinic_label" class="mt-1 block w-full" :value="old('website_clinic_label', $values['website_clinic_label'])" />
                    </div>
                </section>

                <hr style="border-color: var(--line);">

                <section class="space-y-3">
                    <h3 class="admin-panel__title">یادآوری خودکار</h3>
                    <label class="admin-check">
                        <input type="checkbox" name="reminders_enabled" value="1" @checked(old('reminders_enabled', $values['reminders_enabled']))>
                        <span>یادآوری نوبت‌ها فعال باشد</span>
                    </label>
                    <div>
                        <x-input-label value="چند روز قبل از نوبت ارسال شود" />
                        <x-text-input name="days_ahead" type="number" min="0" max="14" class="mt-1 block w-40" :value="old('days_ahead', $values['days_ahead'])" required />
                        <x-input-error :messages="$errors->get('days_ahead')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="ساعت ارسال خودکار یادآوری" />
                        <x-text-input name="send_time" type="time" class="mt-1 block w-40 ltr-data" dir="ltr" :value="old('send_time', $values['send_time'])" required />
                        <p class="admin-panel__hint">هر روز در این ساعت، یادآوری نوبت فردا (یا طبق «چند روز قبل») ارسال می‌شود.</p>
                        <x-input-error :messages="$errors->get('send_time')" class="mt-1" />
                    </div>
                </section>

                <hr style="border-color: var(--line);">

                <section class="space-y-3">
                    <h3 class="admin-panel__title">تلگرام</h3>
                    <label class="admin-check">
                        <input type="checkbox" name="telegram_enabled" value="1" @checked(old('telegram_enabled', $values['telegram_enabled']))>
                        <span>ارسال یادآوری به چت کلینیک در تلگرام</span>
                    </label>
                    <div>
                        <x-input-label value="Bot Token" />
                        <x-text-input name="telegram_bot_token" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('telegram_bot_token', $values['telegram_bot_token'])" placeholder="********" autocomplete="off" />
                        <p class="admin-panel__hint">
                            @if($values['telegram_bot_token_set'])
                                توکن ذخیره شده است. برای تغییر، مقدار جدید بگذارید؛ برای نگه داشتن همان، خالی/ستاره بماند.
                            @else
                                هنوز توکنی ذخیره نشده.
                            @endif
                        </p>
                    </div>
                    <div>
                        <x-input-label value="Chat ID" />
                        <x-text-input name="telegram_chat_id" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('telegram_chat_id', $values['telegram_chat_id'])" />
                    </div>
                </section>

                <hr style="border-color: var(--line);">

                <section class="space-y-3">
                    <h3 class="admin-panel__title">پیامک (SMS.ir)</h3>
                    <label class="admin-check">
                        <input type="checkbox" name="sms_enabled" value="1" @checked(old('sms_enabled', $values['sms_enabled']))>
                        <span>ارسال پیامک فعال باشد</span>
                    </label>
                    <div>
                        <x-input-label value="درایور" />
                        <select name="sms_driver" class="field-input mt-1">
                            <option value="log" @selected(old('sms_driver', $values['sms_driver']) === 'log')>log (فقط لاگ — بدون ارسال واقعی)</option>
                            <option value="smsir" @selected(old('sms_driver', $values['sms_driver']) === 'smsir')>smsir (ارسال واقعی)</option>
                            <option value="http" @selected(old('sms_driver', $values['sms_driver']) === 'http')>http (سفارشی)</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label value="API Key" />
                        <x-text-input name="smsir_api_key" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('smsir_api_key', $values['smsir_api_key'])" placeholder="********" autocomplete="off" />
                        <p class="admin-panel__hint">
                            @if($values['smsir_api_key_set'])
                                کلید ذخیره شده است. برای تغییر مقدار جدید وارد کنید.
                            @else
                                هنوز کلید SMS.ir ذخیره نشده.
                            @endif
                        </p>
                    </div>
                    <div>
                        <x-input-label value="شماره خط (Line Number)" />
                        <x-text-input name="smsir_line_number" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('smsir_line_number', $values['smsir_line_number'])" />
                    </div>
                </section>

                <hr style="border-color: var(--line);">

                <section class="space-y-3">
                    <h3 class="admin-panel__title">پیامک خودکار ثبت ویزیت</h3>
                    <p class="admin-panel__hint">
                        فقط برای <strong>نوبت ویزیت</strong> (ثبت دستی یا از وب‌سایت).
                        <strong>متن پیامک</strong> از بخش <a href="{{ route('times.index') }}" class="underline">تایم‌ها</a> — همان «متن پیامک آماده» هر روز — خوانده می‌شود.
                    </p>
                    <label class="admin-check">
                        <input type="checkbox" name="visit_sms_on_booking" value="1" @checked(old('visit_sms_on_booking', $values['visit_sms_on_booking']))>
                        <span>ارسال خودکار پیامک پس از ثبت ویزیت</span>
                    </label>
                    @unless($values['sms_enabled'])
                        <p class="text-xs font-bold text-amber-700">برای ارسال واقعی، «ارسال پیامک فعال باشد» و درایور smsir را هم فعال کنید.</p>
                    @endunless
                </section>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">ذخیره ارتباطات</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
