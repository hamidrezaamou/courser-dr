<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Models\SurgerySubtype;
use App\Models\SurgeryType;
use App\Support\ActivityLogger;
use App\Support\AppointmentSms;
use App\Support\BookingStatus;
use App\Support\His\HisLock;
use App\Support\ImageCompressor;
use App\Support\IranianId;
use App\Support\Jalali;
use App\Support\PatientResolver;
use App\Support\Digits;
use App\Support\SlotGuard;
use App\Support\SurgeryCapacityLimits;
use App\Support\SurgeryCooldown;
use App\Support\VisitLinker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SurgeryAppointmentController extends Controller
{
    public function register(): View
    {
        $this->ensureCooldownColumns();
        $hospitals = Hospital::query()->orderBy('name')->get(['id', 'name']);

        return view('surgery-appointments.register', compact('hospitals'));
    }

    public function cooldownCheck(Request $request): JsonResponse
    {
        $this->ensureCooldownColumns();

        $dateRaw = trim((string) $request->query('date', ''));
        $gregorian = null;
        if ($dateRaw !== '') {
            try {
                $gregorian = Jalali::parseJalaliDate($dateRaw)->toDateString();
            } catch (\Throwable) {
                try {
                    $gregorian = \Carbon\Carbon::parse($dateRaw)->toDateString();
                } catch (\Throwable) {
                    $gregorian = null;
                }
            }
        }

        $patientId = $request->integer('patient_id') ?: null;
        $exceptId = $request->integer('exclude_id') ?: null;
        $subtypeId = $request->integer('surgery_subtype_id') ?: null;
        $typeId = $request->integer('surgery_type_id') ?: null;

        return response()->json(SurgeryCooldown::inspect(
            $subtypeId,
            $request->query('eye_side'),
            $gregorian,
            $patientId,
            Digits::toEnglish(trim((string) $request->query('national_code', ''))),
            $exceptId,
            $typeId,
        ));
    }

    public function registerStore(Request $request): RedirectResponse
    {
        $validated = $this->validateSurgeryPayload($request, withPhotos: true);

        $patient = PatientResolver::resolve($validated);
        $validated = PatientResolver::syncValidatedFromPatient($patient, $validated);

        $surgery = $this->persistSurgery($request, $patient, $validated);
        $uploaded = $this->storePhotos($request, $patient, $surgery);

        $msg = 'نوبت عمل ثبت شد و در پرونده بیمار نمایش داده می‌شود.';
        if ($uploaded > 0) {
            $msg .= " ({$uploaded} تصویر به پرونده اضافه شد)";
        }

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', $msg)
            ->with('booking_success', [
                'name' => $patient->name,
                'mobile' => $patient->mobile,
                'mobileSecondary' => $surgery->mobile_secondary ?: null,
                'nationalCode' => PatientResolver::displayNationalCode($patient->national_code),
                'meta' => 'نوبت عمل',
                'patientUrl' => route('patients.show', $patient),
                'boardUrl' => route('appointments.board', ['kind' => 'surgery']),
                'printUrl' => route('surgery-appointments.prints', $surgery),
                'reportsUrl' => route('reports.index', ['kind' => 'surgery', 'q' => $patient->national_code]),
                'kind' => 'surgery',
                'closeTab' => true,
                'smsBody' => AppointmentSms::forItem($surgery->loadMissing('hospital'), 'surgery'),
                'smsEnabled' => (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir',
            ]);
    }

    public function create(Patient $patient): View
    {
        $this->ensureCooldownColumns();
        $hospitals = Hospital::query()->orderBy('name')->get(['id', 'name']);

        return view('surgery-appointments.create', compact('patient', 'hospitals'));
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $this->validateSurgeryPayload($request, withPhotos: true);

        // Booking from an open patient file must not rewrite identity fields.
        $validated = PatientResolver::syncValidatedFromPatient($patient, $validated);
        $surgery = $this->persistSurgery($request, $patient, $validated);
        $uploaded = $this->storePhotos($request, $patient, $surgery);

        $msg = 'نوبت عمل ثبت شد و در پرونده نمایش داده می‌شود.';
        if ($uploaded > 0) {
            $msg .= " ({$uploaded} تصویر به پرونده اضافه شد)";
        }

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', $msg)
            ->with('booking_success', [
                'name' => $patient->name,
                'mobile' => $patient->mobile,
                'mobileSecondary' => $surgery->mobile_secondary ?: null,
                'nationalCode' => PatientResolver::displayNationalCode($patient->national_code),
                'meta' => 'نوبت عمل',
                'patientUrl' => route('patients.show', $patient),
                'boardUrl' => route('appointments.board', ['kind' => 'surgery']),
                'printUrl' => route('surgery-appointments.prints', $surgery),
                'reportsUrl' => route('reports.index', ['kind' => 'surgery', 'q' => $patient->national_code]),
                'kind' => 'surgery',
                'closeTab' => true,
                'smsBody' => AppointmentSms::forItem($surgery->loadMissing('hospital'), 'surgery'),
                'smsEnabled' => (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir',
            ]);
    }

    public function edit(SurgeryAppointment $surgeryAppointment): View
    {
        HisLock::guard($surgeryAppointment);

        $this->ensureCooldownColumns();
        $surgeryAppointment->load(['patient', 'hospital']);

        return view('surgery-appointments.edit', [
            'surgery' => $surgeryAppointment,
            'patient' => $surgeryAppointment->patient,
            'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, SurgeryAppointment $surgeryAppointment): RedirectResponse
    {
        [$gregorian, $hospitalId] = $this->persistSurgeryUpdate($request, $surgeryAppointment);

        return redirect()
            ->route('appointments.board', [
                'date' => Jalali::format($gregorian, 'Y/m/d'),
                'kind' => 'surgery',
                'hospital_id' => $hospitalId,
            ])
            ->with('success', 'نوبت عمل به‌روزرسانی شد.');
    }

    public function updateForMobile(Request $request, SurgeryAppointment $surgeryAppointment): SurgeryAppointment
    {
        $this->persistSurgeryUpdate($request, $surgeryAppointment);

        return $surgeryAppointment->fresh(['hospital', 'surgeryType', 'surgerySubtype']) ?? $surgeryAppointment;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function persistSurgeryUpdate(Request $request, SurgeryAppointment $surgeryAppointment): array
    {
        HisLock::guard($surgeryAppointment);

        $validated = $this->validateSurgeryPayload($request, withPhotos: false);

        $gregorian = Jalali::parseJalaliDate($validated['scheduled_date'])->toDateString();
        $time = SlotGuard::normalizeTime($validated['scheduled_time']);
        $hospitalId = (int) $validated['hospital_id'];

        DB::transaction(function () use ($surgeryAppointment, $validated, $gregorian, $time, $hospitalId) {
            $isException = (bool) ($validated['is_exception'] ?? false);
            SlotGuard::assertSurgerySlotFree(
                $gregorian,
                $time,
                $hospitalId,
                $surgeryAppointment->id,
                $isException,
                ! empty($validated['surgery_type_id']) ? (int) $validated['surgery_type_id'] : null,
                ! empty($validated['surgery_subtype_id']) ? (int) $validated['surgery_subtype_id'] : null,
            );
            $this->assertSurgeryCapacity($validated, $gregorian, $time, $hospitalId, $surgeryAppointment->id, $isException);

            $old = [
                'hospital_id' => $surgeryAppointment->hospital_id,
                'scheduled_date' => optional($surgeryAppointment->scheduled_date)->toDateString(),
                'scheduled_time' => $surgeryAppointment->scheduled_time,
                'status' => $surgeryAppointment->status,
                'is_exception' => (bool) $surgeryAppointment->is_exception,
            ];

            $surgeryAppointment->update([
                'hospital_id' => $hospitalId,
                'patient_name' => $validated['patient_name'],
                'national_code' => $validated['national_code'],
                'mobile' => $validated['mobile'],
                'mobile_secondary' => $validated['mobile_secondary'] ?? null,
                'age' => $validated['age'] ?? null,
                'surgery_type' => $validated['surgery_type'],
                ...$this->surgeryTypeIdFields($validated),
                'eye_side' => \App\Support\EyeSide::normalize($validated['eye_side'] ?? null),
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'surgeon_name' => $validated['surgeon_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_exception' => $isException,
                'is_emergency' => (bool) ($validated['is_emergency'] ?? false),
                'reminder_sent_at' => null,
            ]);

            ActivityLogger::log($surgeryAppointment, 'updated', $old, [
                'hospital_id' => $hospitalId,
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'status' => $surgeryAppointment->status,
                'is_exception' => $isException,
            ]);

            if ($isException) {
                ActivityLogger::log($surgeryAppointment, 'exception_slot_used', $old, [
                    'scheduled_date' => $gregorian,
                    'scheduled_time' => $time,
                    'hospital_id' => $hospitalId,
                    'is_exception' => true,
                ]);
            }
        });

        return [$gregorian, $hospitalId];
    }

    public function updateStatus(Request $request, SurgeryAppointment $surgeryAppointment): RedirectResponse|JsonResponse
    {
        HisLock::guard($surgeryAppointment);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', BookingStatus::all())],
        ]);

        if (! BookingStatus::canTransition($surgeryAppointment->status, $validated['status'])) {
            throw ValidationException::withMessages([
                'status' => 'تغییر وضعیت مجاز نیست.',
            ]);
        }

        $from = $surgeryAppointment->status;
        $surgeryAppointment->update(['status' => $validated['status']]);

        ActivityLogger::log($surgeryAppointment, 'status_changed', [
            'status' => $from,
        ], [
            'status' => $validated['status'],
        ]);

        $linkedVisit = null;
        if (VisitLinker::shouldLinkOnStatus($validated['status'])) {
            $linkedVisit = VisitLinker::ensureForSurgery($surgeryAppointment, $request->user()?->id);
        }

        $message = 'وضعیت نوبت به «'.BookingStatus::label($validated['status']).'» تغییر کرد.';
        $redirect = null;

        if ($validated['status'] === BookingStatus::IN_CONSULT && $surgeryAppointment->patient_id) {
            $redirect = route('patients.show', [
                'patient' => $surgeryAppointment->patient_id,
                'open' => 'exam',
                'visit' => $linkedVisit?->id,
            ]);
        } elseif ($validated['status'] === BookingStatus::DONE && $surgeryAppointment->patient_id) {
            $redirect = route('patients.show', [
                'patient' => $surgeryAppointment->patient_id,
                'open' => 'postop',
                'surgery' => $surgeryAppointment->id,
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

    /**
     * @return array<string, mixed>
     */
    private function validateSurgeryPayload(Request $request, bool $withPhotos): array
    {
        $rules = [
            'patient_name' => ['required', 'string', 'max:255'],
            ...PatientResolver::nationalCodeValidationRules(),
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
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'surgery_type' => ['required', 'string', 'max:255'],
            'eye_side' => ['required', 'string', 'in:OD,OS,OU,راست,چپ,دو طرفه,دوطرفه,هر دو'],
            'scheduled_date' => ['required', 'string'],
            'scheduled_time' => ['required', 'string'],
            'surgeon_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_exception' => ['nullable', 'boolean'],
            'is_emergency' => ['nullable', 'boolean'],
        ];

        if ($withPhotos) {
            $rules['photos'] = ['nullable', 'array', 'max:20'];
            $rules['photos.*'] = ['file', 'image', 'max:10240'];
            $rules['documents'] = ['nullable', 'array', 'max:20'];
            $rules['documents.*'] = ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:15360'];
        }

        if (Schema::hasColumn('surgery_appointments', 'surgery_type_id')) {
            $rules['surgery_type_id'] = ['nullable', 'integer', 'exists:surgery_types,id'];
            $rules['surgery_subtype_id'] = ['nullable', 'integer', 'exists:surgery_subtypes,id'];
        }

        $validated = $request->validate($rules, [
            'hospital_id.required' => 'لطفاً بیمارستان را انتخاب کنید.',
            'surgery_type.required' => 'لطفاً نوع عمل را انتخاب کنید.',
            'scheduled_time.required' => 'لطفاً نوبت یا ساعت را انتخاب کنید.',
            'national_code.size' => 'کد ملی باید ۱۰ رقم باشد.',
        ]);

        $validated = PatientResolver::applyNationalCodeRules($validated);
        $validated['mobile'] = IranianId::normalizeMobile($validated['mobile']) ?? $validated['mobile'];
        if (! empty($validated['mobile_secondary'])) {
            $validated['mobile_secondary'] = IranianId::normalizeMobile($validated['mobile_secondary']);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistSurgery(Request $request, Patient $patient, array $validated): SurgeryAppointment
    {
        $gregorian = Jalali::parseJalaliDate($validated['scheduled_date'])->toDateString();
        $time = SlotGuard::normalizeTime($validated['scheduled_time']);
        $hospitalId = (int) $validated['hospital_id'];
        $isException = (bool) ($validated['is_exception'] ?? false);

        return DB::transaction(function () use ($request, $patient, $validated, $gregorian, $time, $hospitalId, $isException) {
            SlotGuard::assertSurgerySlotFree(
                $gregorian,
                $time,
                $hospitalId,
                null,
                $isException,
                ! empty($validated['surgery_type_id']) ? (int) $validated['surgery_type_id'] : null,
                ! empty($validated['surgery_subtype_id']) ? (int) $validated['surgery_subtype_id'] : null,
            );
            $this->assertSurgeryCapacity($validated, $gregorian, $time, $hospitalId, null, $isException);

            $surgery = SurgeryAppointment::create([
                'patient_id' => $patient->id,
                'hospital_id' => $hospitalId,
                'created_by' => $request->user()->id,
                'patient_name' => $validated['patient_name'],
                'national_code' => $validated['national_code'],
                'mobile' => $validated['mobile'],
                'mobile_secondary' => $validated['mobile_secondary'] ?? null,
                'age' => $validated['age'] ?? null,
                'surgery_type' => $validated['surgery_type'],
                ...$this->surgeryTypeIdFields($validated),
                'eye_side' => \App\Support\EyeSide::normalize($validated['eye_side'] ?? null),
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'surgeon_name' => $validated['surgeon_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => BookingStatus::SCHEDULED,
                'is_exception' => $isException,
                'is_emergency' => (bool) ($validated['is_emergency'] ?? false),
            ]);

            ActivityLogger::log($surgery, 'created', null, [
                'hospital_id' => $hospitalId,
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'status' => BookingStatus::SCHEDULED,
                'is_exception' => $isException,
            ]);

            if ($isException) {
                ActivityLogger::log($surgery, 'exception_slot_used', null, [
                    'scheduled_date' => $gregorian,
                    'scheduled_time' => $time,
                    'hospital_id' => $hospitalId,
                    'is_exception' => true,
                ]);
            }

            \App\Support\ModuleFinance::syncBillable($surgery);

            return $surgery;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertSurgeryCapacity(
        array $validated,
        string $gregorianDate,
        string $time,
        int $hospitalId,
        ?int $excludeAppointmentId,
        bool $isException,
    ): void {
        $typeId = (int) ($validated['surgery_type_id'] ?? 0);
        if ($typeId < 1) {
            return;
        }

        $subtypeId = ! empty($validated['surgery_subtype_id'])
            ? (int) $validated['surgery_subtype_id']
            : null;

        $schedule = SurgeryCapacityLimits::findRepresentativeSchedule(
            $hospitalId,
            $validated['scheduled_date'],
            $typeId,
            $subtypeId
        );

        if (! $schedule) {
            return;
        }

        SurgeryCapacityLimits::assertSlotBookable(
            $schedule,
            $time,
            $subtypeId,
            $excludeAppointmentId,
            $isException
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function storePhotos(Request $request, Patient $patient, SurgeryAppointment $surgery): int
    {
        $count = 0;
        $dateLabel = optional($surgery->scheduled_date)?->format('Y-m-d') ?: '';
        $typeLabel = $surgery->surgery_type ?: 'عمل';

        foreach ($request->file('photos', []) as $file) {
            if (! $file) {
                continue;
            }
            $path = ImageCompressor::storeUploaded($file, 'medical_documents');
            $patient->medicalDocuments()->create([
                'file_path' => $path,
                'type' => 'عکس عمل',
                'description' => "ثبت عمل · {$typeLabel}".($dateLabel ? " · {$dateLabel}" : ''),
                'created_by' => $request->user()->id,
            ]);
            $count++;
        }

        foreach ($request->file('documents', []) as $file) {
            if (! $file) {
                continue;
            }
            $path = ImageCompressor::storeUploaded($file, 'medical_documents');
            $patient->medicalDocuments()->create([
                'file_path' => $path,
                'type' => 'مدارک عمل',
                'description' => "ثبت عمل · {$typeLabel}".($dateLabel ? " · {$dateLabel}" : ''),
                'created_by' => $request->user()->id,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function surgeryTypeIdFields(array $validated): array
    {
        if (! Schema::hasColumn('surgery_appointments', 'surgery_type_id')) {
            return [];
        }

        return [
            'surgery_type_id' => $validated['surgery_type_id'] ?? null,
            'surgery_subtype_id' => $validated['surgery_subtype_id'] ?? null,
        ];
    }

    private function ensureCooldownColumns(): void
    {
        SurgeryType::ensureCooldownColumn();
        SurgerySubtype::ensureCooldownColumn();
    }

    public function storeSurgeryForMobile(Request $request, Patient $patient): SurgeryAppointment
    {
        $this->ensureCooldownColumns();
        $validated = $this->validateSurgeryPayload($request, withPhotos: false);
        $validated = PatientResolver::syncValidatedFromPatient($patient, $validated);

        return $this->persistSurgery($request, $patient, $validated);
    }

    public function registerSurgeryForMobile(Request $request): SurgeryAppointment
    {
        $this->ensureCooldownColumns();
        $validated = $this->validateSurgeryPayload($request, withPhotos: false);
        $patient = PatientResolver::resolve($validated);
        $validated = PatientResolver::syncValidatedFromPatient($patient, $validated);

        return $this->persistSurgery($request, $patient, $validated);
    }
}
