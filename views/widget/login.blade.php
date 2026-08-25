@php
    use Elcreator\aSocialAuth\Support\Config;

    /**
     * The front-end sign-in widget.
     *
     * Two presentations from one markup: 'inline' drops the box into the page,
     * 'modal' wraps it in a <dialog> behind a trigger button. The modal is
     * progressive — with scripting off the dialog is simply rendered open, so
     * the widget never becomes a dead button.
     *
     * Nothing here is styled by attribute or element selector; every hook is a
     * class, so a theme can restyle the whole widget without touching the view.
     *
     * @var \Illuminate\Support\Collection $providers
     * @var string      $mode         'inline'|'modal'
     * @var string      $form         which credential form to show
     * @var bool        $showCredentials
     * @var string|null $error
     * @var string|null $success
     * @var string|null $returnPath
     * @var string|null $resetToken
     * @var string      $trigger      modal trigger label
     * @var string      $wrapperClass extra classes for the wrapper
     */
    $providers       = $providers ?? collect();
    $mode            = ($mode ?? 'inline') === 'modal' ? 'modal' : 'inline';
    $form            = $form ?? 'login';
    $showCredentials = $showCredentials ?? true;
    $returnPath      = $returnPath ?? null;
    $resetToken      = $resetToken ?? null;
    $trigger         = $trigger ?? __('aSocialAuth::login.sign_in');
    $wrapperClass    = trim((string) ($wrapperClass ?? ''));

    $heading = match ($form) {
        'register' => __('aSocialAuth::login.register_heading'),
        'recover'  => __('aSocialAuth::login.recover_heading'),
        'reset'    => __('aSocialAuth::login.reset_heading'),
        default    => __('aSocialAuth::login.sign_in_heading'),
    };

    // The social buttons make no sense next to a "choose a new password" form.
    $showProviders = $providers->isNotEmpty() && $form !== 'reset';

    $dialogId = 'asocialauth-dialog-' . substr(md5($form . $mode . (string) $returnPath), 0, 8);
@endphp

<div class="asocialauth asocialauth--login asocialauth--{{ $mode }} {{ $wrapperClass }}"
     data-asocialauth="login"
     data-mode="{{ $mode }}"
     data-form="{{ $form }}">

    @include('aSocialAuth::partials.styles')

    @if ($mode === 'modal')
        <button type="button"
                class="asocialauth__trigger"
                data-asocialauth-open="{{ $dialogId }}">{{ $trigger }}</button>
    @endif

    <{{ $mode === 'modal' ? 'dialog' : 'div' }}
        @class(['asocialauth__panel', 'asocialauth__panel--modal' => $mode === 'modal'])
        @if ($mode === 'modal') id="{{ $dialogId }}" @endif>

        <div class="asocialauth__inner">
            @if ($mode === 'modal')
                <button type="button"
                        class="asocialauth__close"
                        data-asocialauth-close="{{ $dialogId }}"
                        aria-label="{{ __('aSocialAuth::login.close') }}">&times;</button>
            @endif

            <p class="asocialauth__heading">{{ $heading }}</p>

            @if (!empty($error))
                <div class="asocialauth__msg asocialauth__msg--error" role="alert">{{ $error }}</div>
            @endif

            @if (!empty($success))
                <div class="asocialauth__msg asocialauth__msg--ok" role="status">{{ $success }}</div>
            @endif

            @if ($showCredentials)
                @include('aSocialAuth::partials.credentials', [
                    'form'       => $form,
                    'returnPath' => $returnPath,
                    'resetToken' => $resetToken,
                ])
            @endif

            @if ($showProviders)
                @if ($showCredentials)
                    <div class="asocialauth__divider">{{ __('aSocialAuth::login.or_sign_in_with') }}</div>
                @endif

                @include('aSocialAuth::partials.buttons', [
                    'providers'  => $providers,
                    'returnPath' => $returnPath,
                    'action'     => 'login',
                ])
            @endif
        </div>
    </{{ $mode === 'modal' ? 'dialog' : 'div' }}>

    @if ($mode === 'modal')
        {{--
            Six lines of vanilla JS, scoped to this widget. No dependency, and
            nothing breaks without it: a <dialog> that never receives showModal()
            is styled open by the stylesheet's no-JS fallback.
        --}}
        <script>
            (function () {
                var id = @json($dialogId);
                var dialog = document.getElementById(id);
                if (!dialog || typeof dialog.showModal !== 'function') { return; }
                dialog.classList.add('is-enhanced');
                document.querySelectorAll('[data-asocialauth-open="' + id + '"]').forEach(function (button) {
                    button.addEventListener('click', function () { dialog.showModal(); });
                });
                document.querySelectorAll('[data-asocialauth-close="' + id + '"]').forEach(function (button) {
                    button.addEventListener('click', function () { dialog.close(); });
                });
            })();
        </script>
    @endif
</div>
