<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\Hospital;
use App\Models\SurgeryAppointment;
use App\Models\SurgeryProgramGroup;
use App\Models\SurgeryProgramGroupDay;
use App\Models\SurgerySubtype;
use App\Models\SurgeryType;
use App\Support\AppointmentSms;
use App\Support\SurgeryCapacityLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TimeController extends Controller
{
    public function index(Request $request): View
    {
        $kind = $request->string('kind')->toString();
        if (! in_array($kind, ['visit', 'surgery'], true)) {
            $kind = 'visit';
        }

        $hospitals = Hospital::query()->orderBy('name')->get();
        $surgeryTypes = SurgeryType::query()->active()->ordered()->with(['subtypes' => fn ($q) => $q->active()->ordered()])->get();
        SurgeryProgramGroup::ensureTables();

        $selectedHospitalId = $request->integer('hospital_id') ?: ($hospitals->first()?->id);
        $selectedTypeId = $request->filled('surgery_type_id') ? $request->integer('surgery_type_id') : null;
        $rawSubtype = $request->input('surgery_subtype_id');
        $selectedSubtypeId = null;
        if ($rawSubtype === 'general') {
            $selectedSubtypeId = 'general';
        } elseif ($rawSubtype !== null && $rawSubtype !== '') {
            $selectedSubtypeId = (int) $rawSubtype;
        }

        $visitSchedules = ClinicSchedule::query()
            ->where('kind', 'visit')
            ->whereNull('hospital_id')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        $allSurgerySchedules = ClinicSchedule::query()
            ->with(['hospital', 'surgeryType', 'surgerySubtype'])
            ->where('kind', 'surgery')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        $surgeryBookedCounts = $this->bookedCountsFor($allSurgerySchedules);
        $groupDayTotals = $allSurgerySchedules->isEmpty()
            ? collect()
            : SurgeryProgramGroupDay::query()
                ->whereIn(
                    'hospital_id',
                    $allSurgerySchedules->pluck('hospital_id')->filter()->unique()->values()
                )
                ->whereIn(
                    'date_key',
                    $allSurgerySchedules->pluck('date_key')->filter()->unique()->values()
                )
                ->get()
                ->keyBy(fn (SurgeryProgramGroupDay $day) => implode('|', [
                    $day->surgery_program_group_id,
                    $day->hospital_id,
                    $day->date_key,
                ]));

        $surgerySchedulesPayload = $allSurgerySchedules->map(function (ClinicSchedule $day) use ($surgeryBookedCounts, $groupDayTotals) {
            $group = SurgeryCapacityLimits::groupForSchedule($day);
            $groupDay = $group && $day->hospital_id
                ? $groupDayTotals->get(implode('|', [$group->id, $day->hospital_id, $day->date_key]))
                : null;
            $baseTotal = $groupDay
                ? max(1, (int) $groupDay->total_slots)
                : count($day->slotOptions());
            $usedUnits = (int) ($surgeryBookedCounts[$day->id] ?? 0);
            $remaining = max(0, $baseTotal - $usedUnits);
            $settings = $day->settings ?? [];
            $capacityRules = SurgeryCapacityLimits::rulesFromSettings($settings);

            $groupTotal = $groupDay ? (int) $groupDay->total_slots : null;

            return [
                'id' => $day->id,
                'hospital_id' => $day->hospital_id,
                'hospital_name' => $day->hospital?->name,
                'surgery_type_id' => $day->surgery_type_id,
                'surgery_subtype_id' => $day->surgery_subtype_id,
                'type_name' => $day->surgeryType?->name,
                'subtype_name' => $day->surgerySubtype?->name,
                'program_group_id' => $group?->id,
                'program_group_name' => $group?->name,
                'program_group_total' => $groupTotal,
                'date_key' => $day->date_key,
                'year' => $day->year,
                'month' => $day->month,
                'day' => $day->day,
                'weekday' => $day->weekday,
                'total' => $baseTotal,
                'raw_total' => $day->totalSlots(),
                'booked' => $usedUnits,
                'free' => $remaining,
                'times_count' => count($day->times()),
                'delete_url' => route('times.destroy', $day),
                'sms_text' => $day->smsText(),
                'update_sms_url' => route('times.update-sms', $day),
                'capacity_limits_enabled' => (bool) ($settings['capacityLimitsEnabled'] ?? false),
                'capacity_limits' => $capacityRules,
                'update_capacity_url' => route('times.update-capacity', $day),
            ];
        })->values();

        return view('times.index', [
            'kind' => $kind,
            'hospitals' => $hospitals,
            'surgeryTypes' => $surgeryTypes,
            'selectedHospitalId' => $selectedHospitalId,
            'selectedTypeId' => $selectedTypeId,
            'selectedSubtypeId' => $selectedSubtypeId,
            'visitSchedules' => $visitSchedules,
            'visitGrouped' => $visitSchedules->groupBy('month'),
            'visitBookedCounts' => $this->bookedCountsFor($visitSchedules),
            'surgerySchedulesPayload' => $surgerySchedulesPayload,
            'monthNames' => ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
            'smsPresets' => AppointmentSms::presets(),
            'smsDefaultVisit' => AppointmentSms::defaultFor('visit'),
            'smsDefaultSurgery' => AppointmentSms::defaultFor('surgery'),
            'programGroups' => \Illuminate\Support\Facades\Schema::hasTable('surgery_program_groups')
                ? SurgeryProgramGroup::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with('items')
                    ->get()
                    ->map(fn (SurgeryProgramGroup $g) => [
                        'id' => $g->id,
                        'name' => $g->name,
                        'whole_type_ids' => $g->items->whereNull('surgery_subtype_id')->pluck('surgery_type_id')->values(),
                        'subtype_ids' => $g->items->whereNotNull('surgery_subtype_id')->pluck('surgery_subtype_id')->values(),
                        'type_ids' => $g->items->pluck('surgery_type_id')->unique()->values(),
                    ])
                : collect(),
            'settingsSection' => 'times',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kind' => ['required', 'in:visit,surgery'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'surgery_type_id' => ['nullable', 'integer', 'exists:surgery_types,id'],
            'surgery_subtype_id' => ['nullable', 'integer', 'exists:surgery_subtypes,id'],
            'surgery_subtype_ids' => ['nullable', 'array'],
            'surgery_subtype_ids.*' => ['nullable'],
            'date_key' => ['required', 'string', 'max:20'],
            'year' => ['required', 'integer', 'min:1300', 'max:1600'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'day' => ['required', 'integer', 'min:1', 'max:31'],
            'weekday' => ['nullable', 'string', 'max:30'],
            'display_text' => ['nullable', 'string', 'max:120'],
            'total_slots' => ['nullable', 'integer', 'min:1', 'max:100'],
            'slot_prefix' => ['nullable', 'string', 'max:50'],
            'slot_mode' => ['nullable', 'in:time,queue'],
            'times' => ['nullable', 'string'],
            'queue_count' => ['nullable', 'integer', 'min:1', 'max:99'],
            'sms_text' => ['nullable', 'string', 'max:1000'],
            'capacity_limits_enabled' => ['nullable', 'boolean'],
            'capacity_limits' => ['nullable', 'string'],
        ]);

        $subtypeTargets = $this->resolveSubtypeTargets($validated);
        $created = 0;
        $skipped = 0;

        foreach ($subtypeTargets as $subtypeId) {
            $payload = $validated;
            $payload['surgery_subtype_id'] = $subtypeId;
            $result = $this->createSchedule($request, $payload, count($subtypeTargets) === 1);
            if ($result === true) {
                $created++;
            } elseif ($result instanceof RedirectResponse) {
                return $result;
            } else {
                $skipped++;
            }
        }

        if ($created === 0 && $skipped > 0) {
            return back()->withInput()->with('error', 'این تاریخ برای زیرگروه‌های انتخاب‌شده قبلاً ثبت شده است.');
        }

        $redirect = ['kind' => $validated['kind']];
        if (! empty($validated['hospital_id'])) {
            $redirect['hospital_id'] = $validated['hospital_id'];
            $redirect['surgery_type_id'] = $validated['surgery_type_id'] ?? '';
            $redirect['surgery_subtype_id'] = $validated['surgery_subtype_id'] ?? '';
        }

        $message = $created > 0
            ? "{$created} برنامه روز با موفقیت ثبت شد."
            : 'روزی ثبت نشد.';
        if ($skipped > 0) {
            $message .= " ({$skipped} مورد تکراری رد شد)";
        }

        return redirect()
            ->route('settings.times', $redirect)
            ->with('success', $message);
    }

    /**
     * @return true|RedirectResponse
     */
    private function createSchedule(Request $request, array $validated, bool $withRedirectErrors)
    {
        $hospitalId = null;
        $typeId = null;
        $subtypeId = null;

        if ($validated['kind'] === 'surgery') {
            if (empty($validated['hospital_id'])) {
                return $withRedirectErrors
                    ? back()->withInput()->withErrors(['hospital_id' => 'بیمارستان را انتخاب کنید.'])
                    : false;
            }
            if (empty($validated['surgery_type_id'])) {
                return $withRedirectErrors
                    ? back()->withInput()->withErrors(['surgery_type_id' => 'نوع عمل را انتخاب کنید.'])
                    : false;
            }

            $hospitalId = (int) $validated['hospital_id'];
            $typeId = (int) $validated['surgery_type_id'];
            $subtypeId = ! empty($validated['surgery_subtype_id']) ? (int) $validated['surgery_subtype_id'] : null;

            if ($subtypeId) {
                $belongs = SurgerySubtype::query()
                    ->where('id', $subtypeId)
                    ->where('surgery_type_id', $typeId)
                    ->exists();
                if (! $belongs) {
                    return $withRedirectErrors
                        ? back()->withInput()->withErrors(['surgery_subtype_id' => 'زیرگروه با نوع عمل هم‌خوان نیست.'])
                        : false;
                }
            }
        }

        $slotMode = ($validated['slot_mode'] ?? 'time') === 'queue' ? 'queue' : 'time';

        if ($slotMode === 'queue') {
            $count = (int) ($validated['queue_count'] ?? $validated['total_slots'] ?? 10);
            $times = \App\Support\SlotLabel::queueTokens($count);
        } else {
            $times = \App\Support\SlotLabel::normalizeList(
                preg_split('/\r\n|\r|\n/', (string) ($validated['times'] ?? '')) ?: [],
                'time'
            );
        }

        if ($times === []) {
            return $withRedirectErrors
                ? back()->withInput()->withErrors(['times' => $slotMode === 'queue' ? 'تعداد نوبت را وارد کنید.' : 'حداقل یک ساعت وارد کنید.'])
                : false;
        }

        $existsQuery = ClinicSchedule::query()
            ->where('kind', $validated['kind'])
            ->where('date_key', $validated['date_key']);

        if ($hospitalId) {
            $existsQuery->where('hospital_id', $hospitalId)
                ->where('surgery_type_id', $typeId);
            if ($subtypeId) {
                $existsQuery->where('surgery_subtype_id', $subtypeId);
            } else {
                $existsQuery->whereNull('surgery_subtype_id');
            }
        } else {
            $existsQuery->whereNull('hospital_id')
                ->whereNull('surgery_type_id')
                ->whereNull('surgery_subtype_id');
        }

        if ($existsQuery->exists()) {
            return $withRedirectErrors
                ? back()->withInput()->with('error', 'این تاریخ برای همین ترکیب بیمارستان/نوع/زیرگروه قبلاً ثبت شده است.')
                : false;
        }

        $schedule = ClinicSchedule::create([
            'kind' => $validated['kind'],
            'hospital_id' => $hospitalId,
            'surgery_type_id' => $typeId,
            'surgery_subtype_id' => $subtypeId,
            'date_key' => $validated['date_key'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'day' => $validated['day'],
            'weekday' => $validated['weekday'] ?? null,
            'display_text' => $validated['display_text'] ?? null,
            'settings' => SurgeryCapacityLimits::mergeIntoSettings([
                'totalSlots' => (int) ($validated['total_slots'] ?? count($times)),
                'slotPrefix' => $validated['slot_prefix'] ?? ($validated['kind'] === 'surgery' ? 'عمل' : 'ویزیت'),
                'slotMode' => $slotMode,
                'times' => $times,
                'smsText' => trim((string) ($validated['sms_text'] ?? ''))
                    ?: AppointmentSms::defaultFor($validated['kind']),
            ], $validated['kind'] === 'surgery' && $request->boolean('capacity_limits_enabled'),
                SurgeryCapacityLimits::parseRequest($validated['capacity_limits'] ?? null)),
            'created_by' => $request->user()->id,
        ]);

        \App\Models\SurgeryProgramGroupDay::syncFromSchedule($schedule);

        return true;
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kind' => ['required', 'in:visit,surgery'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'surgery_type_id' => ['nullable', 'integer', 'exists:surgery_types,id'],
            'surgery_subtype_id' => ['nullable', 'integer', 'exists:surgery_subtypes,id'],
            'surgery_subtype_ids' => ['nullable', 'array'],
            'surgery_subtype_ids.*' => ['nullable'],
            'days' => ['required', 'array', 'min:1'],
            'days.*.date_key' => ['required', 'string', 'max:20'],
            'days.*.year' => ['required', 'integer', 'min:1300', 'max:1600'],
            'days.*.month' => ['required', 'integer', 'min:1', 'max:12'],
            'days.*.day' => ['required', 'integer', 'min:1', 'max:31'],
            'days.*.weekday' => ['nullable', 'string', 'max:30'],
            'days.*.display_text' => ['nullable', 'string', 'max:120'],
            'total_slots' => ['nullable', 'integer', 'min:1', 'max:100'],
            'slot_prefix' => ['nullable', 'string', 'max:50'],
            'slot_mode' => ['nullable', 'in:time,queue'],
            'times' => ['nullable', 'string'],
            'queue_count' => ['nullable', 'integer', 'min:1', 'max:99'],
            'sms_text' => ['nullable', 'string', 'max:1000'],
            'capacity_limits_enabled' => ['nullable', 'boolean'],
            'capacity_limits' => ['nullable', 'string'],
        ]);

        $subtypeTargets = $this->resolveSubtypeTargets($validated);
        $created = 0;
        $skipped = 0;

        foreach ($validated['days'] as $day) {
            foreach ($subtypeTargets as $subtypeId) {
                $payload = array_merge($validated, $day);
                unset($payload['days'], $payload['surgery_subtype_ids']);
                $payload['surgery_subtype_id'] = $subtypeId;
                $result = $this->createSchedule($request, $payload, false);
                if ($result === true) {
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        $redirect = ['kind' => $validated['kind']];
        if (! empty($validated['hospital_id'])) {
            $redirect['hospital_id'] = $validated['hospital_id'];
            $redirect['surgery_type_id'] = $validated['surgery_type_id'] ?? '';
            $redirect['surgery_subtype_id'] = $validated['surgery_subtype_id'] ?? '';
        }

        $message = $created > 0
            ? "{$created} برنامه روز با موفقیت ثبت شد."
            : 'روزی ثبت نشد.';
        if ($skipped > 0) {
            $message .= " ({$skipped} مورد تکراری رد شد)";
        }

        return redirect()
            ->route('settings.times', $redirect)
            ->with($created > 0 ? 'success' : 'error', $message);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<int|null>
     */
    private function resolveSubtypeTargets(array $validated): array
    {
        if (($validated['kind'] ?? '') !== 'surgery') {
            return [null];
        }

        $raw = $validated['surgery_subtype_ids'] ?? null;
        if (! is_array($raw) || $raw === []) {
            $single = $validated['surgery_subtype_id'] ?? null;

            return [$single !== null && $single !== '' ? (int) $single : null];
        }

        $out = [];
        foreach ($raw as $value) {
            if ($value === null || $value === '' || $value === 'general') {
                $out[] = null;
            } else {
                $out[] = (int) $value;
            }
        }

        $out = array_values(array_unique($out, SORT_REGULAR));

        return $out !== [] ? $out : [null];
    }

    public function destroy(Request $request, ClinicSchedule $schedule): RedirectResponse
    {
        $kind = $schedule->kind;
        $hospitalId = $schedule->hospital_id;
        $typeId = $schedule->surgery_type_id;
        $subtypeId = $schedule->surgery_subtype_id;
        $schedule->delete();

        return redirect()
            ->route('settings.times', $this->timesRedirectParams($kind, $hospitalId, $typeId, $subtypeId))
            ->with('success', 'روز کاری حذف شد.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:clinic_schedules,id'],
            'kind' => ['nullable', 'in:visit,surgery'],
        ]);

        $schedules = ClinicSchedule::query()
            ->whereIn('id', $validated['ids'])
            ->get();

        if ($schedules->isEmpty()) {
            return redirect()
                ->route('settings.times', ['kind' => $validated['kind'] ?? 'visit'])
                ->with('error', 'روزی برای حذف یافت نشد.');
        }

        $first = $schedules->first();
        $kind = $validated['kind'] ?? $first->kind;
        $hospitalId = $first->hospital_id;
        $typeId = $first->surgery_type_id;
        $subtypeId = $first->surgery_subtype_id;

        foreach ($schedules as $schedule) {
            $schedule->delete();
        }

        $count = $schedules->count();

        return redirect()
            ->route('settings.times', $this->timesRedirectParams($kind, $hospitalId, $typeId, $subtypeId))
            ->with('success', $count.' روز حذف شد.');
    }

    public function bulkUpdateSms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:clinic_schedules,id'],
            'sms_text' => ['nullable', 'string', 'max:1000'],
            'kind' => ['nullable', 'in:visit,surgery'],
        ]);

        $schedules = ClinicSchedule::query()
            ->whereIn('id', $validated['ids'])
            ->get();

        if ($schedules->isEmpty()) {
            return redirect()
                ->route('settings.times', ['kind' => $validated['kind'] ?? 'visit'])
                ->with('error', 'روزی برای ذخیره پیامک یافت نشد.');
        }

        $first = $schedules->first();
        $kind = $validated['kind'] ?? $first->kind;
        $hospitalId = $first->hospital_id;
        $typeId = $first->surgery_type_id;
        $subtypeId = $first->surgery_subtype_id;

        foreach ($schedules as $schedule) {
            $settings = $schedule->settings ?? [];
            $settings['smsText'] = trim((string) ($validated['sms_text'] ?? ''))
                ?: AppointmentSms::defaultFor($schedule->kind);
            $schedule->update(['settings' => $settings]);
        }

        $count = $schedules->count();

        return redirect()
            ->route('settings.times', $this->timesRedirectParams($kind, $hospitalId, $typeId, $subtypeId))
            ->with('success', 'متن پیامک برای '.$count.' روز ذخیره شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function timesRedirectParams(string $kind, ?int $hospitalId, ?int $typeId, mixed $subtypeId): array
    {
        $redirect = ['kind' => $kind];
        if ($hospitalId) {
            $redirect['hospital_id'] = $hospitalId;
            $redirect['surgery_type_id'] = $typeId;
            $redirect['surgery_subtype_id'] = $subtypeId ?? '';
        }

        return $redirect;
    }

    public function updateSms(Request $request, ClinicSchedule $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'sms_text' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = $schedule->settings ?? [];
        $settings['smsText'] = trim((string) ($validated['sms_text'] ?? ''))
            ?: AppointmentSms::defaultFor($schedule->kind);

        $schedule->update(['settings' => $settings]);

        $redirect = ['kind' => $schedule->kind];
        if ($schedule->hospital_id) {
            $redirect['hospital_id'] = $schedule->hospital_id;
            $redirect['surgery_type_id'] = $schedule->surgery_type_id ?? '';
            $redirect['surgery_subtype_id'] = $schedule->surgery_subtype_id ?? '';
        }

        return redirect()
            ->route('settings.times', $redirect)
            ->with('success', 'متن پیامک این روز ذخیره شد.');
    }

    public function updateCapacity(Request $request, ClinicSchedule $schedule): RedirectResponse
    {
        abort_unless($schedule->kind === 'surgery', 404);

        $validated = $request->validate([
            'capacity_limits_enabled' => ['nullable', 'boolean'],
            'capacity_limits' => ['nullable', 'string'],
        ]);

        $settings = $schedule->settings ?? [];
        $settings = SurgeryCapacityLimits::mergeIntoSettings(
            $settings,
            $request->boolean('capacity_limits_enabled'),
            SurgeryCapacityLimits::parseRequest($validated['capacity_limits'] ?? null)
        );

        $schedule->update(['settings' => $settings]);

        // همسان‌سازی محدودیت روی همه زیرگروه‌های همان روز/بیمارستان/نوع
        if ($schedule->hospital_id && $schedule->surgery_type_id) {
            $peerSettings = $settings;
            ClinicSchedule::query()
                ->where('kind', 'surgery')
                ->where('hospital_id', $schedule->hospital_id)
                ->where('surgery_type_id', $schedule->surgery_type_id)
                ->where('date_key', $schedule->date_key)
                ->where('id', '!=', $schedule->id)
                ->each(function (ClinicSchedule $peer) use ($peerSettings): void {
                    $merged = SurgeryCapacityLimits::mergeIntoSettings(
                        $peer->settings ?? [],
                        (bool) ($peerSettings['capacityLimitsEnabled'] ?? false),
                        SurgeryCapacityLimits::rulesFromSettings($peerSettings)
                    );
                    $peer->update(['settings' => $merged]);
                });
        }

        $redirect = ['kind' => $schedule->kind];
        if ($schedule->hospital_id) {
            $redirect['hospital_id'] = $schedule->hospital_id;
            $redirect['surgery_type_id'] = $schedule->surgery_type_id ?? '';
            $redirect['surgery_subtype_id'] = $schedule->surgery_subtype_id ?? '';
        }

        return redirect()
            ->route('settings.times', $redirect)
            ->with('success', 'محدودیت ظرفیت ذخیره شد.');
    }

    public function updateGroupDay(Request $request): RedirectResponse
    {
        SurgeryProgramGroup::ensureTables();

        $validated = $request->validate([
            'surgery_program_group_id' => ['required', 'integer', 'exists:surgery_program_groups,id'],
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'date_key' => ['required', 'string', 'max:20'],
            'total_slots' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        SurgeryProgramGroupDay::query()->updateOrCreate(
            [
                'surgery_program_group_id' => (int) $validated['surgery_program_group_id'],
                'hospital_id' => (int) $validated['hospital_id'],
                'date_key' => $validated['date_key'],
            ],
            ['total_slots' => (int) $validated['total_slots']]
        );

        return redirect()
            ->route('settings.times', [
                'kind' => 'surgery',
                'hospital_id' => $validated['hospital_id'],
            ])
            ->with('success', 'ظرفیت مستقل این گروه برای این روز ذخیره شد.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ClinicSchedule>  $schedules
     * @return array<int, int>
     */
    private function bookedCountsFor($schedules): array
    {
        if ($schedules->isEmpty()) {
            return [];
        }

        if ($schedules->every(fn (ClinicSchedule $schedule) => $schedule->kind === 'visit')) {
            $dateBySchedule = [];
            foreach ($schedules as $schedule) {
                try {
                    $dateBySchedule[$schedule->id] = \App\Support\Jalali::parseJalaliDate($schedule->date_key)
                        ->toDateString();
                } catch (\Throwable) {
                    $dateBySchedule[$schedule->id] = null;
                }
            }

            $dates = collect($dateBySchedule)->filter()->unique()->values();
            if ($dates->isEmpty()) {
                return array_fill_keys($schedules->pluck('id')->all(), 0);
            }

            $countsByDate = Appointment::query()
                ->selectRaw('DATE(scheduled_date) as booking_date, COUNT(*) as aggregate')
                ->whereDate('scheduled_date', '>=', $dates->min())
                ->whereDate('scheduled_date', '<=', $dates->max())
                ->holdingSlot()
                ->groupBy(DB::raw('DATE(scheduled_date)'))
                ->pluck('aggregate', 'booking_date');

            return collect($dateBySchedule)
                ->map(fn ($date) => $date ? (int) ($countsByDate[$date] ?? 0) : 0)
                ->all();
        }

        $counts = [];
        foreach ($schedules as $schedule) {
            $counts[$schedule->id] = $this->bookedCountFor($schedule);
        }

        return $counts;
    }

    private function bookedCountFor(ClinicSchedule $schedule): int
    {
        if ($schedule->kind === 'surgery') {
            return SurgeryCapacityLimits::usedCapacityUnits($schedule);
        }

        try {
            $gregorian = \App\Support\Jalali::parseJalaliDate($schedule->date_key)->toDateString();
        } catch (\Throwable $e) {
            return 0;
        }

        return Appointment::query()
            ->whereDate('scheduled_date', $gregorian)
            ->holdingSlot()
            ->count();
    }
}
