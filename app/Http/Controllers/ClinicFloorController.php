<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\SurgeryAppointment;
use App\Support\BookingStatus;
use App\Support\FeatureFlags;
use App\Support\Jalali;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ClinicFloorController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(FeatureFlags::enabled('features.clinic_floor'), 404);

        $payload = $this->floorPayload($request);

        return view('clinic.floor', $payload);
    }

    public function live(Request $request): JsonResponse
    {
        abort_unless(FeatureFlags::enabled('features.clinic_floor'), 404);

        $payload = $this->floorPayload($request);

        return response()->json([
            'version' => $payload['version'],
            'html' => view('clinic.partials.floor-board', $payload)->render(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function floorPayload(Request $request): array
    {
        $todayJalali = Jalali::format(now(), 'Y/m/d');
        $dateJalali = $request->string('date')->toString() ?: $todayJalali;
        $kindFilter = $request->string('kind')->toString();
        if (! in_array($kindFilter, ['all', 'visit', 'surgery'], true)) {
            $kindFilter = 'all';
        }

        try {
            $gregorian = Jalali::parseJalaliDate($dateJalali)->toDateString();
        } catch (\Throwable $e) {
            $dateJalali = $todayJalali;
            $gregorian = now()->toDateString();
        }

        $rows = $this->rowsForDate($gregorian, $kindFilter);

        $upcoming = $rows->filter(fn ($r) => in_array($r['item']->status, [BookingStatus::SCHEDULED, BookingStatus::CONFIRMED], true))->values();
        $waiting = $rows->filter(fn ($r) => $r['item']->status === BookingStatus::WAITING)->values();
        $ready = $rows->filter(fn ($r) => $r['item']->status === BookingStatus::READY)->values();
        $inConsult = $rows->filter(fn ($r) => $r['item']->status === BookingStatus::IN_CONSULT)->values();

        $role = auth()->user()?->role;
        $isDoctor = in_array($role, ['doctor', 'admin'], true);

        $version = $rows
            ->map(fn ($r) => $r['type'].':'.$r['item']->id.':'.$r['item']->status.':'.optional($r['item']->updated_at)?->timestamp)
            ->implode('|');

        return [
            'dateJalali' => $dateJalali,
            'todayJalali' => $todayJalali,
            'kindFilter' => $kindFilter,
            'upcoming' => $upcoming,
            'waiting' => $waiting,
            'ready' => $ready,
            'inConsult' => $inConsult,
            'isDoctor' => $isDoctor,
            'current' => $inConsult->first() ?? $ready->first(),
            'version' => sha1($version !== '' ? $version : 'empty'),
        ];
    }

    /**
     * @return Collection<int, array{type: string, item: Appointment|SurgeryAppointment}>
     */
    private function rowsForDate(string $gregorian, string $kindFilter = 'all'): Collection
    {
        $rows = collect();

        if ($kindFilter !== 'surgery') {
            foreach (
                Appointment::query()
                    ->with('patient')
                    ->whereDate('scheduled_date', $gregorian)
                    ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::DONE, BookingStatus::NO_SHOW])
                    ->orderBy('scheduled_time')
                    ->get() as $item
            ) {
                $rows->push(['type' => 'visit', 'item' => $item]);
            }
        }

        if ($kindFilter !== 'visit') {
            foreach (
                SurgeryAppointment::query()
                    ->with(['patient', 'hospital'])
                    ->whereDate('scheduled_date', $gregorian)
                    ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::DONE, BookingStatus::NO_SHOW])
                    ->orderBy('scheduled_time')
                    ->get() as $item
            ) {
                $rows->push(['type' => 'surgery', 'item' => $item]);
            }
        }

        return $rows->sortBy(fn ($r) => (string) $r['item']->scheduled_time)->values();
    }
}
