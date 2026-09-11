<?php
/**
 * یک‌بار بعد از نصب باز کنید، بعد حذف کنید.
 * https://دامنه/public/fix-404.php
 */
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__);
$messages = [];

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    @unlink($root.'/bootstrap/cache/config.php');
    @unlink($root.'/bootstrap/cache/routes-v7.php');
    foreach (glob($root.'/bootstrap/cache/*.php') ?: [] as $f) {
        @unlink($f);
    }
    $messages[] = 'کش bootstrap پاک شد.';

    $envUrl = env('APP_URL', '');
    $messages[] = 'APP_URL فعلی: '.($envUrl !== '' ? $envUrl : '(خالی)');

    $host = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
        .'://'.($_SERVER['HTTP_HOST'] ?? 'localhost');
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if (str_ends_with($base, '/public')) {
        $suggested = $host.$base;
    } elseif (str_contains($script, '/public/')) {
        $suggested = $host.'/public';
    } else {
        $suggested = $host;
    }
    $messages[] = 'پیشنهاد APP_URL: '.$suggested;

    $envPath = $root.'/.env';
    if (is_file($envPath) && is_writable($envPath)) {
        $env = file_get_contents($envPath);
        if (preg_match('/^APP_URL=.*$/m', $env)) {
            $env = preg_replace('/^APP_URL=.*$/m', 'APP_URL="'.$suggested.'"', $env, 1);
        } else {
            $env .= "\nAPP_URL=\"{$suggested}\"\n";
        }
        file_put_contents($envPath, $env);
        $messages[] = '.env به‌روز شد (APP_URL).';
    }

    $kernel->call('config:clear');
    $kernel->call('route:clear');
    $kernel->call('view:clear');
    $messages[] = 'config/route/view clear انجام شد.';

    $login = $suggested.'/login';
    $messages[] = 'الان این را باز کنید: '.$login;
} catch (Throwable $e) {
    $messages[] = 'خطا: '.$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><title>رفع 404</title>
<style>body{font-family:Tahoma,sans-serif;background:#0f172a;color:#e2e8f0;padding:2rem;line-height:1.8}
.card{max-width:720px;margin:auto;background:#1e293b;padding:1.25rem;border-radius:12px}
code{background:#0f172a;padding:.1rem .35rem;border-radius:4px;direction:ltr;display:inline-block}
a{color:#38bdf8}</style></head>
<body><div class="card">
<h1>رفع 404</h1>
<?php foreach ($messages as $m): ?>
<p><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>
<p style="color:#fbbf24">بعد از درست شدن سایت، این فایل را حذف کنید.</p>
</div></body></html>
