<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsIrClient
{
    public function send(string $mobile, string $message): void
    {
        $apiKey = config('reminders.sms.smsir.api_key');
        $lineNumber = config('reminders.sms.smsir.line_number');

        if (! $apiKey) {
            throw new RuntimeException('کلید API مربوط به SMS.ir تنظیم نشده است (SMSIR_API_KEY).');
        }

        if (! $lineNumber) {
            throw new RuntimeException('شماره خط SMS.ir تنظیم نشده است (SMSIR_LINE_NUMBER).');
        }

        $normalized = $this->normalizeMobile($mobile);
        if ($normalized === null) {
            throw new RuntimeException('شماره موبایل نامعتبر است: '.$mobile);
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'X-API-KEY' => $apiKey,
            ])
            ->timeout(20)
            ->post('https://api.sms.ir/v1/send/bulk', [
                'lineNumber' => is_numeric($lineNumber) ? $lineNumber + 0 : $lineNumber,
                'MessageText' => $message,
                'Mobiles' => [$normalized],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('SMS.ir HTTP '.$response->status().': '.$response->body());
        }

        $payload = $response->json();
        $status = (int) data_get($payload, 'status', 0);

        if ($status !== 1) {
            $detail = data_get($payload, 'message')
                ?? data_get($payload, 'data.message')
                ?? $response->body();

            throw new RuntimeException('SMS.ir خطا: '.$detail);
        }
    }

    public function normalizeMobile(string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        if (! preg_match('/^09\d{9}$/', $digits)) {
            return null;
        }

        return $digits;
    }
}
