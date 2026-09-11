<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'ip_address',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{0: self, 1: string}
     */
    public static function issue(User $user, string $deviceName = 'android'): array
    {
        $plain = bin2hex(random_bytes(32));

        $model = static::query()->create([
            'user_id' => $user->id,
            'name' => mb_substr($deviceName !== '' ? $deviceName : 'android', 0, 120),
            'token' => hash('sha256', $plain),
            'ip_address' => request()?->ip(),
            'expires_at' => now()->addDays(90),
        ]);

        return [$model, $plain];
    }

    public static function findValid(?string $plain): ?self
    {
        $plain = trim((string) $plain);
        if ($plain === '' || strlen($plain) < 32) {
            return null;
        }

        $token = static::query()
            ->where('token', hash('sha256', $plain))
            ->first();

        if (! $token) {
            return null;
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();

            return null;
        }

        return $token;
    }

    public function touchUsage(): void
    {
        $this->forceFill([
            'last_used_at' => now(),
            'ip_address' => request()?->ip() ?: $this->ip_address,
        ])->save();
    }
}
