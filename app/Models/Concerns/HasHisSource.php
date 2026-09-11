<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Marks records that were imported from the clinic's HIS.
 *
 * HIS is the source of truth for these, so the website must not let staff edit
 * them: the next sync would silently overwrite the change and the user would
 * never learn their edit was lost.
 */
trait HasHisSource
{
    public function isFromHis(): bool
    {
        return $this->source === 'his';
    }

    public function isEditableHere(): bool
    {
        return ! $this->isFromHis();
    }

    public function scopeFromHis(Builder $query): Builder
    {
        return $query->where('source', 'his');
    }

    public function scopeEnteredHere(Builder $query): Builder
    {
        return $query->where('source', '!=', 'his');
    }
}
