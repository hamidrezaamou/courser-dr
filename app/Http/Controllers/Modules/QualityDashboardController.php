<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\PatientSurgeryChecklist;
use App\Models\SurgeryAppointment;
use App\Support\BookingStatus;
use App\Support\Jalali;
use App\Support\ModuleRegistry;
use App\Support\SurgeryChecklist;
use Carbon\Carbon;
use Illuminate\View\View;

class QualityDashboardController extends Controller
{
    public function index(): View
    {
        $from = now()->startOfMonth();
        $to = now()->endOfDay();

        $visits = Appointment::query()->whereBetween('scheduled_date', [$from, $to])->get();
        $surgeries = SurgeryAppointment::query()->whereBetween('scheduled_date', [$from, $to])->get();
        $all = $visits->count() + $surgeries->count();

        $noShow = $visits->where('status', BookingStatus::NO_SHOW)->count()
            + $surgeries->where('status', BookingStatus::NO_SHOW)->count();

        $done = $visits->where('status', BookingStatus::DONE)->count()
            + $surgeries->where('status', BookingStatus::DONE)->count();

        $cancelled = $visits->where('status', BookingStatus::CANCELLED)->count()
            + $surgeries->where('status', BookingStatus::CANCELLED)->count();

        $checklistTotal = 0;
        $checklistSaved = 0;
        if (SurgeryChecklist::isAvailable()) {
            $checklistTotal = PatientSurgeryChecklist::query()->whereBetween('created_at', [$from, $to])->count();
            $checklistSaved = PatientSurgeryChecklist::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('saved_at')
                ->count();
        }

        $today = Carbon::today();
        $todayTotal = Appointment::query()->whereDate('scheduled_date', $today)->count()
            + SurgeryAppointment::query()->whereDate('scheduled_date', $today)->count();

        return view('modules.quality.index', [
            'moduleSection' => ModuleRegistry::definitions()['quality']['section'],
            'monthLabel' => Jalali::format($from, 'Y/m'),
            'stats' => [
                'total' => $all,
                'done' => $done,
                'no_show' => $noShow,
                'cancelled' => $cancelled,
                'no_show_rate' => $all > 0 ? round($noShow / $all * 100, 1) : 0,
                'done_rate' => $all > 0 ? round($done / $all * 100, 1) : 0,
                'checklist_total' => $checklistTotal,
                'checklist_saved' => $checklistSaved,
                'checklist_rate' => $checklistTotal > 0 ? round($checklistSaved / $checklistTotal * 100, 1) : 0,
                'today_total' => $todayTotal,
            ],
        ]);
    }
}
