<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Support\PublicStorage;
use App\Support\SiteSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PurgeRetentionData extends Command
{
    protected $signature = 'retention:purge {--dry-run : فقط گزارش}';

    protected $description = 'پاکسازی لاگ‌های قدیمی و فایل‌های موقت طبق سیاست نگه‌داشت';

    public function handle(): int
    {
        $logDays = (int) SiteSettings::effective('privacy.activity_log_days', 365);
        $qrDays = (int) SiteSettings::effective('privacy.qr_cache_days', 30);
        $dry = (bool) $this->option('dry-run');

        $logCutoff = now()->subDays(max(30, $logDays));
        $logQuery = ActivityLog::query()
            ->where('created_at', '<', $logCutoff)
            ->whereNotIn('action', ['secure_erased']);

        $logCount = $logQuery->count();
        $this->info("لاگ‌های قدیمی‌تر از {$logCutoff->toDateString()}: {$logCount}");

        if (! $dry && $logCount > 0) {
            $logQuery->delete();
            $this->info('لاگ‌ها پاک شدند.');
        }

        $qrDeleted = 0;
        $cutoffTs = now()->subDays(max(1, $qrDays))->getTimestamp();

        foreach ([PublicStorage::path('qr-cache'), PublicStorage::legacyPath('qr-cache')] as $qrDir) {
            if (! File::isDirectory($qrDir)) {
                continue;
            }

            foreach (File::files($qrDir) as $file) {
                if ($file->getMTime() < $cutoffTs) {
                    $qrDeleted++;
                    if (! $dry) {
                        File::delete($file->getPathname());
                    }
                }
            }
        }
        $this->info("فایل QR قدیمی: {$qrDeleted}");

        return self::SUCCESS;
    }
}
