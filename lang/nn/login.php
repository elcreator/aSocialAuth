<?php

return [
    // Login page.
    'or_sign_in_with'             => 'eller logg inn med',
    'sign_in_with'                => 'Logg inn med :provider',

    // Identities widget.
    'linked_accounts'             => 'Tilknyttede kontoer',
    'linked_accounts_intro'       => 'Alle disse kan brukes til å logge inn på denne kontoen.',
    'connect'                     => 'Koble til :provider',
    'disconnect'                  => 'Koble fra',
    'connect_more'                => 'Legg til en ny måte å logge inn på',
    'no_linked_accounts'          => 'Ingen kontoer er tilknyttet ennå.',
    'linked_on'                   => 'Tilknyttet :date',
    'last_used'                   => 'Sist brukt :date',
    'never_used'                  => 'Aldri brukt til innlogging',
    'linked'                      => ':provider er nå knyttet til kontoen din.',
    'unlinked'                    => ':provider er koblet fra.',
    'confirm_unlink'              => 'Koble :provider fra denne kontoen?',
    'placeholder_email_notice'    => 'Denne kontoen har ingen ekte e-postadresse. Legg til en så du kan få tilgang igjen hvis du mister de tilknyttede kontoene.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Denne kontoen har ingen e-postadresse. Legg til en ekte adresse før du gir den en manager-rolle, ellers kan ikke tilgangen gjenopprettes.',
    'promote_placeholder_email'   => 'Denne kontoen ble opprettet via en leverandør som ikke returnerer e-postadresse, så adressen er en plassholder som ikke kan motta post. Gjenoppretting av passord ville vært umulig. Be eieren legge til en ekte adresse før du gir kontoen en manager-rolle.',

    // E-mail and password forms.
    'sign_in'                     => 'Logg inn',
    'sign_in_heading'             => 'Logg inn',
    'email'                       => 'E-post',
    'username'                    => 'Brukernavn',
    'login_field'                 => 'E-post eller brukernavn',
    'password'                    => 'Passord',
    'password_confirm'            => 'Gjenta passordet',
    'full_name'                   => 'Fullt navn',
    'remember_me'                 => 'Forbli innlogget',
    'forgot_password'             => 'Glemt passordet?',
    'register'                    => 'Opprett en konto',
    'register_heading'            => 'Opprett en konto',
    'have_account'                => 'Har du allerede en konto? Logg inn',
    'recover_heading'             => 'Tilbakestill passordet',
    'recover_intro'               => 'Skriv inn e-postadressen din, så sender vi deg en lenke for å velge et nytt passord.',
    'recover_submit'              => 'Send lenke',
    'recover_sent'                => 'Hvis adressen hører til en konto, er lenken på vei.',
    'reset_heading'               => 'Velg et nytt passord',
    'reset_submit'                => 'Lagre passord',
    'reset_done'                  => 'Passordet ditt er endret. Du kan logge inn med det nå.',
    'registered'                  => 'Kontoen din er opprettet.',
    'back_to_sign_in'             => 'Tilbake til innlogging',
    'close'                       => 'Lukk',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Legg til en e-postadresse',
    'change_email'                => 'Endre e-postadressen din',
    'verify_submit'               => 'Send bekreftelseslenke',
    'verify_sent'                 => 'Sjekk innboksen — følg lenken for å bekrefte adressen.',
    'verify_pending'              => 'Venter på at du bekrefter :email. Inntil da hører den ikke til denne kontoen.',
    'verify_cancel'               => 'Avbryt',
    'verify_cancelled'            => 'Endringen av e-postadressen er avbrutt.',
    'verify_done'                 => ':email er nå bekreftet på kontoen din.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Bekreft e-postadressen din',
    'verify_mail_intro'           => 'Bekreft denne adressen slik at den kan brukes for kontoen din.',
    'verify_mail_link'            => 'Bekreft denne adressen',
    'verify_mail_ignore'          => 'Hvis du ikke ba om dette, kan du se bort fra meldingen — adressen blir ikke tatt i bruk.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Tilbakestill passordet ditt',
    'reset_mail_intro'            => 'Noen har bedt om å tilbakestille passordet til kontoen din.',
    'reset_mail_link'             => 'Velg et nytt passord',
    'reset_mail_ignore'           => 'Hvis det ikke var deg, kan du se bort fra meldingen — ingenting er endret.',

    // Errors shown to the visitor.
    'error_generic'               => 'Innlogging via sosiale nettverk er midlertidig utilgjengelig. Prøv igjen.',
    'error_oauth'                 => 'Leverandøren avviste forespørselen. Prøv igjen.',
    'error_no_identifier'         => 'Leverandøren returnerte ingen gyldig bruker-ID.',
    'error_no_user'               => 'Ingen konto er knyttet til denne identiteten. Logg inn med passordet ditt og knytt kontoen fra profilen din.',
    'error_create_user'           => 'Kontoen kunne ikke opprettes. Kontakt en administrator.',
    'error_login'                 => 'Innloggingen mislyktes. Prøv igjen eller bruk passordet ditt.',
    'error_expired'               => 'Dette innloggingsforsøket er utløpt. Prøv igjen.',
    'error_identity_taken'        => 'Den :provider-kontoen er allerede knyttet til en annen bruker.',
    'error_link_session'          => 'Økten din endret seg mens kontoen ble tilknyttet. Logg inn på nytt og prøv igjen.',
    'error_link_signed_out'       => 'Logg inn før du knytter til en annen konto.',
    'error_not_linked'            => 'Den leverandøren er ikke knyttet til kontoen din.',
    'error_unlink_last'           => 'Dette er den eneste måten du kan logge inn på. Velg et passord eller knytt til en annen leverandør før du kobler fra denne.',

    // Credential form errors.
    'error_credentials_required'  => 'Skriv inn e-postadressen og passordet ditt.',
    'error_credentials_invalid'   => 'Disse innloggingsopplysningene er ikke riktige.',
    'error_registration_disabled' => 'Registrering er ikke åpen på dette nettstedet.',
    'error_email_invalid'         => 'Skriv inn en gyldig e-postadresse.',
    'error_email_taken'           => 'Det finnes allerede en konto med den e-postadressen.',
    'error_password_short'        => 'Velg et passord på minst :min tegn.',
    'error_password_mismatch'     => 'De to passordene er ikke like.',
    'error_reset_token'           => 'Denne tilbakestillingslenken er ugyldig eller utløpt. Be om en ny.',
    'error_verify_token'          => 'Denne bekreftelseslenken er ugyldig eller utløpt. Be om en ny.',
    'error_email_already_yours'   => 'Den adressen er allerede på kontoen din.',
    'error_email_send'            => 'Bekreftelsesmeldingen kunne ikke sendes. Prøv igjen senere.',
];
