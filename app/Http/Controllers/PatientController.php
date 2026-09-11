<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\Patient;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Digits;
use App\Support\StaffNoteAlerts;
use App\Support\IranianId;
use App\Support\PatientJsonImporter;
use App\Support\PatientSecureErase;
use App\Support\ProfilePhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * Display a listing of patients, optionally filtered by search.
     */
    public function index(Request $request): View
    {
        $rawSearch = trim($request->string('search')->toString());
        $normalized = Digits::toEnglish($rawSearch);
        $digits = Digits::only($rawSearch);

        $patients = Patient::query()
            ->withCount([
                'appointments as upcoming_visits_count' => function ($query) {
                    $query->holdingSlot()->whereDate('scheduled_date', '>=', now()->toDateString());
                },
                'surgeryAppointments as upcoming_surgeries_count' => function ($query) {
                    $query->holdingSlot()->whereDate('scheduled_date', '>=', now()->toDateString());
                },
                'surgeryAppointments as surgeries_count',
            ])
            ->when($rawSearch !== '', function ($query) use ($normalized, $digits) {
                $query->where(function ($query) use ($normalized, $digits) {
                    $query->where('name', 'like', "%{$normalized}%");

                    if ($digits !== '') {
                        $query->orWhere('national_code', 'like', "%{$digits}%")
                            ->orWhere('mobile', 'like', "%{$digits}%")
                            ->orWhere('national_code', $digits)
                            ->orWhere('mobile', $digits);
                    } else {
                        $query->orWhere('national_code', 'like', "%{$normalized}%")
                            ->orWhere('mobile', 'like', "%{$normalized}%");
                    }
                });

                if ($digits !== '') {
                    $query->orderByRaw(
                        'CASE
                            WHEN national_code = ? THEN 0
                            WHEN mobile = ? THEN 1
                            WHEN national_code LIKE ? THEN 2
                            WHEN mobile LIKE ? THEN 3
                            ELSE 4
                        END',
                        [$digits, $digits, $digits.'%', $digits.'%']
                    );
                }
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Patient::query()->count(),
            'new_today' => Patient::query()->whereDate('created_at', now()->toDateString())->count(),
            'upcoming_surgery' => Patient::query()
                ->whereHas('surgeryAppointments', function ($query) {
                    $query->holdingSlot()->whereDate('scheduled_date', '>=', now()->toDateString());
                })
                ->count(),
        ];

        if ($request->ajax()) {
            return view('dashboard.partials.results-ajax', [
                'patients' => $patients,
                'search' => $rawSearch,
                'stats' => $stats,
            ]);
        }

        return view('dashboard', [
            'patients' => $patients,
            'search' => $rawSearch,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for registering a new patient.
     */
    public function create(): View
    {
        return view('patients.create');
    }

    /**
     * Real-time national-code lookup for visit/surgery registration forms.
     */
    public function lookupByNationalCode(Request $request): JsonResponse
    {
        $digits = Digits::only($request->string('national_code')->toString());
        if (strlen($digits) !== 10) {
            return response()->json(['found' => false, 'ready' => false]);
        }

        $code = IranianId::normalizeNationalCode($digits) ?? $digits;
        $patient = Patient::query()->where('national_code', $code)->first();

        if (! $patient) {
            return response()->json([
                'found' => false,
                'ready' => true,
                'national_code' => $code,
            ]);
        }

        return response()->json([
            'found' => true,
            'ready' => true,
            'national_code' => $code,
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'national_code' => $patient->national_code,
                'mobile' => $patient->mobile,
                'mobile_secondary' => $patient->mobile_secondary,
                'age' => $patient->age,
            ],
        ]);
    }

    /**
     * Store a newly registered patient.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'national_code' => ['required', 'string', 'max:255', 'unique:patients,national_code', 'unique:users,national_code'],
            'mobile' => ['required', 'string', 'max:255', 'unique:users,mobile'],
            'age' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['national_code'] = Digits::only($validated['national_code']) ?: Digits::toEnglish($validated['national_code']);
        $validated['mobile'] = Digits::only($validated['mobile']) ?: Digits::toEnglish($validated['mobile']);

        Patient::create($validated);

        User::create([
            'name' => $validated['name'],
            'national_code' => $validated['national_code'],
            'mobile' => $validated['mobile'],
            'role' => 'patient',
            'password' => Hash::make($validated['mobile']),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'بیمار با موفقیت ثبت شد.');
    }

    /**
     * Bulk import patients from JSON (admin / doctor).
     */
    public function importJson(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);

        $validated = $request->validate([
            'json_file' => [
                'required',
                'file',
                'max:51200',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof \Illuminate\Http\UploadedFile) {
                        return;
                    }
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (! in_array($ext, ['json', 'txt'], true)) {
                        $fail('فقط فایل با پسوند json یا txt مجاز است.');
                    }
                },
            ],
        ]);

        $raw = file_get_contents($request->file('json_file')->getRealPath());
        if ($raw === false || trim($raw) === '') {
            return back()->with('error', 'فایل JSON خالی است.');
        }

        try {
            $rows = PatientJsonImporter::decodePayload($raw);
        } catch (\Throwable $error) {
            return back()->with('error', $error->getMessage());
        }

        if ($rows === []) {
            return back()->with('error', 'هیچ ردیفی در فایل JSON پیدا نشد.');
        }

        $stats = PatientJsonImporter::import($rows);

        ActivityLogger::log($request->user(), 'created', null, [
            'section' => 'patient_json_import',
            'created' => $stats['created'],
            'skipped' => $stats['skipped'],
            'invalid' => $stats['invalid'],
        ]);

        $message = "واردسازی انجام شد: {$stats['created']} پرونده جدید، {$stats['skipped']} تکراری (رد شد)، {$stats['invalid']} نامعتبر.";

        if ($stats['errors'] !== []) {
            $preview = implode(' | ', array_slice($stats['errors'], 0, 5));
            if (count($stats['errors']) > 5) {
                $preview .= ' | و '.(count($stats['errors']) - 5).' خطای دیگر';
            }

            return back()
                ->with('success', $message)
                ->with('error', $preview);
        }

        return back()->with('success', $message);
    }

    /**
     * Patient management hub (settings): search, edit, delete.
     */
    public function manage(Request $request): View
    {
        abort_unless($request->user()?->canManageSettings(), 403);

        $rawSearch = trim($request->string('q')->toString());
        $normalized = Digits::toEnglish($rawSearch);
        $digits = Digits::only($rawSearch);
        $showErased = $request->boolean('show_erased');

        $patients = Patient::query()
            ->when(! $showErased, fn ($query) => $query->active())
            ->when($rawSearch !== '', function ($query) use ($normalized, $digits) {
                $query->where(function ($query) use ($normalized, $digits) {
                    $query->where('name', 'like', "%{$normalized}%");

                    if ($digits !== '') {
                        $query->orWhere('national_code', 'like', "%{$digits}%")
                            ->orWhere('mobile', 'like', "%{$digits}%");
                    } else {
                        $query->orWhere('national_code', 'like', "%{$normalized}%")
                            ->orWhere('mobile', 'like', "%{$normalized}%");
                    }
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('patients.manage', [
            'patients' => $patients,
            'q' => $rawSearch,
            'showErased' => $showErased,
        ]);
    }

    /**
     * Show the form for editing a patient.
     */
    public function edit(Request $request, Patient $patient): View
    {
        abort_unless($request->user()?->canEditPatient(), 403);
        Patient::ensureMobileSecondaryColumn();

        return view('patients.edit', [
            'patient' => $patient,
            'fromFile' => $request->boolean('from_file'),
        ]);
    }

    /**
     * Update patient identity fields.
     */
    public function update(Request $request, Patient $patient): RedirectResponse
    {
        abort_unless($request->user()?->canEditPatient(), 403);
        Patient::ensureMobileSecondaryColumn();

        $patientUserId = User::query()
            ->where('role', 'patient')
            ->where('national_code', $patient->national_code)
            ->value('id');

        $nationalCodeRules = [
            'required',
            'string',
            'max:255',
            Rule::unique('patients', 'national_code')->ignore($patient->id),
        ];
        $mobileRules = ['required', 'string', 'max:255'];

        if ($patientUserId) {
            $nationalCodeRules[] = Rule::unique('users', 'national_code')->ignore($patientUserId);
            $mobileRules[] = Rule::unique('users', 'mobile')->ignore($patientUserId);
        } else {
            $nationalCodeRules[] = Rule::unique('users', 'national_code');
            $mobileRules[] = Rule::unique('users', 'mobile');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'national_code' => $nationalCodeRules,
            'mobile' => $mobileRules,
            'mobile_secondary' => ['nullable', 'string', 'max:20'],
            'age' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['national_code'] = Digits::only($validated['national_code']) ?: Digits::toEnglish($validated['national_code']);
        $validated['mobile'] = Digits::only($validated['mobile']) ?: Digits::toEnglish($validated['mobile']);

        $secondaryRaw = trim((string) ($validated['mobile_secondary'] ?? ''));
        if ($secondaryRaw === '') {
            $validated['mobile_secondary'] = null;
        } else {
            $secondary = IranianId::normalizeMobile($secondaryRaw);
            if ($secondary === null) {
                return back()
                    ->withErrors(['mobile_secondary' => 'شماره دوم باید ۱۱ رقم و با ۰۹ شروع شود.'])
                    ->withInput();
            }
            $validated['mobile_secondary'] = $secondary;
        }

        $patient->update($validated);

        ActivityLogger::log($patient, 'updated', null, [
            'section' => 'patient_identity',
            'by' => $request->user()?->id,
        ]);

        if ($request->boolean('from_file') || ! $request->user()?->canManageSettings()) {
            return redirect()
                ->route('patients.show', $patient)
                ->with('success', 'اطلاعات بیمار به‌روزرسانی شد.');
        }

        return redirect()
            ->route('settings.patients', ['q' => $request->string('q')->toString() ?: null])
            ->with('success', 'اطلاعات بیمار به‌روزرسانی شد.');
    }

    public function updateMobileSecondary(Request $request, Patient $patient): JsonResponse
    {
        abort_unless($request->user()?->canEditPatient(), 403);
        Patient::ensureMobileSecondaryColumn();

        $validated = $request->validate([
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

        $patient->mobile_secondary = $mobile;
        $patient->save();

        return response()->json([
            'ok' => true,
            'mobile_secondary' => $mobile,
        ]);
    }

    /**
     * Display the patient's file and visits.
     */
    public function show(Patient $patient): View
    {
        Patient::ensurePhotoColumn();
        $patient->load([
            'visits' => function ($query) {
                $query->with(['creator', 'editor'])->latest();
            },
            'medicalDocuments' => function ($query) {
                $query->with('creator')->latest();
            },
            'internalNotes' => function ($query) {
                $query->with('user')->latest();
            },
            'prescriptions' => function ($query) {
                $query->with(['items.drug', 'creator', 'editor'])->latest();
            },
            'appointments' => function ($query) {
                $query->with('creator')->orderByDesc('scheduled_date')->orderByDesc('scheduled_time');
            },
            'surgeryAppointments' => function ($query) {
                $query->with(['hospital', 'creator']);
                if (\App\Support\SurgeryChecklist::isAvailable()) {
                    $query->with(['checklist.items.checker']);
                }
                $query->orderByDesc('scheduled_date')->orderByDesc('scheduled_time');
            },
            'followUpReminders' => function ($query) {
                $query->where('status', 'pending')->orderBy('due_date');
            },
        ]);

        if (\App\Support\PatientFollowUps::isAvailable()) {
            \App\Support\PatientFollowUps::ensureTables();
            try {
                app(\App\Services\PatientFollowUpService::class)->importFromReminders();
            } catch (\Throwable $e) {
                report($e);
            }
            $patient->load([
                'followUps' => function ($query) {
                    $query->with(['assignee', 'hospital', 'appointment.patient', 'surgeryType', 'surgerySubtype', 'surgeryAppointment.hospital', 'parent'])
                        ->orderByDesc('due_at');
                },
            ]);
        }

        if (\App\Support\FeatureFlags::enabled('features.consent_forms')) {
            $patient->load([
                'consents' => fn ($q) => $q->with('template')->latest(),
            ]);
        }

        ActivityLogger::logPatientView($patient, 'patients.show');

        $focusNoteId = (int) request('note', 0);
        if ($focusNoteId > 0 && auth()->user()?->isStaff()) {
            try {
                StaffNoteAlerts::markReadForNote((int) auth()->id(), $focusNoteId);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('patients.show', [
            'patient' => $patient,
            'drugsCatalog' => Drug::query()->active()->ordered()->get(),
        ]);
    }

    /**
     * Display the logged-in patient's own medical file.
     */
    public function myProfile(): View
    {
        Patient::ensurePhotoColumn();
        $patient = Patient::query()
            ->where('national_code', auth()->user()->national_code)
            ->firstOrFail();

        $patient->load([
            'visits' => function ($query) {
                $query->latest();
            },
            'medicalDocuments' => function ($query) {
                $query->latest();
            },
            'appointments' => function ($query) {
                $query->orderByDesc('scheduled_date')->orderByDesc('scheduled_time');
            },
            'surgeryAppointments' => function ($query) {
                $query->with('hospital')->orderByDesc('scheduled_date')->orderByDesc('scheduled_time');
            },
        ]);

        ActivityLogger::logPatientView($patient, 'my-profile');

        return view('patients.show', [
            'patient' => $patient,
            'drugsCatalog' => collect(),
        ]);
    }

    /**
     * Secure erase — irreversible anonymization (doctor/admin).
     */
    public function destroy(Request $request, Patient $patient): RedirectResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);

        $request->validate([
            'confirm_name' => ['required', 'string'],
        ]);

        if (trim($request->string('confirm_name')->toString()) !== $patient->name) {
            return redirect()
                ->route('settings.patients', $this->manageRedirectParams($request))
                ->withErrors(['confirm_name' => 'نام بیمار برای تأیید حذف امن مطابقت ندارد.'])
                ->withInput($request->only('confirm_name'))
                ->with('delete_patient_id', $patient->id)
                ->with('delete_patient_name', $patient->name);
        }

        $patientName = $patient->name;
        $patient->load(['medicalDocuments', 'visits', 'prescriptions']);
        PatientSecureErase::erase($patient, $request->user()->id);

        return redirect()
            ->route('settings.patients', $this->manageRedirectParams($request))
            ->with('success', 'پرونده «'.$patientName.'» با حذف امن پاکسازی شد و از لیست فعال حذف می‌شود.');
    }

    public function updatePhoto(Request $request, Patient $patient): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->canEditPatient(), 403);
        abort_if($patient->isSecureErased(), 403);

        Patient::ensurePhotoColumn();

        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:10240'],
        ], [
            'photo.required' => 'یک عکس انتخاب کنید.',
            'photo.image' => 'فقط فایل تصویری مجاز است.',
            'photo.max' => 'حجم عکس نباید بیشتر از ۱۰ مگابایت باشد.',
        ]);

        ProfilePhoto::store($patient, $request->file('photo'), 'patient-photos');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'url' => $patient->fresh()->photoUrl(),
                'message' => 'عکس پروفایل ذخیره شد.',
            ]);
        }

        return back()->with('success', 'عکس پروفایل ذخیره شد.');
    }

    public function destroyPhoto(Request $request, Patient $patient): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->canEditPatient(), 403);
        abort_if($patient->isSecureErased(), 403);

        Patient::ensurePhotoColumn();
        ProfilePhoto::destroy($patient);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'url' => null,
                'message' => 'عکس پروفایل حذف شد.',
            ]);
        }

        return back()->with('success', 'عکس پروفایل حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function manageRedirectParams(Request $request): array
    {
        return array_filter([
            'q' => $request->input('q'),
            'page' => $request->input('page'),
            'show_erased' => $request->boolean('show_erased') ? '1' : null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
