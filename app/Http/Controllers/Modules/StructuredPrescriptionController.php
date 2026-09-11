<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Prescription;
use App\Support\ClinicBrand;
use App\Support\Jalali;
use App\Support\ModuleRegistry;
use App\Support\QrImage;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StructuredPrescriptionController extends Controller
{
    public function index(): View
    {
        return view('modules.prescriptions.index', [
            'moduleSection' => ModuleRegistry::definitions()['rx_print']['section'],
            'prescriptions' => Prescription::query()
                ->with(['patient', 'items', 'creator'])
                ->latest()
                ->limit(40)
                ->get(),
        ]);
    }

    public function print(Prescription $prescription): View
    {
        $prescription->load(['patient', 'items.drug', 'creator', 'visit']);

        // Scanning the code should land the reader on the prescription itself.
        $qrPayload = route('modules.prescriptions.print', $prescription);
        $qrReference = 'RX:'.$prescription->id.':P'.$prescription->patient_id;

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
}
