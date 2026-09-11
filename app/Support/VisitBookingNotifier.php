<?php

namespace App\Support;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;

class VisitBookingNotifier
{
    public static function registered(Appointment $appointment): void
    {
        if ($appointment->status === BookingStatus::PENDING_APPROVAL) {
            return;
        }

        if (! (bool) SiteSettings::effective('reminders.sms.visit_on_booking', false)) {
            return;
        }

        if (! config('reminders.sms.enabled')) {
            return;
        }

        $mobile = trim((string) $appointment->mobile);
        if ($mobile === '') {
            return;
        }

        $message = AppointmentSms::forItem($appointment, 'visit');

        try {
            PatientSms::send($mobile, $message);
        } catch (\Throwable $e) {
            Log::warning('visit booking sms failed: '.$e->getMessage(), [
                'appointment_id' => $appointment->id,
            ]);
        }
    }
}
