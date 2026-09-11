<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\FollowUpCatalogItem;
use App\Models\FollowUpReminder;
use App\Models\FollowUpTemplate;
use App\Models\FollowUpTemplateStep;
use App\Models\Patient;
use App\Models\PatientFollowUp;
use App\Models\SurgeryAppointment;
use App\Models\Visit;
use App\Support\ActivityLogger;
use App\Support\BookingStatus;
use App\Support\FollowUpStatus;
use App\Support\FollowUpTiming;
use App\Support\PatientFollowUps;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientFollowUpService
{
    public function ensureForSurgery(SurgeryAppointment $surgery): int
    {
        if (! PatientFollowUps::isAvailable()) {
            return 0;
        }
        if (in_array($surgery->status, [BookingStatus::CANCELLED], true)) {
            return 0;
        }

        $template = PatientFollowUps::findTemplateForSurgery($surgery);
        if (! $template || $template->steps->isEmpty()) {
            return 0;
        }

        $this->removeStaleTemplateFollowUpsForSurgery($surgery, $template);

        return $this->generateFromTemplate($template, $surgery);
    }

    public function ensureForVisit(Appointment $appointment): int
    {
        if (! PatientFollowUps::isAvailable()) {
            return 0;
        }
        if (($appointment->source ?? null) === 'his') {
            return 0;
        }
        if (in_array($appointment->status, [BookingStatus::CANCELLED], true)) {
            return 0;
        }

        $template = PatientFollowUps::findTemplateForVisit();
        if (! $template || $template->steps->isEmpty()) {
            return 0;
        }

        return $this->generateFromTemplate($template, $appointment);
    }

    public function syncSurgery(SurgeryAppointment $surgery): void
    {
        if (! PatientFollowUps::isAvailable()) {
            return;
        }

        if ($surgery->status === BookingStatus::CANCELLED) {
            $this->cancelOpenForSurgery($surgery, 'لغو نوبت عمل');

            return;
        }

        $dateChanged = $surgery->wasChanged('scheduled_date') || $surgery->wasChanged('scheduled_time');
        $bindingChanged = $surgery->wasChanged('hospital_id')
            || $surgery->wasChanged('surgery_type_id')
            || $surgery->wasChanged('surgery_subtype_id');

        if ($bindingChanged) {
            PatientFollowUp::query()
                ->where('surgery_appointment_id', $surgery->id)
                ->where('source', PatientFollowUp::SOURCE_TEMPLATE)
                ->open()
                ->delete();
            $this->ensureForSurgery($surgery);

            return;
        }

        if ($dateChanged) {
            $this->recalculateOpenForSurgery($surgery);
        }

        if ($surgery->wasRecentlyCreated || $dateChanged) {
            $this->ensureForSurgery($surgery);
        }
    }

    public function syncVisit(Appointment $appointment): void
    {
        if (! PatientFollowUps::isAvailable()) {
            return;
        }

        if ($appointment->status === BookingStatus::CANCELLED) {
            $this->cancelOpenForVisit($appointment, 'لغو نوبت ویزیت');

            return;
        }

        $dateChanged = $appointment->wasChanged('scheduled_date') || $appointment->wasChanged('scheduled_time');

        if ($dateChanged) {
            $this->recalculateOpenForVisit($appointment);
        }

        if ($appointment->wasRecentlyCreated || $dateChanged) {
            $this->ensureForVisit($appointment);
        }
    }

    /**
     * Apply this template to already-booked surgeries / visits (not only future registrations).
     */
    public function applyTemplateToExisting(FollowUpTemplate $template): int
    {
        if (! PatientFollowUps::isAvailable() || ! $template->is_active) {
            return 0;
        }

        $template->loadMissing(['steps', 'surgeryType']);
        if ($template->steps->isEmpty()) {
            return 0;
        }

        $since = now()->subDays(90)->toDateString();
        $created = 0;
        $scanned = 0;
        $limit = 1000;

        if ($template->applies_to === FollowUpTemplate::APPLIES_VISIT) {
            Appointment::query()
                ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::NO_SHOW])
                ->where(function ($query) {
                    $query->whereNull('source')->orWhere('source', '!=', 'his');
                })
                ->whereDate('scheduled_date', '>=', $since)
                ->orderBy('id')
                ->chunkById(80, function ($rows) use ($template, &$created, &$scanned, $limit) {
                    foreach ($rows as $appointment) {
                        if ($scanned >= $limit) {
                            return false;
                        }
                        $scanned++;
                        $chosen = PatientFollowUps::findTemplateForVisit();
                        if (! $chosen || (int) $chosen->id !== (int) $template->id) {
                            continue;
                        }
                        $created += $this->generateFromTemplate($template, $appointment, false);
                    }

                    return $scanned < $limit;
                });

            return $created;
        }

        $typeId = $template->surgery_type_id ? (int) $template->surgery_type_id : null;
        $subtypeId = $template->surgery_subtype_id ? (int) $template->surgery_subtype_id : null;
        $typeName = trim((string) ($template->surgeryType?->name ?? ''));
        if (! $typeId && $typeName === '') {
            return 0;
        }

        $query = SurgeryAppointment::query()
            ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::NO_SHOW])
            ->whereDate('scheduled_date', '>=', $since);

        if ($template->hospital_id) {
            $query->where('hospital_id', $template->hospital_id);
        }
        if ($subtypeId) {
            $query->where('surgery_subtype_id', $subtypeId);
        }

        $query->where(function ($outer) use ($typeId, $typeName) {
            if ($typeId) {
                $outer->where('surgery_type_id', $typeId);
            }
            if ($typeName !== '') {
                $outer->orWhere('surgery_type', $typeName);
            }
        });

        $query->orderBy('id')->chunkById(80, function ($rows) use ($template, &$created, &$scanned, $limit) {
            foreach ($rows as $surgery) {
                if ($scanned >= $limit) {
                    return false;
                }
                $scanned++;
                $chosen = PatientFollowUps::findTemplateForSurgery($surgery);
                if (! $chosen || (int) $chosen->id !== (int) $template->id) {
                    continue;
                }
                $this->removeStaleTemplateFollowUpsForSurgery($surgery, $template);
                $created += $this->generateFromTemplate($template, $surgery, false);
            }

            return $scanned < $limit;
        });

        return $created;
    }

    /**
     * @param  SurgeryAppointment|Appointment  $subject
     */
    public function generateFromTemplate(FollowUpTemplate $template, $subject, bool $auditEach = true): int
    {
        $created = 0;

        DB::transaction(function () use ($template, $subject, &$created, $auditEach) {
            $template->loadMissing('steps');
            foreach ($template->steps as $step) {
                $key = $this->generationKey($template, $step, $subject);
                $existing = PatientFollowUp::query()->where('generation_key', $key)->first();
                if ($existing) {
                    if ($existing->isOpen()) {
                        $this->applyStepTiming($existing, $step, $subject);
                    }
                    continue;
                }

                $payload = $this->basePayloadFromSubject($subject);
                $reference = $this->resolveReference($step->reference_event, $subject, null);
                $due = FollowUpTiming::apply(
                    $reference,
                    (int) $step->offset_amount,
                    (string) $step->offset_unit,
                    (string) $step->offset_direction
                );

                $followUp = PatientFollowUp::create(array_merge($payload, [
                    'template_id' => $template->id,
                    'template_step_id' => $step->id,
                    'generation_key' => $key,
                    'title' => $step->title,
                    'description' => $step->description,
                    'kind' => $step->kind,
                    'method' => $step->method,
                    'reference_event' => $step->reference_event,
                    'reference_at' => $reference,
                    'due_at' => $due,
                    'status' => FollowUpStatus::PENDING,
                    'assigned_to' => $step->assigned_user_id,
                    'source' => PatientFollowUp::SOURCE_TEMPLATE,
                ]));

                if ($auditEach) {
                    ActivityLogger::log($followUp, 'created', null, [
                        'source' => PatientFollowUp::SOURCE_TEMPLATE,
                        'template_id' => $template->id,
                        'step_id' => $step->id,
                        'due_at' => $due->toDateTimeString(),
                    ]);
                }
                $created++;
            }
        });

        if ($created > 0) {
            Log::info('[followup] generated '.$created.' from template #'.$template->id);
        }

        return $created;
    }

    public function recalculateOpenForSurgery(SurgeryAppointment $surgery): int
    {
        $count = 0;
        $items = PatientFollowUp::query()
            ->with('templateStep')
            ->where('surgery_appointment_id', $surgery->id)
            ->open()
            ->where('source', PatientFollowUp::SOURCE_TEMPLATE)
            ->get();

        foreach ($items as $item) {
            $step = $item->templateStep;
            if (! $step) {
                continue;
            }
            $oldDue = optional($item->due_at)?->toDateTimeString();
            $this->applyStepTiming($item, $step, $surgery);
            if ($oldDue !== optional($item->due_at)?->toDateTimeString()) {
                ActivityLogger::log($item, 'rescheduled', ['due_at' => $oldDue], [
                    'due_at' => optional($item->due_at)?->toDateTimeString(),
                    'reason' => 'surgery_date_changed',
                ]);
                $count++;
            }
        }

        return $count;
    }

    public function recalculateOpenForVisit(Appointment $appointment): int
    {
        $count = 0;
        $items = PatientFollowUp::query()
            ->with('templateStep')
            ->where('appointment_id', $appointment->id)
            ->open()
            ->where('source', PatientFollowUp::SOURCE_TEMPLATE)
            ->get();

        foreach ($items as $item) {
            $step = $item->templateStep;
            if (! $step) {
                continue;
            }
            $oldDue = optional($item->due_at)?->toDateTimeString();
            $this->applyStepTiming($item, $step, $appointment);
            if ($oldDue !== optional($item->due_at)?->toDateTimeString()) {
                ActivityLogger::log($item, 'rescheduled', ['due_at' => $oldDue], [
                    'due_at' => optional($item->due_at)?->toDateTimeString(),
                    'reason' => 'appointment_date_changed',
                ]);
                $count++;
            }
        }

        return $count;
    }

    public function cancelOpenForSurgery(SurgeryAppointment $surgery, string $reason): int
    {
        return $this->cancelOpenQuery(
            PatientFollowUp::query()->where('surgery_appointment_id', $surgery->id),
            $reason
        );
    }

    public function cancelOpenForVisit(Appointment $appointment, string $reason): int
    {
        return $this->cancelOpenQuery(
            PatientFollowUp::query()->where('appointment_id', $appointment->id),
            $reason
        );
    }

    /**
     * Remove follow-ups generated from a deleted template (including retries).
     */
    public function purgeTemplateFollowUps(FollowUpTemplate $template): int
    {
        if (! PatientFollowUps::isAvailable()) {
            return 0;
        }

        $ids = PatientFollowUp::query()
            ->where(function ($query) use ($template) {
                $query->where('template_id', $template->id)
                    ->orWhere('generation_key', 'like', 'tpl:'.$template->id.':%');
            })
            ->pluck('id');

        return $this->deleteFollowUpsAndRetries($ids);
    }

    public function purgeStepFollowUps(FollowUpTemplateStep $step): int
    {
        if (! PatientFollowUps::isAvailable()) {
            return 0;
        }

        $ids = PatientFollowUp::query()
            ->where('template_step_id', $step->id)
            ->pluck('id');

        return $this->deleteFollowUpsAndRetries($ids);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int|string>  $ids
     */
    private function deleteFollowUpsAndRetries($ids): int
    {
        $all = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($all->isEmpty()) {
            return 0;
        }

        do {
            $more = PatientFollowUp::query()
                ->whereIn('parent_id', $all)
                ->whereNotIn('id', $all)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
            $all = $all->merge($more)->unique()->values();
        } while ($more->isNotEmpty());

        return (int) PatientFollowUp::query()->whereIn('id', $all)->delete();
    }

    /**
     * Bring existing «مراجعه بعدی» reminders into the follow-up board.
     */
    public function importFromReminders(): int
    {
        if (! PatientFollowUps::isAvailable()) {
            return 0;
        }
        if (! \Illuminate\Support\Facades\Schema::hasTable('follow_up_reminders')) {
            return 0;
        }

        static $imported = false;
        if ($imported) {
            return 0;
        }
        $imported = true;

        $count = 0;
        FollowUpReminder::query()
            ->orderBy('id')
            ->chunkById(200, function ($reminders) use (&$count) {
                foreach ($reminders as $reminder) {
                    if ($this->syncReminder($reminder)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function syncReminder(FollowUpReminder $reminder): bool
    {
        if (! PatientFollowUps::isAvailable() || ! $reminder->patient_id) {
            return false;
        }

        $key = 'reminder:'.$reminder->id;
        $existing = PatientFollowUp::query()->where('generation_key', $key)->first();
        $due = $this->reminderActionAt($reminder);
        $title = trim((string) $reminder->message);
        if ($title === '') {
            $title = 'یادآوری مراجعه بعدی';
        }
        $visitJalali = $reminder->due_date
            ? \App\Support\Jalali::format($reminder->due_date, 'Y/m/d')
            : null;

        if ($reminder->status === 'cancelled') {
            if ($existing && $existing->isOpen()) {
                $existing->update([
                    'status' => FollowUpStatus::CANCELLED,
                    'cancelled_at' => $reminder->cancelled_at ?: now(),
                    'cancel_reason' => $existing->cancel_reason ?: 'لغو یادآوری مراجعه',
                ]);

                return true;
            }

            return false;
        }

        if ($existing) {
            if ($existing->isOpen() && $existing->status === FollowUpStatus::PENDING) {
                $existing->fill([
                    'title' => $title,
                    'due_at' => $due,
                    'reference_at' => $reminder->due_date,
                    'description' => $visitJalali ? 'تاریخ مراجعه: '.$visitJalali : $existing->description,
                ]);
                if ($existing->isDirty()) {
                    $existing->save();
                }
            }

            return false;
        }

        PatientFollowUp::create([
            'patient_id' => $reminder->patient_id,
            'subject_type' => Patient::class,
            'subject_id' => $reminder->patient_id,
            'generation_key' => $key,
            'title' => $title,
            'description' => $visitJalali ? 'تاریخ مراجعه: '.$visitJalali : null,
            'kind' => 'reminder',
            'method' => 'sms',
            'reference_event' => 'custom',
            'reference_at' => $reminder->due_date,
            'due_at' => $due,
            'status' => FollowUpStatus::PENDING,
            'created_by' => $reminder->created_by,
            'source' => PatientFollowUp::SOURCE_REMINDER,
            'meta' => [
                'reminder_id' => $reminder->id,
                'remind_at' => optional($reminder->remind_at)?->toDateString(),
                'visit_date' => optional($reminder->due_date)?->toDateString(),
            ],
        ]);

        return true;
    }

    private function reminderActionAt(FollowUpReminder $reminder): Carbon
    {
        $action = $reminder->remind_at ?: $reminder->due_date;

        return Carbon::parse($action ?: now())->startOfDay()->setTime(9, 0, 0);
    }

    private function syncLinkedReminder(PatientFollowUp $followUp, string $action): void
    {
        $id = (int) data_get($followUp->meta, 'reminder_id');
        if ($id < 1 && is_string($followUp->generation_key) && str_starts_with($followUp->generation_key, 'reminder:')) {
            $id = (int) substr($followUp->generation_key, 9);
        }
        if ($id < 1) {
            return;
        }

        $reminder = FollowUpReminder::query()->find($id);
        if (! $reminder || $reminder->status === 'cancelled') {
            return;
        }

        if ($action === 'cancel' && $reminder->status === 'pending') {
            $reminder->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        }

        if ($action === 'complete' && $reminder->status === 'pending') {
            $reminder->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }
    }

    public function createManual(Patient $patient, array $data, ?int $userId = null): PatientFollowUp
    {
        PatientFollowUps::ensureTables();

        $due = $data['due_at'] instanceof CarbonInterface
            ? Carbon::parse($data['due_at'])
            : Carbon::parse($data['due_at']);

        $followUp = PatientFollowUp::create([
            'patient_id' => $patient->id,
            'hospital_id' => $data['hospital_id'] ?? null,
            'subject_type' => $data['subject_type'] ?? Patient::class,
            'subject_id' => $data['subject_id'] ?? $patient->id,
            'surgery_appointment_id' => $data['surgery_appointment_id'] ?? null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'visit_id' => $data['visit_id'] ?? null,
            'surgery_type_id' => $data['surgery_type_id'] ?? null,
            'surgery_subtype_id' => $data['surgery_subtype_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'kind' => $data['kind'] ?? 'custom',
            'method' => $data['method'] ?? 'call',
            'reference_event' => $data['reference_event'] ?? 'custom',
            'reference_at' => $data['reference_at'] ?? $due,
            'due_at' => $due,
            'status' => FollowUpStatus::PENDING,
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $userId,
            'source' => PatientFollowUp::SOURCE_MANUAL,
        ]);

        ActivityLogger::log($followUp, 'created', null, [
            'source' => PatientFollowUp::SOURCE_MANUAL,
            'due_at' => $due->toDateTimeString(),
        ]);

        return $followUp;
    }

    /**
     * Create relative follow-ups for many surgery/visit rows (bulk toolbox).
     *
     * @param  list<array{type?: string, subject_type?: string, id?: int, subject_id?: int}>  $items
     * @param  array{
     *   title?: string,
     *   kind?: string,
     *   method?: string,
     *   offset_amount: int,
     *   offset_unit: string,
     *   offset_direction: string,
     *   due_time?: string|null,
     *   assigned_to?: int|null,
     *   description?: string|null
     * }  $options
     * @return array{ok: int, fail: int, created_ids: list<int>}
     */
    public function createBulkRelative(array $items, array $options, ?int $userId = null): array
    {
        PatientFollowUps::ensureTables();

        $amount = max(0, (int) ($options['offset_amount'] ?? 0));
        $unit = (string) ($options['offset_unit'] ?? 'day');
        $direction = (string) ($options['offset_direction'] ?? 'before');
        if (! isset(FollowUpTiming::units()[$unit])) {
            $unit = 'day';
        }
        if (! isset(FollowUpTiming::directions()[$direction])) {
            $direction = 'before';
        }

        $kind = (string) ($options['kind'] ?? 'custom');
        $method = (string) ($options['method'] ?? 'call');
        $dueTime = trim((string) ($options['due_time'] ?? ''));
        $titleBase = trim((string) ($options['title'] ?? ''));
        if ($titleBase === '') {
            $titleBase = 'پیگیری · '.FollowUpTiming::summarize($amount, $unit, $direction);
        }

        $ok = 0;
        $fail = 0;
        $createdIds = [];

        foreach ($items as $row) {
            try {
                $type = (string) ($row['type'] ?? $row['subject_type'] ?? '');
                $id = (int) ($row['id'] ?? $row['subject_id'] ?? 0);
                if ($id <= 0) {
                    $fail++;
                    continue;
                }

                if ($type === 'surgery') {
                    $subject = SurgeryAppointment::query()->with(['patient', 'hospital'])->find($id);
                    if (! $subject || ! $subject->patient) {
                        $fail++;
                        continue;
                    }
                    $reference = $this->referenceFromBooking($subject);
                    $due = FollowUpTiming::apply($reference, $amount, $unit, $direction);
                    if ($dueTime !== '' && preg_match('/^\d{1,2}:\d{2}/', $dueTime)) {
                        [$h, $m] = array_map('intval', explode(':', substr($dueTime, 0, 5)));
                        $due->setTime($h, $m, 0);
                    }
                    $created = $this->createManual($subject->patient, [
                        'title' => $titleBase,
                        'description' => $options['description'] ?? null,
                        'kind' => $kind,
                        'method' => $method,
                        'hospital_id' => $subject->hospital_id,
                        'assigned_to' => $options['assigned_to'] ?? null,
                        'due_at' => $due,
                        'surgery_appointment_id' => $subject->id,
                        'surgery_type_id' => $subject->surgery_type_id,
                        'surgery_subtype_id' => $subject->surgery_subtype_id,
                        'subject_type' => SurgeryAppointment::class,
                        'subject_id' => $subject->id,
                        'reference_event' => 'surgery_date',
                        'reference_at' => $reference,
                    ], $userId);
                    $createdIds[] = $created->id;
                    $ok++;
                    continue;
                }

                if ($type === 'visit') {
                    $subject = Appointment::query()->with('patient')->find($id);
                    if (! $subject || ! $subject->patient) {
                        $fail++;
                        continue;
                    }
                    $reference = $this->referenceFromBooking($subject);
                    $due = FollowUpTiming::apply($reference, $amount, $unit, $direction);
                    if ($dueTime !== '' && preg_match('/^\d{1,2}:\d{2}/', $dueTime)) {
                        [$h, $m] = array_map('intval', explode(':', substr($dueTime, 0, 5)));
                        $due->setTime($h, $m, 0);
                    }
                    $created = $this->createManual($subject->patient, [
                        'title' => $titleBase,
                        'description' => $options['description'] ?? null,
                        'kind' => $kind,
                        'method' => $method,
                        'hospital_id' => null,
                        'assigned_to' => $options['assigned_to'] ?? null,
                        'due_at' => $due,
                        'appointment_id' => $subject->id,
                        'subject_type' => Appointment::class,
                        'subject_id' => $subject->id,
                        'reference_event' => 'appointment_date',
                        'reference_at' => $reference,
                    ], $userId);
                    $createdIds[] = $created->id;
                    $ok++;
                    continue;
                }

                $fail++;
            } catch (\Throwable $e) {
                report($e);
                $fail++;
            }
        }

        return [
            'ok' => $ok,
            'fail' => $fail,
            'created_ids' => $createdIds,
        ];
    }

    private function referenceFromBooking(Appointment|SurgeryAppointment $item): Carbon
    {
        $date = Carbon::parse($item->scheduled_date)->startOfDay();
        $raw = trim((string) ($item->scheduled_time ?? ''));
        if ($raw !== '' && preg_match('/^\d{1,2}:\d{2}/', $raw)) {
            [$h, $m] = array_map('intval', explode(':', substr($raw, 0, 5)));
            $date->setTime($h, $m, 0);
        } else {
            $date->setTime(9, 0, 0);
        }

        return $date;
    }

    public function start(PatientFollowUp $followUp, ?int $userId = null): PatientFollowUp
    {
        if (! $followUp->isOpen()) {
            return $followUp;
        }

        $from = $followUp->status;
        $followUp->update(['status' => FollowUpStatus::IN_PROGRESS]);
        ActivityLogger::log($followUp, 'status_changed', ['status' => $from], [
            'status' => FollowUpStatus::IN_PROGRESS,
            'by' => $userId,
        ]);

        return $followUp->fresh();
    }

    public function complete(PatientFollowUp $followUp, array $data, ?int $userId = null): PatientFollowUp
    {
        $from = $followUp->getOriginal();
        $followUp->update([
            'status' => FollowUpStatus::DONE,
            'outcome' => $data['outcome'] ?? $followUp->outcome,
            'outcome_notes' => $data['outcome_notes'] ?? $followUp->outcome_notes,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $followUp->notes,
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);

        ActivityLogger::log($followUp, 'completed', [
            'status' => $from['status'] ?? null,
            'outcome' => $from['outcome'] ?? null,
        ], [
            'status' => FollowUpStatus::DONE,
            'outcome' => $followUp->outcome,
        ]);

        $this->syncLinkedReminder($followUp, 'complete');

        return $followUp->fresh();
    }

    public function fail(PatientFollowUp $followUp, string $status, array $data, ?int $userId = null): PatientFollowUp
    {
        $status = in_array($status, [FollowUpStatus::REJECTED, FollowUpStatus::FAILED], true)
            ? $status
            : FollowUpStatus::FAILED;

        $from = $followUp->status;
        $followUp->update([
            'status' => $status,
            'outcome' => $data['outcome'] ?? $followUp->outcome,
            'outcome_notes' => $data['outcome_notes'] ?? $followUp->outcome_notes,
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);

        ActivityLogger::log($followUp, 'status_changed', ['status' => $from], [
            'status' => $status,
        ]);

        return $followUp->fresh();
    }

    public function cancel(PatientFollowUp $followUp, ?string $reason = null, ?int $userId = null): PatientFollowUp
    {
        if ($followUp->status === FollowUpStatus::DONE) {
            return $followUp;
        }

        $from = $followUp->status;
        $followUp->update([
            'status' => FollowUpStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        ActivityLogger::log($followUp, 'cancelled', ['status' => $from], [
            'status' => FollowUpStatus::CANCELLED,
            'reason' => $reason,
            'by' => $userId,
        ]);

        $this->syncLinkedReminder($followUp, 'cancel');

        return $followUp->fresh();
    }

    public function assign(PatientFollowUp $followUp, ?int $userId, ?int $actorId = null): PatientFollowUp
    {
        $from = $followUp->assigned_to;
        $followUp->update(['assigned_to' => $userId]);
        ActivityLogger::log($followUp, 'assigned', ['assigned_to' => $from], [
            'assigned_to' => $userId,
            'by' => $actorId,
        ]);

        return $followUp->fresh();
    }

    public function reschedule(PatientFollowUp $followUp, CarbonInterface $dueAt, ?int $actorId = null): PatientFollowUp
    {
        if ($followUp->status === FollowUpStatus::DONE) {
            return $followUp;
        }

        $from = optional($followUp->due_at)?->toDateTimeString();
        $followUp->update(['due_at' => Carbon::parse($dueAt)]);
        ActivityLogger::log($followUp, 'rescheduled', ['due_at' => $from], [
            'due_at' => $followUp->due_at?->toDateTimeString(),
            'by' => $actorId,
        ]);

        return $followUp->fresh();
    }

    public function retry(PatientFollowUp $followUp, CarbonInterface $dueAt, array $data = [], ?int $userId = null): PatientFollowUp
    {
        $retry = PatientFollowUp::create([
            'patient_id' => $followUp->patient_id,
            'hospital_id' => $followUp->hospital_id,
            'subject_type' => $followUp->subject_type,
            'subject_id' => $followUp->subject_id,
            'surgery_appointment_id' => $followUp->surgery_appointment_id,
            'appointment_id' => $followUp->appointment_id,
            'visit_id' => $followUp->visit_id,
            'surgery_type_id' => $followUp->surgery_type_id,
            'surgery_subtype_id' => $followUp->surgery_subtype_id,
            'parent_id' => $followUp->id,
            'title' => $data['title'] ?? ('پیگیری مجدد · '.$followUp->title),
            'description' => $data['description'] ?? $followUp->description,
            'notes' => $data['notes'] ?? null,
            'kind' => $data['kind'] ?? $followUp->kind,
            'method' => $data['method'] ?? $followUp->method,
            'reference_event' => 'custom',
            'reference_at' => now(),
            'due_at' => Carbon::parse($dueAt),
            'status' => FollowUpStatus::PENDING,
            'assigned_to' => $data['assigned_to'] ?? $followUp->assigned_to,
            'created_by' => $userId,
            'source' => PatientFollowUp::SOURCE_RETRY,
            'meta' => [
                'from_follow_up_id' => $followUp->id,
                'from_outcome' => $followUp->outcome,
            ],
        ]);

        ActivityLogger::log($retry, 'created', null, [
            'source' => PatientFollowUp::SOURCE_RETRY,
            'parent_id' => $followUp->id,
            'due_at' => $retry->due_at?->toDateTimeString(),
        ]);

        return $retry;
    }

    /**
     * @param  SurgeryAppointment|Appointment  $subject
     */
    private function applyStepTiming(PatientFollowUp $followUp, FollowUpTemplateStep $step, $subject): void
    {
        if (! $followUp->isOpen()) {
            return;
        }

        $reference = $this->resolveReference($step->reference_event, $subject, $followUp->reference_at);
        $due = FollowUpTiming::apply(
            $reference,
            (int) $step->offset_amount,
            (string) $step->offset_unit,
            (string) $step->offset_direction
        );

        $followUp->fill([
            'reference_at' => $reference,
            'due_at' => $due,
            'title' => $followUp->title ?: $step->title,
            'kind' => $followUp->kind ?: $step->kind,
            'method' => $followUp->method ?: $step->method,
        ]);

        if ($followUp->isDirty()) {
            $followUp->save();
        }
    }

    /**
     * @param  SurgeryAppointment|Appointment|Visit|Patient|null  $subject
     */
    private function resolveReference(string $event, $subject, $fallback = null): Carbon
    {
        if ($event === 'custom' && $fallback) {
            return Carbon::parse($fallback);
        }

        if ($subject instanceof SurgeryAppointment) {
            if (in_array($event, ['surgery_date', 'appointment_date', 'visit_date'], true)) {
                return $this->combineDateTime($subject->scheduled_date, $subject->scheduled_time);
            }
        }

        if ($subject instanceof Appointment) {
            if (in_array($event, ['appointment_date', 'visit_date', 'surgery_date'], true)) {
                return $this->combineDateTime($subject->scheduled_date, $subject->scheduled_time);
            }
        }

        if ($subject instanceof Visit) {
            return Carbon::parse($subject->created_at ?? now());
        }

        if ($subject instanceof Patient && $event === 'patient_created') {
            return Carbon::parse($subject->created_at ?? now());
        }

        if ($subject && isset($subject->patient) && $event === 'patient_created') {
            $patient = $subject->patient;
            if ($patient?->created_at) {
                return Carbon::parse($patient->created_at);
            }
        }

        if ($fallback) {
            return Carbon::parse($fallback);
        }

        return now();
    }

    private function combineDateTime($date, ?string $time): Carbon
    {
        $base = $date ? Carbon::parse($date) : now();
        $slot = trim((string) $time);
        if ($slot !== '' && preg_match('/^(\d{1,2}):(\d{2})/', $slot, $m)) {
            $base->setTime((int) $m[1], (int) $m[2], 0);
        } else {
            $base->setTime(9, 0, 0);
        }

        return $base;
    }

    /**
     * @param  SurgeryAppointment|Appointment  $subject
     * @return array<string, mixed>
     */
    private function basePayloadFromSubject($subject): array
    {
        if ($subject instanceof SurgeryAppointment) {
            return [
                'patient_id' => $subject->patient_id,
                'hospital_id' => $subject->hospital_id,
                'subject_type' => SurgeryAppointment::class,
                'subject_id' => $subject->id,
                'surgery_appointment_id' => $subject->id,
                'appointment_id' => null,
                'surgery_type_id' => $subject->surgery_type_id,
                'surgery_subtype_id' => $subject->surgery_subtype_id,
                'created_by' => $subject->created_by,
            ];
        }

        return [
            'patient_id' => $subject->patient_id,
            'hospital_id' => null,
            'subject_type' => Appointment::class,
            'subject_id' => $subject->id,
            'surgery_appointment_id' => null,
            'appointment_id' => $subject->id,
            'surgery_type_id' => null,
            'surgery_subtype_id' => null,
            'created_by' => $subject->created_by ?? null,
        ];
    }

    /**
     * @param  SurgeryAppointment|Appointment  $subject
     */
    private function generationKey(FollowUpTemplate $template, FollowUpTemplateStep $step, $subject): string
    {
        $type = $subject instanceof SurgeryAppointment ? 'surgery' : 'visit';

        return 'tpl:'.$template->id.':step:'.$step->id.':'.$type.':'.$subject->id;
    }

    private function removeStaleTemplateFollowUpsForSurgery(
        SurgeryAppointment $surgery,
        FollowUpTemplate $selectedTemplate,
    ): void {
        PatientFollowUp::query()
            ->where('surgery_appointment_id', $surgery->id)
            ->where('source', PatientFollowUp::SOURCE_TEMPLATE)
            ->where('template_id', '!=', $selectedTemplate->id)
            ->open()
            ->delete();
    }

    private function cancelOpenQuery($query, string $reason): int
    {
        $items = $query->open()->get();
        $count = 0;
        foreach ($items as $item) {
            $this->cancel($item, $reason);
            $count++;
        }

        return $count;
    }

    public static function outcomeNeedsRetry(?string $outcome): bool
    {
        return in_array($outcome, ['no_answer', 'retry', 'reschedule'], true);
    }

    /**
     * @return array<string, string>
     */
    public static function kinds(): array
    {
        return PatientFollowUps::catalogMap(FollowUpCatalogItem::GROUP_KIND);
    }

    /**
     * @return array<string, string>
     */
    public static function methods(): array
    {
        return PatientFollowUps::catalogMap(FollowUpCatalogItem::GROUP_METHOD);
    }

    /**
     * @return array<string, string>
     */
    public static function outcomes(): array
    {
        return PatientFollowUps::catalogMap(FollowUpCatalogItem::GROUP_OUTCOME);
    }
}
