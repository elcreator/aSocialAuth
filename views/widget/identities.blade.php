@php
    use EvolutionCMS\aSocialAuth\Support\Config;
    use EvolutionCMS\aSocialAuth\Support\UserResolver;

    /**
     * The linked-accounts widget.
     *
     * Lists every provider identity attached to the signed-in user, with a way
     * to add more and to remove the ones they no longer want. This is what makes
     * several providers add up to one account: each "connect" here produces
     * another `social_accounts` row against the same user, and any of them then
     * signs that user in.
     *
     * @var \Illuminate\Support\Collection $accounts     SocialAccount rows, provider loaded
     * @var \Illuminate\Support\Collection $connectable  providers not yet linked
     * @var bool        $canUnlink   whether removing the last identity is allowed
     * @var string|null $error
     * @var string|null $success
     * @var string|null $returnPath  where the connect/disconnect actions come back to
     * @var bool        $needsEmail       the account has no real address yet
     * @var bool        $canVerifyEmail   the address form may be shown
     * @var string|null $pendingEmail     an address claimed but not yet proven
     */
    $accounts       = $accounts ?? collect();
    $connectable    = $connectable ?? collect();
    $canUnlink      = $canUnlink ?? true;
    $returnPath     = $returnPath ?? null;
    $needsEmail     = $needsEmail ?? false;
    $canVerifyEmail = $canVerifyEmail ?? false;
    $pendingEmail   = $pendingEmail ?? null;
    $returnParam    = Config::returnParam();
@endphp

<div class="asocialauth asocialauth--identities" data-asocialauth="identities">
    @include('aSocialAuth::partials.styles')

    <p class="asocialauth__heading">{{ __('aSocialAuth::login.linked_accounts') }}</p>
    <p class="asocialauth__intro">{{ __('aSocialAuth::login.linked_accounts_intro') }}</p>

    @if (!empty($error))
        <div class="asocialauth__msg asocialauth__msg--error">{{ $error }}</div>
    @endif

    @if (!empty($success))
        <div class="asocialauth__msg asocialauth__msg--ok">{{ $success }}</div>
    @endif

    @if ($needsEmail)
        <div class="asocialauth__msg asocialauth__msg--error">
            {{ __('aSocialAuth::login.placeholder_email_notice') }}
        </div>
    @endif

    @if ($canVerifyEmail)
        <form class="asocialauth__form asocialauth__form--email"
              method="post"
              action="{{ Config::credentialsUrl('email') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            @if ($returnPath)
                <input type="hidden" name="{{ $returnParam }}" value="{{ $returnPath }}">
            @endif

            @if ($pendingEmail)
                {{-- A claim is outstanding: the address is not on the account
                     yet and will not be until the link is followed. --}}
                <p class="asocialauth__intro">
                    {{ __('aSocialAuth::login.verify_pending', ['email' => $pendingEmail]) }}
                </p>
                <div class="asocialauth__links">
                    <button type="submit" class="asocialauth__unlink" name="cancel" value="1">
                        {{ __('aSocialAuth::login.verify_cancel') }}
                    </button>
                </div>
            @else
                <label class="asocialauth__field">
                    <span class="asocialauth__label">
                        {{ $needsEmail
                            ? __('aSocialAuth::login.add_email')
                            : __('aSocialAuth::login.change_email') }}
                    </span>
                    <input class="asocialauth__input"
                           type="email"
                           name="email"
                           autocomplete="email"
                           required>
                </label>

                <button type="submit" class="asocialauth__submit">
                    {{ __('aSocialAuth::login.verify_submit') }}
                </button>
            @endif
        </form>
    @endif

    @if ($accounts->isEmpty())
        <p class="asocialauth__empty">{{ __('aSocialAuth::login.no_linked_accounts') }}</p>
    @else
        <ul class="asocialauth__list">
            @foreach ($accounts as $account)
                @php
                    $provider = $account->provider;
                    $label    = $provider?->label ?: ($provider?->slug ?? '?');
                    // The last identity may only be removed when the site allows
                    // it; otherwise the owner would have no way back into the
                    // account, since a socially created user has no known password.
                    $blocked  = !$canUnlink && $accounts->count() <= 1;
                @endphp
                <li class="asocialauth__item">
                    @include('aSocialAuth::partials.icon', ['icon' => $provider?->iconKey() ?? ''])

                    <div class="asocialauth__item-body">
                        <div class="asocialauth__item-name">{{ $label }}</div>
                        <div class="asocialauth__item-meta">
                            @if ($account->email && !UserResolver::isPlaceholderEmail($account->email))
                                {{ $account->email }} &middot;
                            @elseif ($account->name)
                                {{ $account->name }} &middot;
                            @endif

                            @if ($account->last_login_at)
                                {{ __('aSocialAuth::login.last_used', ['date' => date('Y-m-d', (int) $account->last_login_at)]) }}
                            @else
                                {{ __('aSocialAuth::login.never_used') }}
                            @endif
                        </div>
                    </div>

                    @if ($provider)
                        <form method="post"
                              action="{{ Config::buildUnlinkUrl($provider->slug) }}"
                              onsubmit="return confirm({{ json_encode(__('aSocialAuth::login.confirm_unlink', ['provider' => $label])) }});">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            @if ($returnPath)
                                <input type="hidden" name="{{ $returnParam }}" value="{{ $returnPath }}">
                            @endif
                            <button type="submit"
                                    class="asocialauth__unlink"
                                    @disabled($blocked)
                                    @if ($blocked) title="{{ __('aSocialAuth::login.error_unlink_last') }}" @endif>
                                {{ __('aSocialAuth::login.disconnect') }}
                            </button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($connectable->isNotEmpty())
        <div class="asocialauth__divider">{{ __('aSocialAuth::login.connect_more') }}</div>

        <div class="asocialauth__buttons">
            @foreach ($connectable as $provider)
                @php
                    $url = Config::buildLinkUrl($provider->slug);
                    if ($returnPath) {
                        $url .= '?' . http_build_query([$returnParam => $returnPath]);
                    }
                @endphp
                <a href="{{ $url }}" class="asocialauth__btn" rel="nofollow noopener">
                    @include('aSocialAuth::partials.icon', ['icon' => $provider->iconKey()])
                    <span>{{ __('aSocialAuth::login.connect', ['provider' => $provider->label]) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
