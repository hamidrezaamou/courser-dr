<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurgeryType extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
        'cooldown_days',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'cooldown_days' => 'integer',
        ];
    }

    public static function ensureCooldownColumn(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('surgery_types')) {
                return;
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('surgery_types', 'cooldown_days')) {
                \Illuminate\Support\Facades\Schema::table('surgery_types', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->unsignedSmallInteger('cooldown_days')->nullable()->after('sort_order');
                });
            }
        } catch (\Throwable) {
            $ensured = false;
        }
    }

    public function subtypes(): HasMany
    {
        return $this->hasMany(SurgerySubtype::class)->ordered();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
