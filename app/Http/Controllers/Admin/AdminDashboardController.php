<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\FollowUpReminder;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Models\Drug;
use App\Models\Hospital;
use App\Models\MedicalDocument;
use App\Models\Prescription;
use App\Models\SurgeryType;
use App\Models\User;
use App\Models\Visit;
use App\Support\AdminDashboardWidgets;
use App\Support\BookingStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $layout = AdminDashboardWidgets::normalizeLayout($user?->dashboard_layout);
        $data = $this->buildWidgetData();

        return view('admin.index', [
            'layout' => $layout,
            'widgets' => AdminDashboardWidgets::definitions(),
            'availableWidgets' => AdminDashboardWidgets::availableFor($layout),
            'widgetData' => $data,
            'adminSection' => 'overview',
        ]);
    }

    public function updateLayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'layout' => ['required', 'array'],
            'layout.*.id' => ['required', 'string', 'distinct', 'in:'.implode(',', AdminDashboardWidgets::ids())],
            'layout.*.span' => ['nullable', 'string', 'in:full,half'],
        ]);

        $layout = AdminDashboardWidgets::normalizeLayout($validated['layout']);

        /** @var User $user */
        $user = $request->user();
        $user->dashboard_layout = $layout;
        $user->save();

        return response()->json([
            'ok' => true,
            'layout' => $layout,
            'available' => array_values(AdminDashboardWidgets::availableFor($layout)),
        ]);
    }

    public function resetLayout(Request $request): JsonResponse
    {
        $layout = AdminDashboardWidgets::defaultLayout();

        /** @var User $user */
        $user = $request->user();
        $user->dashboard_layout = $layout;
        $user->save();

        return response()->json([
            'ok' => true,
            'layout' => $layout,
            'available' => array_values(AdminDashboardWidgets::availableFor($layout)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildWidgetData(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $weekStart = now()->subDays(6)->toDateString();

        $visitToday = Appointment::query()->whereDate('scheduled_date', $today);
        $surgeryToday = SurgeryAppointment::query()->whereDate('scheduled_date', $today);
        $visitYesterday = Appointment::query()->whereDate('scheduled_date', $yesterday);
        $surgeryYesterday = SurgeryAppointment::query()->whereDate('scheduled_date', $yesterday);

        $kpis = [
            'patients_total' => Patient::query()->count(),
            'patients_new_today' => Patient::query()->whereDate('created_at', $today)->count(),
            'patients_new_week' => Patient::query()->whereDate('created_at', '>=', $weekStart)->count(),
            'staff_total' => User::query()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT])->count(),
            'today_total' => (clone $visitToday)->count() + (clone $surgeryToday)->count(),
            'today_confirmed' => (clone $visitToday)->where('status', BookingStatus::CONFIRMED)->count()
                + (clone $surgeryToday)->where('status', BookingStatus::CONFIRMED)->count(),
            'today_cancelled' => (clone $visitToday)->where('status', BookingStatus::CANCELLED)->count()
                + (clone $surgeryToday)->where('status', BookingStatus::CANCELLED)->count(),
            'yesterday_total' => (clone $visitYesterday)->count() + (clone $surgeryYesterday)->count(),
            'followups_pending' => FollowUpReminder::query()
                ->where('status', 'pending')
                ->whereDate('due_date', '<=', $today)
                ->count()
                + (\App\Support\PatientFollowUps::isAvailable()
                    ? \App\Support\PatientFollowUps::openDueCount()
                    : 0),
            'followups_upcoming' => FollowUpReminder::query()
                ->where('status', 'pending')
                ->whereDate('due_date', '>', $today)
                ->count()
                + (\App\Support\PatientFollowUps::isAvailable()
                    ? \App\Support\PatientFollowUps::openUpcomingCount()
                    : 0),
        ];

        $funnel = [
            'scheduled' => Appointment::query()->whereDate('scheduled_date', $today)->where('status', BookingStatus::SCHEDULED)->count()
                + SurgeryAppointment::query()->whereDate('scheduled_date', $today)->where('status', BookingStatus::SCHEDULED)->count(),
            'confirmed' => $kpis['today_confirmed'],
            'done' => Appointment::query()->whereDate('scheduled_date', $today)->where('status', BookingStatus::DONE)->count()
                + SurgeryAppointment::query()->whereDate('scheduled_date', $today)->where('status', BookingStatus::DONE)->count(),
            'cancelled' => $kpis['today_cancelled'],
        ];

        $roleCounts = [
            'admin' => User::query()->where('role', User::ROLE_ADMIN)->count(),
            'doctor' => User::query()->where('role', User::ROLE_DOCTOR)->count(),
            'assistant' => User::query()->where('role', User::ROLE_ASSISTANT)->count(),
            'patient' => User::query()->where('role', User::ROLE_PATIENT)->count(),
        ];

        $recentLogs = ActivityLog::query()
            ->with('user')
            ->latest()
            ->limit(12)
            ->get();

        $smsEnabled = (bool) config('reminders.sms.enabled');
        $smsDriver = (string) config('reminders.sms.driver');
        $telegramEnabled = (bool) config('reminders.telegram.enabled');

        $backupDir = storage_path('app/backups');
        $latestBackup = null;
        if (File::isDirectory($backupDir)) {
            $files = collect(File::files($backupDir))
                ->sortByDesc(fn ($f) => $f->getMTime())
                ->values();
            if ($files->isNotEmpty()) {
                $file = $files->first();
                $latestBackup = [
                    'name' => $file->getFilename(),
                    'at' => $file->getMTime(),
                    'size' => $file->getSize(),
                ];
            }
        }

        $alerts = [];
        if ($kpis['followups_pending'] > 0) {
            $alerts[] = [
                'tone' => 'warn',
                'text' => number_format($kpis['followups_pending']).' پیگیری عقب‌افتاده یا سررسید امروز',
                'href' => \App\Support\PatientFollowUps::isAvailable()
                    ? route('followups.index')
                    : route('appointments.board'),
            ];
        }
        if (! $smsEnabled || $smsDriver !== 'smsir') {
            $alerts[] = [
                'tone' => 'muted',
                'text' => $smsEnabled
                    ? 'پیامک در حالت «'.$smsDriver.'» است — ارسال واقعی ممکن است خاموش باشد'
                    : 'ارسال پیامک در تنظیمات خاموش است',
                'href' => route('admin.settings.communications'),
            ];
        }
        if (! $latestBackup || ($latestBackup['at'] < now()->subDays(2)->timestamp)) {
            $alerts[] = [
                'tone' => 'danger',
                'text' => $latestBackup
                    ? 'آخرین بک‌آپ بیش از ۲ روز پیش است'
                    : 'هنوز بک‌آپی در storage/app/backups پیدا نشد',
                'href' => route('admin.settings.system'),
            ];
        }

        $deltaToday = $kpis['today_total'] - $kpis['yesterday_total'];

        $weekDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $visits = Appointment::query()->whereDate('scheduled_date', $day)->count();
            $surgeries = SurgeryAppointment::query()->whereDate('scheduled_date', $day)->count();
            $weekDays[] = [
                'date' => $day,
                'label' => jalali($day, 'm/d'),
                'visits' => $visits,
                'surgeries' => $surgeries,
                'total' => $visits + $surgeries,
            ];
        }

        $visitTodayCount = (clone $visitToday)->count();
        $surgeryTodayCount = (clone $surgeryToday)->count();

        $patientsGrowth = [
            'today' => $kpis['patients_new_today'],
            'week' => $kpis['patients_new_week'],
            'month' => Patient::query()->whereDate('created_at', '>=', now()->startOfMonth()->toDateString())->count(),
            'total' => $kpis['patients_total'],
        ];

        $upcomingSurgeries = SurgeryAppointment::query()
            ->whereDate('scheduled_date', '>=', $today)
            ->whereIn('status', [BookingStatus::SCHEDULED, BookingStatus::CONFIRMED])
            ->count();

        $upcomingSurgeriesSoon = SurgeryAppointment::query()
            ->whereDate('scheduled_date', '>=', $today)
            ->whereDate('scheduled_date', '<=', now()->addDays(7)->toDateString())
            ->whereIn('status', [BookingStatus::SCHEDULED, BookingStatus::CONFIRMED])
            ->count();

        $catalog = [
            'hospitals' => Hospital::query()->count(),
            'drugs' => Drug::query()->count(),
            'surgery_types' => SurgeryType::query()->count(),
            'visits_total' => Visit::query()->count(),
        ];

        $docsRx = [
            'documents' => MedicalDocument::query()->count(),
            'documents_week' => MedicalDocument::query()->whereDate('created_at', '>=', $weekStart)->count(),
            'prescriptions' => Prescription::query()->count(),
            'prescriptions_week' => Prescription::query()->whereDate('created_at', '>=', $weekStart)->count(),
        ];

        return [
            'alerts' => ['alerts' => $alerts],
            'kpis' => ['kpis' => $kpis, 'deltaToday' => $deltaToday],
            'week_bookings' => ['days' => $weekDays],
            'visit_surgery' => [
                'visits' => $visitTodayCount,
                'surgeries' => $surgeryTodayCount,
                'total' => $visitTodayCount + $surgeryTodayCount,
            ],
            'funnel' => ['funnel' => $funnel],
            'roles' => ['roleCounts' => $roleCounts, 'staff_total' => $kpis['staff_total']],
            'patients_growth' => ['growth' => $patientsGrowth],
            'upcoming_surgeries' => [
                'total' => $upcomingSurgeries,
                'week' => $upcomingSurgeriesSoon,
            ],
            'catalog' => ['catalog' => $catalog],
            'docs_rx' => ['docs' => $docsRx],
            'comms' => [
                'smsEnabled' => $smsEnabled,
                'smsDriver' => $smsDriver,
                'telegramEnabled' => $telegramEnabled,
            ],
            'system' => ['latestBackup' => $latestBackup],
            'shortcuts' => [],
            'activity' => ['recentLogs' => $recentLogs],
        ];
    }
}
