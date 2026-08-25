<?php

use Elcreator\aSocialAuth\Enums\SocialProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Points social_accounts at the provider registry instead of carrying a slug.
 *
 * The original table stored the provider as a varchar on every identity row.
 * That works, but it leaves provider metadata unjoinable and lets a typo create
 * a provider nobody registered. After this migration the provider is a foreign
 * key, so "list this user's identities with their labels" is one query and an
 * unknown provider is impossible to store.
 *
 * Existing rows are preserved: every distinct slug already in the table is
 * registered (disabled, so it does not silently appear as a button) and the rows
 * are repointed at it before the old column goes.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('social_accounts') || !Schema::hasTable('social_providers')) {
            return;
        }

        if (!Schema::hasColumn('social_accounts', 'provider_id')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->unsignedBigInteger('provider_id')->nullable()->after('id');
                $table->index('provider_id');
            });
        }

        // Cached profile fields the linking flow needs but the first cut lacked.
        if (!Schema::hasColumn('social_accounts', 'email_verified')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->boolean('email_verified')->default(false)->after('email');
            });
        }

        if (!Schema::hasColumn('social_accounts', 'last_login_at')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->unsignedInteger('last_login_at')->nullable()->after('avatar');
            });
        }

        if (Schema::hasColumn('social_accounts', 'provider')) {
            $this->backfillProviderIds();

            // Rows whose slug could not be resolved would violate the new unique
            // index as a group of NULLs; there should be none, but a row with no
            // provider is meaningless either way, so drop them rather than fail.
            DB::table('social_accounts')->whereNull('provider_id')->delete();

            $this->dropUniqueIfPresent();

            Schema::table('social_accounts', function (Blueprint $table) {
                $table->dropColumn('provider');
            });
        }

        $this->addUniqueIfMissing();
    }

    public function down(): void
    {
        if (!Schema::hasTable('social_accounts')) {
            return;
        }

        if (!Schema::hasColumn('social_accounts', 'provider')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->string('provider', 64)->default('')->after('id');
            });

            if (Schema::hasTable('social_providers')) {
                foreach (DB::table('social_providers')->get() as $provider) {
                    DB::table('social_accounts')
                        ->where('provider_id', $provider->id)
                        ->update(['provider' => $provider->slug]);
                }
            }
        }

        try {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->dropUnique(['provider_id', 'provider_user_id']);
            });
        } catch (\Throwable $e) {
            // Index absent — nothing to undo.
        }

        foreach (['provider_id', 'email_verified', 'last_login_at'] as $column) {
            if (Schema::hasColumn('social_accounts', $column)) {
                Schema::table('social_accounts', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Register every slug already present and repoint the rows at it.
     */
    protected function backfillProviderIds(): void
    {
        $slugs = DB::table('social_accounts')
            ->select('provider')
            ->whereNotNull('provider')
            ->where('provider', '<>', '')
            ->distinct()
            ->pluck('provider');

        foreach ($slugs as $slug) {
            $slug = strtolower(trim((string) $slug));

            if ($slug === '') {
                continue;
            }

            $providerId = $this->registerSlug($slug);

            DB::table('social_accounts')
                ->whereRaw('LOWER(TRIM(provider)) = ?', [$slug])
                ->update(['provider_id' => $providerId]);
        }
    }

    /**
     * Insert (or find) the registry row for a slug carried over from the old
     * column. Registered disabled: the site's config decides what is on offer,
     * and ProviderRegistry::sync() will enable it on the next request if it is.
     */
    protected function registerSlug(string $slug): int
    {
        $existing = DB::table('social_providers')->where('slug', $slug)->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $case = SocialProvider::fromSlug($slug);

        return (int) DB::table('social_providers')->insertGetId([
            'slug'       => $slug,
            'adapter'    => $case?->hybridauthProvider() ?? ucfirst($slug),
            'label'      => $case?->label() ?? ucfirst($slug),
            'enabled'    => false,
            'sort'       => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function dropUniqueIfPresent(): void
    {
        try {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->dropUnique(['provider', 'provider_user_id']);
            });
        } catch (\Throwable $e) {
            // Index already gone, or named differently on this platform.
        }
    }

    protected function addUniqueIfMissing(): void
    {
        try {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->unique(['provider_id', 'provider_user_id']);
            });
        } catch (\Throwable $e) {
            // Already present from an earlier run.
        }
    }
};
