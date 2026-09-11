<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurgeryProgramGroupItem extends Model
{
    protected $fillable = [
        'surgery_program_group_id',
        'surgery_type_id',
        'surgery_subtype_id',
    ];

    protected function casts(): array
    {
        return [
            'surgery_type_id' => 'integer',
            'surgery_subtype_id' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SurgeryProgramGroup::class, 'surgery_program_group_id');
    }

    public function surgeryType(): BelongsTo
    {
        return $this->belongsTo(SurgeryType::class);
    }

    public function surgerySubtype(): BelongsTo
    {
        return $this->belongsTo(SurgerySubtype::class);
    }

    public function coversSubtype(?int $subtypeId): bool
    {
        if ($this->surgery_subtype_id === null) {
            return true;
        }

        return $subtypeId !== null && (int) $this->surgery_subtype_id === $subtypeId;
    }
}
