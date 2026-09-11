<?php

namespace App\Console\Commands;

use App\Support\AppVersion;
use Illuminate\Console\Command;

class AppUpdate extends Command
{
    protected $signature = 'app:update
                            {--skip-cache : Do not rebuild config/route/view cache}
                            {--skip-migrate : Skip database migrations}';

    protected $description = 'Apply safe in-place updates (migrate, clear caches, rebuild production cache)';

    public function handle(): int
    {
        $this->info('به‌روزرسانی سامانه — نسخه '.AppVersion::current());

        if (! $this->option('skip-migrate')) {
            $this->comment('→ migrate --force');
            $exit = $this->call('migrate', ['--force' => true]);
            if ($exit !== self::SUCCESS) {
                $this->error('Migration failed.');

                return self::FAILURE;
            }
        }

        $this->comment('→ optimize:clear');
        $this->call('optimize:clear');

        if (! $this->option('skip-cache') && app()->environment('production')) {
            $this->comment('→ config:cache');
            $this->call('config:cache');
            $this->comment('→ route:cache');
            $this->call('route:cache');
            $this->comment('→ view:cache');
            $this->call('view:cache');
        }

        AppVersion::markUpdated();

        $this->newLine();
        $this->info('به‌روزرسانی با موفقیت انجام شد.');
        $this->line('نسخه: '.AppVersion::current());
        $this->line('زمان: '.AppVersion::lastUpdatedAt());

        if (app()->environment('production')) {
            $this->warn('اگر CSS/JS تغییر کرده: npm run build را روی سرور بزنید یا فایل public/build را آپلود کنید.');
        }

        return self::SUCCESS;
    }
}
