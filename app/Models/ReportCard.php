<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCard extends Model
{
    protected $fillable = [
        'title',
        'card_type',
        'source',
        'metric',
        'filter_service',
        'filter_type',
        'partner_name',
        'partner_percent',
        'range_mode',
        'from_date',
        'to_date',
        'color',
        'text_color',
        'show_count',
        'show_avg',
        'show_total',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'partner_percent' => 'integer',
            'sort_order' => 'integer',
            'show_count' => 'boolean',
            'show_avg' => 'boolean',
            'show_total' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isPartner(): bool
    {
        return $this->card_type === 'partner';
    }
}
