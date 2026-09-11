<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Support\ActivityLogger;
use App\Support\BookingApprovalNotifier;
use App\Support\BookingStatus;
use App\Support\ModuleFinance;
use App\Support\ModuleRegistry;
use App\Support\SlotGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingApprovalController extends Controller
{
    public function index(): View
    {
        return view('modules.approval.index', [
            'moduleSection' => ModuleRegistry::definitions()['approval']['section'],
            'pending' => Appointment::query()
                ->with('patient')
                ->where('status', BookingStatus::PENDING_APPROVAL)
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->get(),
        ]);
    }

    public function approve(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->status === BookingStatus::PENDING_APPROVAL, 404);

        try {
            DB::transaction(function () use ($appointment) {
                SlotGuard::assertVisitSlotFree(
                    $appointment->scheduled_date->format('Y-m-d'),
                    (string) $appointment->scheduled_time,
                    $appointment->id
                );

                $appointment->update(['status' => BookingStatus::SCHEDULED]);

                Appointment::query()
                    ->where('id', '!=', $appointment->id)
                    ->where('status', BookingStatus::PENDING_APPROVAL)
                    ->whereDate('scheduled_date', $appointment->scheduled_date)
                    ->where('scheduled_time', $appointment->scheduled_time)
                    ->update(['status' => BookingStatus::CANCELLED]);

                ActivityLogger::log($appointment, 'updated', ['status' => BookingStatus::PENDING_APPROVAL], [
                    'status' => BookingStatus::SCHEDULED,
                    'action' => 'online_booking_approved',
                ]);

                ModuleFinance::syncBillable($appointment->fresh());
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', 'این ساعت دیگر آزاد نیست.');
        }

        BookingApprovalNotifier::approved($appointment->fresh());

        return back()->with('success', 'نوبت تأیید و در برنامه ثبت شد.');
    }

    public function reject(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->status === BookingStatus::PENDING_APPROVAL, 404);

        $appointment->update(['status' => BookingStatus::CANCELLED]);
        ActivityLogger::log($appointment, 'updated', ['status' => BookingStatus::PENDING_APPROVAL], [
            'status' => BookingStatus::CANCELLED,
            'action' => 'online_booking_rejected',
        ]);

        BookingApprovalNotifier::rejected($appointment);

        return back()->with('success', 'درخواست نوبت رد شد.');
    }
}
