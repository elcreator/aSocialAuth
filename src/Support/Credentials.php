<?php

namespace EvolutionCMS\aSocialAuth\Support;

use EvolutionCMS\aSocialAuth\Exceptions\ChallengeRequiredException;
use EvolutionCMS\aSocialAuth\Exceptions\SocialAuthException;
use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Exceptions\ServiceValidationException;

/**
 * The ordinary way in: e-mail and password, alongside the social buttons.
 *
 * A sign-in widget that offers only social providers is a trap — it hands every
 * account on the site to whichever third parties the administrator happened to
 * enable, and it strands anyone who does not use them. So the same widget also
 * carries the four things a password-based account needs: sign in, register,
 * ask for a reset link, and set a new password.
 *
 * All of it runs through \UserManager, which means through the core's login
 * pipeline, so a captcha or second-factor pipe covers these forms and the social
 * buttons alike. The one thing the core cannot do for us is the front-end half
 * of password recovery: PasswordRecoveryService issues the token but only ever
 * builds a *manager* reset link and returns early on the front end, leaving the
 * mail to an extra. That mail is sent here.
 */
class Credentials
{
    // ------------------------------------------------------------------
    // Sign in
    // ------------------------------------------------------------------

    /**
     * Sign in with a username or an e-mail address plus a password.
     *
     * The core matches on username only. Accepting an e-mail as well is what
     * makes this consistent with the social half, where the e-mail is the
     * identity the visitor actually remembers.
     *
     * @throws ChallengeRequiredException a pipe wants a second factor
     * @throws SocialAuthException        refused
     */
    public static function login(string $login, string $password, string $context, bool $remember = false): int
    {
        $login = trim($login);

        if ($login === '' || $password === '') {
            throw SocialAuthException::lexicon('error_credentials_required');
        }

        $username = static::resolveUsername($login);

        if ($username === null) {
            // Deliberately the same message as a wrong password: telling the two
            // apart turns the form into a way of testing which addresses have
            // accounts.
            throw SocialAuthException::lexicon('error_credentials_invalid');
        }

        try {
            $user = \UserManager::login([
                'username'   => $username,
                'password'   => $password,
                'rememberme' => $remember ? 1 : 0,
                'context'    => $context,

                // Marks this as an interactive form submission, the counterpart
                // of the `social` marker set by SocialAuth::signIn(). A pipe that
                // needs to ask the visitor something can do it inline here.
                'social'     => false,
            ]);
        } catch (ChallengeRequiredException $e) {
            throw $e;
        } catch (ServiceValidationException $e) {
            throw SocialAuthException::lexicon('error_credentials_invalid');
        } catch (ServiceActionException $e) {
            // Blocked account, throttled, or a pipe refusing — the message was
            // chosen by whoever refused and is meant for the visitor.
            throw new SocialAuthException($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Credential login failed: ' . $e->getMessage());

            throw SocialAuthException::lexicon('error_login');
        }

        return (int) $user->getKey();
    }

    // ------------------------------------------------------------------
    // Register
    // ------------------------------------------------------------------

    /**
     * Create an account from an e-mail address and a password.
     *
     * @param array{email: string, password: string, password_confirmation: string, username?: string, fullname?: string} $data
     *
     * @throws SocialAuthException
     */
    public static function register(array $data, string $context): int
    {
        if (!Config::credentialsRegistrationEnabled()) {
            throw SocialAuthException::lexicon('error_registration_disabled');
        }

        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw SocialAuthException::lexicon('error_email_invalid');
        }

        $password     = (string) ($data['password'] ?? '');
        $confirmation = (string) ($data['password_confirmation'] ?? '');
        $minimum      = Config::minimumPasswordLength();

        if (strlen($password) < $minimum) {
            throw SocialAuthException::lexicon('error_password_short', ['min' => $minimum]);
        }

        if ($password !== $confirmation) {
            throw SocialAuthException::lexicon('error_password_mismatch');
        }

        if (static::emailIsTaken($email)) {
            throw SocialAuthException::lexicon('error_email_taken');
        }

        $username = trim((string) ($data['username'] ?? ''));
        $username = $username !== '' ? $username : static::usernameFromEmail($email);

        $userData = [
            'username'              => $username,
            'password'              => $password,
            'password_confirmation' => $password,
            'email'                 => $email,
            'fullname'              => trim((string) ($data['fullname'] ?? '')) ?: $username,
            // A password registration has not proven the address yet. Sites that
            // care send their own confirmation mail off the OnUserFormSave event.
            'verified'              => 0,
            'blocked'               => 0,
        ];

        try {
            $user = \UserManager::create($userData);
        } catch (ServiceValidationException $e) {
            Log::warning('Credential registration rejected: ' . json_encode($e->getValidationErrors()));

            throw SocialAuthException::lexicon('error_create_user');
        } catch (\Throwable $e) {
            Log::error('Credential registration failed: ' . $e->getMessage());

            throw SocialAuthException::lexicon('error_create_user');
        }

        $userId = (int) $user->getKey();

        // Same role and group handling as a social registration: one place
        // decides what a self-registered account may do.
        UserResolver::assignDefaults($userId);

        Log::info("Registered user #{$userId} by e-mail {$email}.");

        return $userId;
    }

    // ------------------------------------------------------------------
    // Recovery
    // ------------------------------------------------------------------

    /**
     * Send a reset link to an address, if it belongs to an account.
     *
     * Always reports success to the caller. Whether an address has an account is
     * not something a public form should answer, and the difference between
     * "sent" and "not sent" is exactly that answer.
     */
    public static function requestReset(string $email, string $context): void
    {
        $email = trim($email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $user = static::findUserByEmail($email);

        if ($user === null) {
            Log::info('Password reset requested for an address with no account.');

            return;
        }

        // An account whose only e-mail is a placeholder we minted has no mailbox
        // to send to; the reset link would go nowhere.
        if (UserResolver::isPlaceholderEmail($email)) {
            Log::info("Password reset skipped for user #{$user->getKey()}: placeholder address.");

            return;
        }

        try {
            $service = new \EvolutionCMS\Services\PasswordRecoveryService();

            if ($service->hasValidToken($user)) {
                // A live token means a mail already went out. Issuing another on
                // every submission would make this form a mail amplifier.
                return;
            }

            $token = $service->issueToken($user);
        } catch (\Throwable $e) {
            Log::error('Failed to issue a password reset token: ' . $e->getMessage());

            return;
        }

        static::sendResetMail($user, (string) $token, $context);
    }

    /**
     * Set a new password from a reset token.
     *
     * @throws SocialAuthException
     */
    public static function resetPassword(string $token, string $password, string $confirmation): void
    {
        $token   = trim($token);
        $minimum = Config::minimumPasswordLength();

        if ($token === '') {
            throw SocialAuthException::lexicon('error_reset_token');
        }

        if (strlen($password) < $minimum) {
            throw SocialAuthException::lexicon('error_password_short', ['min' => $minimum]);
        }

        if ($password !== $confirmation) {
            throw SocialAuthException::lexicon('error_password_mismatch');
        }

        try {
            \UserManager::hashChangePassword([
                'hash'                  => $token,
                'password'              => $password,
                'password_confirmation' => $confirmation,
            ]);
        } catch (ServiceValidationException $e) {
            throw SocialAuthException::lexicon('error_password_short', ['min' => $minimum]);
        } catch (ServiceActionException $e) {
            throw new SocialAuthException($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Password reset failed: ' . $e->getMessage());

            throw SocialAuthException::lexicon('error_reset_token');
        }
    }

    /**
     * The mail the core does not send for front-end resets.
     */
    protected static function sendResetMail($user, string $token, string $context): bool
    {
        $email = $user->attributes?->email;

        if (!is_string($email) || $email === '') {
            return false;
        }

        $link = Config::resetUrl($context, $token);

        $body = '<p>' . e(__('aSocialAuth::login.reset_mail_intro')) . '</p>'
            . '<p><a href="' . e($link) . '">' . e(__('aSocialAuth::login.reset_mail_link')) . '</a></p>'
            . '<p><small>' . e(__('aSocialAuth::login.reset_mail_ignore')) . '</small></p>';

        try {
            return (bool) evo()->sendmail([
                'from'    => evo()->getConfig('site_name') . '<' . evo()->getConfig('emailsender') . '>',
                'to'      => $email,
                'subject' => __('aSocialAuth::login.reset_mail_subject'),
                'body'    => $body,
                'type'    => 'html',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send a password reset mail: ' . $e->getMessage());

            return false;
        }
    }

    // ------------------------------------------------------------------
    // Lookups
    // ------------------------------------------------------------------

    /**
     * Turn whatever the visitor typed into the username the core matches on.
     */
    public static function resolveUsername(string $login): ?string
    {
        $userModel = Config::getUserModel();

        try {
            if ($userModel::query()->where('username', $login)->exists()) {
                return $login;
            }

            if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            $user = static::findUserByEmail($login);

            return $user?->username;
        } catch (\Throwable $e) {
            Log::warning('Username resolution failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @return \EvolutionCMS\Models\User|null
     */
    public static function findUserByEmail(string $email)
    {
        $userModel = Config::getUserModel();

        try {
            return $userModel::query()
                ->with('attributes')
                ->whereHas('attributes', static fn ($query) => $query->where('email', $email))
                ->first();
        } catch (\Throwable $e) {
            Log::warning('User lookup by e-mail failed: ' . $e->getMessage());

            return null;
        }
    }

    public static function emailIsTaken(string $email): bool
    {
        return static::findUserByEmail($email) !== null;
    }

    /**
     * A free username derived from an address's local part.
     */
    public static function usernameFromEmail(string $email): string
    {
        $base = strtolower(Config::usernamePrefix() . strstr($email, '@', true));
        $base = preg_replace('/[^a-z0-9_.-]+/', '', $base) ?? '';
        $base = trim($base, '.-_');

        if (strlen($base) < 3) {
            $base = 'user';
        }

        $base      = substr($base, 0, 90);
        $userModel = Config::getUserModel();
        $candidate = $base;

        for ($i = 1; $i <= 1000; $i++) {
            try {
                if (!$userModel::query()->where('username', $candidate)->exists()) {
                    return $candidate;
                }
            } catch (\Throwable $e) {
                break;
            }

            $candidate = $base . $i;
        }

        return $base . '-' . bin2hex(random_bytes(4));
    }
}
