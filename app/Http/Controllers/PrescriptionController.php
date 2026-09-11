<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Visit;
use App\Support\ActivityLogger;
use App\Support\BookingStatus;
use App\Support\ClinicBrand;
use App\Support\QrImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'visit_id' => ['nullable', 'integer', 'exists:visits,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.drug_id' => ['nullable', 'integer', 'exists:drugs,id'],
            'items.*.drug_name' => ['required', 'string', 'max:255'],
            'items.*.usage_type' => ['nullable', 'string', 'max:100'],
            'items.*.dosage' => ['nullable', 'string', 'max:100'],
            'items.*.frequency' => ['nullable', 'string', 'max:100'],
            'items.*.meal_timing' => ['nullable', 'string', 'in:before_meal,after_meal,with_meal,empty_stomach,anytime'],
            'items.*.duration' => ['nullable', 'string', 'max:100'],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
        ]);

        $visitId = $this->resolveVisitId($patient, $validated['visit_id'] ?? null);

        $prescription = $patient->prescriptions()->create([
            'notes' => $validated['notes'] ?? null,
            'visit_id' => $visitId,
            'created_by' => $request->user()->id,
        ]);

        foreach ($validated['items'] as $index => $item) {
            $prescription->items()->create([
                'drug_id' => $item['drug_id'] ?? null,
                'drug_name' => trim($item['drug_name']),
                'usage_type' => $item['usage_type'] ?? null,
                'dosage' => $item['dosage'] ?? null,
                'frequency' => $item['frequency'] ?? null,
                'meal_timing' => $item['meal_timing'] ?? null,
                'duration' => $item['duration'] ?? null,
                'instructions' => $item['instructions'] ?? null,
                'sort_order' => $index,
            ]);
        }

        ActivityLogger::log($prescription, 'created', null, [
            'items_count' => count($validated['items']),
            'visit_id' => $visitId,
        ]);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'نسخه دارویی ثبت شد.');
    }

    public function update(Request $request, Patient $patient, Prescription $prescription): RedirectResponse
    {
        abort_unless($prescription->patient_id === $patient->id, 404);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.drug_id' => ['nullable', 'integer', 'exists:drugs,id'],
            'items.*.drug_name' => ['required', 'string', 'max:255'],
            'items.*.usage_type' => ['nullable', 'string', 'max:100'],
            'items.*.dosage' => ['nullable', 'string', 'max:100'],
            'items.*.frequency' => ['nullable', 'string', 'max:100'],
            'items.*.meal_timing' => ['nullable', 'string', 'in:before_meal,after_meal,with_meal,empty_stomach,anytime'],
            'items.*.duration' => ['nullable', 'string', 'max:100'],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
        ]);

        $old = ['notes' => $prescription->notes, 'items' => $prescription->items->count()];

        $prescription->update([
            'notes' => $validated['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        $prescription->items()->delete();

        foreach ($validated['items'] as $index => $item) {
            $prescription->items()->create([
                'drug_id' => $item['drug_id'] ?? null,
                'drug_name' => trim($item['drug_name']),
                'usage_type' => $item['usage_type'] ?? null,
                'dosage' => $item['dosage'] ?? null,
                'frequency' => $item['frequency'] ?? null,
                'meal_timing' => $item['meal_timing'] ?? null,
                'duration' => $item['duration'] ?? null,
                'instructions' => $item['instructions'] ?? null,
                'sort_order' => $index,
            ]);
        }

        ActivityLogger::log($prescription, 'updated', $old, [
            'notes' => $prescription->notes,
            'items' => count($validated['items']),
        ]);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'نسخه به‌روزرسانی شد.');
    }

    public function destroy(Patient $patient, Prescription $prescription): RedirectResponse
    {
        abort_unless($prescription->patient_id === $patient->id, 404);

        ActivityLogger::log($prescription, 'deleted', ['id' => $prescription->id], null);
        $prescription->delete();

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'نسخه حذف شد.');
    }

    public function print(Patient $patient, Prescription $prescription): View
    {
        abort_unless($prescription->patient_id === $patient->id, 404);

        $prescription->load(['patient', 'items.drug', 'creator', 'visit']);

        $qrPayload = route('prescriptions.print', [$patient, $prescription]);
        $qrReference = 'RX:'.$prescription->id.':P'.$patient->id;

        return view('modules.prescriptions.print', [
            'prescription' => $prescription,
            'doctorName' => ClinicBrand::doctorName(),
            'clinicPhone' => ClinicBrand::phone(),
            'logoUrl' => ClinicBrand::logoDataUri(),
            'signatureUrl' => ClinicBrand::signatureDataUri(),
            'stampUrl' => ClinicBrand::stampDataUri(),
            'qrPayload' => $qrReference,
            'qrDataUri' => QrImage::dataUri($qrPayload, 240),
        ]);
    }

    private function resolveVisitId(Patient $patient, mixed $requestedId): ?int
    {
        if ($requestedId) {
            $visit = Visit::query()->find((int) $requestedId);
            if ($visit && (int) $visit->patient_id === (int) $patient->id) {
                return (int) $visit->id;
            }
        }

        $active = Visit::query()
            ->where('patient_id', $patient->id)
            ->where(function ($query) {
                $query->whereHas('appointment', fn ($q) => $q->where('status', BookingStatus::IN_CONSULT))
                    ->orWhereHas('surgeryAppointment', fn ($q) => $q->where('status', BookingStatus::IN_CONSULT));
            })
            ->latest('id')
            ->value('id');

        return $active ? (int) $active : null;
    }
}
