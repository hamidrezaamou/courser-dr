<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MedicalDocumentController;
use App\Http\Controllers\Modules\WaitingListController;
use App\Http\Controllers\ReportNoteController;
use App\Http\Controllers\SurgeryPrintController;
use App\Http\Controllers\VisitController;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\BillingRecord;
use App\Models\ClinicSchedule;
use App\Models\ConsentTemplate;
use App\Models\Drug;
use App\Models\FinancialTransaction;
use App\Models\FollowUpCatalogItem;
use App\Models\FollowUpTemplate;
use App\Models\FollowUpTemplateStep;
use App\Models\HisSyncState;
use App\Models\Hospital;
use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\PatientFollowUp;
use App\Models\Prescription;
use App\Models\ReportNote;
use App\Models\ServiceTariff;
use App\Models\SurgeryAppointment;
use App\Models\SurgeryProgramGroup;
use App\Models\SurgerySubtype;
use App\Models\SurgeryType;
use App\Models\User;
use App\Models\Visit;
use App\Models\WaitingListEntry;
use App\Services\ReminderService;
use App\Support\ClinicBrand;
use App\Support\PatientSecureErase;
use App\Services\PatientFollowUpService;
use App\Support\ActivityLogger;
use App\Support\AppointmentSms;
use App\Support\BookingStatus;
use App\Support\FeatureFlags;
use App\Support\FollowUpStatus;
use App\Support\FollowUpTiming;
use App\Support\ImageCompressor;
use App\Support\Jalali;
use App\Support\MobilePayload;
use App\Support\ModuleRegistry;
use App\Support\NavQuickLinks;
use App\Support\PatientFollowUps;
use App\Support\SiteSettings;
use App\Support\SlotGuard;
use App\Support\StaffNoteAlerts;
use App\Support\SurgeryChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClinicOpsApiController extends Controller
{
    public function catalog(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'drugs' => Drug::query()->ordered()->get(['id', 'name', 'generic_name', 'dosage_form', 'default_dosage', 'default_frequency', 'default_duration', 'default_instructions', 'is_active']),
            'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name', 'address', 'phone']),
            'surgery_types' => SurgeryType::query()->ordered()->with(['subtypes' => fn ($q) => $q->ordered()])->get(),
            'features' => collect(FeatureFlags::definitions())->map(fn ($meta, $key) => [
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'hint' => $meta['hint'] ?? '',
                'enabled' => FeatureFlags::enabled($key),
            ])->values(),
            'quick_links' => NavQuickLinks::all(),
            'modules' => collect(ModuleRegistry::enabled())->map(fn ($meta, $key) => [
                'key' => $key,
                'label' => $meta['label'],
            ])->values(),
        ]);
    }

    public function uploadDocuments(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'image', 'max:10240'],
            'type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($request->file('files', []) as $file) {
            $path = ImageCompressor::storeUploaded($file, 'medical_documents');
            $patient->medicalDocuments()->create([
                'file_path' => $path,
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        }

        return response()->json(['ok' => true, 'message' => 'تصاویر پزشکی آپلود شدند.']);
    }

    public function destroyDocument(Request $request, Patient $patient, MedicalDocument $document): JsonResponse
    {
        abort_unless($document->patient_id === $patient->id, 404);
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();

        return response()->json(['ok' => true, 'message' => 'تصویر حذف شد.']);
    }

    public function storePrescription(Request $request, Patient $patient): JsonResponse
    {
        abort_unless($request->user()?->canManageClinical(), 403);
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.drug_id' => ['nullable', 'integer', 'exists:drugs,id'],
            'items.*.drug_name' => ['required', 'string', 'max:255'],
            'items.*.dosage' => ['nullable', 'string', 'max:100'],
            'items.*.frequency' => ['nullable', 'string', 'max:100'],
            'items.*.duration' => ['nullable', 'string', 'max:100'],
            'items.*.meal_timing' => ['nullable', 'string', 'in:before_meal,after_meal,with_meal,empty_stomach,anytime'],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
        ]);

        $prescription = $patient->prescriptions()->create([
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);
        foreach ($validated['items'] as $index => $item) {
            $prescription->items()->create([
                'drug_id' => $item['drug_id'] ?? null,
                'drug_name' => trim($item['drug_name']),
                'dosage' => $item['dosage'] ?? null,
                'frequency' => $item['frequency'] ?? null,
                'duration' => $item['duration'] ?? null,
                'meal_timing' => $item['meal_timing'] ?? null,
                'instructions' => $item['instructions'] ?? null,
                'sort_order' => $index,
            ]);
        }
        ActivityLogger::log($prescription, 'created', null, ['items_count' => count($validated['items'])]);

        return response()->json(['ok' => true, 'message' => 'نسخه دارویی ثبت شد.', 'id' => $prescription->id], 201);
    }

    public function storeDrawing(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageClinical(), 403);
        $request->headers->set('Accept', 'application/json');

        return app(VisitController::class)->storeDrawing($request);
    }

    public function followupAction(Request $request, PatientFollowUp $followUp): JsonResponse
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);
        $action = $request->string('action')->toString();
        $service = app(PatientFollowUpService::class);
        $userId = $request->user()->id;

        if ($action === 'start') {
            $service->start($followUp, $userId);

            return response()->json(['ok' => true, 'message' => 'پیگیری در حال انجام است.']);
        }
        if ($action === 'complete') {
            $validated = $request->validate([
                'outcome' => ['required', 'string', 'max:64'],
                'outcome_notes' => ['nullable', 'string', 'max:2000'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);
            $service->complete($followUp, $validated, $userId);

            return response()->json(['ok' => true, 'message' => 'نتیجه پیگیری ثبت شد.']);
        }
        if ($action === 'fail') {
            $validated = $request->validate([
                'status' => ['required', 'in:'.FollowUpStatus::REJECTED.','.FollowUpStatus::FAILED],
                'outcome' => ['nullable', 'string', 'max:64'],
                'outcome_notes' => ['nullable', 'string', 'max:2000'],
            ]);
            $service->fail($followUp, $validated['status'], $validated, $userId);

            return response()->json(['ok' => true, 'message' => 'وضعیت پیگیری به‌روز شد.']);
        }
        if ($action === 'cancel') {
            $service->cancel($followUp, $request->string('cancel_reason')->toString() ?: null, $userId);

            return response()->json(['ok' => true, 'message' => 'پیگیری لغو شد.']);
        }
        if ($action === 'retry') {
            $validated = $request->validate([
                'days_after' => ['nullable', 'integer', 'min:1', 'max:365'],
                'due_date' => ['nullable', 'string'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);
            $due = ! empty($validated['due_date'])
                ? Jalali::parseJalaliDate($validated['due_date'])->setTime(9, 0)
                : now()->startOfDay()->addDays((int) ($validated['days_after'] ?? 7))->setTime(9, 0);
            $service->retry($followUp, $due, $validated, $userId);

            return response()->json(['ok' => true, 'message' => 'پیگیری مجدد ساخته شد.']);
        }

        return response()->json(['ok' => false, 'message' => 'عمل نامعتبر است.'], 422);
    }

    public function storeFollowup(Request $request): JsonResponse
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'title' => ['required', 'string', 'max:190'],
            'kind' => ['nullable', 'string', 'max:64'],
            'method' => ['nullable', 'string', 'max:64'],
            'due_date' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $due = Jalali::parseJalaliDate($validated['due_date'])->setTime(9, 0);
        $item = PatientFollowUp::query()->create([
            'patient_id' => $validated['patient_id'],
            'title' => $validated['title'],
            'kind' => $validated['kind'] ?? 'custom',
            'method' => $validated['method'] ?? 'call',
            'due_at' => $due,
            'status' => FollowUpStatus::PENDING,
            'source' => 'manual',
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['ok' => true, 'message' => 'پیگیری ثبت شد.', 'id' => $item->id], 201);
    }

    public function reportNotes(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(ReportNoteController::class)->show($request);
    }

    public function storeReportNote(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(ReportNoteController::class)->store($request);
    }

    public function updateReportNote(Request $request, ReportNote $note): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(ReportNoteController::class)->update($request, $note);
    }

    public function destroyReportNote(Request $request, ReportNote $note): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(ReportNoteController::class)->destroy($request, $note);
    }

    public function toggleReportNotePrint(Request $request, ReportNote $note): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(ReportNoteController::class)->togglePrint($request, $note);
    }

    public function messages(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isStaff(), 403);
        StaffNoteAlerts::ensureTables();
        $filter = $request->string('filter')->toString() ?: 'unread';
        $query = \App\Models\StaffNoteNotification::query()
            ->with(['patient', 'actor'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id');
        if ($filter !== 'all') {
            $query->whereNull('read_at');
        }
        $items = $query->limit(80)->get()->map(fn ($row) => [
            'id' => $row->id,
            'patient_id' => $row->patient_id,
            'patient_name' => $row->patient?->name,
            'actor_name' => $row->actor?->name,
            'read' => filled($row->read_at),
            'created_at_jalali' => $row->created_at ? Jalali::format($row->created_at, 'Y/m/d H:i') : '',
        ]);

        return response()->json([
            'ok' => true,
            'unread' => StaffNoteAlerts::unreadCount($request->user()->id),
            'items' => $items,
        ]);
    }

    public function markMessagesRead(Request $request): JsonResponse
    {
        StaffNoteAlerts::markAllRead($request->user()->id);

        return response()->json(['ok' => true]);
    }

    public function storeHospital(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);
        $hospital = Hospital::create($validated);

        return response()->json(['ok' => true, 'hospital' => $hospital], 201);
    }

    public function updateHospital(Request $request, Hospital $hospital): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);
        $hospital->update($validated);

        return response()->json(['ok' => true, 'hospital' => $hospital]);
    }

    public function destroyHospital(Hospital $hospital): JsonResponse
    {
        abort_unless(request()->user()?->canAccessClinicSettings(), 403);
        try {
            $hospital->delete();
        } catch (\Throwable) {
            return response()->json(['ok' => false, 'message' => 'این بیمارستان نوبت ثبت‌شده دارد.'], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function storeDrug(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'dosage_form' => ['nullable', 'string', 'max:50'],
            'default_dosage' => ['nullable', 'string', 'max:100'],
            'default_frequency' => ['nullable', 'string', 'max:100'],
            'default_duration' => ['nullable', 'string', 'max:100'],
            'default_instructions' => ['nullable', 'string', 'max:500'],
        ]);
        $drug = Drug::create([
            ...$validated,
            'sort_order' => (int) Drug::query()->max('sort_order') + 1,
            'is_active' => true,
        ]);

        return response()->json(['ok' => true, 'drug' => $drug], 201);
    }

    public function destroyDrug(Request $request, Drug $drug): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $drug->delete();

        return response()->json(['ok' => true]);
    }

    public function updateDrug(Request $request, Drug $drug): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'dosage_form' => ['nullable', 'string', 'max:50'],
            'default_dosage' => ['nullable', 'string', 'max:100'],
            'default_frequency' => ['nullable', 'string', 'max:100'],
            'default_duration' => ['nullable', 'string', 'max:100'],
            'default_instructions' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $drug->update([
            ...$validated,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $drug->is_active,
        ]);

        return response()->json(['ok' => true, 'drug' => $drug]);
    }

    public function storeSurgeryType(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $validated = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:surgery_types,name']]);
        $type = SurgeryType::create([
            'name' => trim($validated['name']),
            'is_active' => true,
            'sort_order' => (int) SurgeryType::query()->max('sort_order') + 1,
        ]);

        return response()->json(['ok' => true, 'type' => $type], 201);
    }

    public function storeTime(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $validated = $request->validate([
            'kind' => ['required', 'in:visit,surgery'],
            'date_key' => ['required', 'string', 'max:20'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'surgery_type_id' => ['nullable', 'integer', 'exists:surgery_types,id'],
            'total_slots' => ['nullable', 'integer', 'min:1', 'max:99'],
            'times' => ['nullable', 'string'],
            'sms_text' => ['nullable', 'string', 'max:1000'],
            'slot_mode' => ['nullable', 'in:time,queue'],
        ]);
        $parts = preg_split('#[/-]#', $validated['date_key']) ?: [];
        if (count($parts) < 3) {
            return response()->json(['ok' => false, 'message' => 'تاریخ نامعتبر است.'], 422);
        }
        $count = (int) ($validated['total_slots'] ?? 10);
        $mode = $validated['slot_mode'] ?? 'queue';
        $lines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", (string) ($validated['times'] ?? '')) ?: [])));
        $times = $mode === 'time' && $lines
            ? $lines
            : \App\Support\SlotLabel::queueTokens($count);
        if ($mode === 'time' && $times) {
            $count = count($times);
        }
        $hospitalId = $validated['kind'] === 'surgery' ? ($validated['hospital_id'] ?? null) : null;
        $typeId = $validated['kind'] === 'surgery' ? ($validated['surgery_type_id'] ?? null) : null;
        $exists = ClinicSchedule::query()
            ->where('kind', $validated['kind'])
            ->where('date_key', $validated['date_key'])
            ->when($hospitalId, fn ($q) => $q->where('hospital_id', $hospitalId)->where('surgery_type_id', $typeId), fn ($q) => $q->whereNull('hospital_id'))
            ->exists();
        if ($exists) {
            return response()->json(['ok' => false, 'message' => 'این تاریخ قبلاً ثبت شده است.'], 422);
        }
        $weekday = '';
        $displayText = $validated['date_key'];
        try {
            $gregorian = Jalali::parseJalaliDate($validated['date_key']);
            $weekday = Jalali::weekdayName($gregorian);
            $displayText = Jalali::format($gregorian, 'Y/m/d');
        } catch (\Throwable) {
        }
        $schedule = ClinicSchedule::create([
            'kind' => $validated['kind'],
            'hospital_id' => $hospitalId,
            'surgery_type_id' => $typeId,
            'date_key' => $validated['date_key'],
            'display_text' => $displayText,
            'year' => (int) $parts[0],
            'month' => (int) $parts[1],
            'day' => (int) $parts[2],
            'weekday' => $weekday,
            'settings' => [
                'totalSlots' => $count,
                'slotPrefix' => $validated['kind'] === 'surgery' ? 'عمل' : 'ویزیت',
                'slotMode' => $mode,
                'times' => $times,
                'smsText' => trim((string) ($validated['sms_text'] ?? '')) ?: AppointmentSms::defaultFor($validated['kind']),
            ],
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['ok' => true, 'id' => $schedule->id], 201);
    }

    public function destroyTime(Request $request, ClinicSchedule $schedule): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $schedule->delete();

        return response()->json(['ok' => true]);
    }

    public function times(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $kind = $request->string('kind')->toString();
        if (! in_array($kind, ['visit', 'surgery'], true)) {
            $kind = 'visit';
        }

        $query = ClinicSchedule::query()
            ->with(['hospital', 'surgeryType', 'surgerySubtype'])
            ->where('kind', $kind)
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day');

        if ($kind === 'visit') {
            $query->whereNull('hospital_id');
        } else {
            if ($request->filled('hospital_id')) {
                $query->where('hospital_id', $request->integer('hospital_id'));
            }
            if ($request->filled('surgery_type_id')) {
                $query->where('surgery_type_id', $request->integer('surgery_type_id'));
            }
        }

        $items = $query->get()->map(function (ClinicSchedule $schedule) {
            $settings = $schedule->settings ?? [];
            $times = array_values($settings['times'] ?? []);

            return [
                'id' => $schedule->id,
                'kind' => $schedule->kind,
                'date_key' => $schedule->date_key,
                'display_text' => $schedule->display_text,
                'year' => $schedule->year,
                'month' => $schedule->month,
                'day' => $schedule->day,
                'weekday' => $schedule->weekday,
                'hospital_id' => $schedule->hospital_id,
                'hospital_name' => $schedule->hospital?->name,
                'surgery_type_id' => $schedule->surgery_type_id,
                'surgery_type_name' => $schedule->surgeryType?->name,
                'surgery_subtype_name' => $schedule->surgerySubtype?->name,
                'total_slots' => $settings['totalSlots'] ?? count($times),
                'times_count' => count($times),
                'sms_text' => $settings['smsText'] ?? AppointmentSms::defaultFor((string) $schedule->kind),
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'kind' => $kind,
            'default_sms' => AppointmentSms::defaultFor($kind),
            'items' => $items,
        ]);
    }

    public function updateTimeSms(Request $request, ClinicSchedule $schedule): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $validated = $request->validate([
            'sms_text' => ['nullable', 'string', 'max:1000'],
        ]);
        $settings = $schedule->settings ?? [];
        $settings['smsText'] = trim((string) ($validated['sms_text'] ?? ''))
            ?: AppointmentSms::defaultFor((string) $schedule->kind);
        $schedule->update(['settings' => $settings]);

        return response()->json([
            'ok' => true,
            'message' => 'متن پیامک این روز ذخیره شد.',
            'sms_text' => $settings['smsText'],
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $roles = [User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT];
        $users = User::query()->whereIn('role', $roles)->orderBy('name')->get(['id', 'name', 'national_code', 'mobile', 'role']);

        return response()->json(['ok' => true, 'users' => $users]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'national_code' => ['required', 'string', 'max:20', 'unique:users,national_code'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT])],
            'password' => ['required', 'string', 'min:6'],
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'national_code' => $validated['national_code'],
            'mobile' => $validated['mobile'] ?: null,
            'role' => $validated['role'],
            'password' => $validated['password'],
        ]);

        return response()->json(['ok' => true, 'user' => $user], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'national_code' => ['required', 'string', 'max:20', Rule::unique('users', 'national_code')->ignore($user->id)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT])],
            'password' => ['nullable', 'string', 'min:6'],
        ]);
        $payload = [
            'name' => $validated['name'],
            'national_code' => $validated['national_code'],
            'mobile' => $validated['mobile'] ?: null,
            'role' => $validated['role'],
        ];
        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }
        $user->update($payload);

        return response()->json(['ok' => true]);
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        abort_if($user->id === $request->user()->id, 422, 'نمی‌توانید حساب خودتان را حذف کنید.');
        $user->delete();

        return response()->json(['ok' => true]);
    }

    public function updateFeatures(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'flags' => ['required', 'array'],
            'flags.*' => ['boolean'],
        ]);
        $pairs = [];
        foreach (array_keys(FeatureFlags::definitions()) as $key) {
            if (array_key_exists($key, $validated['flags'])) {
                $pairs[$key] = (bool) $validated['flags'][$key];
            }
        }
        SiteSettings::putMany($pairs);
        SiteSettings::applyToConfig();

        return response()->json(['ok' => true, 'message' => 'قابلیت‌ها ذخیره شد.']);
    }

    public function updateQuickLinks(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'links' => ['present', 'array'],
            'links.*.title' => ['required', 'string', 'max:80'],
            'links.*.url' => ['required', 'string', 'max:500'],
        ]);
        NavQuickLinks::save(NavQuickLinks::normalize($validated['links']));

        return response()->json(['ok' => true]);
    }

    public function waiting(Request $request): JsonResponse
    {
        $entries = WaitingListEntry::query()
            ->with('patient')
            ->whereIn('status', ['waiting', 'contacted'])
            ->orderBy('priority')
            ->get()
            ->map(fn (WaitingListEntry $row) => [
                'id' => $row->id,
                'patient_id' => $row->patient_id,
                'patient_name' => $row->patient_name,
                'mobile' => $row->mobile,
                'kind' => $row->kind,
                'status' => $row->status,
                'priority' => $row->priority,
                'notes' => $row->notes,
                'national_code' => $row->national_code,
                'preferred_jalali' => $row->preferred_date ? Jalali::format($row->preferred_date, 'Y/m/d') : null,
            ]);

        return response()->json(['ok' => true, 'entries' => $entries]);
    }

    public function storeWaiting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['nullable', 'exists:patients,id'],
            'patient_name' => ['required', 'string', 'max:160'],
            'mobile' => ['required', 'string', 'max:20'],
            'national_code' => ['nullable', 'string', 'max:20'],
            'kind' => ['required', 'in:visit,surgery'],
            'preferred_date' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:999'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $preferred = null;
        if (! empty($validated['preferred_date'])) {
            $preferred = Jalali::parseJalaliDate($validated['preferred_date'])->toDateString();
        }
        WaitingListEntry::create([
            ...$validated,
            'preferred_date' => $preferred,
            'priority' => (int) ($validated['priority'] ?? 50),
            'status' => 'waiting',
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['ok' => true, 'message' => 'به لیست انتظار اضافه شد.'], 201);
    }

    public function waitingStatus(Request $request, WaitingListEntry $entry): JsonResponse
    {
        $validated = $request->validate(['status' => ['required', 'in:waiting,contacted,converted,cancelled']]);
        $entry->update($validated);

        return response()->json(['ok' => true]);
    }

    public function convertWaiting(Request $request, WaitingListEntry $entry): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return response()->json(app(WaitingListController::class)->convertForMobile($request, $entry));
    }

    public function accounting(Request $request): JsonResponse
    {
        $dateJalali = $request->string('date')->toString() ?: Jalali::format(now(), 'Y/m/d');
        try {
            $day = Jalali::parseJalaliDate($dateJalali)->toDateString();
        } catch (\Throwable) {
            $day = now()->toDateString();
            $dateJalali = Jalali::format(now(), 'Y/m/d');
        }
        $rows = FinancialTransaction::query()
            ->with('patient')
            ->whereDate('transaction_date', $day)
            ->latest()
            ->get()
            ->map(fn (FinancialTransaction $row) => [
                'id' => $row->id,
                'patient_id' => $row->patient_id,
                'patient_name' => $row->patient?->name,
                'type' => $row->type,
                'amount' => $row->amount,
                'method' => $row->method,
                'label' => $row->label,
                'notes' => $row->notes,
            ]);

        return response()->json([
            'ok' => true,
            'date' => $dateJalali,
            'income' => (int) $rows->where('type', 'payment')->sum('amount'),
            'charges' => (int) $rows->where('type', 'charge')->sum('amount'),
            'rows' => $rows->values(),
        ]);
    }

    public function storeAccounting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'type' => ['required', 'in:charge,payment'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['nullable', 'string', 'max:40'],
            'label' => ['required', 'string', 'max:160'],
            'transaction_date' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        FinancialTransaction::create([
            'patient_id' => $validated['patient_id'],
            'type' => $validated['type'],
            'amount' => (int) $validated['amount'],
            'method' => $validated['type'] === 'payment' ? ($validated['method'] ?: 'cash') : null,
            'label' => $validated['label'],
            'recorded_by' => $request->user()->id,
            'transaction_date' => Jalali::parseJalaliDate($validated['transaction_date'])->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['ok' => true, 'message' => 'تراکنش ثبت شد.'], 201);
    }

    public function approvals(): JsonResponse
    {
        $pending = Appointment::query()
            ->with('patient')
            ->where('status', BookingStatus::PENDING_APPROVAL)
            ->orderBy('scheduled_date')
            ->get()
            ->map(fn (Appointment $item) => MobilePayload::appointment($item));

        return response()->json(['ok' => true, 'pending' => $pending]);
    }

    public function approveBooking(Request $request, Appointment $appointment): JsonResponse
    {
        abort_unless($appointment->status === BookingStatus::PENDING_APPROVAL, 404);
        DB::transaction(function () use ($appointment) {
            SlotGuard::assertVisitSlotFree(
                $appointment->scheduled_date->format('Y-m-d'),
                (string) $appointment->scheduled_time,
                $appointment->id
            );
            $appointment->update(['status' => BookingStatus::SCHEDULED]);
        });

        return response()->json(['ok' => true, 'message' => 'نوبت تأیید شد.']);
    }

    public function rejectBooking(Appointment $appointment): JsonResponse
    {
        abort_unless($appointment->status === BookingStatus::PENDING_APPROVAL, 404);
        $appointment->update(['status' => BookingStatus::CANCELLED]);

        return response()->json(['ok' => true, 'message' => 'نوبت رد شد.']);
    }

    public function prints(Request $request): JsonResponse
    {
        $date = $request->string('date')->toString() ?: Jalali::format(now(), 'Y/m/d');
        try {
            $gregorian = Jalali::parseJalaliDate($date)->toDateString();
        } catch (\Throwable) {
            $gregorian = now()->toDateString();
        }
        $items = SurgeryAppointment::query()
            ->with(['patient', 'hospital'])
            ->whereDate('scheduled_date', $gregorian)
            ->orderBy('scheduled_time')
            ->get()
            ->map(fn (SurgeryAppointment $item) => MobilePayload::surgery($item));

        return response()->json([
            'ok' => true,
            'date' => $date,
            'types' => ['hospital' => 'معرفی بیمارستان', 'anesthesiologist' => 'بیهوشی', 'laboratory' => 'آزمایشگاه', 'iol_master' => 'IOL Master', 'prescription' => 'نسخه'],
            'items' => $items,
        ]);
    }

    public function rotateDocument(Request $request, Patient $patient, MedicalDocument $document): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');
        $response = app(MedicalDocumentController::class)->rotate($request, $patient, $document);

        return $response instanceof JsonResponse ? $response : response()->json(['ok' => true]);
    }

    public function storeVoice(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageClinical(), 403);
        $request->headers->set('Accept', 'application/json');

        return app(VisitController::class)->storeVoice($request);
    }

    public function updatePatientPhoto(Request $request, Patient $patient): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');
        $response = app(\App\Http\Controllers\PatientController::class)->updatePhoto($request, $patient);

        return $response instanceof JsonResponse ? $response : response()->json(['ok' => true]);
    }

    public function sendReminders(Request $request, ReminderService $reminders): JsonResponse
    {
        if (! config('reminders.enabled')) {
            return response()->json(['ok' => false, 'message' => 'یادآوری نوبت‌ها در تنظیمات غیرفعال است.'], 422);
        }
        $date = $request->filled('date')
            ? Jalali::parseJalaliDate($request->string('date')->toString())
            : now()->startOfDay()->addDays((int) config('reminders.days_ahead', 1));
        $jalali = Jalali::format($date, 'Y/m/d');
        $stats = $reminders->sendForDate($date);
        $channelNote = ($stats['sms_enabled'] ?? false)
            ? 'کانال پیامک: '.($stats['sms_driver'] ?? 'log')
            : 'پیامک خاموش است (فقط لاگ)';

        return response()->json([
            'ok' => true,
            'message' => "یادآوری {$jalali}: ".($stats['visit'] ?? 0).' ویزیت، '.($stats['surgery'] ?? 0)." عمل. ({$channelNote})",
            'stats' => $stats,
        ]);
    }

    public function toggleSms(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        SiteSettings::applyToConfig();
        $enabled = ! (bool) SiteSettings::effective('reminders.sms.enabled', false);
        SiteSettings::put('reminders.sms.enabled', $enabled);
        SiteSettings::applyToConfig();

        return response()->json([
            'ok' => true,
            'enabled' => $enabled,
            'message' => $enabled ? 'ارسال پیامک یادآوری فعال شد.' : 'ارسال پیامک یادآوری متوقف شد.',
        ]);
    }

    public function billing(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'tariffs' => ServiceTariff::query()->orderBy('kind')->orderBy('name')->get(),
            'records' => BillingRecord::query()
                ->with(['patient', 'tariff'])
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (BillingRecord $row) => [
                    'id' => $row->id,
                    'patient_id' => $row->patient_id,
                    'patient_name' => $row->patient?->name,
                    'fee_amount' => $row->fee_amount,
                    'insurance_share' => $row->insurance_share,
                    'patient_share' => $row->patient_share,
                    'settlement_status' => $row->settlement_status,
                    'tariff' => $row->tariff?->name,
                ]),
            'recent_visits' => Appointment::query()->with('patient')->orderByDesc('scheduled_date')->limit(30)->get(['id', 'patient_id', 'patient_name', 'visit_type', 'scheduled_date']),
            'recent_surgeries' => SurgeryAppointment::query()->with('patient')->orderByDesc('scheduled_date')->limit(30)->get(['id', 'patient_id', 'patient_name', 'surgery_type', 'scheduled_date']),
        ]);
    }

    public function storeTariff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'kind' => ['required', 'in:visit,surgery'],
            'amount' => ['required', 'integer', 'min:0'],
            'insurance_coverage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        ServiceTariff::create($validated + ['is_active' => true]);

        return response()->json(['ok' => true, 'message' => 'تعرفه ذخیره شد.'], 201);
    }

    public function storeBilling(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'billable_type' => ['required', 'in:visit,surgery'],
            'billable_id' => ['required', 'integer'],
            'service_tariff_id' => ['required', 'exists:service_tariffs,id'],
            'settlement_status' => ['required', 'in:open,partial,paid'],
        ]);
        $tariff = ServiceTariff::query()->findOrFail($validated['service_tariff_id']);
        $billable = $validated['billable_type'] === 'visit'
            ? Appointment::query()->findOrFail($validated['billable_id'])
            : SurgeryAppointment::query()->findOrFail($validated['billable_id']);
        BillingRecord::updateOrCreate(
            ['billable_type' => $billable::class, 'billable_id' => $billable->id],
            [
                'patient_id' => $billable->patient_id,
                'service_tariff_id' => $tariff->id,
                'fee_amount' => $tariff->amount,
                'insurance_share' => $tariff->insuranceShareAmount(),
                'patient_share' => $tariff->patientShareAmount(),
                'settlement_status' => $validated['settlement_status'],
            ]
        );

        return response()->json(['ok' => true, 'message' => 'صورتحساب ثبت شد.']);
    }

    public function consent(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'templates' => ConsentTemplate::query()->latest()->get(),
            'recent' => PatientConsent::query()->with(['patient', 'template'])->latest()->limit(30)->get()->map(fn (PatientConsent $row) => [
                'id' => $row->id,
                'patient_id' => $row->patient_id,
                'patient_name' => $row->patient?->name,
                'template' => $row->template?->title,
                'signed_by_name' => $row->signed_by_name,
                'signed_at' => $row->signed_at?->toDateTimeString(),
            ]),
        ]);
    }

    public function storeConsentTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'kind' => ['required', 'in:visit,surgery'],
            'body' => ['required', 'string', 'max:8000'],
        ]);
        ConsentTemplate::create($validated + ['is_active' => true]);

        return response()->json(['ok' => true, 'message' => 'قالب رضایت‌نامه ذخیره شد.'], 201);
    }

    public function storeConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'consent_template_id' => ['required', 'exists:consent_templates,id'],
            'signed_by_name' => ['required', 'string', 'max:160'],
        ]);
        PatientConsent::create([
            'patient_id' => $validated['patient_id'],
            'consent_template_id' => $validated['consent_template_id'],
            'signed_at' => now(),
            'signed_by_name' => $validated['signed_by_name'],
            'recorded_by' => $request->user()->id,
        ]);

        return response()->json(['ok' => true, 'message' => 'رضایت‌نامه در پرونده ثبت شد.'], 201);
    }

    public function portal(): JsonResponse
    {
        $portalCodes = User::query()->where('role', User::ROLE_PATIENT)->whereNotNull('national_code')->pluck('national_code');

        return response()->json([
            'ok' => true,
            'portal_patients' => Patient::query()->whereIn('national_code', $portalCodes)->count(),
            'total_patients' => Patient::query()->count(),
        ]);
    }

    public function quality(): JsonResponse
    {
        $from = now()->startOfMonth();
        $to = now()->endOfDay();
        $visits = Appointment::query()->whereBetween('scheduled_date', [$from, $to])->get();
        $surgeries = SurgeryAppointment::query()->whereBetween('scheduled_date', [$from, $to])->get();
        $all = $visits->count() + $surgeries->count();
        $noShow = $visits->where('status', BookingStatus::NO_SHOW)->count() + $surgeries->where('status', BookingStatus::NO_SHOW)->count();
        $done = $visits->where('status', BookingStatus::DONE)->count() + $surgeries->where('status', BookingStatus::DONE)->count();
        $cancelled = $visits->where('status', BookingStatus::CANCELLED)->count() + $surgeries->where('status', BookingStatus::CANCELLED)->count();

        return response()->json([
            'ok' => true,
            'month' => Jalali::format($from, 'Y/m'),
            'stats' => [
                'total' => $all,
                'done' => $done,
                'no_show' => $noShow,
                'cancelled' => $cancelled,
                'no_show_rate' => $all > 0 ? round($noShow / $all * 100, 1) : 0,
                'done_rate' => $all > 0 ? round($done / $all * 100, 1) : 0,
            ],
        ]);
    }

    public function eyeChart(Request $request): JsonResponse
    {
        $patientFilter = $request->integer('patient') ?: null;
        $query = Visit::query()
            ->with('patient')
            ->where(fn ($q) => $q->whereNotNull('va_right')->orWhereNotNull('va_left'))
            ->latest();
        if ($patientFilter) {
            $query->where('patient_id', $patientFilter);
        }

        return response()->json([
            'ok' => true,
            'visits' => $query->limit(40)->get()->map(fn (Visit $visit) => [
                'id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'patient_name' => $visit->patient?->name,
                'va_right' => $visit->va_right,
                'va_left' => $visit->va_left,
                'iop_right' => $visit->iop_right,
                'iop_left' => $visit->iop_left,
                'created_at_jalali' => $visit->created_at ? Jalali::format($visit->created_at, 'Y/m/d H:i') : null,
            ]),
        ]);
    }

    public function rxPrints(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'prescriptions' => Prescription::query()->with(['patient', 'items'])->latest()->limit(40)->get()->map(fn (Prescription $rx) => [
                'id' => $rx->id,
                'patient_id' => $rx->patient_id,
                'patient_name' => $rx->patient?->name,
                'created_at_jalali' => $rx->created_at ? Jalali::format($rx->created_at, 'Y/m/d H:i') : null,
                'items' => $rx->items->map(fn ($item) => [
                    'drug_name' => $item->drug_name,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                ]),
            ]),
        ]);
    }

    public function printSheet(Request $request, SurgeryAppointment $surgeryAppointment): JsonResponse
    {
        $type = $request->string('type')->toString() ?: 'hospital';
        $view = app(SurgeryPrintController::class)->show($surgeryAppointment, $type);

        return response()->json([
            'ok' => true,
            'title' => $type,
            'html' => $view->render(),
        ]);
    }

    public function printHub(SurgeryAppointment $surgeryAppointment): JsonResponse
    {
        $surgeryAppointment->loadMissing(['hospital', 'patient', 'surgeryType', 'surgerySubtype', 'checklist.items']);
        $warning = null;
        if (SurgeryChecklist::isAvailable()) {
            $checklist = $surgeryAppointment->checklist;
            if ($checklist) {
                $unchecked = $checklist->items->whereNull('checked_at')->count();
                if ($unchecked > 0) {
                    $warning = "هنوز {$unchecked} مورد از چک‌لیست عمل تیک نخورده است.";
                }
            }
        }

        return response()->json([
            'ok' => true,
            'surgery' => MobilePayload::surgery($surgeryAppointment),
            'types' => [
                'hospital' => 'معرفی بیمارستان',
                'anesthesiologist' => 'بیهوشی',
                'laboratory' => 'آزمایشگاه',
                'iol_master' => 'IOL Master',
                'prescription' => 'نسخه',
            ],
            'checklist_warning' => $warning,
        ]);
    }

    public function printAll(SurgeryAppointment $surgeryAppointment): JsonResponse
    {
        $view = app(SurgeryPrintController::class)->printAll($surgeryAppointment);

        return response()->json([
            'ok' => true,
            'title' => 'چاپ همه برگه‌ها',
            'html' => $view->render(),
        ]);
    }

    public function printRx(Prescription $prescription): JsonResponse
    {
        $view = app(\App\Http\Controllers\Modules\StructuredPrescriptionController::class)->print($prescription);

        return response()->json(['ok' => true, 'html' => $view->render()]);
    }

    public function contacts(): JsonResponse
    {
        $request = request();
        $request->headers->set('Accept', 'application/json');

        return app(\App\Http\Controllers\ContactExportController::class)->json($request);
    }

    public function contactsVcf(Request $request): \Illuminate\Http\Response
    {
        $request->headers->set('Accept', '*/*');

        return app(\App\Http\Controllers\ContactExportController::class)->vcf($request);
    }

    public function managePatients(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $q = trim($request->string('q')->toString());
        $patients = Patient::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('national_code', 'like', "%{$q}%")
                        ->orWhere('mobile', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->limit(40)
            ->get(['id', 'name', 'national_code', 'mobile']);

        return response()->json(['ok' => true, 'patients' => $patients]);
    }

    public function secureErase(Request $request, Patient $patient): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $request->validate(['confirm_name' => ['required', 'string']]);
        if (trim($request->string('confirm_name')->toString()) !== $patient->name) {
            return response()->json(['ok' => false, 'message' => 'نام بیمار برای تأیید حذف امن مطابقت ندارد.'], 422);
        }
        $name = $patient->name;
        $patient->load(['medicalDocuments', 'visits', 'prescriptions']);
        PatientSecureErase::erase($patient, $request->user()->id);

        return response()->json(['ok' => true, 'message' => 'پرونده «'.$name.'» با حذف امن پاکسازی شد.']);
    }

    public function programGroups(): JsonResponse
    {
        SurgeryProgramGroup::ensureTables();

        return response()->json([
            'ok' => true,
            'groups' => SurgeryProgramGroup::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'is_active', 'sort_order']),
        ]);
    }

    public function storeProgramGroup(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        SurgeryProgramGroup::ensureTables();
        $validated = $request->validate(['name' => ['required', 'string', 'max:120']]);
        SurgeryProgramGroup::create([
            'name' => $validated['name'],
            'is_active' => true,
            'sort_order' => (int) SurgeryProgramGroup::query()->max('sort_order') + 10,
        ]);

        return response()->json(['ok' => true], 201);
    }

    public function followupSettings(): JsonResponse
    {
        abort_unless(\App\Support\PatientFollowUps::isAvailable(), 404);
        \App\Support\PatientFollowUps::ensureTables();

        $templates = FollowUpTemplate::query()
            ->with(['steps.assignee', 'hospital', 'surgeryType', 'surgerySubtype'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (FollowUpTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'applies_to' => $template->applies_to,
                'is_active' => (bool) $template->is_active,
                'hospital_id' => $template->hospital_id,
                'surgery_type_id' => $template->surgery_type_id,
                'surgery_subtype_id' => $template->surgery_subtype_id,
                'binding_label' => $template->bindingLabel(),
                'steps' => $template->steps->map(fn ($step) => [
                    'id' => $step->id,
                    'title' => $step->title,
                    'kind' => $step->kind,
                    'method' => $step->method,
                    'offset_amount' => $step->offset_amount,
                    'offset_unit' => $step->offset_unit,
                    'offset_direction' => $step->offset_direction,
                    'reference_event' => $step->reference_event,
                    'assigned_user_id' => $step->assigned_user_id,
                    'sort_order' => $step->sort_order,
                    'timing_label' => $step->timingLabel(),
                ])->values(),
            ]);

        $catalog = fn (string $group) => FollowUpCatalogItem::query()->forGroup($group)->ordered()->get(['id', 'slug', 'label', 'sort_order', 'is_active']);

        return response()->json([
            'ok' => true,
            'can_manage' => (bool) request()->user()?->canManageSettings(),
            'templates' => $templates,
            'kinds' => $catalog(FollowUpCatalogItem::GROUP_KIND),
            'methods' => $catalog(FollowUpCatalogItem::GROUP_METHOD),
            'outcomes' => $catalog(FollowUpCatalogItem::GROUP_OUTCOME),
            'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name']),
            'surgery_types' => SurgeryType::query()->ordered()->with(['subtypes' => fn ($q) => $q->ordered()])->get()->map(fn (SurgeryType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'subtypes' => $type->subtypes->map(fn ($sub) => ['id' => $sub->id, 'name' => $sub->name])->values(),
            ]),
            'staff' => User::query()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT])->orderBy('name')->get(['id', 'name']),
            'units' => collect(FollowUpTiming::units())->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label])->values(),
            'directions' => collect(FollowUpTiming::directions())->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label])->values(),
            'reference_events' => collect(FollowUpTiming::referenceEvents())->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label])->values(),
        ]);
    }

    public function storeFollowupCatalog(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'group' => ['required', 'in:kind,method,outcome'],
            'slug' => ['required', 'string', 'max:64'],
            'label' => ['required', 'string', 'max:120'],
        ]);
        FollowUpCatalogItem::query()->firstOrCreate(
            ['group' => $validated['group'], 'slug' => $validated['slug']],
            [
                'label' => $validated['label'],
                'sort_order' => (int) FollowUpCatalogItem::query()->where('group', $validated['group'])->max('sort_order') + 10,
                'is_active' => true,
            ]
        );

        return response()->json(['ok' => true], 201);
    }

    public function updateFollowupCatalog(Request $request, FollowUpCatalogItem $catalogItem): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $catalogItem->update([
            'label' => $validated['label'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? $catalogItem->sort_order,
        ]);

        return response()->json(['ok' => true]);
    }

    public function storeFollowupTemplate(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'applies_to' => ['required', 'in:surgery,visit'],
            'description' => ['nullable', 'string', 'max:2000'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'surgery_type_id' => ['nullable', 'integer', 'exists:surgery_types,id'],
            'surgery_subtype_id' => ['nullable', 'integer', 'exists:surgery_subtypes,id'],
        ]);
        if ($validated['applies_to'] === FollowUpTemplate::APPLIES_SURGERY && empty($validated['surgery_type_id'])) {
            return response()->json(['ok' => false, 'message' => 'برای الگوی عمل، نوع عمل را انتخاب کنید.'], 422);
        }
        $subtypeId = $validated['surgery_subtype_id'] ?? null;
        $typeId = $validated['surgery_type_id'] ?? null;
        if ($subtypeId) {
            $subtype = SurgerySubtype::query()->findOrFail($subtypeId);
            if ((int) $subtype->surgery_type_id !== (int) $typeId) {
                return response()->json(['ok' => false, 'message' => 'زیرگروه با نوع عمل هم‌خوان نیست.'], 422);
            }
        }
        if ($validated['applies_to'] === FollowUpTemplate::APPLIES_VISIT) {
            $validated['hospital_id'] = null;
            $typeId = null;
            $subtypeId = null;
        }
        FollowUpTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'applies_to' => $validated['applies_to'],
            'hospital_id' => $validated['hospital_id'] ?? null,
            'surgery_type_id' => $typeId,
            'surgery_subtype_id' => $subtypeId,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['ok' => true], 201);
    }

    public function updateFollowupTemplate(Request $request, FollowUpTemplate $template): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'is_active' => ['nullable', 'boolean'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
        ]);
        $template->update([
            'name' => $validated['name'],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $template->is_active,
            'hospital_id' => $template->applies_to === FollowUpTemplate::APPLIES_VISIT
                ? null
                : ($validated['hospital_id'] ?? $template->hospital_id),
        ]);

        return response()->json(['ok' => true, 'message' => 'الگو ذخیره شد.']);
    }

    public function destroyFollowupTemplate(Request $request, FollowUpTemplate $template): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $removed = 0;
        DB::transaction(function () use ($template, &$removed) {
            $removed = app(PatientFollowUpService::class)->purgeTemplateFollowUps($template);
            $template->steps()->delete();
            $template->delete();
        });

        return response()->json(['ok' => true, 'message' => 'الگو حذف شد.'.($removed ? " {$removed} پیگیری هم پاک شد." : '')]);
    }

    public function storeFollowupStep(Request $request, FollowUpTemplate $template): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $this->validateFollowupStep($request);
        FollowUpTemplateStep::create([
            'template_id' => $template->id,
            'title' => $validated['title'],
            'kind' => $validated['kind'],
            'method' => $validated['method'],
            'offset_amount' => $validated['offset_amount'],
            'offset_unit' => $validated['offset_unit'],
            'offset_direction' => $validated['offset_direction'],
            'reference_event' => $validated['reference_event'],
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'sort_order' => (int) $template->steps()->max('sort_order') + 10,
        ]);
        $applied = app(PatientFollowUpService::class)->applyTemplateToExisting($template->fresh(['steps', 'surgeryType']));

        return response()->json(['ok' => true, 'message' => 'مرحله اضافه شد.'.($applied ? ' برای '.$applied.' نوبت موجود هم پیگیری ساخته شد.' : '')], 201);
    }

    public function updateFollowupStep(Request $request, FollowUpTemplateStep $step): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $this->validateFollowupStep($request);
        $step->update([
            'title' => $validated['title'],
            'kind' => $validated['kind'],
            'method' => $validated['method'],
            'offset_amount' => $validated['offset_amount'],
            'offset_unit' => $validated['offset_unit'],
            'offset_direction' => $validated['offset_direction'],
            'reference_event' => $validated['reference_event'],
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $step->sort_order,
        ]);

        return response()->json(['ok' => true, 'message' => 'مرحله ذخیره شد.']);
    }

    public function destroyFollowupStep(Request $request, FollowUpTemplateStep $step): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $removed = 0;
        DB::transaction(function () use ($step, &$removed) {
            $removed = app(PatientFollowUpService::class)->purgeStepFollowUps($step);
            $step->delete();
        });

        return response()->json(['ok' => true, 'message' => 'مرحله حذف شد.'.($removed ? " {$removed} پیگیری مربوط هم پاک شد." : '')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFollowupStep(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'kind' => ['required', 'string', 'max:64'],
            'method' => ['required', 'string', 'max:64'],
            'offset_amount' => ['required', 'integer', 'min:0', 'max:3650'],
            'offset_unit' => ['required', 'in:minute,hour,day,week,month'],
            'offset_direction' => ['required', 'in:before,after'],
            'reference_event' => ['required', 'in:surgery_date,appointment_date,visit_date,patient_created,custom'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    public function activityLogs(): JsonResponse
    {
        abort_unless(request()->user()?->canManageSettings(), 403);
        $logs = ActivityLog::query()->with('user')->latest()->limit(50)->get()->map(fn (ActivityLog $log) => [
            'id' => $log->id,
            'action' => $log->action,
            'user' => $log->user?->name,
            'subject' => class_basename((string) $log->subject_type),
            'subject_id' => $log->subject_id,
            'jalali' => $log->created_at ? Jalali::format($log->created_at, 'Y/m/d H:i') : null,
        ]);

        return response()->json(['ok' => true, 'logs' => $logs]);
    }

    public function communications(): JsonResponse
    {
        abort_unless(request()->user()?->canManageSettings(), 403);

        return response()->json([
            'ok' => true,
            'reminders_enabled' => (bool) SiteSettings::effective('reminders.enabled', true),
            'sms_enabled' => (bool) SiteSettings::effective('reminders.sms.enabled', false),
            'days_ahead' => (int) SiteSettings::effective('reminders.days_ahead', 1),
            'send_time' => (string) SiteSettings::effective('reminders.send_time', '09:00'),
            'visit_sms_on_booking' => (bool) SiteSettings::effective('reminders.sms.visit_on_booking', false),
        ]);
    }

    public function updateCommunications(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'reminders_enabled' => ['nullable', 'boolean'],
            'sms_enabled' => ['nullable', 'boolean'],
            'days_ahead' => ['required', 'integer', 'min:0', 'max:14'],
            'send_time' => ['required', 'date_format:H:i'],
            'visit_sms_on_booking' => ['nullable', 'boolean'],
        ]);
        SiteSettings::putMany([
            'reminders.enabled' => $request->boolean('reminders_enabled'),
            'reminders.sms.enabled' => $request->boolean('sms_enabled'),
            'reminders.days_ahead' => (int) $validated['days_ahead'],
            'reminders.send_time' => $validated['send_time'],
            'reminders.sms.visit_on_booking' => $request->boolean('visit_sms_on_booking'),
        ]);
        SiteSettings::applyToConfig();

        return response()->json(['ok' => true, 'message' => 'تنظیمات پیام ذخیره شد.']);
    }

    public function brand(): JsonResponse
    {
        abort_unless(request()->user()?->canManageSettings(), 403);

        return response()->json([
            'ok' => true,
            'doctor_name' => ClinicBrand::doctorName(),
            'phone' => ClinicBrand::phone(),
        ]);
    }

    public function updateBrand(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'doctor_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        SiteSettings::putMany([
            'clinic.doctor_name' => $validated['doctor_name'],
            'clinic.phone' => $validated['phone'] ?? '',
        ]);
        SiteSettings::applyToConfig();

        return response()->json(['ok' => true, 'message' => 'برند ذخیره شد.']);
    }

    public function hisMonitor(): JsonResponse
    {
        abort_unless(request()->user()?->canManageSettings(), 403);
        try {
            $rows = HisSyncState::query()->get()->map(fn (HisSyncState $state) => [
                'resource' => $state->resource,
                'imported' => (int) $state->rows_imported,
                'updated_at' => optional($state->updated_at)?->toDateTimeString(),
            ]);
        } catch (\Throwable) {
            $rows = [];
        }

        return response()->json(['ok' => true, 'rows' => $rows]);
    }

    public function destroySurgeryType(Request $request, SurgeryType $surgeryType): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        try {
            $surgeryType->delete();
        } catch (\Throwable) {
            return response()->json(['ok' => false, 'message' => 'این نوع عمل نوبت ثبت‌شده دارد.'], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function storeSubtype(Request $request, SurgeryType $surgeryType): JsonResponse
    {
        abort_unless($request->user()?->canAccessClinicSettings(), 403);
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $sub = $surgeryType->subtypes()->create([
            'name' => $validated['name'],
            'is_active' => true,
            'sort_order' => (int) $surgeryType->subtypes()->max('sort_order') + 1,
        ]);

        return response()->json(['ok' => true, 'subtype' => $sub], 201);
    }
}
