<?php

namespace Elcreator\aSocialAuth\Support;

use Elcreator\aSocialAuth\Exceptions\ChallengeRequiredException;
use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use EvolutionCMS\Exceptions\ServiceActionException;

/**
 * The package's public API — the two calls other code has reason to make.
 *
 * `signIn()` is what the callback controller uses and what a challenge page uses
 * to retry; keeping it in one place means the login payload, and therefore what
 * a pipe sees, is identical on the first attempt and every retry.
 */
class SocialAuth
{
    /**
     * Open a session for a resolved user.
     *
     * @param array $extra additional data merged into the login payload, for
     *                     pipes that asked for something (e.g. ['totp' => …])
     *
     * @throws ChallengeRequiredException a pipe wants interactive input first
     * @throws SocialAuthException        the login was refused
     */
    public static function signIn(
        int $userId,
        string $context,
        string $providerSlug,
        string $providerUserId = '',
        array $extra = []
    ): void {
        $payload = array_merge([
            'id'         => $userId,
            'rememberme' => false,
            'context'    => $context,

            // Markers a pipe can read through LoginAttempt::get(). They are what
            // makes the chain usable for social logins rather than merely
            // survivable: a captcha pipe can skip an attempt the provider has
            // already gated behind its own human check, and a second-factor pipe
            // can tell "no code was submitted" from "there was nowhere to submit
            // one" and throw ChallengeRequiredException instead of refusing.
            'social'                  => true,
            'social_provider'         => $providerSlug,
            'social_provider_user_id' => $providerUserId,
        ], $extra);

        try {
            // Entering through loginById means the core's `loginById` stage and
            // every '*' pipe run for a social sign-in exactly as for a password
            // one. That is deliberate: a second factor configured under '*'
            // would be worthless if the social door bypassed it.
            \UserManager::loginById($payload);
        } catch (ChallengeRequiredException $e) {
            throw $e;
        } catch (ServiceActionException $e) {
            // A pipe refused, or the account is blocked. The message was chosen
            // by whoever refused, so it is what the visitor should be told.
            Log::warning("Social sign-in refused for user #{$userId} via {$providerSlug}: " . $e->getMessage());

            throw new SocialAuthException($e->getMessage());
        } catch (SocialAuthException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("UserManager::loginById failed for user #{$userId}: " . $e->getMessage());

            throw SocialAuthException::lexicon('error_login');
        }
    }

    /**
     * Finish a sign-in that a pipe interrupted with a challenge.
     *
     * Call this from the page that collected the factor:
     *
     *     $destination = SocialAuth::completePendingLogin(['totp' => $_POST['code']]);
     *     header('Location: ' . $destination);
     *
     * The pending login is consumed on success and on refusal alike, so a wrong
     * code sends the visitor back to the start rather than leaving a half-open
     * attempt lying in the session to be guessed at. Pass `$keepOnFailure` to
     * allow retries, and impose your own attempt limit if you do.
     *
     * @return string the URL to send the visitor to
     *
     * @throws SocialAuthException when there is nothing pending, or it failed
     */
    public static function completePendingLogin(array $extra = [], bool $keepOnFailure = false): string
    {
        $pending = PendingLogin::peek();

        if ($pending === null) {
            throw SocialAuthException::lexicon('error_expired');
        }

        try {
            static::signIn(
                $pending['user_id'],
                $pending['context'],
                $pending['provider'],
                '',
                $extra
            );
        } catch (ChallengeRequiredException $e) {
            // Still not satisfied — a second factor on top of the first, or a
            // wrong code. The pending login stays, so the page can ask again.
            throw $e;
        } catch (\Throwable $e) {
            if (!$keepOnFailure) {
                PendingLogin::clear();
            }

            throw $e;
        }

        PendingLogin::clear();

        $returnPath = $pending['return'];

        if ($returnPath !== null && $returnPath !== '') {
            return rtrim(Config::getSiteUrl(), '/') . $returnPath;
        }

        return Config::loginRedirect($pending['context']);
    }

    /**
     * The challenge currently outstanding, or null. For a challenge page that
     * needs to render the right form, and to guard itself against direct visits.
     */
    public static function pendingChallenge(): ?array
    {
        return PendingLogin::peek();
    }

    /**
     * Abandon an outstanding challenge.
     */
    public static function cancelPendingLogin(): void
    {
        PendingLogin::clear();
    }
}
