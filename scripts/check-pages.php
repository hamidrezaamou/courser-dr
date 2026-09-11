<?php

/**
 * Boots the app and requests every reachable page as a real HTTP call, so a
 * broken Blade template in any view fails here instead of in front of a client.
 * Everything runs inside a transaction that is rolled back at the end.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo 'users columns: '.implode(', ', Schema::getColumnListing('users'))."\n\n";

DB::beginTransaction();

$columns = Schema::getColumnListing('users');
$attributes = [
    'name' => 'کاربر آزمایشی',
    'password' => 'password123',
];
foreach (['national_code' => '0010000001', 'mobile' => '09120000001', 'role' => User::ROLE_ADMIN, 'email' => 'test@example.test'] as $key => $value) {
    if (in_array($key, $columns, true)) {
        $attributes[$key] = $value;
    }
}

// forceFill, because the column set differs between the stock and clinic schemas.
$user = new User();
$user->forceFill($attributes)->save();

// The dev database predates the role column, so grant staff access in memory.
$user->role = User::ROLE_ADMIN;

$fail = 0;

function hit(string $uri, ?User $as, string $label): void
{
    global $kernel, $fail;

    if ($as) {
        auth()->login($as);
    } else {
        auth()->logout();
    }

    $request = Request::create($uri, 'GET');
    $request->setLaravelSession(app('session.store'));

    try {
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $body = $response->getContent();
    } catch (Throwable $e) {
        $fail++;
        printf("%-34s %-8s %s\n", $label, 'THROW', get_class($e).': '.$e->getMessage());

        return;
    }

    // A redirect is fine (auth/guest middleware); a 500 or an error page is not.
    $ok = $status < 400;
    $leak = $ok && preg_match('/Undefined variable|htmlspecialchars\(\)|Trying to access array offset|syntax error/i', $body);

    if (! $ok || $leak) {
        $fail++;
    }

    printf(
        "%-34s %-8s %s\n",
        $label,
        $status,
        $leak ? 'RENDER WARNING in body' : ($ok ? '' : 'failed')
    );
}

echo "--- guest pages ---\n";
hit('/login', null, 'login');
hit('/register', null, 'register');
hit('/forgot-password', null, 'forgot password');
hit('/reset-password/testtoken', null, 'reset password');

echo "\n--- authenticated pages ---\n";
hit('/dashboard', $user, 'dashboard');
hit('/profile', $user, 'profile');
hit('/confirm-password', $user, 'confirm password');
hit('/verify-email', $user, 'verify email');
hit('/settings/times', $user, 'settings: times');
hit('/settings/hospitals', $user, 'settings: hospitals');
hit('/settings/surgery-types', $user, 'settings: surgery types');
hit('/settings/drugs', $user, 'settings: drugs');
hit('/appointments/board', $user, 'appointment board');
hit('/reports', $user, 'reports');
hit('/activity-logs', $user, 'activity logs');
hit('/admin', $user, 'admin overview');
hit('/admin/users', $user, 'admin users');
hit('/admin/users/create', $user, 'admin user create');
hit('/admin/communications', $user, 'admin communications');
hit('/admin/brand', $user, 'admin brand');
hit('/admin/system', $user, 'admin system');
hit('/admin/features', $user, 'admin features');
hit('/prints', $user, 'prints');
hit('/patients/create', $user, 'patient create');
hit('/surgery/register', $user, 'surgery register');

if ($patient = App\Models\Patient::query()->first()) {
    hit("/patients/{$patient->id}", $user, 'patient profile');
    hit("/patients/{$patient->id}/appointments/create", $user, 'appointment create');
    hit("/patients/{$patient->id}/surgery-appointments/create", $user, 'surgery create');
} else {
    echo "no patient rows; skipped patient-scoped pages\n";
}

DB::rollBack();

echo "\n".($fail === 0 ? "all pages rendered\n" : "{$fail} page(s) failed\n");
exit($fail === 0 ? 0 : 1);
