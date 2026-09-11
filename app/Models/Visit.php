<?php

namespace App\Models;

use App\Models\Concerns\HasHisSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    use HasHisSource;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'surgery_appointment_id',
        'created_by',
        'updated_by',
        'history',
        'examination',
        'diagnosis',
        'treatment',
        'eye_side',
        'va_right',
        'va_left',
        'iop_right',
        'iop_left',
        'next_instruction',
        'drawing_path',
        'voice_path',
        'his_admission_id',
        'source',
        'his_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'his_synced_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function surgeryAppointment(): BelongsTo
    {
        return $this->belongsTo(SurgeryAppointment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function wasEdited(): bool
    {
        return $this->updated_by !== null
            && $this->updated_at
            && $this->created_at
            && $this->updated_at->gt($this->created_at);
    }

    public function hasExamContent(): bool
    {
        return filled($this->history)
            || filled($this->examination)
            || filled($this->diagnosis)
            || filled($this->treatment)
            || filled($this->next_instruction)
            || filled($this->eye_side)
            || filled($this->va_right)
            || filled($this->va_left)
            || filled($this->iop_right)
            || filled($this->iop_left);
    }
}
