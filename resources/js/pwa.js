/**
 * Progressive Web App — service worker + install prompt.
 */
(function () {
    if ('serviceWorker' in navigator) {
        const version = document.querySelector('meta[name="app-version"]')?.content || '1';
        const swUrl = '/sw.js?build=' + encodeURIComponent(version);

        window.addEventListener('load', function () {
            navigator.serviceWorker.register(swUrl, { scope: '/' }).catch(function () {
                /* optional */
            });
        });
    }

    window.pwaInstall = function () {
        const dismissedKey = 'pwa-install-dismissed';
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
        const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
        let deferredPrompt = null;

        return {
            visible: false,
            canInstall: false,
            isIos,
            title: 'نصب روی دستگاه',
            hint: 'برای دسترسی سریع‌تر مثل اپ موبایل، روی گوشی یا تبلت نصب کنید.',
            init() {
                if (isStandalone || localStorage.getItem(dismissedKey) === '1') {
                    return;
                }

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    deferredPrompt = e;
                    this.canInstall = true;
                    this.visible = true;
                });

                if (isIos) {
                    this.title = 'افزودن به صفحه اصلی';
                    this.hint = 'در Safari دکمه اشتراک‌گذاری را بزنید و «Add to Home Screen» را انتخاب کنید.';
                    this.visible = true;
                } else {
                    window.setTimeout(() => {
                        if (!this.visible && !isStandalone) {
                            this.hint = 'از منوی مرورگر (⋮) گزینه «Install app» یا «Add to Home screen» را بزنید.';
                            this.visible = true;
                        }
                    }, 4000);
                }
            },
            async install() {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                await deferredPrompt.userChoice;
                deferredPrompt = null;
                this.visible = false;
                localStorage.setItem(dismissedKey, '1');
            },
            showIosHelp() {
                alert('راهنمای iOS:\n1) Safari را باز کنید\n2) دکمه Share (مربع با فلش) را بزنید\n3) Add to Home Screen را انتخاب کنید\n4) Add را بزنید');
            },
            dismiss() {
                this.visible = false;
                localStorage.setItem(dismissedKey, '1');
            },
        };
    };
})();
