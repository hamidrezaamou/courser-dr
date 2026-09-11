<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Support\BookingStatus;
use App\Support\Jalali;
use App\Support\MobilePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();
        $todayJalali = Jalali::format(now(), 'Y/m/d');

        if (! $user->isStaff()) {
            $patient = Patient::query()
                ->where('national_code', $user->national_code)
                ->first();

            $visits = $patient
                ? Appointment::query()
                    ->where('patient_id', $patient->id)
                    ->whereDate('scheduled_date', '>=', $today)
                    ->orderBy('scheduled_date')
                    ->orderBy('scheduled_time')
                    ->limit(8)
                    ->get()
                    ->map(fn (Appointment $item) => MobilePayload::appointment($item, false))
                    ->values()
                : collect();

            $surgeries = $patient
                ? SurgeryAppointment::query()
                    ->with('hospital')
                    ->where('patient_id', $patient->id)
                    ->whereDate('scheduled_date', '>=', $today)
                    ->orderBy('scheduled_date')
                    ->orderBy('scheduled_time')
                    ->limit(8)
                    ->get()
                    ->map(fn (SurgeryAppointment $item) => MobilePayload::surgery($item, false))
                    ->values()
                : collect();

            return response()->json([
                'ok' => true,
                'user' => MobilePayload::user($user),
                'clinic' => MobilePayload::clinic(),
                'today_jalali' => $todayJalali,
                'patient' => $patient ? MobilePayload::patientCard($patient) : null,
                'stats' => [
                    ['key' => 'visits', 'label' => 'نوبت ویزیت پیش‌رو', 'value' => $visits->count()],
                    ['key' => 'surgeries', 'label' => 'نوبت عمل پیش‌رو', 'value' => $surgeries->count()],
                ],
                'upcoming_visits' => $visits,
                'upcoming_surgeries' => $surgeries,
            ]);
        }

        $visitBase = Appointment::query()->whereDate('scheduled_date', $today);
        $surgeryBase = SurgeryAppointment::query()->whereDate('scheduled_date', $today);

        $stats = [
            ['key' => 'patients', 'label' => 'کل پرونده', 'value' => Patient::query()->count()],
            ['key' => 'visits_today', 'label' => 'ویزیت امروز', 'value' => (clone $visitBase)->count()],
            ['key' => 'surgeries_today', 'label' => 'عمل امروز', 'value' => (clone $surgeryBase)->count()],
            ['key' => 'waiting', 'label' => 'در صف امروز', 'value' => (clone $visitBase)->whereIn('status', BookingStatus::clinicQueue())->count()],
            ['key' => 'confirmed', 'label' => 'تأییدشده امروز', 'value' => (clone $visitBase)->where('status', BookingStatus::CONFIRMED)->count()
                + (clone $surgeryBase)->where('status', BookingStatus::CONFIRMED)->count()],
            ['key' => 'new_today', 'label' => 'بیمار جدید امروز', 'value' => Patient::query()->whereDate('created_at', $today)->count()],
        ];

        $todayVisits = Appointment::query()
            ->with('patient')
            ->whereDate('scheduled_date', $today)
            ->whereNotIn('status', [BookingStatus::CANCELLED])
            ->orderBy('scheduled_time')
            ->limit(12)
            ->get()
            ->map(fn (Appointment $item) => MobilePayload::appointment($item))
            ->values();

        $todaySurgeries = SurgeryAppointment::query()
            ->with(['patient', 'hospital'])
            ->whereDate('scheduled_date', $today)
            ->whereNotIn('status', [BookingStatus::CANCELLED])
            ->orderBy('scheduled_time')
            ->limit(12)
            ->get()
            ->map(fn (SurgeryAppointment $item) => MobilePayload::surgery($item))
            ->values();

        return response()->json([
            'ok' => true,
            'user' => MobilePayload::user($user),
            'clinic' => MobilePayload::clinic(),
            'today_jalali' => $todayJalali,
            'stats' => $stats,
            'today_visits' => $todayVisits,
            'today_surgeries' => $todaySurgeries,
        ]);
    }
}
