<?php

namespace App\Support;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;

class BookingApprovalNotifier
{
    public static function approved(Appointment $appointment): void
    {
        $date = Jalali::format($appointment->scheduled_date, 'Y/m/d');
        $time = SlotLabel::display((string) $appointment->scheduled_time);

        $message = AppointmentSms::render(
            '{name} عزیز، درخواست نوبت ویزیت شما برای {date} ساعت {time} تأیید شد.',
            [
                'name' => (string) $appointment->patient_name,
                'date' => $date,
                'time' => $time,
            ]
        );

        try {
            PatientSms::send((string) $appointment->mobile, $message);
        } catch (\Throwable $e) {
            Log::warning('booking approval sms failed: '.$e->getMessage());
        }
    }

    public static function rejected(Appointment $appointment): void
    {
        $date = Jalali::format($appointment->scheduled_date, 'Y/m/d');
        $time = SlotLabel::display((string) $appointment->scheduled_time);

        $message = AppointmentSms::render(
            '{name} عزیز، متأسفانه درخواست نوبت ویزیت {date} ساعت {time} پذیرفته نشد. لطفاً با مطب تماس بگیرید.',
            [
                'name' => (string) $appointment->patient_name,
                'date' => $date,
                'time' => $time,
            ]
        );

        try {
            PatientSms::send((string) $appointment->mobile, $message);
        } catch (\Throwable $e) {
            Log::warning('booking rejection sms failed: '.$e->getMessage());
        }
    }
}
