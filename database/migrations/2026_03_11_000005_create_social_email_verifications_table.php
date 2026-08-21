<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addresses that have been claimed but not yet proven.
 *
 * A separate table rather than a column on the user, because the whole point is
 * that the address must NOT reach `user_attributes.email` until it is confirmed.
 *
 * Writing it there straight away would look simpler and would be a real hole:
 * e-mail is unique across users, and `linking.match_by_email` attaches a new
 * provider identity to whichever account already holds that address. Claiming
 * someone else's address unproven would therefore let the claimant collect that
 * person's Google or Facebook identity the next time they signed in — and would
 * also block the rightful owner from ever registering it. So a claim lives here
 * until the confirmation link is followed, and only then moves.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('social_email_verifications')) {
            return;
        }

        Schema::create('social_email_verifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('user_id');

            // The address being claimed. Not unique: two people may both be
            // trying to prove the same address, and only the first to confirm
            // gets it — enforcing uniqueness here would let an attacker block a
            // claim simply by starting one.
            $table->string('email', 255);

            $table->string('token', 64)->unique();

            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // One outstanding claim per user; a new one replaces it.
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_email_verifications');
    }
};
