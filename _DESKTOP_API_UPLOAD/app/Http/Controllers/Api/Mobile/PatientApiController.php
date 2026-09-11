<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Digits;
use App\Support\IranianId;
use App\Support\MobilePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PatientApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rawSearch = trim($request->string('q')->toString());
        $normalized = Digits::toEnglish($rawSearch);
        $digits = Digits::only($rawSearch);

        $patients = Patient::query()
            ->active()
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
                            ->orWhere('mobile', 'like', "%{$digits}%");
                    } else {
                        $query->orWhere('national_code', 'like', "%{$normalized}%")
                            ->orWhere('mobile', 'like', "%{$normalized}%");
                    }
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'ok' => true,
            'q' => $rawSearch,
            'patients' => collect($patients->items())->map(fn (Patient $p) => MobilePayload::patientCard($p))->values(),
            'stats' => [
                'total' => Patient::query()->count(),
                'new_today' => Patient::query()->whereDate('created_at', now()->toDateString())->count(),
                'upcoming_surgery' => Patient::query()
                    ->whereHas('surgeryAppointments', function ($query) {
                        $query->holdingSlot()->whereDate('scheduled_date', '>=', now()->toDateString());
                    })
                    ->count(),
            ],
            'meta' => [
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'total' => $patients->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canEditPatient(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'national_code' => ['required', 'string', 'max:255', 'unique:patients,national_code', 'unique:users,national_code'],
            'mobile' => ['required', 'string', 'max:255', 'unique:users,mobile'],
            'age' => ['nullable', 'string', 'max:255'],
        ]);

        $national = IranianId::normalizeNationalCode($validated['national_code']);
        $mobile = IranianId::normalizeMobile($validated['mobile']);
        if ($national === null) {
            return response()->json(['ok' => false, 'message' => 'کد ملی باید ۱۰ رقم باشد.'], 422);
        }
        if ($mobile === null) {
            return response()->json(['ok' => false, 'message' => 'موبایل باید ۱۱ رقم و با ۰۹ شروع شود.'], 422);
        }

        $validated['national_code'] = $national;
        $validated['mobile'] = $mobile;

        $patient = Patient::create($validated);

        User::create([
            'name' => $validated['name'],
            'national_code' => $validated['national_code'],
            'mobile' => $validated['mobile'],
            'role' => 'patient',
            'password' => Hash::make($validated['mobile']),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'بیمار ثبت شد.',
            'patient' => MobilePayload::patientCard($patient->fresh()),
        ], 201);
    }

    public function show(Request $request, Patient $patient): JsonResponse
    {
        $user = $request->user();
        if (! $user->isStaff()) {
            abort_unless($user->national_code && $user->national_code === $patient->national_code, 403);
        }

        Patient::ensurePhotoColumn();
        $relations = [
            'visits' => fn ($q) => $q->with('creator')->latest(),
            'medicalDocuments' => fn ($q) => $q->latest(),
            'prescriptions' => fn ($q) => $q->with('items.drug')->latest(),
            'appointments' => fn ($q) => $q->orderByDesc('scheduled_date')->orderByDesc('scheduled_time'),
            'surgeryAppointments' => fn ($q) => $q->with('hospital')->orderByDesc('scheduled_date')->orderByDesc('scheduled_time'),
        ];
        if ($user->isStaff()) {
            $relations['internalNotes'] = fn ($q) => $q->with('user')->latest();
        }
        $patient->load($relations);

        ActivityLogger::logPatientView($patient, 'mobile.patients.show');

        return response()->json([
            'ok' => true,
            'patient' => MobilePayload::patientCard($patient),
            'timeline' => MobilePayload::timeline($patient, $user),
        ]);
    }

    public function update(Request $request, Patient $patient): JsonResponse
    {
        abort_unless($request->user()?->canEditPatient(), 403);
        abort_if($patient->isSecureErased(), 403);
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
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'national_code' => $nationalCodeRules,
            'mobile' => $mobileRules,
            'mobile_secondary' => ['nullable', 'string', 'max:20'],
            'age' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['national_code'] = IranianId::normalizeNationalCode($validated['national_code']) ?? Digits::only($validated['national_code']);
        $validated['mobile'] = IranianId::normalizeMobile($validated['mobile']) ?? Digits::only($validated['mobile']);
        $secondaryRaw = trim((string) ($validated['mobile_secondary'] ?? ''));
        $validated['mobile_secondary'] = $secondaryRaw === '' ? null : IranianId::normalizeMobile($secondaryRaw);

        $patient->update($validated);

        return response()->json([
            'ok' => true,
            'message' => 'اطلاعات بیمار به‌روزرسانی شد.',
            'patient' => MobilePayload::patientCard($patient->fresh()),
        ]);
    }

    public function myProfile(Request $request): JsonResponse
    {
        $patient = Patient::query()
            ->where('national_code', $request->user()->national_code)
            ->firstOrFail();

        return $this->show($request, $patient);
    }
}
