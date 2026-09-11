<?php

/**
 * به‌روزرسانی درجا روی cPanel — بدون نصب مجدد و بدون پاک کردن دیتابیس.
 * آدرس: https://your-domain.com/cpanel-update.php
 * پس از استفاده می‌توانید این فایل را روی سرور نگه دارید (با رمز) یا حذف کنید.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$lockFile = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . '.cpanel-installed';
$versionFile = $root . DIRECTORY_SEPARATOR . 'VERSION';
$lastUpdateFile = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . '.last-update';

header('Content-Type: text/html; charset=utf-8');

function cpanel_update_version(string $path): string
{
    if (! is_file($path)) {
        return '0.0.0';
    }

    $v = trim((string) file_get_contents($path));

    return $v !== '' ? $v : '0.0.0';
}

function cpanel_update_token_valid(string $root): bool
{
    $posted = trim((string) ($_POST['update_token'] ?? $_GET['token'] ?? ''));
    if ($posted === '') {
        return false;
    }

    $envPath = $root . DIRECTORY_SEPARATOR . '.env';
    if (is_file($envPath)) {
        $env = file_get_contents($envPath) ?: '';
        if (preg_match('/^UPDATE_TOKEN=(.*)$/m', $env, $m)) {
            $expected = trim($m[1], " \t\n\r\0\x0B\"'");
            if ($expected !== '' && hash_equals($expected, $posted)) {
                return true;
            }
        }
    }

    $tokenFile = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . '.update-token';
    if (is_file($tokenFile)) {
        $expected = trim((string) file_get_contents($tokenFile));

        return $expected !== '' && hash_equals($expected, $posted);
    }

    return false;
}

if (! is_file($lockFile)) {
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>نصب نشده</title></head>';
    echo '<body style="font-family:Tahoma,sans-serif;padding:2rem;line-height:1.8">';
    echo '<h1>ابتدا نصب اولیه</h1><p>قبل از به‌روزرسانی، سایت باید با <code>cpanel-install.php</code> نصب شده باشد.</p>';
    echo '</body></html>';
    exit;
}

$errors = [];
$ok = [];
$done = false;
$currentVersion = cpanel_update_version($versionFile);
$needsToken = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (! cpanel_update_token_valid($root)) {
        $errors[] = 'رمز به‌روزرسانی نادرست است. در .env مقدار UPDATE_TOKEN را تنظیم کنید یا فایل storage/app/.update-token بسازید.';
    }

    if ($errors === []) {
        try {
            if (version_compare(PHP_VERSION, '8.2.0', '<')) {
                throw new RuntimeException('PHP 8.2+ لازم است (فعلی: '.PHP_VERSION.')');
            }
            if (! is_file($root . '/vendor/autoload.php')) {
                throw new RuntimeException('پوشه vendor یافت نشد. ابتدا composer install را روی سرور اجرا کنید.');
            }

            require $root . '/vendor/autoload.php';
            /** @var \Illuminate\Foundation\Application $app */
            $app = require $root . '/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            foreach (glob($root . '/bootstrap/cache/*.php') ?: [] as $cacheFile) {
                @unlink($cacheFile);
            }
            $ok[] = 'کش bootstrap پاک شد.';

            $exit = $kernel->call('migrate', ['--force' => true]);
            if ($exit !== 0) {
                throw new RuntimeException('migrate ناموفق: '.$kernel->output());
            }
            $ok[] = 'Migrationها اجرا شد (داده‌ها حفظ شد).';

            $kernel->call('optimize:clear');
            $ok[] = 'optimize:clear انجام شد.';

            $kernel->call('config:cache');
            $kernel->call('route:cache');
            $kernel->call('view:cache');
            $ok[] = 'کش production ساخته شد.';

            if (! is_dir(dirname($lastUpdateFile))) {
                mkdir(dirname($lastUpdateFile), 0775, true);
            }
            file_put_contents($lastUpdateFile, date('c')."\n");

            $done = true;
            $ok[] = 'به‌روزرسانی کامل شد — نسخه '.$currentVersion;
            $ok[] = 'اگر ظاهر سایت عوض نشد: فایل‌های public/build را آپلود کنید یا npm run build بزنید.';
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$lastUpdate = is_file($lastUpdateFile) ? trim((string) file_get_contents($lastUpdateFile)) : '—';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>به‌روزرسانی cPanel</title>
    <style>
        :root { --bg:#0f172a; --card:#1e293b; --text:#f1f5f9; --muted:#94a3b8; --accent:#38bdf8; --ok:#34d399; --err:#f87171; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:Tahoma,Vazirmatn,sans-serif; background:linear-gradient(160deg,#0f172a,#1e3a5f); color:var(--text); min-height:100vh; padding:1.5rem; }
        .wrap { max-width:640px; margin:0 auto; }
        .card { background:var(--card); border-radius:16px; padding:1.5rem; box-shadow:0 20px 50px rgba(0,0,0,.35); }
        h1 { margin:0 0 .5rem; font-size:1.35rem; }
        p, li { line-height:1.85; color:var(--muted); }
        .meta { display:flex; gap:1rem; flex-wrap:wrap; margin:1rem 0; font-size:.9rem; }
        .meta span { background:#0f172a; padding:.45rem .75rem; border-radius:8px; }
        label { display:block; margin:.9rem 0 .35rem; font-size:.85rem; font-weight:700; }
        input[type=password], input[type=text] { width:100%; padding:.7rem .85rem; border:1px solid #334155; border-radius:10px; background:#0f172a; color:var(--text); }
        button { margin-top:1rem; width:100%; padding:.85rem; border:0; border-radius:10px; background:var(--accent); color:#0f172a; font-weight:800; cursor:pointer; font-size:1rem; }
        .msg { margin-top:1rem; padding:.75rem 1rem; border-radius:10px; font-size:.9rem; }
        .msg.ok { background:rgba(52,211,153,.15); color:var(--ok); }
        .msg.err { background:rgba(248,113,113,.15); color:var(--err); }
        code { background:#0f172a; padding:.1rem .35rem; border-radius:4px; }
        ol { padding-right:1.2rem; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>به‌روزرسانی بدون نصب مجدد</h1>
        <p>فایل‌های جدید را آپلود کرده‌اید؟ این صفحه فقط migration و cache را به‌روز می‌کند — <strong>دیتابیس پاک نمی‌شود</strong>.</p>
        <div class="meta">
            <span>نسخه: <strong><?= htmlspecialchars($currentVersion, ENT_QUOTES, 'UTF-8') ?></strong></span>
            <span>آخرین به‌روزرسانی: <strong><?= htmlspecialchars($lastUpdate, ENT_QUOTES, 'UTF-8') ?></strong></span>
        </div>

        <?php foreach ($ok as $line): ?>
            <div class="msg ok"><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
        <?php foreach ($errors as $line): ?>
            <div class="msg err"><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>

        <?php if (! $done): ?>
            <ol>
                <li>فایل‌های پروژه را روی هاست جایگزین کنید (به‌جز <code>.env</code> و <code>storage/</code>).</li>
                <li>در <code>.env</code> خط <code>UPDATE_TOKEN=یک_رمز_قوی</code> بگذارید.</li>
                <li>دکمه زیر را بزنید.</li>
            </ol>
            <form method="POST">
                <label for="update_token">رمز به‌روزرسانی (UPDATE_TOKEN)</label>
                <input type="password" id="update_token" name="update_token" required autocomplete="off">
                <button type="submit">اجرای به‌روزرسانی</button>
            </form>
        <?php else: ?>
            <p style="color:var(--ok);font-weight:700">✓ سایت آماده است. یک بار Ctrl+F5 بزنید.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
