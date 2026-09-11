<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Visit;
use App\Support\ActivityLogger;
use App\Support\EyeSide;
use App\Support\His\HisLock;
use App\Support\ImageCompressor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VisitController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function examRules(): array
    {
        return [
            'history' => ['nullable', 'string'],
            'examination' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'next_instruction' => ['nullable', 'string'],
            'eye_side' => ['nullable', 'string', 'in:OD,OS,OU,راست,چپ,دو طرفه,دوطرفه'],
            'va_right' => ['nullable', 'string', 'max:50'],
            'va_left' => ['nullable', 'string', 'max:50'],
            'iop_right' => ['nullable', 'string', 'max:50'],
            'iop_left' => ['nullable', 'string', 'max:50'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'surgery_appointment_id' => ['nullable', 'integer', 'exists:surgery_appointments,id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeExam(array $validated): array
    {
        if (array_key_exists('eye_side', $validated)) {
            $validated['eye_side'] = EyeSide::normalize($validated['eye_side'] ?? null);
        }

        return $validated;
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $this->normalizeExam($request->validate($this->examRules()));

        $visit = $patient->visits()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        ActivityLogger::log($visit, 'created', null, $validated);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'معاینه با موفقیت ثبت شد.');
    }

    public function update(Request $request, Patient $patient, Visit $visit): RedirectResponse
    {
        abort_unless($visit->patient_id === $patient->id, 404);
        HisLock::guard($visit);

        $validated = $this->normalizeExam($request->validate($this->examRules()));

        $old = $visit->only([
            'history', 'examination', 'diagnosis', 'treatment', 'next_instruction',
            'eye_side', 'va_right', 'va_left', 'iop_right', 'iop_left',
        ]);

        $visit->update([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        ActivityLogger::log($visit, 'updated', $old, $validated);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'معاینه ویرایش شد.');
    }

    public function storeDrawing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'image' => ['required', 'string'],
            'visit_id' => ['nullable', 'integer', 'exists:visits,id'],
        ]);

        $filename = $this->saveDrawingImage($validated['image']);

        if (! empty($validated['visit_id'])) {
            $visit = Visit::query()->findOrFail($validated['visit_id']);
            abort_unless($visit->patient_id === (int) $validated['patient_id'], 404);
            abort_unless($visit->drawing_path, 422);

            $oldPath = $visit->drawing_path;
            $visit->update([
                'drawing_path' => $filename,
                'updated_by' => $request->user()->id,
            ]);

            if ($oldPath && $oldPath !== $filename) {
                Storage::disk('public')->delete($oldPath);
            }

            ActivityLogger::log($visit, 'updated', ['drawing_path' => $oldPath], ['drawing_path' => $filename]);

            return response()->json([
                'success' => true,
                'message' => 'وایت‌برد ویرایش شد.',
                'path' => $filename,
                'visit_id' => $visit->id,
            ]);
        }

        $visit = Visit::create([
            'patient_id' => $validated['patient_id'],
            'drawing_path' => $filename,
            'created_by' => $request->user()->id,
        ]);

        ActivityLogger::log($visit, 'created', null, ['drawing_path' => $filename]);

        return response()->json([
            'success' => true,
            'message' => 'نقاشی با موفقیت ذخیره شد.',
            'path' => $filename,
            'visit_id' => $visit->id,
        ]);
    }

    public function storeVoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'audio' => ['required', 'file', 'max:51200'],
        ]);

        $extension = $request->file('audio')->getClientOriginalExtension() ?: 'webm';
        $filename = Str::uuid().'.'.$extension;
        $path = $request->file('audio')->storeAs('voices', $filename, 'public');

        $visit = Visit::create([
            'patient_id' => $validated['patient_id'],
            'voice_path' => $path,
            'created_by' => $request->user()->id,
        ]);

        ActivityLogger::log($visit, 'created', null, ['voice_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'صدای معاینه با موفقیت ذخیره شد.',
            'path' => $path,
        ]);
    }

    public function destroy(Request $request, Patient $patient, Visit $visit): RedirectResponse
    {
        abort_unless($visit->patient_id === $patient->id, 404);
        HisLock::guard($visit);

        ActivityLogger::log($visit, 'deleted', $visit->only(['drawing_path', 'voice_path', 'examination']), null);

        if ($visit->voice_path) {
            Storage::disk('public')->delete($visit->voice_path);
        }
        if ($visit->drawing_path) {
            Storage::disk('public')->delete($visit->drawing_path);
        }

        $visit->delete();

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
            ->with('success', 'مورد حذف شد.');
    }

    private function saveDrawingImage(string $imageData): string
    {
        if (str_contains($imageData, ',')) {
            $imageData = explode(',', $imageData, 2)[1];
        }

        $decoded = base64_decode($imageData, true);

        if ($decoded === false) {
            abort(422, 'تصویر نامعتبر است.');
        }

        return ImageCompressor::storeBinary($decoded, 'drawings');
    }
}
