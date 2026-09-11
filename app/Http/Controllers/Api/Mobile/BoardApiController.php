<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SurgeryAppointmentController;
use App\Models\Appointment;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Support\BookingStatus;
use App\Support\FeatureFlags;
use App\Support\Jalali;
use App\Support\MobilePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $kind = $request->string('kind')->toString();
        if (! in_array($kind, ['visit', 'surgery'], true)) {
            $kind = 'surgery';
        }

        $todayJalali = Jalali::format(now(), 'Y/m/d');
        $dateJalali = $request->string('date')->toString() ?: $todayJalali;
        $hospitalId = $request->integer('hospital_id') ?: null;

        try {
            $gregorian = Jalali::parseJalaliDate($dateJalali)->toDateString();
        } catch (\Throwable) {
            $dateJalali = $todayJalali;
            $gregorian = now()->toDateString();
        }

        $items = [];
        if ($kind === 'visit') {
            $rows = Appointment::query()
                ->with(['patient'])
                ->whereDate('scheduled_date', $gregorian)
                ->orderBy('scheduled_time')
                ->get();
            foreach ($rows as $row) {
                $items[] = MobilePayload::appointment($row);
            }
            $statsBase = Appointment::query()->whereDate('scheduled_date', $gregorian);
        } else {
            $query = SurgeryAppointment::query()
                ->with(['patient', 'hospital', 'surgerySubtype'])
                ->whereDate('scheduled_date', $gregorian)
                ->orderBy('scheduled_time');
            if ($hospitalId) {
                $query->where('hospital_id', $hospitalId);
            }
            foreach ($query->get() as $row) {
                $items[] = MobilePayload::surgery($row);
            }
            $statsBase = SurgeryAppointment::query()
                ->whereDate('scheduled_date', $gregorian)
                ->when($hospitalId, fn ($q) => $q->where('hospital_id', $hospitalId));
        }

        $stats = [
            'total' => (clone $statsBase)->count(),
            'confirmed' => (clone $statsBase)->where('status', BookingStatus::CONFIRMED)->count(),
            'scheduled' => (clone $statsBase)->where('status', BookingStatus::SCHEDULED)->count(),
            'waiting' => (clone $statsBase)->whereIn('status', BookingStatus::clinicQueue())->count(),
            'done' => (clone $statsBase)->where('status', BookingStatus::DONE)->count(),
            'cancelled' => (clone $statsBase)->where('status', BookingStatus::CANCELLED)->count(),
            'no_show' => (clone $statsBase)->where('status', BookingStatus::NO_SHOW)->count(),
            'pending_approval' => $kind === 'visit'
                ? Appointment::query()->whereDate('scheduled_date', $gregorian)->where('status', BookingStatus::PENDING_APPROVAL)->count()
                : 0,
        ];

        $reminderItems = collect($items)
            ->filter(fn (array $item) => ($item['status'] ?? '') !== BookingStatus::CANCELLED)
            ->take(40)
            ->values()
            ->map(function (array $item) {
                $status = (string) ($item['status'] ?? '');
                $isSurgery = ($item['kind'] ?? '') === 'surgery';
                $label = match (true) {
                    $status === BookingStatus::NO_SHOW => 'پیگیری عدم حضور',
                    $status === BookingStatus::DONE => 'پیام پس از مراجعه',
                    $status === BookingStatus::CANCELLED => 'پیام لغو',
                    $isSurgery => 'یادآوری عمل',
                    default => 'یادآوری ویزیت',
                };

                return [
                    'patient_id' => $item['patient_id'] ?? null,
                    'name' => $item['patient_name'] ?? '',
                    'mobile' => $item['mobile'] ?? '',
                    'national_code' => $item['national_code'] ?? '',
                    'mobile_secondary' => $item['mobile_secondary'] ?? '',
                    'time' => $item['scheduled_time_label'] ?? '',
                    'kind' => $item['title'] ?? '',
                    'label' => $label,
                    'booking' => $item,
                ];
            });

        $smsOn = (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir';

        return response()->json([
            'ok' => true,
            'kind' => $kind,
            'date' => $dateJalali,
            'today' => $todayJalali,
            'tomorrow' => Jalali::format(now()->addDay(), 'Y/m/d'),
            'hospital_id' => $hospitalId,
            'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name']),
            'stats' => $stats,
            'items' => $items,
            'reminder_items' => $reminderItems,
            'reminders_enabled' => (bool) config('reminders.enabled'),
            'board_reminders_enabled' => FeatureFlags::enabled('features.board_reminders'),
            'sms_enabled' => (bool) config('reminders.sms.enabled'),
            'sms_driver' => (string) config('reminders.sms.driver'),
            'sms_live' => $smsOn,
            'approval_enabled' => FeatureFlags::enabled('features.online_booking_approval'),
            'global_pending_approval' => FeatureFlags::enabled('features.online_booking_approval')
                ? Appointment::query()->where('status', BookingStatus::PENDING_APPROVAL)->count()
                : 0,
        ]);
    }

    public function updateVisitStatus(Request $request, Appointment $appointment): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(AppointmentController::class)->updateStatus($request, $appointment);
    }

    public function updateSurgeryStatus(Request $request, SurgeryAppointment $surgeryAppointment): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(SurgeryAppointmentController::class)->updateStatus($request, $surgeryAppointment);
    }
}
