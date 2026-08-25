<?php

namespace Elcreator\aSocialAuth\Http\Controllers;

use Elcreator\aSocialAuth\Models\SocialAccount;
use Elcreator\aSocialAuth\Support\Config;
use Elcreator\aSocialAuth\Support\FlowState;
use Elcreator\aSocialAuth\Support\HybridAuthManager;
use Elcreator\aSocialAuth\Support\Identity;
use Elcreator\aSocialAuth\Support\Log;
use Elcreator\aSocialAuth\Support\ProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Adding and removing identities on an account that is already signed in.
 *
 * This is the half that makes several providers add up to one person: sign in
 * with Google today, press "connect" next to Telegram tomorrow, and both then
 * open the same account. The rows go in `social_accounts`, so the tenth provider
 * costs exactly what the first did.
 */
class SocialLinkController extends SocialController
{
    /**
     * Begin attaching a provider to the current account.
     *
     * Route: GET {prefix}/{provider}/link
     */
    public function start(Request $request, string $provider)
    {
        $registered = $this->provider($provider);
        $context    = Identity::currentContext() ?? Config::resolveContext(
            (string) $request->headers->get('referer', '')
        );

        if (!Config::linkingEnabled()) {
            abort(404);
        }

        $userId = Identity::currentUserId($context);

        if ($userId === null) {
            return $this->fail(__('aSocialAuth::login.error_link_signed_out'), $context);
        }

        FlowState::start(
            $registered->slug,
            FlowState::INTENT_LINK,
            $context,
            $this->returnPath($request) ?? $this->refererPath($request),
            $userId
        );

        try {
            $adapter = HybridAuthManager::adapterFor($registered);
            $adapter->authenticate();
        } catch (\Throwable $e) {
            Log::error("Link initiation failed for '{$registered->slug}': " . $e->getMessage());
            FlowState::clear();

            return $this->fail(__('aSocialAuth::login.error_generic'), $context);
        }

        return redirect()->away(Config::buildCallbackUrl($registered->slug));
    }

    /**
     * Detach a provider from the current account.
     *
     * Route: POST {prefix}/{provider}/unlink
     */
    public function unlink(Request $request, string $provider): RedirectResponse
    {
        if (!Config::isEnabled() || !Config::linkingEnabled()) {
            abort(404);
        }

        $context = Identity::currentContext() ?? Config::resolveContext(
            (string) $request->headers->get('referer', '')
        );

        $userId = Identity::currentUserId($context);

        if ($userId === null) {
            return $this->fail(__('aSocialAuth::login.error_link_signed_out'), $context);
        }

        // Unlinking must reach identities of providers the site has since turned
        // off, so this looks the provider up in the registry rather than among
        // the enabled ones — otherwise disabling a provider would strand every
        // identity attached to it.
        $registered = ProviderRegistry::find($provider);

        if ($registered === null) {
            abort(404);
        }

        $account = SocialAccount::query()
            ->where('user_id', $userId)
            ->where('provider_id', $registered->getKey())
            ->first();

        if ($account === null) {
            return $this->back($context, $request, __('aSocialAuth::login.error_not_linked'), false);
        }

        if (!Config::allowUnlinkLast() && Identity::isLastIdentity($userId)) {
            // An account created through social login has no password its owner
            // knows, so removing its only identity would lock them out with no
            // way back in.
            return $this->back($context, $request, __('aSocialAuth::login.error_unlink_last'), false);
        }

        $providerUserId = $account->provider_user_id;

        try {
            $account->delete();
        } catch (\Throwable $e) {
            Log::error("Failed to unlink {$registered->slug} from user #{$userId}: " . $e->getMessage());

            return $this->back($context, $request, __('aSocialAuth::login.error_generic'), false);
        }

        Log::info("Unlinked {$registered->slug} identity {$providerUserId} from user #{$userId}.");

        if (function_exists('evo')) {
            try {
                evo()->invokeEvent('OnSocialAuthUnlink', [
                    'provider'         => $registered->slug,
                    'provider_user_id' => $providerUserId,
                    'user_id'          => $userId,
                    'context'          => $context,
                ]);
            } catch (\Throwable $e) {
                // Advisory.
            }
        }

        return $this->back(
            $context,
            $request,
            __('aSocialAuth::login.unlinked', ['provider' => $registered->label]),
            true
        );
    }

    /**
     * Return to the page the widget was rendered on, with a message.
     */
    protected function back(string $context, Request $request, string $message, bool $success): RedirectResponse
    {
        $success ? FlowState::flashSuccess($message) : FlowState::flashError($message);

        $returnPath = Config::sanitizeReturnPath($request->input(Config::returnParam()))
            ?? $this->refererPath($request);

        return redirect()->away($this->destination($context, $returnPath, true));
    }

}
