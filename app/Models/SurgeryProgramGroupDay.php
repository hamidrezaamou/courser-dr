<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurgeryProgramGroupDay extends Model
{
    protected $fillable = [
        'surgery_program_group_id',
        'hospital_id',
        'date_key',
        'total_slots',
    ];

    protected function casts(): array
    {
        return [
            'surgery_program_group_id' => 'integer',
            'hospital_id' => 'integer',
            'total_slots' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SurgeryProgramGroup::class, 'surgery_program_group_id');
    }

    public static function syncFromSchedule(ClinicSchedule $schedule): ?self
    {
        if ($schedule->kind !== 'surgery' || ! $schedule->hospital_id || ! $schedule->surgery_type_id) {
            return null;
        }

        $group = SurgeryProgramGroup::findFor(
            (int) $schedule->surgery_type_id,
            $schedule->surgery_subtype_id ? (int) $schedule->surgery_subtype_id : null
        );
        if (! $group) {
            return null;
        }

        $slots = max(1, $schedule->totalSlots());

        return static::query()->firstOrCreate(
            [
                'surgery_program_group_id' => $group->id,
                'hospital_id' => $schedule->hospital_id,
                'date_key' => $schedule->date_key,
            ],
            ['total_slots' => $slots]
        );
    }

    public static function totalFor(int $groupId, int $hospitalId, string $dateKey): ?int
    {
        $row = static::query()
            ->where('surgery_program_group_id', $groupId)
            ->where('hospital_id', $hospitalId)
            ->where('date_key', $dateKey)
            ->first();

        return $row ? (int) $row->total_slots : null;
    }
}
