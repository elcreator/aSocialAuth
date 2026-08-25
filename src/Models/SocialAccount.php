<?php

namespace Elcreator\aSocialAuth\Models;

use Elcreator\aSocialAuth\Enums\SocialProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One provider identity belonging to one Evolution CMS user.
 *
 * A user may own many of these — that is the whole linking model. Signing in
 * with Google and later adding Telegram from the identities widget produces two
 * rows pointing at the same `user_id`, and either one then logs that user in.
 *
 * The pair (provider_id, provider_user_id) is unique, so an identity can belong
 * to exactly one local user; re-authenticating with a provider always lands on
 * the same account rather than quietly forking a second one.
 *
 * @property int         $id
 * @property int         $provider_id      social_providers.id
 * @property string      $provider_user_id Opaque identifier from the provider
 * @property int         $user_id          users.id
 * @property string|null $email            Cached, refreshed on each login
 * @property string|null $name             Cached
 * @property string|null $avatar           Cached
 * @property bool        $email_verified   Provider asserted the address is theirs
 * @property int|null    $last_login_at    Unix time of the last sign-in with it
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read RegisteredProvider $provider
 */
class SocialAccount extends Model
{
    protected $table = 'social_accounts';

    protected $fillable = [
        'provider_id',
        'provider_user_id',
        'user_id',
        'email',
        'name',
        'avatar',
        'email_verified',
        'last_login_at',
    ];

    protected $casts = [
        'provider_id'    => 'int',
        'user_id'        => 'int',
        'email_verified' => 'bool',
        'last_login_at'  => 'int',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(RegisteredProvider::class, 'provider_id');
    }

    public function user(): BelongsTo
    {
        $userModel = config(
            'cms.settings.aSocialAuth.models.user',
            \EvolutionCMS\Models\User::class
        );

        return $this->belongsTo($userModel, 'user_id');
    }

    /**
     * The provider slug, without loading the relation when it is already eager
     * loaded. Views and log lines want the slug far more often than the row.
     */
    public function providerSlug(): ?string
    {
        return $this->provider?->slug;
    }

    public function catalogue(): ?SocialProvider
    {
        return $this->provider?->catalogue();
    }

    /**
     * Find the identity a provider's callback just proved ownership of.
     *
     * @param RegisteredProvider|int $provider
     */
    public static function findIdentity($provider, string $providerUserId): ?static
    {
        $providerId = $provider instanceof RegisteredProvider ? $provider->getKey() : (int) $provider;

        return static::query()
            ->where('provider_id', $providerId)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }

    /**
     * Every identity linked to a user, provider row included, ready for the
     * identities widget.
     *
     * @return Collection<int, static>
     */
    public static function forUser(int $userId): Collection
    {
        return static::query()
            ->with('provider')
            ->where('user_id', $userId)
            ->get()
            ->sortBy(fn (self $account) => $account->provider?->sort ?? PHP_INT_MAX)
            ->values();
    }

    public static function countForUser(int $userId): int
    {
        return static::query()->where('user_id', $userId)->count();
    }

    public function scopeForProviderSlug(Builder $query, string $slug): Builder
    {
        return $query->whereHas('provider', fn (Builder $q) => $q->where('slug', strtolower($slug)));
    }
}
