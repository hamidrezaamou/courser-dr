<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'mode' => 'toolbox',
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
    'mode' => 'toolbox',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $toolboxPrefs = \App\Support\ToolboxPrefs::normalize(auth()->user()?->toolbox_prefs);
    $toolboxBuiltins = \App\Support\ToolboxPrefs::toolboxBuiltins();
    $bookingBuiltins = \App\Support\ToolboxPrefs::bookingBuiltins();
    $bookingSuccess = session('booking_success');
?>


<div
    id="row-toolbox-root"
    data-prefs-url="<?php echo e(route('toolbox-prefs.update')); ?>"
    data-sms-url="<?php echo e(route('sms.send')); ?>"
    data-checklist-ensure-base="/surgery-appointments/__ID__/checklist/ensure"
    data-checklist-api-base="/surgery-checklists"
    data-csrf="<?php echo e(csrf_token()); ?>"
>
    <div class="row-toolbox-overlay" data-rt-overlay style="display:none">
        <div class="row-toolbox-panel" data-rt-panel data-surface="toolbox">
            <button type="button" class="row-toolbox-edit" data-rt-edit aria-label="ویرایش ابزار" title="سفارشی‌سازی">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 113 3L8.25 18.1 4 19l.9-4.25L16.862 3.487z"/></svg>
            </button>
            <button type="button" class="row-toolbox-close" data-rt-close aria-label="بستن"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3baa94417da9f5dcb14f5f04da758571 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon-close','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon-close'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $attributes = $__attributesOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $component = $__componentOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__componentOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?></button>
            <h4 class="row-toolbox-title" data-rt-name></h4>

            <div class="row-toolbox-info">
                <div class="row-toolbox-row" data-rt-row="nationalCode">
                    <span class="lbl">کد ملی</span>
                    <span class="val" dir="ltr" data-rt-text="nationalCode"></span>
                </div>
                <div class="row-toolbox-row" data-rt-row="mobile">
                    <span class="lbl">موبایل</span>
                    <span class="val" dir="ltr" data-rt-text="mobile"></span>
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
                <p class="row-toolbox-editbar__hint" data-rt-color-hint>روی یک دکمه بزن، بعد رنگش را عوض کن</p>
                <button type="button" class="btn-secondary !py-1.5 !px-3 !text-xs" data-rt-add>+ افزودن</button>
                <button type="button" class="btn-primary !py-1.5 !px-3 !text-xs" data-rt-done>تمام</button>
            </div>

            <div class="row-toolbox-actions" data-rt-actions></div>
            <p class="row-toolbox-msg" data-rt-msg style="display:none"></p>
        </div>
    </div>

    
    <div class="row-toolbox-overlay" data-bs-overlay style="display:none">
        <div class="row-toolbox-panel row-toolbox-panel--success" data-bs-panel data-surface="booking_success">
            <button type="button" class="row-toolbox-edit" data-bs-edit aria-label="ویرایش میانبرها" title="سفارشی‌سازی">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 113 3L8.25 18.1 4 19l.9-4.25L16.862 3.487z"/></svg>
            </button>
            <button type="button" class="row-toolbox-close" data-bs-close aria-label="بستن"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3baa94417da9f5dcb14f5f04da758571 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon-close','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon-close'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $attributes = $__attributesOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $component = $__componentOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__componentOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?></button>
            <div class="row-toolbox-success-head">
                <span class="row-toolbox-success-badge">ثبت شد</span>
                <h4 class="row-toolbox-title" data-bs-name>با موفقیت ثبت شد</h4>
                <p class="row-toolbox-success-sub" data-bs-sub></p>
            </div>
            <div class="row-toolbox-info">
                <div class="row-toolbox-row" data-bs-row="nationalCode">
                    <span class="lbl">کد ملی</span>
                    <span class="val" dir="ltr" data-bs-text="nationalCode"></span>
                </div>
                <div class="row-toolbox-row" data-bs-row="mobile">
                    <span class="lbl">موبایل</span>
                    <span class="val" dir="ltr" data-bs-text="mobile"></span>
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
                <p class="row-toolbox-editbar__hint" data-bs-color-hint>یک دکمه را انتخاب کن، بعد فقط همان را رنگ/سبک بده</p>
                <button type="button" class="btn-secondary !py-1.5 !px-3 !text-xs" data-bs-add>+ افزودن</button>
                <button type="button" class="btn-primary !py-1.5 !px-3 !text-xs" data-bs-done>تمام</button>
            </div>
            <div class="row-toolbox-actions" data-bs-actions></div>
            <p class="row-toolbox-empty" data-bs-empty>هنوز میانبری نیست. با مداد، دکمه‌های دلخواهت را بچین.</p>
            <p class="row-toolbox-msg" data-bs-msg style="display:none"></p>
        </div>
    </div>

    
    <div class="rt-add-overlay" data-add-overlay style="display:none">
        <div class="rt-add-sheet" role="dialog" aria-modal="true" aria-label="افزودن دکمه">
            <div class="rt-add-sheet__head">
                <strong data-add-title>افزودن دکمه</strong>
                <button type="button" class="tg-tool" data-add-close aria-label="بستن"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3baa94417da9f5dcb14f5f04da758571 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon-close','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon-close'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $attributes = $__attributesOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $component = $__componentOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__componentOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?></button>
            </div>
            <div class="rt-add-sheet__body" data-add-body></div>
        </div>
    </div>

    
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

    
    <div class="patient-workspace-overlay" data-pw-overlay style="display:none" aria-hidden="true">
        <div class="patient-workspace-shell" role="dialog" aria-modal="true" aria-label="پرونده بالینی">
            <div class="patient-workspace-bar">
                <strong class="patient-workspace-bar__title" data-pw-title>پرونده / گالری</strong>
                <button type="button" class="patient-workspace-bar__close" data-pw-close aria-label="بستن">
                    <?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3baa94417da9f5dcb14f5f04da758571 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon-close','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon-close'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $attributes = $__attributesOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $component = $__componentOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__componentOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?>
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
        var csrf = root.getAttribute('data-csrf') || (document.querySelector('meta[name=csrf-token]') || {}).content || '';

        var prefs = <?php echo \Illuminate\Support\Js::from($toolboxPrefs)->toHtml() ?>;
        var toolboxBuiltins = <?php echo \Illuminate\Support\Js::from($toolboxBuiltins)->toHtml() ?>;
        var bookingBuiltins = <?php echo \Illuminate\Support\Js::from($bookingBuiltins)->toHtml() ?>;
        var initialBookingSuccess = <?php echo \Illuminate\Support\Js::from($bookingSuccess)->toHtml() ?>;

        var overlay = root.querySelector('[data-rt-overlay]');
        var panel = root.querySelector('[data-rt-panel]');
        var actionsEl = root.querySelector('[data-rt-actions]');
        var msgEl = root.querySelector('[data-rt-msg]');
        var editBar = root.querySelector('[data-rt-editbar]');
        var styleSel = root.querySelector('[data-rt-style]');
        var accentInp = root.querySelector('[data-rt-accent]');

        var bsOverlay = root.querySelector('[data-bs-overlay]');
        var bsPanel = root.querySelector('[data-bs-panel]');
        var bsActions = root.querySelector('[data-bs-actions]');
        var bsMsg = root.querySelector('[data-bs-msg]');
        var bsEmpty = root.querySelector('[data-bs-empty]');
        var bsEditBar = root.querySelector('[data-bs-editbar]');
        var bsStyleSel = root.querySelector('[data-bs-style]');
        var bsAccentInp = root.querySelector('[data-bs-accent]');

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
        var smsConfirmCancel = root.querySelector('[data-sms-confirm-cancel]');
        var smsConfirmOk = root.querySelector('[data-sms-confirm-ok]');
        var pendingSms = null;

        var item = null;
        var bookingItem = null;
        var editingSurface = null;
        var addTargetSurface = null;
        var sending = false;
        var dragId = null;
        var selectedActionId = null;

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
            if (surface === 'booking_success') {
                if (bsStyleSel) bsStyleSel.value = style;
                if (bsAccentInp) bsAccentInp.value = accent;
            } else {
                if (styleSel) styleSel.value = style;
                if (accentInp) accentInp.value = accent;
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
            if (data.meta) lines.push('جزئیات: ' + data.meta);
            return lines.join('\n').slice(0, 900);
        }

        function labelOf(surface, entry) {
            if (entry.kind === 'tel' || entry.kind === 'sms') return entry.label || (entry.kind === 'tel' ? 'تماس' : 'پیامک');
            var meta = builtinsFor(surface)[entry.id] || {};
            if (entry.id === 'share_details' && entry.label) return entry.label;
            return meta.label || entry.id;
        }

        function isAvailable(surface, entry, data) {
            if (entry.kind === 'tel' || entry.kind === 'sms') return !!normalizeMobile(entry.mobile);
            if (surface === 'booking_success') {
                if (entry.id === 'open_patient') return !!data.patientUrl;
                if (entry.id === 'open_board') return !!data.boardUrl;
                if (entry.id === 'call_patient') return !!normalizeMobile(data.mobile);
                if (entry.id === 'sms_patient') return !!normalizeMobile(data.mobile);
                if (entry.id === 'share_details') return !!normalizeMobile(entry.mobile || '');
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
            if (entry.id === 'panel_sms') return !!data.smsEnabled && !!data.mobile;
            if (entry.id === 'checklist') return !!data.hasSurgeryChecklist && !!data.surgeryAppointmentId;
            if (entry.id === 'patient_file') return !!data.patientUrl && !alreadyOnFile;
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
                        'open_patient', 'open_board', 'call_patient', 'sms_patient',
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

                var meta = builtinsFor(surface)[entry.id] || {};
                if (meta.full || entry.kind === 'tel' || entry.kind === 'sms' || entry.id === 'share_details' || entry.id === 'patient_file') {
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

            var id = entry.id;
            if (surface === 'toolbox') {
                if (id === 'call') { btn.href = data.telHref || '#'; return; }
                if (id === 'sms') { btn.href = data.smsHref || '#'; return; }
                if (id === 'edit') { btn.href = data.editUrl || '#'; return; }
                if (id === 'print') { btn.href = data.printUrl || '#'; btn.target = '_blank'; return; }
                if (id === 'surgery') { btn.href = data.surgeryUrl || '#'; return; }
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
                                nationalCode: data.nationalCode || '',
                                meta: data.meta || '',
                                date: data.dateLabel || '',
                            }
                        }));
                        closeToolbox();
                    });
                    return;
                }
                if (id === 'panel_sms') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        openSmsConfirm(data, msgEl, btn);
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
                if (id === 'call_patient') { btn.href = telHref(data.mobile); return; }
                if (id === 'sms_patient') { btn.href = smsHref(data.mobile, ''); return; }
                if (id === 'share_details') {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        openSmsConfirm({
                            mobile: entry.mobile,
                            smsBody: patientDetailsMessage(data),
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
                if (present[id] && id !== 'share_details') return;
                if (present[id] && id === 'share_details' && !builtins[id].needs_mobile) return;
                hiddenCount += 1;
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'rt-add-option';
                b.innerHTML = '<span>' + builtins[id].label + '</span><em>نمایش بده</em>';
                b.addEventListener('click', function () {
                    if (builtins[id].needs_mobile || id === 'share_details') {
                        showCustomForm(surface, 'share_details', builtins[id].label);
                        return;
                    }
                    surfaceState(surface).items.push({
                        id: id,
                        kind: 'builtin',
                        style: surfaceState(surface).style || 'soft',
                        accent: surfaceState(surface).accent || '#4f86be',
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
            [['tel', 'تماس با یک نفر'], ['sms', 'پیامک به یک نفر']].forEach(function (pair) {
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
        }

        function showCustomForm(surface, kind, title) {
            addTitle.textContent = title;
            addBody.innerHTML = '';
            var form = document.createElement('form');
            form.className = 'rt-add-form';
            form.innerHTML =
                '<label><span>عنوان دکمه</span><input name="label" class="field-input" required maxlength="60" placeholder="مثلاً تماس با منشی"></label>' +
                '<label><span>شماره موبایل</span><input name="mobile" class="field-input ltr-data" dir="ltr" required inputmode="tel" placeholder="09xxxxxxxxx"></label>' +
                (kind === 'sms' ? '<label><span>متن ثابت (اختیاری)</span><input name="body" class="field-input" maxlength="200" placeholder="پیام پیش‌فرض"></label>' : '') +
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
                if (kind === 'share_details') {
                    surfaceState(surface).items = (surfaceState(surface).items || []).filter(function (it) {
                        return it.id !== 'share_details';
                    });
                    surfaceState(surface).items.push({
                        id: 'share_details',
                        kind: 'builtin',
                        label: label,
                        mobile: mobile,
                        style: surfaceState(surface).style || 'soft',
                        accent: surfaceState(surface).accent || '#4f86be',
                    });
                    selectedActionId = 'share_details';
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

        function closeAddSheet() {
            show(addOverlay, false);
            addOverlay.classList.remove('is-open');
            addTargetSurface = null;
        }

        function openReadyAnswers() {
            var mobile = (item && item.mobile) || '';
            var name = (item && item.name) || '';
            var detail = { mobile: mobile, patientName: name };
            closeToolbox();
            requestAnimationFrame(function () {
                if (window.ReadyAnswers && typeof window.ReadyAnswers.open === 'function') {
                    window.ReadyAnswers.open(detail);
                    return;
                }
                window.dispatchEvent(new CustomEvent('open-ready-answers', { detail: detail }));
            });
        }

        function workspaceTab(raw) {
            var t = raw || 'photos';
            if (t === 'whiteboard') return 'drawings';
            if (t === 'prescription' || t === 'rx') return 'rx';
            if (t === 'exam') return 'exams';
            if (t === 'photos' || t === 'drawings' || t === 'exams' || t === 'rx') return t;
            return 'photos';
        }

        function closeWorkspaceFrame() {
            if (!pwOverlay) return;
            show(pwOverlay, false);
            pwOverlay.setAttribute('aria-hidden', 'true');
            pwOverlay.classList.remove('is-open');
            if (pwFrame) pwFrame.removeAttribute('src');
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
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
        }

        function openClinicalWorkspace(preferredTab) {
            var tab = workspaceTab(preferredTab);
            var snapshot = item;
            closeToolbox();
            requestAnimationFrame(function () {
                if (window.PatientWorkspace && typeof window.PatientWorkspace.openMedia === 'function') {
                    window.PatientWorkspace.openMedia(tab);
                    return;
                }
                var url = (snapshot && snapshot.patientUrl) || '';
                if (!url) return;
                var sep = url.indexOf('?') >= 0 ? '&' : '?';
                openWorkspaceFrame(url + sep + 'embed=1&open=' + encodeURIComponent(tab), (snapshot && snapshot.name) || 'پرونده / گالری');
            });
        }

        async function sendPanelSms(data, msgTarget, btn) {
            if (!data || !data.mobile || sending) return;
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
            } catch (e) {
                setMsg(msgTarget, e.message || 'خطا در ارسال پیامک');
            } finally {
                sending = false;
                if (btn) btn.disabled = false;
            }
        }

        function closeSmsConfirm() {
            pendingSms = null;
            if (!smsConfirmOverlay) return;
            show(smsConfirmOverlay, false);
            smsConfirmOverlay.classList.remove('is-open');
            smsConfirmOverlay.setAttribute('aria-hidden', 'true');
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
        }

        function fillInfo(prefix, data) {
            root.querySelectorAll('[data-' + prefix + '-row]').forEach(function (row) {
                var key = row.getAttribute('data-' + prefix + '-row');
                var value = data[key];
                show(row, !!value);
                var valueEl = row.querySelector('[data-' + prefix + '-text="' + key + '"]');
                if (valueEl) valueEl.textContent = value || '';
            });
        }

        function openToolbox(detail) {
            item = detail || {};
            setEditing('toolbox', false);
            setMsg(msgEl, '');
            root.querySelector('[data-rt-name]').textContent = item.name || 'جعبه ابزار';
            fillInfo('rt', item);
            var smsPreviewBox = root.querySelector('[data-rt-sms-preview]');
            var smsPreviewText = root.querySelector('[data-rt-sms-preview-text]');
            if (smsPreviewBox && smsPreviewText) {
                if (item.smsBody) {
                    smsPreviewText.textContent = item.smsBody;
                    show(smsPreviewBox, true);
                } else {
                    smsPreviewText.textContent = '';
                    show(smsPreviewBox, false);
                }
            }
            panel.classList.toggle('row-toolbox-panel--patient', item.sheetMode === 'patient');

            // Ensure report note action is available on reports page even if prefs omit it.
            if (item.sheetMode === 'report') {
                var st = surfaceState('toolbox');
                var hasNote = (st.items || []).some(function (entry) { return entry.id === 'report_note'; });
                if (!hasNote) {
                    st.items = (st.items || []).concat([{ id: 'report_note', kind: 'builtin' }]);
                }
            }

            // Ensure checklist button exists for surgery appointments (prefs saved before this feature).
            if (item.hasSurgeryChecklist && item.surgeryAppointmentId) {
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
            show(overlay, true);
            overlay.classList.add('is-open');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';

            if (item.openChecklistOnOpen && item.surgeryAppointmentId && window.SurgeryChecklistPanel) {
                window.SurgeryChecklistPanel.open(item);
            }
        }

        function closeToolbox() {
            item = null;
            setEditing('toolbox', false);
            show(overlay, false);
            overlay.classList.remove('is-open');
            if ((!pwOverlay || !pwOverlay.classList.contains('is-open')) && (!bsOverlay || !bsOverlay.classList.contains('is-open'))) {
                document.documentElement.style.overflow = '';
                document.body.style.overflow = '';
            }
        }

        function openBookingSuccess(detail) {
            bookingItem = detail || {};
            setEditing('booking_success', false);
            setMsg(bsMsg, '');
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
        }

        function closeBookingSuccess() {
            bookingItem = null;
            setEditing('booking_success', false);
            show(bsOverlay, false);
            bsOverlay.classList.remove('is-open');
            if ((!pwOverlay || !pwOverlay.classList.contains('is-open')) && (!overlay || !overlay.classList.contains('is-open'))) {
                document.documentElement.style.overflow = '';
                document.body.style.overflow = '';
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
            var clOverlay = document.querySelector('[data-cl-overlay]');
            if (clOverlay && (clOverlay.classList.contains('is-open') || clOverlay.style.display === 'flex')) {
                if (window.SurgeryChecklistPanel) window.SurgeryChecklistPanel.close();
                return;
            }
            if (smsConfirmOverlay && smsConfirmOverlay.classList.contains('is-open')) { closeSmsConfirm(); return; }
            if (addOverlay.classList.contains('is-open')) { closeAddSheet(); return; }
            if (pwOverlay && pwOverlay.classList.contains('is-open')) { closeWorkspaceFrame(); return; }
            if (bsOverlay.classList.contains('is-open')) { closeBookingSuccess(); return; }
            if (overlay.classList.contains('is-open')) closeToolbox();
        });

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-toolbox-trigger]');
            var row = trigger || e.target.closest('[data-toolbox-b64], [data-toolbox]');
            if (!row || root.contains(row)) return;
            var isToolboxTarget = row.hasAttribute('data-toolbox-b64') || row.hasAttribute('data-toolbox');
            if (!trigger && !isToolboxTarget && e.target.closest('a, button, input, label, select, textarea')) return;
            var payload = payloadOf(row);
            if (!payload) return;
            e.preventDefault();
            e.stopPropagation();
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\row-toolbox-modal.blade.php ENDPATH**/ ?>