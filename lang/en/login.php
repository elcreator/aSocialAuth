<?php

return [
    // Login page.
    'or_sign_in_with' => 'or sign in with',
    'sign_in_with'    => 'Sign in with :provider',

    // Identities widget.
    'linked_accounts'   => 'Linked accounts',
    'linked_accounts_intro' => 'Any of these can be used to sign in to this account.',
    'connect'           => 'Connect :provider',
    'disconnect'        => 'Disconnect',
    'connect_more'      => 'Add another way to sign in',
    'no_linked_accounts' => 'No accounts are linked yet.',
    'linked_on'         => 'Linked :date',
    'last_used'         => 'Last used :date',
    'never_used'        => 'Never used to sign in',
    'linked'            => ':provider is now linked to your account.',
    'unlinked'          => ':provider has been disconnected.',
    'confirm_unlink'    => 'Disconnect :provider from this account?',
    'placeholder_email_notice' => 'This account has no real e-mail address. Add one so you can recover access if you lose your linked accounts.',

    // Shown to an administrator considering a role change.
    'promote_no_email'          => 'This account has no e-mail address. Add a real one before giving it a manager role, or there will be no way to recover access to it.',
    'promote_placeholder_email' => 'This account signed up through a provider that returns no e-mail, so its address is a placeholder that cannot receive mail. Password recovery would be impossible. Ask the owner to add a real address before giving it a manager role.',

    // E-mail and password forms.
    'sign_in'            => 'Sign in',
    'sign_in_heading'    => 'Sign in',
    'email'              => 'E-mail',
    'username'           => 'Username',
    'login_field'        => 'E-mail or username',
    'password'           => 'Password',
    'password_confirm'   => 'Repeat password',
    'full_name'          => 'Full name',
    'remember_me'        => 'Stay signed in',
    'forgot_password'    => 'Forgot your password?',
    'register'           => 'Create an account',
    'register_heading'   => 'Create an account',
    'have_account'       => 'Already have an account? Sign in',
    'recover_heading'    => 'Reset your password',
    'recover_intro'      => 'Enter your e-mail address and we will send you a link to set a new password.',
    'recover_submit'     => 'Send reset link',
    'recover_sent'       => 'If that address has an account, a reset link is on its way.',
    'reset_heading'      => 'Choose a new password',
    'reset_submit'       => 'Set password',
    'reset_done'         => 'Your password has been changed. You can sign in with it now.',
    'registered'         => 'Your account has been created.',
    'back_to_sign_in'    => 'Back to sign in',
    'close'              => 'Close',

    // Adding and proving an e-mail address.
    'add_email'       => 'Add an e-mail address',
    'change_email'    => 'Change your e-mail address',
    'verify_submit'   => 'Send confirmation link',
    'verify_sent'     => 'Check your inbox — follow the link to confirm the address.',
    'verify_pending'  => 'Waiting for you to confirm :email. Until you do, it is not attached to this account.',
    'verify_cancel'   => 'Cancel',
    'verify_cancelled' => 'That e-mail change has been cancelled.',
    'verify_done'     => ':email is now confirmed on your account.',

    // Verification e-mail.
    'verify_mail_subject' => 'Confirm your e-mail address',
    'verify_mail_intro'   => 'Please confirm this address so it can be used for your account.',
    'verify_mail_link'    => 'Confirm this address',
    'verify_mail_ignore'  => 'If you did not ask for this, you can ignore this message — the address will not be used.',

    // Reset e-mail.
    'reset_mail_subject' => 'Reset your password',
    'reset_mail_intro'   => 'Someone asked to reset the password for your account.',
    'reset_mail_link'    => 'Choose a new password',
    'reset_mail_ignore'  => 'If this was not you, you can ignore this message — nothing has changed.',

    // Errors shown to the visitor. These are deliberately vague about which of
    // the possible causes applied: an error page that distinguishes "no such
    // user" from "wrong provider" is a way of enumerating accounts.
    'error_generic'       => 'Social login is temporarily unavailable. Please try again.',
    'error_oauth'         => 'The social provider rejected the request. Please try again.',
    'error_no_identifier' => 'The social provider did not return a valid user identifier.',
    'error_no_user'       => 'No account is linked to this social identity. Please sign in with your password and link the account from your profile.',
    'error_create_user'   => 'Failed to create an account. Please contact an administrator.',
    'error_login'         => 'Sign-in failed. Please try again or use your password.',
    'error_expired'       => 'That sign-in attempt expired. Please try again.',
    'error_identity_taken' => 'That :provider account is already linked to a different user.',
    'error_link_session'  => 'Your session changed while connecting the account. Please sign in again and retry.',
    'error_link_signed_out' => 'Please sign in before connecting another account.',
    'error_not_linked'    => 'That provider is not linked to your account.',
    'error_unlink_last'   => 'This is the only way you can sign in. Set a password or connect another provider before disconnecting this one.',

    // Credential form errors. "invalid" is deliberately used for both an unknown
    // account and a wrong password: telling them apart would let the form be
    // used to discover which addresses are registered.
    'error_credentials_required'  => 'Please enter your e-mail and password.',
    'error_credentials_invalid'   => 'Those sign-in details are not correct.',
    'error_registration_disabled' => 'Registration is not open on this site.',
    'error_email_invalid'         => 'Please enter a valid e-mail address.',
    'error_email_taken'           => 'An account already exists for that e-mail address.',
    'error_password_short'        => 'Please choose a password of at least :min characters.',
    'error_password_mismatch'     => 'The two passwords do not match.',
    'error_reset_token'           => 'That reset link is invalid or has expired. Please request a new one.',
    'error_verify_token'          => 'That confirmation link is invalid or has expired. Please request a new one.',
    'error_email_already_yours'   => 'That address is already on your account.',
    'error_email_send'            => 'The confirmation message could not be sent. Please try again later.',
];
