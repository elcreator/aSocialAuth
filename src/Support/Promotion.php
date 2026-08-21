<?php

namespace EvolutionCMS\aSocialAuth\Support;

/**
 * Whether a socially-created account is fit to be given manager rights.
 *
 * Promotion itself is core behaviour and this package does not reimplement it.
 * Evolution CMS 3.x keeps manager users and web users in the same `users` table
 * and tells them apart only by `user_attributes.role`, so an administrator with
 * the `save_role` permission raises a social account to a manager one the same
 * way as any other: open it under Users → Web user management, pick a role, save.
 * The form posts to EditOrNewUser, which calls \UserManager::setRole() — the one
 * door role changes go through, since `role` is deliberately absent from the
 * mass-assignable attributes.
 *
 * What this class adds is the precondition. An account created through a
 * provider that returns no e-mail carries a synthesised `@social.invalid`
 * address that can never receive mail. As a web user that is fine — the provider
 * is the way back in. As a manager it is not:
 *
 *   - password recovery is the standard way back into a manager account, and it
 *     works by e-mail; there is no route back for an unreachable address;
 *   - a manager who loses access to their Telegram account loses the manager
 *     account with it, and nobody can help them without direct database access;
 *   - security notifications and the event-log mailer have nowhere to go.
 *
 * So a manager role wants a real address first. The account owner adds one from
 * the identities widget, which prompts for it precisely because of this.
 */
class Promotion
{
    /**
     * Whether this account can safely be given a manager role.
     */
    public static function canBecomeManager(int $userId): bool
    {
        return static::blockReason($userId) === null;
    }

    /**
     * Why not, as a message an administrator can act on, or null when it can.
     */
    public static function blockReason(int $userId): ?string
    {
        $email = static::emailFor($userId);

        if ($email === null || $email === '') {
            return __('aSocialAuth::login.promote_no_email');
        }

        if (UserResolver::isPlaceholderEmail($email)) {
            return __('aSocialAuth::login.promote_placeholder_email');
        }

        return null;
    }

    /**
     * Whether a role id grants manager standing.
     *
     * Role 0 is "no user role" — a web user with no manager access at all, and
     * the role a social registration is created with. Anything above it is a row
     * in `user_roles` whose permissions decide what the manager shows; only a
     * role granting `access_permissions` actually opens the manager, but any
     * non-zero role is a promotion out of plain web-user standing and is where
     * the e-mail precondition starts to matter.
     */
    public static function isManagerRole(int $roleId): bool
    {
        return $roleId > 0;
    }

    /**
     * The stored e-mail for a user, or null when it cannot be read.
     */
    protected static function emailFor(int $userId): ?string
    {
        try {
            $userModel = Config::getUserModel();
            $user      = $userModel::query()->with('attributes')->find($userId);
            $email     = $user?->attributes?->email;
        } catch (\Throwable $e) {
            Log::warning('Could not read the e-mail for user #' . $userId . ': ' . $e->getMessage());

            return null;
        }

        return is_string($email) ? $email : null;
    }
}
