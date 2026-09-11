<?php

/**
 * One-time setup: enable HIS patient ingest on this host.
 * Upload to public/, open once in browser, then DELETE this file.
 *
 * URL: https://mramo.ir/enable-his-sync.php
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__);
$envPath = $root . DIRECTORY_SEPARATOR . '.env';
$key = 'd3b152b5d43cfb8435327a1530c32f487d8bad33';
$secret = 'dae5a3def08abd3354d4fa02e321ece3a497ac5a';

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (! is_file($envPath)) {
    http_response_code(500);
    echo '<h1>فایل .env پیدا نشد</h1><p>' . h($envPath) . '</p>';
    exit;
}

$env = file_get_contents($envPath);
if ($env === false) {
    http_response_code(500);
    echo '<h1>خواندن .env ناموفق بود</h1>';
    exit;
}

$lines = [
    'HIS_SYNC_ENABLED=true',
    'HIS_AGENT_KEY=' . $key,
    'HIS_AGENT_SECRET=' . $secret,
];

foreach ($lines as $line) {
    [$name] = explode('=', $line, 2);
    if (preg_match('/^' . preg_quote($name, '/') . '=.*$/m', $env)) {
        $env = preg_replace('/^' . preg_quote($name, '/') . '=.*$/m', $line, $env, 1);
    } else {
        $env = rtrim($env) . "\n" . $line . "\n";
    }
}

if (file_put_contents($envPath, $env) === false) {
    http_response_code(500);
    echo '<h1>نوشتن .env ناموفق بود</h1><p>مجوز نوشتن فایل را در cPanel بررسی کنید.</p>';
    exit;
}

$cacheFiles = [
    $root . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'config.php',
    $root . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'routes-v7.php',
    $root . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'services.php',
];
foreach ($cacheFiles as $cacheFile) {
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
}

echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>HIS Sync</title></head><body style="font-family:Tahoma,sans-serif;padding:2rem;line-height:1.8">';
echo '<h1>همگام‌سازی HIS روشن شد</h1>';
echo '<p><code>HIS_SYNC_ENABLED</code> و کلید عامل در <code>.env</code> ذخیره شد.</p>';
echo '<p><strong>همین الان این فایل را از هاست حذف کنید:</strong><br><code>public/enable-his-sync.php</code></p>';
echo '<p>بعد روی سیستم کلینیک دوباره <code>send.bat</code> را اجرا کنید. مرحله ۸ باید Clinic ping OK بدهد.</p>';
echo '</body></html>';
