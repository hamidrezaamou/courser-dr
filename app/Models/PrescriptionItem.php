<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $fillable = [
        'prescription_id',
        'drug_id',
        'drug_name',
        'usage_type',
        'dosage',
        'frequency',
        'meal_timing',
        'duration',
        'instructions',
        'sort_order',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function mealTimingLabel(): string
    {
        return match ($this->meal_timing) {
            'before_meal' => 'قبل غذا',
            'after_meal' => 'بعد غذا',
            'with_meal' => 'همراه غذا',
            'empty_stomach' => 'ناشتا',
            default => $this->meal_timing ?: '—',
        };
    }
}
