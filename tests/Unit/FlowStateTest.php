<?php

use Elcreator\aSocialAuth\Support\FlowState;

/**
 * The state that has to survive the trip to the provider and back. Everything a
 * callback is allowed to act on comes from here, so its failure modes matter
 * more than its happy path.
 */

it('round-trips a started flow', function () {
    FlowState::start('google', FlowState::INTENT_LOGIN, 'mgr', '/members', 42);

    $flow = FlowState::consume('google');

    expect($flow)->toMatchArray([
        'provider' => 'google',
        'intent'   => 'login',
        'context'  => 'mgr',
        'return'   => '/members',
        'user_id'  => 42,
    ]);
});

it('is consumed exactly once', function () {
    // A callback is a one-shot event; leaving the state behind would let a
    // replayed URL act on an intent the visitor already spent.
    FlowState::start('google', FlowState::INTENT_LOGIN, 'web');

    expect(FlowState::consume('google'))->not->toBeNull();
    expect(FlowState::consume('google'))->toBeNull();
});

it('refuses a callback from a provider the flow did not start with', function () {
    FlowState::start('google', FlowState::INTENT_LOGIN, 'web');

    expect(FlowState::consume('facebook'))->toBeNull();
});

it('clears the state even when the provider does not match', function () {
    // Otherwise a mismatched callback would leave the flow available to a
    // second, correctly-addressed replay.
    FlowState::start('google', FlowState::INTENT_LOGIN, 'web');

    FlowState::consume('facebook');

    expect(FlowState::consume('google'))->toBeNull();
});

it('expires a flow that was abandoned', function () {
    FlowState::start('google', FlowState::INTENT_LOGIN, 'web');

    $_SESSION[FlowState::KEY]['started'] = time() - FlowState::TTL_SECONDS - 1;

    expect(FlowState::consume('google'))->toBeNull();
});

it('keeps a flow that is still inside its window', function () {
    FlowState::start('google', FlowState::INTENT_LOGIN, 'web');

    $_SESSION[FlowState::KEY]['started'] = time() - FlowState::TTL_SECONDS + 5;

    expect(FlowState::consume('google'))->not->toBeNull();
});

it('returns nothing when no flow was ever started', function () {
    expect(FlowState::consume('google'))->toBeNull();
});

it('normalises an unknown intent to login', function () {
    // The intent decides between signing someone in and modifying an existing
    // account, so anything unrecognised must fall to the weaker of the two.
    FlowState::start('google', 'something-else', 'web');

    expect(FlowState::consume('google')['intent'])->toBe('login');
});

it('overwrites a previous flow rather than queueing', function () {
    // Only one flow is ever in progress. An abandoned attempt must not linger
    // where a later callback could pick up its intent.
    FlowState::start('google', FlowState::INTENT_LINK, 'web', null, 7);
    FlowState::start('facebook', FlowState::INTENT_LOGIN, 'web');

    $flow = FlowState::consume('facebook');

    expect($flow)->not->toBeNull();
    expect($flow['intent'])->toBe('login');
    expect($flow['user_id'])->toBeNull();
});

describe('flash messages', function () {
    it('carries an error across a redirect and clears it on read', function () {
        FlowState::flashError('nope');

        expect(FlowState::takeError())->toBe('nope');
        expect(FlowState::takeError())->toBeNull();
    });

    it('keeps success and error apart', function () {
        FlowState::flashError('bad');
        FlowState::flashSuccess('good');

        expect(FlowState::takeSuccess())->toBe('good');
        expect(FlowState::takeError())->toBe('bad');
    });

    it('survives the session regeneration that login performs', function () {
        // UserLogin::regenerateSessionId() destroys the session and copies the
        // data across. A message queued before that must still arrive.
        FlowState::flashSuccess('linked');

        $carried  = $_SESSION;
        $_SESSION = [];
        foreach ($carried as $key => $value) {
            $_SESSION[$key] = $value;
        }

        expect(FlowState::takeSuccess())->toBe('linked');
    });
});
