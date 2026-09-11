<?php

namespace App\Models;

use App\Models\Concerns\HasHisSource;
use App\Services\PatientFollowUpService;
use App\Support\BookingReportNote;
use App\Support\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SurgeryAppointment extends Model
{
    use HasHisSource;

    protected $fillable = [
        'patient_id',
        'hospital_id',
        'created_by',
        'patient_name',
        'national_code',
        'mobile',
        'age',
        'surgery_type',
        'surgery_type_id',
        'surgery_subtype_id',
        'eye_side',
        'scheduled_date',
        'scheduled_time',
        'surgeon_name',
        'notes',
        'status',
        'is_exception',
        'is_emergency',
        'mobile_secondary',
        'reminder_sent_at',
        'his_surgery_id',
        'source',
        'his_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'reminder_sent_at' => 'datetime',
            'his_synced_at' => 'datetime',
            'is_exception' => 'boolean',
            'is_emergency' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (SurgeryAppointment $surgery): void {
            if ($surgery->wasRecentlyCreated || $surgery->wasChanged('notes')) {
                BookingReportNote::sync($surgery, 'surgery');
            }
        });

        static::created(function (SurgeryAppointment $surgery): void {
            try {
                app(PatientFollowUpService::class)->ensureForSurgery($surgery);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        static::updated(function (SurgeryAppointment $surgery): void {
            if ($surgery->wasChanged(['scheduled_date', 'scheduled_time', 'hospital_id', 'surgery_type_id', 'surgery_subtype_id', 'status'])) {
                try {
                    app(PatientFollowUpService::class)->syncSurgery($surgery);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function surgeryType(): BelongsTo
    {
        return $this->belongsTo(SurgeryType::class);
    }

    public function surgerySubtype(): BelongsTo
    {
        return $this->belongsTo(SurgerySubtype::class);
    }

    public function checklist(): HasOne
    {
        return $this->hasOne(PatientSurgeryChecklist::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(PatientFollowUp::class);
    }

    public function visit(): HasOne
    {
        return $this->hasOne(Visit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeHoldingSlot(Builder $query): Builder
    {
        return $query->whereIn('status', BookingStatus::holding());
    }

    public function statusLabel(): string
    {
        return BookingStatus::label($this->status);
    }

    /**
     * @return list<string>
     */
    public function allowedStatusTransitions(): array
    {
        return BookingStatus::transitionsFrom($this->status);
    }

    public function holdsSlot(): bool
    {
        return in_array($this->status, BookingStatus::holding(), true);
    }
}
