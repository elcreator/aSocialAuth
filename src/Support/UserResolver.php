<?php

namespace EvolutionCMS\aSocialAuth\Support;

use EvolutionCMS\aSocialAuth\Exceptions\SocialAuthException;
use EvolutionCMS\aSocialAuth\Models\RegisteredProvider;
use EvolutionCMS\aSocialAuth\Models\SocialAccount;
use Hybridauth\User\Profile;

/**
 * Turns a provider profile into an Evolution CMS user, and keeps the two linked.
 *
 * Three routes lead to a user, tried in this order:
 *
 *   1. the identity is already linked — the ordinary repeat sign-in;
 *   2. the identity is new but its verified e-mail matches an existing user —
 *      link it to that user rather than forking a second account;
 *   3. nothing matches — create a user, if the site allows it.
 *
 * Everything that writes a user goes through \UserManager rather than touching
 * Eloquent directly. Evolution CMS splits a user across two tables: credentials
 * in `users`, everything else — name, e-mail, role, blocked flags — in
 * `user_attributes`. `User::create(['email' => …])` looks reasonable and is
 * simply wrong: `users` has no e-mail column, so the value is dropped and the
 * profile row is never created at all. UserManager::create() writes both halves
 * and fires the OnUserFormSave events extras listen for.
 */
class UserResolver
{
    /**
     * Resolve, or create, the user this profile should sign in as.
     *
     * @return array{user_id: int, account: SocialAccount, created: bool}
     * @throws SocialAuthException when no user can be resolved
     */
    public static function forLogin(RegisteredProvider $provider, Profile $profile): array
    {
        $providerUserId = static::providerUserId($profile);
        $email          = static::email($profile);
        $emailVerified  = static::emailIsVerified($profile);

        // 1. Known identity.
        $account = SocialAccount::findIdentity($provider, $providerUserId);

        if ($account !== null) {
            static::refresh($account, $profile, $emailVerified);

            return ['user_id' => (int) $account->user_id, 'account' => $account, 'created' => false];
        }

        // 2. Same person, already has an account under this e-mail.
        if ($email !== null && Config::matchByEmail()) {
            $userId = static::findUserIdByEmail($email, $provider, $emailVerified);

            if ($userId !== null) {
                $account = static::link($provider, $profile, $userId);

                Log::info(sprintf(
                    'Linked %s identity %s to existing user #%d by e-mail.',
                    $provider->slug,
                    $providerUserId,
                    $userId
                ));

                return ['user_id' => $userId, 'account' => $account, 'created' => false];
            }
        }

        // 3. New person.
        if (!Config::shouldCreateUsers()) {
            Log::warning(sprintf(
                'Social login via %s refused: no user matches identity %s%s, and registration is disabled.',
                $provider->slug,
                $providerUserId,
                $email !== null ? " / e-mail {$email}" : ''
            ));

            throw SocialAuthException::lexicon('error_no_user');
        }

        $userId  = static::createUser($provider, $profile);
        $account = static::link($provider, $profile, $userId);

        Log::info(sprintf('Created user #%d from %s identity %s.', $userId, $provider->slug, $providerUserId));

        return ['user_id' => $userId, 'account' => $account, 'created' => true];
    }

    /**
     * Attach an identity to a user who is already signed in — the widget's
     * "connect another account" path.
     *
     * @throws SocialAuthException when the identity belongs to someone else
     */
    public static function forLink(RegisteredProvider $provider, Profile $profile, int $userId): SocialAccount
    {
        $providerUserId = static::providerUserId($profile);
        $existing       = SocialAccount::findIdentity($provider, $providerUserId);

        if ($existing !== null) {
            if ((int) $existing->user_id === $userId) {
                static::refresh($existing, $profile, static::emailIsVerified($profile));

                return $existing;
            }

            // The identity is spoken for. Silently moving it would let anyone who
            // can authenticate with a provider detach it from its owner, so this
            // is refused and the owner keeps it.
            Log::warning(sprintf(
                'Refused to link %s identity %s to user #%d: already linked to user #%d.',
                $provider->slug,
                $providerUserId,
                $userId,
                (int) $existing->user_id
            ));

            throw SocialAuthException::lexicon('error_identity_taken', ['provider' => $provider->label]);
        }

        return static::link($provider, $profile, $userId);
    }

    /**
     * Create the `social_accounts` row for an identity.
     */
    public static function link(RegisteredProvider $provider, Profile $profile, int $userId): SocialAccount
    {
        return SocialAccount::query()->create([
            'provider_id'      => $provider->getKey(),
            'provider_user_id' => static::providerUserId($profile),
            'user_id'          => $userId,
            'email'            => static::email($profile),
            'name'             => static::displayName($profile),
            'avatar'           => static::avatar($profile),
            'email_verified'   => static::emailIsVerified($profile),
            'last_login_at'    => time(),
        ]);
    }

    /**
     * Refresh the cached profile fields on an existing identity.
     */
    public static function refresh(SocialAccount $account, Profile $profile, bool $emailVerified): void
    {
        $account->fill([
            'email'          => static::email($profile) ?? $account->email,
            'name'           => static::displayName($profile) ?? $account->name,
            'avatar'         => static::avatar($profile) ?? $account->avatar,
            'email_verified' => $emailVerified || (bool) $account->email_verified,
            'last_login_at'  => time(),
        ]);

        try {
            $account->save();
        } catch (\Throwable $e) {
            // Cached display data is a convenience; failing to refresh it must
            // not cost the visitor their sign-in.
            Log::warning('Failed to refresh social account #' . $account->getKey() . ': ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // Users
    // ------------------------------------------------------------------

    /**
     * Find a user by the e-mail on their profile row.
     *
     * Returns null when the address may not be trusted for matching. An address
     * a provider does not verify is just a string the account holder typed, and
     * treating it as proof of identity would turn any such provider into a way
     * of claiming the Evolution CMS account that uses the same address.
     */
    public static function findUserIdByEmail(string $email, RegisteredProvider $provider, bool $emailVerified): ?int
    {
        if (Config::requireVerifiedEmail() && !$emailVerified) {
            Log::warning(sprintf(
                'Not matching %s identity by e-mail %s: the provider did not verify it.',
                $provider->slug,
                $email
            ));

            return null;
        }

        $userModel = Config::getUserModel();

        try {
            $user = $userModel::query()
                ->with('attributes')
                ->whereHas('attributes', static fn ($query) => $query->where('email', $email))
                ->first();
        } catch (\Throwable $e) {
            Log::warning('User lookup by e-mail failed: ' . $e->getMessage());

            return null;
        }

        if ($user === null || $user->getKey() === null) {
            return null;
        }

        // The provider proving the address is only half of it. The local account
        // must have proven it too, or self-registration becomes a way to collect
        // other people's identities: register with someone else's address, wait
        // for them to sign in with Google, and their identity attaches to your
        // account. Accounts created from a provider-verified e-mail are already
        // flagged verified, so the ordinary social path is unaffected.
        if (Config::requireLocalVerifiedEmail() && (int) ($user->attributes->verified ?? 0) !== 1) {
            Log::warning(sprintf(
                'Not matching the %s identity to user #%d: that account has not verified its own e-mail.',
                $provider->slug,
                (int) $user->getKey()
            ));

            return null;
        }

        return (int) $user->getKey();
    }

    /**
     * Create an Evolution CMS user from a provider profile.
     *
     * @throws SocialAuthException
     */
    public static function createUser(RegisteredProvider $provider, Profile $profile): int
    {
        $email         = static::email($profile);
        $emailVerified = $email !== null && static::emailIsVerified($profile);

        // Telegram and Steam never return an address, and X only does with an
        // approved scope. Evolution CMS requires a unique non-empty e-mail on
        // every user, so one is synthesised rather than refusing the sign-up —
        // registering with nothing but a Telegram account is a supported case,
        // not a degraded one.
        if ($email === null) {
            $email = static::placeholderEmail($provider, static::providerUserId($profile));
        }

        $password = static::randomPassword();
        $username = static::generateUsername($provider, $profile);

        $userData = [
            'username'              => $username,
            'password'              => $password,
            'password_confirmation' => $password,
            'email'                 => $email,
            'fullname'              => static::displayName($profile) ?? $username,
            'first_name'            => (string) ($profile->firstName ?? ''),
            'last_name'             => (string) ($profile->lastName ?? ''),
            'photo'                 => static::avatar($profile) ?? '',
            'verified'              => $emailVerified ? 1 : 0,
            'blocked'               => 0,
        ];

        try {
            $user = \UserManager::create($userData);
        } catch (\EvolutionCMS\Exceptions\ServiceValidationException $e) {
            Log::error('Social registration rejected: ' . json_encode($e->getValidationErrors()));

            throw SocialAuthException::lexicon('error_create_user');
        } catch (\Throwable $e) {
            Log::error('Social registration failed: ' . $e->getMessage());

            throw SocialAuthException::lexicon('error_create_user');
        }

        $userId = (int) $user->getKey();

        static::assignDefaults($userId);

        return $userId;
    }

    /**
     * Apply the configured role and groups to a freshly created account.
     *
     * Shared with the password-registration path so that however someone signs
     * up, one setting decides what their account may do.
     */
    public static function assignDefaults(int $userId): void
    {
        static::applyRole($userId);
        static::applyGroups($userId);
    }

    /**
     * Give a freshly created user their configured role.
     *
     * Role is deliberately not mass assignable in the core — `role` is absent
     * from UserAttribute's $fillable, and UserEdit skips it explicitly — so that
     * a privilege change can only ever happen through one call. Passing it to
     * create() would silently do nothing.
     */
    protected static function applyRole(int $userId): void
    {
        $role = Config::defaultRole();

        if ($role <= 0) {
            // 0 is already the stored default: a web user with no manager access.
            return;
        }

        try {
            \UserManager::setRole(['id' => $userId, 'role' => $role]);
        } catch (\Throwable $e) {
            Log::error("Failed to assign role {$role} to user #{$userId}: " . $e->getMessage());
        }
    }

    protected static function applyGroups(int $userId): void
    {
        $groups = Config::defaultGroups();

        if ($groups === []) {
            return;
        }

        try {
            \UserManager::setGroups(['id' => $userId, 'groups' => $groups]);
        } catch (\Throwable $e) {
            Log::error("Failed to assign groups to user #{$userId}: " . $e->getMessage());
        }
    }

    /**
     * A unique, non-deliverable address for a provider that returns none.
     *
     * `.invalid` is reserved by RFC 2606 and guaranteed never to resolve, so no
     * mail can leak towards it and it can never collide with a real address. The
     * local part is derived from the provider id, so the same Telegram account
     * always maps to the same placeholder.
     */
    public static function placeholderEmail(RegisteredProvider $provider, string $providerUserId): string
    {
        $hash   = substr(hash('sha256', $provider->slug . ':' . $providerUserId), 0, 16);
        $domain = Config::placeholderEmailDomain();

        return $provider->slug . '-' . $hash . '@' . $domain;
    }

    /**
     * Whether an address was minted by placeholderEmail() rather than supplied.
     * Front-end code can use this to prompt for a real address.
     */
    public static function isPlaceholderEmail(?string $email): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        return str_ends_with(strtolower($email), '@' . strtolower(Config::placeholderEmailDomain()));
    }

    /**
     * A username that is free, derived from the profile where possible.
     */
    public static function generateUsername(RegisteredProvider $provider, Profile $profile): string
    {
        $source = (string) ($profile->displayName ?: ($profile->firstName ?: $provider->slug));
        $base   = strtolower(Config::usernamePrefix() . $source);
        $base   = preg_replace('/[^a-z0-9_.-]+/', '', $base) ?? '';
        $base   = trim($base, '.-_');

        if ($base === '' || strlen($base) < 3) {
            $base = Config::usernamePrefix() . $provider->slug;
        }

        $base      = substr($base, 0, 90);
        $userModel = Config::getUserModel();
        $candidate = $base;

        for ($i = 1; $i <= 1000; $i++) {
            try {
                $taken = $userModel::query()->where('username', $candidate)->exists();
            } catch (\Throwable $e) {
                // Cannot check: fall through to the random suffix below, which is
                // collision-resistant enough to insert without checking.
                break;
            }

            if (!$taken) {
                return $candidate;
            }

            $candidate = $base . $i;
        }

        return $base . '-' . bin2hex(random_bytes(4));
    }

    /**
     * A password nobody knows.
     *
     * The account is reachable through its linked identities; this exists only
     * because the core requires a password hash on every user. It is never shown
     * or mailed, so the owner signs in socially, or resets it through the normal
     * "forgot password" flow if they have a real address.
     */
    protected static function randomPassword(): string
    {
        return bin2hex(random_bytes(24));
    }

    // ------------------------------------------------------------------
    // Profile reading
    // ------------------------------------------------------------------

    /**
     * @throws SocialAuthException when the provider identified nobody
     */
    public static function providerUserId(Profile $profile): string
    {
        $identifier = trim((string) ($profile->identifier ?? ''));

        if ($identifier === '') {
            throw SocialAuthException::lexicon('error_no_identifier');
        }

        return $identifier;
    }

    public static function email(Profile $profile): ?string
    {
        $email = trim((string) ($profile->email ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * HybridAuth reports verification by echoing the address back in
     * `emailVerified` — the two matching is the assertion. An empty field means
     * the provider said nothing, which is not the same as saying no.
     */
    public static function emailIsVerified(Profile $profile): bool
    {
        $email    = static::email($profile);
        $verified = trim((string) ($profile->emailVerified ?? ''));

        return $email !== null && $verified !== '' && strcasecmp($verified, $email) === 0;
    }

    public static function displayName(Profile $profile): ?string
    {
        $name = trim((string) ($profile->displayName ?? ''));

        if ($name === '') {
            $name = trim(((string) ($profile->firstName ?? '')) . ' ' . ((string) ($profile->lastName ?? '')));
        }

        return $name !== '' ? $name : null;
    }

    public static function avatar(Profile $profile): ?string
    {
        $avatar = trim((string) ($profile->photoURL ?? ''));

        return $avatar !== '' ? $avatar : null;
    }
}
