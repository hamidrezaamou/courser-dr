<?php

namespace App\Models;

use App\Models\Concerns\HasHisSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialTransaction extends Model
{
    use HasHisSource;

    protected $fillable = [
        'patient_id',
        'type',
        'amount',
        'method',
        'label',
        'reference_type',
        'reference_id',
        'recorded_by',
        'transaction_date',
        'notes',
        'his_transaction_id',
        'source',
        'his_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'integer',
            'his_synced_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
