<?php

namespace Elcreator\aSocialAuth\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An address a user has claimed but not yet proven.
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $email
 * @property string      $token
 * @property string|null $expires_at
 */
class EmailVerification extends Model
{
    protected $table = 'social_email_verifications';

    protected $fillable = [
        'user_id',
        'email',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'user_id'    => 'int',
        'expires_at' => 'datetime',
    ];

    public function hasExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    public static function findByToken(string $token): ?static
    {
        if ($token === '') {
            return null;
        }

        return static::query()->where('token', $token)->first();
    }

    public static function forUser(int $userId): ?static
    {
        return static::query()->where('user_id', $userId)->first();
    }
}
