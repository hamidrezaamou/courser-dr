<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceTariff extends Model
{
    protected $fillable = [
        'name',
        'kind',
        'amount',
        'insurance_coverage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'insurance_coverage' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function billingRecords(): HasMany
    {
        return $this->hasMany(BillingRecord::class);
    }

    public function patientShareAmount(): int
    {
        $coverage = max(0, min(100, (int) $this->insurance_coverage));

        return (int) round($this->amount * (100 - $coverage) / 100);
    }

    public function insuranceShareAmount(): int
    {
        return max(0, (int) $this->amount - $this->patientShareAmount());
    }
}
