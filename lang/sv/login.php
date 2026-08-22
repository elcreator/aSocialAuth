<?php

return [
    // Login page.
    'or_sign_in_with'             => 'eller logga in med',
    'sign_in_with'                => 'Logga in med :provider',

    // Identities widget.
    'linked_accounts'             => 'Kopplade konton',
    'linked_accounts_intro'       => 'Vilket som helst av dem kan användas för att logga in på det här kontot.',
    'connect'                     => 'Koppla :provider',
    'disconnect'                  => 'Koppla bort',
    'connect_more'                => 'Lägg till ett till sätt att logga in',
    'no_linked_accounts'          => 'Inga konton är kopplade ännu.',
    'linked_on'                   => 'Kopplat :date',
    'last_used'                   => 'Senast använt :date',
    'never_used'                  => 'Har aldrig använts för inloggning',
    'linked'                      => ':provider är nu kopplat till ditt konto.',
    'unlinked'                    => ':provider har kopplats bort.',
    'confirm_unlink'              => 'Koppla bort :provider från det här kontot?',
    'placeholder_email_notice'    => 'Det här kontot saknar en riktig e-postadress. Lägg till en så att du kan återfå åtkomsten om du förlorar dina kopplade konton.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Det här kontot saknar e-postadress. Lägg till en riktig adress innan du ger det en manager-roll, annars går åtkomsten inte att återställa.',
    'promote_placeholder_email'   => 'Kontot registrerades via en leverantör som inte lämnar ut någon e-postadress, så adressen är en platshållare som inte kan ta emot post. Lösenordsåterställning skulle vara omöjlig. Be ägaren lägga till en riktig adress innan du ger kontot en manager-roll.',

    // E-mail and password forms.
    'sign_in'                     => 'Logga in',
    'sign_in_heading'             => 'Logga in',
    'email'                       => 'E-post',
    'username'                    => 'Användarnamn',
    'login_field'                 => 'E-post eller användarnamn',
    'password'                    => 'Lösenord',
    'password_confirm'            => 'Upprepa lösenordet',
    'full_name'                   => 'Fullständigt namn',
    'remember_me'                 => 'Håll mig inloggad',
    'forgot_password'             => 'Glömt lösenordet?',
    'register'                    => 'Skapa ett konto',
    'register_heading'            => 'Skapa ett konto',
    'have_account'                => 'Har du redan ett konto? Logga in',
    'recover_heading'             => 'Återställ ditt lösenord',
    'recover_intro'               => 'Ange din e-postadress så skickar vi en länk för att välja ett nytt lösenord.',
    'recover_submit'              => 'Skicka länk',
    'recover_sent'                => 'Om adressen hör till ett konto är länken på väg.',
    'reset_heading'               => 'Välj ett nytt lösenord',
    'reset_submit'                => 'Spara lösenord',
    'reset_done'                  => 'Ditt lösenord har ändrats. Du kan logga in med det nu.',
    'registered'                  => 'Ditt konto har skapats.',
    'back_to_sign_in'             => 'Tillbaka till inloggningen',
    'close'                       => 'Stäng',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Lägg till en e-postadress',
    'change_email'                => 'Ändra din e-postadress',
    'verify_submit'               => 'Skicka bekräftelselänk',
    'verify_sent'                 => 'Kolla din inkorg — följ länken för att bekräfta adressen.',
    'verify_pending'              => 'Väntar på att du bekräftar :email. Tills dess hör den inte till det här kontot.',
    'verify_cancel'               => 'Avbryt',
    'verify_cancelled'            => 'Ändringen av e-postadressen har avbrutits.',
    'verify_done'                 => ':email är nu bekräftad på ditt konto.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Bekräfta din e-postadress',
    'verify_mail_intro'           => 'Bekräfta den här adressen så att den kan användas för ditt konto.',
    'verify_mail_link'            => 'Bekräfta adressen',
    'verify_mail_ignore'          => 'Om du inte har bett om det här kan du strunta i meddelandet — adressen kommer inte att användas.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Återställ ditt lösenord',
    'reset_mail_intro'            => 'Någon har begärt att lösenordet till ditt konto ska återställas.',
    'reset_mail_link'             => 'Välj ett nytt lösenord',
    'reset_mail_ignore'           => 'Om det inte var du kan du strunta i meddelandet — ingenting har ändrats.',

    // Errors shown to the visitor.
    'error_generic'               => 'Inloggning via sociala nätverk är tillfälligt otillgänglig. Försök igen.',
    'error_oauth'                 => 'Leverantören avvisade begäran. Försök igen.',
    'error_no_identifier'         => 'Leverantören lämnade inte något giltigt användar-id.',
    'error_no_user'               => 'Inget konto är kopplat till den här identiteten. Logga in med ditt lösenord och koppla kontot från din profil.',
    'error_create_user'           => 'Kontot kunde inte skapas. Kontakta en administratör.',
    'error_login'                 => 'Inloggningen misslyckades. Försök igen eller använd ditt lösenord.',
    'error_expired'               => 'Det här inloggningsförsöket har gått ut. Försök igen.',
    'error_identity_taken'        => 'Det :provider-kontot är redan kopplat till en annan användare.',
    'error_link_session'          => 'Din session ändrades medan kontot kopplades. Logga in igen och försök på nytt.',
    'error_link_signed_out'       => 'Logga in innan du kopplar ett annat konto.',
    'error_not_linked'            => 'Den leverantören är inte kopplad till ditt konto.',
    'error_unlink_last'           => 'Det här är ditt enda sätt att logga in. Välj ett lösenord eller koppla en annan leverantör innan du kopplar bort den här.',

    // Credential form errors.
    'error_credentials_required'  => 'Ange din e-postadress och ditt lösenord.',
    'error_credentials_invalid'   => 'De inloggningsuppgifterna stämmer inte.',
    'error_registration_disabled' => 'Registrering är inte öppen på den här webbplatsen.',
    'error_email_invalid'         => 'Ange en giltig e-postadress.',
    'error_email_taken'           => 'Det finns redan ett konto med den e-postadressen.',
    'error_password_short'        => 'Välj ett lösenord med minst :min tecken.',
    'error_password_mismatch'     => 'De två lösenorden stämmer inte överens.',
    'error_reset_token'           => 'Återställningslänken är ogiltig eller har gått ut. Begär en ny.',
    'error_verify_token'          => 'Bekräftelselänken är ogiltig eller har gått ut. Begär en ny.',
    'error_email_already_yours'   => 'Adressen finns redan på ditt konto.',
    'error_email_send'            => 'Bekräftelsemeddelandet kunde inte skickas. Försök igen senare.',
];
