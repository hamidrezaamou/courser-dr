<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\ReportNote;
use App\Models\SurgeryAppointment;
use App\Models\SurgeryType;
use App\Support\ActivityLogger;
use App\Support\BookingStatus;
use App\Support\Digits;
use App\Support\FeatureFlags;
use App\Support\IranianId;
use App\Support\Jalali;
use App\Support\ListPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $resolved = $this->resolveReportData($request);
        $rows = ListPagination::paginate($resolved['rows'], $request, 25);
        $reportNotes = $this->notesForRows(collect($rows->items()));

        return view('reports.index', array_merge($resolved['view'], [
            'rows' => $rows,
            'reportNotes' => $reportNotes,
            'canExport' => FeatureFlags::enabled('features.reports_export'),
        ]));
    }

    public function export(Request $request): StreamedResponse
    {
        if (! FeatureFlags::enabled('features.reports_export')) {
            throw new HttpException(403, 'خروجی گزارشات غیرفعال است.');
        }

        $resolved = $this->resolveReportData($request);
        $rows = $resolved['rows'];
        $noteMap = $this->notesForRows($rows);

        if (auth()->user()?->canManageSettings()) {
            ActivityLogger::log(auth()->user(), 'exported', null, [
                'section' => 'reports',
                'count' => $rows->count(),
                'kind' => $resolved['view']['kind'],
            ]);
        }

        $filename = 'reports-'.now()->format('Ymd-His').'.xlsx';
        $exportRows = [];
        foreach ($rows as $row) {
            $item = $row['item'];
            $detail = $row['type'] === 'surgery'
                ? (string) ($item->surgery_type ?? '')
                : (string) ($item->visit_type ?? $item->reason ?? '');
            $noteKey = $row['type'].':'.$item->id;
            $thread = $noteMap->get($noteKey) ?: collect();
            $exportRows[] = [
                $row['type'] === 'surgery' ? 'عمل' : 'ویزیت',
                $row['date'] ?? '',
                $row['time'] ?? '',
                BookingStatus::label((string) $item->status),
                (string) ($item->patient_name ?? $item->patient?->name ?? ''),
                (string) ($item->national_code ?? $item->patient?->national_code ?? ''),
                (string) ($item->mobile ?? $item->patient?->mobile ?? ''),
                (string) ($item->mobile_secondary ?? ''),
                $detail,
                (string) ($row['hospital'] ?? ''),
                $thread->where('include_in_print', true)->pluck('body')->filter()->implode(' / '),
            ];
        }

        return \App\Support\SimpleXlsx::download($filename, [
            'نوع', 'تاریخ', 'ساعت', 'وضعیت', 'نام بیمار', 'کد ملی', 'موبایل', 'موبایل دوم', 'جزئیات', 'بیمارستان', 'توضیحات',
        ], $exportRows);
    }

    /**
     * @return array{rows: Collection, view: array<string, mixed>}
     */
    private function resolveReportData(Request $request): array
    {
        $hospitals = Hospital::query()->orderBy('name')->get();
        $hospitalId = $request->integer('hospital_id') ?: null;
        $selectedHospital = $hospitalId
            ? $hospitals->firstWhere('id', $hospitalId)
            : null;

        $surgeryTypes = SurgeryType::query()
            ->ordered()
            ->with(['subtypes' => fn ($q) => $q->ordered()])
            ->get();
        $surgeryTypeId = $request->integer('surgery_type_id') ?: null;
        $selectedSurgeryType = $surgeryTypeId
            ? $surgeryTypes->firstWhere('id', $surgeryTypeId)
            : null;
        $subtypeRaw = trim((string) $request->input('surgery_subtype_id', ''));
        $surgerySubtypeId = null;
        $subtypeIsGeneral = false;
        if ($surgeryTypeId && $subtypeRaw === 'general') {
            $subtypeIsGeneral = true;
        } elseif ($surgeryTypeId && $subtypeRaw !== '' && $subtypeRaw !== 'all') {
            $surgerySubtypeId = (int) $subtypeRaw;
        }

        $kind = $request->string('kind')->toString();
        if (! in_array($kind, ['all', 'visit', 'surgery'], true)) {
            $kind = 'surgery';
        }
        if ($kind === 'visit') {
            $surgeryTypeId = null;
            $selectedSurgeryType = null;
            $surgerySubtypeId = null;
            $subtypeIsGeneral = false;
        }

        $emergencyOnly = $request->boolean('emergency');
        if ($emergencyOnly) {
            $kind = 'surgery';
        }

        $status = $request->string('status')->toString();
        if ($status === 'all' || $status === '') {
            $status = 'all';
        } elseif (! in_array($status, BookingStatus::all(), true)) {
            $status = 'all';
        }

        $fromJalali = $request->string('from')->toString();
        $toJalali = $request->string('to')->toString();
        $hasDateFilter = $fromJalali !== '' || $toJalali !== '';
        $q = Digits::toEnglish(trim($request->string('q')->toString()));
        $qDigits = Digits::only($q);

        $sort = $request->string('sort')->toString();
        $allowedSorts = ['id', 'type', 'patient', 'national_code', 'center', 'date', 'time', 'mobile', 'status'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'date';
        }
        $dir = strtolower($request->string('dir')->toString()) === 'asc' ? 'asc' : 'desc';
        if ($sort === 'date' && ! $request->filled('dir')) {
            $dir = 'desc';
        }

        $from = null;
        $to = null;

        if ($hasDateFilter) {
            if ($fromJalali === '') {
                $fromJalali = $toJalali;
            }
            if ($toJalali === '') {
                $toJalali = $fromJalali;
            }

            try {
                $from = Jalali::parseJalaliDate($fromJalali)->toDateString();
                $to = Jalali::parseJalaliDate($toJalali)->toDateString();
            } catch (\Throwable $e) {
                $fromJalali = '';
                $toJalali = '';
                $hasDateFilter = false;
            }

            if ($hasDateFilter && $from > $to) {
                [$from, $to] = [$to, $from];
                [$fromJalali, $toJalali] = [$toJalali, $fromJalali];
            }
        }

        $rows = collect();
        $includeVisits = ($kind === 'all' || $kind === 'visit') && ! $emergencyOnly && ! $surgeryTypeId && ! $hospitalId;

        if ($includeVisits) {
            $visits = Appointment::query()
                ->with('patient')
                ->when($hasDateFilter, fn ($query) => $query
                    ->whereDate('scheduled_date', '>=', $from)
                    ->whereDate('scheduled_date', '<=', $to))
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($q !== '', function ($query) use ($q, $qDigits) {
                    $query->where(function ($inner) use ($q, $qDigits) {
                        $inner->where('patient_name', 'like', "%{$q}%")
                            ->orWhere('national_code', 'like', "%{$q}%")
                            ->orWhere('mobile', 'like', "%{$q}%")
                            ->orWhere('visit_type', 'like', "%{$q}%")
                            ->orWhere('reason', 'like', "%{$q}%");
                        if (Schema::hasColumn('appointments', 'mobile_secondary')) {
                            $inner->orWhere('mobile_secondary', 'like', "%{$q}%");
                        }
                        if ($qDigits !== '') {
                            $inner->orWhere('national_code', 'like', "%{$qDigits}%")
                                ->orWhere('mobile', 'like', "%{$qDigits}%");
                            if (Schema::hasColumn('appointments', 'mobile_secondary')) {
                                $inner->orWhere('mobile_secondary', 'like', "%{$qDigits}%");
                            }
                        }
                    });
                })
                ->orderByDesc('scheduled_date')
                ->orderBy('scheduled_time')
                ->get();

            foreach ($visits as $item) {
                $rows->push([
                    'type' => 'visit',
                    'item' => $item,
                    'date' => $item->scheduled_date?->toDateString(),
                    'time' => (string) $item->scheduled_time,
                    'hospital' => null,
                ]);
            }
        }

        if ($kind === 'all' || $kind === 'surgery') {
            $surgeries = SurgeryAppointment::query()
                ->with(['patient', 'hospital', 'surgerySubtype'])
                ->when($hasDateFilter, fn ($query) => $query
                    ->whereDate('scheduled_date', '>=', $from)
                    ->whereDate('scheduled_date', '<=', $to))
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($hospitalId, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->when($emergencyOnly, fn ($query) => $query->where('is_emergency', true))
                ->when($q !== '', function ($query) use ($q, $qDigits) {
                    $query->where(function ($inner) use ($q, $qDigits) {
                            $inner->where('patient_name', 'like', "%{$q}%")
                                ->orWhere('national_code', 'like', "%{$q}%")
                                ->orWhere('mobile', 'like', "%{$q}%")
                                ->orWhere('mobile_secondary', 'like', "%{$q}%")
                                ->orWhere('surgery_type', 'like', "%{$q}%")
                                ->orWhereHas('hospital', fn ($h) => $h->where('name', 'like', "%{$q}%"));
                            if ($qDigits !== '') {
                                $inner->orWhere('national_code', 'like', "%{$qDigits}%")
                                    ->orWhere('mobile', 'like', "%{$qDigits}%")
                                    ->orWhere('mobile_secondary', 'like', "%{$qDigits}%");
                            }
                    });
                });
            $this->applySurgeryCatalogFilter($surgeries, $surgeryTypeId, $selectedSurgeryType, $surgerySubtypeId, $subtypeIsGeneral);
            $surgeries = $surgeries
                ->orderByDesc('scheduled_date')
                ->orderBy('scheduled_time')
                ->get();

            foreach ($surgeries as $item) {
                $rows->push([
                    'type' => 'surgery',
                    'item' => $item,
                    'date' => $item->scheduled_date?->toDateString(),
                    'time' => (string) $item->scheduled_time,
                    'hospital' => $item->hospital?->name,
                ]);
            }
        }

        $sortKey = function (array $row) use ($sort) {
            $item = $row['item'];

            return match ($sort) {
                'id' => (int) $item->id,
                'type' => $row['type'],
                'patient' => (string) ($item->patient_name ?? ''),
                'national_code' => (string) ($item->national_code ?? ''),
                'center' => (string) ($row['hospital'] ?? ($row['type'] === 'visit' ? 'ویزیت' : '')),
                'time' => (string) ($row['time'] ?? ''),
                'mobile' => (string) ($item->mobile ?? ''),
                'status' => (string) ($item->status ?? ''),
                default => (string) ($row['date'] ?? ''),
            };
        };

        $rows = $dir === 'asc'
            ? $rows->sortBy($sortKey, SORT_NATURAL | SORT_FLAG_CASE)->values()
            : $rows->sortByDesc($sortKey, SORT_NATURAL | SORT_FLAG_CASE)->values();

        $hospitalStatsQuery = SurgeryAppointment::query()
            ->selectRaw('hospital_id, status, count(*) as total')
            ->when($hasDateFilter, fn ($query) => $query
                ->whereDate('scheduled_date', '>=', $from)
                ->whereDate('scheduled_date', '<=', $to))
            ->when($hospitalId, fn ($query) => $query->where('hospital_id', $hospitalId))
            ->when($emergencyOnly, fn ($query) => $query->where('is_emergency', true));
        $this->applySurgeryCatalogFilter($hospitalStatsQuery, $surgeryTypeId, $selectedSurgeryType, $surgerySubtypeId, $subtypeIsGeneral);
        $hospitalStatsQuery = $hospitalStatsQuery
            ->groupBy('hospital_id', 'status')
            ->get()
            ->groupBy('hospital_id');

        $hospitalStats = $hospitals
            ->when($hospitalId, fn ($c) => $c->where('id', $hospitalId))
            ->map(function (Hospital $hospital) use ($hospitalStatsQuery) {
                $group = $hospitalStatsQuery->get($hospital->id, collect());
                $counts = [
                    BookingStatus::SCHEDULED => 0,
                    BookingStatus::CONFIRMED => 0,
                    BookingStatus::WAITING => 0,
                    BookingStatus::READY => 0,
                    BookingStatus::IN_CONSULT => 0,
                    BookingStatus::DONE => 0,
                    BookingStatus::CANCELLED => 0,
                ];
                foreach ($group as $row) {
                    $counts[$row->status] = (int) $row->total;
                }

                return [
                    'hospital' => $hospital,
                    'counts' => $counts,
                    'total' => array_sum($counts),
                ];
            })
            ->filter(fn ($row) => $row['total'] > 0 || $hospitalId)
            ->values();

        $summary = [
            'visit' => $includeVisits ? Appointment::query()
                ->when($hasDateFilter, fn ($query) => $query
                    ->whereDate('scheduled_date', '>=', $from)
                    ->whereDate('scheduled_date', '<=', $to))
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->count() : 0,
            'surgery' => tap(SurgeryAppointment::query()
                ->when($hasDateFilter, fn ($query) => $query
                    ->whereDate('scheduled_date', '>=', $from)
                    ->whereDate('scheduled_date', '<=', $to))
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($hospitalId, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->when($emergencyOnly, fn ($query) => $query->where('is_emergency', true)), function ($query) use ($surgeryTypeId, $selectedSurgeryType, $surgerySubtypeId, $subtypeIsGeneral) {
                    $this->applySurgeryCatalogFilter($query, $surgeryTypeId, $selectedSurgeryType, $surgerySubtypeId, $subtypeIsGeneral);
                })->count(),
        ];

        $statusLabels = ['all' => 'همه وضعیت‌ها'] + BookingStatus::labels();

        return [
            'rows' => $rows,
            'view' => [
                'fromJalali' => $fromJalali,
                'toJalali' => $toJalali,
                'hasDateFilter' => $hasDateFilter,
                'kind' => $kind,
                'status' => $status,
                'hospitalId' => $hospitalId,
                'selectedHospital' => $selectedHospital,
                'hospitals' => $hospitals,
                'surgeryTypes' => $surgeryTypes,
                'selectedSurgeryType' => $selectedSurgeryType,
                'surgeryTypeId' => $surgeryTypeId,
                'surgerySubtypeId' => $surgerySubtypeId,
                'subtypeIsGeneral' => $subtypeIsGeneral,
                'hospitalStats' => $hospitalStats,
                'summary' => $summary,
                'statusLabels' => $statusLabels,
                'q' => $q,
                'emergencyOnly' => $emergencyOnly,
                'sort' => $sort,
                'dir' => $dir,
            ],
        ];
    }

    public function updateMobileSecondary(Request $request): JsonResponse
    {
        if (! auth()->user()?->canViewReports()) {
            throw new HttpException(403, 'دسترسی به گزارشات ندارید.');
        }

        $validated = $request->validate([
            'subject_type' => ['required', 'in:visit,surgery'],
            'subject_id' => ['required', 'integer'],
            'mobile_secondary' => ['nullable', 'string', 'max:20'],
        ]);

        $raw = trim((string) ($validated['mobile_secondary'] ?? ''));
        $mobile = null;
        if ($raw !== '') {
            $mobile = IranianId::normalizeMobile($raw);
            if ($mobile === null) {
                return response()->json([
                    'ok' => false,
                    'message' => 'شماره دوم باید ۱۱ رقم و با ۰۹ شروع شود.',
                ], 422);
            }
        }

        if ($validated['subject_type'] === 'visit') {
            Appointment::ensureMobileSecondaryColumn();
            $subject = Appointment::query()->findOrFail((int) $validated['subject_id']);
        } else {
            $subject = SurgeryAppointment::query()->findOrFail((int) $validated['subject_id']);
        }

        $subject->mobile_secondary = $mobile;
        $subject->save();

        if ($subject->patient_id) {
            Patient::ensureMobileSecondaryColumn();
            $patient = Patient::query()->find($subject->patient_id);
            if ($patient && $patient->mobile_secondary !== $mobile) {
                $patient->mobile_secondary = $mobile;
                $patient->save();
            }
        }

        return response()->json([
            'ok' => true,
            'mobile_secondary' => $mobile,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\SurgeryAppointment>  $query
     */
    private function applySurgeryCatalogFilter(
        $query,
        ?int $surgeryTypeId,
        ?SurgeryType $selectedSurgeryType,
        ?int $surgerySubtypeId,
        bool $subtypeIsGeneral,
    ): void {
        if (! $surgeryTypeId || ! Schema::hasColumn('surgery_appointments', 'surgery_type_id')) {
            return;
        }

        $query->where(function ($inner) use ($surgeryTypeId, $selectedSurgeryType) {
            $inner->where('surgery_type_id', $surgeryTypeId);
            if ($selectedSurgeryType?->name) {
                $inner->orWhere('surgery_type', $selectedSurgeryType->name);
            }
        });

        if ($subtypeIsGeneral) {
            $query->whereNull('surgery_subtype_id');
        } elseif ($surgerySubtypeId) {
            $query->where('surgery_subtype_id', $surgerySubtypeId);
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<string, Collection<int, ReportNote>>
     */
    private function notesForRows(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        ReportNote::ensureIncludeInPrintColumn();

        return ReportNote::query()
            ->where(function ($query) use ($rows) {
                foreach ($rows as $row) {
                    $query->orWhere(function ($inner) use ($row) {
                        $inner->where('subject_type', $row['type'])
                            ->where('subject_id', $row['item']->id);
                    });
                }
            })
            ->orderBy('id')
            ->get()
            ->groupBy(fn (ReportNote $note) => $note->subject_type.':'.$note->subject_id);
    }
}
