<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpReminder extends Model
{
    protected $fillable = [
        'patient_id',
        'created_by',
        'due_date',
        'remind_at',
        'status',
        'message',
        'sent_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'remind_at' => 'date',
            'sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
}
