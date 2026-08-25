# AGENTS.md – aSocialAuth

Context file for AI coding agents working on this repository.

---

## What this is

Authentication for **Evolution CMS 3.5+**: social login through [HybridAuth](https://github.com/hybridauth/hybridauth), multi-provider account linking, and e-mail/password login, registration and recovery — one package, one widget.

- Package: `elcreator/asocialauth` · type `evolutioncms-plugin`
- Namespace: `Elcreator\aSocialAuth\` → `src/`
- PHP 8.3+ · MIT

Run the tests with `composer test` (Pest, 258 unit tests, no database or booted CMS needed).

---

## Layout

```
config/aSocialAuth.php              all configuration; secrets via env() only
database/migrations/                social_accounts, permissions, social_providers, normalisation
lang/en/{global,login}.php          every user-visible string
plugins/aSocialAuthPlugin.php       event listeners (loaded by the provider)
src/
  Enums/SocialProvider.php          the catalogue: 52 HybridAuth providers
  Exceptions/
    SocialAuthException.php         a failure the visitor may be told about
    ChallengeRequiredException.php  a pipe needs interactive input
  Http/
    routes.php                      built entirely from config
    Controllers/
      SocialController.php          shared plumbing
      SocialLoginController.php     GET  {prefix}/{provider}
      SocialCallbackController.php  GET  {prefix}/{provider}/callback
      SocialLinkController.php      GET  …/link, POST …/unlink
      CredentialsController.php     POST {prefix}/credentials/{login,register,recover,reset}
  Models/
    RegisteredProvider.php          social_providers
    SocialAccount.php               social_accounts
  Support/
    Config.php                      typed config access; the ONLY place URLs are built
    ProviderRegistry.php            enum + config → social_providers
    HybridAuthManager.php           adapter factory
    UserResolver.php                profile → Evolution CMS user
    Credentials.php                 e-mail/password login, register, recover, reset
    SocialAuth.php                  public API: signIn(), completePendingLogin()
    Identity.php                    who is signed in; linked identities
    FlowState.php                   intent across the OAuth round trip
    PendingLogin.php                a sign-in paused by a challenge
    Promotion.php                   is this account fit for a manager role?
    Renderer.php                    view data assembly
    Log.php                         evo()->logEvent() wrapper
views/
  manager/login-buttons.blade.php   manager login page
  widget/login.blade.php            front-end sign-in, inline or modal
  widget/identities.blade.php       linked accounts
  partials/{styles,icon,buttons,credentials}.blade.php
tests/Unit/                         Pest
```

---

## The three-layer provider model

This is the design decision most likely to be misunderstood, so it is worth stating plainly. A provider is described in three places and each owns a different part:

| Layer | Owns | Where |
|---|---|---|
| **Enum** | What HybridAuth can do: adapter class, default label, whether an e-mail is ever returned, which credentials are required | `Enums\SocialProvider` |
| **Config** | What this site chose: which slugs are on, credentials, label/order overrides, custom adapters | `config/aSocialAuth.php` |
| **Table** | The joinable projection of the two, so identity rows can carry a foreign key | `social_providers` |

`ProviderRegistry::sync()` writes the table from the other two, fingerprinted so it only runs when config changes.

**Credentials never reach the table.** A database dump is a routine artefact — shared with a host, copied to staging — and OAuth secrets must not travel with it. `SourceContractTest` asserts this.

The shape this replaces is one nullable id column per network on the user row (`fb_id`, `tw_id`, `gg_id`, …). It works for four networks and stops working at forty: each addition is a migration, "which provider is this?" becomes a chain of null checks, and a user can hold at most one identity per network. Here a provider is a row and an identity is a row, so adding Discord is a config entry.

---

## Invariants

These are enforced by `tests/Unit/SourceContractTest.php`. Breaking one should fail a test, not ship.

1. **No hardcoded route prefix.** Everything comes from `Config`. A literal `asocialauth/` anywhere is a URL that would not move when the prefix changes — and for a callback URL that is a broken OAuth round trip discovered in production.
2. **Callback URLs come from `Config::buildCallbackUrl()` only.** Two derivations that disagree is how a provider ends up rejecting `redirect_uri`.
3. **Role changes go through `\UserManager::setRole()`.** `role` is deliberately absent from `UserAttribute::$fillable` and skipped by `UserEdit`, so a privilege change has exactly one door. Never `$user->attributes->role = …`.
4. **User writes go through `\UserManager`.** Evolution CMS splits a user across `users` (credentials) and `user_attributes` (name, e-mail, role). `User::create(['email' => …])` looks reasonable and silently drops the value, then never creates the profile row.
5. **CSRF exactly where it belongs.** The OAuth legs are GET and exempt — a third-party callback cannot carry a token; HybridAuth's `state` guards it. Unlink and all four credential forms are POST with `csrf`.
6. **Every visible string has a lang key.** No literals in views or exception messages.
7. **Views escape provider data.** `{{ }}` everywhere; the only `{!! !!}` is the icon set, which is ours.

---

## Login pipeline

Evolution CMS 3.5 (`core/src/Auth/`) made login chainable: ordered pipes under `cms.auth.pipeline` run around `login`, `loginById` and `hashLogin`, plus `*` for all three.

`SocialAuth::signIn()` calls `\UserManager::loginById()`, so social sign-ins go **through** the pipeline. This is deliberate — a second factor under `'*'` would be worthless if the social door bypassed it. Do not "optimise" this into a direct session write.

Attempts carry markers so pipes can tell them apart:

```php
'social'                  => true|false
'social_provider'         => 'google'
'social_provider_user_id' => '…'
```

A pipe needing interactive input throws `ChallengeRequiredException::at('/two-factor', 'totp')`. The callback parks the identity in `PendingLogin` (ten-minute TTL, confers nothing) and redirects; the challenge page calls `SocialAuth::completePendingLogin(['totp' => …])`, which retries through the same pipeline with the extra data merged.

---

## Session facts worth knowing

- `$_SESSION` is the shared store; `EvoSessionProxy` syncs it with Laravel's. HybridAuth uses it too. Write flow state there, not to Laravel's flash — the manager login page renders outside the lifecycle that would replay one.
- `UserLogin::writeSession()` **regenerates the session id** and copies the data across, so a message queued before login survives. `FlowStateTest` covers this.
- It also sets `$_SESSION['usertype'] = 'manager'` unconditionally, even for a `web` context login. That is a core quirk, and harmless — `ManagerTheme::isAuthManager()` only destroys a session when `mgrValidated` is set *and* usertype is not `manager`.
- Manager and web sessions coexist: `mgrValidated`/`mgrInternalKey` and `webValidated`/`webInternalKey`. Always ask per context.

---

## Adding a provider

Nothing but config:

```php
'providers' => [
    'github' => [
        'enabled' => true,
        'keys'    => ['id' => env('…'), 'secret' => env('…')],
    ],
],
```

Optionally add an icon to `views/partials/icon.blade.php` keyed by slug — without one the button gets a neutral glyph and still works.

**After a HybridAuth upgrade** that adds providers, regenerate the enum: slug is `strtolower(ClassName)`, case name is the upper-snaked class name. `SocialProviderEnumTest` compares the enum against `vendor/hybridauth/hybridauth/src/Provider/*.php` and fails if they drift.

---

## Emailless registration

Telegram and Steam never return an e-mail; X only does with an approved scope. `SocialProvider::providesEmail()` knows which.

Evolution CMS requires a unique non-empty e-mail on every user, so `UserResolver::placeholderEmail()` mints `{slug}-{16 hex}@social.invalid`. `.invalid` is reserved by RFC 2606, so it can never resolve or collide. `isPlaceholderEmail()` detects them; the identities widget prompts the owner to add a real address, and `Promotion` refuses to call such an account fit for a manager role — password recovery works by e-mail, and there would be no route back in.

---

## Testing

`tests/Pest.php` stubs `config()`, `evo()`, `__()`, `app()` and a `UserManager` double, then loads the nested Evolution CMS autoloader **after** — its `preload.php` defines its own `evo()` behind a `function_exists()` guard and would otherwise try to boot a real CMS.

Helpers: `makeProfile([...])`, `makeProvider('google')`, `resetTestState()`.

`UserManager::$logins` records payloads; `UserManager::$throw` makes the next call fail, which is how pipe refusals and challenges are simulated.

Anything touching Eloquent is expected to fail and be caught — the classes are written to degrade (`ProviderRegistry::enabled()` returns an empty collection, `Identity::isLastIdentity()` returns `true`). That is real behaviour, not a fixture gap: the login page must render even when the migrations have not run.

---

## Common pitfalls

- **`$user->attributes`** is the `hasOne` relation to `user_attributes`, not Eloquent's internal array — from outside the model. Inside a model, `$this->attributes` is the raw column array.
- **`{provider}` is `[a-z0-9]+`**, which would match a bare `login`. Credential routes live under `credentials/` for exactly that reason, and are registered first.
- **Changing the route prefix on a live site** means re-registering the redirect URI in every provider console.
- **`config:cache`** — `.env` changes need `php artisan config:clear`.
- **`Route::fallbackToParser()`** is registered in the core's `RoutingServiceProvider::register()`, before package `boot()`. Laravel matches fallbacks last regardless of order, so package routes still win.
- **Routes only register on the front end** (`$app->isFrontend()`), which is correct: the callback is a front-end URL even when the button was on the manager login page.
- **Front-end password recovery** needs `credentials.reset_path`. The core's `PasswordRecoveryService` only builds a manager link and returns early on the front end.

---

## Protocol and transport

HybridAuth's providers are not one protocol, and nothing in the package branches on which one is in play — the adapter answers its own provider and the package only supplies config. Two consequences are load-bearing:

- **The callback is `Route::match(['get','post'], …)`.** OAuth 2 and OpenID 2.0 redirect back with a GET; Apple sets `response_mode=form_post` and OpenID 2.0 permits a POST indirect response. A GET-only callback 404s those, and only once a site enables Apple.
- **`SameSite=Lax` withholds the session cookie on that cross-site POST.** Evolution CMS ships `session.same_site = 'lax'`. A top-level GET redirect is unaffected, so this bites only `form_post` providers. The package cannot fix a site-wide cookie setting, so `SocialCallbackController::diagnoseMissingFlow()` writes the real cause to the event log instead of letting it read as "expired".

Neither leg may acquire CSRF middleware — the request comes from a third party and cannot carry a token; HybridAuth's `state` is what guards it.

## Provider-agnostic identity handling

**The absence of an e-mail in the response is the trigger for a placeholder address — never the provider's name.** There is no `providesEmail()` and no list of e-mailless providers: such a list can only rot (X gained an e-mail scope, LinkedIn moved to OpenID) and any provider returns nothing for a user who withheld consent.

`SourceContractTest` and `AuthTransportTest` assert that no file outside `Enums/` compares against a provider slug. `Enums\SocialProvider` is exempt because it is a lookup table by definition; its `defaultScope()` and `requiredKeys()` are adapter configuration, not identity behaviour.

## E-mail verification

`EmailVerifier` holds a claimed address in `social_email_verifications` and moves it onto `user_attributes.email` only on confirmation. Do not "simplify" this by writing the address immediately: e-mail is unique across users and `match_by_email` attaches identities to whoever holds it, so an unproven claim in the user row is enough to collect a stranger's Google identity and to block them from registering.

`linking.require_local_verified_email` is the same defence from the other side: the provider proves the arriving person owns the address, not that the account already holding it does.

## Two Evolution CMS behaviours that bite

Both were live holes here; both are pinned by `CsrfAndContextTest`.

**1. The core's CSRF middleware is a no-op without a manager session.**
`EvolutionCMS\Middleware\VerifyCsrfToken::requiresVerification()` opens with
`if (empty($_SESSION['mgrValidated'])) return false;`. Every route in this
package targets front-end users, who have `webValidated` and never
`mgrValidated` — so applying the `csrf` alias protects nothing while looking
protected on `route:list`. Use `Http\Middleware\VerifyRequestToken` on every POST.
Never swap it back for `'csrf'`.

**2. `mgrValidated` means more than "logged into the manager".**
The core reads it for the site-offline bypass, the debug-output guard in
`ExceptionHandler`, Tracy, and the document-group merge in `getUserDocGroups()`.
So which context a social login opens is a privilege decision, and under
`context: auto` it is inferred from the `Referer` — an attacker-chosen header.
`Config::refererIsManager()` therefore checks scheme/host, not only the path.
