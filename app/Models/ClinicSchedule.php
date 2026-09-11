<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicSchedule extends Model
{
    protected $fillable = [
        'kind',
        'hospital_id',
        'surgery_type_id',
        'surgery_subtype_id',
        'date_key',
        'display_text',
        'year',
        'month',
        'day',
        'weekday',
        'settings',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'year' => 'integer',
            'month' => 'integer',
            'day' => 'integer',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function surgeryType(): BelongsTo
    {
        return $this->belongsTo(SurgeryType::class);
    }

    public function surgerySubtype(): BelongsTo
    {
        return $this->belongsTo(SurgerySubtype::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function times(): array
    {
        return array_values($this->settings['times'] ?? []);
    }

    public function slotMode(): string
    {
        $mode = $this->settings['slotMode'] ?? 'time';

        return $mode === 'queue' ? 'queue' : 'time';
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function slotOptions(): array
    {
        $mode = $this->slotMode();
        $out = [];
        foreach ($this->times() as $raw) {
            $value = is_string($raw) ? $raw : (string) $raw;
            if ($mode === 'queue' || \App\Support\SlotLabel::isQueue($value) || preg_match('/^نوبت/u', $value)) {
                $normalized = \App\Support\SlotLabel::normalize($value);
                $out[] = [
                    'value' => $normalized,
                    'label' => \App\Support\SlotLabel::display($normalized),
                ];
            } else {
                $normalized = preg_match('/^\d{1,2}:\d{2}/', $value)
                    ? substr($value, 0, 5)
                    : $value;
                $out[] = [
                    'value' => $normalized,
                    'label' => $normalized,
                ];
            }
        }

        return $out;
    }

    public function totalSlots(): int
    {
        return (int) ($this->settings['totalSlots'] ?? count($this->times()));
    }

    public function smsText(): string
    {
        return trim((string) ($this->settings['smsText'] ?? ''));
    }
}
