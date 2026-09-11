<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\SurgeryAppointment;
use App\Services\Sms\SmsIrClient;
use App\Support\ActivityLogger;
use App\Support\AppointmentSms;
use App\Support\Jalali;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReminderService
{
    public function __construct(
        private readonly SmsIrClient $smsIr,
    ) {}

    /**
     * Send reminders for holding appointments on target date.
     *
     * @return array{visit:int,surgery:int,skipped:int,errors:list<string>,sms_driver:string,sms_enabled:bool}
     */
    public function sendForDate(Carbon $date): array
    {
        $stats = [
            'visit' => 0,
            'surgery' => 0,
            'skipped' => 0,
            'errors' => [],
            'sms_driver' => (string) config('reminders.sms.driver', 'log'),
            'sms_enabled' => (bool) config('reminders.sms.enabled'),
        ];

        if (! config('reminders.enabled')) {
            $stats['errors'][] = 'یادآوری‌ها در تنظیمات غیرفعال است.';

            return $stats;
        }

        $dateString = $date->toDateString();
        $jalali = Jalali::format($date, 'Y/m/d');

        $visits = Appointment::query()
            ->holdingSlot()
            ->whereDate('scheduled_date', $dateString)
            ->whereNull('reminder_sent_at')
            ->orderBy('scheduled_time')
            ->get();

        $surgeries = SurgeryAppointment::query()
            ->with('hospital')
            ->holdingSlot()
            ->whereDate('scheduled_date', $dateString)
            ->whereNull('reminder_sent_at')
            ->orderBy('scheduled_time')
            ->get();

        if ($visits->isEmpty() && $surgeries->isEmpty()) {
            $stats['skipped'] = 1;

            return $stats;
        }

        $clinicMessage = $this->buildClinicDigest($jalali, $visits, $surgeries);

        try {
            $this->notifyClinic($clinicMessage);
        } catch (\Throwable $e) {
            $stats['errors'][] = 'تلگرام کلینیک: '.$e->getMessage();
        }

        foreach ($visits as $appointment) {
            try {
                $this->notifyPatient(
                    $appointment->mobile,
                    $this->visitPatientText($appointment, $jalali)
                );
                $appointment->update(['reminder_sent_at' => now()]);
                ActivityLogger::log($appointment, 'reminder_sent', null, [
                    'channel' => 'sms',
                    'driver' => $stats['sms_driver'],
                    'date' => $jalali,
                ]);
                $stats['visit']++;
            } catch (\Throwable $e) {
                $stats['errors'][] = 'ویزیت #'.$appointment->id.': '.$e->getMessage();
            }
        }

        foreach ($surgeries as $surgery) {
            try {
                $this->notifyPatient(
                    $surgery->mobile,
                    $this->surgeryPatientText($surgery, $jalali)
                );
                $surgery->update(['reminder_sent_at' => now()]);
                ActivityLogger::log($surgery, 'reminder_sent', null, [
                    'channel' => 'sms',
                    'driver' => $stats['sms_driver'],
                    'date' => $jalali,
                ]);
                $stats['surgery']++;
            } catch (\Throwable $e) {
                $stats['errors'][] = 'عمل #'.$surgery->id.': '.$e->getMessage();
            }
        }

        return $stats;
    }

    /**
     * @param  Collection<int, Appointment>  $visits
     * @param  Collection<int, SurgeryAppointment>  $surgeries
     */
    private function buildClinicDigest(string $jalali, Collection $visits, Collection $surgeries): string
    {
        $lines = ["یادآوری نوبت‌های {$jalali}", ''];

        if ($visits->isNotEmpty()) {
            $lines[] = 'ویزیت ('.$visits->count().')';
            foreach ($visits as $item) {
                $time = $item->scheduled_time ? substr((string) $item->scheduled_time, 0, 5) : '—';
                $lines[] = "• {$time} — {$item->patient_name} — {$item->mobile}";
            }
            $lines[] = '';
        }

        if ($surgeries->isNotEmpty()) {
            $lines[] = 'عمل ('.$surgeries->count().')';
            foreach ($surgeries as $item) {
                $time = $item->scheduled_time ? substr((string) $item->scheduled_time, 0, 5) : '—';
                $hospital = $item->hospital?->name ?? '—';
                $lines[] = "• {$time} — {$item->patient_name} — {$item->surgery_type} — {$hospital}";
            }
        }

        return implode("\n", $lines);
    }

    private function visitPatientText(Appointment $appointment, string $jalali): string
    {
        return AppointmentSms::forItem($appointment, 'visit');
    }

    private function surgeryPatientText(SurgeryAppointment $surgery, string $jalali): string
    {
        return AppointmentSms::forItem($surgery, 'surgery');
    }

    private function notifyClinic(string $message): void
    {
        Log::info('[reminder:clinic] '.$message);

        if (! config('reminders.telegram.enabled')) {
            return;
        }

        $token = config('reminders.telegram.bot_token');
        $chatId = config('reminders.telegram.chat_id');

        if (! $token || ! $chatId) {
            throw new \RuntimeException('توکن یا چت‌آیدی تلگرام تنظیم نشده است.');
        }

        $response = Http::asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('ارسال تلگرام ناموفق بود: '.$response->body());
        }
    }

    private function notifyPatient(string $mobile, string $message): void
    {
        Log::info('[reminder:sms] '.$mobile.' | '.$message);

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

        if ($driver === 'http') {
            $endpoint = config('reminders.sms.endpoint');
            if (! $endpoint) {
                throw new \RuntimeException('آدرس درگاه SMS تنظیم نشده است.');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('reminders.sms.api_key'),
            ])->post($endpoint, [
                'to' => $mobile,
                'from' => config('reminders.sms.sender'),
                'message' => $message,
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException('ارسال پیامک ناموفق بود: '.$response->body());
            }

            return;
        }

        throw new \RuntimeException('درایور پیامک ناشناخته است: '.$driver);
    }
}
