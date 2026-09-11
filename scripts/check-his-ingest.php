<?php

/**
 * End-to-end check for the HIS ingest pipeline.
 *
 * Boots Laravel on an in-memory SQLite database — no MySQL and no SQL Server
 * required. Asserts the behaviours that matter: idempotent upserts, national-
 * code linking, synthetic codes for patients without one, watermark advance,
 * fail-closed auth, and the edit lock on HIS-owned rows.
 *
 * Run: php scripts/check-his-ingest.php
 */

require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\Api\HisSyncController;
use App\Http\Middleware\VerifyHisAgentKey;
use App\Models\Appointment;
use App\Models\FinancialTransaction;
use App\Models\HisSyncState;
use App\Models\Patient;
use App\Models\Visit;
use App\Support\His\HisLock;
use App\Support\His\HisPatientMatcher;
use App\Support\His\HisStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpKernel\Exception\HttpException;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config([
    'database.default' => 'sqlite',
    'database.connections.sqlite.database' => ':memory:',
    'his.enabled' => true,
    'his.key' => 'test-agent-key-please-change',
    'his.secret' => 'test-agent-secret-please-change',
    'his.max_skew_seconds' => 300,
    'his.allowed_ips' => [],
    'his.max_rows_per_batch' => 500,
    'his.status_map.default' => 'scheduled',
    'his.status_map.appointments' => [
        'visited' => 'done',
        'cancelled' => 'cancelled',
        'noshow' => 'no_show',
    ],
    'his.synthetic_national_code_prefix' => 'his',
]);

Artisan::call('migrate', ['--force' => true]);

$fail = 0;
$check = function (string $label, bool $ok, string $note = '') use (&$fail) {
    printf("%-56s %s%s\n", $label, $ok ? 'OK' : 'FAIL', $note !== '' ? '  — '.$note : '');
    if (! $ok) {
        $fail++;
    }
};

$controller = new HisSyncController();

$signedRequest = function (string $method, string $uri, array $payload = []) {
    $body = $payload === [] ? '' : json_encode($payload, JSON_UNESCAPED_UNICODE);
    $timestamp = (string) time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, (string) config('his.secret'));

    $request = Request::create($uri, $method, [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_AUTHORIZATION' => 'Bearer '.config('his.key'),
        'HTTP_X_HIS_KEY' => config('his.key'),
        'HTTP_X_HIS_TIMESTAMP' => $timestamp,
        'HTTP_X_HIS_SIGNATURE' => $signature,
    ], $body);

    if ($payload !== []) {
        $request->headers->set('Content-Type', 'application/json');
        // Laravel only parses JSON for real HTTP kernels; seed the bag ourselves.
        $request->request->replace($payload);
        $request->merge($payload);
    }

    return $request;
};

$runMiddleware = function (Request $request) {
    $middleware = new VerifyHisAgentKey();

    return $middleware->handle($request, fn ($req) => response()->json(['ok' => true]));
};

// ---------------------------------------------------------------------------
// Auth — fail closed
// ---------------------------------------------------------------------------
echo "auth\n";

$configKey = config('his.key');
config(['his.key' => '']);
$response = $runMiddleware(Request::create('/api/his/ping', 'GET', [], [], [], [
    'HTTP_AUTHORIZATION' => 'Bearer anything',
]));
$check('empty HIS_AGENT_KEY is refused', $response->getStatusCode() === 401);
config(['his.key' => $configKey]);

$response = $runMiddleware(Request::create('/api/his/ping', 'GET'));
$check('missing key is refused', $response->getStatusCode() === 401);

$response = $runMiddleware(Request::create('/api/his/ping', 'GET', [], [], [], [
    'HTTP_AUTHORIZATION' => 'Bearer wrong-key',
]));
$check('wrong key is refused', $response->getStatusCode() === 401);

$configEnabled = config('his.enabled');
config(['his.enabled' => false]);
$response = $runMiddleware($signedRequest('GET', '/api/his/ping'));
$check('disabled sync is refused', $response->getStatusCode() === 401);
config(['his.enabled' => $configEnabled]);

$stale = $signedRequest('POST', '/api/his/sync/patients', ['rows' => []]);
$stale->headers->set('X-His-Timestamp', (string) (time() - 10_000));
$staleBody = json_encode(['rows' => []], JSON_UNESCAPED_UNICODE);
$stale->headers->set(
    'X-His-Signature',
    hash_hmac('sha256', $stale->headers->get('X-His-Timestamp').'.'.$staleBody, (string) config('his.secret'))
);
$response = $runMiddleware($stale);
$check('replay outside the skew window is refused', $response->getStatusCode() === 401);

$response = $runMiddleware($signedRequest('GET', '/api/his/ping'));
$check('valid signed request is accepted', $response->getStatusCode() === 200);

// ---------------------------------------------------------------------------
// Status mapping
// ---------------------------------------------------------------------------
echo "\nstatus map\n";
$check('visited → done', HisStatus::forAppointment('Visited') === 'done');
$check('unknown falls back to scheduled', HisStatus::forAppointment('weird-code') === 'scheduled');
$check('already-valid status passes through', HisStatus::forAppointment('waiting') === 'waiting');

// ---------------------------------------------------------------------------
// Patient matching
// ---------------------------------------------------------------------------
echo "\npatient matching\n";

$manual = Patient::create([
    'name' => 'بیمار دستی',
    'national_code' => '0011223344',
    'mobile' => '09120000001',
    'source' => 'manual',
]);

$linked = HisPatientMatcher::resolve([
    'his_patient_id' => '1001',
    'national_code' => '0011223344',
    'name' => 'بیمار از HIS',
    'mobile' => '09121111111',
]);

$check('national code links to existing patient', $linked->id === $manual->id, "id={$linked->id}");
$check('HIS fields overwrite the manual ones', $linked->name === 'بیمار از HIS' && $linked->mobile === '09121111111');
$check('source becomes his', $linked->source === 'his');
$check('his_patient_id attached', $linked->his_patient_id === '1001');

$orphan = HisPatientMatcher::resolve([
    'his_patient_id' => '2002',
    'national_code' => '',
    'name' => 'بدون کد ملی',
    'mobile' => '09123333333',
]);
$check('missing national code gets synthetic key', $orphan->national_code === 'his2002', $orphan->national_code ?? 'null');
$check('empty national code does not merge people', Patient::query()->where('national_code', 'his2002')->count() === 1);

$again = HisPatientMatcher::resolve([
    'his_patient_id' => '2002',
    'name' => 'بدون کد ملی (ویرایش)',
    'mobile' => '09123333333',
]);
$check('his_patient_id is the stable key', $again->id === $orphan->id && $again->name === 'بدون کد ملی (ویرایش)');

// ---------------------------------------------------------------------------
// Batch ingest — patients / appointments / visits / finance
// ---------------------------------------------------------------------------
echo "\ningest\n";

$patientsPayload = [
    'batch_id' => 'batch-patients-1',
    'rows' => [
        [
            'his_id' => '1001',
            'his_patient_id' => '1001',
            'name' => 'بیمار از HIS',
            'national_code' => '0011223344',
            'mobile' => '09121111111',
            'changed_at' => '2026-08-26 10:00:00',
        ],
        [
            'his_id' => '3003',
            'name' => 'بیمار تازه',
            'national_code' => '1122334455',
            'mobile' => '09124444444',
            'changed_at' => '2026-08-26 10:05:00',
        ],
        [
            'his_id' => 'bad',
            // missing name on purpose
            'national_code' => '9988776655',
            'changed_at' => '2026-08-26 10:06:00',
        ],
    ],
];

$response = $controller->store($signedRequest('POST', '/api/his/sync/patients', $patientsPayload), 'patients');
$data = $response->getData(true);
$check('patients batch returns 200', $response->getStatusCode() === 200);
$check('one created, one updated, one failed', ($data['created'] ?? 0) === 1 && ($data['updated'] ?? 0) === 1 && ($data['failed'] ?? 0) === 1,
    json_encode(['c' => $data['created'] ?? null, 'u' => $data['updated'] ?? null, 'f' => $data['failed'] ?? null]));
$check('bad row did not stop the good ones', Patient::query()->where('his_patient_id', '3003')->exists());

$state = HisSyncState::forResource('patients');
$check('watermark advanced past the last successful row', $state->last_id === '3003', (string) $state->last_id);
$check('changed_at watermark stored', $state->last_changed_at !== null);

// Idempotent replay
$response = $controller->store($signedRequest('POST', '/api/his/sync/patients', [
    'batch_id' => 'batch-patients-2',
    'rows' => [$patientsPayload['rows'][0], $patientsPayload['rows'][1]],
]), 'patients');
$data = $response->getData(true);
$check('replay creates no duplicates', ($data['created'] ?? -1) === 0 && ($data['updated'] ?? -1) === 2);
$check('still one row per his id', Patient::query()->whereIn('his_patient_id', ['1001', '3003'])->count() === 2);

$apptsPayload = [
    'rows' => [
        [
            'his_id' => 'A1',
            'his_patient_id' => '1001',
            'patient_name' => 'بیمار از HIS',
            'national_code' => '0011223344',
            'mobile' => '09121111111',
            'scheduled_date' => '2026-08-27',
            'scheduled_time' => '09:30',
            'status' => 'visited',
            'changed_at' => '2026-08-26 11:00:00',
        ],
        [
            'his_id' => 'A2',
            'his_patient_id' => '3003',
            'patient_name' => 'بیمار تازه',
            'national_code' => '1122334455',
            'mobile' => '09124444444',
            'scheduled_date' => '2026-08-28',
            'scheduled_time' => 'نوبت 3',
            'status' => 'reserved',
            'changed_at' => '2026-08-26 11:05:00',
        ],
    ],
];

$response = $controller->store($signedRequest('POST', '/api/his/sync/appointments', $apptsPayload), 'appointments');
$data = $response->getData(true);
$check('appointments imported', ($data['created'] ?? 0) === 2, json_encode($data));

$a1 = Appointment::query()->where('his_appointment_id', 'A1')->first();
$a2 = Appointment::query()->where('his_appointment_id', 'A2')->first();
$check('status visited mapped to done', $a1?->status === 'done', (string) $a1?->status);
$check('clock time normalised to H:i:s', $a1?->scheduled_time === '09:30:00', (string) $a1?->scheduled_time);
$check('queue label normalised to Q03', $a2?->scheduled_time === 'Q03', (string) $a2?->scheduled_time);
$check('appointment linked to HIS patient', (int) $a1?->patient_id === (int) $linked->id);

$response = $controller->store($signedRequest('POST', '/api/his/sync/visits', [
    'rows' => [[
        'his_id' => 'V1',
        'his_patient_id' => '1001',
        'his_appointment_id' => 'A1',
        'patient_name' => 'بیمار از HIS',
        'national_code' => '0011223344',
        'mobile' => '09121111111',
        'diagnosis' => 'قوز قرنیه',
        'visited_at' => '2026-08-20 12:00:00',
        'changed_at' => '2026-08-26 12:00:00',
    ]],
]), 'visits');
$data = $response->getData(true);
$visit = Visit::query()->where('his_admission_id', 'V1')->first();
$check('visit imported', ($data['created'] ?? 0) === 1 && $visit !== null);
$check('visit linked to appointment', (int) $visit?->appointment_id === (int) $a1?->id);
$check('visit created_at reflects HIS date', $visit?->created_at?->toDateString() === '2026-08-20', (string) $visit?->created_at);

$response = $controller->store($signedRequest('POST', '/api/his/sync/finance', [
    'rows' => [
        [
            'his_id' => 'F1',
            'his_patient_id' => '1001',
            'patient_name' => 'بیمار از HIS',
            'national_code' => '0011223344',
            'mobile' => '09121111111',
            'type' => 'charge',
            'amount' => 250000,
            'label' => 'ویزیت',
            'transaction_date' => '2026-08-20',
            'changed_at' => '2026-08-26 13:00:00',
        ],
        [
            'his_id' => 'PAY-9',
            'his_patient_id' => '1001',
            'his_admission_id' => 'ADM-77',
            'patient_name' => 'بیمار از HIS',
            'national_code' => '0011223344',
            'mobile' => '09121111111',
            'type' => 'payment',
            'amount' => 180000,
            'payment_type' => 1,
            'transaction_date' => '2026-08-21',
            'changed_at' => '2026-08-26 13:05:00',
        ],
        [
            'his_id' => 'PAY-10',
            'his_patient_id' => '1001',
            'type' => 'payment',
            'amount' => 50000,
            'method' => '2',
            'transaction_date' => '2026-08-21',
            'changed_at' => '2026-08-26 13:06:00',
        ],
    ],
]), 'finance');
$data = $response->getData(true);
$tx = FinancialTransaction::query()->where('his_transaction_id', 'F1')->first();
$pos = FinancialTransaction::query()->where('his_transaction_id', 'PAY-9')->first();
$cash = FinancialTransaction::query()->where('his_transaction_id', 'PAY-10')->first();
$check('finance lands in financial_transactions', ($data['created'] ?? 0) === 3 && $tx !== null);
$check('finance amount preserved', (int) $tx?->amount === 250000);
$check('PaymentType 1 maps to pos', $pos?->method === 'pos', (string) $pos?->method);
$check('method code 2 maps to cash', $cash?->method === 'cash', (string) $cash?->method);
$check('admission id kept in notes', str_contains((string) $pos?->notes, 'ADM-77'));

// ---------------------------------------------------------------------------
// Edit lock
// ---------------------------------------------------------------------------
echo "\nedit lock\n";

$locked = false;
try {
    HisLock::guard($a1);
} catch (HttpException $e) {
    $locked = $e->getStatusCode() === 403;
}
$check('HIS appointment cannot be edited', $locked);

$manualAppt = Appointment::create([
    'patient_id' => $manual->id,
    'patient_name' => $manual->name,
    'national_code' => $manual->national_code,
    'mobile' => $manual->mobile,
    'scheduled_date' => '2026-09-01',
    'status' => 'scheduled',
    'source' => 'manual',
]);
$unlocked = true;
try {
    HisLock::guard($manualAppt);
} catch (HttpException) {
    $unlocked = false;
}
$check('manual appointment remains editable', $unlocked);

// ---------------------------------------------------------------------------
// Cursor endpoint
// ---------------------------------------------------------------------------
echo "\ncursor\n";
$cursor = $controller->cursor('patients')->getData(true);
$check('cursor returns last_id', ($cursor['last_id'] ?? null) === '3003');
$check('unknown resource is 404', $controller->cursor('nope')->getStatusCode() === 404);

echo "\n".($fail === 0 ? "ALL PASS\n" : "{$fail} FAILURE(S)\n");
exit($fail === 0 ? 0 : 1);
