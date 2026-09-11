<?php

namespace App\Http\Controllers;

use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Support\ImageCompressor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MedicalDocumentController extends Controller
{
    /**
     * Store a newly uploaded medical document.
     */
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'image', 'max:10240'],
            'type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($request->file('files', []) as $file) {
            $path = ImageCompressor::storeUploaded($file, 'medical_documents');

            $patient->medicalDocuments()->create([
                'file_path' => $path,
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        }

        return $this->redirectToPatient($request, $patient, 'تصاویر پزشکی با موفقیت آپلود شدند.');
    }

    /**
     * Delete a medical document from the patient file.
     */
    public function destroy(Request $request, Patient $patient, MedicalDocument $document): RedirectResponse
    {
        abort_unless($document->patient_id === $patient->id, 404);

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return $this->redirectToPatient($request, $patient, 'تصویر پزشکی حذف شد.');
    }

    public function rotate(Request $request, Patient $patient, MedicalDocument $document): JsonResponse|RedirectResponse
    {
        abort_unless($document->patient_id === $patient->id, 404);

        $direction = strtolower((string) $request->input('direction', 'cw'));
        $clockwise = $direction === 'ccw' ? -90 : 90;
        $ok = $document->file_path && ImageCompressor::rotateStored($document->file_path, $clockwise);

        if ($request->expectsJson() || $request->ajax()) {
            if (! $ok) {
                return response()->json(['ok' => false, 'message' => 'چرخش تصویر انجام نشد.'], 422);
            }

            return response()->json([
                'ok' => true,
                'src' => asset('storage/'.$document->file_path).'?t='.time(),
            ]);
        }

        if (! $ok) {
            return $this->redirectToPatient($request, $patient, 'چرخش تصویر انجام نشد.');
        }

        return $this->redirectToPatient($request, $patient, 'تصویر چرخانده شد.');
    }

    private function redirectToPatient(Request $request, Patient $patient, string $message): RedirectResponse
    {
        $query = [];
        if ($request->boolean('embed') || $request->boolean('_embed')) {
            $query['embed'] = 1;
        }
        $open = $request->input('open', $request->input('_open'));
        if (is_string($open) && $open !== '') {
            $query['open'] = $open;
        } elseif (! empty($query['embed'])) {
            $query['open'] = 'photos';
        }

        return redirect()
            ->route('patients.show', array_merge(['patient' => $patient], $query))
            ->with('success', $message)
            ->with('active_tab', 'images');
    }
}
