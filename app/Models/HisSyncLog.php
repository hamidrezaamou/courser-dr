<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HisSyncLog extends Model
{
    protected $fillable = [
        'resource',
        'batch_id',
        'received',
        'created',
        'updated',
        'skipped',
        'failed',
        'duration_ms',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'received' => 'integer',
            'created' => 'integer',
            'updated' => 'integer',
            'skipped' => 'integer',
            'failed' => 'integer',
            'duration_ms' => 'integer',
        ];
    }
}
