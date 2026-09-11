<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientSurgeryChecklist extends Model
{
    protected $fillable = [
        'patient_id',
        'surgery_appointment_id',
        'surgery_type_id',
        'surgery_subtype_id',
        'title',
        'created_by',
        'saved_at',
    ];

    protected function casts(): array
    {
        return [
            'saved_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function surgeryAppointment(): BelongsTo
    {
        return $this->belongsTo(SurgeryAppointment::class);
    }

    public function surgeryType(): BelongsTo
    {
        return $this->belongsTo(SurgeryType::class);
    }

    public function surgerySubtype(): BelongsTo
    {
        return $this->belongsTo(SurgerySubtype::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PatientSurgeryChecklistItem::class, 'checklist_id')->orderBy('sort_order');
    }
}
