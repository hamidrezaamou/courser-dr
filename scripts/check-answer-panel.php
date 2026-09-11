<?php

/**
 * Compiles the ready-answers component and renders it once, so a Blade or
 * Alpine markup mistake surfaces here instead of in the browser.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$view = 'components.answer-panel';

$compiled = Illuminate\Support\Facades\Blade::compileString(
    file_get_contents(__DIR__.'/../resources/views/components/answer-panel.blade.php')
);

$tmp = tempnam(sys_get_temp_dir(), 'ans').'.php';
file_put_contents($tmp, $compiled);

$lint = [];
exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($tmp).' 2>&1', $lint, $code);
@unlink($tmp);

echo "-- blade compile lint --\n".implode("\n", $lint)."\n";

if ($code !== 0) {
    exit(1);
}

try {
    $html = view($view, ['mobile' => '09123456789', 'patientName' => 'علی رضایی'])->render();
} catch (Throwable $e) {
    echo "-- render FAILED --\n".$e->getMessage()."\n";
    exit(1);
}

echo "-- render ok: ".strlen($html)." bytes --\n";

foreach ([
    'ans-sheet',
    'ans-head__title',
    'ans-recip__dot',
    'ans-tabs',
    'ans-meter__parts',
    'ans-var',
    'ans-empty__title',
    'readyAnswersPanel',
    'x-data',
] as $needle) {
    printf("%-20s %s\n", $needle, str_contains($html, $needle) ? 'OK' : 'MISSING');
}

// Unresolved Blade echoes would mean the template leaked raw syntax into the output.
echo str_contains($html, '@click') ? "alpine bindings preserved: OK\n" : "alpine bindings preserved: MISSING\n";
echo preg_match('/\{\{|@php|@props/', $html) ? "leftover blade syntax: FOUND (bad)\n" : "leftover blade syntax: none\n";
