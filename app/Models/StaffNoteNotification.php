<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffNoteNotification extends Model
{
    protected $fillable = [
        'user_id',
        'actor_id',
        'patient_id',
        'internal_note_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(InternalNote::class, 'internal_note_id');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
