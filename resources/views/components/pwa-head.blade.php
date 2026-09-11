{{-- PWA meta tags + manifest --}}
@php
    $appVersion = \App\Support\AppVersion::current();
@endphp
<meta name="application-name" content="{{ \App\Support\Pwa::shortName() }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ \App\Support\Pwa::shortName() }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="{{ \App\Support\Pwa::themeColor() }}">
<meta name="app-version" content="{{ $appVersion }}">
<meta name="format-detection" content="telephone=no">

<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="192x192" href="/pwa/icon-192.png">
<link rel="icon" type="image/png" sizes="512x512" href="/pwa/icon-512.png">
<link rel="apple-touch-icon" sizes="180x180" href="/pwa/icon-180.png">
<link rel="apple-touch-icon" href="/pwa/icon-180.png">
