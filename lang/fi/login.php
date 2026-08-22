<?php

return [
    // Login page.
    'or_sign_in_with'             => 'tai kirjaudu palvelulla',
    'sign_in_with'                => 'Kirjaudu palvelulla :provider',

    // Identities widget.
    'linked_accounts'             => 'Liitetyt tilit',
    'linked_accounts_intro'       => 'Millä tahansa näistä voi kirjautua tälle tilille.',
    'connect'                     => 'Liitä :provider',
    'disconnect'                  => 'Poista liitos',
    'connect_more'                => 'Lisää toinen tapa kirjautua',
    'no_linked_accounts'          => 'Yhtään tiliä ei ole vielä liitetty.',
    'linked_on'                   => 'Liitetty :date',
    'last_used'                   => 'Viimeksi käytetty :date',
    'never_used'                  => 'Ei ole koskaan käytetty kirjautumiseen',
    'linked'                      => ':provider on nyt liitetty tiliisi.',
    'unlinked'                    => ':provider on irrotettu.',
    'confirm_unlink'              => 'Irrotetaanko :provider tästä tilistä?',
    'placeholder_email_notice'    => 'Tällä tilillä ei ole oikeaa sähköpostiosoitetta. Lisää sellainen, jotta voit palauttaa pääsyn, jos menetät liitetyt tilisi.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Tällä tilillä ei ole sähköpostiosoitetta. Lisää oikea osoite ennen kuin annat sille manager-roolin, muuten pääsyä ei voi palauttaa.',
    'promote_placeholder_email'   => 'Tämä tili on luotu palvelun kautta, joka ei palauta sähköpostiosoitetta, joten sen osoite on paikanvaraaja eikä voi vastaanottaa postia. Salasanan palautus olisi mahdotonta. Pyydä omistajaa lisäämään oikea osoite ennen manager-roolin antamista.',

    // E-mail and password forms.
    'sign_in'                     => 'Kirjaudu sisään',
    'sign_in_heading'             => 'Kirjaudu sisään',
    'email'                       => 'Sähköposti',
    'username'                    => 'Käyttäjätunnus',
    'login_field'                 => 'Sähköposti tai käyttäjätunnus',
    'password'                    => 'Salasana',
    'password_confirm'            => 'Toista salasana',
    'full_name'                   => 'Koko nimi',
    'remember_me'                 => 'Pysy kirjautuneena',
    'forgot_password'             => 'Unohditko salasanasi?',
    'register'                    => 'Luo tili',
    'register_heading'            => 'Luo tili',
    'have_account'                => 'Onko sinulla jo tili? Kirjaudu sisään',
    'recover_heading'             => 'Palauta salasanasi',
    'recover_intro'               => 'Anna sähköpostiosoitteesi, niin lähetämme linkin uuden salasanan asettamiseen.',
    'recover_submit'              => 'Lähetä linkki',
    'recover_sent'                => 'Jos osoitteelle on tili, linkki on jo matkalla.',
    'reset_heading'               => 'Valitse uusi salasana',
    'reset_submit'                => 'Aseta salasana',
    'reset_done'                  => 'Salasanasi on vaihdettu. Voit kirjautua sillä nyt.',
    'registered'                  => 'Tilisi on luotu.',
    'back_to_sign_in'             => 'Takaisin kirjautumiseen',
    'close'                       => 'Sulje',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Lisää sähköpostiosoite',
    'change_email'                => 'Vaihda sähköpostiosoitteesi',
    'verify_submit'               => 'Lähetä vahvistuslinkki',
    'verify_sent'                 => 'Tarkista sähköpostisi — vahvista osoite linkin kautta.',
    'verify_pending'              => 'Odotetaan, että vahvistat osoitteen :email. Siihen asti se ei ole liitetty tähän tiliin.',
    'verify_cancel'               => 'Peruuta',
    'verify_cancelled'            => 'Sähköpostiosoitteen vaihto peruutettiin.',
    'verify_done'                 => ':email on nyt vahvistettu tilillesi.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Vahvista sähköpostiosoitteesi',
    'verify_mail_intro'           => 'Vahvista tämä osoite, jotta sitä voidaan käyttää tilisi kanssa.',
    'verify_mail_link'            => 'Vahvista tämä osoite',
    'verify_mail_ignore'          => 'Jos et pyytänyt tätä, voit jättää viestin huomiotta — osoitetta ei oteta käyttöön.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Palauta salasanasi',
    'reset_mail_intro'            => 'Joku pyysi tilisi salasanan palautusta.',
    'reset_mail_link'             => 'Valitse uusi salasana',
    'reset_mail_ignore'           => 'Jos et ollut sinä, voit jättää viestin huomiotta — mikään ei ole muuttunut.',

    // Errors shown to the visitor.
    'error_generic'               => 'Kirjautuminen sosiaalisen median tilillä ei ole juuri nyt käytettävissä. Yritä uudelleen.',
    'error_oauth'                 => 'Palveluntarjoaja hylkäsi pyynnön. Yritä uudelleen.',
    'error_no_identifier'         => 'Palveluntarjoaja ei palauttanut kelvollista käyttäjätunnistetta.',
    'error_no_user'               => 'Tähän identiteettiin ei ole liitetty tiliä. Kirjaudu salasanallasi ja liitä tili profiilistasi.',
    'error_create_user'           => 'Tilin luonti epäonnistui. Ota yhteyttä ylläpitäjään.',
    'error_login'                 => 'Kirjautuminen epäonnistui. Yritä uudelleen tai käytä salasanaasi.',
    'error_expired'               => 'Tämä kirjautumisyritys vanheni. Yritä uudelleen.',
    'error_identity_taken'        => 'Tämä :provider-tili on jo liitetty toiseen käyttäjään.',
    'error_link_session'          => 'Istuntosi vaihtui tilin liittämisen aikana. Kirjaudu uudelleen ja yritä uudestaan.',
    'error_link_signed_out'       => 'Kirjaudu sisään ennen kuin liität toisen tilin.',
    'error_not_linked'            => 'Tätä palveluntarjoajaa ei ole liitetty tiliisi.',
    'error_unlink_last'           => 'Tämä on ainoa tapasi kirjautua. Aseta salasana tai liitä toinen palveluntarjoaja ennen tämän irrottamista.',

    // Credential form errors.
    'error_credentials_required'  => 'Anna sähköpostiosoitteesi ja salasanasi.',
    'error_credentials_invalid'   => 'Nämä kirjautumistiedot eivät ole oikein.',
    'error_registration_disabled' => 'Rekisteröityminen ei ole avoinna tällä sivustolla.',
    'error_email_invalid'         => 'Anna kelvollinen sähköpostiosoite.',
    'error_email_taken'           => 'Tälle sähköpostiosoitteelle on jo tili.',
    'error_password_short'        => 'Valitse vähintään :min merkin pituinen salasana.',
    'error_password_mismatch'     => 'Salasanat eivät täsmää.',
    'error_reset_token'           => 'Tämä palautuslinkki on virheellinen tai vanhentunut. Pyydä uusi.',
    'error_verify_token'          => 'Tämä vahvistuslinkki on virheellinen tai vanhentunut. Pyydä uusi.',
    'error_email_already_yours'   => 'Tämä osoite on jo tililläsi.',
    'error_email_send'            => 'Vahvistusviestin lähetys epäonnistui. Yritä myöhemmin uudelleen.',
];
