<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\Patient;
use App\Support\ActivityLogger;
use App\Support\BookingStatus;
use App\Support\FeatureFlags;
use App\Support\Jalali;
use App\Support\SiteSettings;
use App\Support\SlotGuard;
use App\Support\SlotLabel;
use App\Support\VisitBookingNotifier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicVisitBookingController extends Controller
{
    /**
     * Visit days that have a clinic schedule in the next N days.
     */
    public function days(Request $request): JsonResponse
    {
        $days = max(1, min(60, (int) $request->query('days', 14)));
        $start = Carbon::today()->startOfDay();
        $end = $start->copy()->addDays($days - 1);

        $out = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $jalaliKey = Jalali::format($date, 'Y/m/d');
            $schedule = ClinicSchedule::query()
                ->where('kind', 'visit')
                ->whereNull('hospital_id')
                ->where('date_key', $jalaliKey)
                ->first();

            if (! $schedule) {
                continue;
            }

            $slotPayload = $this->buildSlotsForDate($date->toDateString(), $jalaliKey, $schedule);
            $freeCount = collect($slotPayload['slots'])->where('booked', false)->count();

            $out[] = [
                'date' => $date->toDateString(),
                'jalali' => $jalaliKey,
                'has_schedule' => true,
                'free_count' => $freeCount,
                'total_count' => count($slotPayload['slots']),
            ];
        }

        return response()->json([
            'days' => $out,
            'clinic_label' => (string) SiteSettings::effective(
                'services.website_api.clinic_label',
                config('services.website_api.clinic_label', 'مطب شخصی')
            ),
        ]);
    }

    /**
     * Free/booked visit slots for one Gregorian or Jalali date.
     */
    public function slots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'string'],
        ]);

        [$gregorian, $jalaliKey] = $this->resolveDate($request->string('date')->toString());

        $schedule = ClinicSchedule::query()
            ->where('kind', 'visit')
            ->whereNull('hospital_id')
            ->where('date_key', $jalaliKey)
            ->first();

        if (! $schedule) {
            return response()->json([
                'date' => $gregorian,
                'jalali' => $jalaliKey,
                'from_schedule' => false,
                'slot_mode' => 'time',
                'slots' => [],
                'available_times' => [],
                'message' => 'برای این روز برنامه ویزیت مطب تنظیم نشده است.',
            ]);
        }

        $payload = $this->buildSlotsForDate($gregorian, $jalaliKey, $schedule);

        return response()->json([
            'date' => $gregorian,
            'jalali' => $jalaliKey,
            'from_schedule' => true,
            'slot_mode' => $payload['slot_mode'],
            'slots' => $payload['slots'],
            'available_times' => $payload['available_times'],
            'message' => null,
        ]);
    }

    /**
     * Create a visit appointment from the public website.
     */
    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_name' => ['required', 'string', 'max:255'],
            'national_code' => ['required', 'string', 'max:20'],
            'mobile' => ['required', 'string', 'max:20'],
            'scheduled_date' => ['required', 'string'],
            'scheduled_time' => ['required', 'string'],
            'visit_type' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        [$gregorian, $jalaliKey] = $this->resolveDate($validated['scheduled_date']);
        $time = SlotGuard::normalizeTime($validated['scheduled_time']);

        $schedule = ClinicSchedule::query()
            ->where('kind', 'visit')
            ->whereNull('hospital_id')
            ->where('date_key', $jalaliKey)
            ->first();

        if (! $schedule) {
            throw ValidationException::withMessages([
                'scheduled_date' => 'برای این روز برنامه ویزیت مطب وجود ندارد.',
            ]);
        }

        $allowed = collect($schedule->slotOptions())->pluck('value')->map(
            fn ($value) => SlotGuard::normalizeTime((string) $value)
        )->all();

        $allowedHi = array_map(function ($value) {
            return preg_match('/^\d{2}:\d{2}/', $value) ? substr($value, 0, 5) : $value;
        }, $allowed);

        if (! in_array($time, $allowed, true) && ! in_array(substr($time, 0, 5), $allowedHi, true)) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'ساعت انتخاب‌شده در برنامه مطب نیست.',
            ]);
        }

        $appointment = null;
        $needsApproval = FeatureFlags::enabled('features.online_booking_approval');
        $status = $needsApproval ? BookingStatus::PENDING_APPROVAL : BookingStatus::SCHEDULED;

        DB::transaction(function () use ($validated, $gregorian, $time, $needsApproval, $status, &$appointment) {
            $slotConflict = Appointment::query()
                ->whereDate('scheduled_date', $gregorian)
                ->where('scheduled_time', $time)
                ->where(function ($query) use ($needsApproval) {
                    $query->whereIn('status', BookingStatus::holding());
                    if ($needsApproval) {
                        $query->orWhere('status', BookingStatus::PENDING_APPROVAL);
                    }
                })
                ->exists();

            if ($slotConflict) {
                throw ValidationException::withMessages([
                    'scheduled_time' => $needsApproval
                        ? 'این ساعت قبلاً رزرو شده یا در صف تأیید است.'
                        : 'این ساعت قبلاً رزرو شده است.',
                ]);
            }

            if (! $needsApproval) {
                SlotGuard::assertVisitSlotFree($gregorian, $time);
            }

            $patient = Patient::query()
                ->where('national_code', $validated['national_code'])
                ->first();

            if ($patient) {
                $patient->update([
                    'name' => $validated['patient_name'],
                    'mobile' => $validated['mobile'],
                ]);
            } else {
                $patient = Patient::create([
                    'name' => $validated['patient_name'],
                    'national_code' => $validated['national_code'],
                    'mobile' => $validated['mobile'],
                ]);
            }

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'created_by' => null,
                'patient_name' => $validated['patient_name'],
                'national_code' => $validated['national_code'],
                'mobile' => $validated['mobile'],
                'visit_type' => $validated['visit_type'] ?? 'ویزیت از وب‌سایت',
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'reason' => 'درخواست از وب‌سایت',
                'notes' => $validated['notes'] ?? null,
                'status' => $status,
            ]);

            ActivityLogger::log($appointment, 'created', null, [
                'source' => 'website',
                'scheduled_date' => $gregorian,
                'scheduled_time' => $time,
                'status' => $status,
            ]);
        });

        if ($appointment) {
            VisitBookingNotifier::registered($appointment);
        }

        return response()->json([
            'ok' => true,
            'appointment_id' => $appointment?->id,
            'date' => $gregorian,
            'jalali' => $jalaliKey,
            'time' => $time,
            'pending_approval' => $needsApproval,
            'message' => $needsApproval
                ? 'درخواست نوبت ثبت شد و پس از تأیید منشی، پیامک اطلاع‌رسانی ارسال می‌شود.'
                : 'درخواست نوبت ویزیت با موفقیت ثبت شد.',
        ], 201);
    }

    /**
     * @return array{0:string,1:string} [gregorian Y-m-d, jalali Y/m/d]
     */
    private function resolveDate(string $raw): array
    {
        $raw = trim($raw);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $carbon = Carbon::parse($raw)->startOfDay();

            return [$carbon->toDateString(), Jalali::format($carbon, 'Y/m/d')];
        }

        $carbon = Jalali::parseJalaliDate($raw);

        return [$carbon->toDateString(), Jalali::format($carbon, 'Y/m/d')];
    }

    /**
     * @return array{slot_mode:string,slots:list<array{value:string,label:string,booked:bool}>,available_times:list<string>}
     */
    private function buildSlotsForDate(string $gregorian, string $jalaliKey, ClinicSchedule $schedule): array
    {
        $slotMode = $schedule->slotMode();
        $slotOptions = $schedule->slotOptions();

        $booked = Appointment::query()
            ->whereDate('scheduled_date', $gregorian)
            ->where(function ($query) {
                $query->whereIn('status', BookingStatus::holding())
                    ->orWhere('status', BookingStatus::PENDING_APPROVAL);
            })
            ->get(['scheduled_time']);

        $bookedMap = [];
        foreach ($booked as $row) {
            $raw = (string) $row->scheduled_time;
            $norm = SlotLabel::normalize($raw);
            $hi = preg_match('/^\d{1,2}:\d{2}/', $raw) ? substr($raw, 0, 5) : $norm;
            $bookedMap[$raw] = true;
            $bookedMap[$norm] = true;
            $bookedMap[$hi] = true;
        }

        $slots = [];
        $availableTimes = [];
        foreach ($slotOptions as $opt) {
            $value = $opt['value'];
            $label = $opt['label'];
            $norm = SlotLabel::normalize($value);
            $hi = preg_match('/^\d{2}:\d{2}/', $value) ? substr($value, 0, 5) : $value;
            $token = $slotMode === 'queue' ? $norm : $hi;
            $isBooked = isset($bookedMap[$value]) || isset($bookedMap[$norm]) || isset($bookedMap[$hi]) || isset($bookedMap[$token]);
            $slots[] = [
                'value' => $token,
                'label' => $label,
                'booked' => $isBooked,
            ];
            if (! $isBooked) {
                $availableTimes[] = $token;
            }
        }

        return [
            'slot_mode' => $slotMode,
            'slots' => $slots,
            'available_times' => $availableTimes,
        ];
    }
}
