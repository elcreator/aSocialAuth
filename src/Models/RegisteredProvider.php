<?php

namespace EvolutionCMS\aSocialAuth\Models;

use EvolutionCMS\aSocialAuth\Enums\SocialProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A provider as registered on this site — one row per network on offer.
 *
 * This table is what `social_accounts.provider_id` points at, and it is the
 * reason a new network no longer costs a schema change. The older shape for this
 * problem is a column per network on the user row (`fb_id`, `tw_id`, `gg_id`,
 * `li_id`, `tg_id`, …): every addition is a migration, every query that asks
 * "which provider is this user?" is a chain of null checks, and the row grows
 * without bound. Here the provider is a row, the identity is a row, and adding
 * Discord is one insert.
 *
 * The rows are derived, not authored: ProviderRegistry::sync() writes them from
 * the SocialProvider catalogue plus the site's config, so config stays the thing
 * an administrator edits. The table exists so that the database can express the
 * foreign key, and so that provider metadata is joinable — listing a user's
 * identities with labels is one query rather than a config lookup per row.
 *
 * @property int         $id
 * @property string      $slug     URL segment and catalogue key ('google')
 * @property string      $adapter  HybridAuth class name ('Google')
 * @property string      $label    Button label
 * @property bool        $enabled  Whether the site currently offers it
 * @property int         $sort     Button order, ascending
 * @property string|null $icon     Icon slug when it differs from `slug`
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RegisteredProvider extends Model
{
    protected $table = 'social_providers';

    protected $fillable = [
        'slug',
        'adapter',
        'label',
        'enabled',
        'sort',
        'icon',
    ];

    protected $casts = [
        'enabled' => 'bool',
        'sort'    => 'int',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class, 'provider_id');
    }

    /**
     * The catalogue case for this row, or null when the site registered a slug
     * the catalogue does not know (a custom adapter).
     */
    public function catalogue(): ?SocialProvider
    {
        return SocialProvider::fromSlug($this->slug);
    }

    /**
     * Fully qualified HybridAuth adapter class. A site may store a FQCN in
     * `adapter` to use an implementation of its own; anything else is read as a
     * class name under \Hybridauth\Provider\.
     */
    public function adapterClass(): string
    {
        $adapter = (string) $this->adapter;

        if (str_contains($adapter, '\\')) {
            return ltrim($adapter, '\\');
        }

        return 'Hybridauth\\Provider\\' . $adapter;
    }

    /**
     * The icon key the views should look up for this provider.
     */
    public function iconKey(): string
    {
        return (string) ($this->icon ?: $this->slug);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('label');
    }

    public static function findBySlug(?string $slug): ?static
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        return static::query()->where('slug', strtolower(trim($slug)))->first();
    }
}
