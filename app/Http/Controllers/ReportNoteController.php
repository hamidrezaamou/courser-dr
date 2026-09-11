<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ReportNote;
use App\Models\SurgeryAppointment;
use App\Support\BookingReportNote;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReportNoteController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $this->assertCanViewReports();
        ReportNote::ensureIncludeInPrintColumn();
        ReportNote::ensurePinColumns();

        $validated = $request->validate([
            'subject_type' => ['required', 'in:visit,surgery'],
            'subject_id' => ['required', 'integer'],
        ]);

        $subject = $this->resolveSubject($validated['subject_type'], (int) $validated['subject_id']);
        BookingReportNote::sync($subject, $validated['subject_type']);

        $notes = ReportNote::query()
            ->with('author:id,name')
            ->where('subject_type', $validated['subject_type'])
            ->where('subject_id', $validated['subject_id'])
            ->orderByDesc('pinned')
            ->orderBy('id')
            ->get();

        $userId = (int) $request->user()->id;

        return response()->json([
            'messages' => $notes->map(fn (ReportNote $note) => $this->serialize($note, $userId))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertCanViewReports();
        ReportNote::ensureIncludeInPrintColumn();

        $validated = $request->validate([
            'subject_type' => ['required', 'in:visit,surgery'],
            'subject_id' => ['required', 'integer'],
            'patient_id' => ['nullable', 'integer'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $subject = $this->resolveSubject($validated['subject_type'], (int) $validated['subject_id']);
        $patientId = $validated['patient_id'] ?? $subject->patient_id ?? null;
        if ($patientId && ! \App\Models\Patient::query()->whereKey($patientId)->exists()) {
            $patientId = $subject->patient_id ?? null;
        }

        try {
            $note = $this->insertNote([
                'subject_type' => $validated['subject_type'],
                'subject_id' => (int) $validated['subject_id'],
                'patient_id' => $patientId ?: null,
                'created_by' => $request->user()->id,
                'body' => trim($validated['body']),
                'include_in_print' => false,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'ارسال پیام انجام نشد. یک‌بار دیگر تلاش کنید.',
            ], 500);
        }
        $note->load('author:id,name');

        return response()->json([
            'ok' => true,
            'note' => $this->serialize($note, (int) $request->user()->id),
        ]);
    }

    public function update(Request $request, ReportNote $note): JsonResponse
    {
        $this->assertCanViewReports();
        ReportNote::ensureIncludeInPrintColumn();
        ReportNote::ensurePinColumns();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $note->update(['body' => trim($validated['body'])]);
        BookingReportNote::writeBack($note);
        $note->load('author:id,name');

        return response()->json([
            'ok' => true,
            'note' => $this->serialize($note, (int) $request->user()->id),
        ]);
    }

    public function destroy(Request $request, ReportNote $note): JsonResponse
    {
        $this->assertCanViewReports();

        BookingReportNote::clearSubject($note);
        $note->delete();

        return response()->json(['ok' => true]);
    }

    public function togglePrint(Request $request, ReportNote $note): JsonResponse
    {
        $this->assertCanViewReports();
        ReportNote::ensureIncludeInPrintColumn();

        $validated = $request->validate([
            'include_in_print' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('include_in_print', $validated)) {
            $note->include_in_print = (bool) $validated['include_in_print'];
        } else {
            $note->include_in_print = ! $note->include_in_print;
        }
        $note->save();
        $note->load('author:id,name');

        return response()->json([
            'ok' => true,
            'note' => $this->serialize($note, (int) $request->user()->id),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ReportNote $note, int $userId): array
    {
        $created = $note->created_at;
        $updated = $note->updated_at;
        $edited = $created && $updated && $updated->gt($created->copy()->addSeconds(2));

        return [
            'id' => $note->id,
            'body' => $note->body,
            'author' => $note->author?->name ?: 'کاربر',
            'mine' => $note->created_by === null || (int) $note->created_by === $userId,
            'include_in_print' => (bool) $note->include_in_print,
            'pinned' => (bool) $note->pinned,
            'source' => $note->source,
            'sourceLabel' => $note->source === BookingReportNote::SOURCE
                ? ($note->subject_type === 'surgery' ? 'از ثبت عمل' : 'از ثبت نوبت')
                : null,
            'date' => $created ? jalali($created, 'Y/m/d') : '',
            'time' => $created ? $created->format('H:i') : '',
            'edited' => $edited,
        ];
    }

    private function resolveSubject(string $type, int $id): Appointment|SurgeryAppointment
    {
        if ($type === 'surgery') {
            return SurgeryAppointment::query()->findOrFail($id);
        }

        return Appointment::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function insertNote(array $attrs): ReportNote
    {
        try {
            $note = ReportNote::query()->create($attrs);
        } catch (QueryException $e) {
            if (! $this->isUniqueConflict($e)) {
                throw $e;
            }
            ReportNote::relaxUniqueConstraint();
            $note = ReportNote::query()->create($attrs);
        }

        $note->load('author:id,name');

        return $note;
    }

    private function isUniqueConflict(QueryException $e): bool
    {
        $driverCode = (string) ($e->errorInfo[1] ?? '');
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        $msg = $e->getMessage();

        return $driverCode === '1062'
            || $sqlState === '23000'
            || str_contains($msg, 'UNIQUE')
            || str_contains($msg, 'unique');
    }

    private function assertCanViewReports(): void
    {
        if (! auth()->user()?->canViewReports()) {
            throw new HttpException(403, 'دسترسی به گزارشات ندارید.');
        }
    }
}
