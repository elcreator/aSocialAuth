@php
    use EvolutionCMS\aSocialAuth\Support\Config;

    /**
     * The provider button list on its own, shared by the manager login page, the
     * front-end widget and the modal body.
     *
     * Every button carries a per-provider modifier class
     * (.asocialauth__btn--google) so a theme can style one network differently
     * without resorting to attribute selectors.
     *
     * @var \Illuminate\Support\Collection $providers
     * @var string|null $returnPath
     * @var string      $action  'login' or 'link'
     */
    $providers   = $providers ?? collect();
    $returnPath  = $returnPath ?? null;
    $action      = ($action ?? 'login') === 'link' ? 'link' : 'login';
    $returnParam = Config::returnParam();
@endphp

<div class="asocialauth__buttons">
    @foreach ($providers as $provider)
        @php
            $url = $action === 'link'
                ? Config::buildLinkUrl($provider->slug)
                : Config::buildLoginUrl($provider->slug);

            if ($returnPath) {
                $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query([$returnParam => $returnPath]);
            }

            $label = $action === 'link'
                ? __('aSocialAuth::login.connect', ['provider' => $provider->label])
                : __('aSocialAuth::login.sign_in_with', ['provider' => $provider->label]);
        @endphp
        <a href="{{ $url }}"
           class="asocialauth__btn asocialauth__btn--{{ $provider->slug }}"
           data-provider="{{ $provider->slug }}"
           rel="nofollow noopener">
            @include('aSocialAuth::partials.icon', ['icon' => $provider->iconKey()])
            <span class="asocialauth__btn-label">{{ $label }}</span>
        </a>
    @endforeach
</div>
