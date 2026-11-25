@php
    /**
     * One Tap on its own, for any page: no buttons, no forms.
     *
     * @var array|null  $oneTap
     * @var string|null $error  message from a failed attempt
     */
    $oneTap = $oneTap ?? null;
    $error  = $error ?? null;
@endphp

@if (!empty($error))
    <div class="asocialauth asocialauth--one-tap" data-asocialauth="one-tap">
        @include('aSocialAuth::partials.styles')
        <div class="asocialauth__msg asocialauth__msg--error" role="alert">{{ $error }}</div>
    </div>
@endif

@include('aSocialAuth::partials.one-tap', ['oneTap' => $oneTap])
