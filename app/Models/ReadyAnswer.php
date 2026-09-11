<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadyAnswer extends Model
{
    protected $fillable = [
        'title',
        'category',
        'body',
        'is_pinned',
        'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term) {
            $query->where('title', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%")
                ->orWhere('category', 'like', "%{$term}%");
        });
    }

    /** Pinned templates first, then the ones the staff actually reaches for. */
    public function scopeOrdered(Builder $query, string $sort = 'smart'): Builder
    {
        return match ($sort) {
            'newest' => $query->orderByDesc('created_at'),
            'used' => $query->orderByDesc('usage_count')->orderByDesc('created_at'),
            default => $query->orderByDesc('is_pinned')
                ->orderByDesc('usage_count')
                ->orderByDesc('created_at'),
        };
    }

    /** Usage is telemetry, so it must not bump updated_at and reshuffle the "newest" sort. */
    public function markUsed(): void
    {
        $this->timestamps = false;

        $this->forceFill([
            'usage_count' => $this->usage_count + 1,
            'last_used_at' => now(),
        ])->saveQuietly();

        $this->timestamps = true;
    }
}
