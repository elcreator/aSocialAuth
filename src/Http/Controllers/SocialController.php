<?php

namespace EvolutionCMS\aSocialAuth\Http\Controllers;

use EvolutionCMS\aSocialAuth\Models\RegisteredProvider;
use EvolutionCMS\aSocialAuth\Support\Config;
use EvolutionCMS\aSocialAuth\Support\FlowState;
use EvolutionCMS\aSocialAuth\Support\ProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Shared plumbing for the three social endpoints.
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
            abort(404);
        }

        $provider = ProviderRegistry::resolve($slug);

        if ($provider === null) {
            abort(404);
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
}
