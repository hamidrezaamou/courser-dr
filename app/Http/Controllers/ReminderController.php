<?php

namespace App\Http\Controllers;

use App\Services\ReminderService;
use App\Support\Jalali;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function send(Request $request, ReminderService $reminders): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'string'],
        ]);

        if (! config('reminders.enabled')) {
            return back()->with('error', 'یادآوری نوبت‌ها در تنظیمات غیرفعال است.');
        }

        $date = ! empty($validated['date'])
            ? Jalali::parseJalaliDate($validated['date'])
            : now()->startOfDay()->addDays((int) config('reminders.days_ahead', 1));

        $jalali = Jalali::format($date, 'Y/m/d');

        $stats = $reminders->sendForDate($date);

        if ($stats['skipped'] === 1 && $stats['errors'] === []) {
            return back()->with('error', "برای تاریخ {$jalali} نوبت فعالی بدون یادآوری قبلی پیدا نشد.");
        }

        $channelNote = $stats['sms_enabled']
            ? 'کانال پیامک: '.$stats['sms_driver']
            : 'پیامک خاموش است (فقط لاگ)';

        $message = "یادآوری {$jalali}: {$stats['visit']} ویزیت، {$stats['surgery']} عمل. ({$channelNote})";

        if ($stats['errors'] !== []) {
            $errorText = implode(' | ', array_slice($stats['errors'], 0, 5));
            if (count($stats['errors']) > 5) {
                $errorText .= ' | و '.(count($stats['errors']) - 5).' خطای دیگر';
            }

            return back()
                ->with('success', $message)
                ->with('error', 'خطاهای ارسال: '.$errorText);
        }

        return back()->with('success', $message);
    }

    public function toggleSmsGlobal(Request $request): RedirectResponse
    {
        SiteSettings::applyToConfig();

        $enabled = ! (bool) SiteSettings::effective('reminders.sms.enabled', false);

        SiteSettings::put('reminders.sms.enabled', $enabled);
        SiteSettings::applyToConfig();

        $message = $enabled
            ? 'ارسال پیامک یادآوری برای همه روزها فعال شد.'
            : 'ارسال پیامک یادآوری برای همه روزها متوقف شد.';

        return back()->with('success', $message);
    }
}
