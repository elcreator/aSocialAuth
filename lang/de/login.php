<?php

return [
    // Login page.
    'or_sign_in_with'             => 'oder anmelden mit',
    'sign_in_with'                => 'Mit :provider anmelden',

    // Identities widget.
    'linked_accounts'             => 'Verknüpfte Konten',
    'linked_accounts_intro'       => 'Mit jedem davon können Sie sich bei diesem Konto anmelden.',
    'connect'                     => ':provider verknüpfen',
    'disconnect'                  => 'Trennen',
    'connect_more'                => 'Weitere Anmeldemöglichkeit hinzufügen',
    'no_linked_accounts'          => 'Es sind noch keine Konten verknüpft.',
    'linked_on'                   => 'Verknüpft am :date',
    'last_used'                   => 'Zuletzt verwendet :date',
    'never_used'                  => 'Noch nie zur Anmeldung verwendet',
    'linked'                      => ':provider ist jetzt mit Ihrem Konto verknüpft.',
    'unlinked'                    => ':provider wurde getrennt.',
    'confirm_unlink'              => ':provider von diesem Konto trennen?',
    'placeholder_email_notice'    => 'Dieses Konto hat keine echte E-Mail-Adresse. Fügen Sie eine hinzu, damit Sie den Zugang wiederherstellen können, falls Sie Ihre verknüpften Konten verlieren.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Dieses Konto hat keine E-Mail-Adresse. Hinterlegen Sie eine echte Adresse, bevor Sie ihm eine Manager-Rolle geben, sonst lässt sich der Zugang nicht wiederherstellen.',
    'promote_placeholder_email'   => 'Dieses Konto wurde über einen Anbieter angelegt, der keine E-Mail-Adresse zurückgibt; die hinterlegte Adresse ist deshalb ein Platzhalter und kann keine Nachrichten empfangen. Eine Passwort-Wiederherstellung wäre unmöglich. Bitten Sie die Person, der das Konto gehört, um eine echte Adresse, bevor Sie eine Manager-Rolle vergeben.',

    // E-mail and password forms.
    'sign_in'                     => 'Anmelden',
    'sign_in_heading'             => 'Anmelden',
    'email'                       => 'E-Mail',
    'username'                    => 'Benutzername',
    'login_field'                 => 'E-Mail oder Benutzername',
    'password'                    => 'Passwort',
    'password_confirm'            => 'Passwort wiederholen',
    'full_name'                   => 'Vollständiger Name',
    'remember_me'                 => 'Angemeldet bleiben',
    'forgot_password'             => 'Passwort vergessen?',
    'register'                    => 'Konto erstellen',
    'register_heading'            => 'Konto erstellen',
    'have_account'                => 'Sie haben schon ein Konto? Anmelden',
    'recover_heading'             => 'Passwort zurücksetzen',
    'recover_intro'               => 'Geben Sie Ihre E-Mail-Adresse ein, und wir senden Ihnen einen Link, um ein neues Passwort zu setzen.',
    'recover_submit'              => 'Link senden',
    'recover_sent'                => 'Falls zu dieser Adresse ein Konto gehört, ist der Link bereits unterwegs.',
    'reset_heading'               => 'Neues Passwort wählen',
    'reset_submit'                => 'Passwort setzen',
    'reset_done'                  => 'Ihr Passwort wurde geändert. Sie können sich jetzt damit anmelden.',
    'registered'                  => 'Ihr Konto wurde erstellt.',
    'back_to_sign_in'             => 'Zurück zur Anmeldung',
    'close'                       => 'Schließen',

    // Adding and proving an e-mail address.
    'add_email'                   => 'E-Mail-Adresse hinzufügen',
    'change_email'                => 'E-Mail-Adresse ändern',
    'verify_submit'               => 'Bestätigungslink senden',
    'verify_sent'                 => 'Sehen Sie in Ihrem Postfach nach — folgen Sie dem Link, um die Adresse zu bestätigen.',
    'verify_pending'              => 'Warten auf die Bestätigung von :email. Bis dahin gehört die Adresse nicht zu diesem Konto.',
    'verify_cancel'               => 'Abbrechen',
    'verify_cancelled'            => 'Die Änderung der E-Mail-Adresse wurde abgebrochen.',
    'verify_done'                 => ':email ist jetzt für Ihr Konto bestätigt.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Bestätigen Sie Ihre E-Mail-Adresse',
    'verify_mail_intro'           => 'Bitte bestätigen Sie diese Adresse, damit sie für Ihr Konto verwendet werden kann.',
    'verify_mail_link'            => 'Diese Adresse bestätigen',
    'verify_mail_ignore'          => 'Wenn Sie das nicht angefordert haben, können Sie diese Nachricht ignorieren — die Adresse wird nicht verwendet.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Passwort zurücksetzen',
    'reset_mail_intro'            => 'Jemand hat darum gebeten, das Passwort für Ihr Konto zurückzusetzen.',
    'reset_mail_link'             => 'Neues Passwort wählen',
    'reset_mail_ignore'           => 'Wenn Sie das nicht waren, können Sie diese Nachricht ignorieren — es hat sich nichts geändert.',

    // Errors shown to the visitor.
    'error_generic'               => 'Die Anmeldung über soziale Netzwerke ist vorübergehend nicht verfügbar. Bitte versuchen Sie es erneut.',
    'error_oauth'                 => 'Der Anbieter hat die Anfrage abgelehnt. Bitte versuchen Sie es erneut.',
    'error_no_identifier'         => 'Der Anbieter hat keine gültige Benutzerkennung zurückgegeben.',
    'error_no_user'               => 'Mit dieser Identität ist kein Konto verknüpft. Melden Sie sich mit Ihrem Passwort an und verknüpfen Sie das Konto in Ihrem Profil.',
    'error_create_user'           => 'Das Konto konnte nicht erstellt werden. Bitte wenden Sie sich an die Administration.',
    'error_login'                 => 'Die Anmeldung ist fehlgeschlagen. Versuchen Sie es erneut oder verwenden Sie Ihr Passwort.',
    'error_expired'               => 'Dieser Anmeldeversuch ist abgelaufen. Bitte versuchen Sie es erneut.',
    'error_identity_taken'        => 'Dieses :provider-Konto ist bereits mit einem anderen Benutzer verknüpft.',
    'error_link_session'          => 'Ihre Sitzung hat sich während des Verknüpfens geändert. Bitte melden Sie sich erneut an und versuchen Sie es noch einmal.',
    'error_link_signed_out'       => 'Bitte melden Sie sich an, bevor Sie ein weiteres Konto verknüpfen.',
    'error_not_linked'            => 'Dieser Anbieter ist nicht mit Ihrem Konto verknüpft.',
    'error_unlink_last'           => 'Das ist Ihre einzige Anmeldemöglichkeit. Setzen Sie ein Passwort oder verknüpfen Sie einen weiteren Anbieter, bevor Sie diesen trennen.',

    // Credential form errors.
    'error_credentials_required'  => 'Bitte geben Sie E-Mail und Passwort ein.',
    'error_credentials_invalid'   => 'Diese Anmeldedaten sind nicht korrekt.',
    'error_registration_disabled' => 'Auf dieser Website ist keine Registrierung möglich.',
    'error_email_invalid'         => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
    'error_email_taken'           => 'Für diese E-Mail-Adresse besteht bereits ein Konto.',
    'error_password_short'        => 'Bitte wählen Sie ein Passwort mit mindestens :min Zeichen.',
    'error_password_mismatch'     => 'Die beiden Passwörter stimmen nicht überein.',
    'error_reset_token'           => 'Dieser Link zum Zurücksetzen ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.',
    'error_verify_token'          => 'Dieser Bestätigungslink ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.',
    'error_email_already_yours'   => 'Diese Adresse gehört bereits zu Ihrem Konto.',
    'error_email_send'            => 'Die Bestätigungsnachricht konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.',
];
