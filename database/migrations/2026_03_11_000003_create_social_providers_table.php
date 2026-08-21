<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The provider registry: one row per network this site offers.
 *
 * Rows are written by ProviderRegistry::sync() from the SocialProvider catalogue
 * and the site's config, so nothing is seeded here — an empty table is the
 * correct state until the site enables its first provider.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('social_providers')) {
            return;
        }

        Schema::create('social_providers', function (Blueprint $table) {
            $table->id();

            // URL segment and catalogue key: 'google', 'telegram', 'linkedin'.
            $table->string('slug', 64)->unique();

            // HybridAuth adapter class name, or a FQCN for a custom adapter.
            $table->string('adapter', 191);

            $table->string('label', 191)->default('');

            // Whether the site currently offers it. Disabling keeps the row so
            // that identities already linked to it survive and can be re-enabled.
            $table->boolean('enabled')->default(false);

            $table->integer('sort')->default(0);

            // Icon slug, when the button should reuse another provider's icon.
            $table->string('icon', 64)->nullable();

            $table->timestamps();

            $table->index(['enabled', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_providers');
    }
};
