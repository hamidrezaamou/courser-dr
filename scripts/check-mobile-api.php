<?php

/**
 * Syntax + route smoke-check for the staff Android API.
 *
 * Run: php scripts/check-mobile-api.php
 */

$root = dirname(__DIR__);
$files = [
    'app/Models/MobileToken.php',
    'app/Http/Middleware/AuthenticateMobile.php',
    'app/Http/Middleware/EnsureMobileStaff.php',
    'app/Support/MobilePayload.php',
    'app/Http/Controllers/Api/Mobile/AuthController.php',
    'app/Http/Controllers/Api/Mobile/HomeController.php',
    'app/Http/Controllers/Api/Mobile/PatientApiController.php',
    'app/Http/Controllers/Api/Mobile/BoardApiController.php',
    'app/Http/Controllers/Api/Mobile/BookingApiController.php',
    'app/Http/Controllers/Api/Mobile/ClinicalApiController.php',
    'routes/api.php',
];

$failed = 0;
foreach ($files as $rel) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = [];
    $code = 0;
    exec('php -l '.escapeshellarg($path).' 2>&1', $out, $code);
    if ($code !== 0) {
        $failed++;
        fwrite(STDERR, implode("\n", $out)."\n");
    }
}

if ($failed > 0) {
    exit(1);
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$expected = [
    'api/mobile/v1/login',
    'api/mobile/v1/me',
    'api/mobile/v1/home',
    'api/mobile/v1/patients',
    'api/mobile/v1/board',
];

$uris = collect(Illuminate\Support\Facades\Route::getRoutes())
    ->map(fn ($route) => $route->uri())
    ->all();

foreach ($expected as $uri) {
    if (! in_array($uri, $uris, true)) {
        fwrite(STDERR, "missing route: {$uri}\n");
        exit(1);
    }
}

echo "mobile api ok\n";
