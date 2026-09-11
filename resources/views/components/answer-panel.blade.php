@props([
    'mobile' => '',
    'patientName' => '',
])

@if(\App\Support\FeatureFlags::enabled('features.ready_answers') && auth()->user()?->isStaff())
@php
    $smsEnabled = (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir';
    $tagChips = \App\Support\MessageTags::chips();
@endphp

{{-- Self-contained ready answers library: browse, compose, edit and send without leaving the page. --}}
<div
    class="ans-root"
    x-data="window.readyAnswersPanel(@js([
        'mobile' => $mobile,
        'patientName' => $patientName,
        'smsEnabled' => $smsEnabled,
        'today' => \App\Support\Jalali::format(now(), 'Y/m/d'),
        'clinic' => config('app.name', 'مطب'),
        'personVars' => $tagChips['person'],
        'bookingVars' => $tagChips['booking'],
        'bookingKeys' => \App\Support\MessageTags::bookingKeys(),
        'indexUrl' => route('ready-answers.index'),
        'storeUrl' => route('ready-answers.store'),
        'sendUrl' => route('ready-answers.send'),
        'baseUrl' => url('/ready-answers'),
        'bookingsUrl' => url('/ready-answers/bookings'),
    ]))"
    @open-ready-answers.window="openPanel($event.detail)"
    @close-ready-answers.window="closePanel()"
    @keydown.escape.window="if (bulkConfirmOpen) closeBulkConfirm(); else if (pickerOpen) closePicker(); else if (open) closePanel()"
>
    <div
        class="ans-overlay"
        x-show="open"
        x-cloak
        @click.self="closePanel()"
    >
        <div class="ans-sheet" role="dialog" aria-modal="true" aria-labelledby="ans-title" @click.stop>

            {{-- ---------- header ---------- --}}
            <header class="ans-head">
                <span class="ans-head__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                </span>
                <div class="ans-head__text">
                    <h3 class="ans-head__title" id="ans-title">پاسخ‌های آماده</h3>
                    <p class="ans-head__sub" x-text="headSubtitle()"></p>
                </div>
                <button type="button" class="ans-close" @click="closePanel()" aria-label="بستن پنل">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </header>

            {{-- ---------- recipient: shared by every send action, so it stays pinned ---------- --}}
            <div class="ans-recipient">
                <div class="ans-bulk-banner" x-show="bulkMode" x-cloak>
                    <strong x-text="faNum(bulkTargets().length) + ' بیمار برای ارسال گروهی'"></strong>
                    <span>پیش‌نمایش تگ‌ها با یک بیمار نمونه است؛ هنگام ارسال هر نفر پیام خودش را می‌گیرد.</span>
                    <button type="button" class="ans-bulk-banner__shuffle" @click="pickBulkSample()" x-show="bulkItems.length > 1">نمونه دیگر</button>
                </div>

                <div class="ans-recip__row" x-show="!bulkMode">
                    <span class="ans-patient" x-show="patientName" x-cloak>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0" />
                        </svg>
                        <span x-text="patientName"></span>
                    </span>
                    <div class="ans-recip__field">
                        <input
                            class="ans-input"
                            type="tel"
                            dir="ltr"
                            inputmode="numeric"
                            x-model="targetMobile"
                            x-ref="mobileInput"
                            placeholder="09123456789"
                            aria-label="شماره مقصد"
                        >
                        <span
                            class="ans-recip__dot"
                            :class="!targetMobile.trim() ? '' : (mobileValid() ? 'is-valid' : 'is-invalid')"
                            :title="!targetMobile.trim() ? 'شماره وارد نشده' : (mobileValid() ? 'شماره معتبر است' : 'شماره نامعتبر')"
                        ></span>
                    </div>
                </div>

                <div class="ans-recip__row" x-show="bulkMode" x-cloak>
                    <span class="ans-patient">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0" />
                        </svg>
                        <span x-text="'نمونه: ' + (patientName || '—')"></span>
                    </span>
                    <div class="ans-recip__field">
                        <input class="ans-input" type="tel" dir="ltr" :value="targetMobile" disabled aria-label="موبایل نمونه">
                    </div>
                </div>

                <div class="ans-booking" x-show="!bulkMode && (patientId || booking)" x-cloak>
                    <button type="button" class="ans-booking__chip" @click="openPicker()" :disabled="!patientId">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M4.5 6.75h15A1.5 1.5 0 0121 8.25v10.5a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 18.75V8.25a1.5 1.5 0 011.5-1.5z" />
                        </svg>
                        <span class="ans-booking__text">
                            <strong x-text="booking ? booking.label : 'انتخاب عمل یا ویزیت'"></strong>
                            <small x-show="booking" x-cloak x-text="booking && booking.meta"></small>
                            <small x-show="!booking && patientId" x-cloak>برای پر کردن تگ‌های نوبت، یک مورد را انتخاب کنید</small>
                        </span>
                    </button>
                    <button type="button" class="ans-booking__clear" x-show="booking && patientId" x-cloak @click="clearBooking()" title="حذف انتخاب">×</button>
                </div>

                <div class="ans-booking" x-show="bulkMode && booking" x-cloak>
                    <div class="ans-booking__chip" style="cursor:default">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M4.5 6.75h15A1.5 1.5 0 0121 8.25v10.5a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 18.75V8.25a1.5 1.5 0 011.5-1.5z" />
                        </svg>
                        <span class="ans-booking__text">
                            <strong x-text="booking.label || 'نوبت نمونه'"></strong>
                            <small x-text="booking.meta || ''"></small>
                        </span>
                    </div>
                </div>

                <div class="ans-tabs" role="tablist">
                    <button type="button" class="ans-tab" :class="tab === 'list' && 'is-active'" role="tab" :aria-selected="tab === 'list'" @click="tab = 'list'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" />
                        </svg>
                        فهرست
                    </button>
                    <button type="button" class="ans-tab" :class="tab === 'compose' && 'is-active'" role="tab" :aria-selected="tab === 'compose'" @click="tab = 'compose'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h12M12 6v12" />
                        </svg>
                        پیام دلخواه
                    </button>
                    <button type="button" class="ans-tab" :class="tab === 'form' && 'is-active'" role="tab" :aria-selected="tab === 'form'" @click="tab = 'form'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65M4 20l.9-3.6L15.4 5.9a1.4 1.4 0 012 0l.7.7a1.4 1.4 0 010 2L7.6 19.1 4 20z" />
                        </svg>
                        <span x-text="editingId ? 'ویرایش' : 'پاسخ جدید'"></span>
                    </button>
                </div>

                <p class="ans-alert" :class="msgKind === 'err' ? 'ans-alert--err' : 'ans-alert--ok'" x-show="msg" x-cloak>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="msgKind === 'err' ? 'M12 8v5m0 3h.01M10.3 3.9L2.6 17a2 2 0 001.7 3h15.4a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z' : 'M5 13l4 4L19 7'" />
                    </svg>
                    <span x-text="msg"></span>
                </p>

                <p class="ans-alert ans-alert--warn" x-show="!smsEnabled" x-cloak>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3h.01M10.3 3.9L2.6 17a2 2 0 001.7 3h15.4a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" />
                    </svg>
                    <span>ارسال پیامک در تنظیمات غیرفعال است. کپی و اشتراک‌گذاری کار می‌کند.</span>
                </p>
            </div>

            <div class="ans-scroll" x-ref="scroll">

                {{-- ---------- tab: list ---------- --}}
                <template x-if="tab === 'list'">
                    <div class="contents">
                        <div class="ans-search">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                            </svg>
                            <input type="search" class="ans-input" x-model="q" @input.debounce.300ms="refresh()" placeholder="جستجو در عنوان، متن یا دسته..." aria-label="جستجوی پاسخ‌ها">
                        </div>

                        <div class="ans-filters">
                            <button type="button" class="ans-chip" :class="category === '' && sort !== 'used' && 'is-active'" @click="category = ''; sort = 'smart'; refresh()">همه</button>
                            <button type="button" class="ans-chip" :class="sort === 'used' && 'is-active'" @click="sort = 'used'; refresh()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 17l5-5 4 4 8-8" />
                                </svg>
                                پرکاربردها
                            </button>
                            <template x-for="cat in categories" :key="cat">
                                <button type="button" class="ans-chip" :class="category === cat && 'is-active'" @click="category = (category === cat ? '' : cat); refresh()" x-text="cat"></button>
                            </template>
                        </div>

                        <div class="ans-skeleton" x-show="loading" x-cloak>
                            <div class="ans-skeleton__row"></div>
                            <div class="ans-skeleton__row"></div>
                            <div class="ans-skeleton__row"></div>
                        </div>

                        <template x-for="item in items" :key="item.id">
                            <article class="ans-item" :class="item.is_pinned && 'is-pinned'" x-show="!loading">
                                <div class="ans-item__top">
                                    <button
                                        type="button"
                                        class="ans-pin"
                                        :class="item.is_pinned && 'is-on'"
                                        @click="togglePin(item)"
                                        :aria-label="item.is_pinned ? 'برداشتن سنجاق' : 'سنجاق کردن'"
                                        :title="item.is_pinned ? 'برداشتن سنجاق' : 'سنجاق به بالای فهرست'"
                                    >
                                        <svg viewBox="0 0 24 24" :fill="item.is_pinned ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l2.4 5.3 5.6.6-4.2 3.9 1.2 5.7L12 15.7 7 18.5l1.2-5.7L4 8.9l5.6-.6L12 3z" />
                                        </svg>
                                    </button>
                                    <span class="ans-item__label" :class="!item.title && 'ans-item__label--empty'" x-text="item.title || 'بدون عنوان'"></span>
                                    <span class="ans-tag" x-show="item.category" x-cloak x-text="item.category"></span>
                                    <span class="ans-uses" x-show="item.usage_count > 0" x-cloak :title="'تا کنون ' + item.usage_count + ' بار استفاده شده'">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 17l5-5 4 4 8-8" />
                                        </svg>
                                        <span x-text="item.usage_count"></span>
                                    </span>
                                </div>

                                <div class="ans-item__body" :class="!expanded[item.id] && isLong(item.body) && 'is-clamped'" x-text="resolve(item.body)"></div>

                                <button type="button" class="ans-more" x-show="isLong(item.body)" x-cloak @click="expanded[item.id] = !expanded[item.id]" x-text="expanded[item.id] ? 'کمتر' : 'نمایش کامل'"></button>

                                <div class="ans-meter">
                                    <span x-text="meterLabel(resolve(item.body))"></span>
                                    <span class="ans-meter__parts" :class="smsParts(resolve(item.body)) > 2 && 'is-high'" x-text="smsParts(resolve(item.body)) + ' پیامک'"></span>
                                </div>

                                <div class="ans-acts">
                                    <button type="button" class="ans-act ans-act--send" @click="sendItem(item)" :disabled="busy || !smsEnabled">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15.5-7.5L4.5 4.5 4.5 10l9 2-9 2z" />
                                        </svg>
                                        <span x-text="sendingId === item.id ? 'در حال ارسال...' : (bulkMode ? 'ارسال گروهی' : 'ارسال')"></span>
                                    </button>
                                    <button type="button" class="ans-act" :class="copiedId === item.id && 'is-done'" @click="copyItem(item)" title="کپی متن">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" :d="copiedId === item.id ? 'M5 13l4 4L19 7' : 'M9 9V5.6c0-.9.7-1.6 1.6-1.6h7.8c.9 0 1.6.7 1.6 1.6v7.8c0 .9-.7 1.6-1.6 1.6H15M5.6 9h7.8c.9 0 1.6.7 1.6 1.6v7.8c0 .9-.7 1.6-1.6 1.6H5.6c-.9 0-1.6-.7-1.6-1.6v-7.8C4 9.7 4.7 9 5.6 9z'" />
                                        </svg>
                                        <span x-text="copiedId === item.id ? 'کپی شد' : 'کپی'"></span>
                                    </button>
                                    <button type="button" class="ans-act ans-act--icon" @click="shareItem(item)" title="اشتراک‌گذاری" aria-label="اشتراک‌گذاری">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.2 13.4l9.6 4.8M16.8 5.8L7.2 10.6M18 8a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM6 14.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM18 21a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                                        </svg>
                                    </button>
                                    <button type="button" class="ans-act ans-act--icon" @click="startEdit(item)" title="ویرایش" aria-label="ویرایش">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65M4 20l.9-3.6L15.4 5.9a1.4 1.4 0 012 0l.7.7a1.4 1.4 0 010 2L7.6 19.1 4 20z" />
                                        </svg>
                                    </button>
                                    <button type="button" class="ans-act ans-act--icon" @click="duplicateItem(item)" title="ساخت رونوشت" aria-label="ساخت رونوشت">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 4h9a3 3 0 013 3v9M6 8h9a2 2 0 012 2v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8a2 2 0 012-2z" />
                                        </svg>
                                    </button>
                                    <button type="button" class="ans-act ans-act--icon ans-act--danger" @click="confirmId = (confirmId === item.id ? null : item.id)" title="حذف" aria-label="حذف">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M10 7V5.5c0-.6.4-1 1-1h2c.6 0 1 .4 1 1V7m-7 0l.8 12c0 .6.5 1 1 1h6.4c.5 0 1-.4 1-1L18 7" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="ans-confirm" x-show="confirmId === item.id" x-cloak>
                                    <p>این پاسخ حذف شود؟</p>
                                    <button type="button" class="ans-confirm__yes" @click="removeItem(item)" :disabled="busy">بله، حذف کن</button>
                                    <button type="button" class="ans-confirm__no" @click="confirmId = null">انصراف</button>
                                </div>
                            </article>
                        </template>

                        <div class="ans-empty" x-show="!loading && !items.length" x-cloak>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                            </svg>
                            <p class="ans-empty__title" x-text="isFiltered() ? 'چیزی پیدا نشد' : 'هنوز پاسخ آماده‌ای ندارید'"></p>
                            <p class="ans-empty__text" x-text="isFiltered() ? 'عبارت جستجو یا دسته را تغییر دهید.' : 'متن‌هایی که زیاد برای بیماران می‌فرستید را یک بار ذخیره کنید تا همیشه یک کلیک فاصله داشته باشند.'"></p>
                            <button type="button" class="ans-btn" style="width:auto" x-show="!isFiltered()" @click="newAnswer()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" d="M6 12h12M12 6v12" />
                                </svg>
                                ساخت اولین پاسخ
                            </button>
                            <button type="button" class="ans-btn ans-btn--ghost" style="width:auto" x-show="isFiltered()" @click="clearFilters()">پاک کردن فیلترها</button>
                        </div>
                    </div>
                </template>

                {{-- ---------- tab: custom message ---------- --}}
                <template x-if="tab === 'compose'">
                    <div class="ans-card">
                        <h4 class="ans-card__title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15.5-7.5L4.5 4.5 4.5 10l9 2-9 2z" />
                            </svg>
                            ارسال پیامک دلخواه
                        </h4>

                        <textarea class="ans-input" rows="4" x-model="customMessage" x-ref="customBody" placeholder="متن پیامک را بنویسید..." maxlength="1000" aria-label="متن پیامک دلخواه"></textarea>

                        <div class="ans-vars">
                            <span class="ans-vars__hint">بیمار:</span>
                            <template x-for="v in personVars" :key="v.token">
                                <button type="button" class="ans-var" :title="v.label" @click="insertVar('customBody', 'customMessage', v.token)" x-text="v.label"></button>
                            </template>
                        </div>
                        <div class="ans-vars">
                            <span class="ans-vars__hint">نوبت:</span>
                            <template x-for="v in bookingVars" :key="v.token">
                                <button type="button" class="ans-var ans-var--booking" :class="!booking && 'needs-pick'" :title="v.label" @click="insertVar('customBody', 'customMessage', v.token, true)" x-text="v.label"></button>
                            </template>
                        </div>

                        <div class="ans-meter">
                            <span x-text="meterLabel(resolve(customMessage))"></span>
                            <span class="ans-meter__parts" :class="smsParts(resolve(customMessage)) > 2 && 'is-high'" x-text="smsParts(resolve(customMessage)) + ' پیامک'"></span>
                        </div>

                        <div class="ans-preview" x-show="hasVars(customMessage)" x-cloak>
                            <span class="ans-preview__cap">پیش‌نمایش متن نهایی</span>
                            <span x-text="resolve(customMessage)"></span>
                        </div>

                        <div class="ans-btn-row">
                            <button type="button" class="ans-btn" @click="sendCustom()" :disabled="busy || !smsEnabled || !customMessage.trim()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15.5-7.5L4.5 4.5 4.5 10l9 2-9 2z" />
                                </svg>
                                <span x-text="busy ? 'در حال ارسال...' : (bulkMode ? 'ارسال گروهی' : 'ارسال پیامک')"></span>
                            </button>
                            <button type="button" class="ans-btn ans-btn--ghost" style="width:auto" @click="saveComposeAsTemplate()" :disabled="!customMessage.trim()" title="ذخیره این متن به عنوان پاسخ آماده">
                                ذخیره در فهرست
                            </button>
                        </div>
                    </div>
                </template>

                {{-- ---------- tab: add / edit ---------- --}}
                <template x-if="tab === 'form'">
                    <div class="ans-card">
                        <h4 class="ans-card__title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65M4 20l.9-3.6L15.4 5.9a1.4 1.4 0 012 0l.7.7a1.4 1.4 0 010 2L7.6 19.1 4 20z" />
                            </svg>
                            <span x-text="editingId ? 'ویرایش پاسخ آماده' : 'افزودن پاسخ آماده'"></span>
                        </h4>

                        <label class="ans-label" for="ans-form-title">عنوان (اختیاری)</label>
                        <input id="ans-form-title" class="ans-input" type="text" x-model="formTitle" placeholder="مثلاً: آماده‌سازی قبل از عمل" maxlength="120">

                        <label class="ans-label" style="margin-top:0.5rem" for="ans-form-cat">دسته (اختیاری)</label>
                        <input id="ans-form-cat" class="ans-input" type="text" x-model="formCategory" list="ans-categories" placeholder="مثلاً: نوبت‌دهی" maxlength="60">
                        <datalist id="ans-categories">
                            <template x-for="cat in categories" :key="cat">
                                <option :value="cat"></option>
                            </template>
                        </datalist>

                        <label class="ans-label" style="margin-top:0.5rem" for="ans-form-body">متن پاسخ</label>
                        <textarea id="ans-form-body" class="ans-input" rows="5" x-model="formBody" x-ref="formBody" placeholder="متن پاسخ را بنویسید..." maxlength="2000"></textarea>

                        <div class="ans-vars">
                            <span class="ans-vars__hint">بیمار:</span>
                            <template x-for="v in personVars" :key="'f-'+v.token">
                                <button type="button" class="ans-var" :title="v.label" @click="insertVar('formBody', 'formBody', v.token)" x-text="v.label"></button>
                            </template>
                        </div>
                        <div class="ans-vars">
                            <span class="ans-vars__hint">نوبت:</span>
                            <template x-for="v in bookingVars" :key="'fb-'+v.token">
                                <button type="button" class="ans-var ans-var--booking" :class="!booking && 'needs-pick'" :title="v.label" @click="insertVar('formBody', 'formBody', v.token, true)" x-text="v.label"></button>
                            </template>
                        </div>

                        <div class="ans-meter">
                            <span x-text="meterLabel(resolve(formBody))"></span>
                            <span class="ans-meter__parts" :class="smsParts(resolve(formBody)) > 2 && 'is-high'" x-text="smsParts(resolve(formBody)) + ' پیامک'"></span>
                        </div>

                        <div class="ans-preview" x-show="hasVars(formBody)" x-cloak>
                            <span class="ans-preview__cap">پیش‌نمایش متن نهایی</span>
                            <span x-text="resolve(formBody)"></span>
                        </div>

                        <label class="ans-chip" style="margin-top:0.55rem" :class="formPinned && 'is-active'">
                            <input type="checkbox" x-model="formPinned" style="display:none">
                            <svg viewBox="0 0 24 24" :fill="formPinned ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l2.4 5.3 5.6.6-4.2 3.9 1.2 5.7L12 15.7 7 18.5l1.2-5.7L4 8.9l5.6-.6L12 3z" />
                            </svg>
                            سنجاق به بالای فهرست
                        </label>

                        <div class="ans-btn-row">
                            <button type="button" class="ans-btn" @click="saveForm()" :disabled="busy || !formBody.trim()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-text="busy ? 'در حال ذخیره...' : (editingId ? 'ذخیره تغییرات' : 'ذخیره پاسخ')"></span>
                            </button>
                            <button type="button" class="ans-btn ans-btn--ghost" style="width:auto" @click="resetForm(); tab = 'list'">انصراف</button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="ans-picker" x-show="pickerOpen" x-cloak @click.self="closePicker()">
                <div class="ans-picker__sheet" role="dialog" aria-modal="true" aria-labelledby="ans-picker-title" @click.stop>
                    <header class="ans-picker__head">
                        <h4 id="ans-picker-title">انتخاب عمل یا ویزیت</h4>
                        <button type="button" class="ans-close" @click="closePicker()" aria-label="بستن">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </header>
                    <p class="ans-picker__hint">با انتخاب، تگ‌های تاریخ، ساعت، بیمارستان و نوع عمل از همین نوبت پر می‌شوند.</p>
                    <div class="ans-picker__list">
                        <div class="ans-skeleton" x-show="pickerLoading" x-cloak>
                            <div class="ans-skeleton__row"></div>
                            <div class="ans-skeleton__row"></div>
                        </div>
                        <p class="ans-picker__empty" x-show="!pickerLoading && !bookings.length" x-cloak>عمل یا ویزیتی برای این بیمار ثبت نشده.</p>
                        <template x-for="row in bookings" :key="row.kind + '-' + row.id">
                            <button
                                type="button"
                                class="ans-picker__item"
                                :class="booking && booking.kind === row.kind && booking.id === row.id && 'is-active'"
                                @click="selectBooking(row)"
                            >
                                <span class="ans-picker__kind" :class="row.kind === 'surgery' ? 'is-surgery' : 'is-visit'" x-text="row.kind === 'surgery' ? 'عمل' : 'ویزیت'"></span>
                                <span class="ans-picker__item-text">
                                    <strong x-text="row.label"></strong>
                                    <small x-text="row.meta"></small>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="ans-picker ans-bulk-confirm" x-show="bulkConfirmOpen" x-cloak @click.self="closeBulkConfirm()">
                <div class="ans-picker__sheet" role="dialog" aria-modal="true" aria-labelledby="ans-bulk-confirm-title" @click.stop>
                    <header class="ans-picker__head">
                        <h4 id="ans-bulk-confirm-title">تأیید ارسال گروهی</h4>
                        <button type="button" class="ans-close" @click="closeBulkConfirm()" aria-label="بستن">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </header>
                    <p class="ans-picker__hint">
                        همین قالب برای
                        <strong x-text="faNum(bulkConfirmCount)"></strong>
                        بیمار ارسال می‌شود. هر بیمار با نام، موبایل و نوبت خودش.
                    </p>
                    <div class="ans-bulk-confirm__preview">
                        <span class="ans-preview__cap">نمونه متن نهایی (بیمار نمونه)</span>
                        <span x-text="bulkConfirmPreview"></span>
                    </div>
                    <div class="ans-btn-row" style="padding:0.7rem 0.85rem 0.95rem">
                        <button type="button" class="ans-btn" @click="runBulkSend()" :disabled="busy">
                            <span x-text="busy ? 'در حال ارسال...' : ('بله، ارسال به ' + faNum(bulkConfirmCount) + ' بیمار')"></span>
                        </button>
                        <button type="button" class="ans-btn ans-btn--ghost" style="width:auto" @click="closeBulkConfirm()" :disabled="busy">انصراف</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Stub so launchers work even before Alpine finishes mounting the panel.
    window.ReadyAnswers = window.ReadyAnswers || {
        open: function (detail) {
            window.dispatchEvent(new CustomEvent('open-ready-answers', { detail: detail || {} }));
        },
        close: function () {
            window.dispatchEvent(new CustomEvent('close-ready-answers'));
        },
    };

    window.readyAnswersPanel = function readyAnswersPanel(cfg) {
        return {
            open: false,
            tab: 'list',
            loading: false,
            busy: false,
            msg: '',
            msgKind: 'ok',
            q: '',
            category: '',
            sort: 'smart',
            items: [],
            categories: [],
            total: 0,
            expanded: {},
            confirmId: null,
            copiedId: null,
            sendingId: null,
            customMessage: '',
            targetMobile: cfg.mobile || '',
            patientName: cfg.patientName || '',
            patientId: null,
            nationalCode: '',
            mobileSecondary: '',
            booking: null,
            bookings: [],
            bookingsCache: {},
            pickerOpen: false,
            pickerLoading: false,
            pendingInsert: null,
            pendingSend: null,
            bulkMode: false,
            bulkItems: [],
            bulkConfirmOpen: false,
            bulkConfirmTemplate: '',
            bulkConfirmPreview: '',
            bulkConfirmCount: 0,
            bulkConfirmAnswerId: null,
            smsEnabled: !!cfg.smsEnabled,
            personVars: cfg.personVars || [],
            bookingVars: cfg.bookingVars || [],
            bookingKeys: cfg.bookingKeys || [],
            formTitle: '',
            formCategory: '',
            formBody: '',
            formPinned: false,
            editingId: null,
            msgTimer: null,

            init() {
                const self = this;
                window.ReadyAnswers = {
                    open: (detail) => self.openPanel(detail || {}),
                    close: () => self.closePanel(),
                };
                if (window.OverlayHistory) {
                    window.OverlayHistory.bindWatch(this, 'open', 'ready-answers');
                }
            },

            /* ---------- panel lifecycle ---------- */
            openPanel(detail) {
                this.open = true;
                this.tab = 'list';
                this.confirmId = null;
                this.pickerOpen = false;
                this.bulkConfirmOpen = false;
                this.pendingInsert = null;
                this.pendingSend = null;
                this.msg = '';
                this.bookings = [];

                this.bulkMode = !!(detail && detail.bulk && Array.isArray(detail.bulkItems) && detail.bulkItems.length);
                this.bulkItems = this.bulkMode ? detail.bulkItems.slice() : [];
                if (detail && typeof detail.smsEnabled === 'boolean') {
                    this.smsEnabled = detail.smsEnabled;
                } else {
                    this.smsEnabled = !!cfg.smsEnabled;
                }

                if (this.bulkMode) {
                    this.pickBulkSample(true);
                } else {
                    if (detail && detail.mobile) this.targetMobile = detail.mobile;
                    else if (!this.targetMobile) this.targetMobile = cfg.mobile || '';
                    if (detail && detail.patientName) this.patientName = detail.patientName;
                    this.patientId = detail && detail.patientId ? detail.patientId : null;
                    this.nationalCode = (detail && detail.nationalCode) || '';
                    this.mobileSecondary = (detail && detail.mobileSecondary) || '';
                    this.booking = (detail && detail.booking) || null;
                }

                document.body.style.overflow = 'hidden';
                this.refresh();
                this.$nextTick(() => this.$refs.scroll?.scrollTo({ top: 0 }));
            },

            closePanel() {
                this.open = false;
                this.confirmId = null;
                this.pickerOpen = false;
                this.bulkConfirmOpen = false;
                this.pendingInsert = null;
                this.pendingSend = null;
                this.bulkMode = false;
                this.bulkItems = [];
                document.body.style.overflow = '';
            },

            headSubtitle() {
                if (this.bulkMode) {
                    return 'ارسال گروهی · پیش‌نمایش با بیمار نمونه';
                }
                if (!this.smsEnabled) return 'کتابخانه متن‌های پرتکرار';
                const n = this.total || this.items.length;
                return n ? n + ' متن آماده برای ارسال' : 'کتابخانه متن‌های پرتکرار';
            },

            faNum(n) {
                return String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
            },

            normalizeDigits(value) {
                let digits = String(value || '').replace(/\D+/g, '');
                if (digits.startsWith('98') && digits.length === 12) digits = '0' + digits.slice(2);
                if (digits.startsWith('9') && digits.length === 10) digits = '0' + digits;
                return digits;
            },

            isValidMobileValue(value) {
                return /^09\d{9}$/.test(this.normalizeDigits(value));
            },

            applyPatientContext(row) {
                row = row || {};
                this.patientName = row.name || '';
                this.targetMobile = row.mobile || '';
                this.patientId = row.patientId || null;
                this.nationalCode = row.nationalCode || '';
                this.mobileSecondary = row.mobileSecondary || '';
                this.booking = row.answerBooking || null;
            },

            bulkTargets() {
                return (this.bulkItems || []).filter((row) => this.isValidMobileValue(row.mobile));
            },

            pickBulkSample(preferRandom) {
                const targets = this.bulkTargets();
                const pool = targets.length ? targets : (this.bulkItems || []);
                if (!pool.length) {
                    this.applyPatientContext({});
                    return;
                }
                let pick = pool[0];
                if (preferRandom !== false && pool.length > 1) {
                    pick = pool[Math.floor(Math.random() * pool.length)];
                }
                this.applyPatientContext(pick);
            },

            resolveForPatient(text, row) {
                const now = new Date();
                const clock = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
                const booked = (row && row.answerBooking && row.answerBooking.vars) ? row.answerBooking.vars : {};
                const map = Object.assign({
                    'نام': (row && row.name) || '',
                    'name': (row && row.name) || '',
                    'موبایل': (row && row.mobile) || '',
                    'موبایل۲': (row && row.mobileSecondary) || booked['موبایل۲'] || '',
                    'کدملی': (row && row.nationalCode) || booked['کدملی'] || '',
                    'امروز': cfg.today,
                    'مطب': cfg.clinic,
                    'تاریخ': booked['تاریخ'] || cfg.today,
                    'date': booked['date'] || cfg.today,
                    'ساعت': booked['ساعت'] || clock,
                    'time': booked['time'] || clock,
                    'نوبت': booked['نوبت'] || '',
                    'slotNumber': booked['slotNumber'] || '',
                    'بیمارستان': booked['بیمارستان'] || '',
                    'hospital': booked['hospital'] || '',
                    'آدرس بیمارستان': booked['آدرس بیمارستان'] || '',
                    'hospitalAddress': booked['hospitalAddress'] || '',
                    'نوع عمل': booked['نوع عمل'] || '',
                    'نوع ویزیت': booked['نوع ویزیت'] || '',
                    'surgeryType': booked['surgeryType'] || '',
                    'چشم': booked['چشم'] || '',
                    'eyeType': booked['eyeType'] || '',
                    'توضیحات': booked['توضیحات'] || '',
                    'description': booked['description'] || '',
                }, booked);
                map['نام'] = (row && row.name) || map['نام'];
                map['name'] = (row && row.name) || map['name'];
                map['موبایل'] = (row && row.mobile) || map['موبایل'];
                map['امروز'] = cfg.today;
                map['مطب'] = cfg.clinic;
                if (row && row.mobileSecondary) map['موبایل۲'] = row.mobileSecondary;
                if (row && row.nationalCode) map['کدملی'] = row.nationalCode;
                return (text || '').replace(/\{([^}]+)\}/g, (match, key) => {
                    const value = map[key.trim()];
                    return value ? String(value) : match;
                });
            },

            closeBulkConfirm() {
                if (this.busy) return;
                this.bulkConfirmOpen = false;
                this.bulkConfirmTemplate = '';
                this.bulkConfirmPreview = '';
                this.bulkConfirmCount = 0;
                this.bulkConfirmAnswerId = null;
            },

            openBulkConfirm(template, answerId) {
                const targets = this.bulkTargets();
                if (!targets.length) {
                    this.flash('در انتخاب‌ها شماره معتبری نیست.', 'err');
                    return;
                }
                const preview = this.resolve(template).trim();
                if (!preview) {
                    this.flash('متن پیامک خالی است.', 'err');
                    return;
                }
                this.bulkConfirmTemplate = template;
                this.bulkConfirmPreview = preview;
                this.bulkConfirmCount = targets.length;
                this.bulkConfirmAnswerId = answerId;
                this.bulkConfirmOpen = true;
            },

            async runBulkSend() {
                const template = this.bulkConfirmTemplate;
                const answerId = this.bulkConfirmAnswerId;
                const targets = this.bulkTargets();
                if (!template || !targets.length) {
                    this.closeBulkConfirm();
                    return;
                }
                this.busy = true;
                this.sendingId = answerId;
                let ok = 0;
                let fail = 0;
                try {
                    for (let i = 0; i < targets.length; i += 1) {
                        const row = targets[i];
                        const text = this.resolveForPatient(template, row).trim();
                        const mobile = this.normalizeDigits(row.mobile);
                        if (!text || !this.isValidMobileValue(mobile)) {
                            fail += 1;
                            continue;
                        }
                        this.flash('ارسال ' + this.faNum(i + 1) + ' از ' + this.faNum(targets.length) + '…', 'ok');
                        try {
                            await this.request(cfg.sendUrl, 'POST', {
                                mobile,
                                message: text,
                                answer_id: i === 0 ? answerId : null,
                            });
                            ok += 1;
                        } catch (e) {
                            fail += 1;
                        }
                    }
                    this.bulkConfirmOpen = false;
                    if (fail) {
                        this.flash(this.faNum(ok) + ' ارسال شد، ' + this.faNum(fail) + ' ناموفق بود.', fail === targets.length ? 'err' : 'ok');
                    } else {
                        this.flash('پیامک برای ' + this.faNum(ok) + ' بیمار ارسال شد.');
                        if (answerId) {
                            const item = this.items.find((i) => i.id === answerId);
                            if (item) item.usage_count = (item.usage_count || 0) + 1;
                        }
                    }
                } finally {
                    this.busy = false;
                    this.sendingId = null;
                    this.bulkConfirmTemplate = '';
                    this.bulkConfirmAnswerId = null;
                }
            },

            flash(text, kind = 'ok') {
                this.msg = text;
                this.msgKind = kind;
                clearTimeout(this.msgTimer);
                this.msgTimer = setTimeout(() => { this.msg = ''; }, 4000);
            },

            /* ---------- data ---------- */
            isFiltered() {
                return this.q.trim() !== '' || this.category !== '';
            },

            clearFilters() {
                this.q = '';
                this.category = '';
                this.sort = 'smart';
                this.refresh();
            },

            async refresh() {
                this.loading = true;
                try {
                    const url = new URL(cfg.indexUrl, window.location.origin);
                    if (this.q.trim()) url.searchParams.set('q', this.q.trim());
                    if (this.category) url.searchParams.set('category', this.category);
                    if (this.sort) url.searchParams.set('sort', this.sort);
                    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    this.items = data.data || [];
                    this.categories = data.categories || [];
                    this.total = data.total || 0;
                } catch (e) {
                    this.flash('بارگذاری پاسخ‌ها ناموفق بود.', 'err');
                } finally {
                    this.loading = false;
                }
            },

            csrf() {
                return document.querySelector('meta[name=csrf-token]')?.content || '';
            },

            async request(url, method, body) {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: body ? JSON.stringify(body) : undefined,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'درخواست ناموفق بود');
                return data;
            },

            /* ---------- placeholders ---------- */
            hasVars(text) {
                return /\{[^}]+\}/.test(text || '');
            },

            bookingVarMap() {
                return (this.booking && this.booking.vars) ? this.booking.vars : {};
            },

            /* An unresolved token stays visible, so nobody sends a message with a hole in it. */
            resolve(text) {
                const now = new Date();
                const time = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
                const booked = this.bookingVarMap();
                const map = Object.assign({
                    'نام': this.patientName,
                    'name': this.patientName,
                    'موبایل': this.targetMobile,
                    'موبایل۲': this.mobileSecondary || booked['موبایل۲'] || '',
                    'کدملی': this.nationalCode || booked['کدملی'] || '',
                    'امروز': cfg.today,
                    'مطب': cfg.clinic,
                    'تاریخ': booked['تاریخ'] || cfg.today,
                    'date': booked['date'] || cfg.today,
                    'ساعت': booked['ساعت'] || time,
                    'time': booked['time'] || time,
                    'نوبت': booked['نوبت'] || '',
                    'slotNumber': booked['slotNumber'] || '',
                    'بیمارستان': booked['بیمارستان'] || '',
                    'hospital': booked['hospital'] || '',
                    'آدرس بیمارستان': booked['آدرس بیمارستان'] || '',
                    'hospitalAddress': booked['hospitalAddress'] || '',
                    'نوع عمل': booked['نوع عمل'] || '',
                    'نوع ویزیت': booked['نوع ویزیت'] || '',
                    'surgeryType': booked['surgeryType'] || '',
                    'چشم': booked['چشم'] || '',
                    'eyeType': booked['eyeType'] || '',
                    'توضیحات': booked['توضیحات'] || '',
                    'description': booked['description'] || '',
                }, booked);
                map['نام'] = this.patientName || map['نام'];
                map['name'] = this.patientName || map['name'];
                map['موبایل'] = this.targetMobile || map['موبایل'];
                map['امروز'] = cfg.today;
                map['مطب'] = cfg.clinic;
                if (this.mobileSecondary) map['موبایل۲'] = this.mobileSecondary;
                if (this.nationalCode) map['کدملی'] = this.nationalCode;
                return (text || '').replace(/\{([^}]+)\}/g, (match, key) => {
                    const value = map[key.trim()];
                    return value ? String(value) : match;
                });
            },

            hasBookingTokens(text) {
                const keys = this.bookingKeys || [];
                let found = false;
                (text || '').replace(/\{([^}]+)\}/g, (match, key) => {
                    if (keys.indexOf(key.trim()) !== -1) found = true;
                    return match;
                });
                return found;
            },

            unresolvedBookingTokens(text) {
                const keys = this.bookingKeys || [];
                const leftover = [];
                this.resolve(text || '').replace(/\{([^}]+)\}/g, (match, key) => {
                    if (keys.indexOf(key.trim()) !== -1) leftover.push(key.trim());
                    return match;
                });
                return leftover;
            },

            insertVar(refName, modelName, token, needsBooking) {
                if (needsBooking && !this.booking && this.patientId && !this.bulkMode) {
                    this.pendingInsert = { refName, modelName, token };
                    this.openPicker();
                    return;
                }
                this.insertToken(refName, modelName, token);
            },

            insertToken(refName, modelName, token) {
                const el = this.$refs[refName];
                const current = this[modelName] || '';
                if (!el) {
                    this[modelName] = current + token;
                    return;
                }
                const start = el.selectionStart ?? current.length;
                const end = el.selectionEnd ?? current.length;
                this[modelName] = current.slice(0, start) + token + current.slice(end);
                this.$nextTick(() => {
                    el.focus();
                    const pos = start + token.length;
                    el.setSelectionRange(pos, pos);
                });
            },

            async openPicker() {
                if (!this.patientId) {
                    this.flash('برای انتخاب نوبت، از پرونده بیمار یا گزارشات باز کنید.', 'err');
                    return;
                }
                this.pickerOpen = true;
                const cached = this.bookingsCache[this.patientId];
                if (cached) {
                    this.bookings = cached;
                    return;
                }
                this.pickerLoading = true;
                try {
                    const data = await this.request(cfg.bookingsUrl + '/' + this.patientId, 'GET');
                    this.bookings = data.data || [];
                    this.bookingsCache[this.patientId] = this.bookings;
                } catch (e) {
                    this.flash(e.message || 'بارگذاری نوبت‌ها ناموفق بود.', 'err');
                } finally {
                    this.pickerLoading = false;
                }
            },

            closePicker() {
                this.pickerOpen = false;
                this.pendingInsert = null;
                this.pendingSend = null;
            },

            clearBooking() {
                this.booking = null;
            },

            async selectBooking(row) {
                this.booking = row;
                this.pickerOpen = false;
                const insert = this.pendingInsert;
                const send = this.pendingSend;
                this.pendingInsert = null;
                this.pendingSend = null;
                if (insert) this.insertToken(insert.refName, insert.modelName, insert.token);
                if (send) {
                    if (send.mode === 'send') await this.sendMessage(send.message, send.itemId);
                    else if (send.mode === 'copy') await this.copyItem(send.item);
                    else if (send.mode === 'share') await this.shareItem(send.item);
                }
            },

            async ensureBooking(text, pending) {
                if (this.bulkMode) return false;
                if (this.booking || !this.hasBookingTokens(text)) return false;
                if (!this.patientId) return false;
                this.pendingSend = pending;
                await this.openPicker();
                this.flash('اول عمل یا ویزیت را انتخاب کنید.', 'ok');
                return true;
            },

            /* ---------- SMS metering ---------- */
            isUnicode(text) {
                return /[^\x00-\x7F]/.test(text || '');
            },

            smsParts(text) {
                const len = (text || '').length;
                if (!len) return 0;
                const unicode = this.isUnicode(text);
                const single = unicode ? 70 : 160;
                const multi = unicode ? 67 : 153;
                return len <= single ? 1 : Math.ceil(len / multi);
            },

            meterLabel(text) {
                const len = (text || '').length;
                if (!len) return 'هنوز متنی نوشته نشده';
                const unicode = this.isUnicode(text);
                const parts = this.smsParts(text);
                const capacity = parts > 1 ? parts * (unicode ? 67 : 153) : (unicode ? 70 : 160);
                return len + ' کاراکتر · ' + (capacity - len) + ' کاراکتر تا پیامک بعدی';
            },

            isLong(text) {
                return (text || '').length > 170 || (text || '').split('\n').length > 4;
            },

            /* ---------- item actions ---------- */
            newAnswer() {
                this.resetForm();
                this.tab = 'form';
                this.$nextTick(() => this.$refs.formBody?.focus());
            },

            startEdit(item) {
                this.editingId = item.id;
                this.formTitle = item.title || '';
                this.formCategory = item.category || '';
                this.formBody = item.body || '';
                this.formPinned = !!item.is_pinned;
                this.tab = 'form';
                this.$nextTick(() => this.$refs.formBody?.focus());
            },

            resetForm() {
                this.editingId = null;
                this.formTitle = '';
                this.formCategory = '';
                this.formBody = '';
                this.formPinned = false;
            },

            saveComposeAsTemplate() {
                this.resetForm();
                this.formBody = this.customMessage;
                this.tab = 'form';
                this.$nextTick(() => this.$refs.formBody?.focus());
            },

            async saveForm() {
                const body = this.formBody.trim();
                if (!body || this.busy) return;
                this.busy = true;
                try {
                    const data = await this.request(
                        this.editingId ? cfg.baseUrl + '/' + this.editingId : cfg.storeUrl,
                        this.editingId ? 'PUT' : 'POST',
                        {
                            title: this.formTitle || null,
                            category: this.formCategory || null,
                            body,
                            is_pinned: this.formPinned,
                        }
                    );
                    this.flash(data.message || 'ذخیره شد');
                    this.resetForm();
                    this.tab = 'list';
                    await this.refresh();
                } catch (e) {
                    this.flash(e.message, 'err');
                } finally {
                    this.busy = false;
                }
            },

            async removeItem(item) {
                this.busy = true;
                try {
                    const data = await this.request(cfg.baseUrl + '/' + item.id, 'DELETE');
                    if (this.editingId === item.id) this.resetForm();
                    this.confirmId = null;
                    this.flash(data.message || 'پاسخ حذف شد');
                    await this.refresh();
                } catch (e) {
                    this.flash(e.message, 'err');
                } finally {
                    this.busy = false;
                }
            },

            async togglePin(item) {
                try {
                    const data = await this.request(cfg.baseUrl + '/' + item.id + '/pin', 'POST');
                    this.flash(data.message);
                    await this.refresh();
                } catch (e) {
                    this.flash(e.message, 'err');
                }
            },

            async duplicateItem(item) {
                try {
                    const data = await this.request(cfg.baseUrl + '/' + item.id + '/duplicate', 'POST');
                    this.flash(data.message || 'رونوشت ساخته شد');
                    await this.refresh();
                } catch (e) {
                    this.flash(e.message, 'err');
                }
            },

            /* Usage stats are best-effort: a failed ping must never block the copy. */
            pingUsed(item) {
                if (!item?.id) return;
                this.request(cfg.baseUrl + '/' + item.id + '/used', 'POST').catch(() => {});
                item.usage_count = (item.usage_count || 0) + 1;
            },

            /* The clipboard API needs a secure context, so keep a textarea fallback. */
            async copyText(text) {
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(text);
                        return true;
                    }
                } catch (e) {}
                try {
                    const helper = document.createElement('textarea');
                    helper.value = text;
                    helper.setAttribute('readonly', '');
                    helper.style.position = 'fixed';
                    helper.style.top = '-1000px';
                    document.body.appendChild(helper);
                    helper.select();
                    helper.setSelectionRange(0, helper.value.length);
                    const done = document.execCommand('copy');
                    document.body.removeChild(helper);
                    return done;
                } catch (e) {
                    return false;
                }
            },

            async copyItem(item) {
                if (await this.ensureBooking(item.body || '', { mode: 'copy', item })) return;
                const done = await this.copyText(this.resolve(item.body || ''));
                if (done) {
                    this.copiedId = item.id;
                    setTimeout(() => { if (this.copiedId === item.id) this.copiedId = null; }, 1800);
                    this.pingUsed(item);
                    return;
                }
                this.flash('کپی ناموفق بود؛ متن را دستی انتخاب کنید.', 'err');
            },

            async shareItem(item) {
                if (await this.ensureBooking(item.body || '', { mode: 'share', item })) return;
                const text = this.resolve(item.body || '');
                if (navigator.share) {
                    try {
                        await navigator.share({ text, title: item.title || 'پاسخ آماده' });
                        this.pingUsed(item);
                        return;
                    } catch (e) {
                        if (e && e.name === 'AbortError') return;
                    }
                }
                const done = await this.copyText(text);
                this.flash(done ? 'اشتراک‌گذاری پشتیبانی نشد؛ متن کپی شد.' : 'اشتراک‌گذاری ناموفق بود.', done ? 'ok' : 'err');
                if (done) this.pingUsed(item);
            },

            /* ---------- sending ---------- */
            normalizedMobile() {
                let digits = (this.targetMobile || '').replace(/\D+/g, '');
                if (digits.startsWith('98') && digits.length === 12) digits = '0' + digits.slice(2);
                if (digits.startsWith('9') && digits.length === 10) digits = '0' + digits;
                return digits;
            },

            mobileValid() {
                return /^09\d{9}$/.test(this.normalizedMobile());
            },

            async sendMessage(message, itemId = null) {
                if (this.bulkMode) {
                    this.openBulkConfirm(message, itemId);
                    return;
                }
                if (await this.ensureBooking(message, { mode: 'send', message, itemId })) return;
                const leftover = this.unresolvedBookingTokens(message);
                if (leftover.length && this.patientId && !this.booking) {
                    this.pendingSend = { mode: 'send', message, itemId };
                    await this.openPicker();
                    return;
                }
                const text = this.resolve(message).trim();
                if (!text) {
                    this.flash('متن پیامک خالی است.', 'err');
                    return;
                }
                if (!this.mobileValid()) {
                    this.flash('شماره مقصد را درست وارد کنید (مثل 09123456789).', 'err');
                    this.$refs.mobileInput?.focus();
                    return;
                }
                this.busy = true;
                this.sendingId = itemId;
                try {
                    const data = await this.request(cfg.sendUrl, 'POST', {
                        mobile: this.normalizedMobile(),
                        message: text,
                        answer_id: itemId,
                    });
                    this.flash(data.message || 'پیامک ارسال شد');
                    if (itemId) {
                        const item = this.items.find((i) => i.id === itemId);
                        if (item) item.usage_count = (item.usage_count || 0) + 1;
                    }
                } catch (e) {
                    this.flash(e.message, 'err');
                } finally {
                    this.busy = false;
                    this.sendingId = null;
                }
            },

            sendCustom() { return this.sendMessage(this.customMessage); },
            sendItem(item) { return this.sendMessage(item.body, item.id); },
        };
    };

    {{-- Launcher buttons anywhere on the page open the panel.
         Capture phase so drawers with @click.stop cannot swallow the click. --}}
    (function () {
        if (window.__answerLauncherBound) return;
        window.__answerLauncherBound = true;
        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('[data-answer-launch]');
            if (!trigger) return;
            e.preventDefault();
            e.stopPropagation();
            const detail = {
                mobile: trigger.getAttribute('data-answer-mobile') || '',
                patientName: trigger.getAttribute('data-answer-name') || '',
                patientId: trigger.getAttribute('data-answer-patient-id') || null,
                nationalCode: trigger.getAttribute('data-answer-national-code') || '',
                mobileSecondary: trigger.getAttribute('data-answer-mobile-secondary') || '',
                booking: null,
            };
            const rawBooking = trigger.getAttribute('data-answer-booking');
            if (rawBooking) {
                try { detail.booking = JSON.parse(rawBooking); } catch (err) { detail.booking = null; }
            }
            if (detail.patientId === '') detail.patientId = null;
            if (window.ReadyAnswers && typeof window.ReadyAnswers.open === 'function') {
                window.ReadyAnswers.open(detail);
                return;
            }
            window.dispatchEvent(new CustomEvent('open-ready-answers', { detail }));
        }, true);
    })();
</script>
@endif
