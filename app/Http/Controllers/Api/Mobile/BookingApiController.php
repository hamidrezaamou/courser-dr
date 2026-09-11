<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SurgeryAppointmentController;
use App\Models\Hospital;
use App\Models\Patient;
use App\Support\AppointmentSms;
use App\Support\MobilePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingApiController extends Controller
{
    public function visitCalendar(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(AppointmentController::class)->visitCalendar();
    }

    public function slots(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(AppointmentController::class)->slots($request);
    }

    public function surgeryOptions(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(AppointmentController::class)->surgeryOptions($request);
    }

    public function hospitals(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeVisit(Request $request, Patient $patient): JsonResponse
    {
        $appointment = app(AppointmentController::class)->storeVisitForMobile($request, $patient);

        return response()->json([
            'ok' => true,
            'message' => 'نوبت ویزیت ثبت شد.',
            'appointment' => MobilePayload::appointment($appointment),
            'sms_body' => AppointmentSms::forItem($appointment, 'visit'),
        ], 201);
    }

    public function registerVisit(Request $request): JsonResponse
    {
        $appointment = app(AppointmentController::class)->registerVisitForMobile($request);

        return response()->json([
            'ok' => true,
            'message' => 'نوبت ویزیت ثبت شد.',
            'appointment' => MobilePayload::appointment($appointment),
            'patient_id' => $appointment->patient_id,
            'sms_body' => AppointmentSms::forItem($appointment, 'visit'),
        ], 201);
    }

    public function storeSurgery(Request $request, Patient $patient): JsonResponse
    {
        $surgery = app(SurgeryAppointmentController::class)->storeSurgeryForMobile($request, $patient);

        return response()->json([
            'ok' => true,
            'message' => 'نوبت عمل ثبت شد.',
            'surgery' => MobilePayload::surgery($surgery->loadMissing('hospital')),
            'sms_body' => AppointmentSms::forItem($surgery, 'surgery'),
        ], 201);
    }

    public function registerSurgery(Request $request): JsonResponse
    {
        $surgery = app(SurgeryAppointmentController::class)->registerSurgeryForMobile($request);

        return response()->json([
            'ok' => true,
            'message' => 'نوبت عمل ثبت شد.',
            'surgery' => MobilePayload::surgery($surgery->loadMissing('hospital')),
            'patient_id' => $surgery->patient_id,
            'sms_body' => AppointmentSms::forItem($surgery, 'surgery'),
        ], 201);
    }

    public function lookup(Request $request): JsonResponse
    {
        return app(\App\Http\Controllers\PatientController::class)->lookupByNationalCode($request);
    }

    public function showVisit(\App\Models\Appointment $appointment): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'appointment' => MobilePayload::appointment($appointment),
        ]);
    }

    public function showSurgery(\App\Models\SurgeryAppointment $surgeryAppointment): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'surgery' => MobilePayload::surgery($surgeryAppointment->loadMissing(['hospital', 'surgeryType', 'surgerySubtype'])),
        ]);
    }

    public function updateVisit(Request $request, \App\Models\Appointment $appointment): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');
        $updated = app(AppointmentController::class)->updateForMobile($request, $appointment);

        return response()->json([
            'ok' => true,
            'message' => 'نوبت ویزیت به‌روزرسانی شد.',
            'appointment' => MobilePayload::appointment($updated),
            'sms_body' => AppointmentSms::forItem($updated, 'visit'),
        ]);
    }

    public function updateSurgery(Request $request, \App\Models\SurgeryAppointment $surgeryAppointment): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');
        $updated = app(SurgeryAppointmentController::class)->updateForMobile($request, $surgeryAppointment);

        return response()->json([
            'ok' => true,
            'message' => 'نوبت عمل به‌روزرسانی شد.',
            'surgery' => MobilePayload::surgery($updated->loadMissing(['hospital', 'surgeryType', 'surgerySubtype'])),
            'sms_body' => AppointmentSms::forItem($updated, 'surgery'),
        ]);
    }

    public function cooldownCheck(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return app(SurgeryAppointmentController::class)->cooldownCheck($request);
    }
}
