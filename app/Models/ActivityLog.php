<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'created' => 'ثبت',
            'updated' => 'ویرایش / جابه‌جایی',
            'status_changed' => 'تغییر وضعیت',
            'deleted' => 'حذف',
            'viewed' => 'مشاهده پرونده',
            'login' => 'ورود',
            'logout' => 'خروج',
            'secure_erased' => 'حذف امن',
            'reminder_sent' => 'ارسال یادآوری',
            'completed' => 'تکمیل',
            'cancelled' => 'لغو',
            'assigned' => 'تغییر مسئول',
            'rescheduled' => 'تغییر تاریخ',
            'exported' => 'خروجی‌گیری',
            default => $this->action,
        };
    }
}
