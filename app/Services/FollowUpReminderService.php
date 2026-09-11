<?php

namespace App\Services;

use App\Models\FollowUpReminder;
use App\Services\Sms\SmsIrClient;
use App\Support\FeatureFlags;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FollowUpReminderService
{
    public function __construct(
        private readonly SmsIrClient $smsIr,
    ) {}

    /**
     * @return array{sent:int,skipped:int,errors:list<string>,sms_driver:string,sms_enabled:bool}
     */
    public function sendDue(Carbon $date): array
    {
        $stats = [
            'sent' => 0,
            'skipped' => 0,
            'errors' => [],
            'sms_driver' => (string) config('reminders.sms.driver', 'log'),
            'sms_enabled' => (bool) config('reminders.sms.enabled'),
        ];

        if (! FeatureFlags::enabled('features.followup_reminders')) {
            $stats['skipped'] = 1;
            $stats['errors'][] = 'پیگیری مراجعه بعدی در تنظیمات قابلیت‌ها غیرفعال است.';

            return $stats;
        }

        $due = FollowUpReminder::query()
            ->with('patient')
            ->where('status', 'pending')
            ->whereDate('remind_at', '<=', $date->toDateString())
            ->orderBy('remind_at')
            ->orderBy('due_date')
            ->get();

        if ($due->isEmpty()) {
            $stats['skipped'] = 1;

            return $stats;
        }

        foreach ($due as $item) {
            try {
                $patient = $item->patient;
                if (! $patient) {
                    $item->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                    $stats['errors'][] = "یادآوری #{$item->id}: بیمار یافت نشد.";
                    continue;
                }

                $dueJalali = $item->due_date
                    ? \App\Support\Jalali::format($item->due_date, 'Y/m/d')
                    : '';
                $text = $this->buildMessage($patient->name, $dueJalali);
                $this->notifyPatient((string) $patient->mobile, $text);

                $item->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'message' => $text,
                ]);
                $stats['sent']++;
            } catch (\Throwable $e) {
                $stats['errors'][] = "یادآوری #{$item->id}: ".$e->getMessage();
            }
        }

        return $stats;
    }

    private function buildMessage(string $patientName, string $dueDate): string
    {
        $phone = (string) config('clinic.phone', '');
        $phoneText = $phone !== '' ? " شماره مطب: {$phone}" : '';
        $name = trim($patientName) !== '' ? trim($patientName) : 'بیمار';
        $due = trim($dueDate) !== '' ? " تاریخ مراجعه: {$dueDate}." : '';

        return "{$name} عزیز، نوبت معاینه/پیگیری شما فرا رسیده است.{$due} لطفاً جهت اخذ نوبت با مطب تماس بگیرید.{$phoneText}";
    }

    private function notifyPatient(string $mobile, string $message): void
    {
        Log::info('[followup:sms] '.$mobile.' | '.$message);

        if (! config('reminders.sms.enabled')) {
            return;
        }

        $driver = config('reminders.sms.driver', 'log');
        if ($driver === 'log') {
            return;
        }
        if ($driver === 'smsir') {
            $this->smsIr->send($mobile, $message);
            return;
        }

        throw new \RuntimeException('درایور پیامک برای یادآوری مراجعه بعدی پشتیبانی نمی‌شود.');
    }
}
