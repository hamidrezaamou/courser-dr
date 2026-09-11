<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Hospital;
use App\Models\PatientFollowUp;
use App\Models\ReportNote;
use App\Models\SurgeryAppointment;
use App\Models\SurgeryType;
use App\Models\User;
use App\Services\PatientFollowUpService;
use App\Support\BookingStatus;
use App\Support\Digits;
use App\Support\FeatureFlags;
use App\Support\FollowUpStatus;
use App\Support\Jalali;
use App\Support\MobilePayload;
use App\Support\ModuleRegistry;
use App\Support\NavQuickLinks;
use App\Support\PatientFollowUps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanelApiController extends Controller
{
    public function bootstrap(Request $request): JsonResponse
    {
        $user = $request->user();
        $defs = FeatureFlags::definitions();
        $modules = [];
        foreach (ModuleRegistry::enabled() as $key => $meta) {
            $modules[] = [
                'key' => $key,
                'label' => $meta['label'],
                'hint' => $defs[$meta['feature']]['hint'] ?? '',
            ];
        }

        return response()->json([
            'ok' => true,
            'user' => MobilePayload::user($user),
            'clinic' => MobilePayload::clinic(),
            'flags' => [
                'clinic_floor' => FeatureFlags::enabled('features.clinic_floor'),
                'followups' => PatientFollowUps::isAvailable(),
                'modules' => $user->canAccessModules() && ModuleRegistry::anyEnabled(),
                'reports_export' => FeatureFlags::enabled('features.reports_export'),
                'ready_answers' => FeatureFlags::enabled('features.ready_answers'),
                'surgery_checklist' => \App\Support\SurgeryChecklist::isAvailable(),
            ],
            'modules' => $modules,
            'quick_links' => NavQuickLinks::all(),
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $kind = $request->string('kind')->toString();
        if (! in_array($kind, ['all', 'visit', 'surgery'], true)) {
            $kind = 'surgery';
        }

        $emergency = $request->boolean('emergency');
        if ($emergency) {
            $kind = 'surgery';
        }

        $todayJalali = Jalali::format(now(), 'Y/m/d');
        $fromJalali = $request->string('from')->toString() ?: $todayJalali;
        $toJalali = $request->string('to')->toString() ?: $fromJalali;
        $q = trim($request->string('q')->toString());

        try {
            $from = Jalali::parseJalaliDate($fromJalali)->toDateString();
            $to = Jalali::parseJalaliDate($toJalali)->toDateString();
        } catch (\Throwable) {
            $fromJalali = $todayJalali;
            $toJalali = $todayJalali;
            $from = now()->toDateString();
            $to = $from;
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
            [$fromJalali, $toJalali] = [$toJalali, $fromJalali];
        }

        $status = $request->string('status')->toString();
        if ($status === 'all') {
            $status = '';
        }
        $hospitalId = $request->integer('hospital_id') ?: null;
        $surgeryTypeId = $kind === 'visit' ? null : ($request->integer('surgery_type_id') ?: null);
        $subtypeRaw = trim($request->string('surgery_subtype_id')->toString());
        $subtypeIsGeneral = $surgeryTypeId && $subtypeRaw === 'general';
        $surgerySubtypeId = ($surgeryTypeId && $subtypeRaw !== '' && $subtypeRaw !== 'general')
            ? (int) $subtypeRaw
            : null;

        $surgeryTypes = SurgeryType::query()
            ->with('subtypes')
            ->orderBy('name')
            ->get()
            ->map(fn (SurgeryType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'subtypes' => $type->subtypes->map(fn ($sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                ])->values(),
            ])
            ->values();
        $selectedMatch = $surgeryTypeId
            ? $surgeryTypes->firstWhere('id', $surgeryTypeId)
            : null;
        $selectedTypeName = (string) ($selectedMatch['name'] ?? '');

        $rows = collect();
        if (($kind === 'all' || $kind === 'visit') && ! $emergency && ! $surgeryTypeId && ! $hospitalId) {
            $visits = Appointment::query()
                ->with('patient')
                ->whereDate('scheduled_date', '>=', $from)
                ->whereDate('scheduled_date', '<=', $to)
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('patient_name', 'like', "%{$q}%")
                            ->orWhere('national_code', 'like', "%{$q}%")
                            ->orWhere('mobile', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('scheduled_date')
                ->orderBy('scheduled_time')
                ->limit(200)
                ->get();
            foreach ($visits as $item) {
                $rows->push(MobilePayload::appointment($item));
            }
        }
        if ($kind === 'all' || $kind === 'surgery') {
            $surgeries = SurgeryAppointment::query()
                ->with(['patient', 'hospital', 'surgerySubtype'])
                ->whereDate('scheduled_date', '>=', $from)
                ->whereDate('scheduled_date', '<=', $to)
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($hospitalId, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->when($emergency, fn ($query) => $query->where('is_emergency', true))
                ->when($surgeryTypeId, function ($query) use ($surgeryTypeId, $selectedTypeName) {
                    $query->where(function ($inner) use ($surgeryTypeId, $selectedTypeName) {
                        $inner->where('surgery_type_id', $surgeryTypeId);
                        if ($selectedTypeName !== '') {
                            $inner->orWhere('surgery_type', $selectedTypeName);
                        }
                    });
                })
                ->when($subtypeIsGeneral, fn ($query) => $query->whereNull('surgery_subtype_id'))
                ->when($surgerySubtypeId, fn ($query) => $query->where('surgery_subtype_id', $surgerySubtypeId))
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('patient_name', 'like', "%{$q}%")
                            ->orWhere('national_code', 'like', "%{$q}%")
                            ->orWhere('mobile', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('scheduled_date')
                ->orderBy('scheduled_time')
                ->limit(200)
                ->get();
            foreach ($surgeries as $item) {
                $rows->push(MobilePayload::surgery($item));
            }
        }

        $noteCounts = [];
        if ($rows->isNotEmpty()) {
            try {
                $noteQuery = ReportNote::query();
                $noteQuery->where(function ($outer) use ($rows) {
                    foreach ($rows as $row) {
                        $outer->orWhere(function ($inner) use ($row) {
                            $inner->where('subject_type', $row['kind'] ?? 'visit')
                                ->where('subject_id', $row['id']);
                        });
                    }
                });
                foreach ($noteQuery->selectRaw('subject_type, subject_id, count(*) as c')->groupBy('subject_type', 'subject_id')->get() as $note) {
                    $noteCounts[($note->subject_type).':'.$note->subject_id] = (int) $note->c;
                }
            } catch (\Throwable) {
                $noteCounts = [];
            }
        }

        $rows = $rows->map(function (array $row) use ($noteCounts) {
            $key = ($row['kind'] ?? 'visit').':'.($row['id'] ?? 0);
            $row['note_count'] = $noteCounts[$key] ?? ((trim((string) ($row['notes'] ?? '')) !== '') ? 1 : 0);

            return $row;
        })->values();

        $summary = [
            'visit' => $rows->where('kind', 'visit')->count(),
            'surgery' => $rows->where('kind', 'surgery')->count(),
        ];

        return response()->json([
            'ok' => true,
            'kind' => $kind,
            'from' => $fromJalali,
            'to' => $toJalali,
            'today' => $todayJalali,
            'yesterday' => Jalali::format(now()->subDay(), 'Y/m/d'),
            'tomorrow' => Jalali::format(now()->addDay(), 'Y/m/d'),
            'emergency' => $emergency,
            'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name']),
            'surgery_types' => $surgeryTypes,
            'status_labels' => ['all' => 'همه وضعیت‌ها'] + BookingStatus::labels(),
            'summary' => $summary,
            'can_export' => FeatureFlags::enabled('features.reports_export'),
            'rows' => $rows,
        ]);
    }

    public function floor(Request $request): JsonResponse
    {
        $todayJalali = Jalali::format(now(), 'Y/m/d');
        $dateJalali = $request->string('date')->toString() ?: $todayJalali;
        $kind = $request->string('kind')->toString();
        if (! in_array($kind, ['all', 'visit', 'surgery'], true)) {
            $kind = 'all';
        }

        try {
            $gregorian = Jalali::parseJalaliDate($dateJalali)->toDateString();
        } catch (\Throwable) {
            $dateJalali = $todayJalali;
            $gregorian = now()->toDateString();
        }

        $map = function (string $type, $item) {
            return $type === 'surgery'
                ? MobilePayload::surgery($item)
                : MobilePayload::appointment($item);
        };

        $upcoming = [];
        $waiting = [];
        $ready = [];
        $inConsult = [];

        if ($kind !== 'surgery') {
            foreach (
                Appointment::query()
                    ->with(['patient'])
                    ->whereDate('scheduled_date', $gregorian)
                    ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::DONE, BookingStatus::NO_SHOW])
                    ->orderBy('scheduled_time')
                    ->get() as $item
            ) {
                $dto = $map('visit', $item);
                if (in_array($item->status, [BookingStatus::SCHEDULED, BookingStatus::CONFIRMED], true)) {
                    $upcoming[] = $dto;
                } elseif ($item->status === BookingStatus::WAITING) {
                    $waiting[] = $dto;
                } elseif ($item->status === BookingStatus::READY) {
                    $ready[] = $dto;
                } elseif ($item->status === BookingStatus::IN_CONSULT) {
                    $inConsult[] = $dto;
                }
            }
        }

        if ($kind !== 'visit') {
            foreach (
                SurgeryAppointment::query()
                    ->with(['patient', 'hospital', 'surgerySubtype'])
                    ->whereDate('scheduled_date', $gregorian)
                    ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::DONE, BookingStatus::NO_SHOW])
                    ->orderBy('scheduled_time')
                    ->get() as $item
            ) {
                $dto = $map('surgery', $item);
                if (in_array($item->status, [BookingStatus::SCHEDULED, BookingStatus::CONFIRMED], true)) {
                    $upcoming[] = $dto;
                } elseif ($item->status === BookingStatus::WAITING) {
                    $waiting[] = $dto;
                } elseif ($item->status === BookingStatus::READY) {
                    $ready[] = $dto;
                } elseif ($item->status === BookingStatus::IN_CONSULT) {
                    $inConsult[] = $dto;
                }
            }
        }

        return response()->json([
            'ok' => true,
            'date' => $dateJalali,
            'today' => $todayJalali,
            'kind' => $kind,
            'upcoming' => $upcoming,
            'waiting' => $waiting,
            'ready' => $ready,
            'in_consult' => $inConsult,
        ]);
    }

    public function followups(Request $request): JsonResponse
    {
        if (! PatientFollowUps::isAvailable()) {
            return response()->json(['ok' => true, 'available' => false, 'counts' => [], 'items' => []]);
        }

        PatientFollowUps::ensureTables();
        $bucket = $request->string('bucket')->toString() ?: 'due';
        if (! in_array($bucket, ['due', 'today', 'overdue', 'upcoming', 'in_progress', 'done', 'all'], true)) {
            $bucket = 'due';
        }
        $today = now()->toDateString();
        $hospitalId = $request->filled('hospital_id') ? $request->integer('hospital_id') : null;
        $query = PatientFollowUp::query()->with([
            'patient',
            'assignee',
            'hospital',
            'surgeryType',
            'surgerySubtype',
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
        if ($request->filled('source')) {
            $query->where('source', $request->string('source')->toString());
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
            'done' => $query->where('status', FollowUpStatus::DONE)->whereDate('completed_at', $today),
            default => null,
        };

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

        $items = $query
            ->orderByRaw("CASE WHEN status IN ('pending','in_progress') THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->limit(80)
            ->get()
            ->map(function (PatientFollowUp $item) {
                $display = $item->displayStatus();

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'status' => $item->status,
                    'display_status' => $display,
                    'status_label' => $item->statusLabel(),
                    'source' => $item->source,
                    'source_label' => $item->sourceLabel(),
                    'kind' => $item->kind,
                    'kind_label' => $item->kindLabel(),
                    'method' => $item->method,
                    'method_label' => $item->methodLabel(),
                    'outcome' => $item->outcome,
                    'outcome_label' => $item->outcome ? $item->outcomeLabel() : null,
                    'outcome_notes' => $item->outcome_notes,
                    'description' => $item->description,
                    'due_jalali' => $item->dueJalali(),
                    'patient_id' => $item->patient_id,
                    'patient_name' => $item->patient?->name,
                    'patient_mobile' => $item->patient?->mobile,
                    'hospital_name' => $item->hospital?->name,
                    'surgery_type_name' => $item->surgeryType?->name,
                    'surgery_subtype_name' => $item->surgerySubtype?->name,
                    'assignee_name' => $item->assignee?->name,
                    'parent_id' => $item->parent_id,
                    'open' => $item->isOpen(),
                ];
            })->values();

        $mapPairs = fn (array $pairs) => collect($pairs)->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label])->values();

        return response()->json([
            'ok' => true,
            'available' => true,
            'bucket' => $bucket,
            'counts' => $counts,
            'items' => $items,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'hospital_id' => $hospitalId,
                'kind' => $request->string('kind')->toString(),
                'method' => $request->string('method')->toString(),
                'assigned_to' => $request->string('assigned_to')->toString(),
                'surgery_type_id' => $request->integer('surgery_type_id') ?: null,
                'status' => $request->string('status')->toString(),
                'done' => $request->string('done')->toString(),
                'source' => $request->string('source')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
            'kinds' => $mapPairs(PatientFollowUpService::kinds()),
            'methods' => $mapPairs(PatientFollowUpService::methods()),
            'outcomes' => $mapPairs(PatientFollowUpService::outcomes()),
            'statuses' => collect(FollowUpStatus::all())->map(fn ($st) => ['slug' => $st, 'label' => FollowUpStatus::label($st)])->values(),
            'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name']),
            'surgery_types' => SurgeryType::query()->ordered()->get(['id', 'name']),
            'staff' => User::query()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT])->orderBy('name')->get(['id', 'name']),
        ]);
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
            return null;
        }
    }
}
