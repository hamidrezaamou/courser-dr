<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'mobile' => '',
    'patientName' => '',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'mobile' => '',
    'patientName' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if(\App\Support\FeatureFlags::enabled('features.ready_answers')): ?>
<?php
    $smsEnabled = (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir';

    $answerVars = [
        ['token' => '{نام}', 'label' => 'نام بیمار'],
        ['token' => '{موبایل}', 'label' => 'موبایل'],
        ['token' => '{تاریخ}', 'label' => 'تاریخ امروز'],
        ['token' => '{ساعت}', 'label' => 'ساعت'],
        ['token' => '{مطب}', 'label' => 'نام مطب'],
    ];
?>


<div
    class="ans-root"
    x-data="window.readyAnswersPanel(<?php echo \Illuminate\Support\Js::from([
        'mobile' => $mobile,
        'patientName' => $patientName,
        'smsEnabled' => $smsEnabled,
        'today' => \App\Support\Jalali::format(now(), 'Y/m/d'),
        'clinic' => config('app.name', 'مطب'),
        'vars' => $answerVars,
        'indexUrl' => route('ready-answers.index'),
        'storeUrl' => route('ready-answers.store'),
        'sendUrl' => route('ready-answers.send'),
        'baseUrl' => url('/ready-answers'),
    ])->toHtml() ?>)"
    @open-ready-answers.window="openPanel($event.detail)"
    @close-ready-answers.window="closePanel()"
    @keydown.escape.window="if (open) closePanel()"
>
    <div
        class="ans-overlay"
        x-show="open"
        x-cloak
        @click.self="closePanel()"
    >
        <div class="ans-sheet" role="dialog" aria-modal="true" aria-labelledby="ans-title" @click.stop>

            
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

            
            <div class="ans-recipient">
                <div class="ans-recip__row">
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
                                        <span x-text="sendingId === item.id ? 'در حال ارسال...' : 'ارسال'"></span>
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
                            <span class="ans-vars__hint">افزودن:</span>
                            <template x-for="v in vars" :key="v.token">
                                <button type="button" class="ans-var" :title="v.label" @click="insertVar('customBody', 'customMessage', v.token)" x-text="v.label"></button>
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
                                <span x-text="busy ? 'در حال ارسال...' : 'ارسال پیامک'"></span>
                            </button>
                            <button type="button" class="ans-btn ans-btn--ghost" style="width:auto" @click="saveComposeAsTemplate()" :disabled="!customMessage.trim()" title="ذخیره این متن به عنوان پاسخ آماده">
                                ذخیره در فهرست
                            </button>
                        </div>
                    </div>
                </template>

                
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
                            <span class="ans-vars__hint">افزودن:</span>
                            <template x-for="v in vars" :key="v.token">
                                <button type="button" class="ans-var" :title="v.label" @click="insertVar('formBody', 'formBody', v.token)" x-text="v.label"></button>
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
            smsEnabled: !!cfg.smsEnabled,
            vars: cfg.vars || [],
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
            },

            /* ---------- panel lifecycle ---------- */
            openPanel(detail) {
                this.open = true;
                this.tab = 'list';
                this.confirmId = null;
                if (detail && detail.mobile) this.targetMobile = detail.mobile;
                else if (!this.targetMobile) this.targetMobile = cfg.mobile || '';
                if (detail && detail.patientName) this.patientName = detail.patientName;
                this.msg = '';
                document.body.style.overflow = 'hidden';
                this.refresh();
                this.$nextTick(() => this.$refs.scroll?.scrollTo({ top: 0 }));
            },

            closePanel() {
                this.open = false;
                this.confirmId = null;
                document.body.style.overflow = '';
            },

            headSubtitle() {
                if (!this.smsEnabled) return 'کتابخانه متن‌های پرتکرار';
                const n = this.total || this.items.length;
                return n ? n + ' متن آماده برای ارسال' : 'کتابخانه متن‌های پرتکرار';
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

            /* An unresolved token stays visible, so nobody sends a message with a hole in it. */
            resolve(text) {
                const now = new Date();
                const time = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
                const map = {
                    'نام': this.patientName,
                    'موبایل': this.targetMobile,
                    'تاریخ': cfg.today,
                    'ساعت': time,
                    'مطب': cfg.clinic,
                };
                return (text || '').replace(/\{([^}]+)\}/g, (match, key) => {
                    const value = map[key.trim()];
                    return value ? String(value) : match;
                });
            },

            insertVar(refName, modelName, token) {
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
            };
            if (window.ReadyAnswers && typeof window.ReadyAnswers.open === 'function') {
                window.ReadyAnswers.open(detail);
                return;
            }
            window.dispatchEvent(new CustomEvent('open-ready-answers', { detail }));
        }, true);
    })();
</script>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\answer-panel.blade.php ENDPATH**/ ?>