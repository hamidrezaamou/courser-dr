@props([
    'mode' => 'toolbox',
])

@php
    $toolboxPrefs = \App\Support\ToolboxPrefs::normalize(auth()->user()?->toolbox_prefs);
    $toolboxBuiltins = \App\Support\ToolboxPrefs::toolboxBuiltins();
    $bookingBuiltins = \App\Support\ToolboxPrefs::bookingBuiltins();
    $bookingSuccess = session('booking_success');
@endphp

{{-- Patient/appointment toolbox — plain JS so a row click always opens it, even if Alpine fails --}}
<div
    id="row-toolbox-root"
    data-prefs-url="{{ route('toolbox-prefs.update') }}"
    data-sms-url="{{ route('sms.send') }}"
    data-mobile2-url="{{ route('reports.mobile-secondary') }}"
    data-patient-mobile2-base="{{ url('/patients') }}"
    data-checklist-ensure-base="/surgery-appointments/__ID__/checklist/ensure"
    data-checklist-api-base="/surgery-checklists"
    data-followups-bulk-url="{{ \App\Support\PatientFollowUps::isAvailable() ? route('followups.bulk') : '' }}"
    data-followups-enabled="{{ \App\Support\PatientFollowUps::isAvailable() ? '1' : '0' }}"
    data-followup-kinds='@json(\App\Support\PatientFollowUps::isAvailable() ? \App\Services\PatientFollowUpService::kinds() : [])'
    data-followup-methods='@json(\App\Support\PatientFollowUps::isAvailable() ? \App\Services\PatientFollowUpService::methods() : [])'
    data-csrf="{{ csrf_token() }}"
>
    <div class="row-toolbox-overlay" data-rt-overlay style="display:none">
        <div class="row-toolbox-panel" data-rt-panel data-surface="toolbox">
            <button type="button" class="row-toolbox-edit" data-rt-edit aria-label="ویرایش ابزار" title="سفارشی‌سازی">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 113 3L8.25 18.1 4 19l.9-4.25L16.862 3.487z"/></svg>
            </button>
            <button type="button" class="row-toolbox-close" data-rt-close aria-label="بستن"><x-icon-close /></button>
            <h4 class="row-toolbox-title" data-rt-name></h4>
            <p class="row-toolbox-bulk-hint" data-rt-bulk-hint style="display:none">هر بیمار پیامک و وضعیت مخصوص خودش را می‌گیرد. قبل از اجرا تأیید تعداد می‌آید.</p>

            <div class="row-toolbox-info">
                <div class="row-toolbox-row is-copy" data-rt-row="nationalCode" data-rt-copy="nationalCode" title="برای کپی کلیک کنید">
                    <span class="lbl">کد ملی</span>
                    <span class="val" dir="ltr" data-rt-text="nationalCode"></span>
                </div>
                <div class="row-toolbox-row is-copy" data-rt-row="mobile" data-rt-copy="mobile" title="برای کپی کلیک کنید">
                    <span class="lbl">موبایل</span>
                    <span class="val" dir="ltr" data-rt-text="mobile"></span>
                </div>
                <div class="row-toolbox-row rt-phone2-row" data-rt-mobile2-row style="display:none" title="برای نوشتن شماره بزنید">
                    <span class="lbl">موبایل دوم</span>
                    <span class="val" dir="ltr">
                        <input type="text" class="rt-inline-phone" data-rt-mobile2-input dir="ltr" inputmode="tel" maxlength="11" placeholder="—" autocomplete="off" aria-label="شماره تماس دوم">
                    </span>
                </div>
                <div class="row-toolbox-row" data-rt-row="meta">
                    <span class="lbl">جزئیات</span>
                    <span class="val" data-rt-text="meta"></span>
                </div>
            </div>

            <div class="row-toolbox-editbar" data-rt-editbar style="display:none">
                <label class="row-toolbox-editbar__field">
                    <span>سبک همین دکمه</span>
                    <select data-rt-style>
                        <option value="soft">نرم</option>
                        <option value="filled">توپُر</option>
                        <option value="outline">خطی</option>
                    </select>
                </label>
                <label class="row-toolbox-editbar__field">
                    <span>رنگ دکمه انتخاب‌شده</span>
                    <input type="color" data-rt-accent value="#4f86be">
                </label>
                <label class="row-toolbox-editbar__field">
                    <span>عرض همین دکمه</span>
                    <select data-rt-span>
                        <option value="half">نیم‌عرض</option>
                        <option value="full">تمام‌عرض</option>
                    </select>
                </label>
                <p class="row-toolbox-editbar__hint" data-rt-color-hint>روی یک دکمه بزن، بعد رنگ و عرضش را عوض کن</p>
                <button type="button" class="btn-secondary !py-1.5 !px-3 !text-xs" data-rt-add>+ افزودن</button>
                <button type="button" class="btn-primary !py-1.5 !px-3 !text-xs" data-rt-done>تمام</button>
            </div>

            <div class="row-toolbox-actions" data-rt-actions></div>
            <p class="row-toolbox-msg" data-rt-msg style="display:none"></p>
        </div>
    </div>

    {{-- After booking success sheet --}}
    <div class="row-toolbox-overlay" data-bs-overlay style="display:none">
        <div class="row-toolbox-panel row-toolbox-panel--success" data-bs-panel data-surface="booking_success">
            <button type="button" class="row-toolbox-edit" data-bs-edit aria-label="ویرایش میانبرها" title="سفارشی‌سازی">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 113 3L8.25 18.1 4 19l.9-4.25L16.862 3.487z"/></svg>
            </button>
            <button type="button" class="row-toolbox-close" data-bs-close aria-label="بستن"><x-icon-close /></button>
            <div class="row-toolbox-success-head">
                <span class="row-toolbox-success-badge">ثبت شد</span>
                <h4 class="row-toolbox-title" data-bs-name>با موفقیت ثبت شد</h4>
                <p class="row-toolbox-success-sub" data-bs-sub></p>
            </div>
            <div class="row-toolbox-info">
                <div class="row-toolbox-row is-copy" data-bs-row="nationalCode" data-bs-copy="nationalCode" title="برای کپی کلیک کنید">
                    <span class="lbl">کد ملی</span>
                    <span class="val" dir="ltr" data-bs-text="nationalCode"></span>
                </div>
                <div class="row-toolbox-row is-copy" data-bs-row="mobile" data-bs-copy="mobile" title="برای کپی کلیک کنید">
                    <span class="lbl">موبایل</span>
                    <span class="val" dir="ltr" data-bs-text="mobile"></span>
                </div>
                <div class="row-toolbox-row is-copy" data-bs-row="mobileSecondary" data-bs-copy="mobileSecondary" title="برای کپی کلیک کنید">
                    <span class="lbl">موبایل دوم</span>
                    <span class="val" dir="ltr" data-bs-text="mobileSecondary"></span>
                </div>
                <div class="row-toolbox-row" data-bs-row="meta">
                    <span class="lbl">جزئیات</span>
                    <span class="val" data-bs-text="meta"></span>
                </div>
            </div>
            <div class="row-toolbox-editbar" data-bs-editbar style="display:none">
                <label class="row-toolbox-editbar__field">
                    <span>سبک همین دکمه</span>
                    <select data-bs-style>
                        <option value="soft">نرم</option>
                        <option value="filled">توپُر</option>
                        <option value="outline">خطی</option>
                    </select>
                </label>
                <label class="row-toolbox-editbar__field">
                    <span>رنگ همین دکمه</span>
                    <input type="color" data-bs-accent value="#4f86be">
                </label>
                <label class="row-toolbox-editbar__field">
                    <span>عرض همین دکمه</span>
                    <select data-bs-span>
                        <option value="half">نیم‌عرض</option>
                        <option value="full">تمام‌عرض</option>
                    </select>
                </label>
                <p class="row-toolbox-editbar__hint" data-bs-color-hint>یک دکمه را انتخاب کن، بعد رنگ/سبک/عرض همان را بده</p>
                <button type="button" class="btn-secondary !py-1.5 !px-3 !text-xs" data-bs-add>+ افزودن</button>
                <button type="button" class="btn-primary !py-1.5 !px-3 !text-xs" data-bs-done>تمام</button>
            </div>
            <div class="row-toolbox-actions" data-bs-actions></div>
            <p class="row-toolbox-empty" data-bs-empty>هنوز میانبری نیست. با مداد، دکمه‌های دلخواهت را بچین.</p>
            <p class="row-toolbox-msg" data-bs-msg style="display:none"></p>
        </div>
    </div>

    {{-- Status change popup --}}
    <div class="rt-status-overlay" data-status-overlay style="display:none" aria-hidden="true">
        <div class="rt-status-sheet" role="dialog" aria-modal="true" aria-label="تغییر وضعیت">
            <div class="rt-status-sheet__head">
                <div>
                    <strong>تغییر وضعیت</strong>
                    <p data-status-current></p>
                </div>
                <button type="button" class="tg-tool" data-status-close aria-label="بستن"><x-icon-close /></button>
            </div>
            <div class="rt-status-sheet__list" data-status-list></div>
            <div class="rt-status-sheet__confirm" data-status-confirm style="display:none">
                <h4 data-status-confirm-title></h4>
                <p data-status-confirm-text></p>
                <div class="rt-status-sheet__confirm-acts">
                    <button type="button" class="btn-secondary !py-1.5 !px-3 !text-xs" data-status-confirm-cancel>انصراف</button>
                    <button type="button" class="btn-primary !py-1.5 !px-3 !text-xs" data-status-confirm-ok>بله، انجام بده</button>
                </div>
            </div>
            <p class="rt-status-sheet__msg" data-status-msg style="display:none"></p>
        </div>
    </div>

    {{-- Bulk follow-up sheet --}}
    <div class="rt-status-overlay" data-bulk-followup-overlay style="display:none" aria-hidden="true">
        <div class="rt-status-sheet rt-followup-sheet" role="dialog" aria-modal="true" aria-label="پیگیری گروهی">
            <div class="rt-status-sheet__head">
                <div>
                    <strong>پیگیری گروهی</strong>
                    <p data-bulk-followup-count>برای بیماران انتخاب‌شده</p>
                </div>
                <button type="button" class="tg-tool" data-bulk-followup-close aria-label="بستن"><x-icon-close /></button>
            </div>
            <form class="rt-followup-form" data-bulk-followup-form>
                <p class="rt-followup-form__hint">سررسید نسبت به تاریخ عمل/نوبت هر بیمار محاسبه می‌شود (مثلاً ۱ هفته قبل از عمل).</p>
                <label class="rt-followup-field">
                    <span>عنوان</span>
                    <input type="text" name="title" class="field-input" maxlength="190" placeholder="مثلاً: یادآوری قبل از عمل">
                </label>
                <div class="rt-followup-row">
                    <label class="rt-followup-field">
                        <span>مقدار</span>
                        <input type="number" name="offset_amount" class="field-input" min="0" max="365" value="1" required>
                    </label>
                    <label class="rt-followup-field">
                        <span>واحد</span>
                        <select name="offset_unit" class="field-input">
                            <option value="day">روز</option>
                            <option value="week" selected>هفته</option>
                            <option value="month">ماه</option>
                        </select>
                    </label>
                    <label class="rt-followup-field">
                        <span>نسبت به نوبت</span>
                        <select name="offset_direction" class="field-input">
                            <option value="before" selected>قبل از رویداد</option>
                            <option value="after">بعد از رویداد</option>
                        </select>
                    </label>
                </div>
                <div class="rt-followup-presets" data-bulk-followup-presets>
                    <button type="button" data-preset="1|week|before">۱ هفته قبل</button>
                    <button type="button" data-preset="1|day|before">۱ روز قبل</button>
                    <button type="button" data-preset="1|day|after">۱ روز بعد</button>
                    <button type="button" data-preset="7|day|after">۱ هفته بعد</button>
                    <button type="button" data-preset="30|day|after">۱ ماه بعد</button>
                </div>
                <div class="rt-followup-row">
                    <label class="rt-followup-field">
                        <span>نوع پیگیری</span>
                        <select name="kind" class="field-input" data-bulk-followup-kind></select>
                    </label>
                    <label class="rt-followup-field">
                        <span>روش</span>
                        <select name="method" class="field-input" data-bulk-followup-method></select>
                    </label>
                    <label class="rt-followup-field">
                        <span>ساعت (اختیاری)</span>
                        <input type="time" name="due_time" class="field-input" value="09:00">
                    </label>
                </div>
                <label class="rt-followup-field">
                    <span>توضیح (اختیاری)</span>
                    <textarea name="description" class="field-input" rows="2" maxlength="2000" placeholder="یادداشت برای پیگیری‌ها…"></textarea>
                </label>
                <p class="rt-followup-summary" data-bulk-followup-summary></p>
                <p class="rt-status-sheet__msg" data-bulk-followup-msg style="display:none"></p>
                <div class="rt-status-sheet__confirm-acts" style="display:flex;gap:0.4rem;margin-top:0.7rem">
                    <button type="button" class="btn-secondary !py-1.5 !px-3 !text-xs" data-bulk-followup-close>انصراف</button>
                    <button type="submit" class="btn-primary !py-1.5 !px-3 !text-xs" data-bulk-followup-submit>ثبت پیگیری‌ها</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add action sheet --}}
    <div class="rt-add-overlay" data-add-overlay style="display:none">
        <div class="rt-add-sheet" role="dialog" aria-modal="true" aria-label="افزودن دکمه">
            <div class="rt-add-sheet__head">
                <strong data-add-title>افزودن دکمه</strong>
                <button type="button" class="tg-tool" data-add-close aria-label="بستن"><x-icon-close /></button>
            </div>
            <div class="rt-add-sheet__body" data-add-body></div>
        </div>
    </div>

    {{-- SMS confirm before panel send --}}
    <div class="rt-sms-confirm-overlay" data-sms-confirm-overlay style="display:none" aria-hidden="true">
        <div class="rt-sms-confirm" role="dialog" aria-modal="true" aria-label="تأیید ارسال پیامک">
            <h4 class="rt-sms-confirm__title">ارسال پیامک؟</h4>
            <p class="rt-sms-confirm__to">به شماره <strong dir="ltr" data-sms-confirm-mobile></strong></p>
            <div class="rt-sms-confirm__body" data-sms-confirm-text></div>
            <div class="rt-sms-confirm__actions">
                <button type="button" class="rt-sms-confirm__cancel" data-sms-confirm-cancel>انصراف</button>
                <button type="button" class="rt-sms-confirm__ok" data-sms-confirm-ok>ارسال</button>
            </div>
        </div>
    </div>

    {{-- Group action confirm --}}
    <div class="rt-sms-confirm-overlay" data-bulk-confirm-overlay style="display:none" aria-hidden="true">
        <div class="rt-sms-confirm" role="dialog" aria-modal="true" aria-label="تأیید عملیات گروهی">
            <h4 class="rt-sms-confirm__title" data-bulk-confirm-title>تأیید عملیات گروهی</h4>
            <p class="rt-sms-confirm__to" data-bulk-confirm-to></p>
            <div class="rt-sms-confirm__body" data-bulk-confirm-body></div>
            <div class="rt-sms-confirm__actions">
                <button type="button" class="rt-sms-confirm__cancel" data-bulk-confirm-cancel>انصراف</button>
                <button type="button" class="rt-sms-confirm__ok" data-bulk-confirm-ok>بله، مطمئنم</button>
            </div>
        </div>
    </div>

    {{-- Surgery checklist popup (same interaction pattern as patient gallery pg-modal) --}}
    <div class="scl-overlay" data-cl-overlay style="display:none" aria-hidden="true">
        <div class="scl-panel" role="dialog" aria-modal="true" aria-labelledby="scl-dialog-title" aria-label="چک‌لیست عمل">
            <div class="scl-head">
                <button type="button" class="scl-close" data-cl-close aria-label="بستن">&times;</button>
                <strong id="scl-dialog-title" class="scl-heading">چک‌لیست عمل</strong>
            </div>
            <div class="scl-meta">
                <span data-cl-patient></span>
                <span class="scl-meta__saved" data-cl-saved dir="ltr" style="display:none"></span>
            </div>
            <div class="scl-body">
                <div class="scl-record scl-record--modal">
                    <div class="scl-record__head">
                        <span class="scl-record__kicker">چک‌لیست عمل</span>
                        <strong class="scl-record__title" data-cl-title>چک‌لیست</strong>
                    </div>
                    <ul class="scl-record__list" data-cl-list></ul>
                </div>
            </div>
            <p class="scl-msg" data-cl-msg style="display:none"></p>
            <div class="scl-foot">
                <button type="button" class="btn-secondary !py-2 !text-sm" data-cl-add>+ مورد</button>
                <button type="button" class="btn-primary !py-2 !text-sm flex-1" data-cl-confirm>ثبت در پرونده</button>
            </div>
        </div>
    </div>

    {{-- Same-page clinical workspace (iframe of patient file in embed mode) --}}
    <div class="patient-workspace-overlay" data-pw-overlay style="display:none" aria-hidden="true">
        <div class="patient-workspace-shell" role="dialog" aria-modal="true" aria-label="پرونده بالینی">
            <div class="patient-workspace-bar">
                <strong class="patient-workspace-bar__title" data-pw-title>پرونده / گالری</strong>
                <button type="button" class="patient-workspace-bar__close" data-pw-close aria-label="بستن">
                    <x-icon-close />
                </button>
            </div>
            <iframe class="patient-workspace-frame" data-pw-frame title="پرونده بالینی"></iframe>
        </div>
    </div>
</div>

<script>
    (function () {
        if (window.RowToolbox) return;

        var root = document.getElementById('row-toolbox-root');
        if (!root) return;

        var prefsUrl = root.getAttribute('data-prefs-url');
        var sendUrl = root.getAttribute('data-sms-url');
        var mobile2Url = root.getAttribute('data-mobile2-url');
        var patientMobile2Base = root.getAttribute('data-patient-mobile2-base');
        var followupsBulkUrl = root.getAttribute('data-followups-bulk-url') || '';
        var followupsEnabled = root.getAttribute('data-followups-enabled') === '1' && !!followupsBulkUrl;
        var followupKinds = {};
        var followupMethods = {};
        try { followupKinds = JSON.parse(root.getAttribute('data-followup-kinds') || '{}') || {}; } catch (e) { followupKinds = {}; }
        try { followupMethods = JSON.parse(root.getAttribute('data-followup-methods') || '{}') || {}; } catch (e) { followupMethods = {}; }
        var csrf = root.getAttribute('data-csrf') || (document.querySelector('meta[name=csrf-token]') || {}).content || '';

        var prefs = @js($toolboxPrefs);
        var toolboxBuiltins = @js($toolboxBuiltins);
        var bookingBuiltins = @js($bookingBuiltins);
        var initialBookingSuccess = @js($bookingSuccess);

        var overlay = root.querySelector('[data-rt-overlay]');
        var panel = root.querySelector('[data-rt-panel]');
        var actionsEl = root.querySelector('[data-rt-actions]');
        var msgEl = root.querySelector('[data-rt-msg]');
        var editBar = root.querySelector('[data-rt-editbar]');
        var styleSel = root.querySelector('[data-rt-style]');
        var accentInp = root.querySelector('[data-rt-accent]');
        var spanSel = root.querySelector('[data-rt-span]');

        var bsOverlay = root.querySelector('[data-bs-overlay]');
        var bsPanel = root.querySelector('[data-bs-panel]');
        var bsActions = root.querySelector('[data-bs-actions]');
        var bsMsg = root.querySelector('[data-bs-msg]');
        var bsEmpty = root.querySelector('[data-bs-empty]');
        var bsEditBar = root.querySelector('[data-bs-editbar]');
        var bsStyleSel = root.querySelector('[data-bs-style]');
        var bsAccentInp = root.querySelector('[data-bs-accent]');
        var bsSpanSel = root.querySelector('[data-bs-span]');

        var addOverlay = root.querySelector('[data-add-overlay]');
        var addBody = root.querySelector('[data-add-body]');
        var addTitle = root.querySelector('[data-add-title]');

        var pwOverlay = root.querySelector('[data-pw-overlay]');
        var pwFrame = root.querySelector('[data-pw-frame]');
        var pwTitle = root.querySelector('[data-pw-title]');
        var pwClose = root.querySelector('[data-pw-close]');

        var smsConfirmOverlay = root.querySelector('[data-sms-confirm-overlay]');
        var smsConfirmMobile = root.querySelector('[data-sms-confirm-mobile]');
        var smsConfirmText = root.querySelector('[data-sms-confirm-text]');

        function histPush(id, closer) {
            if (window.OverlayHistory) window.OverlayHistory.push(id, closer);
        }
        function histDismiss(id) {
            if (window.OverlayHistory) window.OverlayHistory.dismiss(id);
        }
        var smsConfirmCancel = root.querySelector('[data-sms-confirm-cancel]');
        var smsConfirmOk = root.querySelector('[data-sms-confirm-ok]');
        var pendingSms = null;

        var bulkConfirmOverlay = root.querySelector('[data-bulk-confirm-overlay]');
        var bulkConfirmTitle = root.querySelector('[data-bulk-confirm-title]');
        var bulkConfirmTo = root.querySelector('[data-bulk-confirm-to]');
        var bulkConfirmBody = root.querySelector('[data-bulk-confirm-body]');
        var bulkConfirmCancel = root.querySelector('[data-bulk-confirm-cancel]');
        var bulkConfirmOk = root.querySelector('[data-bulk-confirm-ok]');
        var pendingBulk = null;

        var bulkFollowupOverlay = root.querySelector('[data-bulk-followup-overlay]');
        var bulkFollowupForm = root.querySelector('[data-bulk-followup-form]');
        var bulkFollowupCount = root.querySelector('[data-bulk-followup-count]');
        var bulkFollowupSummary = root.querySelector('[data-bulk-followup-summary]');
        var bulkFollowupMsg = root.querySelector('[data-bulk-followup-msg]');
        var bulkFollowupKind = root.querySelector('[data-bulk-followup-kind]');
        var bulkFollowupMethod = root.querySelector('[data-bulk-followup-method]');
        var bulkFollowupBusy = false;

        var statusOverlay = root.querySelector('[data-status-overlay]');
        var statusList = root.querySelector('[data-status-list]');
        var statusCurrent = root.querySelector('[data-status-current]');
        var statusMsg = root.querySelector('[data-status-msg]');
        var statusConfirmBox = root.querySelector('[data-status-confirm]');
        var statusConfirmTitle = root.querySelector('[data-status-confirm-title]');
        var statusConfirmText = root.querySelector('[data-status-confirm-text]');
        var pendingStatus = null;
        var statusBusy = false;

        var item = null;
        var toolboxSourceEl = null;
        var bookingItem = null;
        var editingSurface = null;
        var addTargetSurface = null;
        var sending = false;
        var dragId = null;
        var selectedActionId = null;

        function faNum(n) {
            return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
        }

        function isBulk(data) {
            return !!(data && data.bulk && Array.isArray(data.bulkItems) && data.bulkItems.length);
        }

        function bulkRows(data) {
            if (isBulk(data)) return data.bulkItems;
            return data ? [data] : [];
        }

        function show(el, visible) {
            if (el) el.style.display = visible ? '' : 'none';
        }

        function setMsg(el, text) {
            if (!el) return;
            el.textContent = text || '';
            show(el, !!text);
        }

        function surfaceKey(surface) {
            return surface === 'booking_success' ? 'booking_success' : 'toolbox';
        }

        function surfaceState(surface) {
            var key = surfaceKey(surface);
            if (!prefs[key]) prefs[key] = { style: 'soft', accent: '#4f86be', items: [] };
            return prefs[key];
        }

        function findItem(surface, id) {
            return (surfaceState(surface).items || []).find(function (it) { return it.id === id; }) || null;
        }

        function itemAccent(surface, entry) {
            if (entry && entry.accent && /^#[0-9A-Fa-f]{6}$/.test(entry.accent)) return entry.accent;
            return surfaceState(surface).accent || '#4f86be';
        }

        function itemStyle(surface, entry) {
            var style = entry && entry.style;
            if (style === 'soft' || style === 'filled' || style === 'outline') return style;
            return surfaceState(surface).style || 'soft';
        }

        function itemSpan(surface, entry) {
            if (entry && (entry.span === 'full' || entry.span === 'half')) return entry.span;
            var meta = builtinsFor(surface)[entry && entry.id] || {};
            return meta.full ? 'full' : 'half';
        }

        function ensureSelectedEntry(surface) {
            var entry = selectedActionId ? findItem(surface, selectedActionId) : null;
            if (entry) return entry;
            var first = (surfaceState(surface).items || [])[0];
            if (!first) return null;
            selectedActionId = first.id;
            return first;
        }

        function syncEditControls(surface) {
            var entry = selectedActionId ? findItem(surface, selectedActionId) : null;
            var style = itemStyle(surface, entry || {});
            var accent = itemAccent(surface, entry || {});
            var span = itemSpan(surface, entry || {});
            if (surface === 'booking_success') {
                if (bsStyleSel) bsStyleSel.value = style;
                if (bsAccentInp) bsAccentInp.value = accent;
                if (bsSpanSel) bsSpanSel.value = span;
            } else {
                if (styleSel) styleSel.value = style;
                if (accentInp) accentInp.value = accent;
                if (spanSel) spanSel.value = span;
            }
        }

        function builtinsFor(surface) {
            return surface === 'booking_success' ? bookingBuiltins : toolboxBuiltins;
        }

        function normalizeMobile(raw) {
            var digits = String(raw || '').replace(/\D+/g, '');
            if (digits.indexOf('98') === 0 && digits.length === 12) digits = '0' + digits.slice(2);
            if (digits.indexOf('9') === 0 && digits.length === 10) digits = '0' + digits;
            return /^09\d{9}$/.test(digits) ? digits : '';
        }

        function telHref(mobile) {
            var m = normalizeMobile(mobile);
            return m ? ('tel:+98' + m.slice(1)) : '';
        }

        function smsHref(mobile, body) {
            var m = normalizeMobile(mobile);
            if (!m) return '';
            var href = 'sms:+98' + m.slice(1);
            if (body) href += '?body=' + encodeURIComponent(body);
            return href;
        }

        function patientDetailsMessage(data) {
            var lines = [
                'مشخصات بیمار',
                'نام: ' + (data.name || '—'),
                'کد ملی: ' + (data.nationalCode || '—'),
                'موبایل: ' + (data.mobile || '—'),
            ];
            if (data.mobileSecondary) lines.push('موبایل دوم: ' + data.mobileSecondary);
            if (data.meta) lines.push('جزئیات: ' + data.meta);
            return lines.join('\n').slice(0, 900);
        }

        function labelOf(surface, entry) {
            if (entry.kind === 'tel' || entry.kind === 'sms' || entry.kind === 'panel_sms') return entry.label || (entry.kind === 'tel' ? 'تماس' : 'پیامک پنل');
            var meta = builtinsFor(surface)[entry.id] || {};
            if ((entry.id === 'share_details' || entry.id === 'panel_sms_person') && entry.label) return entry.label;
            if (isBulk(item) && entry.id === 'panel_sms') return 'ارسال پیامک پنل به همه';
            if (isBulk(item) && entry.id === 'change_status') return 'تغییر وضعیت همه';
            if (isBulk(item) && entry.id === 'answers') return 'پیام آماده / دلخواه گروهی';
            if (isBulk(item) && entry.id === 'bulk_followup') return 'ثبت پیگیری گروهی';
            return meta.label || entry.id;
        }

        function isAvailable(surface, entry, data) {
            if (isBulk(data) && surface === 'toolbox') {
                if (entry.kind === 'tel' || entry.kind === 'sms' || entry.kind === 'panel_sms') {
                    return false;
                }
                if (entry.id === 'panel_sms') {
                    return bulkRows(data).some(function (row) { return row.smsEnabled && normalizeMobile(row.mobile); });
                }
                if (entry.id === 'change_status') {
                    return bulkRows(data).some(function (row) {
                        return row.canChangeStatus && row.statusUrl && (row.statusActions || []).length;
                    });
                }
                if (entry.id === 'answers') {
                    return data.readyAnswersEnabled !== false
                        && bulkRows(data).some(function (row) { return normalizeMobile(row.mobile); });
                }
                if (entry.id === 'bulk_followup') {
                    return followupsEnabled && bulkRows(data).some(function (row) {
                        return (row.subjectType === 'surgery' || row.subjectType === 'visit') && row.subjectId;
                    });
                }
                return false;
            }
            if (entry.kind === 'tel' || entry.kind === 'sms' || entry.kind === 'panel_sms') return !!normalizeMobile(entry.mobile);
            if (surface === 'booking_success') {
                if (entry.id === 'open_patient') return !!data.patientUrl;
                if (entry.id === 'open_board') return !!data.boardUrl;
                if (entry.id === 'call_patient') return !!normalizeMobile(data.mobile);
                if (entry.id === 'sms_patient') return !!normalizeMobile(data.mobile);
                if (entry.id === 'share_details' || entry.id === 'panel_sms_person') return !!normalizeMobile(entry.mobile || '');
                if (entry.id === 'open_print') return !!data.printUrl;
                if (entry.id === 'open_reports') return !!data.reportsUrl;
                if (entry.id === 'panel_sms') return !!normalizeMobile(data.mobile);
                return true;
            }
            var onPatient = data.sheetMode === 'patient';
            var alreadyOnFile = onPatient && !!window.PatientWorkspace;
            if (entry.id === 'call') return !!data.telHref;
            if (entry.id === 'sms') return !!data.smsHref;
            if (entry.id === 'edit') return !!data.editUrl;
            if (entry.id === 'print') return !!data.printUrl;
            if (entry.id === 'surgery') return !!data.surgeryUrl;
            if (entry.id === 'visit') return !!data.visitUrl;
            if (entry.id === 'workspace') return !!data.patientUrl;
            if (entry.id === 'answers') return data.readyAnswersEnabled !== false;
            if (entry.id === 'report_note') return data.sheetMode === 'report';
            if (entry.id === 'change_status') return !!data.canChangeStatus && !!data.statusUrl && (data.statusActions || []).length;
            if (entry.id === 'panel_sms') return !!data.smsEnabled && !!data.mobile;
            if (entry.id === 'checklist') return !!data.hasSurgeryChecklist && !!data.surgeryAppointmentId;
            if (entry.id === 'patient_file') return !!data.patientUrl && !alreadyOnFile;
            if (entry.id === 'bulk_followup') return false;
            return true;
        }

        function renderActions(surface, container, data, emptyEl) {
            var st = surfaceState(surface);
            var editing = editingSurface === surface;
            container.innerHTML = '';
            var items = st.items || [];
            var visibleCount = 0;

            items.forEach(function (entry) {
                var available = isAvailable(surface, entry, data);
                if (!editing && !available) return;
                visibleCount += 1;

                var useAnchor = entry.kind === 'tel' || entry.kind === 'sms' || (
                    entry.kind === 'builtin' && [
                        'call', 'sms', 'edit', 'print', 'surgery', 'visit', 'patient_file',
                        'open_patient', 'open_board', 'open_print', 'open_reports', 'call_patient', 'sms_patient',
                    ].indexOf(entry.id) >= 0
                );
                var btn = document.createElement(useAnchor ? 'a' : 'button');
                if (!useAnchor) btn.setAttribute('type', 'button');
                btn.className = 'tb-btn';
                btn.textContent = labelOf(surface, entry);
                btn.dataset.actionId = entry.id;
                btn.dataset.actionKind = entry.kind || 'builtin';
                btn.setAttribute('data-btn-style', itemStyle(surface, entry));
                btn.style.setProperty('--tb-accent', itemAccent(surface, entry));

                if (itemSpan(surface, entry) === 'full') {
                    btn.classList.add('full');
                }
                if (!available) btn.classList.add('is-disabled');
                if (editing && selectedActionId === entry.id) btn.classList.add('is-selected');

                if (editing) {
                    btn.draggable = true;
                    btn.classList.add('is-editing');
                    var rm = document.createElement('button');
                    rm.type = 'button';
                    rm.className = 'tb-btn-remove';
                    rm.setAttribute('aria-label', 'حذف');
                    rm.textContent = '×';
                    rm.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (selectedActionId === entry.id) selectedActionId = null;
                        removeItem(surface, entry.id);
                    });
                    btn.appendChild(rm);
                    btn.addEventListener('click', function (e) {
                        if (e.target === rm || rm.contains(e.target)) return;
                        e.preventDefault();
                        e.stopPropagation();
                        selectedActionId = entry.id;
                        syncEditControls(surface);
                        refreshSurface(surface);
                    });
                    btn.addEventListener('dragstart', function (e) {
                        dragId = entry.id;
                        e.dataTransfer.effectAllowed = 'move';
                        btn.classList.add('is-dragging');
                    });
                    btn.addEventListener('dragend', function () {
                        btn.classList.remove('is-dragging');
                        dragId = null;
                    });
                    btn.addEventListener('dragover', function (e) {
                        e.preventDefault();
                    });
                    btn.addEventListener('drop', function (e) {
                        e.preventDefault();
                        if (!dragId || dragId === entry.id) return;
                        reorderItem(surface, dragId, entry.id);
                    });
                }

                if (!editing) {
                    wireAction(btn, surface, entry, data);
                }

                container.appendChild(btn);
            });

            if (emptyEl) {
                show(emptyEl, visibleCount === 0 && !editing);
            }
        }

        function wireAction(btn, surface, entry, data) {
            if (entry.kind === 'tel') {
                btn.href = telHref(entry.mobile);
                return;
            }
            if (entry.kind === 'sms') {
                btn.href = smsHref(entry.mobile, entry.body || '');
                return;
            }
            if (entry.kind === 'panel_sms') {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    openSmsConfirm({
                        mobile: entry.mobile,
                        smsBody: entry.body || patientDetailsMessage(data),
                        name: data.name,
                    }, surface === 'booking_success' ? bsMsg : msgEl, btn);
                });
                return;
            }

            var id = entry.id;
            if (surface === 'toolbox') {
                if (id === 'call') { btn.href = data.telHref || '#'; return; }
                if (id === 'sms') { btn.href = data.smsHref || '#'; return; }
                if (id === 'edit') { btn.href = data.editUrl || '#'; return; }
                if (id === 'print') { btn.href = data.printUrl || '#'; btn.target = '_blank'; return; }
                if (id === 'surgery') {
                    btn.href = data.surgeryUrl || '#';
                    btn.target = '_blank';
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        window.open(btn.href, 'surgery-register');
                    });
                    return;
                }
                if (id === 'visit') { btn.href = data.visitUrl || '#'; return; }
                if (id === 'patient_file') { btn.href = data.patientUrl || '#'; return; }
                if (id === 'workspace') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        openClinicalWorkspace('photos');
                    });
                    return;
                }
                if (id === 'answers') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        openReadyAnswers();
                    });
                    return;
                }
                if (id === 'bulk_followup') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        openBulkFollowupSheet();
                    });
                    return;
                }
                if (id === 'report_note') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        window.dispatchEvent(new CustomEvent('open-report-note', {
                            detail: {
                                subjectType: data.subjectType,
                                subjectId: data.subjectId,
                                patientId: data.patientId,
                                patientName: data.name || '',
                                mobile: data.mobile || '',
                                mobileSecondary: data.mobileSecondary || '',
                                nationalCode: data.nationalCode || '',
                                meta: data.meta || '',
                                date: data.dateLabel || '',
                            }
                        }));
                    });
                    return;
                }
                if (id === 'change_status') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        openStatusSheet();
                    });
                    return;
                }
                if (id === 'panel_sms') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (isBulk(data)) {
                            confirmBulkSms(data, msgEl, btn);
                            return;
                        }
                        openSmsConfirm(data, msgEl, btn);
                    });
                    return;
                }
                if (id === 'panel_sms_person') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        openSmsConfirm({
                            mobile: entry.mobile,
                            smsBody: entry.body || patientDetailsMessage(data),
                            name: data.name,
                        }, msgEl, btn);
                    });
                    return;
                }
                if (id === 'checklist') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (window.SurgeryChecklistPanel && typeof window.SurgeryChecklistPanel.open === 'function') {
                            window.SurgeryChecklistPanel.open(data);
                        } else {
                            alert('اسکریپت چک‌لیست بارگذاری نشده. Ctrl+F5 بزنید.');
                        }
                    });
                    return;
                }
            }

            if (surface === 'booking_success') {
                if (id === 'open_patient') { btn.href = data.patientUrl || '#'; return; }
                if (id === 'open_board') { btn.href = data.boardUrl || '#'; return; }
                if (id === 'open_print') { btn.href = data.printUrl || '#'; btn.target = '_blank'; return; }
                if (id === 'open_reports') { btn.href = data.reportsUrl || '#'; return; }
                if (id === 'panel_sms') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        openSmsConfirm({
                            mobile: data.mobile,
                            smsBody: data.smsBody || '',
                            name: data.name,
                        }, bsMsg, btn);
                    });
                    return;
                }
                if (id === 'call_patient') { btn.href = telHref(data.mobile); return; }
                if (id === 'sms_patient') { btn.href = smsHref(data.mobile, ''); return; }
                if (id === 'share_details' || id === 'panel_sms_person') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        openSmsConfirm({
                            mobile: entry.mobile,
                            smsBody: id === 'share_details' ? patientDetailsMessage(data) : (entry.body || patientDetailsMessage(data)),
                            name: data.name,
                        }, bsMsg, btn);
                    });
                }
            }
        }

        function removeItem(surface, id) {
            var st = surfaceState(surface);
            st.items = (st.items || []).filter(function (it) { return it.id !== id; });
            refreshSurface(surface);
        }

        function reorderItem(surface, fromId, toId) {
            var st = surfaceState(surface);
            var items = st.items || [];
            var fromIdx = items.findIndex(function (it) { return it.id === fromId; });
            var toIdx = items.findIndex(function (it) { return it.id === toId; });
            if (fromIdx < 0 || toIdx < 0) return;
            var moved = items.splice(fromIdx, 1)[0];
            items.splice(toIdx, 0, moved);
            refreshSurface(surface);
        }

        function refreshSurface(surface) {
            if (surface === 'toolbox') {
                renderActions('toolbox', actionsEl, item || {}, null);
            } else {
                renderActions('booking_success', bsActions, bookingItem || {}, bsEmpty);
            }
        }

        function setEditing(surface, on) {
            editingSurface = on ? surface : null;
            if (!on) selectedActionId = null;
            if (surface === 'toolbox') {
                show(editBar, on);
                panel.classList.toggle('is-editing', on);
                if (on) {
                    var firstTb = (surfaceState('toolbox').items || [])[0];
                    selectedActionId = firstTb ? firstTb.id : null;
                    syncEditControls('toolbox');
                }
                refreshSurface('toolbox');
            } else {
                show(bsEditBar, on);
                bsPanel.classList.toggle('is-editing', on);
                if (on) {
                    var firstBs = (surfaceState('booking_success').items || [])[0];
                    selectedActionId = firstBs ? firstBs.id : null;
                    syncEditControls('booking_success');
                }
                refreshSurface('booking_success');
            }
        }

        async function savePrefs() {
            try {
                var res = await fetch(prefsUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(prefs),
                });
                var data = await res.json().catch(function () { return {}; });
                if (!res.ok) throw new Error(data.message || 'ذخیره نشد');
                if (data.prefs) prefs = data.prefs;
            } catch (e) {
                alert(e.message || 'خطا در ذخیره چیدمان');
            }
        }

        function openAddSheet(surface) {
            addTargetSurface = surface;
            addTitle.textContent = surface === 'booking_success' ? 'افزودن میانبر موفقیت' : 'افزودن دکمه ابزار';
            addBody.innerHTML = '';

            var builtins = builtinsFor(surface);
            var present = {};
            (surfaceState(surface).items || []).forEach(function (it) { present[it.id] = true; });

            var section = document.createElement('div');
            section.className = 'rt-add-section';
            section.innerHTML = '<p class="rt-add-section__title">دکمه‌های مخفی / آماده</p>';
            var hiddenCount = 0;
            Object.keys(builtins).forEach(function (id) {
                if (present[id] && id !== 'share_details' && id !== 'panel_sms_person') return;
                if (present[id] && (id === 'share_details' || id === 'panel_sms_person') && !builtins[id].needs_mobile) return;
                hiddenCount += 1;
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'rt-add-option';
                b.innerHTML = '<span>' + builtins[id].label + '</span><em>نمایش بده</em>';
                b.addEventListener('click', function () {
                    if (builtins[id].needs_mobile || id === 'share_details' || id === 'panel_sms_person') {
                        showCustomForm(surface, id, builtins[id].label);
                        return;
                    }
                    surfaceState(surface).items.push({
                        id: id,
                        kind: 'builtin',
                        style: surfaceState(surface).style || 'soft',
                        accent: surfaceState(surface).accent || '#4f86be',
                        span: builtins[id].full ? 'full' : 'half',
                    });
                    selectedActionId = id;
                    closeAddSheet();
                    refreshSurface(surface);
                    syncEditControls(surface);
                });
                section.appendChild(b);
            });
            if (hiddenCount === 0) {
                var empty = document.createElement('p');
                empty.className = 'rt-add-empty';
                empty.textContent = 'همه دکمه‌های آماده روی ابزار هستند. با × می‌توانی مخفی‌شان کنی تا اینجا برگردند.';
                section.appendChild(empty);
            }
            addBody.appendChild(section);

            var custom = document.createElement('div');
            custom.className = 'rt-add-section';
            custom.innerHTML = '<p class="rt-add-section__title">شخص خاص</p>';
            [['tel', 'تماس با یک نفر'], ['sms', 'پیامک گوشی به یک نفر'], ['panel_sms', 'پیامک پنل به یک نفر']].forEach(function (pair) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'rt-add-option';
                b.innerHTML = '<span>' + pair[1] + '</span><em>جدید</em>';
                b.addEventListener('click', function () {
                    showCustomForm(surface, pair[0], pair[1]);
                });
                custom.appendChild(b);
            });
            addBody.appendChild(custom);

            show(addOverlay, true);
            addOverlay.classList.add('is-open');
            histPush('toolbox-add', function () { closeAddSheet(true); });
        }

        function showCustomForm(surface, kind, title) {
            addTitle.textContent = title;
            addBody.innerHTML = '';
            var form = document.createElement('form');
            form.className = 'rt-add-form';
            form.innerHTML =
                '<label><span>عنوان دکمه</span><input name="label" class="field-input" required maxlength="60" placeholder="مثلاً تماس با منشی"></label>' +
                '<label><span>شماره موبایل</span><input name="mobile" class="field-input ltr-data" dir="ltr" required inputmode="tel" placeholder="09xxxxxxxxx"></label>' +
                (kind === 'sms' || kind === 'panel_sms' || kind === 'panel_sms_person' ? '<label><span>متن ثابت (اختیاری)</span><input name="body" class="field-input" maxlength="200" placeholder="خالی = مشخصات بیمار"></label>' : '') +
                '<button type="submit" class="btn-primary w-full !py-2">افزودن</button>';
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var fd = new FormData(form);
                var mobile = normalizeMobile(fd.get('mobile'));
                if (!mobile) {
                    alert('شماره موبایل معتبر نیست');
                    return;
                }
                var label = String(fd.get('label') || '').trim() || title;
                if (kind === 'share_details' || kind === 'panel_sms_person') {
                    surfaceState(surface).items = (surfaceState(surface).items || []).filter(function (it) {
                        return it.id !== kind;
                    });
                    var builtinItem = {
                        id: kind,
                        kind: 'builtin',
                        label: label,
                        mobile: mobile,
                        style: surfaceState(surface).style || 'soft',
                        accent: surfaceState(surface).accent || '#4f86be',
                        span: 'full',
                    };
                    if (kind === 'panel_sms_person') builtinItem.body = String(fd.get('body') || '');
                    surfaceState(surface).items.push(builtinItem);
                    selectedActionId = kind;
                } else {
                    var newId = kind + '_' + Date.now().toString(36);
                    surfaceState(surface).items.push({
                        id: newId,
                        kind: kind,
                        label: label,
                        mobile: mobile,
                        body: String(fd.get('body') || ''),
                        style: surfaceState(surface).style || 'soft',
                        accent: surfaceState(surface).accent || '#4f86be',
                        span: 'half',
                    });
                    selectedActionId = newId;
                }
                closeAddSheet();
                refreshSurface(surface);
                syncEditControls(surface);
            });
            addBody.appendChild(form);
            form.querySelector('input[name=label]')?.focus();
        }

        function closeAddSheet(fromHistory) {
            var wasOpen = addOverlay && addOverlay.classList.contains('is-open');
            show(addOverlay, false);
            addOverlay.classList.remove('is-open');
            addTargetSurface = null;
            if (wasOpen && !fromHistory) histDismiss('toolbox-add');
        }

        function openReadyAnswers() {
            var detail;
            if (isBulk(item)) {
                detail = {
                    bulk: true,
                    bulkItems: bulkRows(item).slice(),
                    smsEnabled: !!(item && item.smsEnabled),
                };
            } else {
                detail = {
                    mobile: (item && item.mobile) || '',
                    patientName: (item && item.name) || '',
                    patientId: (item && item.patientId) || null,
                    nationalCode: (item && item.nationalCode) || '',
                    mobileSecondary: (item && item.mobileSecondary) || '',
                    booking: (item && item.answerBooking) || null,
                };
            }
            if (window.ReadyAnswers && typeof window.ReadyAnswers.open === 'function') {
                window.ReadyAnswers.open(detail);
                return;
            }
            window.dispatchEvent(new CustomEvent('open-ready-answers', { detail: detail }));
        }

        function fillFollowupSelect(selectEl, map, preferred) {
            if (!selectEl) return;
            selectEl.innerHTML = '';
            var keys = Object.keys(map || {});
            if (!keys.length) {
                var fallback = document.createElement('option');
                fallback.value = preferred || 'custom';
                fallback.textContent = preferred === 'call' ? 'تماس' : 'سفارشی';
                selectEl.appendChild(fallback);
                return;
            }
            keys.forEach(function (key) {
                var opt = document.createElement('option');
                opt.value = key;
                opt.textContent = map[key] || key;
                if (preferred && key === preferred) opt.selected = true;
                selectEl.appendChild(opt);
            });
            if (preferred && map[preferred]) selectEl.value = preferred;
        }

        function followupTimingLabel(amount, unit, direction) {
            var units = { day: 'روز', week: 'هفته', month: 'ماه', hour: 'ساعت' };
            var dirs = { before: 'قبل از رویداد', after: 'بعد از رویداد' };
            amount = Math.max(0, parseInt(amount, 10) || 0);
            if (amount === 0) return 'همان روز رویداد';
            return faNum(amount) + ' ' + (units[unit] || unit) + ' ' + (dirs[direction] || direction);
        }

        function bulkFollowupTargets() {
            return bulkRows(item).filter(function (row) {
                return (row.subjectType === 'surgery' || row.subjectType === 'visit') && row.subjectId;
            });
        }

        function updateBulkFollowupSummary() {
            if (!bulkFollowupForm || !bulkFollowupSummary) return;
            var fd = new FormData(bulkFollowupForm);
            var timing = followupTimingLabel(fd.get('offset_amount'), fd.get('offset_unit'), fd.get('offset_direction'));
            var n = bulkFollowupTargets().length;
            bulkFollowupSummary.textContent = n
                ? ('برای ' + faNum(n) + ' بیمار، سررسید = ' + timing + ' نسبت به تاریخ عمل/نوبت همان بیمار.')
                : 'نوبت معتبری برای پیگیری انتخاب نشده است.';
        }

        function closeBulkFollowupSheet(fromHistory) {
            var wasOpen = bulkFollowupOverlay && bulkFollowupOverlay.classList.contains('is-open');
            bulkFollowupBusy = false;
            setMsg(bulkFollowupMsg, '');
            if (bulkFollowupOverlay) {
                show(bulkFollowupOverlay, false);
                bulkFollowupOverlay.classList.remove('is-open');
                bulkFollowupOverlay.setAttribute('aria-hidden', 'true');
            }
            if (wasOpen && !fromHistory) histDismiss('bulk-followup');
        }

        function openBulkFollowupSheet() {
            if (!followupsEnabled || !bulkFollowupOverlay || !item || !isBulk(item)) return;
            var targets = bulkFollowupTargets();
            if (!targets.length) {
                setMsg(msgEl, 'برای این انتخاب‌ها نوبت عمل/ویزیت معتبری نیست.');
                return;
            }
            fillFollowupSelect(bulkFollowupKind, followupKinds, 'reminder');
            if (bulkFollowupKind && !followupKinds.reminder && bulkFollowupKind.options.length) {
                bulkFollowupKind.selectedIndex = 0;
            }
            fillFollowupSelect(bulkFollowupMethod, followupMethods, 'call');
            if (bulkFollowupCount) {
                bulkFollowupCount.textContent = 'برای ' + faNum(targets.length) + ' بیمار انتخاب‌شده';
            }
            if (bulkFollowupForm) {
                if (!bulkFollowupForm.offset_amount.value) bulkFollowupForm.offset_amount.value = '1';
                if (!bulkFollowupForm.offset_unit.value) bulkFollowupForm.offset_unit.value = 'week';
                if (!bulkFollowupForm.offset_direction.value) bulkFollowupForm.offset_direction.value = 'before';
            }
            setMsg(bulkFollowupMsg, '');
            updateBulkFollowupSummary();
            show(bulkFollowupOverlay, true);
            bulkFollowupOverlay.classList.add('is-open');
            bulkFollowupOverlay.setAttribute('aria-hidden', 'false');
            histPush('bulk-followup', function () { closeBulkFollowupSheet(true); });
        }

        async function submitBulkFollowup(e) {
            if (e) e.preventDefault();
            if (!followupsBulkUrl || !bulkFollowupForm || bulkFollowupBusy) return;
            var targets = bulkFollowupTargets();
            if (!targets.length) {
                setMsg(bulkFollowupMsg, 'نوبت معتبری برای پیگیری نیست.');
                return;
            }
            var fd = new FormData(bulkFollowupForm);
            var payload = {
                items: targets.map(function (row) {
                    return { type: row.subjectType, id: row.subjectId };
                }),
                title: String(fd.get('title') || '').trim(),
                description: String(fd.get('description') || '').trim() || null,
                kind: String(fd.get('kind') || 'custom'),
                method: String(fd.get('method') || 'call'),
                offset_amount: parseInt(fd.get('offset_amount'), 10) || 0,
                offset_unit: String(fd.get('offset_unit') || 'day'),
                offset_direction: String(fd.get('offset_direction') || 'before'),
                due_time: String(fd.get('due_time') || '').trim() || null,
            };
            bulkFollowupBusy = true;
            var submitBtn = root.querySelector('[data-bulk-followup-submit]');
            if (submitBtn) submitBtn.disabled = true;
            setMsg(bulkFollowupMsg, 'در حال ثبت برای ' + faNum(targets.length) + ' بیمار…');
            try {
                var res = await fetch(followupsBulkUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                var data = await res.json().catch(function () { return {}; });
                if (!res.ok) throw new Error(data.message || 'ثبت پیگیری ناموفق بود');
                setMsg(bulkFollowupMsg, data.message || 'پیگیری‌ها ثبت شد');
                window.setTimeout(function () {
                    closeBulkFollowupSheet();
                    closeToolbox();
                    if (data.redirect) window.location.href = data.redirect;
                }, 650);
            } catch (err) {
                setMsg(bulkFollowupMsg, err.message || 'خطا در ثبت پیگیری');
            } finally {
                bulkFollowupBusy = false;
                if (submitBtn) submitBtn.disabled = false;
            }
        }

        function workspaceTab(raw) {
            var t = raw || 'photos';
            if (t === 'whiteboard') return 'drawings';
            if (t === 'prescription' || t === 'rx') return 'rx';
            if (t === 'exam') return 'exams';
            if (t === 'photos' || t === 'drawings' || t === 'exams' || t === 'rx') return t;
            return 'photos';
        }

        function cleanupHoistedGallery() {
            document.querySelectorAll('.pg-modal').forEach(function (el) {
                el.classList.remove('is-open');
                el.setAttribute('hidden', '');
                if (el.parentNode) el.parentNode.removeChild(el);
            });
            document.documentElement.classList.remove('pg-viewer-open');
            document.body.classList.remove('pg-viewer-open');
            if (window.PatientGallery && window.PatientGallery !== window) {
                try { window.PatientGallery = undefined; } catch (err) {}
            }
        }

        function closeWorkspaceFrame(fromHistory) {
            if (!pwOverlay) return;
            var wasOpen = pwOverlay.classList.contains('is-open');
            if (pwFrame) pwFrame.removeAttribute('src');
            cleanupHoistedGallery();
            show(pwOverlay, false);
            pwOverlay.setAttribute('aria-hidden', 'true');
            pwOverlay.classList.remove('is-open');
            if ((!overlay || !overlay.classList.contains('is-open')) && (!bsOverlay || !bsOverlay.classList.contains('is-open'))) {
                document.documentElement.style.overflow = '';
                document.body.style.overflow = '';
            }
            if (wasOpen && !fromHistory) histDismiss('workspace');
        }

        function openWorkspaceFrame(url, title) {
            if (!pwOverlay || !pwFrame) {
                window.location.href = url;
                return;
            }
            if (pwTitle) pwTitle.textContent = title || 'پرونده / گالری';
            pwFrame.src = url;
            show(pwOverlay, true);
            pwOverlay.setAttribute('aria-hidden', 'false');
            pwOverlay.classList.add('is-open');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            histPush('workspace', function () { closeWorkspaceFrame(true); });
        }

        function openClinicalWorkspace(preferredTab) {
            var tab = workspaceTab(preferredTab);
            var snapshot = item;
            if (window.PatientWorkspace && typeof window.PatientWorkspace.openMedia === 'function') {
                window.PatientWorkspace.openMedia(tab);
                return;
            }
            var url = (snapshot && snapshot.patientUrl) || '';
            if (!url) return;
            var sep = url.indexOf('?') >= 0 ? '&' : '?';
            openWorkspaceFrame(url + sep + 'embed=1&open=' + encodeURIComponent(tab), (snapshot && snapshot.name) || 'پرونده / گالری');
        }

        async function sendPanelSms(data, msgTarget, btn) {
            if (!data || !data.mobile || sending) return false;
            sending = true;
            if (btn) btn.disabled = true;
            setMsg(msgTarget, 'در حال ارسال...');
            try {
                var res = await fetch(sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        mobile: data.mobile,
                        message: data.smsBody || ('یادآوری نوبت برای ' + (data.name || '')),
                    }),
                });
                var payload = await res.json().catch(function () { return {}; });
                if (!res.ok) throw new Error(payload.message || 'ارسال ناموفق بود');
                setMsg(msgTarget, payload.message || 'پیامک ارسال شد');
                return true;
            } catch (e) {
                setMsg(msgTarget, e.message || 'خطا در ارسال پیامک');
                return false;
            } finally {
                sending = false;
                if (btn) btn.disabled = false;
            }
        }

        function closeSmsConfirm(fromHistory) {
            var wasOpen = smsConfirmOverlay && smsConfirmOverlay.classList.contains('is-open');
            pendingSms = null;
            if (!smsConfirmOverlay) return;
            show(smsConfirmOverlay, false);
            smsConfirmOverlay.classList.remove('is-open');
            smsConfirmOverlay.setAttribute('aria-hidden', 'true');
            if (wasOpen && !fromHistory) histDismiss('sms-confirm');
        }

        function openSmsConfirm(data, msgTarget, btn) {
            if (!data || !normalizeMobile(data.mobile)) {
                setMsg(msgTarget, 'شماره موبایل معتبر نیست');
                return;
            }
            pendingSms = { data: data, msgTarget: msgTarget, btn: btn };
            if (smsConfirmMobile) smsConfirmMobile.textContent = normalizeMobile(data.mobile);
            if (smsConfirmText) smsConfirmText.textContent = data.smsBody || ('یادآوری نوبت برای ' + (data.name || ''));
            if (!smsConfirmOverlay) {
                sendPanelSms(data, msgTarget, btn);
                return;
            }
            show(smsConfirmOverlay, true);
            smsConfirmOverlay.classList.add('is-open');
            smsConfirmOverlay.setAttribute('aria-hidden', 'false');
            histPush('sms-confirm', function () { closeSmsConfirm(true); });
        }

        function closeBulkConfirm(fromHistory) {
            var wasOpen = bulkConfirmOverlay && bulkConfirmOverlay.classList.contains('is-open');
            pendingBulk = null;
            if (!bulkConfirmOverlay) return;
            show(bulkConfirmOverlay, false);
            bulkConfirmOverlay.classList.remove('is-open');
            bulkConfirmOverlay.setAttribute('aria-hidden', 'true');
            if (wasOpen && !fromHistory) histDismiss('bulk-confirm');
        }

        function openBulkConfirm(opts) {
            pendingBulk = opts || {};
            if (bulkConfirmTitle) bulkConfirmTitle.textContent = pendingBulk.title || 'تأیید عملیات گروهی';
            if (bulkConfirmTo) bulkConfirmTo.textContent = pendingBulk.to || '';
            if (bulkConfirmBody) bulkConfirmBody.textContent = pendingBulk.body || '';
            if (bulkConfirmOk) bulkConfirmOk.textContent = pendingBulk.okLabel || 'بله، مطمئنم';
            if (!bulkConfirmOverlay) {
                if (typeof pendingBulk.onOk === 'function') pendingBulk.onOk();
                pendingBulk = null;
                return;
            }
            show(bulkConfirmOverlay, true);
            bulkConfirmOverlay.classList.add('is-open');
            bulkConfirmOverlay.setAttribute('aria-hidden', 'false');
            histPush('bulk-confirm', function () { closeBulkConfirm(true); });
        }

        function confirmBulkSms(data, msgTarget, btn) {
            var targets = bulkRows(data).filter(function (row) {
                return row.smsEnabled && normalizeMobile(row.mobile);
            });
            if (!targets.length) {
                setMsg(msgTarget, 'برای این انتخاب‌ها شماره معتبری نیست');
                return;
            }
            var sample = targets.slice(0, 2).map(function (row) {
                return (row.name || 'بیمار') + ':\n' + (row.smsBody || ('یادآوری نوبت برای ' + (row.name || '')));
            }).join('\n\n');
            if (targets.length > 2) sample += '\n\n… و ' + faNum(targets.length - 2) + ' پیام دیگر مخصوص هر بیمار';
            openBulkConfirm({
                title: 'ارسال گروهی پیامک؟',
                to: 'برای ' + faNum(targets.length) + ' بیمار — هر کدام پیام مخصوص خودش',
                body: sample,
                okLabel: 'بله، ارسال شود',
                onOk: function () { runBulkSms(targets, msgTarget, btn); },
            });
        }

        async function runBulkSms(targets, msgTarget, btn) {
            var ok = 0;
            var fail = 0;
            if (btn) btn.disabled = true;
            for (var i = 0; i < targets.length; i += 1) {
                setMsg(msgTarget, 'ارسال ' + faNum(i + 1) + ' از ' + faNum(targets.length) + '…');
                var sent = await sendPanelSms(targets[i], msgTarget, null);
                if (sent) ok += 1;
                else fail += 1;
            }
            if (btn) btn.disabled = false;
            if (fail) {
                setMsg(msgTarget, faNum(ok) + ' پیامک ارسال شد، ' + faNum(fail) + ' ناموفق بود');
            } else {
                setMsg(msgTarget, 'پیامک ' + faNum(ok) + ' بیمار ارسال شد');
            }
        }

        function fillInfo(prefix, data) {
            root.querySelectorAll('[data-' + prefix + '-row]').forEach(function (row) {
                var key = row.getAttribute('data-' + prefix + '-row');
                if (key === 'mobileSecondary' && prefix === 'rt') return;
                var value = data[key];
                show(row, !!value);
                var valueEl = row.querySelector('[data-' + prefix + '-text="' + key + '"]');
                if (valueEl) valueEl.textContent = value || '';
            });
        }

        function syncMobile2Form() {
            var row = root.querySelector('[data-rt-mobile2-row]');
            var input = root.querySelector('[data-rt-mobile2-input]');
            if (!row) return;
            var canEdit = !!(item && (
                (item.sheetMode === 'report' && item.subjectType && item.subjectId) ||
                (item.sheetMode === 'patient' && item.patientId)
            ));
            show(row, canEdit);
            if (input) {
                input.value = (item && item.mobileSecondary) ? item.mobileSecondary : '';
                input.dataset.saved = input.value;
            }
            row.classList.remove('is-copied');
        }

        function encodeToolboxPayload(data) {
            var json = JSON.stringify(data);
            var bytes = new TextEncoder().encode(json);
            var bin = '';
            for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
            return btoa(bin);
        }

        function persistToolboxSource() {
            if (!item) return;
            var encoded = encodeToolboxPayload(item);
            var nodes = [];
            if (toolboxSourceEl) nodes.push(toolboxSourceEl);
            var tr = toolboxSourceEl && toolboxSourceEl.closest ? toolboxSourceEl.closest('tr') : null;
            if (tr) {
                tr.querySelectorAll('[data-toolbox-b64]').forEach(function (el) { nodes.push(el); });
            }
            nodes.forEach(function (el) {
                if (el && el.setAttribute) el.setAttribute('data-toolbox-b64', encoded);
            });
        }

        function saveMobileSecondary() {
            if (!item) return;
            var input = root.querySelector('[data-rt-mobile2-input]');
            var row = root.querySelector('[data-rt-mobile2-row]');
            var raw = input ? String(input.value || '').replace(/\D+/g, '').slice(0, 11) : '';
            if (input) input.value = raw;
            if (raw === (input && input.dataset.saved || '')) return;

            var url = null;
            var body = { mobile_secondary: raw };
            if (item.sheetMode === 'report' && item.subjectType && item.subjectId && mobile2Url) {
                url = mobile2Url;
                body.subject_type = item.subjectType;
                body.subject_id = item.subjectId;
            } else if (item.sheetMode === 'patient' && item.patientId && patientMobile2Base) {
                url = patientMobile2Base.replace(/\/$/, '') + '/' + item.patientId + '/mobile-secondary';
            }
            if (!url) return;

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            }).then(function (res) {
                return res.json().then(function (data) { return { res: res, data: data }; });
            }).then(function (pack) {
                if (!pack.res.ok) throw new Error((pack.data && pack.data.message) || 'ثبت شماره ناموفق بود');
                item.mobileSecondary = pack.data.mobile_secondary || '';
                if (input) {
                    input.value = item.mobileSecondary || '';
                    input.dataset.saved = input.value;
                }
                persistToolboxSource();
                if (row) flashCopied(row);
            }).catch(function (err) {
                if (input) input.value = input.dataset.saved || '';
                window.alert(err.message || 'خطا در ثبت شماره');
            });
        }

        function copyText(text) {
            text = String(text || '').trim();
            if (!text) return Promise.reject();
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }
            return new Promise(function (resolve, reject) {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', '');
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy') ? resolve() : reject();
                } catch (err) {
                    reject(err);
                }
                document.body.removeChild(ta);
            });
        }

        function flashCopied(row) {
            if (!row) return;
            row.classList.add('is-copied');
            window.setTimeout(function () { row.classList.remove('is-copied'); }, 1200);
        }

        function closeStatusSheet(fromHistory) {
            var wasOpen = statusOverlay && statusOverlay.classList.contains('is-open');
            pendingStatus = null;
            statusBusy = false;
            if (statusConfirmBox) show(statusConfirmBox, false);
            if (statusList) show(statusList, true);
            setMsg(statusMsg, '');
            if (statusOverlay) {
                show(statusOverlay, false);
                statusOverlay.classList.remove('is-open');
                statusOverlay.setAttribute('aria-hidden', 'true');
            }
            if (wasOpen && !fromHistory) histDismiss('status-sheet');
        }

        function renderStatusActions() {
            if (!statusList || !item) return;
            statusList.innerHTML = '';
            var actions = item.statusActions || [];
            if (!actions.length) {
                statusList.innerHTML = '<p class="rt-status-sheet__empty">برای این نوبت تغییر وضعیت مجاز نیست.</p>';
                return;
            }
            actions.forEach(function (action) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'rt-status-btn';
                if (action.id === 'cancelled' || action.id === 'no_show') btn.classList.add('is-danger');
                if (action.id === 'confirmed' || action.id === 'done') btn.classList.add('is-ok');
                btn.textContent = action.label;
                btn.addEventListener('click', function () {
                    pendingStatus = action;
                    var applicable = bulkRows(item).filter(function (row) {
                        return row.canChangeStatus && row.statusUrl && (row.statusActions || []).some(function (a) { return a.id === action.id; });
                    });
                    var count = applicable.length;
                    if (statusConfirmTitle) statusConfirmTitle.textContent = action.label;
                    if (statusConfirmText) {
                        statusConfirmText.textContent = isBulk(item)
                            ? ('برای ' + faNum(count) + ' بیمار می‌خواهید وضعیت را به «' + (action.label || '') + '» تغییر دهید؟ مطمئنید؟')
                            : (action.hint || 'این تغییر اعمال شود؟');
                    }
                    show(statusList, false);
                    show(statusConfirmBox, true);
                    setMsg(statusMsg, '');
                });
                statusList.appendChild(btn);
            });
        }

        function openStatusSheet() {
            if (!item) return;
            if (!isBulk(item) && (!item.canChangeStatus || !item.statusUrl)) return;
            if (isBulk(item) && !(item.statusActions || []).length) return;
            pendingStatus = null;
            if (statusCurrent) {
                statusCurrent.textContent = isBulk(item)
                    ? (faNum(bulkRows(item).length) + ' بیمار انتخاب شده')
                    : (item.statusLabel ? ('وضعیت فعلی: ' + item.statusLabel) : '');
            }
            renderStatusActions();
            if (statusConfirmBox) show(statusConfirmBox, false);
            if (statusList) show(statusList, true);
            setMsg(statusMsg, '');
            show(statusOverlay, true);
            statusOverlay.classList.add('is-open');
            statusOverlay.setAttribute('aria-hidden', 'false');
            histPush('status-sheet', function () { closeStatusSheet(true); });
        }

        async function confirmStatusChange() {
            if (!pendingStatus || !item || statusBusy) return;
            var targets = bulkRows(item).filter(function (row) {
                return row.canChangeStatus && row.statusUrl && (row.statusActions || []).some(function (a) {
                    return a.id === pendingStatus.id;
                });
            });
            if (!targets.length) {
                setMsg(statusMsg, 'برای این انتخاب‌ها تغییر وضعیت مجاز نیست.');
                return;
            }
            statusBusy = true;
            setMsg(statusMsg, isBulk(item) ? ('در حال اعمال برای ' + faNum(targets.length) + ' بیمار…') : 'در حال اعمال…');
            var ok = 0;
            var fail = 0;
            var lastError = '';
            try {
                for (var i = 0; i < targets.length; i += 1) {
                    var row = targets[i];
                    var fd = new FormData();
                    fd.append('_token', csrf);
                    fd.append('_method', 'PATCH');
                    fd.append('status', pendingStatus.id);
                    var res = await fetch(row.statusUrl, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd,
                    });
                    var data = await res.json().catch(function () { return {}; });
                    if (!res.ok) {
                        fail += 1;
                        lastError = data.message
                            || (data.errors && data.errors.status && data.errors.status[0])
                            || 'تغییر وضعیت انجام نشد.';
                        continue;
                    }
                    ok += 1;
                    if (!isBulk(item) && data.redirect) {
                        closeStatusSheet();
                        closeToolbox();
                        window.location.href = data.redirect;
                        return;
                    }
                }
                if (!ok) {
                    setMsg(statusMsg, lastError || 'تغییر وضعیت انجام نشد.');
                    statusBusy = false;
                    return;
                }
                closeStatusSheet();
                closeToolbox();
                window.location.reload();
            } catch (e) {
                setMsg(statusMsg, e.message || 'خطا در تغییر وضعیت');
                statusBusy = false;
            }
        }

        function openToolbox(detail) {
            item = detail || {};
            setEditing('toolbox', false);
            setMsg(msgEl, '');
            var bulk = isBulk(item);
            root.querySelector('[data-rt-name]').textContent = bulk
                ? ('عملیات گروهی · ' + faNum(item.bulkItems.length) + ' بیمار')
                : (item.name || 'جعبه ابزار');
            fillInfo('rt', item);
            var infoBox = root.querySelector('.row-toolbox-info');
            if (infoBox) show(infoBox, !bulk);
            var editBtn = root.querySelector('[data-rt-edit]');
            if (editBtn) show(editBtn, !bulk);
            if (!bulk) syncMobile2Form();
            else {
                var mobile2Row = root.querySelector('[data-rt-mobile2-row]');
                if (mobile2Row) show(mobile2Row, false);
            }
            var smsPreviewBox = root.querySelector('[data-rt-sms-preview]');
            var smsPreviewText = root.querySelector('[data-rt-sms-preview-text]');
            if (smsPreviewBox && smsPreviewText) {
                if (bulk) {
                    smsPreviewText.textContent = 'هر بیمار پیام مخصوص خودش را از تنظیمات تایم‌ها می‌گیرد.';
                    show(smsPreviewBox, true);
                } else if (item.smsBody) {
                    smsPreviewText.textContent = item.smsBody;
                    show(smsPreviewBox, true);
                } else {
                    smsPreviewText.textContent = '';
                    show(smsPreviewBox, false);
                }
            }
            panel.classList.toggle('row-toolbox-panel--patient', item.sheetMode === 'patient');
            panel.classList.toggle('row-toolbox-panel--bulk', bulk);
            var bulkHint = root.querySelector('[data-rt-bulk-hint]');
            if (bulkHint) show(bulkHint, bulk);

            // Ensure report note action is available on reports page even if prefs omit it.
            if (item.sheetMode === 'report' && !bulk) {
                var st = surfaceState('toolbox');
                var hasNote = (st.items || []).some(function (entry) { return entry.id === 'report_note'; });
                if (!hasNote) {
                    st.items = (st.items || []).concat([{ id: 'report_note', kind: 'builtin' }]);
                }
            }

            // Bulk report tools — inject even if user prefs hid them.
            if (bulk) {
                var stBulk = surfaceState('toolbox');
                var bulkNeeded = [];
                if (item.smsEnabled || bulkRows(item).some(function (row) { return row.smsEnabled && row.mobile; })) {
                    bulkNeeded.push('panel_sms');
                }
                if (item.canChangeStatus || (item.statusActions || []).length) {
                    bulkNeeded.push('change_status');
                }
                if (item.readyAnswersEnabled !== false) {
                    bulkNeeded.push('answers');
                }
                if (followupsEnabled) {
                    bulkNeeded.push('bulk_followup');
                }
                bulkNeeded.forEach(function (id) {
                    var exists = (stBulk.items || []).some(function (entry) { return entry.id === id; });
                    if (!exists) {
                        stBulk.items = (stBulk.items || []).concat([{ id: id, kind: 'builtin', span: 'full' }]);
                    }
                });
            }

            if (item.canChangeStatus && (item.statusUrl || bulk) && (item.statusActions || []).length) {
                var stSt = surfaceState('toolbox');
                var hasStatus = (stSt.items || []).some(function (entry) { return entry.id === 'change_status'; });
                if (!hasStatus) {
                    var editIdx = (stSt.items || []).findIndex(function (entry) { return entry.id === 'edit'; });
                    var insertAt = editIdx >= 0 ? editIdx + 1 : 0;
                    stSt.items = (stSt.items || []).slice();
                    stSt.items.splice(insertAt, 0, { id: 'change_status', kind: 'builtin', span: 'full' });
                }
            }

            // Ensure checklist button exists for surgery appointments (prefs saved before this feature).
            if (!bulk && item.hasSurgeryChecklist && item.surgeryAppointmentId) {
                var stCl = surfaceState('toolbox');
                var hasCl = (stCl.items || []).some(function (entry) { return entry.id === 'checklist'; });
                if (!hasCl) {
                    var printIdx = (stCl.items || []).findIndex(function (entry) { return entry.id === 'print'; });
                    var insertAt = printIdx >= 0 ? printIdx + 1 : (stCl.items || []).length;
                    stCl.items = (stCl.items || []).slice();
                    stCl.items.splice(insertAt, 0, { id: 'checklist', kind: 'builtin' });
                }
            }

            renderActions('toolbox', actionsEl, item, null);
            if (bulk && actionsEl && !actionsEl.children.length) {
                var emptyBulk = document.createElement('p');
                emptyBulk.className = 'row-toolbox-bulk-hint';
                emptyBulk.textContent = 'برای این انتخاب‌ها ابزار گروهی فعالی نیست (پیامک پنل یا تغییر وضعیت).';
                actionsEl.appendChild(emptyBulk);
            }
            show(overlay, true);
            overlay.classList.add('is-open');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            histPush('toolbox', function () { closeToolbox(true); });

            if (item.openChecklistOnOpen && item.surgeryAppointmentId && window.SurgeryChecklistPanel) {
                window.SurgeryChecklistPanel.open(item);
            }
        }

        function closeToolbox(fromHistory) {
            var wasOpen = overlay && overlay.classList.contains('is-open');
            item = null;
            toolboxSourceEl = null;
            setEditing('toolbox', false);
            closeStatusSheet(true);
            closeSmsConfirm(true);
            closeBulkConfirm(true);
            show(overlay, false);
            overlay.classList.remove('is-open');
            if ((!pwOverlay || !pwOverlay.classList.contains('is-open')) && (!bsOverlay || !bsOverlay.classList.contains('is-open'))) {
                var noteEl = document.querySelector('.rn-overlay');
                var noteOpen = noteEl && window.getComputedStyle(noteEl).display !== 'none';
                var clOpen = document.querySelector('.scl-overlay.is-open');
                var ansEl = document.querySelector('.ans-overlay');
                var ansOpen = ansEl && window.getComputedStyle(ansEl).display !== 'none';
                if (!noteOpen && !clOpen && !ansOpen) {
                    document.documentElement.style.overflow = '';
                    document.body.style.overflow = '';
                }
            }
            if (wasOpen && !fromHistory) histDismiss('toolbox');
        }

        function openBookingSuccess(detail) {
            bookingItem = detail || {};
            setEditing('booking_success', false);
            setMsg(bsMsg, '');
            var stBs = surfaceState('booking_success');
            var hasPanelSms = (stBs.items || []).some(function (entry) { return entry.id === 'panel_sms'; });
            if (!hasPanelSms) {
                stBs.items = (stBs.items || []).slice();
                var reportsIdx = stBs.items.findIndex(function (entry) { return entry.id === 'open_reports'; });
                var insertAt = reportsIdx >= 0 ? reportsIdx + 1 : stBs.items.length;
                stBs.items.splice(insertAt, 0, { id: 'panel_sms', kind: 'builtin' });
            }
            var title = root.querySelector('[data-bs-name]');
            var sub = root.querySelector('[data-bs-sub]');
            if (title) title.textContent = 'با موفقیت ثبت شد';
            if (sub) sub.textContent = bookingItem.name || '';
            fillInfo('bs', bookingItem);
            renderActions('booking_success', bsActions, bookingItem, bsEmpty);
            show(bsOverlay, true);
            bsOverlay.classList.add('is-open');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            histPush('booking-success', function () { closeBookingSuccess(true); });
        }

        function closeBookingSuccess(fromHistory) {
            var wasOpen = bsOverlay && bsOverlay.classList.contains('is-open');
            var shouldCloseTab = !!(bookingItem && bookingItem.closeTab);
            var dashboardUrl = @js(url('/dashboard'));
            bookingItem = null;
            setEditing('booking_success', false);
            show(bsOverlay, false);
            bsOverlay.classList.remove('is-open');
            if ((!pwOverlay || !pwOverlay.classList.contains('is-open')) && (!overlay || !overlay.classList.contains('is-open'))) {
                document.documentElement.style.overflow = '';
                document.body.style.overflow = '';
            }
            if (wasOpen && !fromHistory) histDismiss('booking-success');
            if (shouldCloseTab && !fromHistory) {
                window.close();
                window.setTimeout(function () {
                    window.location.replace(dashboardUrl);
                }, 200);
            }
        }

        function payloadOf(el) {
            try {
                var b64 = el.getAttribute('data-toolbox-b64');
                if (b64) {
                    var bytes = Uint8Array.from(atob(b64), function (c) { return c.charCodeAt(0); });
                    return JSON.parse(new TextDecoder().decode(bytes));
                }
                return JSON.parse(el.getAttribute('data-toolbox') || '{}');
            } catch (err) {
                return null;
            }
        }

        root.querySelector('[data-rt-close]').addEventListener('click', closeToolbox);
        var mobile2Input = root.querySelector('[data-rt-mobile2-input]');
        var mobile2Row = root.querySelector('[data-rt-mobile2-row]');
        if (mobile2Input) {
            mobile2Input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    mobile2Input.blur();
                }
            });
            mobile2Input.addEventListener('input', function () {
                mobile2Input.value = String(mobile2Input.value || '').replace(/\D+/g, '').slice(0, 11);
            });
            mobile2Input.addEventListener('blur', saveMobileSecondary);
        }
        if (mobile2Row && mobile2Input) {
            mobile2Row.addEventListener('click', function (e) {
                if (e.target === mobile2Input) return;
                mobile2Input.focus();
            });
        }
        root.querySelector('[data-bs-close]').addEventListener('click', closeBookingSuccess);
        root.querySelector('[data-rt-edit]').addEventListener('click', function () {
            setEditing('toolbox', editingSurface !== 'toolbox');
        });
        root.querySelector('[data-bs-edit]').addEventListener('click', function () {
            setEditing('booking_success', editingSurface !== 'booking_success');
        });
        root.querySelector('[data-rt-add]').addEventListener('click', function () { openAddSheet('toolbox'); });
        root.querySelector('[data-bs-add]').addEventListener('click', function () { openAddSheet('booking_success'); });
        root.querySelector('[data-rt-done]').addEventListener('click', function () {
            setEditing('toolbox', false);
            savePrefs();
        });
        root.querySelector('[data-bs-done]').addEventListener('click', function () {
            setEditing('booking_success', false);
            savePrefs();
        });
        styleSel.addEventListener('change', function () {
            var entry = ensureSelectedEntry('toolbox');
            if (!entry) return;
            entry.style = styleSel.value;
            refreshSurface('toolbox');
        });
        accentInp.addEventListener('input', function () {
            var entry = ensureSelectedEntry('toolbox');
            if (!entry) return;
            entry.accent = accentInp.value;
            refreshSurface('toolbox');
        });
        if (spanSel) {
            spanSel.addEventListener('change', function () {
                var entry = ensureSelectedEntry('toolbox');
                if (!entry) return;
                entry.span = spanSel.value === 'full' ? 'full' : 'half';
                refreshSurface('toolbox');
            });
        }
        bsStyleSel.addEventListener('change', function () {
            var entry = ensureSelectedEntry('booking_success');
            if (!entry) return;
            entry.style = bsStyleSel.value;
            refreshSurface('booking_success');
        });
        bsAccentInp.addEventListener('input', function () {
            var entry = ensureSelectedEntry('booking_success');
            if (!entry) return;
            entry.accent = bsAccentInp.value;
            refreshSurface('booking_success');
        });
        if (bsSpanSel) {
            bsSpanSel.addEventListener('change', function () {
                var entry = ensureSelectedEntry('booking_success');
                if (!entry) return;
                entry.span = bsSpanSel.value === 'full' ? 'full' : 'half';
                refreshSurface('booking_success');
            });
        }

        overlay.addEventListener('click', function (e) { if (e.target === overlay) closeToolbox(); });
        bsOverlay.addEventListener('click', function (e) { if (e.target === bsOverlay) closeBookingSuccess(); });
        addOverlay.addEventListener('click', function (e) { if (e.target === addOverlay) closeAddSheet(); });
        root.querySelector('[data-add-close]').addEventListener('click', closeAddSheet);

        if (smsConfirmCancel) {
            smsConfirmCancel.addEventListener('click', closeSmsConfirm);
        }
        if (smsConfirmOk) {
            smsConfirmOk.addEventListener('click', function () {
                var pending = pendingSms;
                closeSmsConfirm();
                if (!pending) return;
                sendPanelSms(pending.data, pending.msgTarget, pending.btn);
            });
        }
        if (smsConfirmOverlay) {
            smsConfirmOverlay.addEventListener('click', function (e) {
                if (e.target === smsConfirmOverlay) closeSmsConfirm();
            });
        }

        if (bulkConfirmCancel) bulkConfirmCancel.addEventListener('click', closeBulkConfirm);
        if (bulkConfirmOk) {
            bulkConfirmOk.addEventListener('click', function () {
                var pending = pendingBulk;
                closeBulkConfirm();
                if (pending && typeof pending.onOk === 'function') pending.onOk();
            });
        }
        if (bulkConfirmOverlay) {
            bulkConfirmOverlay.addEventListener('click', function (e) {
                if (e.target === bulkConfirmOverlay) closeBulkConfirm();
            });
        }

        root.addEventListener('click', function (e) {
            var copyRow = e.target.closest('[data-rt-copy], [data-bs-copy]');
            if (!copyRow) return;
            var key = copyRow.getAttribute('data-rt-copy') || copyRow.getAttribute('data-bs-copy');
            var source = copyRow.hasAttribute('data-bs-copy') ? bookingItem : item;
            if (!source || !key || !source[key]) return;
            e.preventDefault();
            copyText(source[key]).then(function () {
                flashCopied(copyRow);
            }).catch(function () {});
        });

        if (statusOverlay) {
            var statusClose = root.querySelector('[data-status-close]');
            var statusCancel = root.querySelector('[data-status-confirm-cancel]');
            var statusOk = root.querySelector('[data-status-confirm-ok]');
            if (statusClose) statusClose.addEventListener('click', closeStatusSheet);
            if (statusCancel) {
                statusCancel.addEventListener('click', function () {
                    pendingStatus = null;
                    show(statusConfirmBox, false);
                    show(statusList, true);
                    setMsg(statusMsg, '');
                });
            }
            if (statusOk) statusOk.addEventListener('click', confirmStatusChange);
            statusOverlay.addEventListener('click', function (e) {
                if (e.target === statusOverlay) closeStatusSheet();
            });
        }

        if (bulkFollowupOverlay) {
            root.querySelectorAll('[data-bulk-followup-close]').forEach(function (btn) {
                btn.addEventListener('click', function () { closeBulkFollowupSheet(); });
            });
            bulkFollowupOverlay.addEventListener('click', function (e) {
                if (e.target === bulkFollowupOverlay) closeBulkFollowupSheet();
            });
            if (bulkFollowupForm) {
                bulkFollowupForm.addEventListener('submit', submitBulkFollowup);
                bulkFollowupForm.addEventListener('input', updateBulkFollowupSummary);
                bulkFollowupForm.addEventListener('change', updateBulkFollowupSummary);
            }
            var presets = root.querySelector('[data-bulk-followup-presets]');
            if (presets) {
                presets.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-preset]');
                    if (!btn || !bulkFollowupForm) return;
                    var parts = String(btn.getAttribute('data-preset') || '').split('|');
                    if (parts.length < 3) return;
                    bulkFollowupForm.offset_amount.value = parts[0];
                    bulkFollowupForm.offset_unit.value = parts[1];
                    bulkFollowupForm.offset_direction.value = parts[2];
                    updateBulkFollowupSummary();
                });
            }
        }

        if (pwClose) pwClose.addEventListener('click', closeWorkspaceFrame);
        if (pwOverlay) {
            pwOverlay.addEventListener('click', function (e) {
                if (e.target === pwOverlay) closeWorkspaceFrame();
            });
        }

        window.addEventListener('message', function (e) {
            if (!e.data || e.data.type !== 'patient-workspace-close') return;
            closeWorkspaceFrame();
        });

            document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (document.querySelector('.pg-modal.is-open')) return;
            if (window.PatientGallery && window.PatientGallery.isOpen && window.PatientGallery.isOpen()) return;
            var clOverlay = document.querySelector('[data-cl-overlay]');
            if (clOverlay && (clOverlay.classList.contains('is-open') || clOverlay.style.display === 'flex')) {
                if (window.SurgeryChecklistPanel) window.SurgeryChecklistPanel.close();
                return;
            }
            if (smsConfirmOverlay && smsConfirmOverlay.classList.contains('is-open')) { closeSmsConfirm(); return; }
            if (bulkConfirmOverlay && bulkConfirmOverlay.classList.contains('is-open')) { closeBulkConfirm(); return; }
            if (bulkFollowupOverlay && bulkFollowupOverlay.classList.contains('is-open')) { closeBulkFollowupSheet(); return; }
            if (statusOverlay && statusOverlay.classList.contains('is-open')) { closeStatusSheet(); return; }
            if (addOverlay.classList.contains('is-open')) { closeAddSheet(); return; }
            if (pwOverlay && pwOverlay.classList.contains('is-open')) { closeWorkspaceFrame(); return; }
            if (bsOverlay.classList.contains('is-open')) { closeBookingSuccess(); return; }
            if (overlay.classList.contains('is-open')) closeToolbox();
        });

        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-bulk-toolbox]')) return;
            if (document.querySelector('.report-page--bulk')) return;
            var trigger = e.target.closest('[data-toolbox-trigger]');
            var row = trigger || e.target.closest('[data-toolbox-b64], [data-toolbox]');
            if (!row || root.contains(row)) return;
            var isToolboxTarget = row.hasAttribute('data-toolbox-b64') || row.hasAttribute('data-toolbox');
            if (!trigger && !isToolboxTarget && e.target.closest('a, button, input, label, select, textarea')) return;
            var payload = payloadOf(row);
            if (!payload) return;
            e.preventDefault();
            e.stopPropagation();
            toolboxSourceEl = row;
            openToolbox(payload);
        }, true);

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var row = e.target.closest && e.target.closest('[data-toolbox-b64], [data-toolbox]');
            if (!row || root.contains(row)) return;
            var isToolboxTarget = row.hasAttribute('data-toolbox-b64') || row.hasAttribute('data-toolbox');
            if (!isToolboxTarget && e.target.closest('a, button, input, select, textarea')) return;
            var payload = payloadOf(row);
            if (!payload) return;
            e.preventDefault();
            openToolbox(payload);
        });

        window.addEventListener('open-row-toolbox', function (e) { openToolbox(e.detail); });
        window.addEventListener('open-booking-success', function (e) { openBookingSuccess(e.detail); });

        window.RowToolbox = {
            open: openToolbox,
            close: closeToolbox,
            openWorkspace: openClinicalWorkspace,
            closeWorkspace: closeWorkspaceFrame,
            openBookingSuccess: openBookingSuccess,
        };

        if (initialBookingSuccess) {
            setTimeout(function () { openBookingSuccess(initialBookingSuccess); }, 250);
        }
    })();
</script>
