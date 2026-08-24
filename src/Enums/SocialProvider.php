<?php

namespace EvolutionCMS\aSocialAuth\Enums;

/**
 * The catalogue of identity providers HybridAuth ships with.
 *
 * This is the "enum constants" half of the provider model. It replaces the pattern
 * of one nullable id column per network (`fb_id`, `tw_id`, `gg_id`, …) that a
 * hand-rolled integration ends up with: adding a network there costs a schema
 * migration and a column, while here it costs nothing — the identity lives as a row
 * in `social_accounts` pointing at a `social_providers` row.
 *
 * The enum is the *catalogue*, not the *runtime source of truth*. What a given site
 * actually offers is the `social_providers` table, which ProviderRegistry syncs from
 * this enum plus any custom entries in config. That split is deliberate: a site can
 * register a HybridAuth provider this enum has never heard of (an in-house Keycloak
 * realm, a fork) without patching the package.
 *
 * Case names are the class name upper-snaked; values are the class name lowercased,
 * which is also the URL segment (/{prefix}/google). The mapping is mechanical so a
 * HybridAuth upgrade that adds providers is a regeneration, not a redesign.
 */
enum SocialProvider: string
{
    case AMAZON = 'amazon';
    case AOLOPENID = 'aolopenid';
    case APPLE = 'apple';
    case AUTHENTIQ = 'authentiq';
    case AUTODESK = 'autodesk';
    case BITBUCKET = 'bitbucket';
    case BLIZZARD = 'blizzard';
    case BLIZZARDAPAC = 'blizzardapac';
    case BLIZZARDEU = 'blizzardeu';
    case DEVIANTART = 'deviantart';
    case DISCORD = 'discord';
    case DISQUS = 'disqus';
    case DRIBBBLE = 'dribbble';
    case DROPBOX = 'dropbox';
    case FACEBOOK = 'facebook';
    case FOURSQUARE = 'foursquare';
    case GITHUB = 'github';
    case GITLAB = 'gitlab';
    case GOOGLE = 'google';
    case INSTAGRAM = 'instagram';
    case KEYCLOAK = 'keycloak';
    case LINKEDIN = 'linkedin';
    case LINKEDINOPENID = 'linkedinopenid';
    case MASTODON = 'mastodon';
    case MEDIUM = 'medium';
    case MICROSOFTGRAPH = 'microsoftgraph';
    case OKTAOIDC = 'oktaoidc';
    case OPENID = 'openid';
    case ORCID = 'orcid';
    case PATREON = 'patreon';
    case PAYPAL = 'paypal';
    case PAYPALOPENID = 'paypalopenid';
    case PINTEREST = 'pinterest';
    case QQ = 'qq';
    case REDDIT = 'reddit';
    case SEZNAM = 'seznam';
    case SLACK = 'slack';
    case SPOTIFY = 'spotify';
    case STACKEXCHANGE = 'stackexchange';
    case STACKEXCHANGEOPENID = 'stackexchangeopenid';
    case STEAM = 'steam';
    case STEEMCONNECT = 'steemconnect';
    case STRAVA = 'strava';
    case TELEGRAM = 'telegram';
    case TUMBLR = 'tumblr';
    case TWITCHTV = 'twitchtv';
    case TWITTER = 'twitter';
    case WECHAT = 'wechat';
    case WECHATCHINA = 'wechatchina';
    case WINDOWSLIVE = 'windowslive';
    case WORDPRESS = 'wordpress';
    case X = 'x';
    case YAHOO = 'yahoo';

    /**
     * The HybridAuth adapter class name, relative to \Hybridauth\Provider\.
     */
    public function hybridauthProvider(): string
    {
        return match ($this) {
            self::AMAZON => 'Amazon',
            self::AOLOPENID => 'AOLOpenID',
            self::APPLE => 'Apple',
            self::AUTHENTIQ => 'Authentiq',
            self::AUTODESK => 'AutoDesk',
            self::BITBUCKET => 'BitBucket',
            self::BLIZZARD => 'Blizzard',
            self::BLIZZARDAPAC => 'BlizzardAPAC',
            self::BLIZZARDEU => 'BlizzardEU',
            self::DEVIANTART => 'DeviantArt',
            self::DISCORD => 'Discord',
            self::DISQUS => 'Disqus',
            self::DRIBBBLE => 'Dribbble',
            self::DROPBOX => 'Dropbox',
            self::FACEBOOK => 'Facebook',
            self::FOURSQUARE => 'Foursquare',
            self::GITHUB => 'GitHub',
            self::GITLAB => 'GitLab',
            self::GOOGLE => 'Google',
            self::INSTAGRAM => 'Instagram',
            self::KEYCLOAK => 'Keycloak',
            self::LINKEDIN => 'LinkedIn',
            self::LINKEDINOPENID => 'LinkedInOpenID',
            self::MASTODON => 'Mastodon',
            self::MEDIUM => 'Medium',
            self::MICROSOFTGRAPH => 'MicrosoftGraph',
            self::OKTAOIDC => 'OktaOIDC',
            self::OPENID => 'OpenID',
            self::ORCID => 'ORCID',
            self::PATREON => 'Patreon',
            self::PAYPAL => 'Paypal',
            self::PAYPALOPENID => 'PaypalOpenID',
            self::PINTEREST => 'Pinterest',
            self::QQ => 'QQ',
            self::REDDIT => 'Reddit',
            self::SEZNAM => 'Seznam',
            self::SLACK => 'Slack',
            self::SPOTIFY => 'Spotify',
            self::STACKEXCHANGE => 'StackExchange',
            self::STACKEXCHANGEOPENID => 'StackExchangeOpenID',
            self::STEAM => 'Steam',
            self::STEEMCONNECT => 'SteemConnect',
            self::STRAVA => 'Strava',
            self::TELEGRAM => 'Telegram',
            self::TUMBLR => 'Tumblr',
            self::TWITCHTV => 'TwitchTV',
            self::TWITTER => 'Twitter',
            self::WECHAT => 'WeChat',
            self::WECHATCHINA => 'WeChatChina',
            self::WINDOWSLIVE => 'WindowsLive',
            self::WORDPRESS => 'WordPress',
            self::X => 'X',
            self::YAHOO => 'Yahoo',
        };
    }

    /**
     * Fully qualified HybridAuth adapter class.
     */
    public function adapterClass(): string
    {
        return 'Hybridauth\\Provider\\' . $this->hybridauthProvider();
    }

    /**
     * Human-readable button label. Sites override this per provider in config.
     */
    public function label(): string
    {
        return match ($this) {
            self::AMAZON => 'Amazon',
            self::AOLOPENID => 'AOL',
            self::APPLE => 'Apple',
            self::AUTHENTIQ => 'Authentiq',
            self::AUTODESK => 'Autodesk',
            self::BITBUCKET => 'Bitbucket',
            self::BLIZZARD => 'Blizzard',
            self::BLIZZARDAPAC => 'Blizzard (APAC)',
            self::BLIZZARDEU => 'Blizzard (EU)',
            self::DEVIANTART => 'DeviantArt',
            self::DISCORD => 'Discord',
            self::DISQUS => 'Disqus',
            self::DRIBBBLE => 'Dribbble',
            self::DROPBOX => 'Dropbox',
            self::FACEBOOK => 'Facebook',
            self::FOURSQUARE => 'Foursquare',
            self::GITHUB => 'GitHub',
            self::GITLAB => 'GitLab',
            self::GOOGLE => 'Google',
            self::INSTAGRAM => 'Instagram',
            self::KEYCLOAK => 'Keycloak',
            self::LINKEDIN => 'LinkedIn',
            self::LINKEDINOPENID => 'LinkedIn (OpenID)',
            self::MASTODON => 'Mastodon',
            self::MEDIUM => 'Medium',
            self::MICROSOFTGRAPH => 'Microsoft',
            self::OKTAOIDC => 'Okta',
            self::OPENID => 'OpenID',
            self::ORCID => 'ORCID',
            self::PATREON => 'Patreon',
            self::PAYPAL => 'PayPal',
            self::PAYPALOPENID => 'PayPal (OpenID)',
            self::PINTEREST => 'Pinterest',
            self::QQ => 'QQ',
            self::REDDIT => 'Reddit',
            self::SEZNAM => 'Seznam',
            self::SLACK => 'Slack',
            self::SPOTIFY => 'Spotify',
            self::STACKEXCHANGE => 'Stack Exchange',
            self::STACKEXCHANGEOPENID => 'Stack Exchange (OpenID)',
            self::STEAM => 'Steam',
            self::STEEMCONNECT => 'SteemConnect',
            self::STRAVA => 'Strava',
            self::TELEGRAM => 'Telegram',
            self::TUMBLR => 'Tumblr',
            self::TWITCHTV => 'Twitch',
            self::TWITTER => 'Twitter',
            self::WECHAT => 'WeChat',
            self::WECHATCHINA => 'WeChat (China)',
            self::WINDOWSLIVE => 'Microsoft Live',
            self::WORDPRESS => 'WordPress',
            self::X => 'X',
            self::YAHOO => 'Yahoo',
        };
    }

    /**
     * Credential keys that must be present before this provider may be offered.
     *
     * Most networks need a client id and a secret. The OpenID-based ones need
     * neither — the whole point of OpenID is that there is no app registration —
     * so demanding credentials from them would keep a perfectly usable provider
     * permanently hidden.
     *
     * @return string[]
     */
    public function requiredKeys(): array
    {
        return match ($this) {
            self::OPENID,
            self::AOLOPENID,
            self::STACKEXCHANGEOPENID,
            self::PAYPALOPENID,
            self::STEAM => [],
            default => ['id', 'secret'],
        };
    }

    /**
     * A scope the provider needs beyond HybridAuth's default to return a profile
     * with an e-mail. Null means HybridAuth's own default is already right.
     */
    public function defaultScope(): ?string
    {
        return match ($this) {
            self::DISCORD => 'identify email',
            self::GITHUB => 'user:email',
            self::GITLAB => 'read_user',
            self::SLACK => 'identity.basic identity.email identity.avatar',
            self::X => 'tweet.read users.read users.email offline.access',
            default => null,
        };
    }

    /**
     * Resolve a slug to a case, tolerating case and surrounding whitespace.
     */
    public static function fromSlug(?string $slug): ?self
    {
        if ($slug === null) {
            return null;
        }

        return self::tryFrom(strtolower(trim($slug)));
    }

    /**
     * Slug => label for every catalogued provider, for config scaffolding and docs.
     *
     * @return array<string, string>
     */
    public static function catalogue(): array
    {
        $out = [];

        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}