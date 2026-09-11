<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ConsentTemplate;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\SurgeryAppointment;
use App\Support\ClinicBrand;
use App\Support\ModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsentController extends Controller
{
    public function index(): View
    {
        return view('modules.consent.index', [
            'moduleSection' => ModuleRegistry::definitions()['consent']['section'],
            'templates' => ConsentTemplate::query()->latest()->get(),
            'recent' => PatientConsent::query()->with(['patient', 'template'])->latest()->limit(30)->get(),
            'patients' => Patient::query()->orderBy('name')->limit(100)->get(['id', 'name']),
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'kind' => ['required', 'in:visit,surgery'],
            'body' => ['required', 'string', 'max:8000'],
        ]);

        ConsentTemplate::create($validated + ['is_active' => true]);

        return back()->with('success', 'قالب رضایت‌نامه ذخیره شد.');
    }

    public function storeConsent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'consent_template_id' => ['required', 'exists:consent_templates,id'],
            'signed_by_name' => ['required', 'string', 'max:160'],
            'subject_type' => ['nullable', 'in:visit,surgery'],
            'subject_id' => ['nullable', 'integer'],
        ]);

        $subjectType = null;
        $subjectId = null;
        if (! empty($validated['subject_type']) && ! empty($validated['subject_id'])) {
            $subjectType = $validated['subject_type'] === 'surgery'
                ? SurgeryAppointment::class
                : Appointment::class;
            $subjectId = (int) $validated['subject_id'];
        }

        PatientConsent::create([
            'patient_id' => $validated['patient_id'],
            'consent_template_id' => $validated['consent_template_id'],
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'signed_at' => now(),
            'signed_by_name' => $validated['signed_by_name'],
            'recorded_by' => $request->user()->id,
        ]);

        $redirect = route('patients.show', $validated['patient_id']);

        return redirect($redirect)->with('success', 'رضایت‌نامه در پرونده ثبت شد.');
    }

    public function print(PatientConsent $consent): View
    {
        $consent->load(['patient', 'template', 'subject']);

        return view('modules.consent.print', [
            'consent' => $consent,
            'doctorName' => ClinicBrand::doctorName(),
            'clinicPhone' => ClinicBrand::phone(),
            'logoUrl' => ClinicBrand::logoDataUri(),
            'signatureUrl' => ClinicBrand::signatureDataUri(),
            'stampUrl' => ClinicBrand::stampDataUri(),
        ]);
    }
}
