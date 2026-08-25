<?php

namespace Elcreator\aSocialAuth\Http\Controllers;

use Elcreator\aSocialAuth\Exceptions\ChallengeRequiredException;
use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Elcreator\aSocialAuth\Support\Config;
use Elcreator\aSocialAuth\Support\Credentials;
use Elcreator\aSocialAuth\Support\EmailVerifier;
use Elcreator\aSocialAuth\Support\FlowState;
use Elcreator\aSocialAuth\Support\Identity;
use Elcreator\aSocialAuth\Support\Log;
use Elcreator\aSocialAuth\Support\PendingLogin;
use Elcreator\aSocialAuth\Support\SocialAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The e-mail-and-password forms.
 *
 * All four are POST and CSRF-protected — unlike the OAuth legs, these are
 * ordinary submissions from a page we rendered, so there is no reason to exempt
 * them.
 *
 * Every action redirects back with a message rather than rendering a page: the
 * forms live in a snippet on whatever template the site chose, and that page is
 * where the visitor should stay.
 */
class CredentialsController extends SocialController
{
    /**
     * POST {prefix}/credentials/login
     */
    public function login(Request $request): RedirectResponse
    {
        $context = $this->context($request);

        if (!Config::isEnabled() || !Config::credentialsLoginEnabled()) {
            abort(404);
        }

        try {
            $userId = Credentials::login(
                (string) $request->input('login', $request->input('username', $request->input('email', ''))),
                (string) $request->input('password', ''),
                $context,
                (bool) $request->input('rememberme', false)
            );
        } catch (ChallengeRequiredException $e) {
            // A second-factor pipe interrupted. Same handling as a social login:
            // park the attempt, hand the visitor to the page that can ask.
            PendingLogin::store(0, $context, 'credentials', $this->returnPath($request), $e->challenge(), $e->payload());

            return redirect()->away(Config::absoluteFor($context, $e->redirectPath()));
        } catch (SocialAuthException $e) {
            return $this->back($request, $context, $e->getMessage(), false);
        }

        Log::info("Credential sign-in for user #{$userId}.");

        return redirect()->away($this->destination($context, $this->returnPath($request)));
    }

    /**
     * POST {prefix}/credentials/register
     */
    public function register(Request $request): RedirectResponse
    {
        $context = $this->context($request);

        if (!Config::isEnabled() || !Config::credentialsRegistrationEnabled()) {
            abort(404);
        }

        try {
            $userId = Credentials::register([
                'email'                 => (string) $request->input('email', ''),
                'password'              => (string) $request->input('password', ''),
                'password_confirmation' => (string) $request->input('password_confirmation', ''),
                'username'              => (string) $request->input('username', ''),
                'fullname'              => (string) $request->input('fullname', ''),
            ], $context);
        } catch (SocialAuthException $e) {
            return $this->back($request, $context, $e->getMessage(), false);
        }

        if (!Config::loginAfterRegister()) {
            return $this->back($request, $context, __('aSocialAuth::login.registered'), true);
        }

        try {
            SocialAuth::signIn($userId, $context, 'credentials');
        } catch (ChallengeRequiredException $e) {
            PendingLogin::store($userId, $context, 'credentials', $this->returnPath($request), $e->challenge(), $e->payload());

            return redirect()->away(Config::absoluteFor($context, $e->redirectPath()));
        } catch (SocialAuthException $e) {
            // The account exists; only the automatic sign-in failed, so say so
            // rather than implying the registration did not happen.
            return $this->back($request, $context, __('aSocialAuth::login.registered'), true);
        }

        return redirect()->away($this->destination($context, $this->returnPath($request)));
    }

    /**
     * POST {prefix}/credentials/recover
     */
    public function recover(Request $request): RedirectResponse
    {
        $context = $this->context($request);

        if (!Config::isEnabled() || !Config::credentialsRecoveryEnabled()) {
            abort(404);
        }

        Credentials::requestReset((string) $request->input('email', ''), $context);

        // Always the same answer, whether or not the address had an account —
        // the difference is precisely what an account-enumeration probe wants.
        return $this->back($request, $context, __('aSocialAuth::login.recover_sent'), true);
    }

    /**
     * POST {prefix}/credentials/reset
     */
    public function reset(Request $request): RedirectResponse
    {
        $context = $this->context($request);

        if (!Config::isEnabled() || !Config::credentialsRecoveryEnabled()) {
            abort(404);
        }

        try {
            Credentials::resetPassword(
                (string) $request->input(Config::resetTokenParam(), $request->input('token', '')),
                (string) $request->input('password', ''),
                (string) $request->input('password_confirmation', '')
            );
        } catch (SocialAuthException $e) {
            return $this->back($request, $context, $e->getMessage(), false);
        }

        return $this->back($request, $context, __('aSocialAuth::login.reset_done'), true);
    }

    /**
     * POST {prefix}/credentials/email
     *
     * Claim an address for the account that is signed in. Nothing is written to
     * the user until the link is followed.
     */
    public function email(Request $request): RedirectResponse
    {
        $context = $this->context($request);

        if (!Config::isEnabled() || !Config::emailVerificationEnabled()) {
            abort(404);
        }

        $userId = Identity::currentUserId($context);

        if ($userId === null) {
            return $this->back($request, $context, __('aSocialAuth::login.error_link_signed_out'), false);
        }

        // Cancelling is the same form with an empty field, so a claim typed by
        // mistake can be withdrawn without waiting for it to expire.
        if ((bool) $request->input('cancel', false)) {
            EmailVerifier::cancel($userId);

            return $this->back($request, $context, __('aSocialAuth::login.verify_cancelled'), true);
        }

        try {
            EmailVerifier::request($userId, (string) $request->input('email', ''), $context);
        } catch (SocialAuthException $e) {
            return $this->back($request, $context, $e->getMessage(), false);
        }

        return $this->back($request, $context, __('aSocialAuth::login.verify_sent'), true);
    }

    /**
     * GET {prefix}/credentials/verify?token=…
     *
     * Follow the proof link. This is the only place a claimed address is moved
     * onto the user.
     */
    public function verify(Request $request): RedirectResponse
    {
        $context = $this->context($request);

        if (!Config::isEnabled() || !Config::emailVerificationEnabled()) {
            abort(404);
        }

        try {
            $result = EmailVerifier::confirm((string) $request->query('token', ''));
        } catch (SocialAuthException $e) {
            FlowState::flashError($e->getMessage());

            return redirect()->away(Config::verifyRedirect($context));
        }

        FlowState::flashSuccess(__('aSocialAuth::login.verify_done', ['email' => $result['email']]));

        if (function_exists('evo')) {
            try {
                evo()->invokeEvent('OnSocialAuthEmailVerified', [
                    'user_id' => $result['user_id'],
                    'email'   => $result['email'],
                    'context' => $context,
                ]);
            } catch (\Throwable $e) {
                // Advisory.
            }
        }

        return redirect()->away(Config::verifyRedirect($context));
    }

    /**
     * The context these forms operate in.
     *
     * A signed-in session wins — that is what a "change my password" form on a
     * profile page is operating in. Otherwise the configured resolution applies.
     */
    protected function context(Request $request): string
    {
        return Identity::currentContext()
            ?? Config::resolveContext((string) $request->headers->get('referer', ''));
    }

    /**
     * Return to the page the form was on, carrying a message.
     */
    protected function back(Request $request, string $context, string $message, bool $success): RedirectResponse
    {
        $success ? FlowState::flashSuccess($message) : FlowState::flashError($message);

        $returnPath = $this->returnPath($request) ?? $this->refererPath($request);

        if ($returnPath !== null) {
            return redirect()->away(rtrim(Config::getSiteUrl(), '/') . $returnPath);
        }

        return redirect()->away(Config::errorRedirect($context));
    }

}
