<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurgeryChecklistTemplate extends Model
{
    protected $fillable = [
        'surgery_type_id',
        'surgery_subtype_id',
    ];

    public function surgeryType(): BelongsTo
    {
        return $this->belongsTo(SurgeryType::class);
    }

    public function surgerySubtype(): BelongsTo
    {
        return $this->belongsTo(SurgerySubtype::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SurgeryChecklistTemplateItem::class, 'template_id')->orderBy('sort_order');
    }
}
