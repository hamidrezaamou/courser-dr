<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientSurgeryChecklistItem extends Model
{
    protected $fillable = [
        'checklist_id',
        'label',
        'sort_order',
        'checked_at',
        'checked_by',
        'is_custom',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'is_custom' => 'boolean',
        ];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(PatientSurgeryChecklist::class, 'checklist_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
