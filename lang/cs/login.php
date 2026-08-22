<?php

return [
    // Login page.
    'or_sign_in_with'             => 'nebo se přihlaste přes',
    'sign_in_with'                => 'Přihlásit se přes :provider',

    // Identities widget.
    'linked_accounts'             => 'Propojené účty',
    'linked_accounts_intro'       => 'Kterýkoli z nich lze použít k přihlášení k tomuto účtu.',
    'connect'                     => 'Propojit :provider',
    'disconnect'                  => 'Odpojit',
    'connect_more'                => 'Přidat další způsob přihlášení',
    'no_linked_accounts'          => 'Zatím nejsou propojeny žádné účty.',
    'linked_on'                   => 'Propojeno :date',
    'last_used'                   => 'Naposledy použito :date',
    'never_used'                  => 'Nikdy nepoužito k přihlášení',
    'linked'                      => ':provider je nyní propojen s vaším účtem.',
    'unlinked'                    => ':provider byl odpojen.',
    'confirm_unlink'              => 'Odpojit :provider od tohoto účtu?',
    'placeholder_email_notice'    => 'Tento účet nemá skutečnou e-mailovou adresu. Přidejte ji, abyste mohli obnovit přístup, pokud přijdete o propojené účty.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Tento účet nemá e-mailovou adresu. Přidejte skutečnou adresu dřív, než mu dáte roli správce, jinak nepůjde přístup obnovit.',
    'promote_placeholder_email'   => 'Tento účet vznikl přes poskytovatele, který nevrací e-mailovou adresu, takže jeho adresa je zástupná a nemůže přijímat poštu. Obnovení hesla by nebylo možné. Požádejte majitele o skutečnou adresu dřív, než mu dáte roli správce.',

    // E-mail and password forms.
    'sign_in'                     => 'Přihlásit se',
    'sign_in_heading'             => 'Přihlášení',
    'email'                       => 'E-mail',
    'username'                    => 'Uživatelské jméno',
    'login_field'                 => 'E-mail nebo uživatelské jméno',
    'password'                    => 'Heslo',
    'password_confirm'            => 'Zopakujte heslo',
    'full_name'                   => 'Celé jméno',
    'remember_me'                 => 'Zůstat přihlášen',
    'forgot_password'             => 'Zapomněli jste heslo?',
    'register'                    => 'Vytvořit účet',
    'register_heading'            => 'Vytvořit účet',
    'have_account'                => 'Už máte účet? Přihlaste se',
    'recover_heading'             => 'Obnovení hesla',
    'recover_intro'               => 'Zadejte svou e-mailovou adresu a pošleme vám odkaz pro nastavení nového hesla.',
    'recover_submit'              => 'Odeslat odkaz',
    'recover_sent'                => 'Pokud k této adrese patří účet, odkaz už je na cestě.',
    'reset_heading'               => 'Zvolte nové heslo',
    'reset_submit'                => 'Nastavit heslo',
    'reset_done'                  => 'Vaše heslo bylo změněno. Nyní se s ním můžete přihlásit.',
    'registered'                  => 'Váš účet byl vytvořen.',
    'back_to_sign_in'             => 'Zpět na přihlášení',
    'close'                       => 'Zavřít',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Přidat e-mailovou adresu',
    'change_email'                => 'Změnit e-mailovou adresu',
    'verify_submit'               => 'Odeslat potvrzovací odkaz',
    'verify_sent'                 => 'Zkontrolujte schránku — adresu potvrdíte kliknutím na odkaz.',
    'verify_pending'              => 'Čeká se na potvrzení adresy :email. Dokud ji nepotvrdíte, není k tomuto účtu připojena.',
    'verify_cancel'               => 'Zrušit',
    'verify_cancelled'            => 'Změna e-mailové adresy byla zrušena.',
    'verify_done'                 => 'Adresa :email je nyní na vašem účtu potvrzena.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Potvrďte svou e-mailovou adresu',
    'verify_mail_intro'           => 'Potvrďte prosím tuto adresu, aby ji bylo možné použít pro váš účet.',
    'verify_mail_link'            => 'Potvrdit tuto adresu',
    'verify_mail_ignore'          => 'Pokud jste o to nežádali, zprávu ignorujte — adresa nebude použita.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Obnovení hesla',
    'reset_mail_intro'            => 'Někdo požádal o obnovení hesla k vašemu účtu.',
    'reset_mail_link'             => 'Zvolit nové heslo',
    'reset_mail_ignore'           => 'Pokud jste to nebyli vy, zprávu ignorujte — nic se nezměnilo.',

    // Errors shown to the visitor.
    'error_generic'               => 'Přihlášení přes sociální sítě je dočasně nedostupné. Zkuste to prosím znovu.',
    'error_oauth'                 => 'Poskytovatel požadavek odmítl. Zkuste to prosím znovu.',
    'error_no_identifier'         => 'Poskytovatel nevrátil platný identifikátor uživatele.',
    'error_no_user'               => 'K této identitě není propojen žádný účet. Přihlaste se heslem a propojte účet ve svém profilu.',
    'error_create_user'           => 'Účet se nepodařilo vytvořit. Obraťte se na správce.',
    'error_login'                 => 'Přihlášení se nezdařilo. Zkuste to znovu nebo použijte heslo.',
    'error_expired'               => 'Platnost tohoto pokusu o přihlášení vypršela. Zkuste to znovu.',
    'error_identity_taken'        => 'Tento účet :provider je už propojen s jiným uživatelem.',
    'error_link_session'          => 'Během propojování účtu se změnila vaše relace. Přihlaste se znovu a zkuste to zase.',
    'error_link_signed_out'       => 'Než propojíte další účet, přihlaste se.',
    'error_not_linked'            => 'Tento poskytovatel není propojen s vaším účtem.',
    'error_unlink_last'           => 'Toto je váš jediný způsob přihlášení. Než ho odpojíte, nastavte si heslo nebo propojte jiného poskytovatele.',

    // Credential form errors.
    'error_credentials_required'  => 'Zadejte e-mail a heslo.',
    'error_credentials_invalid'   => 'Tyto přihlašovací údaje nejsou správné.',
    'error_registration_disabled' => 'Registrace na tomto webu není otevřená.',
    'error_email_invalid'         => 'Zadejte platnou e-mailovou adresu.',
    'error_email_taken'           => 'Účet s touto e-mailovou adresou už existuje.',
    'error_password_short'        => 'Zvolte heslo dlouhé alespoň :min znaků.',
    'error_password_mismatch'     => 'Hesla se neshodují.',
    'error_reset_token'           => 'Tento odkaz pro obnovení je neplatný nebo vypršel. Vyžádejte si nový.',
    'error_verify_token'          => 'Tento potvrzovací odkaz je neplatný nebo vypršel. Vyžádejte si nový.',
    'error_email_already_yours'   => 'Tuto adresu už na svém účtu máte.',
    'error_email_send'            => 'Potvrzovací zprávu se nepodařilo odeslat. Zkuste to prosím později.',
];
