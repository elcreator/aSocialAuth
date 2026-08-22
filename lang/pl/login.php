<?php

return [
    // Login page.
    'or_sign_in_with'             => 'lub zaloguj się przez',
    'sign_in_with'                => 'Zaloguj się przez :provider',

    // Identities widget.
    'linked_accounts'             => 'Powiązane konta',
    'linked_accounts_intro'       => 'Każdego z nich możesz użyć, aby zalogować się na to konto.',
    'connect'                     => 'Powiąż :provider',
    'disconnect'                  => 'Odłącz',
    'connect_more'                => 'Dodaj kolejny sposób logowania',
    'no_linked_accounts'          => 'Nie powiązano jeszcze żadnego konta.',
    'linked_on'                   => 'Powiązano :date',
    'last_used'                   => 'Ostatnio użyto :date',
    'never_used'                  => 'Nigdy nie użyto do logowania',
    'linked'                      => ':provider został powiązany z Twoim kontem.',
    'unlinked'                    => ':provider został odłączony.',
    'confirm_unlink'              => 'Odłączyć :provider od tego konta?',
    'placeholder_email_notice'    => 'To konto nie ma prawdziwego adresu e-mail. Dodaj go, aby móc odzyskać dostęp, jeśli stracisz powiązane konta.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'To konto nie ma adresu e-mail. Dodaj prawdziwy adres, zanim nadasz mu rolę menedżera, w przeciwnym razie nie da się odzyskać do niego dostępu.',
    'promote_placeholder_email'   => 'To konto zostało założone przez dostawcę, który nie zwraca adresu e-mail, więc jego adres jest zastępczy i nie odbiera poczty. Odzyskanie hasła byłoby niemożliwe. Poproś właściciela o prawdziwy adres, zanim nadasz kontu rolę menedżera.',

    // E-mail and password forms.
    'sign_in'                     => 'Zaloguj się',
    'sign_in_heading'             => 'Logowanie',
    'email'                       => 'E-mail',
    'username'                    => 'Nazwa użytkownika',
    'login_field'                 => 'E-mail lub nazwa użytkownika',
    'password'                    => 'Hasło',
    'password_confirm'            => 'Powtórz hasło',
    'full_name'                   => 'Imię i nazwisko',
    'remember_me'                 => 'Pozostań zalogowany',
    'forgot_password'             => 'Nie pamiętasz hasła?',
    'register'                    => 'Załóż konto',
    'register_heading'            => 'Załóż konto',
    'have_account'                => 'Masz już konto? Zaloguj się',
    'recover_heading'             => 'Zresetuj hasło',
    'recover_intro'               => 'Podaj swój adres e-mail, a wyślemy Ci link do ustawienia nowego hasła.',
    'recover_submit'              => 'Wyślij link',
    'recover_sent'                => 'Jeśli do tego adresu przypisane jest konto, link jest już w drodze.',
    'reset_heading'               => 'Wybierz nowe hasło',
    'reset_submit'                => 'Ustaw hasło',
    'reset_done'                  => 'Twoje hasło zostało zmienione. Możesz się nim teraz zalogować.',
    'registered'                  => 'Twoje konto zostało utworzone.',
    'back_to_sign_in'             => 'Powrót do logowania',
    'close'                       => 'Zamknij',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Dodaj adres e-mail',
    'change_email'                => 'Zmień adres e-mail',
    'verify_submit'               => 'Wyślij link potwierdzający',
    'verify_sent'                 => 'Sprawdź skrzynkę — kliknij link, aby potwierdzić adres.',
    'verify_pending'              => 'Czekamy na potwierdzenie adresu :email. Do tego czasu nie jest on przypisany do tego konta.',
    'verify_cancel'               => 'Anuluj',
    'verify_cancelled'            => 'Zmiana adresu e-mail została anulowana.',
    'verify_done'                 => 'Adres :email został potwierdzony na Twoim koncie.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Potwierdź swój adres e-mail',
    'verify_mail_intro'           => 'Potwierdź ten adres, aby można go było używać na Twoim koncie.',
    'verify_mail_link'            => 'Potwierdź ten adres',
    'verify_mail_ignore'          => 'Jeśli nie prosiłeś o to, zignoruj tę wiadomość — adres nie zostanie użyty.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Zresetuj hasło',
    'reset_mail_intro'            => 'Ktoś poprosił o zresetowanie hasła do Twojego konta.',
    'reset_mail_link'             => 'Wybierz nowe hasło',
    'reset_mail_ignore'           => 'Jeśli to nie Ty, zignoruj tę wiadomość — nic się nie zmieniło.',

    // Errors shown to the visitor.
    'error_generic'               => 'Logowanie przez serwisy społecznościowe jest chwilowo niedostępne. Spróbuj ponownie.',
    'error_oauth'                 => 'Dostawca odrzucił żądanie. Spróbuj ponownie.',
    'error_no_identifier'         => 'Dostawca nie zwrócił prawidłowego identyfikatora użytkownika.',
    'error_no_user'               => 'Z tą tożsamością nie jest powiązane żadne konto. Zaloguj się hasłem i powiąż konto w swoim profilu.',
    'error_create_user'           => 'Nie udało się utworzyć konta. Skontaktuj się z administratorem.',
    'error_login'                 => 'Logowanie nie powiodło się. Spróbuj ponownie lub użyj hasła.',
    'error_expired'               => 'Ta próba logowania wygasła. Spróbuj ponownie.',
    'error_identity_taken'        => 'To konto :provider jest już powiązane z innym użytkownikiem.',
    'error_link_session'          => 'Twoja sesja zmieniła się podczas łączenia konta. Zaloguj się ponownie i spróbuj jeszcze raz.',
    'error_link_signed_out'       => 'Zaloguj się, zanim powiążesz kolejne konto.',
    'error_not_linked'            => 'Ten dostawca nie jest powiązany z Twoim kontem.',
    'error_unlink_last'           => 'To jedyny sposób, w jaki możesz się zalogować. Ustaw hasło lub powiąż innego dostawcę, zanim odłączysz ten.',

    // Credential form errors.
    'error_credentials_required'  => 'Podaj adres e-mail i hasło.',
    'error_credentials_invalid'   => 'Te dane logowania są nieprawidłowe.',
    'error_registration_disabled' => 'Rejestracja w tym serwisie jest zamknięta.',
    'error_email_invalid'         => 'Podaj prawidłowy adres e-mail.',
    'error_email_taken'           => 'Konto z tym adresem e-mail już istnieje.',
    'error_password_short'        => 'Wybierz hasło o długości co najmniej :min znaków.',
    'error_password_mismatch'     => 'Podane hasła nie są takie same.',
    'error_reset_token'           => 'Ten link do resetu jest nieprawidłowy lub wygasł. Poproś o nowy.',
    'error_verify_token'          => 'Ten link potwierdzający jest nieprawidłowy lub wygasł. Poproś o nowy.',
    'error_email_already_yours'   => 'Ten adres jest już na Twoim koncie.',
    'error_email_send'            => 'Nie udało się wysłać wiadomości potwierdzającej. Spróbuj później.',
];
