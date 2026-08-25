@php
    use Elcreator\aSocialAuth\Support\Config;

    /**
     * The e-mail-and-password forms.
     *
     * Which one shows is driven by $form: 'login', 'register', 'recover' or
     * 'reset'. They are plain forms with no JavaScript dependency — the links
     * between them are ordinary links carrying ?form=, so the whole thing works
     * with scripting off and can be restyled entirely from CSS.
     *
     * @var string      $form
     * @var string|null $returnPath
     * @var string|null $resetToken
     */
    $form        = in_array($form ?? 'login', ['login', 'register', 'recover', 'reset'], true) ? $form : 'login';
    $returnPath  = $returnPath ?? null;
    $resetToken  = $resetToken ?? null;
    $returnParam = Config::returnParam();
    $loginField  = Config::loginField();

    // Self-links that swap the visible form, preserving the current page.
    $switch = function (string $target) use ($returnPath) {
        $query = array_filter([
            'form'   => $target,
            'return' => $returnPath,
        ]);

        return '?' . http_build_query($query);
    };
@endphp

@if ($form === 'login' && Config::credentialsLoginEnabled())
    <form class="asocialauth__form asocialauth__form--login"
          method="post"
          action="{{ Config::credentialsUrl('login') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        @if ($returnPath)
            <input type="hidden" name="{{ $returnParam }}" value="{{ $returnPath }}">
        @endif

        <label class="asocialauth__field">
            <span class="asocialauth__label">
                @if ($loginField === 'email')
                    {{ __('aSocialAuth::login.email') }}
                @elseif ($loginField === 'username')
                    {{ __('aSocialAuth::login.username') }}
                @else
                    {{ __('aSocialAuth::login.login_field') }}
                @endif
            </span>
            <input class="asocialauth__input"
                   type="{{ $loginField === 'email' ? 'email' : 'text' }}"
                   name="login"
                   autocomplete="username"
                   required>
        </label>

        <label class="asocialauth__field">
            <span class="asocialauth__label">{{ __('aSocialAuth::login.password') }}</span>
            <input class="asocialauth__input"
                   type="password"
                   name="password"
                   autocomplete="current-password"
                   required>
        </label>

        <label class="asocialauth__check">
            <input type="checkbox" name="rememberme" value="1">
            <span>{{ __('aSocialAuth::login.remember_me') }}</span>
        </label>

        <button type="submit" class="asocialauth__submit">{{ __('aSocialAuth::login.sign_in') }}</button>

        <div class="asocialauth__links">
            @if (Config::credentialsRecoveryEnabled())
                <a href="{{ $switch('recover') }}">{{ __('aSocialAuth::login.forgot_password') }}</a>
            @endif
            @if (Config::credentialsRegistrationEnabled())
                <a href="{{ $switch('register') }}">{{ __('aSocialAuth::login.register') }}</a>
            @endif
        </div>
    </form>
@endif

@if ($form === 'register' && Config::credentialsRegistrationEnabled())
    <form class="asocialauth__form asocialauth__form--register"
          method="post"
          action="{{ Config::credentialsUrl('register') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        @if ($returnPath)
            <input type="hidden" name="{{ $returnParam }}" value="{{ $returnPath }}">
        @endif

        <label class="asocialauth__field">
            <span class="asocialauth__label">{{ __('aSocialAuth::login.email') }}</span>
            <input class="asocialauth__input" type="email" name="email" autocomplete="email" required>
        </label>

        <label class="asocialauth__field">
            <span class="asocialauth__label">{{ __('aSocialAuth::login.full_name') }}</span>
            <input class="asocialauth__input" type="text" name="fullname" autocomplete="name">
        </label>

        <label class="asocialauth__field">
            <span class="asocialauth__label">{{ __('aSocialAuth::login.password') }}</span>
            <input class="asocialauth__input"
                   type="password"
                   name="password"
                   autocomplete="new-password"
                   minlength="{{ Config::minimumPasswordLength() }}"
                   required>
        </label>

        <label class="asocialauth__field">
            <span class="asocialauth__label">{{ __('aSocialAuth::login.password_confirm') }}</span>
            <input class="asocialauth__input"
                   type="password"
                   name="password_confirmation"
                   autocomplete="new-password"
                   minlength="{{ Config::minimumPasswordLength() }}"
                   required>
        </label>

        <button type="submit" class="asocialauth__submit">{{ __('aSocialAuth::login.register') }}</button>

        <div class="asocialauth__links">
            <a href="{{ $switch('login') }}">{{ __('aSocialAuth::login.have_account') }}</a>
        </div>
    </form>
@endif

@if ($form === 'recover' && Config::credentialsRecoveryEnabled())
    <form class="asocialauth__form asocialauth__form--recover"
          method="post"
          action="{{ Config::credentialsUrl('recover') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        @if ($returnPath)
            <input type="hidden" name="{{ $returnParam }}" value="{{ $returnPath }}">
        @endif

        <p class="asocialauth__intro">{{ __('aSocialAuth::login.recover_intro') }}</p>

        <label class="asocialauth__field">
            <span class="asocialauth__label">{{ __('aSocialAuth::login.email') }}</span>
            <input class="asocialauth__input" type="email" name="email" autocomplete="email" required>
        </label>

        <button type="submit" class="asocialauth__submit">{{ __('aSocialAuth::login.recover_submit') }}</button>

        <div class="asocialauth__links">
            <a href="{{ $switch('login') }}">{{ __('aSocialAuth::login.back_to_sign_in') }}</a>
        </div>
    </form>
@endif

@if ($form === 'reset' && Config::credentialsRecoveryEnabled())
    <form class="asocialauth__form asocialauth__form--reset"
          method="post"
          action="{{ Config::credentialsUrl('reset') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="{{ Config::resetTokenParam() }}" value="{{ $resetToken }}">
        @if ($returnPath)
            <input type="hidden" name="{{ $returnParam }}" value="{{ $returnPath }}">
        @endif

        <label class="asocialauth__field">
            <span class="asocialauth__label">{{ __('aSocialAuth::login.password') }}</span>
            <input class="asocialauth__input"
                   type="password"
                   name="password"
                   autocomplete="new-password"
                   minlength="{{ Config::minimumPasswordLength() }}"
                   required>
        </label>

        <label class="asocialauth__field">
            <span class="asocialauth__label">{{ __('aSocialAuth::login.password_confirm') }}</span>
            <input class="asocialauth__input"
                   type="password"
                   name="password_confirmation"
                   autocomplete="new-password"
                   minlength="{{ Config::minimumPasswordLength() }}"
                   required>
        </label>

        <button type="submit" class="asocialauth__submit">{{ __('aSocialAuth::login.reset_submit') }}</button>
    </form>
@endif
