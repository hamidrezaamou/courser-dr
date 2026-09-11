<?php

/**
 * One-time setup: set WEBSITE_API_KEY on the host.
 * Upload to public/, open once in browser, then DELETE this file.
 *
 * URL: https://mramo.ir/set-website-api-key.php
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__);
$envPath = $root . DIRECTORY_SEPARATOR . '.env';
$key = 'dr-website-mramo-2026';
$label = 'مطب شخصی (ساختمان سان)';

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
    'WEBSITE_API_KEY=' . $key,
    'WEBSITE_CLINIC_LABEL="' . $label . '"',
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

// Clear Laravel caches without shell exec (often disabled on shared hosting)
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

echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>API Key</title></head><body style="font-family:Tahoma,sans-serif;padding:2rem;line-height:1.8">';
echo '<h1>کلید API تنظیم شد</h1>';
echo '<p><code>WEBSITE_API_KEY</code> و برچسب مطب در <code>.env</code> ذخیره شد.</p>';
echo '<p><strong>همین الان این فایل را از هاست حذف کنید:</strong><br><code>public/set-website-api-key.php</code></p>';
echo '<p>بعد سایت لوکال را رفرش کنید و رزرو نوبت را دوباره باز کنید.</p>';
echo '</body></html>';
