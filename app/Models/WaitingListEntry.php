<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WaitingListEntry extends Model
{
    protected $fillable = [
        'patient_id',
        'patient_name',
        'mobile',
        'national_code',
        'kind',
        'preferred_date',
        'status',
        'priority',
        'notes',
        'converted_to_type',
        'converted_to_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'priority' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedTo(): MorphTo
    {
        return $this->morphTo();
    }
}
