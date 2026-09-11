<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Models\WaitingListEntry;
use App\Support\ActivityLogger;
use App\Support\BookingStatus;
use App\Support\Jalali;
use App\Support\ModuleFinance;
use App\Support\ModuleRegistry;
use App\Support\SlotGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WaitingListController extends Controller
{
    public function index(): View
    {
        return view('modules.waiting.index', [
            'moduleSection' => ModuleRegistry::definitions()['waiting']['section'],
            'entries' => WaitingListEntry::query()
                ->with('patient')
                ->whereIn('status', ['waiting', 'contacted'])
                ->orderBy('priority')
                ->orderBy('preferred_date')
                ->get(),
            'patients' => Patient::query()->orderBy('name')->limit(100)->get(['id', 'name', 'mobile']),
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            try {
                $preferred = Jalali::parseJalaliDate($validated['preferred_date'])->toDateString();
            } catch (\Throwable) {
                return back()->withErrors(['preferred_date' => 'تاریخ نامعتبر است.'])->withInput();
            }
        }

        if (! empty($validated['patient_id'])) {
            $patient = Patient::query()->find($validated['patient_id']);
            if ($patient) {
                $validated['patient_name'] = $patient->name;
                $validated['mobile'] = $patient->mobile;
                $validated['national_code'] = $patient->national_code;
            }
        }

        WaitingListEntry::create([
            'patient_id' => $validated['patient_id'] ?? null,
            'patient_name' => $validated['patient_name'],
            'mobile' => $validated['mobile'],
            'national_code' => $validated['national_code'] ?? null,
            'kind' => $validated['kind'],
            'preferred_date' => $preferred,
            'priority' => (int) ($validated['priority'] ?? 50),
            'notes' => $validated['notes'] ?? null,
            'status' => 'waiting',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'به لیست انتظار اضافه شد.');
    }

    public function updateStatus(Request $request, WaitingListEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:waiting,contacted,converted,cancelled'],
        ]);

        $entry->update(['status' => $validated['status']]);

        return back()->with('success', 'وضعیت لیست انتظار به‌روز شد.');
    }

    public function convert(Request $request, WaitingListEntry $entry): RedirectResponse
    {
        abort_unless(in_array($entry->status, ['waiting', 'contacted'], true), 404);

        if ($entry->kind === 'surgery') {
            return redirect()
                ->route('surgery-appointments.register', $this->surgeryPrefillQuery($entry))
                ->with('info', 'فرم ثبت عمل با اطلاعات لیست انتظار پر شد.');
        }

        $appointment = $this->convertVisit($request, $entry);

        return redirect()
            ->route('appointments.edit', $appointment)
            ->with('success', 'نوبت ویزیت از لیست انتظار ساخته شد.');
    }

    /**
     * @return array<string, mixed>
     */
    public function convertForMobile(Request $request, WaitingListEntry $entry): array
    {
        abort_unless(in_array($entry->status, ['waiting', 'contacted'], true), 404);

        if ($entry->kind === 'surgery') {
            return [
                'ok' => true,
                'kind' => 'surgery',
                'created' => false,
                'patient_id' => $entry->patient_id,
                'prefill' => [
                    'patient_id' => $entry->patient_id,
                    'name' => $entry->patient_name,
                    'mobile' => $entry->mobile,
                    'national_code' => $entry->national_code,
                    'date' => $entry->preferred_date ? Jalali::format($entry->preferred_date, 'Y/m/d') : null,
                    'notes' => $entry->notes,
                    'waiting_id' => $entry->id,
                ],
                'message' => 'فرم ثبت عمل با اطلاعات لیست انتظار پر شد.',
            ];
        }

        $appointment = $this->convertVisit($request, $entry);

        return [
            'ok' => true,
            'kind' => 'visit',
            'created' => true,
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'message' => 'نوبت ویزیت از لیست انتظار ساخته شد.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function surgeryPrefillQuery(WaitingListEntry $entry): array
    {
        return array_filter([
            'patient_id' => $entry->patient_id,
            'name' => $entry->patient_name,
            'mobile' => $entry->mobile,
            'national_code' => $entry->national_code,
            'date' => $entry->preferred_date ? Jalali::format($entry->preferred_date, 'Y/m/d') : null,
        ]);
    }

    private function convertVisit(Request $request, WaitingListEntry $entry): Appointment
    {
        return DB::transaction(function () use ($request, $entry) {
            $patient = $this->resolvePatient($entry);
            $gregorian = ($entry->preferred_date ?: now())->toDateString();
            $jalaliKey = Jalali::format($gregorian, 'Y/m/d');
            $time = $this->firstFreeVisitSlot($gregorian, $jalaliKey) ?? '09:00';

            SlotGuard::assertVisitSlotFree($gregorian, $time);

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'created_by' => $request->user()->id,
                'patient_name' => $entry->patient_name,
                'national_code' => $patient->national_code,
                'mobile' => $entry->mobile,
                'visit_type' => 'ویزیت — لیست انتظار',
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'reason' => 'تبدیل از لیست انتظار',
                'notes' => $entry->notes,
                'status' => BookingStatus::SCHEDULED,
            ]);

            $entry->update([
                'status' => 'converted',
                'converted_to_type' => Appointment::class,
                'converted_to_id' => $appointment->id,
            ]);

            ActivityLogger::log($appointment, 'created', null, [
                'source' => 'waiting_list',
                'waiting_list_entry_id' => $entry->id,
            ]);

            ModuleFinance::syncBillable($appointment);

            return $appointment;
        });
    }

    private function resolvePatient(WaitingListEntry $entry): Patient
    {
        if ($entry->patient_id) {
            return Patient::query()->findOrFail($entry->patient_id);
        }

        if ($entry->national_code) {
            $existing = Patient::query()->where('national_code', $entry->national_code)->first();
            if ($existing) {
                return $existing;
            }
        }

        return Patient::create([
            'name' => $entry->patient_name,
            'mobile' => $entry->mobile,
            'national_code' => $entry->national_code ?: null,
        ]);
    }

    private function firstFreeVisitSlot(string $gregorian, string $jalaliKey): ?string
    {
        $schedule = ClinicSchedule::query()
            ->where('kind', 'visit')
            ->whereNull('hospital_id')
            ->where('date_key', $jalaliKey)
            ->first();

        if (! $schedule) {
            return null;
        }

        $booked = Appointment::query()
            ->whereDate('scheduled_date', $gregorian)
            ->holdingSlot()
            ->pluck('scheduled_time')
            ->map(fn ($t) => SlotGuard::normalizeTime((string) $t))
            ->all();

        foreach ($schedule->slotOptions() as $opt) {
            $time = SlotGuard::normalizeTime((string) $opt['value']);
            if (! in_array($time, $booked, true)) {
                return $time;
            }
        }

        return null;
    }
}
