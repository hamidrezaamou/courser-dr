<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SurgeryProgramGroup extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @var Collection<int, SurgeryProgramGroupItem>|null */
    protected static ?Collection $itemCache = null;

    public function items(): HasMany
    {
        return $this->hasMany(SurgeryProgramGroupItem::class, 'surgery_program_group_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(SurgeryProgramGroupDay::class, 'surgery_program_group_id');
    }

    public static function flushCache(): void
    {
        self::$itemCache = null;
    }

    public static function ensureTables(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            if (! Schema::hasTable('surgery_program_groups')) {
                Schema::create('surgery_program_groups', function (Blueprint $table) {
                    $table->id();
                    $table->string('name', 120);
                    $table->boolean('is_active')->default(true);
                    $table->unsignedSmallInteger('sort_order')->default(0);
                    $table->timestamps();
                });
            }
            if (! Schema::hasTable('surgery_program_group_items')) {
                Schema::create('surgery_program_group_items', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('surgery_program_group_id');
                    $table->unsignedBigInteger('surgery_type_id');
                    $table->unsignedBigInteger('surgery_subtype_id')->nullable();
                    $table->timestamps();
                    $table->index(['surgery_program_group_id']);
                    $table->index(['surgery_type_id', 'surgery_subtype_id'], 'spgi_type_subtype_idx');
                });
            }
            if (! Schema::hasTable('surgery_program_group_days')) {
                Schema::create('surgery_program_group_days', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('surgery_program_group_id');
                    $table->unsignedBigInteger('hospital_id');
                    $table->string('date_key', 20);
                    $table->unsignedSmallInteger('total_slots')->default(10);
                    $table->timestamps();
                    $table->unique(
                        ['surgery_program_group_id', 'hospital_id', 'date_key'],
                        'spgd_group_hospital_date_unique'
                    );
                });
            }
        } catch (\Throwable $e) {
            $ensured = false;
        }
    }

    /**
     * @return Collection<int, SurgeryProgramGroupItem>
     */
    public static function primedItems(): Collection
    {
        self::ensureTables();
        if (self::$itemCache !== null) {
            return self::$itemCache;
        }
        if (! Schema::hasTable('surgery_program_group_items')) {
            self::$itemCache = collect();

            return self::$itemCache;
        }

        self::$itemCache = SurgeryProgramGroupItem::query()
            ->with('group')
            ->get();

        return self::$itemCache;
    }

    public static function findFor(?int $typeId, ?int $subtypeId): ?self
    {
        if (! $typeId) {
            return null;
        }

        $items = self::primedItems();
        if ($subtypeId) {
            $exact = $items->first(function (SurgeryProgramGroupItem $item) use ($typeId, $subtypeId) {
                return (int) $item->surgery_type_id === $typeId
                    && (int) $item->surgery_subtype_id === $subtypeId;
            });
            if ($exact && $exact->group && $exact->group->is_active) {
                $group = $exact->group;
                $group->setRelation(
                    'items',
                    $items->where('surgery_program_group_id', $group->id)->values()
                );

                return $group;
            }
        }

        $whole = $items->first(function (SurgeryProgramGroupItem $item) use ($typeId) {
            return (int) $item->surgery_type_id === $typeId
                && $item->surgery_subtype_id === null;
        });
        if ($whole && $whole->group && $whole->group->is_active) {
            $group = $whole->group;
            $group->setRelation(
                'items',
                $items->where('surgery_program_group_id', $group->id)->values()
            );

            return $group;
        }

        return null;
    }

    /**
     * Subtypes of this type that belong to any program group (as a specific subtype, not whole-type).
     *
     * @return list<int>
     */
    public static function groupedSubtypeIdsForType(int $typeId): array
    {
        return self::primedItems()
            ->filter(function (SurgeryProgramGroupItem $item) use ($typeId) {
                return (int) $item->surgery_type_id === $typeId
                    && $item->surgery_subtype_id
                    && $item->group
                    && $item->group->is_active;
            })
            ->map(fn (SurgeryProgramGroupItem $item) => (int) $item->surgery_subtype_id)
            ->unique()
            ->values()
            ->all();
    }

    public function applyToAppointmentQuery(Builder $query): Builder
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return $query->where(function (Builder $outer) use ($items) {
            foreach ($items as $item) {
                $outer->orWhere(function (Builder $q) use ($item) {
                    $q->where('surgery_type_id', $item->surgery_type_id);
                    if ($item->surgery_subtype_id) {
                        $q->where('surgery_subtype_id', $item->surgery_subtype_id);
                    }
                });
            }
            if ($items->isEmpty()) {
                $outer->whereRaw('0 = 1');
            }
        });
    }

    /**
     * @param  list<array{surgery_type_id: int, surgery_subtype_id: int|null}>  $members
     */
    public function syncMembers(array $members): void
    {
        $this->items()->delete();
        foreach ($members as $member) {
            $typeId = (int) ($member['surgery_type_id'] ?? 0);
            if ($typeId < 1) {
                continue;
            }
            $this->items()->create([
                'surgery_type_id' => $typeId,
                'surgery_subtype_id' => ! empty($member['surgery_subtype_id'])
                    ? (int) $member['surgery_subtype_id']
                    : null,
            ]);
        }
        self::flushCache();
    }
}
