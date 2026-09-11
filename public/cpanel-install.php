<?php

/**
 * نصب‌کننده وب برای cPanel — پس از نصب موفق این فایل را حذف کنید.
 * آدرس: https://your-domain.com/cpanel-install.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$lockFile = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . '.cpanel-installed';
$envPath = $root . DIRECTORY_SEPARATOR . '.env';

header('Content-Type: text/html; charset=utf-8');

if (is_file($lockFile) && ! isset($_GET['reinstall'])) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>نصب شده</title></head>';
    echo '<body style="font-family:Tahoma,sans-serif;padding:2rem;background:#f8fafc;color:#0f172a;line-height:1.8">';
    echo '<h1 style="margin-top:0">نصب قبلاً انجام شده</h1>';
    echo '<p>روی این سرور فایل قفل <code>storage/app/.cpanel-installed</code> وجود دارد، برای همین نصب‌کننده عمداً خطای ۴۰۳ می‌دهد تا دوباره روی دیتابیس زنده اجرا نشود.</p>';
    echo '<p><strong>اگر می‌خواهید از نو نصب کنید</strong> (جداول پاک می‌شوند):</p>';
    echo '<p><a href="?reinstall=1" style="display:inline-block;padding:.7rem 1.2rem;background:#0ea5e9;color:#fff;text-decoration:none;border-radius:8px;font-weight:700">باز کردن فرم نصب مجدد</a></p>';
    echo '<p style="color:#64748b;font-size:.9rem">یا در File Manager همان فایل <code>.cpanel-installed</code> را حذف کنید و صفحه را رفرش کنید.</p>';
    echo '<p style="color:#64748b;font-size:.9rem">اگر نصب تمام شده و سایت کار می‌کند، همین فایل <code>public/cpanel-install.php</code> را حذف کنید.</p>';
    echo '</body></html>';
    exit;
}

if (isset($_GET['reinstall']) && is_file($lockFile)) {
    @unlink($lockFile);
}

$errors = [];
$ok = [];
$done = false;

/** پیش‌فرض‌های نصب آسان — در فرم قابل ویرایش هستند */
$cpanelDefaults = [
    'db_database' => 'dqdcizks_hamid_amou',
    'db_username' => 'dqdcizks_hamid',
    'db_password' => '01250125hH',
    'admin_national_code' => '01250125',
    'admin_password' => '01250125',
];

function cpanel_req(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function cpanel_random_key(): string
{
    return 'base64:' . base64_encode(random_bytes(32));
}

function cpanel_write_env(string $path, array $vars): void
{
    $lines = [];
    foreach ($vars as $k => $v) {
        if ($v === null) {
            continue;
        }
        $v = (string) $v;
        if ($v === '' || preg_match('/[\s#"\\\\]/', $v) || str_contains($v, '$')) {
            $v = '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $v) . '"';
        }
        $lines[] = $k . '=' . $v;
    }
    if (file_put_contents($path, implode("\n", $lines) . "\n") === false) {
        throw new RuntimeException('نوشتن فایل .env ناموفق بود. مجوز نوشتن روی ریشه پروژه را بررسی کنید.');
    }
}

function cpanel_test_db(string $host, string $port, string $database, string $user, string $pass): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

/** حذف همه جداول دیتابیس (برای نصب مجدد تمیز روی cPanel) */
function cpanel_wipe_database(PDO $pdo): int
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_NUM);
    $count = 0;
    foreach ($tables as $row) {
        $name = $row[0];
        $pdo->exec('DROP TABLE IF EXISTS `'.str_replace('`', '``', $name).'`');
        $count++;
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    return $count;
}

function cpanel_ensure_dirs(string $root): void
{
    $dirs = [
        'storage/app/public',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache',
    ];
    foreach ($dirs as $dir) {
        $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
        if (! is_dir($full) && ! mkdir($full, 0775, true) && ! is_dir($full)) {
            throw new RuntimeException('ساختن پوشه ناموفق: ' . $dir);
        }
        @chmod($full, 0775);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appName = cpanel_req('app_name', 'بایگانی بیماران');
    $appUrl = rtrim(cpanel_req('app_url'), '/');
    $dbHost = cpanel_req('db_host', 'localhost');
    $dbPort = cpanel_req('db_port', '3306');
    $dbName = cpanel_req('db_database', $cpanelDefaults['db_database']);
    $dbUser = cpanel_req('db_username', $cpanelDefaults['db_username']);
    $dbPass = cpanel_req('db_password', $cpanelDefaults['db_password']);
    $doctorName = cpanel_req('clinic_doctor_name', 'دکتر مرضیه فتوحی');
    $adminName = cpanel_req('admin_name', 'دکتر فتوحی');
    $adminNational = cpanel_req('admin_national_code', $cpanelDefaults['admin_national_code']);
    $adminMobile = cpanel_req('admin_mobile', '09123456789');
    $adminPassword = cpanel_req('admin_password', $cpanelDefaults['admin_password']);
    $smsReminders = isset($_POST['sms_reminders']) ? 'true' : 'false';
    $smsDriver = cpanel_req('sms_driver', 'log');
    $smsIrKey = cpanel_req('smsir_api_key');
    $smsIrLine = cpanel_req('smsir_line_number');
    $runSeed = isset($_POST['run_seed']);
    $freshDb = isset($_POST['fresh_db']);

    if ($appUrl === '') {
        $errors[] = 'آدرس سایت (APP_URL) الزامی است.';
    }
    if ($dbName === '' || $dbUser === '') {
        $errors[] = 'نام دیتابیس و نام کاربری دیتابیس الزامی است.';
    }
    if (strlen($adminPassword) < 8) {
        $errors[] = 'رمز ادمین حداقل ۸ کاراکتر باشد.';
    }
    if (! extension_loaded('pdo_mysql')) {
        $errors[] = 'افزونه PHP pdo_mysql فعال نیست.';
    }
    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        $errors[] = 'نسخه PHP باید ۸.۲ یا بالاتر باشد (فعلی: ' . PHP_VERSION . ').';
    }

    if ($errors === []) {
        try {
            cpanel_ensure_dirs($root);
            $pdo = cpanel_test_db($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
            $ok[] = 'اتصال به دیتابیس برقرار شد.';

            $appKey = cpanel_random_key();
            cpanel_write_env($envPath, [
                'APP_NAME' => $appName,
                'APP_ENV' => 'production',
                'APP_KEY' => $appKey,
                'APP_DEBUG' => 'false',
                'APP_URL' => $appUrl,
                'APP_LOCALE' => 'fa',
                'APP_FALLBACK_LOCALE' => 'en',
                'APP_FAKER_LOCALE' => 'fa_IR',
                'APP_MAINTENANCE_DRIVER' => 'file',
                'BCRYPT_ROUNDS' => '12',
                'LOG_CHANNEL' => 'stack',
                'LOG_STACK' => 'single',
                'LOG_DEPRECATIONS_CHANNEL' => 'null',
                'LOG_LEVEL' => 'error',
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $dbHost,
                'DB_PORT' => $dbPort,
                'DB_DATABASE' => $dbName,
                'DB_USERNAME' => $dbUser,
                'DB_PASSWORD' => $dbPass,
                'SESSION_DRIVER' => 'database',
                'SESSION_LIFETIME' => '120',
                'SESSION_ENCRYPT' => 'false',
                'SESSION_PATH' => '/',
                'SESSION_DOMAIN' => 'null',
                'BROADCAST_CONNECTION' => 'log',
                'FILESYSTEM_DISK' => 'local',
                'QUEUE_CONNECTION' => 'database',
                'CACHE_STORE' => 'database',
                'MAIL_MAILER' => 'log',
                'MAIL_FROM_ADDRESS' => 'noreply@' . (parse_url($appUrl, PHP_URL_HOST) ?: 'localhost'),
                'MAIL_FROM_NAME' => '${APP_NAME}',
                'VITE_APP_NAME' => '${APP_NAME}',
                'CLINIC_DOCTOR_NAME' => $doctorName,
                'CLINIC_PRINT_HEADER_SPACER' => '120',
                'REMINDERS_ENABLED' => 'true',
                'REMINDERS_DAYS_AHEAD' => '1',
                'TELEGRAM_REMINDERS' => 'false',
                'TELEGRAM_BOT_TOKEN' => '',
                'TELEGRAM_CHAT_ID' => '',
                'SMS_REMINDERS' => $smsReminders,
                'SMS_DRIVER' => $smsDriver !== '' ? $smsDriver : 'log',
                'SMSIR_API_KEY' => $smsIrKey,
                'SMSIR_LINE_NUMBER' => $smsIrLine,
                'SMS_HTTP_ENDPOINT' => '',
                'SMS_API_KEY' => '',
                'SMS_SENDER' => '',
            ]);
            $ok[] = 'فایل .env ساخته شد.';

            // آپلودها مستقیم در public/storage ذخیره می‌شوند (بدون نیاز به symlink)
            $publicStorage = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'storage';
            foreach (['', 'drawings', 'voices', 'medical_documents'] as $sub) {
                $dir = $sub === '' ? $publicStorage : $publicStorage . DIRECTORY_SEPARATOR . $sub;
                if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                    throw new RuntimeException('ساختن پوشه آپلود ناموفق: public/storage/' . $sub);
                }
                @chmod($dir, 0775);
            }
            $ok[] = 'پوشه آپلود public/storage آماده شد.';

            require $root . '/vendor/autoload.php';
            /** @var \Illuminate\Foundation\Application $app */
            $app = require $root . '/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            // پاک کردن کش قبلی تا Schema::defaultStringLength اعمال شود
            @unlink($root . '/bootstrap/cache/config.php');
            @unlink($root . '/bootstrap/cache/packages.php');
            @unlink($root . '/bootstrap/cache/services.php');
            @unlink($root . '/bootstrap/cache/routes-v7.php');
            foreach (glob($root . '/bootstrap/cache/*.php') ?: [] as $cacheFile) {
                @unlink($cacheFile);
            }

            // روی نصب cPanel همیشه قبل از migrate جداول را با PDO پاک می‌کنیم
            // (migrate:fresh گاهی روی هاست مشترک گیر می‌کند / نیمه‌کاره می‌ماند)
            if ($freshDb) {
                $dropped = cpanel_wipe_database($pdo);
                $ok[] = "جداول قبلی پاک شد ({$dropped} جدول).";
            }

            $exit = $kernel->call('migrate', ['--force' => true]);
            if ($exit !== 0) {
                throw new RuntimeException('migrate ناموفق بود. خروجی: ' . $kernel->output());
            }
            $ok[] = 'جداول دیتابیس ساخته شد (migrate).';

            if ($runSeed) {
                // جلوگیری از دوباره‌کاری اگر کاربر از قبل وجود داشته باشد
                $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE national_code = ?');
                $exists->execute([$adminNational]);
                if ((int) $exists->fetchColumn() === 0) {
                    $hash = password_hash($adminPassword, PASSWORD_BCRYPT);
                    $ins = $pdo->prepare(
                        'INSERT INTO users (name, national_code, mobile, role, password, created_at, updated_at) VALUES (?,?,?,?,?,?,?)'
                    );
                    $now = date('Y-m-d H:i:s');
                    $ins->execute([$adminName, $adminNational, $adminMobile, 'doctor', $hash, $now, $now]);
                    $ok[] = 'کاربر ادمین (دکتر) ساخته شد.';
                } else {
                    $ok[] = 'کاربر با این کد ملی از قبل وجود داشت؛ ساخته نشد.';
                }
            }

            $kernel->call('config:cache');
            $kernel->call('route:cache');
            $kernel->call('view:cache');
            $ok[] = 'کش تنظیمات/مسیر/ویو ساخته شد.';

            if (! is_dir(dirname($lockFile))) {
                mkdir(dirname($lockFile), 0775, true);
            }
            file_put_contents($lockFile, date('c') . "\n");
            $done = true;
            $ok[] = 'نصب کامل شد. cpanel-install.php را حذف کنید. برای به‌روزرسانی‌های بعدی از cpanel-update.php استفاده کنید.';
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$detectedUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نصب روی cPanel — بایگانی بیماران</title>
    <style>
        :root { --bg:#0f172a; --card:#1e293b; --text:#f1f5f9; --muted:#94a3b8; --accent:#38bdf8; --ok:#34d399; --err:#f87171; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Tahoma, Vazirmatn, sans-serif; background: linear-gradient(160deg,#0f172a,#1e3a5f); color:var(--text); min-height:100vh; padding:1.5rem; }
        .wrap { max-width:720px; margin:0 auto; }
        h1 { font-size:1.4rem; margin:0 0 .5rem; }
        p.lead { color:var(--muted); margin:0 0 1.5rem; line-height:1.7; }
        .card { background:var(--card); border-radius:14px; padding:1.25rem 1.4rem; margin-bottom:1rem; border:1px solid #334155; }
        label { display:block; font-size:.85rem; color:var(--muted); margin-bottom:.35rem; }
        input[type=text], input[type=password], input[type=url], select {
            width:100%; padding:.7rem .8rem; border-radius:10px; border:1px solid #475569; background:#0f172a; color:var(--text); margin-bottom:1rem; font-size:1rem;
        }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:0 1rem; }
        @media (max-width:640px) { .grid { grid-template-columns:1fr; } }
        .check { display:flex; align-items:center; gap:.5rem; margin:0 0 1rem; color:var(--muted); }
        .check input { width:auto; margin:0; }
        button { background:var(--accent); color:#0f172a; border:0; padding:.85rem 1.4rem; border-radius:10px; font-weight:700; cursor:pointer; width:100%; font-size:1rem; }
        button:hover { filter:brightness(1.08); }
        .msg { padding:.75rem 1rem; border-radius:10px; margin-bottom:.6rem; line-height:1.6; }
        .msg.ok { background:rgba(52,211,153,.15); color:var(--ok); }
        .msg.err { background:rgba(248,113,113,.15); color:var(--err); }
        code { background:#0f172a; padding:.1rem .35rem; border-radius:4px; }
        .hint { font-size:.8rem; color:var(--muted); margin-top:-.7rem; margin-bottom:1rem; }
        h2 { font-size:1rem; margin:0 0 1rem; color:var(--accent); }
    </style>
</head>
<body>
<div class="wrap">
    <h1>نصب خودکار روی cPanel</h1>
    <p class="lead">دیتابیس MySQL را از cPanel بسازید، مشخصات را اینجا وارد کنید؛ بقیه (ساخت .env، migrate، کاربر ادمین، کش) خودکار انجام می‌شود.</p>

    <?php foreach ($errors as $e): ?>
        <div class="msg err"><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>
    <?php foreach ($ok as $m): ?>
        <div class="msg ok"><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <?php if ($done): ?>
        <div class="card">
            <p>ورود با <strong>کد ملی</strong> و رمزی که وارد کردید.</p>
            <p><a href="<?= htmlspecialchars($appUrl ?? '/', ENT_QUOTES, 'UTF-8') ?>/login" style="color:var(--accent)">رفتن به صفحه ورود</a></p>
            <p class="hint" style="margin-top:1rem">حتماً فایل <code>public/cpanel-install.php</code> را حذف کنید.</p>
        </div>
    <?php else: ?>
    <form method="post" autocomplete="off">
        <div class="card">
            <h2>۱) سایت</h2>
            <label>نام اپلیکیشن</label>
            <input type="text" name="app_name" value="<?= htmlspecialchars(cpanel_req('app_name', 'بایگانی بیماران'), ENT_QUOTES, 'UTF-8') ?>" required>
            <label>آدرس کامل سایت (بدون / آخر)</label>
            <input type="url" name="app_url" value="<?= htmlspecialchars(cpanel_req('app_url', $detectedUrl), ENT_QUOTES, 'UTF-8') ?>" required>
            <p class="hint">اگر Document Root روی <code>public</code> است همین دامنه کافی است. اگر ریشه پروژه است، از .htaccess ریشه استفاده کنید.</p>
            <label>نام پزشک روی برگه‌های چاپ</label>
            <input type="text" name="clinic_doctor_name" value="<?= htmlspecialchars(cpanel_req('clinic_doctor_name', 'دکتر مرضیه فتوحی'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="card">
            <h2>۲) دیتابیس MySQL (از cPanel → MySQL Databases)</h2>
            <div class="grid">
                <div>
                    <label>هاست</label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars(cpanel_req('db_host', 'localhost'), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div>
                    <label>پورت</label>
                    <input type="text" name="db_port" value="<?= htmlspecialchars(cpanel_req('db_port', '3306'), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>
            <label>نام دیتابیس</label>
            <input type="text" name="db_database" value="<?= htmlspecialchars(cpanel_req('db_database', $cpanelDefaults['db_database']), ENT_QUOTES, 'UTF-8') ?>" required placeholder="مثلاً user_clinic">
            <label>نام کاربری</label>
            <input type="text" name="db_username" value="<?= htmlspecialchars(cpanel_req('db_username', $cpanelDefaults['db_username']), ENT_QUOTES, 'UTF-8') ?>" required>
            <label>رمز دیتابیس</label>
            <input type="password" name="db_password" value="<?= htmlspecialchars(cpanel_req('db_password', $cpanelDefaults['db_password']), ENT_QUOTES, 'UTF-8') ?>">
            <p class="hint">کاربر را در cPanel به دیتابیس وصل کنید و همهٔ Privileges را بدهید.</p>
        </div>

        <div class="card">
            <h2>۳) کاربر ادمین اولیه</h2>
            <label>نام</label>
            <input type="text" name="admin_name" value="<?= htmlspecialchars(cpanel_req('admin_name', 'دکتر فتوحی'), ENT_QUOTES, 'UTF-8') ?>" required>
            <div class="grid">
                <div>
                    <label>کد ملی (ورود با این)</label>
                    <input type="text" name="admin_national_code" value="<?= htmlspecialchars(cpanel_req('admin_national_code', $cpanelDefaults['admin_national_code']), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div>
                    <label>موبایل</label>
                    <input type="text" name="admin_mobile" value="<?= htmlspecialchars(cpanel_req('admin_mobile', '09123456789'), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>
            <label>رمز ورود</label>
            <input type="password" name="admin_password" value="<?= htmlspecialchars(cpanel_req('admin_password', $cpanelDefaults['admin_password']), ENT_QUOTES, 'UTF-8') ?>" required minlength="8" placeholder="حداقل ۸ کاراکتر">
            <label class="check"><input type="checkbox" name="fresh_db" value="1" checked> پاک کردن جداول قبلی و نصب تمیز (اگر نصب نیمه‌کاره مانده، حتماً فعال باشد)</label>
            <label class="check"><input type="checkbox" name="run_seed" value="1" checked> ساخت کاربر ادمین بعد از migrate</label>
        </div>

        <div class="card">
            <h2>۴) پیامک (اختیاری)</h2>
            <label class="check"><input type="checkbox" name="sms_reminders" value="1" <?= isset($_POST['sms_reminders']) ? 'checked' : '' ?>> فعال‌سازی یادآوری پیامکی</label>
            <label>درایور SMS</label>
            <select name="sms_driver">
                <option value="log" <?= cpanel_req('sms_driver', 'log') === 'log' ? 'selected' : '' ?>>log (تست — ارسال واقعی نه)</option>
                <option value="smsir" <?= cpanel_req('sms_driver') === 'smsir' ? 'selected' : '' ?>>smsir</option>
            </select>
            <label>SMS.ir API Key</label>
            <input type="text" name="smsir_api_key" value="<?= htmlspecialchars(cpanel_req('smsir_api_key'), ENT_QUOTES, 'UTF-8') ?>">
            <label>شماره خط SMS.ir</label>
            <input type="text" name="smsir_line_number" value="<?= htmlspecialchars(cpanel_req('smsir_line_number'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <button type="submit">شروع نصب خودکار</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
