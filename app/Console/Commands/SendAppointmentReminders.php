<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use App\Support\Jalali;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders
                            {--date= : تاریخ میلادی Y-m-d یا شمسی Y/m/d}
                            {--days= : چند روز بعد (پیش‌فرض از config)}';

    protected $description = 'ارسال یادآوری نوبت‌های ویزیت و عمل (تلگرام کلینیک + پیامک بیمار)';

    public function handle(ReminderService $reminders): int
    {
        $dateOption = $this->option('date');
        $days = $this->option('days');

        if ($dateOption) {
            $date = $this->parseDate($dateOption);
        } else {
            $ahead = $days !== null ? (int) $days : (int) config('reminders.days_ahead', 1);
            $date = now()->startOfDay()->addDays($ahead);
        }

        $this->info('ارسال یادآوری برای '.Jalali::format($date, 'Y/m/d').' ...');

        $stats = $reminders->sendForDate($date);

        $this->line("ویزیت: {$stats['visit']} | عمل: {$stats['surgery']}");
        $this->line('SMS: '.($stats['sms_enabled'] ? $stats['sms_driver'] : 'disabled'));

        foreach ($stats['errors'] as $error) {
            $this->warn($error);
        }

        if ($stats['skipped'] === 1 && $stats['errors'] === []) {
            $this->comment('موردی برای یادآوری نبود (یا قبلاً ارسال شده).');
        }

        return self::SUCCESS;
    }

    private function parseDate(string $value): Carbon
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::parse($value)->startOfDay();
        }

        return Jalali::parseJalaliDate($value);
    }
}
