<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Where the importer got to for one resource.
 *
 * The website owns the watermark rather than the agent, so a reinstalled or
 * wiped agent picks up exactly where the previous one stopped instead of
 * replaying the whole history.
 */
class HisSyncState extends Model
{
    protected $fillable = [
        'resource',
        'last_id',
        'last_changed_at',
        'last_run_at',
        'last_success_at',
        'last_status',
        'last_message',
        'rows_imported',
    ];

    protected function casts(): array
    {
        return [
            'last_changed_at' => 'datetime',
            'last_run_at' => 'datetime',
            'last_success_at' => 'datetime',
            'rows_imported' => 'integer',
        ];
    }

    public static function forResource(string $resource): self
    {
        return static::query()->firstOrCreate(['resource' => $resource]);
    }

    public function isStale(int $minutes = 30): bool
    {
        return $this->last_success_at === null
            || $this->last_success_at->lt(now()->subMinutes($minutes));
    }
}
