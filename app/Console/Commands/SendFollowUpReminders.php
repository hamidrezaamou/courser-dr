<?php

namespace App\Console\Commands;

use App\Services\FollowUpReminderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendFollowUpReminders extends Command
{
    protected $signature = 'followups:send-reminders {--date= : تاریخ میلادی Y-m-d}';

    protected $description = 'ارسال یادآوری‌های مراجعه بعدی به بیماران';

    public function handle(FollowUpReminderService $service): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();

        $stats = $service->sendDue($date);

        $this->line("ارسال شد: {$stats['sent']}");
        $this->line('SMS: '.($stats['sms_enabled'] ? $stats['sms_driver'] : 'disabled'));
        foreach ($stats['errors'] as $error) {
            $this->warn($error);
        }
        if ($stats['skipped'] === 1 && $stats['errors'] === []) {
            $this->comment('موردی برای ارسال نبود.');
        }

        return self::SUCCESS;
    }
}
