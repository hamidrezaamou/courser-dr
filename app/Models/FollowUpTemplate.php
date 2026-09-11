<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FollowUpTemplate extends Model
{
    public const APPLIES_SURGERY = 'surgery';

    public const APPLIES_VISIT = 'visit';

    protected $fillable = [
        'name',
        'description',
        'applies_to',
        'hospital_id',
        'surgery_type_id',
        'surgery_subtype_id',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function surgeryType(): BelongsTo
    {
        return $this->belongsTo(SurgeryType::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function surgerySubtype(): BelongsTo
    {
        return $this->belongsTo(SurgerySubtype::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(FollowUpTemplateStep::class, 'template_id')->orderBy('sort_order')->orderBy('id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(PatientFollowUp::class, 'template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function bindingLabel(): string
    {
        $hospital = $this->hospital?->name ?: 'همه بیمارستان‌ها';
        if ($this->applies_to === self::APPLIES_VISIT) {
            return 'ویزیت · '.$hospital;
        }

        $type = $this->surgeryType?->name ?: 'نوع عمل';
        $operation = $this->surgerySubtype
            ? $type.' · '.$this->surgerySubtype->name
            : $type.' (عمومی)';

        return $operation.' · '.$hospital;
    }
}
