{{-- نصب وب‌اپ — مستقل از Alpine/Vite تا روی سرور بدون rebuild هم دیده شود --}}
<div id="pwa-install-banner" class="pwa-install" hidden>
    <div class="pwa-install__inner">
        <div class="pwa-install__text">
            <strong id="pwa-install-title">نصب روی دستگاه</strong>
            <span id="pwa-install-hint">برای دسترسی سریع‌تر مثل اپ موبایل، روی گوشی یا تبلت نصب کنید.</span>
        </div>
        <div class="pwa-install__actions">
            <button type="button" id="pwa-install-btn" class="pwa-install__btn" hidden>نصب اپ</button>
            <button type="button" id="pwa-ios-btn" class="pwa-install__btn pwa-install__btn--ghost" hidden>راهنمای iOS</button>
            <button type="button" id="pwa-dismiss-btn" class="pwa-install__close" aria-label="بستن">×</button>
        </div>
    </div>
</div>

<style>
    .pwa-install {
        position: fixed;
        inset-inline: 0;
        bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));
        z-index: 70;
        display: flex;
        justify-content: center;
        padding: 0 0.75rem;
        pointer-events: none;
    }
    .pwa-install[hidden] { display: none !important; }
    .pwa-install__inner {
        pointer-events: auto;
        width: min(100%, 36rem);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.75rem 0.85rem;
        border-radius: 1rem;
        border: 1px solid color-mix(in srgb, var(--brand, #2f5f8c) 35%, var(--line, #d7e2ee));
        background: color-mix(in srgb, var(--panel, #fff) 92%, #fff);
        box-shadow: 0 16px 40px -20px rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(10px);
    }
    .pwa-install__text {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        font-size: 0.75rem;
        line-height: 1.5;
        color: var(--muted, #64748b);
    }
    .pwa-install__text strong {
        color: var(--ink, #0f172a);
        font-size: 0.8125rem;
    }
    .pwa-install__actions {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex-shrink: 0;
    }
    .pwa-install__btn {
        border: 0;
        border-radius: 0.75rem;
        padding: 0.45rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 800;
        cursor: pointer;
        background: linear-gradient(145deg, var(--brand, #2f5f8c), var(--brand-dark, #244a6e));
        color: #fff;
        white-space: nowrap;
    }
    .pwa-install__btn[hidden] { display: none !important; }
    .pwa-install__btn--ghost {
        background: var(--panel-soft, #f1f5f9);
        color: var(--brand-dark, #244a6e);
        box-shadow: inset 0 0 0 1px var(--line, #d7e2ee);
    }
    .pwa-install__close {
        width: 1.75rem;
        height: 1.75rem;
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: var(--muted, #64748b);
        font-size: 1.1rem;
        line-height: 1;
        cursor: pointer;
    }
    @media (display-mode: standalone) {
        .pwa-install { display: none !important; }
    }
</style>

<script>
(function () {
    var dismissedKey = 'pwa-install-dismissed-v2';
    var root = document.getElementById('pwa-install-banner');
    if (!root) return;

    var isStandalone = false;
    try {
        isStandalone = window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
    } catch (e) {}

    if (isStandalone) return;

    // قبلاً بسته شده بود؟ بعد از ۷ روز دوباره نشان بده
    try {
        var raw = localStorage.getItem(dismissedKey);
        if (raw) {
            var until = Number(raw);
            if (until && Date.now() < until) return;
            localStorage.removeItem(dismissedKey);
        }
    } catch (e) {}

    var titleEl = document.getElementById('pwa-install-title');
    var hintEl = document.getElementById('pwa-install-hint');
    var installBtn = document.getElementById('pwa-install-btn');
    var iosBtn = document.getElementById('pwa-ios-btn');
    var dismissBtn = document.getElementById('pwa-dismiss-btn');
    var deferredPrompt = null;
    var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent || '');

    function show() {
        root.hidden = false;
    }

    function hide() {
        root.hidden = true;
    }

    function dismiss() {
        hide();
        try {
            localStorage.setItem(dismissedKey, String(Date.now() + 7 * 24 * 60 * 60 * 1000));
        } catch (e) {}
    }

    if (dismissBtn) dismissBtn.addEventListener('click', dismiss);

    if (installBtn) {
        installBtn.addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.finally(function () {
                deferredPrompt = null;
                dismiss();
            });
        });
    }

    if (iosBtn) {
        iosBtn.addEventListener('click', function () {
            alert('راهنمای iOS:\n1) Safari را باز کنید\n2) دکمه Share (مربع با فلش) را بزنید\n3) Add to Home Screen را انتخاب کنید\n4) Add را بزنید');
        });
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        if (installBtn) installBtn.hidden = false;
        if (titleEl) titleEl.textContent = 'نصب روی دستگاه';
        if (hintEl) hintEl.textContent = 'برای دسترسی سریع‌تر مثل اپ موبایل، روی گوشی یا تبلت نصب کنید.';
        show();
    });

    if (isIos) {
        if (titleEl) titleEl.textContent = 'افزودن به صفحه اصلی';
        if (hintEl) hintEl.textContent = 'در Safari دکمه اشتراک‌گذاری را بزنید و «Add to Home Screen» را انتخاب کنید.';
        if (iosBtn) iosBtn.hidden = false;
        show();
    } else {
        // حتی اگر beforeinstallprompt نیاید، راهنما را نشان بده
        window.setTimeout(function () {
            if (!root.hidden) return;
            if (titleEl) titleEl.textContent = 'نصب وب‌اپلیکیشن';
            if (hintEl) hintEl.textContent = 'از منوی مرورگر (⋮) گزینه Install app یا Add to Home screen را بزنید.';
            show();
        }, 1500);
    }

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            var version = document.querySelector('meta[name="app-version"]');
            var swUrl = '/sw.js?build=' + encodeURIComponent(version ? version.content : '1');
            navigator.serviceWorker.register(swUrl, { scope: '/' }).catch(function () {});
        });
    }
})();
</script>
