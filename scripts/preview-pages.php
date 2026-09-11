<?php

/**
 * Renders real pages to standalone HTML files with the built CSS inlined, so the
 * UI can be reviewed (and screenshotted) without serving the app or logging in.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$outDir = __DIR__.'/../storage/app/ui-preview';
@mkdir($outDir, 0775, true);

$manifest = json_decode(file_get_contents(__DIR__.'/../public/build/manifest.json'), true);
$css = file_get_contents(__DIR__.'/../public/build/'.$manifest['resources/css/app.css']['file']);
$alpine = file_get_contents(__DIR__.'/../node_modules/alpinejs/dist/cdn.min.js');

DB::beginTransaction();

$columns = Schema::getColumnListing('users');
$attributes = ['name' => 'دکتر فتوحی', 'password' => 'password123'];
foreach (['national_code' => '0010000001', 'mobile' => '09120000001', 'role' => User::ROLE_ADMIN, 'email' => 'doctor@clinic.test'] as $key => $value) {
    if (in_array($key, $columns, true)) {
        $attributes[$key] = $value;
    }
}

$user = new User();
$user->forceFill($attributes)->save();
$user->role = User::ROLE_ADMIN;

function render(string $uri, ?User $as): ?string
{
    global $kernel;

    if ($as) {
        auth()->login($as);
    } else {
        auth()->logout();
    }

    $request = Request::create($uri, 'GET');
    $request->setLaravelSession(app('session.store'));

    try {
        $response = $kernel->handle($request);
    } catch (Throwable $e) {
        echo "  render failed: {$uri} — ".$e->getMessage()."\n";

        return null;
    }

    return $response->getStatusCode() < 400 ? $response->getContent() : null;
}

function standalone(string $html, string $css, string $alpine): string
{
    // Swap the vite tags for inlined assets so the file works straight off disk.
    $html = preg_replace('#<link[^>]+rel="?stylesheet"?[^>]*href="[^"]*build/assets[^"]*"[^>]*>#i', '<style>'.$css.'</style>', $html);
    $html = preg_replace('#<link[^>]+href="[^"]*build/assets[^"]*\.css"[^>]*>#i', '<style>'.$css.'</style>', $html);
    $html = preg_replace('#<script[^>]+src="[^"]*build/assets[^"]*"[^>]*></script>#i', '<script>'.$alpine.'</script>', $html);

    if (! str_contains($html, '<style>')) {
        $html = str_replace('</head>', '<style>'.$css.'</style></head>', $html);
    }

    return $html;
}

$pages = [
    'login' => ['/login', null],
    'register' => ['/register', null],
    'forgot-password' => ['/forgot-password', null],
    'profile' => ['/profile', $user],
    'dashboard' => ['/dashboard', $user],
    'settings-hospitals' => ['/settings/hospitals', $user],
    'reports' => ['/reports', $user],
    'board' => ['/appointments/board', $user],
    'activity-logs' => ['/activity-logs', $user],
];

foreach ($pages as $name => [$uri, $as]) {
    $html = render($uri, $as);
    if ($html === null) {
        continue;
    }
    file_put_contents($outDir.'/page-'.$name.'.html', standalone($html, $css, $alpine));
    echo "wrote page-{$name}.html\n";
}

DB::rollBack();
