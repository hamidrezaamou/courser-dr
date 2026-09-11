<?php

/**
 * Exercises the ready-answers endpoints as a logged-in user and rolls the
 * whole run back, so it can be re-run against a live database safely.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ReadyAnswer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// created_by is nullable, so the checks run fine on a database with no users yet.
if ($user = User::query()->first()) {
    auth()->login($user);
}

DB::beginTransaction();

$fail = 0;

function check(string $label, bool $ok, string $detail = ''): void
{
    global $fail;
    if (! $ok) {
        $fail++;
    }
    printf("%-34s %s %s\n", $label, $ok ? 'OK' : 'FAIL', $detail);
}

$controller = app(App\Http\Controllers\ReadyAnswerController::class);
$make = fn (array $q = [], string $method = 'GET') => Illuminate\Http\Request::create('/ready-answers', $method, $q);

// create
$created = $controller->store($make([
    'title' => 'تست خودکار',
    'category' => 'تست',
    'body' => 'سلام {نام}، نوبت شما در تاریخ {تاریخ} ثبت شد.',
    'is_pinned' => true,
], 'POST'));
$payload = $created->getData(true);
$id = $payload['data']['id'] ?? null;
check('store', $created->status() === 201 && $id !== null);
check('store keeps category', ($payload['data']['category'] ?? null) === 'تست');
check('store keeps pin', (bool) ($payload['data']['is_pinned'] ?? false));

$model = ReadyAnswer::query()->findOrFail($id);

// index + filters
$index = $controller->index($make(['q' => 'تست خودکار']))->getData(true);
check('index search finds it', collect($index['data'])->contains('id', $id));
check('index returns categories', in_array('تست', $index['categories'], true));

$byCat = $controller->index($make(['category' => 'تست']))->getData(true);
check('index category filter', collect($byCat['data'])->contains('id', $id));

$missing = $controller->index($make(['q' => 'zzz-nothing-here']))->getData(true);
check('index empty result', count($missing['data']) === 0);

// pinned sorting puts it first
$smart = $controller->index($make())->getData(true);
check('pinned sorts first', ($smart['data'][0]['id'] ?? null) === $id);

// usage tracking must not bump updated_at
$before = $model->fresh()->updated_at;
$controller->markUsed($model->fresh());
$after = $model->fresh();
check('markUsed increments', $after->usage_count === 1, 'count='.$after->usage_count);
check('markUsed keeps updated_at', $before->eq($after->updated_at));

// pin toggle
$controller->togglePin($after);
check('togglePin off', $model->fresh()->is_pinned === false);

// duplicate
$dup = $controller->duplicate($make([], 'POST'), $model->fresh());
$dupData = $dup->getData(true);
check('duplicate', $dup->status() === 201 && str_contains($dupData['data']['title'], 'رونوشت'), $dupData['data']['title'] ?? '');
check('duplicate copies body', $dupData['data']['body'] === $model->body);

// update
$updated = $controller->update($make([
    'title' => 'ویرایش شد',
    'body' => 'متن جدید',
], 'PUT'), $model->fresh())->getData(true);
check('update title', $updated['data']['title'] === 'ویرایش شد');
check('update clears category', $updated['data']['category'] === null);

// validation
try {
    $controller->store($make(['body' => ''], 'POST'));
    check('empty body rejected', false);
} catch (Illuminate\Validation\ValidationException $e) {
    check('empty body rejected', true);
}

// delete
$controller->destroy($model->fresh());
check('destroy', ReadyAnswer::query()->find($id) === null);

DB::rollBack();

echo "\n".($fail === 0 ? "all checks passed\n" : "{$fail} check(s) failed\n");
exit($fail === 0 ? 0 : 1);
