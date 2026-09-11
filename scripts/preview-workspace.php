<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Patient;
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

$patient = Patient::query()->first();
if (! $patient) {
    echo "no patient\n";
    DB::rollBack();
    exit(1);
}

auth()->login($user);
$uri = '/patients/'.$patient->id.'?embed=1&open=photos';
$request = Request::create($uri, 'GET');
$request->setLaravelSession(app('session.store'));
$response = $kernel->handle($request);
$html = $response->getContent();

$html = preg_replace('#<link[^>]+rel="?stylesheet"?[^>]*href="[^"]*build/assets[^"]*"[^>]*>#i', '<style>'.$css.'</style>', $html);
$html = preg_replace('#<link[^>]+href="[^"]*build/assets[^"]*\.css"[^>]*>#i', '<style>'.$css.'</style>', $html);
$html = preg_replace('#<script[^>]+src="[^"]*build/assets[^"]*"[^>]*></script>#i', '<script>'.$alpine.'</script>', $html);
if (! str_contains($html, '<style>')) {
    $html = str_replace('</head>', '<style>'.$css.'</style></head>', $html);
}

$out = $outDir.'/page-workspace-media.html';
file_put_contents($out, $html);
echo "wrote page-workspace-media.html for patient #{$patient->id}\n";
echo "status ".$response->getStatusCode()."\n";

DB::rollBack();
