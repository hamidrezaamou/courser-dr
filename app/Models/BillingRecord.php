<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BillingRecord extends Model
{
    protected $fillable = [
        'billable_type',
        'billable_id',
        'patient_id',
        'service_tariff_id',
        'fee_amount',
        'insurance_share',
        'patient_share',
        'settlement_status',
    ];

    protected function casts(): array
    {
        return [
            'fee_amount' => 'integer',
            'insurance_share' => 'integer',
            'patient_share' => 'integer',
        ];
    }

    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(ServiceTariff::class, 'service_tariff_id');
    }
}
