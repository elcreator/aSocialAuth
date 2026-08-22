<?php

return [
    // Login page.
    'or_sign_in_with'             => 'eller log ind med',
    'sign_in_with'                => 'Log ind med :provider',

    // Identities widget.
    'linked_accounts'             => 'Tilknyttede konti',
    'linked_accounts_intro'       => 'Alle disse kan bruges til at logge ind på denne konto.',
    'connect'                     => 'Tilknyt :provider',
    'disconnect'                  => 'Fjern tilknytning',
    'connect_more'                => 'Tilføj endnu en måde at logge ind på',
    'no_linked_accounts'          => 'Der er endnu ingen tilknyttede konti.',
    'linked_on'                   => 'Tilknyttet :date',
    'last_used'                   => 'Sidst brugt :date',
    'never_used'                  => 'Aldrig brugt til at logge ind',
    'linked'                      => ':provider er nu tilknyttet din konto.',
    'unlinked'                    => 'Tilknytningen til :provider er fjernet.',
    'confirm_unlink'              => 'Fjern tilknytningen til :provider fra denne konto?',
    'placeholder_email_notice'    => 'Denne konto har ingen rigtig e-mailadresse. Tilføj en, så du kan få adgang igen, hvis du mister dine tilknyttede konti.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Denne konto har ingen e-mailadresse. Tilføj en rigtig adresse, før du giver den en manager-rolle, ellers kan adgangen ikke gendannes.',
    'promote_placeholder_email'   => 'Denne konto er oprettet via en udbyder, der ikke returnerer en e-mailadresse, så adressen er en pladsholder, der ikke kan modtage post. Nulstilling af adgangskode ville være umulig. Bed ejeren om at tilføje en rigtig adresse, før du giver kontoen en manager-rolle.',

    // E-mail and password forms.
    'sign_in'                     => 'Log ind',
    'sign_in_heading'             => 'Log ind',
    'email'                       => 'E-mail',
    'username'                    => 'Brugernavn',
    'login_field'                 => 'E-mail eller brugernavn',
    'password'                    => 'Adgangskode',
    'password_confirm'            => 'Gentag adgangskoden',
    'full_name'                   => 'Fulde navn',
    'remember_me'                 => 'Forbliv logget ind',
    'forgot_password'             => 'Har du glemt din adgangskode?',
    'register'                    => 'Opret en konto',
    'register_heading'            => 'Opret en konto',
    'have_account'                => 'Har du allerede en konto? Log ind',
    'recover_heading'             => 'Nulstil din adgangskode',
    'recover_intro'               => 'Indtast din e-mailadresse, så sender vi dig et link til at vælge en ny adgangskode.',
    'recover_submit'              => 'Send link',
    'recover_sent'                => 'Hvis der findes en konto til den adresse, er linket på vej.',
    'reset_heading'               => 'Vælg en ny adgangskode',
    'reset_submit'                => 'Gem adgangskode',
    'reset_done'                  => 'Din adgangskode er ændret. Du kan logge ind med den nu.',
    'registered'                  => 'Din konto er oprettet.',
    'back_to_sign_in'             => 'Tilbage til login',
    'close'                       => 'Luk',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Tilføj en e-mailadresse',
    'change_email'                => 'Skift din e-mailadresse',
    'verify_submit'               => 'Send bekræftelseslink',
    'verify_sent'                 => 'Se i din indbakke — følg linket for at bekræfte adressen.',
    'verify_pending'              => 'Venter på, at du bekræfter :email. Indtil da er den ikke knyttet til denne konto.',
    'verify_cancel'               => 'Annuller',
    'verify_cancelled'            => 'Ændringen af e-mailadressen er annulleret.',
    'verify_done'                 => ':email er nu bekræftet på din konto.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Bekræft din e-mailadresse',
    'verify_mail_intro'           => 'Bekræft venligst denne adresse, så den kan bruges til din konto.',
    'verify_mail_link'            => 'Bekræft denne adresse',
    'verify_mail_ignore'          => 'Hvis du ikke har bedt om dette, kan du ignorere beskeden — adressen bliver ikke brugt.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Nulstil din adgangskode',
    'reset_mail_intro'            => 'Nogen har bedt om at nulstille adgangskoden til din konto.',
    'reset_mail_link'             => 'Vælg en ny adgangskode',
    'reset_mail_ignore'           => 'Hvis det ikke var dig, kan du ignorere beskeden — der er ikke ændret noget.',

    // Errors shown to the visitor.
    'error_generic'               => 'Login via sociale netværk er midlertidigt utilgængeligt. Prøv igen.',
    'error_oauth'                 => 'Udbyderen afviste anmodningen. Prøv igen.',
    'error_no_identifier'         => 'Udbyderen returnerede ikke et gyldigt bruger-id.',
    'error_no_user'               => 'Ingen konto er knyttet til denne identitet. Log ind med din adgangskode, og tilknyt kontoen fra din profil.',
    'error_create_user'           => 'Kontoen kunne ikke oprettes. Kontakt en administrator.',
    'error_login'                 => 'Login mislykkedes. Prøv igen, eller brug din adgangskode.',
    'error_expired'               => 'Dette loginforsøg er udløbet. Prøv igen.',
    'error_identity_taken'        => 'Den :provider-konto er allerede knyttet til en anden bruger.',
    'error_link_session'          => 'Din session ændrede sig, mens kontoen blev tilknyttet. Log ind igen, og prøv forfra.',
    'error_link_signed_out'       => 'Log ind, før du tilknytter en anden konto.',
    'error_not_linked'            => 'Den udbyder er ikke tilknyttet din konto.',
    'error_unlink_last'           => 'Det er den eneste måde, du kan logge ind på. Vælg en adgangskode, eller tilknyt en anden udbyder, før du fjerner denne.',

    // Credential form errors.
    'error_credentials_required'  => 'Indtast din e-mail og adgangskode.',
    'error_credentials_invalid'   => 'De loginoplysninger er ikke korrekte.',
    'error_registration_disabled' => 'Registrering er ikke åben på dette websted.',
    'error_email_invalid'         => 'Indtast en gyldig e-mailadresse.',
    'error_email_taken'           => 'Der findes allerede en konto med den e-mailadresse.',
    'error_password_short'        => 'Vælg en adgangskode på mindst :min tegn.',
    'error_password_mismatch'     => 'De to adgangskoder er ikke ens.',
    'error_reset_token'           => 'Det nulstillingslink er ugyldigt eller udløbet. Bed om et nyt.',
    'error_verify_token'          => 'Det bekræftelseslink er ugyldigt eller udløbet. Bed om et nyt.',
    'error_email_already_yours'   => 'Den adresse er allerede på din konto.',
    'error_email_send'            => 'Bekræftelsesbeskeden kunne ikke sendes. Prøv igen senere.',
];
