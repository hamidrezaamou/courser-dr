<?php

/**
 * Renders the ready-answers panel into a standalone HTML file with stubbed
 * network calls, so the visual design can be reviewed without a running app.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$panel = view('components.answer-panel', [
    'mobile' => '09153630780',
    'patientName' => 'زهرا محمدی',
])->render();

$manifest = json_decode(file_get_contents(__DIR__.'/../public/build/manifest.json'), true);
$cssFile = $manifest['resources/css/app.css']['file'] ?? null;
$css = file_get_contents(__DIR__.'/../public/build/'.$cssFile);
$alpine = file_get_contents(__DIR__.'/../node_modules/alpinejs/dist/cdn.min.js');

$sample = [
    [
        'id' => 1,
        'title' => 'ارسال پرونده در ایتا',
        'category' => 'راهنمای بیمار',
        'body' => "نسخه، عکس، دارو یا خلاصه پرونده رو توی ایتا همراه با سوالتون ارسال کنید.\nhttps://eitaa.com/Dr_Fotouhi",
        'is_pinned' => true,
        'usage_count' => 42,
    ],
    [
        'id' => 2,
        'title' => 'هماهنگی نوبت قائن',
        'category' => 'نوبت‌دهی',
        'body' => "سلام {نام}\nبا شماره 09153630780 آقای زرگر جهت نوبت‌های قائن تماس بگیرید.",
        'is_pinned' => false,
        'usage_count' => 17,
    ],
    [
        'id' => 3,
        'title' => 'آماده‌سازی قبل از عمل',
        'category' => 'قبل از عمل',
        'body' => "{نام} عزیز، تاریخ عمل شما به شنبه 13 مرداد ماه تغییر کرد.\nلطفا از الان برای آزمایش، تعیین لنز و دکتر بیهوشی خود اقدام فرمایید و توجه داشته باشید عواقب ناشی از عدم انجام اقدامات قبل از عمل به عهده شما میباشد.\nبا تشکر\nدر صورت هر گونه سوال با شماره های 09156158413 و 09006158413 میتوانید تماس بگیرید.",
        'is_pinned' => false,
        'usage_count' => 8,
    ],
    [
        'id' => 4,
        'title' => null,
        'category' => null,
        'body' => "مدیریت درمان تامین اجتماعی\nکوهسنگی 14\nآدرس نشان:\nhttps://nshn.ir/90_b1GNtIJG-Zy",
        'is_pinned' => false,
        'usage_count' => 0,
    ],
];

$sampleJson = json_encode([
    'data' => $sample,
    'count' => count($sample),
    'total' => 14,
    'categories' => ['راهنمای بیمار', 'نوبت‌دهی', 'قبل از عمل'],
], JSON_UNESCAPED_UNICODE);

$html = <<<HTML
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="preview">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>{$css}</style>
<script>
// Stub the network so the preview renders the sample library offline.
window.fetch = function (url) {
    return Promise.resolve({
        ok: true,
        json: () => Promise.resolve({$sampleJson}),
    });
};
</script>
</head>
<body class="font-sans antialiased" style="color: var(--ink);">
<div class="app-shell" style="min-height:100vh">
    <div style="padding:2rem; display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center">
        <button class="ans-launch" data-answer-launch data-answer-mobile="09153630780" data-answer-name="زهرا محمدی">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
            <span>پاسخ‌های آماده</span>
        </button>
        <button class="ans-launch ans-launch--soft" data-answer-launch>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
            <span>ارسال پیام</span>
        </button>
    </div>
    {$panel}
</div>
<script>{$alpine}</script>
</body>
</html>
HTML;

$out = __DIR__.'/../storage/app/answer-panel-preview.html';
file_put_contents($out, $html);

echo realpath($out)."\n";
