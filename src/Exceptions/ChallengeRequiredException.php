<?php

namespace Elcreator\aSocialAuth\Exceptions;

use Elcreator\aSocialAuth\Support\PendingLogin;

/**
 * "This identity is proven, but I need something more before you may sign in."
 *
 * A login pipe that needs interactive input — a TOTP code, an SMS, a device
 * confirmation — has a problem specific to social login: there is no form to ask
 * on. A password login arrives as a POST the pipe can read a code out of; an
 * OAuth callback arrives as a GET from a third party, so the pipe has nowhere to
 * get the second factor from and no way to ask for it.
 *
 * Throwing a plain ServiceActionException at that point is the wrong answer:
 * the visitor sees "second factor required" and has no way to supply one, so
 * social sign-in is simply broken for every account with 2FA on.
 *
 * A pipe throws this instead. The callback parks the resolved user, redirects to
 * whatever page the pipe named, and that page collects the factor and calls
 * SocialAuth::completePendingLogin(['totp' => …]). The login is then retried
 * through the same pipeline with the extra data attached, so the pipe that
 * refused gets what it asked for and lets the attempt through.
 *
 * The identity is never treated as authenticated while a challenge is pending —
 * PendingLogin holds nothing but a user id and expires quickly, and the session
 * is only written when the retried pipeline runs to completion.
 *
 *     // in a TOTP pipe
 *     public function handle(LoginAttempt $attempt, Closure $next)
 *     {
 *         if (!$this->userHasTotp($attempt)) {
 *             return $next($attempt);
 *         }
 *
 *         if ($this->codeIsValid($attempt->get('totp'))) {
 *             return $next($attempt);
 *         }
 *
 *         // Social attempt with no code yet: ask for one instead of refusing.
 *         if ($attempt->get('social')) {
 *             throw ChallengeRequiredException::at('/two-factor', 'totp');
 *         }
 *
 *         throw new ServiceActionException('second factor required');
 *     }
 */
class ChallengeRequiredException extends SocialAuthException
{
    /** Where the visitor must go to satisfy the challenge. */
    protected string $redirectPath;

    /** An identifier the challenge page can switch on, e.g. 'totp', 'captcha'. */
    protected string $challenge;

    /** Anything else the pipe wants to hand to the challenge page. */
    protected array $payload;

    public function __construct(string $redirectPath, string $challenge = '', array $payload = [], string $message = '')
    {
        parent::__construct($message !== '' ? $message : 'Additional verification required.');

        $this->redirectPath = $redirectPath;
        $this->challenge    = $challenge;
        $this->payload      = $payload;
    }

    /**
     * @param string $redirectPath a site-relative path, e.g. '/two-factor'
     */
    public static function at(string $redirectPath, string $challenge = '', array $payload = []): self
    {
        return new self($redirectPath, $challenge, $payload);
    }

    public function redirectPath(): string
    {
        return $this->redirectPath;
    }

    public function challenge(): string
    {
        return $this->challenge;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * The pending login this challenge belongs to, once the callback has parked
     * it. Useful to a challenge page that needs to know whose factor to check.
     */
    public function pending(): ?array
    {
        return PendingLogin::peek();
    }
}
