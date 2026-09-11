<?php

namespace App\Models;

use App\Support\FollowUpTiming;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpTemplateStep extends Model
{
    protected $fillable = [
        'template_id',
        'title',
        'description',
        'kind',
        'method',
        'offset_amount',
        'offset_unit',
        'offset_direction',
        'reference_event',
        'assigned_user_id',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'offset_amount' => 'integer',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FollowUpTemplate::class, 'template_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function timingLabel(): string
    {
        return FollowUpTiming::summarize(
            (int) $this->offset_amount,
            (string) $this->offset_unit,
            (string) $this->offset_direction
        );
    }
}
