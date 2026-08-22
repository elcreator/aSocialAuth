<?php

return [
    // Login page.
    'or_sign_in_with'             => 'of log in met',
    'sign_in_with'                => 'Inloggen met :provider',

    // Identities widget.
    'linked_accounts'             => 'Gekoppelde accounts',
    'linked_accounts_intro'       => 'Met elk hiervan kun je op dit account inloggen.',
    'connect'                     => ':provider koppelen',
    'disconnect'                  => 'Ontkoppelen',
    'connect_more'                => 'Nog een manier om in te loggen toevoegen',
    'no_linked_accounts'          => 'Er zijn nog geen accounts gekoppeld.',
    'linked_on'                   => 'Gekoppeld op :date',
    'last_used'                   => 'Laatst gebruikt :date',
    'never_used'                  => 'Nooit gebruikt om in te loggen',
    'linked'                      => ':provider is nu aan je account gekoppeld.',
    'unlinked'                    => ':provider is ontkoppeld.',
    'confirm_unlink'              => ':provider van dit account ontkoppelen?',
    'placeholder_email_notice'    => 'Dit account heeft geen echt e-mailadres. Voeg er een toe zodat je weer toegang kunt krijgen als je je gekoppelde accounts kwijtraakt.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Dit account heeft geen e-mailadres. Voeg een echt adres toe voordat je het een beheerdersrol geeft, anders is de toegang niet meer te herstellen.',
    'promote_placeholder_email'   => 'Dit account is aangemaakt via een provider die geen e-mailadres teruggeeft, dus het adres is een tijdelijke waarde die geen post kan ontvangen. Een wachtwoordherstel zou onmogelijk zijn. Vraag de eigenaar om een echt adres voordat je dit account een beheerdersrol geeft.',

    // E-mail and password forms.
    'sign_in'                     => 'Inloggen',
    'sign_in_heading'             => 'Inloggen',
    'email'                       => 'E-mail',
    'username'                    => 'Gebruikersnaam',
    'login_field'                 => 'E-mail of gebruikersnaam',
    'password'                    => 'Wachtwoord',
    'password_confirm'            => 'Herhaal het wachtwoord',
    'full_name'                   => 'Volledige naam',
    'remember_me'                 => 'Ingelogd blijven',
    'forgot_password'             => 'Wachtwoord vergeten?',
    'register'                    => 'Account aanmaken',
    'register_heading'            => 'Account aanmaken',
    'have_account'                => 'Heb je al een account? Log in',
    'recover_heading'             => 'Je wachtwoord opnieuw instellen',
    'recover_intro'               => 'Vul je e-mailadres in en we sturen je een link om een nieuw wachtwoord in te stellen.',
    'recover_submit'              => 'Link versturen',
    'recover_sent'                => 'Als er een account bij dat adres hoort, is de link onderweg.',
    'reset_heading'               => 'Kies een nieuw wachtwoord',
    'reset_submit'                => 'Wachtwoord instellen',
    'reset_done'                  => 'Je wachtwoord is gewijzigd. Je kunt er nu mee inloggen.',
    'registered'                  => 'Je account is aangemaakt.',
    'back_to_sign_in'             => 'Terug naar inloggen',
    'close'                       => 'Sluiten',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Een e-mailadres toevoegen',
    'change_email'                => 'Je e-mailadres wijzigen',
    'verify_submit'               => 'Bevestigingslink versturen',
    'verify_sent'                 => 'Kijk in je inbox — volg de link om het adres te bevestigen.',
    'verify_pending'              => 'We wachten tot je :email bevestigt. Tot die tijd hoort het niet bij dit account.',
    'verify_cancel'               => 'Annuleren',
    'verify_cancelled'            => 'De wijziging van het e-mailadres is geannuleerd.',
    'verify_done'                 => ':email is nu bevestigd op je account.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Bevestig je e-mailadres',
    'verify_mail_intro'           => 'Bevestig dit adres zodat het voor je account gebruikt kan worden.',
    'verify_mail_link'            => 'Dit adres bevestigen',
    'verify_mail_ignore'          => 'Heb je hier niet om gevraagd, negeer dit bericht dan — het adres wordt niet gebruikt.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Je wachtwoord opnieuw instellen',
    'reset_mail_intro'            => 'Iemand heeft gevraagd het wachtwoord van je account opnieuw in te stellen.',
    'reset_mail_link'             => 'Kies een nieuw wachtwoord',
    'reset_mail_ignore'           => 'Was jij dit niet, negeer dit bericht dan — er is niets gewijzigd.',

    // Errors shown to the visitor.
    'error_generic'               => 'Inloggen via sociale netwerken is tijdelijk niet beschikbaar. Probeer het opnieuw.',
    'error_oauth'                 => 'De provider heeft het verzoek geweigerd. Probeer het opnieuw.',
    'error_no_identifier'         => 'De provider gaf geen geldige gebruikers-id terug.',
    'error_no_user'               => 'Er is geen account aan deze identiteit gekoppeld. Log in met je wachtwoord en koppel het account vanuit je profiel.',
    'error_create_user'           => 'Het account kon niet worden aangemaakt. Neem contact op met een beheerder.',
    'error_login'                 => 'Inloggen is mislukt. Probeer het opnieuw of gebruik je wachtwoord.',
    'error_expired'               => 'Deze inlogpoging is verlopen. Probeer het opnieuw.',
    'error_identity_taken'        => 'Dat :provider-account is al aan een andere gebruiker gekoppeld.',
    'error_link_session'          => 'Je sessie is veranderd tijdens het koppelen. Log opnieuw in en probeer het nog eens.',
    'error_link_signed_out'       => 'Log in voordat je een ander account koppelt.',
    'error_not_linked'            => 'Die provider is niet aan je account gekoppeld.',
    'error_unlink_last'           => 'Dit is de enige manier waarop je kunt inloggen. Stel een wachtwoord in of koppel een andere provider voordat je deze ontkoppelt.',

    // Credential form errors.
    'error_credentials_required'  => 'Vul je e-mailadres en wachtwoord in.',
    'error_credentials_invalid'   => 'Deze inloggegevens kloppen niet.',
    'error_registration_disabled' => 'Registreren is op deze site niet mogelijk.',
    'error_email_invalid'         => 'Vul een geldig e-mailadres in.',
    'error_email_taken'           => 'Er bestaat al een account met dat e-mailadres.',
    'error_password_short'        => 'Kies een wachtwoord van minimaal :min tekens.',
    'error_password_mismatch'     => 'De twee wachtwoorden komen niet overeen.',
    'error_reset_token'           => 'Deze herstellink is ongeldig of verlopen. Vraag een nieuwe aan.',
    'error_verify_token'          => 'Deze bevestigingslink is ongeldig of verlopen. Vraag een nieuwe aan.',
    'error_email_already_yours'   => 'Dat adres staat al op je account.',
    'error_email_send'            => 'Het bevestigingsbericht kon niet worden verzonden. Probeer het later opnieuw.',
];
