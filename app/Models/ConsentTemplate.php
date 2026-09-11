<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentTemplate extends Model
{
    protected $fillable = [
        'title',
        'kind',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function consents(): HasMany
    {
        return $this->hasMany(PatientConsent::class);
    }
}
