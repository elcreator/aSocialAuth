<?php

namespace Elcreator\aSocialAuth\Support;

/**
 * Renders the package's UI.
 *
 * Each surface is reachable three ways — an Evolution CMS event, a snippet tag,
 * and a plain function — so the view data is assembled once here rather than in
 * each entry point.
 */
class Renderer
{
    /** Whether the stylesheet has already gone out this request. */
    protected static bool $stylesEmitted = false;

    /**
     * Whether the styles partial should emit anything right now.
     *
     * Called from the Blade partial. Answers false when the site has turned
     * inline styles off, and false on every call after the first, so several
     * widgets on one page do not repeat a few kilobytes of CSS.
     */
    public static function shouldEmitStyles(): bool
    {
        if (!Config::inlineStyles()) {
            return false;
        }

        if (static::$stylesEmitted) {
            return false;
        }

        return static::$stylesEmitted = true;
    }

    /**
     * Reset the once-per-request guard. For tests and long-running workers.
     */
    public static function resetStyles(): void
    {
        static::$stylesEmitted = false;
    }

    /**
     * The manager login page's buttons.
     *
     * Deliberately buttons only: the manager already has its own username and
     * password form, and a second one underneath it would be nonsense.
     */
    public static function loginButtons(?string $returnPath = null): string
    {
        if (!Config::isEnabled()) {
            return '';
        }

        $providers = ProviderRegistry::enabled();

        if ($providers->isEmpty()) {
            return '';
        }

        return static::render(Config::view('manager_login'), [
            'providers'  => $providers,
            'error'      => FlowState::takeError(),
            'returnPath' => $returnPath,
        ]);
    }

    /**
     * The front-end sign-in widget: credential forms plus provider buttons,
     * inline or in a modal.
     *
     * @param array $options mode, form, trigger, class, credentials, providers
     */
    public static function loginWidget(?string $returnPath = null, array $options = []): string
    {
        if (!Config::isEnabled()) {
            return '';
        }

        $form = static::resolveForm($options['form'] ?? null);

        $showCredentials = array_key_exists('credentials', $options)
            ? static::flag($options['credentials'])
            : true;

        $showProviders = array_key_exists('providers', $options)
            ? static::flag($options['providers'])
            : true;

        $providers = $showProviders ? ProviderRegistry::enabled() : collect();

        // Nothing to render is better than an empty box: if the site turned the
        // credential forms off and configured no providers, say nothing.
        if ($providers->isEmpty() && !$showCredentials) {
            return '';
        }

        return static::render(Config::view('login'), [
            'providers'       => $providers,
            'mode'            => Config::normaliseMode($options['mode'] ?? Config::widgetMode()),
            'form'            => $form,
            'showCredentials' => $showCredentials,
            'error'           => FlowState::takeError(),
            'success'         => FlowState::takeSuccess(),
            'returnPath'      => $returnPath,
            'resetToken'      => static::resetTokenFromRequest(),
            'trigger'         => (string) ($options['trigger'] ?? __('aSocialAuth::login.sign_in')),
            'wrapperClass'    => trim((string) ($options['class'] ?? Config::wrapperClass())),
        ]);
    }

    /**
     * The linked-accounts widget for whoever is currently signed in.
     *
     * Renders nothing for a guest, so it is safe on a template shared with
     * anonymous visitors.
     */
    public static function identitiesWidget(?string $returnPath = null): string
    {
        if (!Config::isEnabled() || !Config::linkingEnabled()) {
            return '';
        }

        $userId = Identity::anyCurrentUserId();

        if ($userId === null) {
            return '';
        }

        $pending = Config::emailVerificationEnabled()
            ? EmailVerifier::pendingFor($userId)
            : null;

        return static::render(Config::view('identities'), [
            'accounts'       => Identity::linkedAccounts($userId),
            'connectable'    => Identity::connectableProviders($userId),
            'canUnlink'      => Config::allowUnlinkLast(),
            'error'          => FlowState::takeError(),
            'success'        => FlowState::takeSuccess(),
            'returnPath'     => $returnPath,

            // An account that signed up through Telegram has a synthesised
            // address; this is what turns the prompt into an actual form.
            'needsEmail'     => static::placeholderEmailFor($userId) !== null,
            'canVerifyEmail' => Config::emailVerificationEnabled(),
            'pendingEmail'   => $pending?->email,
        ]);
    }

    /**
     * Which credential form to show.
     *
     * A reset token in the URL wins over anything asked for: arriving on a page
     * from a reset mail should show the "choose a new password" form, whatever
     * the snippet's default was.
     */
    public static function resolveForm($requested): string
    {
        if (static::resetTokenFromRequest() !== null) {
            return 'reset';
        }

        $requested = strtolower(trim((string) ($requested ?? '')));

        if ($requested === '') {
            $requested = strtolower(trim((string) ($_GET['form'] ?? '')));
        }

        return in_array($requested, ['login', 'register', 'recover', 'reset'], true)
            ? $requested
            : 'login';
    }

    /**
     * The reset token on the current URL, if there is one.
     */
    public static function resetTokenFromRequest(): ?string
    {
        $token = $_GET[Config::resetTokenParam()] ?? null;

        if (!is_string($token)) {
            return null;
        }

        $token = trim($token);

        // Tokens are hex-ish; anything else is not one, and echoing an arbitrary
        // query value back into a hidden field is not worth the risk.
        return preg_match('/^[A-Za-z0-9._-]{8,255}$/', $token) ? $token : null;
    }

    /**
     * The account's own e-mail when it is one this package synthesised.
     *
     * Worth surfacing: an account that registered through Telegram has no real
     * address, so it has no route back in if every linked identity is lost, and
     * it cannot be given manager access safely. The widget nudges the owner to
     * add one.
     */
    protected static function placeholderEmailFor(int $userId): ?string
    {
        try {
            $userModel = Config::getUserModel();
            $user      = $userModel::query()->with('attributes')->find($userId);
            $email     = $user?->attributes?->email;
        } catch (\Throwable $e) {
            return null;
        }

        return UserResolver::isPlaceholderEmail($email) ? $email : null;
    }

    /**
     * Snippet parameters arrive as strings, so "0" and "false" have to mean no.
     */
    protected static function flag($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return !in_array(strtolower(trim((string) $value)), ['0', 'false', 'no', 'off', ''], true);
    }

    protected static function render(string $view, array $data): string
    {
        return (string) \View::make($view, $data)->render();
    }
}
