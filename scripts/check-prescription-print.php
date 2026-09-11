<?php

/**
 * End-to-end check for the prescription printout: brand images (logo, signature,
 * stamp) must reach the page, and the QR must be a real, scannable symbol.
 *
 * Runs against an in-memory SQLite database so it needs no live MySQL.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\Modules\StructuredPrescriptionController;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Support\ClinicBrand;
use App\Support\PublicStorage;
use App\Support\QrCode;
use App\Support\QrImage;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config([
    'database.default' => 'sqlite',
    'database.connections.sqlite.database' => ':memory:',
]);

Artisan::call('migrate', ['--force' => true]);

$fail = 0;
$check = function (string $label, bool $ok, string $note = '') use (&$fail) {
    printf("%-52s %s%s\n", $label, $ok ? 'OK' : 'FAIL', $note !== '' ? '  '.$note : '');
    if (! $ok) {
        $fail++;
    }
};

// ---------------------------------------------------------------------------
// Brand assets: write real images through the same disk the admin form uses.
// ---------------------------------------------------------------------------
$made = [];

$makeImage = function (string $relative, int $w, int $h) use (&$made): string {
    $absolute = PublicStorage::path($relative);
    File::ensureDirectoryExists(dirname($absolute));

    $img = imagecreatetruecolor($w, $h);
    imagefilledrectangle($img, 0, 0, $w - 1, $h - 1, imagecolorallocate($img, 20, 90, 160));
    imagepng($img, $absolute);
    imagedestroy($img);

    $made[] = $absolute;

    return $relative;
};

SiteSettings::putMany([
    'clinic.doctor_name' => 'دکتر آزمایشی',
    'clinic.phone' => '02100000000',
    'clinic.logo_path' => $makeImage('clinic-brand/test-logo.png', 120, 60),
    'clinic.signature_path' => $makeImage('clinic-brand/test-signature.png', 160, 70),
    'clinic.stamp_path' => $makeImage('clinic-brand/test-stamp.png', 80, 80),
]);
SiteSettings::applyToConfig();

$check('public disk is writable', PublicStorage::writable(), PublicStorage::root());
$check('logo resolves to a data URI', str_starts_with((string) ClinicBrand::logoDataUri(), 'data:image/png;base64,'));
$check('signature resolves to a data URI', str_starts_with((string) ClinicBrand::signatureDataUri(), 'data:image/png;base64,'));
$check('stamp resolves to a data URI', str_starts_with((string) ClinicBrand::stampDataUri(), 'data:image/png;base64,'));

// A file recorded in settings but missing on disk must not blow up the print.
SiteSettings::putMany(['clinic.logo_path' => 'clinic-brand/does-not-exist.png']);
$check('missing file degrades to null', ClinicBrand::logoDataUri() === null);
SiteSettings::putMany(['clinic.logo_path' => 'clinic-brand/test-logo.png']);

// ---------------------------------------------------------------------------
// QR: the cached PNG must decode back to the exact payload.
// ---------------------------------------------------------------------------
$payload = 'https://clinic.test/modules/prescriptions/1/print';
$uri = QrImage::dataUri($payload, 240);

$check('QR data URI produced', is_string($uri) && str_starts_with($uri, 'data:image/'));
$check('QR is a PNG (GD available)', is_string($uri) && str_starts_with($uri, 'data:image/png;base64,'));

if (is_string($uri) && str_starts_with($uri, 'data:image/png;base64,')) {
    $binary = base64_decode(substr($uri, strlen('data:image/png;base64,')));
    $image = @imagecreatefromstring($binary);
    $check('QR PNG is decodable', $image !== false);

    if ($image !== false) {
        // Read the pixels back into a matrix and confirm it carries the payload.
        $matrix = QrCode::matrix($payload);
        $modules = count($matrix) + 8;
        $scale = (int) (imagesx($image) / $modules);
        $mismatch = 0;

        foreach ($matrix as $r => $cells) {
            foreach ($cells as $c => $dark) {
                $rgb = imagecolorat($image, (($c + 4) * $scale) + 1, (($r + 4) * $scale) + 1);
                $isDark = (($rgb >> 16) & 0xFF) < 128;
                if ($isDark !== $dark) {
                    $mismatch++;
                }
            }
        }

        $check('QR pixels match the encoded matrix', $mismatch === 0, "mismatches={$mismatch}");
        $check('QR quiet zone is white', ((imagecolorat($image, 1, 1) >> 16) & 0xFF) > 200);
        imagedestroy($image);
    }
}

$check('QR SVG fallback renders', str_contains((string) QrImage::svg($payload), '<rect'));
$check('QR cache landed on the public disk', PublicStorage::resolve('qr-cache') !== null || File::isDirectory(PublicStorage::path('qr-cache')));

// ---------------------------------------------------------------------------
// Render the actual print view.
// ---------------------------------------------------------------------------
$user = new User();
$user->forceFill([
    'name' => 'دکتر آزمایشی',
    'national_code' => '0010000009',
    'mobile' => '09120000009',
    'password' => bcrypt('secret123'),
    'role' => User::ROLE_ADMIN,
])->save();

$patient = Patient::create([
    'name' => 'بیمار آزمایشی',
    'national_code' => '0011223344',
    'mobile' => '09120000001',
]);

$prescription = Prescription::create([
    'patient_id' => $patient->id,
    'created_by' => $user->id,
    'notes' => 'یادداشت آزمایشی',
]);

PrescriptionItem::create([
    'prescription_id' => $prescription->id,
    'drug_name' => 'قطره بتامتازون',
    'usage_type' => 'چشمی',
    'dosage' => '۱ قطره',
    'frequency' => 'روزی ۴ بار',
    'meal_timing' => 'after_meal',
    'duration' => '۷ روز',
    'instructions' => 'قبل از مصرف تکان دهید',
    'sort_order' => 1,
]);

view()->share('errors', new Illuminate\Support\ViewErrorBag());

$html = (new StructuredPrescriptionController())->print($prescription)->render();

$check('print view renders', strlen($html) > 500, strlen($html).' bytes');
$check('signature embedded in printout', substr_count($html, 'data:image/png;base64,') >= 4, substr_count($html, 'data:image/png;base64,').' images');
$check('no unresolved storage/ links', ! str_contains($html, 'src="'.rtrim(config('app.url'), '/').'/storage/'));
$check('drug row present', str_contains($html, 'قطره بتامتازون'));
$check('reference caption present', str_contains($html, 'RX:'.$prescription->id.':P'.$patient->id));

// ---------------------------------------------------------------------------
foreach ($made as $path) {
    File::delete($path);
}
File::deleteDirectory(PublicStorage::path('qr-cache'));

echo "\n".($fail === 0 ? "ALL PASS\n" : "{$fail} FAILURE(S)\n");
exit($fail === 0 ? 0 : 1);
