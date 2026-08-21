<?php

namespace EvolutionCMS\aSocialAuth\Http\Controllers;

use EvolutionCMS\aSocialAuth\Support\Config;
use EvolutionCMS\aSocialAuth\Support\FlowState;
use EvolutionCMS\aSocialAuth\Support\HybridAuthManager;
use EvolutionCMS\aSocialAuth\Support\Log;
use Illuminate\Http\Request;

/**
 * Step one: send the visitor to the provider.
 *
 * Route: GET {prefix}/{provider}
 */
class SocialLoginController extends SocialController
{
    public function __invoke(Request $request, string $provider)
    {
        $registered = $this->provider($provider);

        // Decide the context here rather than in the callback: by the time the
        // provider redirects back, the referring page — the only evidence of
        // whether this started in the manager or on the front end — is gone.
        $context = Config::resolveContext((string) $request->headers->get('referer', ''));

        FlowState::start(
            $registered->slug,
            FlowState::INTENT_LOGIN,
            $context,
            $this->returnPath($request)
        );

        try {
            $adapter = HybridAuthManager::adapterFor($registered);

            // On the first pass this redirects the browser to the provider and
            // never returns — HybridAuth exits inside authenticate().
            $adapter->authenticate();
        } catch (\Throwable $e) {
            Log::error("Social login initiation failed for '{$registered->slug}': " . $e->getMessage());
            FlowState::clear();

            return $this->fail(__('aSocialAuth::login.error_generic'), $context);
        }

        // Reached only when a valid token was already in the session, in which
        // case the callback endpoint is where the profile gets processed.
        return redirect()->away(Config::buildCallbackUrl($registered->slug));
    }
}
