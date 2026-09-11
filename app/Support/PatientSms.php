<?php

namespace App\Support;

use App\Services\Sms\SmsIrClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PatientSms
{
    public static function send(string $mobile, string $message): void
    {
        Log::info('[patient:sms] '.$mobile.' | '.$message);

        if (! config('reminders.sms.enabled')) {
            return;
        }

        $driver = config('reminders.sms.driver', 'log');

        if ($driver === 'log') {
            return;
        }

        if ($driver === 'smsir') {
            app(SmsIrClient::class)->send($mobile, $message);

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
