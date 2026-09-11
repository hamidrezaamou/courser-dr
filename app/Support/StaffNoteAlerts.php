<?php

namespace App\Support;

use App\Models\InternalNote;
use App\Models\StaffNoteNotification;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StaffNoteAlerts
{
    /** @var array<int, int> */
    private static array $unreadCache = [];

    public static function ensureTables(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            if (! Schema::hasTable('staff_note_notifications')) {
                Schema::create('staff_note_notifications', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id');
                    $table->unsignedBigInteger('actor_id');
                    $table->unsignedBigInteger('patient_id');
                    $table->unsignedBigInteger('internal_note_id');
                    $table->timestamp('read_at')->nullable();
                    $table->timestamps();
                    $table->unique(['user_id', 'internal_note_id'], 'staff_note_notif_user_note_unique');
                    $table->index(['user_id', 'read_at', 'id'], 'staff_note_notif_inbox_idx');
                    $table->index('internal_note_id');
                });
            }
        } catch (\Throwable $e) {
            $ensured = false;
            report($e);
        }
    }

    public static function isAvailable(): bool
    {
        self::ensureTables();

        return Schema::hasTable('staff_note_notifications')
            && Schema::hasTable('internal_notes');
    }

    public static function notifyOthers(InternalNote $note): void
    {
        if (! self::isAvailable()) {
            return;
        }

        $actorId = (int) $note->user_id;
        $recipientIds = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT])
            ->where('id', '!=', $actorId)
            ->pluck('id');

        if ($recipientIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $recipientIds->map(fn ($id) => [
            'user_id' => (int) $id,
            'actor_id' => $actorId,
            'patient_id' => (int) $note->patient_id,
            'internal_note_id' => (int) $note->id,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        try {
            StaffNoteNotification::query()->insertOrIgnore($rows);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function unreadCount(?int $userId): int
    {
        if (! $userId || ! self::isAvailable()) {
            return 0;
        }

        if (array_key_exists($userId, self::$unreadCache)) {
            return self::$unreadCache[$userId];
        }

        return self::$unreadCache[$userId] = StaffNoteNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public static function markReadForNote(int $userId, int $noteId): void
    {
        if (! self::isAvailable() || $noteId < 1) {
            return;
        }

        StaffNoteNotification::query()
            ->where('user_id', $userId)
            ->where('internal_note_id', $noteId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        unset(self::$unreadCache[$userId]);
    }

    public static function markAllRead(int $userId): void
    {
        if (! self::isAvailable()) {
            return;
        }

        StaffNoteNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        unset(self::$unreadCache[$userId]);
    }

    public static function forgetNote(int $noteId): void
    {
        if (! self::isAvailable() || $noteId < 1) {
            return;
        }

        StaffNoteNotification::query()
            ->where('internal_note_id', $noteId)
            ->delete();

        self::$unreadCache = [];
    }

    public static function preview(string $text, int $limit = 90): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return Str::limit($clean, $limit);
    }
}
