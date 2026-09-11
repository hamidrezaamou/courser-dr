<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\PatientFollowUp;
use App\Models\SurgeryAppointment;
use App\Models\SurgeryType;
use App\Models\User;
use App\Services\PatientFollowUpService;
use App\Support\Digits;
use App\Support\FollowUpStatus;
use App\Support\Jalali;
use App\Support\ListPagination;
use App\Support\PatientFollowUps;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientFollowUpController extends Controller
{
    public function __construct(
        private readonly PatientFollowUpService $followUps,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);
        PatientFollowUps::ensureTables();
        try {
            $this->followUps->importFromReminders();
        } catch (\Throwable $e) {
            report($e);
        }

        $bucket = (string) $request->query('bucket', 'due');
        $allowedBuckets = ['due', 'today', 'overdue', 'upcoming', 'in_progress', 'done', 'all'];
        if (! in_array($bucket, $allowedBuckets, true)) {
            $bucket = 'due';
        }

        $today = now()->toDateString();
        $hospitalId = $request->filled('hospital_id') ? $request->integer('hospital_id') : null;
        $query = PatientFollowUp::query()
            ->with([
                'patient',
                'assignee',
                'hospital',
                'appointment.patient',
                'surgeryType',
                'surgerySubtype',
                'surgeryAppointment.hospital',
                'parent',
            ]);

        $rawSearch = trim($request->string('q')->toString());
        if ($rawSearch !== '') {
            $normalized = Digits::toEnglish($rawSearch);
            $digits = Digits::only($rawSearch);
            $query->whereHas('patient', function ($patientQuery) use ($normalized, $digits) {
                $patientQuery->where(function ($inner) use ($normalized, $digits) {
                    $inner->where('name', 'like', "%{$normalized}%");
                    if ($digits !== '') {
                        $inner->orWhere('national_code', 'like', "%{$digits}%")
                            ->orWhere('mobile', 'like', "%{$digits}%");
                    } else {
                        $inner->orWhere('national_code', 'like', "%{$normalized}%")
                            ->orWhere('mobile', 'like', "%{$normalized}%");
                    }
                });
            });
        }

        if ($request->filled('status') && in_array($request->string('status')->toString(), FollowUpStatus::all(), true)) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('kind')) {
            $query->where('kind', $request->string('kind')->toString());
        }
        if ($request->filled('method')) {
            $query->where('method', $request->string('method')->toString());
        }
        if ($request->filled('assigned_to')) {
            $assigned = $request->string('assigned_to')->toString();
            if ($assigned === 'me') {
                $query->where('assigned_to', $request->user()->id);
            } elseif ($assigned === 'none') {
                $query->whereNull('assigned_to');
            } elseif (ctype_digit($assigned)) {
                $query->where('assigned_to', (int) $assigned);
            }
        }
        if ($request->filled('surgery_type_id')) {
            $query->where('surgery_type_id', $request->integer('surgery_type_id'));
        }
        if ($hospitalId) {
            $query->where('hospital_id', $hospitalId);
        }
        if ($request->filled('surgery_subtype_id')) {
            $query->where('surgery_subtype_id', $request->integer('surgery_subtype_id'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->string('source')->toString());
        }
        if ($request->boolean('overdue')) {
            $query->open()->whereDate('due_at', '<', $today);
        }
        if ($request->filled('done')) {
            if ($request->string('done')->toString() === '1') {
                $query->where('status', FollowUpStatus::DONE);
            } elseif ($request->string('done')->toString() === '0') {
                $query->open();
            }
        }

        $from = $this->parseJalaliQuery($request->query('from'));
        $to = $this->parseJalaliQuery($request->query('to'));
        if ($from) {
            $query->whereDate('due_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('due_at', '<=', $to);
        }

        match ($bucket) {
            'due' => $query->open()->whereDate('due_at', '<=', $today),
            'today' => $query->open()->whereDate('due_at', $today),
            'overdue' => $query->open()->whereDate('due_at', '<', $today),
            'upcoming' => $query->open()->whereDate('due_at', '>', $today),
            'in_progress' => $query->where('status', FollowUpStatus::IN_PROGRESS),
            'done' => $query->where('status', FollowUpStatus::DONE),
            default => null,
        };

        $followUps = $query
            ->orderByRaw("CASE WHEN status IN ('pending','in_progress') THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->paginate(ListPagination::perPage($request))
            ->withQueryString();

        $countBase = PatientFollowUp::query()
            ->when($hospitalId, fn ($countQuery) => $countQuery->where('hospital_id', $hospitalId));
        $counts = [
            'due' => PatientFollowUps::openDueCount($hospitalId),
            'today' => PatientFollowUps::openTodayCount($hospitalId),
            'overdue' => PatientFollowUps::openOverdueCount($hospitalId),
            'upcoming' => PatientFollowUps::openUpcomingCount($hospitalId),
            'in_progress' => (clone $countBase)->where('status', FollowUpStatus::IN_PROGRESS)->count(),
            'done' => (clone $countBase)->where('status', FollowUpStatus::DONE)->whereDate('completed_at', $today)->count(),
            'all' => (clone $countBase)->count(),
        ];

        return view('follow-ups.index', [
            'followUps' => $followUps,
            'bucket' => $bucket,
            'counts' => $counts,
            'kinds' => PatientFollowUpService::kinds(),
            'methods' => PatientFollowUpService::methods(),
            'outcomes' => PatientFollowUpService::outcomes(),
            'staff' => $this->staffUsers(),
            'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name']),
            'surgeryTypes' => SurgeryType::query()->ordered()->with(['subtypes' => fn ($q) => $q->ordered()])->get(),
            'patients' => Patient::query()->active()->latest()->limit(80)->get(['id', 'name', 'mobile']),
        ]);
    }

    public function alertSummary(Request $request): JsonResponse
    {
        if (! PatientFollowUps::isAvailable()) {
            return response()->json([
                'ok' => false,
                'available' => false,
                'today' => 0,
                'upcoming' => 0,
                'overdue' => 0,
                'due' => 0,
            ], 404);
        }

        $counts = PatientFollowUps::openCounts();

        return response()->json([
            'ok' => true,
            'available' => true,
            'today' => (int) $counts['today'],
            'upcoming' => (int) $counts['upcoming'],
            'overdue' => (int) $counts['overdue'],
            'due' => (int) $counts['due'],
            'date' => now()->toDateString(),
            'date_jalali' => Jalali::format(now(), 'Y/m/d'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'kind' => ['required', 'string', 'max:64'],
            'method' => ['required', 'string', 'max:64'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['required', 'string'],
            'due_time' => ['nullable', 'string', 'max:8'],
            'surgery_appointment_id' => ['nullable', 'integer', 'exists:surgery_appointments,id'],
        ]);

        $patient = Patient::query()->findOrFail($validated['patient_id']);
        $due = $this->parseDue($validated['due_date'], $validated['due_time'] ?? null);
        if (! $due) {
            return back()->withErrors(['due_date' => 'تاریخ سررسید نامعتبر است.'])->withInput();
        }

        $surgeryId = $validated['surgery_appointment_id'] ?? null;
        $surgery = null;
        if ($surgeryId) {
            $surgery = SurgeryAppointment::query()->findOrFail($surgeryId);
            abort_unless((int) $surgery->patient_id === (int) $patient->id, 404);
        }

        $this->followUps->createManual($patient, [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'kind' => $validated['kind'],
            'method' => $validated['method'],
            'hospital_id' => $surgery?->hospital_id ?? ($validated['hospital_id'] ?? null),
            'assigned_to' => $validated['assigned_to'] ?? null,
            'due_at' => $due,
            'surgery_appointment_id' => $surgery?->id,
            'surgery_type_id' => $surgery?->surgery_type_id,
            'surgery_subtype_id' => $surgery?->surgery_subtype_id,
            'subject_type' => $surgery ? SurgeryAppointment::class : Patient::class,
            'subject_id' => $surgery?->id ?? $patient->id,
            'reference_event' => 'custom',
            'reference_at' => $due,
        ], $request->user()->id);

        return back()->with('success', 'پیگیری دستی ثبت شد.');
    }

    public function storeBulk(Request $request): JsonResponse
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.type' => ['required', 'in:surgery,visit'],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'kind' => ['nullable', 'string', 'max:64'],
            'method' => ['nullable', 'string', 'max:64'],
            'offset_amount' => ['required', 'integer', 'min:0', 'max:365'],
            'offset_unit' => ['required', 'in:day,week,month,hour'],
            'offset_direction' => ['required', 'in:before,after'],
            'due_time' => ['nullable', 'string', 'max:8'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $result = $this->followUps->createBulkRelative(
            $validated['items'],
            [
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'kind' => $validated['kind'] ?? 'custom',
                'method' => $validated['method'] ?? 'call',
                'offset_amount' => (int) $validated['offset_amount'],
                'offset_unit' => $validated['offset_unit'],
                'offset_direction' => $validated['offset_direction'],
                'due_time' => $validated['due_time'] ?? null,
                'assigned_to' => $validated['assigned_to'] ?? null,
            ],
            $request->user()?->id
        );

        if ($result['ok'] < 1) {
            return response()->json([
                'message' => 'هیچ پیگیری‌ای ثبت نشد. نوبت‌ها را بررسی کنید.',
                'ok' => 0,
                'fail' => $result['fail'],
            ], 422);
        }

        $msg = $result['fail'] > 0
            ? fa_digits((string) $result['ok']).' پیگیری ثبت شد، '.fa_digits((string) $result['fail']).' ناموفق بود.'
            : fa_digits((string) $result['ok']).' پیگیری گروهی ثبت شد.';

        return response()->json([
            'message' => $msg,
            'ok' => $result['ok'],
            'fail' => $result['fail'],
            'redirect' => route('followups.index', ['bucket' => 'upcoming']),
        ]);
    }

    public function update(Request $request, PatientFollowUp $followUp): RedirectResponse
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'kind' => ['nullable', 'string', 'max:64'],
            'method' => ['nullable', 'string', 'max:64'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'string'],
            'due_time' => ['nullable', 'string', 'max:8'],
        ]);

        $payload = array_filter([
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'kind' => $validated['kind'] ?? null,
            'method' => $validated['method'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
        if (array_key_exists('hospital_id', $validated)) {
            $payload['hospital_id'] = $validated['hospital_id'] ?: null;
        }

        if (array_key_exists('assigned_to', $validated)) {
            $this->followUps->assign($followUp, $validated['assigned_to'] ? (int) $validated['assigned_to'] : null, $request->user()->id);
            $followUp->refresh();
        }

        if (! empty($validated['due_date']) && $followUp->status !== FollowUpStatus::DONE) {
            $due = $this->parseDue($validated['due_date'], $validated['due_time'] ?? null);
            if ($due) {
                $this->followUps->reschedule($followUp, $due, $request->user()->id);
                $followUp->refresh();
            }
        }

        if ($payload !== []) {
            $followUp->update($payload);
        }

        return back()->with('success', 'پیگیری به‌روز شد.');
    }

    public function start(Request $request, PatientFollowUp $followUp): RedirectResponse
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);
        $this->followUps->start($followUp, $request->user()->id);

        return back()->with('success', 'پیگیری در حال انجام است.');
    }

    public function complete(Request $request, PatientFollowUp $followUp): RedirectResponse
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);

        $validated = $request->validate([
            'outcome' => ['required', 'string', 'max:64'],
            'outcome_notes' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->followUps->complete($followUp, $validated, $request->user()->id);

        $msg = 'نتیجه پیگیری ثبت شد.';
        if (PatientFollowUpService::outcomeNeedsRetry($validated['outcome'])) {
            $msg .= ' در صورت نیاز می‌توانید پیگیری مجدد بسازید.';
        }

        return back()->with('success', $msg);
    }

    public function fail(Request $request, PatientFollowUp $followUp): RedirectResponse
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);

        $validated = $request->validate([
            'status' => ['required', 'in:'.FollowUpStatus::REJECTED.','.FollowUpStatus::FAILED],
            'outcome' => ['nullable', 'string', 'max:64'],
            'outcome_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->followUps->fail($followUp, $validated['status'], $validated, $request->user()->id);

        return back()->with('success', 'وضعیت پیگیری به «'.FollowUpStatus::label($validated['status']).'» تغییر کرد.');
    }

    public function cancel(Request $request, PatientFollowUp $followUp): RedirectResponse
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);

        $validated = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:190'],
        ]);

        $this->followUps->cancel($followUp, $validated['cancel_reason'] ?? null, $request->user()->id);

        return back()->with('success', 'پیگیری لغو شد.');
    }

    public function retry(Request $request, PatientFollowUp $followUp): RedirectResponse
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);

        $validated = $request->validate([
            'days_after' => ['nullable', 'integer', 'min:1', 'max:365'],
            'due_date' => ['nullable', 'string'],
            'due_time' => ['nullable', 'string', 'max:8'],
            'title' => ['nullable', 'string', 'max:190'],
            'kind' => ['nullable', 'string', 'max:64'],
            'method' => ['nullable', 'string', 'max:64'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $due = null;
        if (! empty($validated['due_date'])) {
            $due = $this->parseDue($validated['due_date'], $validated['due_time'] ?? null);
        } elseif (! empty($validated['days_after'])) {
            $due = now()->startOfDay()->addDays((int) $validated['days_after'])->setTime(9, 0);
        }

        if (! $due) {
            return back()->withErrors(['due_date' => 'تاریخ یا تعداد روز پیگیری مجدد را مشخص کنید.']);
        }

        $this->followUps->retry($followUp, $due, $validated, $request->user()->id);

        return back()->with('success', 'پیگیری مجدد ثبت شد.');
    }

    private function parseDue(string $dateRaw, ?string $timeRaw): ?Carbon
    {
        $gregorian = $this->parseJalaliQuery($dateRaw);
        if (! $gregorian) {
            return null;
        }
        $due = Carbon::parse($gregorian)->startOfDay();
        $slot = trim((string) $timeRaw);
        if ($slot !== '' && preg_match('/^(\d{1,2}):(\d{2})/', $slot, $m)) {
            $due->setTime((int) $m[1], (int) $m[2], 0);
        } else {
            $due->setTime(9, 0, 0);
        }

        return $due;
    }

    private function parseJalaliQuery(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }
        try {
            return Jalali::parseJalaliDate($raw)->toDateString();
        } catch (\Throwable) {
            try {
                return Carbon::parse($raw)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function staffUsers()
    {
        return User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }
}
