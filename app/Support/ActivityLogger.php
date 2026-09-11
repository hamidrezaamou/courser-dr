<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function log(Model $subject, string $action, ?array $old = null, ?array $new = null): ActivityLog
    {
        return ActivityLog::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'user_id' => auth()->id(),
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Log patient file view at most once per user/patient within $minutes.
     */
    public static function logPatientView(Model $patient, string $route, int $minutes = 30): ?ActivityLog
    {
        $userId = auth()->id() ?: 0;
        $key = 'audit.view.'.$userId.'.'.$patient->getKey();

        $last = session($key);
        if (is_string($last)) {
            try {
                if (now()->lt(\Carbon\Carbon::parse($last)->addMinutes($minutes))) {
                    return null;
                }
            } catch (\Throwable) {
                // continue
            }
        }

        session([$key => now()->toDateTimeString()]);

        return self::log($patient, 'viewed', null, [
            'route' => $route,
            'role' => auth()->user()?->role,
        ]);
    }
}
