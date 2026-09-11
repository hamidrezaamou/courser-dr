<?php

namespace App\Http\Controllers;

use App\Services\Sms\SmsIrClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function send(Request $request, SmsIrClient $smsIr): JsonResponse
    {
        if (! config('reminders.sms.enabled') || config('reminders.sms.driver') !== 'smsir') {
            return response()->json(['message' => 'ارسال پیامک از پنل در تنظیمات فعال نیست.'], 422);
        }

        $validated = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $smsIr->send($validated['mobile'], $validated['message']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'پیامک با موفقیت ارسال شد.']);
    }
}
