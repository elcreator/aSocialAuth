<?php

return [
    // Login page.
    'or_sign_in_with'             => 'oppure accedi con',
    'sign_in_with'                => 'Accedi con :provider',

    // Identities widget.
    'linked_accounts'             => 'Account collegati',
    'linked_accounts_intro'       => 'Puoi usare uno qualsiasi di questi per accedere a questo account.',
    'connect'                     => 'Collega :provider',
    'disconnect'                  => 'Scollega',
    'connect_more'                => 'Aggiungi un altro modo per accedere',
    'no_linked_accounts'          => 'Non c’è ancora nessun account collegato.',
    'linked_on'                   => 'Collegato il :date',
    'last_used'                   => 'Ultimo utilizzo :date',
    'never_used'                  => 'Mai usato per accedere',
    'linked'                      => ':provider è ora collegato al tuo account.',
    'unlinked'                    => ':provider è stato scollegato.',
    'confirm_unlink'              => 'Scollegare :provider da questo account?',
    'placeholder_email_notice'    => 'Questo account non ha un indirizzo e-mail reale. Aggiungine uno per poter recuperare l’accesso se perdi gli account collegati.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Questo account non ha un indirizzo e-mail. Aggiungine uno reale prima di assegnargli un ruolo di gestione, altrimenti non ci sarà modo di recuperarne l’accesso.',
    'promote_placeholder_email'   => 'Questo account è stato registrato tramite un provider che non restituisce l’e-mail, quindi il suo indirizzo è un segnaposto che non può ricevere messaggi. Il recupero della password sarebbe impossibile. Chiedi al proprietario di aggiungere un indirizzo reale prima di assegnargli un ruolo di gestione.',

    // E-mail and password forms.
    'sign_in'                     => 'Accedi',
    'sign_in_heading'             => 'Accedi',
    'email'                       => 'E-mail',
    'username'                    => 'Nome utente',
    'login_field'                 => 'E-mail o nome utente',
    'password'                    => 'Password',
    'password_confirm'            => 'Ripeti la password',
    'full_name'                   => 'Nome completo',
    'remember_me'                 => 'Resta connesso',
    'forgot_password'             => 'Hai dimenticato la password?',
    'register'                    => 'Crea un account',
    'register_heading'            => 'Crea un account',
    'have_account'                => 'Hai già un account? Accedi',
    'recover_heading'             => 'Reimposta la password',
    'recover_intro'               => 'Inserisci il tuo indirizzo e-mail e ti invieremo un link per impostare una nuova password.',
    'recover_submit'              => 'Invia il link',
    'recover_sent'                => 'Se a quell’indirizzo corrisponde un account, il link è già in arrivo.',
    'reset_heading'               => 'Scegli una nuova password',
    'reset_submit'                => 'Imposta la password',
    'reset_done'                  => 'La tua password è stata cambiata. Ora puoi accedere con questa.',
    'registered'                  => 'Il tuo account è stato creato.',
    'back_to_sign_in'             => 'Torna all’accesso',
    'close'                       => 'Chiudi',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Aggiungi un indirizzo e-mail',
    'change_email'                => 'Cambia il tuo indirizzo e-mail',
    'verify_submit'               => 'Invia il link di conferma',
    'verify_sent'                 => 'Controlla la posta in arrivo: segui il link per confermare l’indirizzo.',
    'verify_pending'              => 'In attesa della conferma di :email. Finché non la confermi, non è collegata a questo account.',
    'verify_cancel'               => 'Annulla',
    'verify_cancelled'            => 'La modifica dell’indirizzo e-mail è stata annullata.',
    'verify_done'                 => ':email è ora confermato sul tuo account.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Conferma il tuo indirizzo e-mail',
    'verify_mail_intro'           => 'Conferma questo indirizzo perché possa essere usato per il tuo account.',
    'verify_mail_link'            => 'Conferma questo indirizzo',
    'verify_mail_ignore'          => 'Se non hai richiesto tu questa operazione, ignora il messaggio: l’indirizzo non verrà usato.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Reimposta la password',
    'reset_mail_intro'            => 'Qualcuno ha chiesto di reimpostare la password del tuo account.',
    'reset_mail_link'             => 'Scegli una nuova password',
    'reset_mail_ignore'           => 'Se non sei stato tu, ignora il messaggio: non è cambiato nulla.',

    // Errors shown to the visitor.
    'error_generic'               => 'L’accesso tramite social è temporaneamente non disponibile. Riprova.',
    'error_oauth'                 => 'Il provider ha rifiutato la richiesta. Riprova.',
    'error_no_identifier'         => 'Il provider non ha restituito un identificativo utente valido.',
    'error_no_user'               => 'Nessun account è collegato a questa identità. Accedi con la password e collega l’account dal tuo profilo.',
    'error_create_user'           => 'Creazione dell’account non riuscita. Contatta un amministratore.',
    'error_login'                 => 'Accesso non riuscito. Riprova oppure usa la password.',
    'error_expired'               => 'Questo tentativo di accesso è scaduto. Riprova.',
    'error_identity_taken'        => 'Quell’account :provider è già collegato a un altro utente.',
    'error_link_session'          => 'La tua sessione è cambiata durante il collegamento dell’account. Accedi di nuovo e riprova.',
    'error_link_signed_out'       => 'Accedi prima di collegare un altro account.',
    'error_not_linked'            => 'Quel provider non è collegato al tuo account.',
    'error_unlink_last'           => 'È l’unico modo che hai per accedere. Imposta una password o collega un altro provider prima di scollegare questo.',

    // Credential form errors.
    'error_credentials_required'  => 'Inserisci e-mail e password.',
    'error_credentials_invalid'   => 'Questi dati di accesso non sono corretti.',
    'error_registration_disabled' => 'Le registrazioni non sono aperte su questo sito.',
    'error_email_invalid'         => 'Inserisci un indirizzo e-mail valido.',
    'error_email_taken'           => 'Esiste già un account con quell’indirizzo e-mail.',
    'error_password_short'        => 'Scegli una password di almeno :min caratteri.',
    'error_password_mismatch'     => 'Le due password non coincidono.',
    'error_reset_token'           => 'Questo link di reimpostazione non è valido o è scaduto. Richiedine uno nuovo.',
    'error_verify_token'          => 'Questo link di conferma non è valido o è scaduto. Richiedine uno nuovo.',
    'error_email_already_yours'   => 'Quell’indirizzo è già sul tuo account.',
    'error_email_send'            => 'Non è stato possibile inviare il messaggio di conferma. Riprova più tardi.',
];
