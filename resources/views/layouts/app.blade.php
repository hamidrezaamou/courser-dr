<!DOCTYPE html>
<html lang="fa" dir="rtl" @class(['clinic-app' => $isClinicApp ?? false])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <x-pwa-head />

        <title>آرشیو بیمار | {{ config('app.name', 'Patient Archive') }}</title>

        <script>
            (function () {
                try {
                    var theme = localStorage.getItem('theme');
                    if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <style>
            .msg-live {
                position: fixed;
                top: calc(var(--nav-h, 3.25rem) + 0.55rem);
                left: 50%;
                transform: translateX(-50%);
                z-index: 90;
                max-width: min(28rem, calc(100vw - 1.5rem));
                padding: 0.7rem 1rem;
                border-radius: 0.9rem;
                background: var(--ink);
                color: #fff;
                font-size: 0.82rem;
                font-weight: 700;
                text-align: center;
                box-shadow: 0 10px 30px rgba(15, 23, 42, .22);
                text-decoration: none;
            }
            .msg-row {
                display: block;
                padding: 0.85rem 1rem;
                border: 1px solid var(--line);
                border-radius: 1rem;
                background: var(--panel);
                color: inherit;
                text-decoration: none;
                box-shadow: var(--shadow);
            }
            .msg-row:hover { border-color: var(--brand); }
            .msg-row.is-unread {
                background: color-mix(in srgb, var(--brand-soft) 55%, var(--panel));
                border-color: color-mix(in srgb, var(--brand) 28%, var(--line));
            }
            .msg-row__top {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex-wrap: wrap;
            }
            .msg-row__top strong { font-size: 0.92rem; }
            .msg-row__dot {
                font-size: 10px;
                font-weight: 800;
                color: #fff;
                background: #dc2626;
                border-radius: 999px;
                padding: 0.05rem 0.4rem;
            }
            .msg-row__time { margin-inline-start: auto; font-size: 11px; color: var(--muted); }
            .msg-row__meta { margin-top: 0.2rem; font-size: 12px; color: var(--muted); }
            .msg-row__preview { margin: 0.35rem 0 0; font-size: 0.84rem; line-height: 1.7; color: var(--ink); }
            .tg-bubble.is-note-focus {
                outline: 2px solid var(--brand);
                box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand) 22%, transparent);
            }
        </style>

        @if($isClinicApp ?? false)
        <style>
            html.clinic-app .pwa-install { display: none !important; }
        </style>
        @endif

        @stack('head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body @class(['font-sans antialiased', $bodyClass ?? '']) style="color: var(--ink);">
        <div class="app-shell relative">
            <div class="relative z-10">
                @include('layouts.navigation')

                @isset($header)
                    <header class="page-topbar border-b backdrop-blur-sm">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 fade-up">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="relative">
                    {{ $slot }}
                </main>
            </div>
        </div>
        @unless($isClinicApp ?? false)
        <x-pwa-install-banner />
        @endunless
        <script>
            (function () {
                var flashes = document.querySelectorAll('[data-auto-dismiss]');
                flashes.forEach(function (el) {
                    var ms = Number(el.getAttribute('data-auto-dismiss')) || 4000;
                    window.setTimeout(function () {
                        el.style.transition = 'opacity .28s ease, transform .28s ease, max-height .28s ease, margin .28s ease, padding .28s ease';
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-4px)';
                        el.style.maxHeight = '0';
                        el.style.marginTop = '0';
                        el.style.marginBottom = '0';
                        el.style.paddingTop = '0';
                        el.style.paddingBottom = '0';
                        window.setTimeout(function () { el.remove(); }, 300);
                    }, ms);
                });
            })();
        </script>
        @stack('scripts')
    </body>
</html>
