# aSocialAuth

Authentication for [Evolution CMS](https://github.com/evolution-cms/evolution) 3.5+ — social, password, or both, in one widget.

- **Every provider HybridAuth ships.** All 52 of them, catalogued as an enum. Enabling one is a config entry; there is no per-provider code.
- **One account, many identities.** Sign in with Google today, add Telegram tomorrow — both open the same account. Identities are rows, not columns, so the tenth provider costs what the first did.
- **E-mail and password too.** Sign in, register, request a reset link, set a new password. A login box with nothing but third-party buttons strands everyone who uses none of them.
- **Registration without an e-mail address.** Whenever a provider returns none — Telegram, Steam, a user who withheld consent — the account still works, and the owner can add and verify an address later.
- **Configurable URLs.** Don't want `/asocialauth/`? Change one value.
- **A widget, not a fixed chrome.** Inline or modal, every hook a class, every colour a custom property, and the stylesheet can be turned off entirely.
- **Ready for whatever is in your login pipeline.** A captcha or TOTP pipe can tell a social attempt from a form post, and can ask the visitor for a code even though an OAuth callback has no form.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.3+ |
| Evolution CMS | 3.5.2+ |
| hybridauth/hybridauth | dev-master |

---

## Installation

```bash
composer require elcreator/asocialauth
php artisan migrate
```

Then switch it on:

```env
ASOCIALAUTH_ENABLED=true
ASOCIALAUTH_GOOGLE_ENABLED=true
ASOCIALAUTH_GOOGLE_CLIENT_ID=123456789.apps.googleusercontent.com
ASOCIALAUTH_GOOGLE_CLIENT_SECRET=GOCSPX-…
```

A **Sign in with Google** button appears on the manager login page. Register the redirect URI shown by `/asocialauth/google/callback` in the Google console and you are done.

Optional publishing:

```bash
php artisan vendor:publish --tag=asocialauth-config   # core/custom/config/cms/settings/aSocialAuth.php
php artisan vendor:publish --tag=asocialauth-views
php artisan vendor:publish --tag=asocialauth-lang
```

### Languages

Every manager language Evolution CMS ships with is translated: `az be bg cs da de en es fa fi fr he it ja nl nn/no pl pt sv uk zh`. The active manager language is used automatically; anything else falls back to English.

---

## The widget

Three snippets, all of which take `&return=` to come back to the page they sit on.

```
[[aSocialAuthLogin]]                                   sign-in: forms + provider buttons
[[aSocialAuthLogin? &mode=`modal` &trigger=`Sign in`]]  the same, behind a dialog
[[aSocialAuthLogin? &form=`register`]]                  open on the registration form
[[aSocialAuthButtons]]                                  provider buttons only
[[aSocialAuthCredentials]]                              e-mail and password only
[[aSocialAuthIdentities]]                               linked accounts, for a profile page
```

| Parameter | Values | Meaning |
|---|---|---|
| `mode` | `inline` (default), `modal` | Embedded in the page, or behind a trigger button |
| `form` | `login`, `register`, `recover`, `reset` | Which credential form to show |
| `credentials` | `1` (default), `0` | Show the e-mail/password forms |
| `providers` | `1` (default), `0` | Show the provider buttons |
| `trigger` | any string | Modal trigger label |
| `class` | any string | Extra classes on the wrapper |
| `return` | a site-relative path | Where to land afterwards |

The modal is progressive: with JavaScript off the dialog renders inline, so the trigger is never a dead button.

### Styling

Every element carries a stable, prefixed class and every colour is a custom property, so a retint is a handful of declarations with no specificity fight:

```css
.asocialauth {
    --asa-accent: #c0392b;
    --asa-radius: 4px;
    --asa-bg: #fff;
}
.asocialauth__btn--google { border-color: #4285F4; }
```

| Class | Element |
|---|---|
| `.asocialauth` | wrapper; carries the `--asa-*` properties |
| `.asocialauth--login` / `--identities` | which widget |
| `.asocialauth--inline` / `--modal` | presentation |
| `.asocialauth__panel`, `__inner`, `__trigger`, `__close` | modal chrome |
| `.asocialauth__btn`, `__btn--{slug}` | a provider button |
| `.asocialauth__form`, `__field`, `__label`, `__input`, `__submit`, `__links` | credential forms |
| `.asocialauth__list`, `__item`, `__unlink` | linked accounts |
| `.asocialauth__divider`, `__msg`, `__msg--error`, `__msg--ok` | shared bits |

Styling it yourself? Drop the packaged CSS entirely:

```env
ASOCIALAUTH_INLINE_STYLES=false
```

Or replace a whole view without publishing:

```php
'ui' => ['views' => ['login' => 'mytheme::auth.box']],
```

---

## Configuration

### Routes

```php
'routes' => [
    'prefix'   => 'auth',
    'patterns' => [
        'login'    => 'in/{provider}',
        'callback' => 'in/{provider}/back',
    ],
],
```

gives `/auth/in/google` and `/auth/in/google/back`. Callback URLs are derived from the same values, so they always follow the prefix — **re-register the redirect URI in each provider's console after changing it on a live site.**

### Context

Evolution CMS 3.x keeps manager users and web users in one `users` table, separated only by role, so the same OAuth round trip can open either kind of session.

| `context` | Effect |
|---|---|
| `mgr` (default) | Manager session |
| `web` | Front-end session, for a member area |
| `auto` | Whichever side the flow started on |

### Providers

The five scaffolded in the config are only a starting point. Any of the 52 slugs in `Elcreator\aSocialAuth\Enums\SocialProvider` works:

```php
'providers' => [
    'discord' => [
        'enabled' => true,
        'keys'    => ['id' => env('…'), 'secret' => env('…')],
        'sort'    => 60,
    ],
],
```

A provider whose required credentials are empty stays hidden even with `enabled => true`, so a half-filled `.env` never renders a button that would fail on click. The OpenID-based providers (`openid`, `steam`, `aolopenid`, …) require no credentials at all.

Something the catalogue has never heard of — an in-house Keycloak realm, a fork:

```php
'custom_providers' => [
    'acme' => [
        'enabled' => true,
        'label'   => 'Acme SSO',
        'adapter' => 'Keycloak',
        'keys'    => ['id' => '…', 'secret' => '…'],
        'extra'   => ['url' => 'https://sso.acme.test/'],
    ],
],
```

### E-mail and password

```php
'credentials' => [
    'login'       => true,   // on by default
    'register'    => false,  // opt in: installing a login widget must not open sign-ups
    'recover'     => true,
    'login_field' => 'both', // 'email' | 'username' | 'both'
    'reset_path'  => '/account/reset',
],
```

`reset_path` is the page carrying `[[aSocialAuthCredentials? &form=`reset`]]`. **Front-end recovery does nothing until you set it** — the core's `PasswordRecoveryService` only ever builds a *manager* reset link and returns early on the front end, leaving the mail to an extra. This package sends it, but it needs to know where to point.

---

## How accounts work

### Linking

```
first sign-in with Google  →  social_accounts row  →  user #42
"connect Telegram" on the profile page  →  second row  →  user #42
```

Either identity now signs that user in. The widget is `[[aSocialAuthIdentities]]`; connecting goes through `/{prefix}/{provider}/link`, disconnecting through a CSRF-protected POST.

An identity already linked to someone else is **refused**, not moved — otherwise anyone who can authenticate with a provider could detach it from its owner.

By default a user cannot remove their last identity (`linking.allow_unlink_last`), because an account created socially holds no password its owner knows.

### Matching by e-mail

A new identity whose e-mail matches an existing user is attached to that user rather than forking a second account — but only if the provider **verified** the address (`linking.require_verified_email`, on by default). With it off, any provider that lets a user type an arbitrary e-mail into their own profile becomes a way to claim the matching Evolution CMS account.

### Registration without an e-mail

Some sign-ins simply carry no address — Telegram and Steam never send one, X only does with an approved scope, and any provider returns none for a user who withheld consent. **The trigger is the empty field in the response, never the provider's name**: nothing in the package branches on which network a profile came from. Evolution CMS requires a unique non-empty e-mail on every user, so one is synthesised:

```
telegram-a1b2c3d4e5f6g7h8@social.invalid
```

`.invalid` is reserved by RFC 2606 and can never resolve, so nothing will try to deliver there and it cannot collide with a real address. The account works normally.

### Adding and proving an address later

An account that started without one can fill it in from the identities widget. The claim is held in `social_email_verifications` and a confirmation link is mailed; **only following that link writes the address onto the user** and sets `verified`.

That ordering is the security of the feature, not bookkeeping. E-mail is unique across users and `match_by_email` attaches new identities to whoever holds the address, so an unproven claim in the user row would let someone collect a stranger's Google identity — and block the rightful owner from registering it.

```php
'credentials' => [
    'verify_email'    => true,
    'verify_ttl'      => 1440,   // minutes
    'verify_redirect' => '/members/profile',
],
```

The link needs no page of your own: the route handles the token and redirects. Once confirmed, the account is promotable and `match_by_email` will work for it.

The mirror-image defence is `linking.require_local_verified_email` (on by default): a provider vouching for an address proves the *arriving* person owns it, not that the account already holding it does. Without this, self-registration with someone else's address would collect their identity.

### Giving a social user manager rights

Yes, an administrator can — it is ordinary core behaviour. Open the user under **Users → Web user management**, pick a role, save. The form calls `\UserManager::setRole()`, the single door role changes go through, and it needs the `save_role` permission.

Two things worth knowing:

- Social accounts are created with **role 0** — "no user role", a web user with no manager access at all. Any other default would hand manager standing to whoever completes an OAuth flow.
- **A manager needs a real e-mail address.** Password recovery is the way back into a manager account and it works by e-mail; an account holding a `@social.invalid` placeholder has no route back if its provider is lost. Ask the owner to add a real address first — `Elcreator\aSocialAuth\Support\Promotion::canBecomeManager($userId)` answers this, and a warning is written to the event log if a role is granted anyway.

---

## Login pipeline

Evolution CMS 3.5 made the login flow chainable: ordered pipes under `cms.auth.pipeline` run around every way into a session. This package goes **through** that pipeline, not around it — `SocialAuth::signIn()` calls `\UserManager::loginById()`, so a second factor configured under `'*'` covers social sign-ins too. Enabling social login is not a way to bypass 2FA.

Attempts are tagged so a pipe can tell them apart:

| Key | Value |
|---|---|
| `social` | `true` for a provider callback, `false` for a credential form |
| `social_provider` | the provider slug |
| `social_provider_user_id` | the opaque provider identifier |

### Captcha

A captcha on an OAuth callback is meaningless — the provider already did the human check, and there is no form to render a challenge into. Skip it:

```php
public function handle(LoginAttempt $attempt, Closure $next)
{
    if ($attempt->get('social')) {
        return $next($attempt);
    }

    return $this->verifyCaptcha($attempt) ? $next($attempt) : throw new ServiceActionException('…');
}
```

### TOTP and other interactive challenges

A second-factor pipe has a real problem with social login: a password attempt arrives as a POST it can read a code from, but an OAuth callback is a GET from a third party. Throwing a plain refusal there breaks social sign-in for every account with 2FA on.

Throw `ChallengeRequiredException` instead. The callback parks the resolved identity, redirects to the page you name, and that page finishes the job:

```php
// in your pipe
if ($attempt->get('social') && $attempt->get('totp') === null) {
    throw ChallengeRequiredException::at('/two-factor', 'totp');
}

// on /two-factor
$destination = SocialAuth::completePendingLogin(['totp' => $_POST['code']]);
header('Location: ' . $destination);
```

Nothing is signed in while a challenge is outstanding: `PendingLogin` holds a user id and expires in ten minutes, and the session is written only when the retried pipeline runs to completion.

---

## Protocols, and the two things that silently block them

HybridAuth's providers are not one protocol, and the differences are the kind that only surface once a site enables a particular one.

| Family | How it answers | Works out of the box? |
|---|---|---|
| OAuth 2 — Google, Facebook, Discord, GitHub, … | top-level GET redirect with `?code=` | yes |
| OpenID 2.0 — Steam, AOL | GET redirect (POST permitted) | yes; no credentials required |
| OpenID Connect / **Apple** | `response_mode=form_post` → **cross-site POST** | route yes, cookie **see below** |
| Custom (Keycloak, a fork) | whatever its adapter does | yes, via `custom_providers` |

The callback route accepts **GET and POST**, so a `form_post` provider is not 404'd. Nothing in the package branches on protocol — the adapter knows how to answer its own provider, and the package only supplies config.

**The cookie is the part you may have to change.** Evolution CMS ships `session.same_site = 'lax'`, and browsers default to the same. Under Lax a cookie *is* sent on a top-level GET redirect — so every OAuth 2 provider and Steam work untouched — but is deliberately **withheld on a cross-site POST**. Apple's callback therefore arrives with an empty session, HybridAuth cannot find the `state` it must check, and the visitor gets a generic "that attempt expired".

If you enable Apple, or any provider configured for `form_post`:

```env
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true    # None requires Secure; HTTPS only
```

The package cannot make that change for you — it is a site-wide cookie setting — so instead it names the cause in the event log rather than leaving you with "expired".

Two things that are **not** a problem, since they come up: the routes carry no manager-auth middleware (the visitor is not signed in yet — that is the point), and the widget uses no iframe and loads no third-party asset, so `X-Frame-Options: SAMEORIGIN` and a strict CSP leave it alone.

---

## Database

Two tables. No column is ever added to `users`.

**`social_providers`** — one row per network on offer, synced from config. Metadata only; **credentials stay in config and are never written here**, because a database dump is a routine artefact and OAuth secrets have no business travelling with it.

| Column | Notes |
|---|---|
| `slug` | `google`, `telegram`, … — also the URL segment |
| `adapter` | HybridAuth class name, or a FQCN for a custom adapter |
| `label`, `enabled`, `sort`, `icon` | presentation |

**`social_accounts`** — one row per identity.

| Column | Notes |
|---|---|
| `provider_id` | → `social_providers.id` |
| `provider_user_id` | opaque, kept as a string (Facebook ids exceed PHP's int range) |
| `user_id` | → `users.id` |
| `email`, `name`, `avatar`, `email_verified` | cached, refreshed on each sign-in |
| `last_login_at` | |

Unique on `(provider_id, provider_user_id)`, so an identity belongs to exactly one local user.

> Upgrading from the first release, which stored the provider as a varchar? The migration registers every slug already present (disabled), repoints the rows, and drops the old column. Nothing is lost.

---

## Events

| Event | Fired when |
|---|---|
| `OnSocialAuthLogin` | a social sign-in completed — `provider`, `provider_user_id`, `user_id`, `context`, `created`, `ip`, `user_agent` |
| `OnSocialAuthLink` | an identity was connected to an account |
| `OnSocialAuthUnlink` | an identity was disconnected |

```php
Event::listen('evolution.OnSocialAuthLogin', function (array $params) {
    // $params['user_id'], $params['provider'], $params['created'], …
});
```

---

## Security notes

- **Open redirects.** `&return=` accepts same-site absolute paths only; an absolute URL, a protocol-relative one, a backslash or a control character is discarded.
- **CSRF — with a caveat specific to Evolution CMS.** Every POST here is checked by the package's own `VerifyRequestToken`, *not* by the core's `csrf` alias. `EvolutionCMS\Middleware\VerifyCsrfToken` returns early when `$_SESSION['mgrValidated']` is empty — sound for the core, whose state-changing endpoints all sit behind a manager session, and wrong for this package, whose endpoints are aimed at front-end users who only ever have `webValidated`. Using the core alias would leave these routes open to cross-site POSTs while showing as protected on the route list. Concretely, that allowed a forged `credentials/email` claim → confirmation link to the attacker's mailbox → password reset → account takeover. The package's middleware reads the same `$_SESSION['_token']` the core's `csrf_token()` helper issues, so the forms need nothing special.
- **The provider legs are exempt**, in both verbs — a callback comes from a third party and cannot carry a token; HybridAuth's `state` parameter guards them instead.
- **Referer-driven context checks the host, not just the path.** Under `context: auto`, the side a flow started on is read from the `Referer`, which is chosen by whoever wrote the page the visitor clicked from. Matching on the path alone let any page at `evil.test/manager/` decide that an ordinary member's login should open a *manager* session — handing them `mgrValidated`, which the core reads as "backend user" well beyond this package (site-offline bypass, the debug-output guard in the exception handler, the document-group merge in `getUserDocGroups()`).
- **Account enumeration.** Sign-in failures, and the recovery form, give the same answer whether or not the account exists.
- **Routes 404 when disabled**, rather than erroring — off means absent, not "installed but broken".
- **Auto-registration is off by default** (`registration.enable`), and creates role 0 when on.

---

## Testing

```bash
composer install
composer test
```

258 unit tests, no database or booted CMS required.

---

## License

GPL-3.0-or-later
