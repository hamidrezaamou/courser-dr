<?php

use App\Support\SiteSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    SiteSettings::applyToConfig();

    if (! config('reminders.enabled')) {
        return;
    }

    $target = (string) config('reminders.send_time', '18:00');
    if (now()->format('H:i') !== $target) {
        return;
    }

    $cacheKey = 'auto-reminders:'.now()->toDateString();
    if (Cache::has($cacheKey)) {
        return;
    }

    Artisan::call('appointments:send-reminders');
    Cache::put($cacheKey, true, now()->endOfDay());
})->everyMinute()->name('appointments:auto-reminders')->withoutOverlapping();

Schedule::command('followups:send-reminders')->dailyAt('10:00');
Schedule::command('db:backup')->dailyAt('02:30');
Schedule::command('retention:purge')->weeklyOn(0, '03:15');
