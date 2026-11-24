<?php

namespace Elcreator\aSocialAuth\Http\Controllers;

use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Elcreator\aSocialAuth\Support\Config;
use Elcreator\aSocialAuth\Support\FlowState;
use Elcreator\aSocialAuth\Support\Identity;
use Elcreator\aSocialAuth\Support\IdTokenVerifier;
use Elcreator\aSocialAuth\Support\Log;
use Elcreator\aSocialAuth\Support\OneTap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Redeems the ID token the One Tap prompt handed to our page.
 *
 * Route: POST {prefix}/{provider}/onetap
 *
 * Posted by our own script from a page we rendered, so it is CSRF-checked —
 * unlike the OAuth callback, no third party is involved in the request.
 */
class OneTapController extends SocialController
{
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        $registered = $this->provider($provider);

        if (!OneTap::offeredBy($registered)) {
            throw new NotFoundHttpException();
        }

        $context    = Config::CONTEXT_WEB;
        $returnPath = $this->returnPath($request) ?? $this->refererPath($request);

        // Another tab got there first; never swap the account under the visitor.
        if (Identity::currentUserId($context) !== null) {
            return redirect()->away($this->destination($context, $returnPath));
        }

        $credential = $request->input('credential', '');

        try {
            $profile = IdTokenVerifier::verify(
                is_string($credential) ? $credential : '',
                OneTap::spec($registered),
                OneTap::clientId($registered),
                OneTap::consumeNonce()
            );

            return $this->completeLogin($registered, $profile, ['return' => $returnPath], $context);
        } catch (SocialAuthException $e) {
            return $this->refuse($e->getMessage(), $context, $returnPath);
        } catch (\Throwable $e) {
            Log::error("Unhandled error completing '{$registered->slug}' One Tap sign-in: " . $e->getMessage());

            return $this->refuse(__('aSocialAuth::login.error_generic'), $context, $returnPath);
        }
    }

    /**
     * Back to the page the prompt was on, which must not prompt straight away.
     */
    protected function refuse(string $message, string $context, ?string $returnPath): RedirectResponse
    {
        OneTap::suppress();

        if ($returnPath === null) {
            return $this->fail($message, $context);
        }

        FlowState::flashError($message);

        return redirect()->away($this->destination($context, $returnPath));
    }
}
