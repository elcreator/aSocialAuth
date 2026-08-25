<?php

use Elcreator\aSocialAuth\Enums\SocialProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Social Auth
    |--------------------------------------------------------------------------
    |
    | Master switch. With this off the routes 404 and no buttons are rendered,
    | so the package can stay installed while being completely inert.
    |
    */
    'enable' => env('ASOCIALAUTH_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Session context
    |--------------------------------------------------------------------------
    |
    | Which kind of session a social login opens. Evolution CMS 3.x keeps manager
    | users and web users in one `users` table and tells them apart by role, so
    | the same OAuth round trip can open either.
    |
    |   'mgr'  – manager session; the user still needs a role granting
    |            access_permissions to actually reach the manager.
    |   'web'  – front-end session; this is what a member area wants.
    |   'auto' – whichever side the flow started on: 'mgr' when the visitor came
    |            from the manager login page, 'web' otherwise.
    |
    | 'auto' is the right answer for a site doing both. 'mgr' is the default only
    | because that is what installs of this package predating contexts did.
    |
    */
    'context' => env('ASOCIALAUTH_CONTEXT', 'mgr'),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Every URL the package answers on is built from this block, so a site that
    | does not want /asocialauth/ in its URLs changes `prefix` and nothing else.
    | The callback URL registered with each provider is derived from the same
    | values, so it always follows the prefix — but changing the prefix on a live
    | site means re-registering the redirect URI in every provider's console.
    |
    | `patterns` are appended to the prefix. `{provider}` is the required
    | placeholder. Change these to localise the URLs, e.g.
    |
    |     'prefix'   => 'auth',
    |     'patterns' => [
    |         'login'    => 'in/{provider}',
    |         'callback' => 'in/{provider}/back',
    |     ],
    |
    | gives /auth/in/google and /auth/in/google/back.
    |
    | `middleware` null means "the application's global middleware stack". The
    | OAuth endpoints must not sit behind CSRF verification: the provider
    | redirects the browser back with a GET that cannot carry a CSRF token.
    | HybridAuth's own `state` parameter is what protects that leg. The unlink
    | endpoint is POST-only and does get CSRF, since it is an ordinary form post
    | from a page we rendered.
    |
    */
    'routes' => [
        'enable' => true,

        'prefix' => env('ASOCIALAUTH_ROUTE_PREFIX', 'asocialauth'),

        // The credential patterns sit under their own segment so they can never
        // be shadowed by the {provider} route, which would otherwise match a
        // bare "login" as a provider slug.
        'patterns' => [
            'login'    => '{provider}',
            'callback' => '{provider}/callback',
            'link'     => '{provider}/link',
            'unlink'   => '{provider}/unlink',

            'credentials_login'    => 'credentials/login',
            'credentials_register' => 'credentials/register',
            'credentials_recover'  => 'credentials/recover',
            'credentials_reset'    => 'credentials/reset',
            'credentials_email'    => 'credentials/email',
            'credentials_verify'   => 'credentials/verify',
        ],

        // Named routes get this prefix, so route('asocialauth.login', …) works.
        'name_prefix' => 'asocialauth.',

        // null = config('app.middleware.global'). Pass an array to override.
        'middleware' => null,

        // Regex the {provider} segment must match. Catalogue slugs are lowercase
        // letters and digits only.
        'provider_pattern' => '[a-z0-9]+',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    |
    | Where the visitor lands after each outcome. Paths are relative: manager
    | paths resolve against the manager URL, web paths against the site URL. An
    | empty string means "the natural root for that context".
    |
    | `return_param` lets a link carry its own destination, e.g.
    | /asocialauth/google?return=/members/dashboard. Only same-site paths are
    | honoured — an absolute or protocol-relative URL is discarded, so the
    | parameter cannot be turned into an open redirect.
    |
    */
    'redirects' => [
        'mgr'          => '',
        'web'          => '/',
        'link'         => '',
        'error'        => '',
        'return_param' => 'return',
    ],

    /*
    |--------------------------------------------------------------------------
    | Appearance
    |--------------------------------------------------------------------------
    |
    | The buttons and the identities list are widgets, not a fixed chrome: every
    | element carries a stable, prefixed class, and every colour is a CSS custom
    | property, so a site can restyle them from its own stylesheet without
    | touching the package or fighting specificity.
    |
    |   .asocialauth                  wrapper, plus --asa-* custom properties
    |   .asocialauth--login           the sign-in widget
    |   .asocialauth--identities      the linked-accounts widget
    |   .asocialauth--modal           modal presentation
    |   .asocialauth__btn             one provider button
    |   .asocialauth__btn--google     …and a modifier per provider slug
    |   .asocialauth__list/-item      the identities list
    |
    | `inline_styles` emits the package's own stylesheet once per page. Turn it
    | off once your theme styles the classes itself — the markup then arrives
    | completely unstyled, which is what a design system wants.
    |
    | `mode` is the default presentation for the front-end widget: 'inline'
    | drops the buttons into the page, 'modal' renders a trigger that opens them
    | in a dialog. Either can be overridden per call:
    |
    |     [[aSocialAuthButtons? &mode=`modal` &trigger=`Sign in`]]
    |
    | `views` lets a site point any of the three surfaces at its own Blade view
    | instead of publishing and editing the packaged one.
    |
    */
    'ui' => [
        'inline_styles' => env('ASOCIALAUTH_INLINE_STYLES', true),
        'mode'          => env('ASOCIALAUTH_WIDGET_MODE', 'inline'),
        'wrapper_class' => '',

        'views' => [
            'login'         => 'aSocialAuth::widget.login',
            'identities'    => 'aSocialAuth::widget.identities',
            'manager_login' => 'aSocialAuth::manager.login-buttons',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Account linking
    |--------------------------------------------------------------------------
    |
    | One Evolution CMS user can own many provider identities: signing in with
    | Google and later adding Telegram leaves one account reachable both ways.
    | Rows live in `social_accounts`, so a new network costs a row, never a
    | column.
    |
    | `match_by_email` is the automatic half: an identity whose e-mail matches an
    | existing user is attached to that user on first sight, instead of becoming
    | a second account for the same person.
    |
    | `require_verified_email` restricts that to providers that actually verify
    | the address they hand out. Leave it on. With it off, any provider that lets
    | a user type an arbitrary e-mail into their own profile becomes a way to
    | take over the matching Evolution CMS account.
    |
    | `require_local_verified_email` is the same defence pointing the other way.
    | The provider vouching for an address only proves the person arriving owns
    | it; it says nothing about whether the *local* account that holds it does.
    | Without this, self-registration becomes a way to collect other people's
    | identities: register with someone else's address, wait for them to sign in
    | with Google, and their identity attaches to your account. Accounts created
    | from a provider-verified address are flagged verified already, so the
    | ordinary social path is unaffected — this only gates accounts whose address
    | was typed into a form and never proven.
    |
    | `allow_unlink_last` decides whether the widget may remove a user's only
    | remaining identity. Off, because an account created through social login
    | holds no usable password, so removing its last identity locks the owner out
    | for good.
    |
    */
    'linking' => [
        'enable'                       => true,
        'match_by_email'               => true,
        'require_verified_email'       => true,
        'require_local_verified_email' => true,
        'allow_unlink_last'            => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration of new users
    |--------------------------------------------------------------------------
    |
    | When an identity matches no existing user, either the login fails or a new
    | user is created. `enable` picks which.
    |
    | `role` is the role id given to users created this way. 0 is "no user role"
    | — a web user with no manager access whatsoever — and is the only safe
    | default: any other value hands manager capabilities to whoever can complete
    | an OAuth flow with an enabled provider.
    |
    | `placeholder_email_domain` covers providers that never return an e-mail;
    | Telegram and Steam are the common ones. Evolution CMS requires a unique,
    | non-empty e-mail on every user, so one is synthesised from the provider and
    | the opaque user id: telegram-a1b2c3d4@social.invalid. The .invalid TLD is
    | reserved by RFC 2606 and can never resolve, so nothing will try to deliver
    | to it and it cannot collide with a real address. Users created this way are
    | flagged unverified; ask for a real address later if you need one.
    |
    */
    'registration' => [
        'enable'                   => env('ASOCIALAUTH_CREATE_USERS', false),
        'role'                     => env('ASOCIALAUTH_DEFAULT_ROLE', 0),
        'groups'                   => [],
        'placeholder_email_domain' => env('ASOCIALAUTH_PLACEHOLDER_EMAIL_DOMAIN', 'social.invalid'),
        'username_prefix'          => env('ASOCIALAUTH_USERNAME_PREFIX', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | E-mail and password
    |--------------------------------------------------------------------------
    |
    | The ordinary way in, offered by the same widget as the social buttons. A
    | sign-in box with nothing but third-party buttons hands every account on the
    | site to whichever providers happen to be enabled, and strands anyone who
    | uses none of them — so all four password surfaces are available:
    |
    |   login    – username or e-mail plus password
    |   register – create an account
    |   recover  – ask for a reset link
    |   reset    – set a new password from that link
    |
    | `login` is on by default; the rest are opt-in, because a site that does not
    | want public sign-ups should not get them by installing a login widget.
    |
    | `reset_path` is the page on your site carrying the reset form — the one
    | with [[aSocialAuthCredentials? &form=`reset`]] on it. The link mailed out
    | points there with the token attached. The core only ever builds a *manager*
    | reset link and skips the mail entirely on the front end, so this is what
    | makes front-end recovery work at all; leave it empty and no reset mail is
    | sent.
    |
    | `login_field` picks what the sign-in form asks for: 'email', 'username' or
    | 'both' (either one is accepted, which is what most people expect).
    |
    */
    'credentials' => [
        'login'    => env('ASOCIALAUTH_CREDENTIALS_LOGIN', true),
        'register' => env('ASOCIALAUTH_CREDENTIALS_REGISTER', false),
        'recover'  => env('ASOCIALAUTH_CREDENTIALS_RECOVER', true),

        'login_field'         => env('ASOCIALAUTH_LOGIN_FIELD', 'both'),
        'min_password_length' => env('ASOCIALAUTH_MIN_PASSWORD_LENGTH', 6),

        // Sign the visitor in straight after a successful registration.
        'login_after_register' => env('ASOCIALAUTH_LOGIN_AFTER_REGISTER', true),

        'reset_path' => env('ASOCIALAUTH_RESET_PATH', ''),

        // Adding and proving an e-mail address after signing up without one —
        // the Telegram and Steam case. The claimed address is held aside and
        // only written onto the user once the link is followed, so an unproven
        // claim can neither collect someone else's provider identity through
        // match_by_email nor block them from registering it.
        //
        // `verify_redirect` is where the confirmation link lands the visitor
        // afterwards; empty means the context's own root.
        'verify_email'     => env('ASOCIALAUTH_VERIFY_EMAIL', true),
        'verify_ttl'       => env('ASOCIALAUTH_VERIFY_TTL', 1440),
        'verify_redirect'  => env('ASOCIALAUTH_VERIFY_REDIRECT', ''),

        // Query parameter the reset link carries the token in. 'hash' matches
        // the core's own manager reset link, so both can share a page.
        'reset_token_param' => 'hash',
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Everything HybridAuth ships is available by slug — 50+ networks, listed in
    | Elcreator\aSocialAuth\Enums\SocialProvider. This block only says which
    | of them this site offers and with what credentials; it does not limit the
    | choice. Any catalogued slug can be added:
    |
    |     'discord' => [
    |         'enabled' => true,
    |         'keys'    => ['id' => env('…'), 'secret' => env('…')],
    |     ],
    |
    | Per-provider keys, all optional except `enabled` and the credentials:
    |
    |   enabled  – offer this provider
    |   label    – button label (defaults to the catalogue label)
    |   keys     – ['id' => …, 'secret' => …] as the provider's console gives them
    |   scope    – override the OAuth scope string
    |   sort     – button order, ascending
    |   icon     – slug of the built-in icon to use, when it differs
    |   adapter  – HybridAuth class name, to point a slug at a different adapter
    |   extra    – further keys passed straight to the HybridAuth adapter
    |
    | The five below are scaffolded because they are the common case. All are off
    | until credentials exist, and a provider whose required keys are empty stays
    | hidden even with enabled = true — so a half-filled .env can never render a
    | button that would fail on click.
    |
    */
    'providers' => [

        SocialProvider::GOOGLE->value => [
            'enabled' => env('ASOCIALAUTH_GOOGLE_ENABLED', false),
            'keys'    => [
                'id'     => env('ASOCIALAUTH_GOOGLE_CLIENT_ID', ''),
                'secret' => env('ASOCIALAUTH_GOOGLE_CLIENT_SECRET', ''),
            ],
            'sort' => 10,
        ],

        SocialProvider::FACEBOOK->value => [
            'enabled' => env('ASOCIALAUTH_FACEBOOK_ENABLED', false),
            'keys'    => [
                'id'     => env('ASOCIALAUTH_FACEBOOK_CLIENT_ID', ''),
                'secret' => env('ASOCIALAUTH_FACEBOOK_CLIENT_SECRET', ''),
            ],
            'sort'  => 20,
            'extra' => [
                // Turn on only behind a proxy you control: it makes the adapter
                // trust X-Forwarded-* headers a client could otherwise forge.
                'trustForwarded' => false,
            ],
        ],

        SocialProvider::LINKEDIN->value => [
            'enabled' => env('ASOCIALAUTH_LINKEDIN_ENABLED', false),
            'keys'    => [
                'id'     => env('ASOCIALAUTH_LINKEDIN_CLIENT_ID', ''),
                'secret' => env('ASOCIALAUTH_LINKEDIN_CLIENT_SECRET', ''),
            ],
            'sort' => 30,
        ],

        SocialProvider::X->value => [
            'enabled' => env('ASOCIALAUTH_X_ENABLED', false),
            'keys'    => [
                'id'     => env('ASOCIALAUTH_X_CLIENT_ID', ''),
                'secret' => env('ASOCIALAUTH_X_CLIENT_SECRET', ''),
            ],
            'sort' => 40,
        ],

        SocialProvider::TELEGRAM->value => [
            // Telegram is the emailless case the placeholder domain exists for:
            // `id` is the bot username, `secret` the bot token from @BotFather.
            'enabled' => env('ASOCIALAUTH_TELEGRAM_ENABLED', false),
            'keys'    => [
                'id'     => env('ASOCIALAUTH_TELEGRAM_BOT_NAME', ''),
                'secret' => env('ASOCIALAUTH_TELEGRAM_BOT_TOKEN', ''),
            ],
            'sort' => 50,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Custom providers
    |--------------------------------------------------------------------------
    |
    | Slugs the catalogue does not know: an in-house Keycloak realm, a fork of a
    | HybridAuth adapter, a network added after this package was released. Same
    | shape as `providers`, plus a required `adapter` naming the HybridAuth class
    | (or a fully qualified class of your own implementing AdapterInterface).
    |
    |     'acme' => [
    |         'enabled' => true,
    |         'label'   => 'Acme SSO',
    |         'adapter' => 'Keycloak',
    |         'keys'    => ['id' => …, 'secret' => …],
    |         'extra'   => ['url' => 'https://sso.acme.test/'],
    |     ],
    |
    */
    'custom_providers' => [],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    */
    'models' => [
        'social_account'      => Elcreator\aSocialAuth\Models\SocialAccount::class,
        'registered_provider' => Elcreator\aSocialAuth\Models\RegisteredProvider::class,
        'user'                => EvolutionCMS\Models\User::class,
    ],
];
