@php
    /**
     * Social sign-in buttons, appended to the Evolution CMS manager login form.
     *
     * Buttons only: the manager already has its own username and password form,
     * and a second one underneath it would be nonsense. The button list itself
     * comes from the shared partial, so the manager page and the front-end
     * widget cannot drift apart — a per-provider class or an icon added for one
     * appears on the other.
     *
     * @var \Illuminate\Support\Collection $providers   RegisteredProvider rows, ordered
     * @var string|null                    $error       message from a failed attempt
     * @var string|null                    $returnPath
     */
    $providers  = $providers ?? collect();
    $error      = $error ?? null;
    $returnPath = $returnPath ?? null;
@endphp

@if ($providers->isNotEmpty())
    <div class="asocialauth asocialauth--login asocialauth--manager" data-asocialauth="login">
        @include('aSocialAuth::partials.styles')

        @if ($error)
            <div class="asocialauth__msg asocialauth__msg--error" role="alert">{{ $error }}</div>
        @endif

        <div class="asocialauth__divider">{{ __('aSocialAuth::login.or_sign_in_with') }}</div>

        @include('aSocialAuth::partials.buttons', [
            'providers'  => $providers,
            'returnPath' => $returnPath,
            'action'     => 'login',
        ])
    </div>
@endif
