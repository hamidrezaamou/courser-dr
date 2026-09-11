<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Support\ActivityLogger;
use App\Support\AppointmentSms;
use App\Support\BookingStatus;
use App\Support\His\HisLock;
use App\Support\IranianId;
use App\Support\Jalali;
use App\Support\PatientResolver;
use App\Support\SlotGuard;
use App\Support\VisitLinker;
use App\Support\SlotLabel;
use App\Support\SurgeryCapacityLimits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function surgeryOptions(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'string'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'surgery_type_id' => ['nullable', 'integer', 'exists:surgery_types,id'],
            'surgery_subtype_id' => ['nullable', 'integer', 'exists:surgery_subtypes,id'],
        ]);

        $dateKey = $request->filled('date') ? $request->string('date')->toString() : null;
        $hospitalId = $request->integer('hospital_id') ?: null;
        $typeId = $request->integer('surgery_type_id') ?: null;
        $subtypeId = $request->filled('surgery_subtype_id') ? $request->integer('surgery_subtype_id') : null;

        // Catalog of types/subtypes for a hospital (no date yet).
        if ($hospitalId && ! $dateKey && ! $typeId) {
            return $this->surgeryCatalogForHospital($hospitalId);
        }

        // Calendar capacity for hospital + type (+ optional subtype).
        if ($hospitalId && $typeId && ! $dateKey) {
            return $this->surgeryCalendarForHospital($hospitalId, $typeId, $subtypeId);
        }

        // Legacy: hospital only → still return calendar (all types).
        if ($hospitalId && ! $dateKey) {
            return $this->surgeryCalendarForHospital($hospitalId);
        }

        if (! $dateKey) {
            return response()->json([
                'options' => [],
                'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name']),
                'message' => 'تاریخ را انتخاب کنید.',
            ]);
        }

        $schedules = ClinicSchedule::query()
            ->with(['hospital', 'surgeryType', 'surgerySubtype'])
            ->where('kind', 'surgery')
            ->where('date_key', $dateKey)
            ->whereNotNull('hospital_id')
            ->whereNotNull('surgery_type_id')
            ->when($hospitalId, fn ($q) => $q->where('hospital_id', $hospitalId))
            ->when($typeId, fn ($q) => $q->where('surgery_type_id', $typeId))
            ->when($subtypeId !== null, fn ($q) => $q->where('surgery_subtype_id', $subtypeId))
            ->when($request->exists('surgery_subtype_id') && $subtypeId === null && $typeId, fn ($q) => $q->whereNull('surgery_subtype_id'))
            ->orderBy('hospital_id')
            ->orderBy('surgery_type_id')
            ->orderByRaw('surgery_subtype_id is null desc')
            ->orderBy('surgery_subtype_id')
            ->get();

        $options = $schedules->map(fn (ClinicSchedule $s) => [
            'schedule_id' => $s->id,
            'hospital_id' => $s->hospital_id,
            'hospital_name' => $s->hospital?->name ?? '—',
            'surgery_type_id' => $s->surgery_type_id,
            'surgery_type_name' => $s->surgeryType?->name ?? '—',
            'surgery_subtype_id' => $s->surgery_subtype_id,
            'surgery_subtype_name' => $s->surgery_subtype_id
                ? ($s->surgerySubtype?->name ?? '—')
                : 'عمومی',
            'is_general' => $s->surgery_subtype_id === null,
            'slot_mode' => $s->slotMode(),
        ])->values();

        $hospitals = $options
            ->unique('hospital_id')
            ->map(fn ($o) => ['id' => $o['hospital_id'], 'name' => $o['hospital_name']])
            ->values();

        return response()->json([
            'date' => $dateKey,
            'options' => $options,
            'hospitals' => $hospitals,
            'message' => $options->isEmpty()
                ? 'برای این تاریخ هیچ برنامهٔ عملی در تنظیمات تایم ثبت نشده است.'
                : null,
        ]);
    }

    private function surgeryCatalogForHospital(int $hospitalId): JsonResponse
    {
        $schedules = ClinicSchedule::query()
            ->with(['surgeryType', 'surgerySubtype'])
            ->where('kind', 'surgery')
            ->where('hospital_id', $hospitalId)
            ->whereNotNull('surgery_type_id')
            ->get();

        $types = [];
        $usage = SurgeryAppointment::query()
            ->selectRaw('surgery_subtype_id, COUNT(*) as usage_count')
            ->whereNotNull('surgery_subtype_id')
            ->groupBy('surgery_subtype_id')
            ->pluck('usage_count', 'surgery_subtype_id');

        foreach ($schedules->groupBy('surgery_type_id') as $typeId => $rows) {
            $first = $rows->first();
            $subtypes = [];
            $hasGeneral = false;
            foreach ($rows as $row) {
                if ($row->surgery_subtype_id === null) {
                    $hasGeneral = true;
                    continue;
                }
                $subtypes[(string) $row->surgery_subtype_id] = [
                    'id' => (string) $row->surgery_subtype_id,
                    'name' => $row->surgerySubtype?->name ?? '—',
                ];
            }
            $subtypeList = array_values($subtypes);
            usort($subtypeList, function (array $a, array $b) use ($usage): int {
                $ua = (int) ($usage[(int) $a['id']] ?? 0);
                $ub = (int) ($usage[(int) $b['id']] ?? 0);
                if ($ua !== $ub) {
                    return $ub <=> $ua;
                }

                return strcmp($a['name'], $b['name']);
            });
            $types[] = [
                'id' => (string) $typeId,
                'name' => $first?->surgeryType?->name ?? '—',
                'has_general' => $hasGeneral,
                'subtypes' => $subtypeList,
            ];
        }

        usort($types, fn ($a, $b) => strcmp($a['name'], $b['name']));

        $hospital = Hospital::query()->find($hospitalId);

        return response()->json([
            'hospital_id' => $hospitalId,
            'hospital_name' => $hospital?->name,
            'types' => $types,
            'days' => new \stdClass,
        ]);
    }

    private function surgeryCalendarForHospital(int $hospitalId, ?int $typeId = null, ?int $subtypeId = null): JsonResponse
    {
        $schedules = ClinicSchedule::query()
            ->where('kind', 'surgery')
            ->where('hospital_id', $hospitalId)
            ->whereNotNull('surgery_type_id')
            ->when($typeId, fn ($q) => $q->where('surgery_type_id', $typeId))
            ->when($subtypeId !== null, fn ($q) => $q->where('surgery_subtype_id', $subtypeId))
            ->when($typeId && $subtypeId === null && request()->exists('surgery_subtype_id'), fn ($q) => $q->whereNull('surgery_subtype_id'))
            ->get();

        $days = [];
        foreach ($schedules->groupBy('date_key') as $dateKey => $rows) {
            /** @var ClinicSchedule $representative */
            $representative = $rows->first();
            $baseTotal = SurgeryCapacityLimits::baseTotal($representative);
            if ($baseTotal < 1) {
                continue;
            }

            try {
                $gregorian = Jalali::parseJalaliDate($dateKey)->toDateString();
            } catch (\Throwable $e) {
                continue;
            }

            $remaining = SurgeryCapacityLimits::remainingCapacityUnits($representative);
            $usedUnits = SurgeryCapacityLimits::usedCapacityUnits($representative);
            $canBook = true;
            if ($typeId && $subtypeId) {
                $limitsMap = SurgeryCapacityLimits::limitsMapForDay($hospitalId, $dateKey, $typeId);
                $cost = SurgeryCapacityLimits::bookingCost($subtypeId, $limitsMap);
                $canBook = $remaining >= $cost;
            } elseif ($remaining < 1) {
                $canBook = false;
            }

            $days[$dateKey] = [
                'total' => $baseTotal,
                'booked' => $usedUnits,
                'free' => $remaining,
                'status' => $canBook ? 'available' : 'full',
            ];
        }

        $hospital = Hospital::query()->find($hospitalId);

        return response()->json([
            'hospital_id' => $hospitalId,
            'hospital_name' => $hospital?->name,
            'days' => $days,
        ]);
    }

    public function visitCalendar(): JsonResponse
    {
        $schedules = ClinicSchedule::query()
            ->where('kind', 'visit')
            ->whereNull('hospital_id')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        $days = [];
        foreach ($schedules as $schedule) {
            $total = count($schedule->slotOptions());
            if ($total < 1) {
                continue;
            }

            try {
                $gregorian = Jalali::parseJalaliDate($schedule->date_key)->toDateString();
            } catch (\Throwable $e) {
                continue;
            }

            $booked = Appointment::query()
                ->whereDate('scheduled_date', $gregorian)
                ->holdingSlot()
                ->count();

            $days[$schedule->date_key] = [
                'total' => $total,
                'booked' => $booked,
                'free' => max(0, $total - $booked),
                'status' => $booked >= $total ? 'full' : 'available',
            ];
        }

        return response()->json([
            'days' => $days,
            'message' => $days === []
                ? 'هنوز هیچ روزی برای ویزیت در تنظیمات تایم ثبت نشده است.'
                : null,
        ]);
    }

    public function register(): View
    {
        return view('appointments.register');
    }

    public function registerStore(Request $request): RedirectResponse
    {
        $validated = $this->validateVisitPayload($request);
        $patient = PatientResolver::resolve($validated);
        $validated = PatientResolver::syncValidatedFromPatient($patient, $validated);
        $appointment = $this->persistVisit($request, $patient, $validated);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'نوبت ویزیت ثبت شد.')
            ->with('booking_success', [
                'name' => $patient->name,
                'mobile' => $patient->mobile,
                'mobileSecondary' => $appointment->mobile_secondary ?: null,
                'nationalCode' => PatientResolver::displayNationalCode($patient->national_code),
                'meta' => trim(($validated['visit_type'] ?? '').' · '.($validated['scheduled_date'] ?? '').' '.($validated['scheduled_time'] ?? '')),
                'patientUrl' => route('patients.show', $patient),
                'boardUrl' => route('appointments.board'),
                'kind' => 'visit',
                'smsBody' => AppointmentSms::forItem($appointment, 'visit'),
                'smsEnabled' => (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir',
            ]);
    }

    public function create(Patient $patient): View
    {
        return view('appointments.create', compact('patient'));
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $this->validateVisitPayload($request);

        // Booking from an open patient file must not rewrite identity fields.
        $appointment = $this->persistVisit($request, $patient, PatientResolver::syncValidatedFromPatient($patient, $validated));

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'نوبت ویزیت ثبت شد و در پرونده نمایش داده می‌شود.')
            ->with('booking_success', [
                'name' => $patient->name,
                'mobile' => $patient->mobile,
                'mobileSecondary' => $appointment->mobile_secondary ?: null,
                'nationalCode' => PatientResolver::displayNationalCode($patient->national_code),
                'meta' => trim(($validated['visit_type'] ?? '').' · '.($validated['scheduled_date'] ?? '').' '.($validated['scheduled_time'] ?? '')),
                'patientUrl' => route('patients.show', $patient),
                'boardUrl' => route('appointments.board'),
                'kind' => 'visit',
                'smsBody' => AppointmentSms::forItem($appointment, 'visit'),
                'smsEnabled' => (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir',
            ]);
    }

    public function edit(Appointment $appointment): View
    {
        HisLock::guard($appointment);

        $appointment->load('patient');

        return view('appointments.edit', [
            'appointment' => $appointment,
            'patient' => $appointment->patient,
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $gregorian = $this->persistVisitUpdate($request, $appointment);

        return redirect()
            ->route('appointments.board', [
                'date' => Jalali::format($gregorian, 'Y/m/d'),
                'kind' => 'visit',
            ])
            ->with('success', 'نوبت ویزیت به‌روزرسانی شد.');
    }

    public function updateForMobile(Request $request, Appointment $appointment): Appointment
    {
        $this->persistVisitUpdate($request, $appointment);

        return $appointment->fresh() ?? $appointment;
    }

    private function persistVisitUpdate(Request $request, Appointment $appointment): string
    {
        HisLock::guard($appointment);
        Appointment::ensureMobileSecondaryColumn();

        $validated = $this->validateVisitPayload($request);

        $gregorian = Jalali::parseJalaliDate($validated['scheduled_date'])->toDateString();
        $time = SlotGuard::normalizeTime($validated['scheduled_time']);
        $this->assertVisitSchedule($validated['scheduled_date'], $time);

        DB::transaction(function () use ($appointment, $validated, $gregorian, $time) {
            SlotGuard::assertVisitSlotFree($gregorian, $time, $appointment->id);

            $old = [
                'scheduled_date' => optional($appointment->scheduled_date)->toDateString(),
                'scheduled_time' => $appointment->scheduled_time,
                'status' => $appointment->status,
            ];

            $attrs = [
                'patient_name' => $validated['patient_name'],
                'national_code' => $validated['national_code'],
                'mobile' => $validated['mobile'],
                'age' => $validated['age'] ?? null,
                'visit_type' => $validated['visit_type'] ?? null,
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'reminder_sent_at' => null,
            ];
            if (Schema::hasColumn('appointments', 'mobile_secondary')) {
                $attrs['mobile_secondary'] = $validated['mobile_secondary'] ?? null;
            }

            $appointment->update($attrs);

            // Appointment edit updates the booking snapshot only — not the patient file.

            ActivityLogger::log($appointment, 'updated', $old, [
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'status' => $appointment->status,
            ]);
        });

        return $gregorian;
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        // Status is part of what HIS pushes, so a change here would be undone
        // by the next batch rather than reaching the clinic.
        HisLock::guard($appointment);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', BookingStatus::all())],
        ]);

        if (! BookingStatus::canTransition($appointment->status, $validated['status'])) {
            throw ValidationException::withMessages([
                'status' => 'تغییر وضعیت مجاز نیست.',
            ]);
        }

        $from = $appointment->status;
        $appointment->update(['status' => $validated['status']]);

        ActivityLogger::log($appointment, 'status_changed', [
            'status' => $from,
        ], [
            'status' => $validated['status'],
        ]);

        $linkedVisit = null;
        if (VisitLinker::shouldLinkOnStatus($validated['status'])) {
            $linkedVisit = VisitLinker::ensureForAppointment($appointment, $request->user()?->id);
        }

        $message = 'وضعیت نوبت به «'.BookingStatus::label($validated['status']).'» تغییر کرد.';
        $redirect = null;

        if ($validated['status'] === BookingStatus::IN_CONSULT && $appointment->patient_id) {
            $redirect = route('patients.show', [
                'patient' => $appointment->patient_id,
                'open' => 'exam',
                'visit' => $linkedVisit?->id,
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'redirect' => $redirect,
            ]);
        }

        if ($redirect) {
            return redirect($redirect)->with('success', $message);
        }

        return back()->with('success', $message);
    }

    public function slots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'string'],
            'kind' => ['nullable', 'in:visit,surgery'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'surgery_type_id' => ['nullable', 'integer', 'exists:surgery_types,id'],
            'surgery_subtype_id' => ['nullable', 'integer', 'exists:surgery_subtypes,id'],
            'exclude_id' => ['nullable', 'integer'],
        ]);

        $gregorian = Jalali::parseJalaliDate($request->string('date')->toString());
        $kind = $request->input('kind', 'visit') === 'surgery' ? 'surgery' : 'visit';
        $date = $gregorian->toDateString();
        $dateKey = $request->string('date')->toString();
        $hospitalId = $request->integer('hospital_id') ?: null;
        $typeId = $request->integer('surgery_type_id') ?: null;
        $subtypeId = $request->integer('surgery_subtype_id') ?: null;
        $excludeId = $request->integer('exclude_id') ?: null;

        if ($kind === 'surgery') {
            \App\Models\SurgeryProgramGroup::ensureTables();
        }

        $scheduleQuery = ClinicSchedule::query()
            ->where('kind', $kind)
            ->where('date_key', $dateKey);

        if ($kind === 'surgery') {
            if (! $hospitalId) {
                return response()->json([
                    'available_times' => [],
                    'booked' => (object) [],
                    'from_schedule' => false,
                    'message' => 'ابتدا بیمارستان را انتخاب کنید.',
                ]);
            }
            if (! $typeId) {
                return response()->json([
                    'available_times' => [],
                    'booked' => (object) [],
                    'from_schedule' => false,
                    'message' => 'نوع عمل را انتخاب کنید.',
                ]);
            }
            $scheduleQuery->where('hospital_id', $hospitalId)
                ->where('surgery_type_id', $typeId);

            if ($subtypeId) {
                $scheduleQuery->where('surgery_subtype_id', $subtypeId);
            } else {
                $scheduleQuery->whereNull('surgery_subtype_id');
            }
        } else {
            $scheduleQuery->whereNull('hospital_id');
        }

        $schedule = $scheduleQuery->first();
        if (! $schedule && $kind === 'surgery' && $hospitalId && $typeId) {
            $schedule = SurgeryCapacityLimits::findRepresentativeSchedule(
                $hospitalId,
                $dateKey,
                $typeId,
                $subtypeId ?: null
            );
        }

        $slotMode = 'time';
        $capacityMeta = null;

        $allSlotOptions = [];
        $effectiveNorms = [];

        if ($schedule) {
            $slotMode = $schedule->slotMode();
            $allSlotOptions = $schedule->slotOptions();
            $effectiveOptions = $kind === 'surgery'
                ? SurgeryCapacityLimits::effectiveSlotOptions($schedule, $excludeId)
                : $allSlotOptions;
            $effectiveNorms = array_map(
                fn (array $opt) => SlotLabel::normalize($opt['value']),
                $effectiveOptions
            );
            if ($kind === 'surgery') {
                $capacityMeta = SurgeryCapacityLimits::capacityMeta(
                    $schedule,
                    $subtypeId ?: null,
                    $excludeId
                );
            }
        } elseif ($kind === 'surgery') {
            // keep empty — surgery must come from schedule
        } else {
            return response()->json([
                'available_times' => [],
                'slots' => [],
                'slot_mode' => 'time',
                'booked' => (object) [],
                'from_schedule' => false,
                'message' => 'برای این تاریخ برنامه ویزیت در تنظیمات تایم ثبت نشده است.',
            ]);
        }

        $bookedQuery = $kind === 'surgery'
            ? SurgeryAppointment::query()
                ->with(['surgerySubtype:id,name'])
                ->whereDate('scheduled_date', $date)
                ->holdingSlot()
                ->where('hospital_id', $hospitalId)
            : Appointment::query()
                ->whereDate('scheduled_date', $date)
                ->holdingSlot();

        if ($kind === 'surgery' && $typeId) {
            \App\Support\SurgeryCapacityLimits::constrainAppointmentPool(
                $bookedQuery,
                $typeId,
                $subtypeId ?: null
            );
        }

        if ($excludeId) {
            $bookedQuery->where('id', '!=', $excludeId);
        }

        $bookedColumns = $kind === 'surgery'
            ? [
                'id', 'scheduled_time', 'patient_name', 'mobile', 'national_code',
                'surgery_type', 'surgery_type_id', 'eye_side', 'status', 'is_exception', 'is_emergency', 'surgeon_name',
                'surgery_subtype_id',
            ]
            : ['scheduled_time', 'patient_name', 'mobile'];

        $booked = $bookedQuery->get($bookedColumns);

        $bookedMap = [];
        $bookedDetailsByNorm = [];

        foreach ($booked as $row) {
            $raw = (string) $row->scheduled_time;
            $norm = SlotLabel::normalize($raw);
            $key = SlotLabel::isQueue($raw) || preg_match('/^Q/i', $raw)
                ? $norm
                : (preg_match('/^\d{1,2}:\d{2}/', $raw) ? substr($raw, 0, 5) : $norm);

            $detail = $kind === 'surgery'
                ? [
                    'id' => $row->id,
                    'patient_name' => $row->patient_name,
                    'mobile' => $row->mobile,
                    'national_code' => $row->national_code,
                    'surgery_type' => $row->surgery_type,
                    'subtype_name' => $row->surgerySubtype?->name,
                    'eye_side' => $row->eye_side,
                    'status' => $row->status,
                    'status_label' => BookingStatus::label((string) $row->status),
                    'is_exception' => (bool) $row->is_exception,
                    'is_emergency' => (bool) $row->is_emergency,
                    'surgeon_name' => $row->surgeon_name,
                    'edit_url' => route('surgery-appointments.edit', $row),
                ]
                : [
                    'patient_name' => $row->patient_name,
                    'mobile' => $row->mobile,
                ];

            $aliases = $kind === 'surgery'
                ? SlotGuard::slotTimeAliases($raw)
                : [$key, $norm];

            foreach ($aliases as $alias) {
                $bookedMap[$alias] = $detail;
                $aliasNorm = SlotLabel::normalize((string) $alias);
                $bookedMap[$aliasNorm] = $detail;
                $bookedDetailsByNorm[$aliasNorm] = $detail;
            }
            $bookedMap[$key] = $detail;
            $bookedMap[$norm] = $detail;
            $bookedDetailsByNorm[$norm] = $detail;

            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $norm)) {
                $short = substr($norm, 0, 5);
                $bookedMap[$short] = $detail;
                $bookedDetailsByNorm[$short] = $detail;
            }
        }

        $slots = [];
        $availableTimes = [];
        $seenNorms = [];

        foreach ($allSlotOptions as $opt) {
            $value = $opt['value'];
            $label = $opt['label'];
            $norm = SlotLabel::normalize($value);
            $displayValue = $slotMode === 'queue'
                ? $norm
                : (preg_match('/^\d{2}:\d{2}/', $value) ? substr($value, 0, 5) : $value);
            $isBooked = isset($bookedDetailsByNorm[$norm])
                || isset($bookedDetailsByNorm[$displayValue])
                || isset($bookedMap[$value]);
            $isEffective = $effectiveNorms === [] || in_array($norm, $effectiveNorms, true);
            $booking = $bookedDetailsByNorm[$norm]
                ?? $bookedDetailsByNorm[$displayValue]
                ?? $bookedMap[$value]
                ?? null;

            $slots[] = [
                'value' => $displayValue,
                'label' => $label,
                'booked' => $isBooked,
                'bookable' => $isEffective && ! $isBooked,
                'removed' => ! $isEffective && ! $isBooked,
                'booking' => $booking,
            ];
            $seenNorms[$norm] = true;

            if ($isEffective && ! $isBooked) {
                $availableTimes[] = $displayValue;
            }
        }

        if ($kind === 'surgery') {
            $addedBookingIds = [];
            foreach ($bookedDetailsByNorm as $norm => $detail) {
                $normKey = SlotLabel::normalize($norm);
                if (isset($seenNorms[$normKey])) {
                    continue;
                }
                $bookingId = $detail['id'] ?? null;
                if ($bookingId && isset($addedBookingIds[$bookingId])) {
                    continue;
                }
                $queueNum = SlotLabel::isQueue($normKey)
                    ? (int) preg_replace('/\D/', '', $normKey)
                    : null;
                $exceptionSuffix = ($detail['is_exception'] ?? false) ? ' (استثنا)' : '';
                $extraLabel = $queueNum
                    ? ('نوبت '.$queueNum.$exceptionSuffix)
                    : ($normKey.$exceptionSuffix);

                $slots[] = [
                    'value' => $normKey,
                    'label' => $extraLabel,
                    'booked' => true,
                    'bookable' => false,
                    'exception' => (bool) ($detail['is_exception'] ?? false),
                    'booking' => $detail,
                ];
                $seenNorms[$normKey] = true;
                if ($bookingId) {
                    $addedBookingIds[$bookingId] = true;
                }
            }
        }

        $freeCount = count(array_filter($slots, fn (array $s) => ($s['bookable'] ?? false)));
        $message = null;

        if (! $schedule) {
            $message = $kind === 'surgery'
                ? 'برای این تاریخ برنامه عمل ثبت نشده است.'
                : 'برای این تاریخ برنامه ویزیت در تنظیمات تایم ثبت نشده است.';
        } elseif ($slots === []) {
            $message = $kind === 'surgery'
                ? 'برای این تاریخ برنامه عمل ثبت نشده است.'
                : 'برای این تاریخ برنامه ویزیت در تنظیمات تایم ثبت نشده است.';
        } elseif ($freeCount === 0 && $kind === 'surgery') {
            $message = 'همه نوبت‌ها پر شده‌اند. «نمایش تایم‌های پر شده» را فعال کنید یا از «نوبت استثنا» استفاده کنید.';
        } elseif ($capacityMeta && ! ($capacityMeta['subtype_allowed'] ?? true)) {
            $message = 'ظرفیت کافی برای این زیرگروه باقی نمانده. از «نوبت استثنا» استفاده کنید.';
        }

        return response()->json([
            'available_times' => $availableTimes,
            'slots' => $slots,
            'slot_mode' => $slotMode,
            'booked' => $bookedMap,
            'from_schedule' => (bool) $schedule,
            'capacity' => $capacityMeta,
            'message' => $message,
        ]);
    }

    private function assertVisitSchedule(string $dateKey, string $time): void
    {
        $schedule = ClinicSchedule::query()
            ->where('kind', 'visit')
            ->whereNull('hospital_id')
            ->where('date_key', $dateKey)
            ->first();

        if (! $schedule) {
            throw ValidationException::withMessages([
                'scheduled_date' => 'برای این تاریخ برنامه ویزیت در تنظیمات تایم ثبت نشده است.',
            ]);
        }

        $allowed = [];
        foreach ($schedule->slotOptions() as $opt) {
            $allowed[] = SlotLabel::normalize($opt['value']);
        }

        if ($allowed !== [] && ! in_array(SlotLabel::normalize($time), $allowed, true)) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'این ساعت در برنامه ویزیت این روز تعریف نشده است.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVisitPayload(Request $request): array
    {
        $validated = $request->validate(array_merge([
            'patient_name' => ['required', 'string', 'max:255'],
            'mobile' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! IranianId::isValidMobile(is_string($value) ? $value : null)) {
                        $fail('شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.');
                    }
                },
            ],
            'mobile_secondary' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    if (! IranianId::isValidMobile(is_string($value) ? $value : null)) {
                        $fail('شماره تماس دوم معتبر نیست.');
                    }
                },
            ],
            'age' => ['nullable', 'string', 'max:255'],
            'visit_type' => ['nullable', 'string', 'max:255'],
            'scheduled_date' => ['required', 'string'],
            'scheduled_time' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ], PatientResolver::nationalCodeValidationRules()), [
            'scheduled_time.required' => 'لطفاً ساعت نوبت را انتخاب کنید.',
            'national_code.size' => 'کد ملی باید ۱۰ رقم باشد.',
        ]);

        $validated = PatientResolver::applyNationalCodeRules($validated);
        $validated['mobile'] = IranianId::normalizeMobile($validated['mobile']) ?? $validated['mobile'];
        if (! empty($validated['mobile_secondary'])) {
            $validated['mobile_secondary'] = IranianId::normalizeMobile($validated['mobile_secondary']);
        } else {
            $validated['mobile_secondary'] = null;
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistVisit(Request $request, Patient $patient, array $validated): Appointment
    {
        $gregorian = Jalali::parseJalaliDate($validated['scheduled_date'])->toDateString();
        $time = SlotGuard::normalizeTime($validated['scheduled_time']);
        $this->assertVisitSchedule($validated['scheduled_date'], $time);

        Appointment::ensureMobileSecondaryColumn();

        return DB::transaction(function () use ($request, $patient, $validated, $gregorian, $time) {
            SlotGuard::assertVisitSlotFree($gregorian, $time);

            $attrs = [
                'patient_id' => $patient->id,
                'created_by' => $request->user()->id,
                'patient_name' => $validated['patient_name'],
                'national_code' => $validated['national_code'],
                'mobile' => $validated['mobile'],
                'age' => $validated['age'] ?? null,
                'visit_type' => $validated['visit_type'] ?? null,
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => BookingStatus::SCHEDULED,
            ];
            if (Schema::hasColumn('appointments', 'mobile_secondary')) {
                $attrs['mobile_secondary'] = $validated['mobile_secondary'] ?? null;
            }

            $appointment = Appointment::create($attrs);

            ActivityLogger::log($appointment, 'created', null, [
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'status' => BookingStatus::SCHEDULED,
            ]);

            \App\Support\ModuleFinance::syncBillable($appointment);

            \App\Support\VisitBookingNotifier::registered($appointment);

            return $appointment;
        });
    }

    public function storeVisitForMobile(Request $request, Patient $patient): Appointment
    {
        $validated = $this->validateVisitPayload($request);

        return $this->persistVisit(
            $request,
            $patient,
            PatientResolver::syncValidatedFromPatient($patient, $validated)
        );
    }

    public function registerVisitForMobile(Request $request): Appointment
    {
        $validated = $this->validateVisitPayload($request);
        $patient = PatientResolver::resolve($validated);
        $validated = PatientResolver::syncValidatedFromPatient($patient, $validated);

        return $this->persistVisit($request, $patient, $validated);
    }
}
