<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\ReportNote;
use App\Models\SurgeryAppointment;
use Illuminate\Database\QueryException;

class BookingReportNote
{
    public const SOURCE = 'booking';

    public static function sync(Appointment|SurgeryAppointment $subject, string $type, ?int $userId = null): void
    {
        ReportNote::ensureIncludeInPrintColumn();
        ReportNote::ensurePinColumns();

        $body = trim((string) ($subject->notes ?? ''));
        $existing = ReportNote::query()
            ->where('subject_type', $type)
            ->where('subject_id', $subject->id)
            ->where('source', self::SOURCE)
            ->first();

        if ($body === '') {
            $existing?->delete();

            return;
        }

        if ($existing) {
            $existing->fill([
                'body' => $body,
                'pinned' => true,
                'patient_id' => $subject->patient_id ?: $existing->patient_id,
            ]);
            if ($existing->isDirty()) {
                $existing->save();
            }

            return;
        }

        $attrs = [
            'subject_type' => $type,
            'subject_id' => $subject->id,
            'patient_id' => $subject->patient_id ?: null,
            'created_by' => $userId ?: $subject->created_by,
            'body' => $body,
            'include_in_print' => false,
            'pinned' => true,
            'source' => self::SOURCE,
        ];

        try {
            ReportNote::query()->create($attrs);
        } catch (QueryException $e) {
            ReportNote::relaxUniqueConstraint();
            $again = ReportNote::query()
                ->where('subject_type', $type)
                ->where('subject_id', $subject->id)
                ->where('source', self::SOURCE)
                ->first();
            if ($again) {
                $again->update([
                    'body' => $body,
                    'pinned' => true,
                    'patient_id' => $subject->patient_id ?: $again->patient_id,
                ]);

                return;
            }
            ReportNote::query()->create($attrs);
        }
    }

    public static function writeBack(ReportNote $note): void
    {
        if ($note->source !== self::SOURCE) {
            return;
        }

        $subject = self::subject($note);
        if (! $subject) {
            return;
        }

        $body = trim((string) $note->body);
        $subject->notes = $body === '' ? null : $body;
        $subject->saveQuietly();
    }

    public static function clearSubject(ReportNote $note): void
    {
        if ($note->source !== self::SOURCE) {
            return;
        }

        $subject = self::subject($note);
        if (! $subject) {
            return;
        }

        $subject->notes = null;
        $subject->saveQuietly();
    }

    private static function subject(ReportNote $note): Appointment|SurgeryAppointment|null
    {
        if ($note->subject_type === 'surgery') {
            return SurgeryAppointment::query()->find($note->subject_id);
        }

        return Appointment::query()->find($note->subject_id);
    }
}
