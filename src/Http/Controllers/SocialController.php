<?php

namespace Elcreator\aSocialAuth\Http\Controllers;

use Elcreator\aSocialAuth\Exceptions\ChallengeRequiredException;
use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Elcreator\aSocialAuth\Models\RegisteredProvider;
use Elcreator\aSocialAuth\Support\Config;
use Elcreator\aSocialAuth\Support\FlowState;
use Elcreator\aSocialAuth\Support\Log;
use Elcreator\aSocialAuth\Support\PendingLogin;
use Elcreator\aSocialAuth\Support\ProviderRegistry;
use Elcreator\aSocialAuth\Support\SocialAuth;
use Elcreator\aSocialAuth\Support\UserResolver;
use Hybridauth\User\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Shared plumbing for the social endpoints.
 */
abstract class SocialController
{
    /**
     * Resolve the {provider} segment to a provider the site actually offers.
     *
     * A disabled or unknown slug 404s rather than erroring. Anything else would
     * turn the endpoint into a probe for which providers a site has configured,
     * and the distinction is of no use to a legitimate visitor.
     */
    protected function provider(string $slug): RegisteredProvider
    {
        if (!Config::isEnabled()) {
            throw new NotFoundHttpException();
        }

        $provider = ProviderRegistry::resolve($slug);

        if ($provider === null) {
            throw new NotFoundHttpException();
        }

        return $provider;
    }

    /**
     * A caller-supplied return path, if it is a safe same-site one.
     *
     * Read from `input()` rather than `query()` so it works the same whether it
     * arrived on a link (the social buttons) or in a form body (the credential
     * forms).
     */
    protected function returnPath(Request $request): ?string
    {
        return Config::sanitizeReturnPath($request->input(Config::returnParam()));
    }

    /**
     * "Go back where you were" — the path of the referring page, when it is a
     * safe same-site one.
     *
     * The widget can sit on any page, so this is the only sensible destination
     * after connecting or disconnecting. Host and path are both checked, since
     * a Referer is chosen by whoever wrote the page the visitor clicked from.
     */
    protected function refererPath(Request $request): ?string
    {
        return Config::refererPath((string) $request->headers->get('referer', ''));
    }

    /**
     * Send the visitor somewhere with an error message waiting for them.
     */
    protected function fail(string $message, string $context): RedirectResponse
    {
        FlowState::flashError($message);

        return redirect()->away(Config::errorRedirect($context));
    }

    /**
     * Resolve where a completed flow should land.
     *
     * A return path captured when the flow started wins, then the configured
     * destination for the context. The return path has already been sanitised at
     * capture time, so it is a same-site path by construction.
     */
    protected function destination(string $context, ?string $returnPath, bool $linking = false): string
    {
        if ($returnPath !== null && $returnPath !== '') {
            return rtrim(Config::getSiteUrl(), '/') . $returnPath;
        }

        return $linking
            ? Config::linkRedirect($context)
            : Config::loginRedirect($context);
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
            // A pipe — TOTP, SMS, device confirmation — needs input that this
            // request cannot carry. Park the resolved identity and hand
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
