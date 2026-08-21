<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('social_accounts')) {
            return;
        }

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();

            // Provider slug: 'google', 'facebook', 'linkedin', 'x', etc.
            $table->string('provider', 32);

            // Opaque identifier returned by the provider (can be a number or UUID).
            $table->string('provider_user_id', 255);

            // The linked Evolution CMS manager user.
            $table->unsignedBigInteger('user_id');

            // Cached profile data from the provider (updated on each login).
            $table->string('email', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('avatar', 2048)->nullable();

            $table->timestamps();

            // Each provider identity maps to exactly one local user.
            $table->unique(['provider', 'provider_user_id']);

            // Allow looking up all social accounts for a local user.
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
