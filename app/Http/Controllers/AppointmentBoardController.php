<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Hospital;
use App\Models\SurgeryAppointment;
use App\Support\BookingStatus;
use App\Support\FeatureFlags;
use App\Support\Jalali;
use App\Support\ListPagination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentBoardController extends Controller
{
    public function index(Request $request): View
    {
        $kind = $request->string('kind')->toString();
        if (! in_array($kind, ['visit', 'surgery'], true)) {
            $kind = 'surgery';
        }

        $hospitals = Hospital::query()->orderBy('name')->get();
        $hospitalId = $request->integer('hospital_id') ?: null;

        $todayJalali = Jalali::format(now(), 'Y/m/d');
        $tomorrowJalali = Jalali::format(now()->addDay(), 'Y/m/d');

        $dateJalali = $request->string('date')->toString();
        if ($dateJalali === '') {
            $dateJalali = $todayJalali;
        }
        $hasDateFilter = true;

        try {
            $gregorian = Jalali::parseJalaliDate($dateJalali)->toDateString();
        } catch (\Throwable $e) {
            $dateJalali = $todayJalali;
            $gregorian = now()->toDateString();
        }

        $rows = collect();

        if ($kind === 'visit') {
            $visitQuery = Appointment::query()
                ->with('patient')
                ->whereDate('scheduled_date', $gregorian)
                ->orderBy('scheduled_time');

            foreach ($visitQuery->get() as $item) {
                $rows->push([
                    'type' => 'visit',
                    'item' => $item,
                    'time' => (string) $item->scheduled_time,
                ]);
            }
        } else {
            $surgeryQuery = SurgeryAppointment::query()
                ->with(['patient', 'hospital'])
                ->whereDate('scheduled_date', $gregorian)
                ->orderBy('scheduled_time');

            if ($hospitalId) {
                $surgeryQuery->where('hospital_id', $hospitalId);
            }

            foreach ($surgeryQuery->get() as $item) {
                $rows->push([
                    'type' => 'surgery',
                    'item' => $item,
                    'time' => (string) $item->scheduled_time,
                ]);
            }
        }

        $rows = $rows->sortBy('time')->values();

        $reminderItems = $rows
            ->filter(fn (array $row) => ($row['item']->status ?? '') !== BookingStatus::CANCELLED)
            ->take(40)
            ->values()
            ->map(function (array $row) {
                $item = $row['item'];
                $isSurgery = $row['type'] === 'surgery';
                $status = (string) ($item->status ?? '');

                $label = match (true) {
                    $status === BookingStatus::NO_SHOW => 'پیگیری عدم حضور',
                    $status === BookingStatus::DONE => 'پیام پس از مراجعه',
                    $status === BookingStatus::CANCELLED => 'پیام لغو',
                    $isSurgery => 'یادآوری عمل',
                    default => 'یادآوری ویزیت',
                };

                $payload = \App\Support\ToolboxPayload::fromBoardRow($row);

                return (object) [
                    'patient_id' => $item->patient_id,
                    'name' => $item->patient_name,
                    'mobile' => $item->mobile,
                    'national_code' => $item->national_code,
                    'mobile_secondary' => $item->mobile_secondary ?? $item->patient?->mobile_secondary,
                    'time' => \App\Support\SlotLabel::display((string) $item->scheduled_time),
                    'kind' => $isSurgery
                        ? ($item->surgery_type ?: 'عمل')
                        : ($item->visit_type ?: 'ویزیت'),
                    'label' => $label,
                    'answer_booking' => $payload['answerBooking'] ?? null,
                    'toolbox_b64' => \App\Support\ToolboxPayload::encode($payload),
                ];
            });

        $rows = ListPagination::paginate($rows, $request, 25);

        $visitBase = Appointment::query()->whereDate('scheduled_date', $gregorian);
        $surgeryBase = SurgeryAppointment::query()
            ->whereDate('scheduled_date', $gregorian)
            ->when($hospitalId, fn ($query) => $query->where('hospital_id', $hospitalId));

        $statsBase = $kind === 'visit' ? $visitBase : $surgeryBase;

        $stats = [
            'total' => (clone $statsBase)->count(),
            'confirmed' => (clone $statsBase)->where('status', BookingStatus::CONFIRMED)->count(),
            'scheduled' => (clone $statsBase)->where('status', BookingStatus::SCHEDULED)->count(),
            'pending_approval' => $kind === 'visit'
                ? (clone $visitBase)->where('status', BookingStatus::PENDING_APPROVAL)->count()
                : 0,
            'cancelled' => (clone $statsBase)->where('status', BookingStatus::CANCELLED)->count(),
            'done' => (clone $statsBase)->where('status', BookingStatus::DONE)->count(),
            'no_show' => (clone $statsBase)->where('status', BookingStatus::NO_SHOW)->count(),
        ];

        $globalPendingApproval = FeatureFlags::enabled('features.online_booking_approval')
            ? Appointment::query()->where('status', BookingStatus::PENDING_APPROVAL)->count()
            : 0;

        return view('appointments.board', [
            'rows' => $rows,
            'dateJalali' => $dateJalali,
            'todayJalali' => $todayJalali,
            'tomorrowJalali' => $tomorrowJalali,
            'hasDateFilter' => $hasDateFilter,
            'kind' => $kind,
            'hospitalId' => $hospitalId,
            'hospitals' => $hospitals,
            'stats' => $stats,
            'reminderItems' => $reminderItems,
            'boardRemindersEnabled' => FeatureFlags::enabled('features.board_reminders'),
            'smsRemindersEnabled' => (bool) config('reminders.sms.enabled'),
            'remindersEnabled' => (bool) config('reminders.enabled'),
            'globalPendingApproval' => $globalPendingApproval,
            'approvalEnabled' => FeatureFlags::enabled('features.online_booking_approval'),
        ]);
    }
}
