<?php

namespace Elcreator\aSocialAuth\Http\Controllers;

use Elcreator\aSocialAuth\Exceptions\ChallengeRequiredException;
use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Elcreator\aSocialAuth\Models\RegisteredProvider;
use Elcreator\aSocialAuth\Support\Config;
use Elcreator\aSocialAuth\Support\FlowState;
use Elcreator\aSocialAuth\Support\HybridAuthManager;
use Elcreator\aSocialAuth\Support\Identity;
use Elcreator\aSocialAuth\Support\Log;
use Elcreator\aSocialAuth\Support\PendingLogin;
use Elcreator\aSocialAuth\Support\SocialAuth;
use Elcreator\aSocialAuth\Support\UserResolver;
use Hybridauth\User\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Step two: the provider sends the browser back, and one of two things happens.
 *
 * Route: GET {prefix}/{provider}/callback
 *
 * Which one is decided by the intent parked in the session before the redirect,
 * not by anything in this request — the callback URL is the same either way, and
 * it is a URL a third party controls the shape of, so nothing in it is trusted
 * to choose between signing someone in and modifying an existing account.
 */
class SocialCallbackController extends SocialController
{
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        $registered = $this->provider($provider);
        $flow       = FlowState::consume($registered->slug);

        // No flow means the callback was reached without a start: a bookmarked
        // URL, a replay, a session that expired mid-round-trip. There is nothing
        // safe to infer, so it ends here.
        if ($flow === null) {
            Log::warning(
                "Social callback for '{$registered->slug}' arrived without a matching flow."
                . $this->diagnoseMissingFlow($request)
            );

            return $this->fail(__('aSocialAuth::login.error_expired'), Config::resolveContext());
        }

        $context = $flow['context'];

        try {
            $profile = $this->authenticate($registered);
        } catch (SocialAuthException $e) {
            return $this->fail($e->getMessage(), $context);
        }

        try {
            return $flow['intent'] === FlowState::INTENT_LINK
                ? $this->completeLink($registered, $profile, $flow, $context)
                : $this->completeLogin($registered, $profile, $flow, $context);
        } catch (SocialAuthException $e) {
            return $this->fail($e->getMessage(), $context);
        } catch (\Throwable $e) {
            Log::error("Unhandled error completing '{$registered->slug}' callback: " . $e->getMessage());

            return $this->fail(__('aSocialAuth::login.error_generic'), $context);
        }
    }

    /**
     * Explain, in the event log, why the flow was not where we left it.
     *
     * Nearly always one cause: a provider that answers with `response_mode=
     * form_post` — Apple, and OpenID 2.0 when the payload is too large for a URL
     * — sends the browser here as a *cross-site POST*. A session cookie set
     * `SameSite=Lax`, which is the Evolution CMS default and the browser default,
     * is deliberately withheld on exactly that request. So the session arrives
     * empty, the flow looks abandoned, and the visitor sees a generic "expired".
     *
     * A top-level GET redirect — every OAuth 2 provider, and Steam's OpenID — is
     * unaffected, which is why this only ever bites the site that enables Apple.
     * The fix is the site's to make (`SESSION_SAME_SITE=none` with
     * `SESSION_SECURE_COOKIE=true`), so the least this can do is name it.
     */
    protected function diagnoseMissingFlow(Request $request): string
    {
        if (!$request->isMethod('POST')) {
            return '';
        }

        $sameSite = strtolower((string) config('session.same_site', 'lax'));

        if ($sameSite === 'none') {
            return ' The callback was a cross-site POST and the session cookie is SameSite=None,'
                . ' so the session should have been sent; the flow most likely expired.';
        }

        return ' The callback was a cross-site POST (form_post response mode) while the session'
            . " cookie is SameSite={$sameSite}, so the browser withheld it and the session arrived"
            . ' empty. Set SESSION_SAME_SITE=none and SESSION_SECURE_COOKIE=true to support this'
            . ' provider.';
    }

    /**
     * Finish the OAuth exchange and read the profile.
     *
     * @throws SocialAuthException
     */
    protected function authenticate(RegisteredProvider $registered): Profile
    {
        try {
            $adapter = HybridAuthManager::adapterFor($registered);

            // On the callback URL this consumes the code the provider appended
            // and validates the state parameter.
            $adapter->authenticate();

            $profile = $adapter->getUserProfile();
        } catch (\Hybridauth\Exception\Exception $e) {
            Log::warning("OAuth callback failed for '{$registered->slug}': " . $e->getMessage());

            throw SocialAuthException::lexicon('error_oauth');
        } catch (\Throwable $e) {
            Log::error("Unexpected error in callback for '{$registered->slug}': " . $e->getMessage());

            throw SocialAuthException::lexicon('error_generic');
        }

        // The Evolution CMS session is what carries authentication from here on;
        // holding the provider's tokens open past that serves no purpose.
        try {
            $adapter->disconnect();
        } catch (\Throwable $e) {
            // Best effort.
        }

        return $profile;
    }

    /**
     * Sign the visitor in, creating or linking the account as configured.
     *
     * @throws SocialAuthException
     */
    protected function completeLogin(
        RegisteredProvider $registered,
        Profile $profile,
        array $flow,
        string $context
    ): RedirectResponse {
        $resolved = UserResolver::forLogin($registered, $profile);
        $userId   = $resolved['user_id'];

        try {
            SocialAuth::signIn(
                $userId,
                $context,
                $registered->slug,
                UserResolver::providerUserId($profile)
            );
        } catch (ChallengeRequiredException $e) {
            // A pipe — TOTP, SMS, device confirmation — needs input that an
            // OAuth callback cannot carry. Park the resolved identity and hand
            // the visitor to the page that can ask. Nothing is signed in yet.
            PendingLogin::store(
                $userId,
                $context,
                $registered->slug,
                $flow['return'],
                $e->challenge(),
                $e->payload()
            );

            Log::info(sprintf(
                'Social sign-in for user #%d via %s paused for challenge "%s".',
                $userId,
                $registered->slug,
                $e->challenge()
            ));

            return redirect()->away(
                Config::absoluteFor($context, $e->redirectPath())
            );
        }

        $this->fireLoginEvent($registered, $profile, $userId, $resolved['created'], $context);

        return redirect()->away($this->destination($context, $flow['return']));
    }

    /**
     * Attach this identity to the account that started the flow.
     *
     * @throws SocialAuthException
     */
    protected function completeLink(
        RegisteredProvider $registered,
        Profile $profile,
        array $flow,
        string $context
    ): RedirectResponse {
        $currentUserId = Identity::currentUserId($context);

        // The session must still hold the same user it held when the flow began.
        // If it does not — signed out, or signed in as someone else while the
        // provider had the browser — the identity would be attached to the wrong
        // account, so the attempt is abandoned rather than guessed at.
        if ($currentUserId === null || $currentUserId !== $flow['user_id']) {
            Log::warning(sprintf(
                'Link flow for %s abandoned: session user %s does not match the user that started it (%s).',
                $registered->slug,
                var_export($currentUserId, true),
                var_export($flow['user_id'], true)
            ));

            throw SocialAuthException::lexicon('error_link_session');
        }

        $account = UserResolver::forLink($registered, $profile, $currentUserId);

        FlowState::flashSuccess(__('aSocialAuth::login.linked', ['provider' => $registered->label]));

        if (function_exists('evo')) {
            try {
                evo()->invokeEvent('OnSocialAuthLink', [
                    'provider'         => $registered->slug,
                    'provider_user_id' => $account->provider_user_id,
                    'user_id'          => $currentUserId,
                    'context'          => $context,
                ]);
            } catch (\Throwable $e) {
                // Events are advisory.
            }
        }

        return redirect()->away($this->destination($context, $flow['return'], true));
    }

    protected function fireLoginEvent(
        RegisteredProvider $registered,
        Profile $profile,
        int $userId,
        bool $created,
        string $context
    ): void {
        if (!function_exists('evo')) {
            return;
        }

        try {
            evo()->invokeEvent('OnSocialAuthLogin', [
                'provider'         => $registered->slug,
                'provider_user_id' => UserResolver::providerUserId($profile),
                'user_id'          => $userId,
                'context'          => $context,
                'created'          => $created,
                'ip'               => request()->ip(),
                'user_agent'       => (string) request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Events are advisory; a listener blowing up must not undo a login
            // that has already happened.
            Log::warning('OnSocialAuthLogin listener failed: ' . $e->getMessage());
        }
    }
}
