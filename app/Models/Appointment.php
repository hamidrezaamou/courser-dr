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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Appointment extends Model
{
    use HasHisSource;

    protected $fillable = [
        'patient_id',
        'created_by',
        'patient_name',
        'national_code',
        'mobile',
        'mobile_secondary',
        'age',
        'visit_type',
        'scheduled_date',
        'scheduled_time',
        'reason',
        'notes',
        'status',
        'reminder_sent_at',
        'his_appointment_id',
        'source',
        'his_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'reminder_sent_at' => 'datetime',
            'his_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Appointment $appointment): void {
            if ($appointment->wasRecentlyCreated || $appointment->wasChanged('notes')) {
                BookingReportNote::sync($appointment, 'visit');
            }
        });

        static::created(function (Appointment $appointment): void {
            try {
                app(PatientFollowUpService::class)->ensureForVisit($appointment);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        static::updated(function (Appointment $appointment): void {
            if ($appointment->wasChanged(['scheduled_date', 'scheduled_time', 'status'])) {
                try {
                    app(PatientFollowUpService::class)->syncVisit($appointment);
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function visit(): HasOne
    {
        return $this->hasOne(Visit::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(PatientFollowUp::class);
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

    public static function ensureMobileSecondaryColumn(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        if (Schema::hasColumn((new static)->getTable(), 'mobile_secondary')) {
            return;
        }

        try {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('mobile_secondary', 20)->nullable()->after('mobile');
            });
        } catch (\Throwable $e) {
            $ensured = false;
        }
    }
}
