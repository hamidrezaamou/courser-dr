<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalNote extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'user_id',
        'note',
    ];

    /**
     * Get the patient that owns the note.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the user who wrote the note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
