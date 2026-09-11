<?php

namespace App\Models;

use App\Support\FollowUpStatus;
use App\Support\Jalali;
use App\Support\PatientFollowUps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PatientFollowUp extends Model
{
    public const SOURCE_TEMPLATE = 'template';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_RETRY = 'retry';

    public const SOURCE_REMINDER = 'reminder';

    protected $fillable = [
        'patient_id',
        'hospital_id',
        'subject_type',
        'subject_id',
        'surgery_appointment_id',
        'appointment_id',
        'visit_id',
        'surgery_type_id',
        'surgery_subtype_id',
        'template_id',
        'template_step_id',
        'generation_key',
        'parent_id',
        'title',
        'description',
        'notes',
        'kind',
        'method',
        'reference_event',
        'reference_at',
        'due_at',
        'status',
        'outcome',
        'outcome_notes',
        'assigned_to',
        'created_by',
        'completed_by',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'source',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'reference_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function surgeryAppointment(): BelongsTo
    {
        return $this->belongsTo(SurgeryAppointment::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function surgeryType(): BelongsTo
    {
        return $this->belongsTo(SurgeryType::class);
    }

    public function surgerySubtype(): BelongsTo
    {
        return $this->belongsTo(SurgerySubtype::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FollowUpTemplate::class, 'template_id');
    }

    public function templateStep(): BelongsTo
    {
        return $this->belongsTo(FollowUpTemplateStep::class, 'template_step_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', FollowUpStatus::open());
    }

    public function scopeTerminal(Builder $query): Builder
    {
        return $query->whereIn('status', FollowUpStatus::terminal());
    }

    public function isOpen(): bool
    {
        return FollowUpStatus::isOpen((string) $this->status);
    }

    public function isLocked(): bool
    {
        return FollowUpStatus::isTerminal((string) $this->status);
    }

    public function displayStatus(): string
    {
        return FollowUpStatus::display((string) $this->status, $this->due_at);
    }

    public function statusLabel(): string
    {
        return FollowUpStatus::label($this->displayStatus());
    }

    public function kindLabel(): string
    {
        return PatientFollowUps::catalogLabel(FollowUpCatalogItem::GROUP_KIND, $this->kind);
    }

    public function methodLabel(): string
    {
        return PatientFollowUps::catalogLabel(FollowUpCatalogItem::GROUP_METHOD, $this->method);
    }

    public function outcomeLabel(): string
    {
        return PatientFollowUps::catalogLabel(FollowUpCatalogItem::GROUP_OUTCOME, $this->outcome);
    }

    public function dueJalali(?string $format = 'Y/m/d H:i'): string
    {
        return $this->due_at ? Jalali::format($this->due_at, $format) : '—';
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_TEMPLATE => 'از الگو',
            self::SOURCE_RETRY => 'پیگیری مجدد',
            self::SOURCE_REMINDER => 'یادآوری مراجعه',
            default => 'دستی',
        };
    }
}
