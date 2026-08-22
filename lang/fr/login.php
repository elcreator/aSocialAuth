<?php

return [
    // Login page.
    'or_sign_in_with'             => 'ou connectez-vous avec',
    'sign_in_with'                => 'Se connecter avec :provider',

    // Identities widget.
    'linked_accounts'             => 'Comptes liés',
    'linked_accounts_intro'       => 'Chacun d’eux permet de se connecter à ce compte.',
    'connect'                     => 'Lier :provider',
    'disconnect'                  => 'Dissocier',
    'connect_more'                => 'Ajouter un autre moyen de connexion',
    'no_linked_accounts'          => 'Aucun compte n’est encore lié.',
    'linked_on'                   => 'Lié le :date',
    'last_used'                   => 'Dernière utilisation :date',
    'never_used'                  => 'Jamais utilisé pour se connecter',
    'linked'                      => ':provider est maintenant lié à votre compte.',
    'unlinked'                    => ':provider a été dissocié.',
    'confirm_unlink'              => 'Dissocier :provider de ce compte ?',
    'placeholder_email_notice'    => 'Ce compte n’a pas de véritable adresse e-mail. Ajoutez-en une afin de pouvoir récupérer l’accès si vous perdez vos comptes liés.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Ce compte n’a pas d’adresse e-mail. Ajoutez-en une véritable avant de lui attribuer un rôle de gestionnaire, sinon il sera impossible d’en récupérer l’accès.',
    'promote_placeholder_email'   => 'Ce compte a été créé via un fournisseur qui ne renvoie pas d’adresse e-mail : son adresse est donc un substitut qui ne reçoit aucun message. La récupération du mot de passe serait impossible. Demandez à son propriétaire d’ajouter une adresse réelle avant de lui attribuer un rôle de gestionnaire.',

    // E-mail and password forms.
    'sign_in'                     => 'Se connecter',
    'sign_in_heading'             => 'Connexion',
    'email'                       => 'E-mail',
    'username'                    => 'Nom d’utilisateur',
    'login_field'                 => 'E-mail ou nom d’utilisateur',
    'password'                    => 'Mot de passe',
    'password_confirm'            => 'Répéter le mot de passe',
    'full_name'                   => 'Nom complet',
    'remember_me'                 => 'Rester connecté',
    'forgot_password'             => 'Mot de passe oublié ?',
    'register'                    => 'Créer un compte',
    'register_heading'            => 'Créer un compte',
    'have_account'                => 'Vous avez déjà un compte ? Connectez-vous',
    'recover_heading'             => 'Réinitialiser votre mot de passe',
    'recover_intro'               => 'Saisissez votre adresse e-mail et nous vous enverrons un lien pour définir un nouveau mot de passe.',
    'recover_submit'              => 'Envoyer le lien',
    'recover_sent'                => 'Si un compte correspond à cette adresse, un lien est en route.',
    'reset_heading'               => 'Choisir un nouveau mot de passe',
    'reset_submit'                => 'Définir le mot de passe',
    'reset_done'                  => 'Votre mot de passe a été modifié. Vous pouvez vous connecter avec.',
    'registered'                  => 'Votre compte a été créé.',
    'back_to_sign_in'             => 'Retour à la connexion',
    'close'                       => 'Fermer',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Ajouter une adresse e-mail',
    'change_email'                => 'Modifier votre adresse e-mail',
    'verify_submit'               => 'Envoyer le lien de confirmation',
    'verify_sent'                 => 'Consultez votre boîte de réception — suivez le lien pour confirmer l’adresse.',
    'verify_pending'              => 'En attente de la confirmation de :email. Tant qu’elle n’est pas confirmée, elle n’est pas rattachée à ce compte.',
    'verify_cancel'               => 'Annuler',
    'verify_cancelled'            => 'Le changement d’adresse e-mail a été annulé.',
    'verify_done'                 => ':email est maintenant confirmée sur votre compte.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Confirmez votre adresse e-mail',
    'verify_mail_intro'           => 'Veuillez confirmer cette adresse afin qu’elle puisse être utilisée pour votre compte.',
    'verify_mail_link'            => 'Confirmer cette adresse',
    'verify_mail_ignore'          => 'Si vous n’êtes pas à l’origine de cette demande, ignorez ce message — l’adresse ne sera pas utilisée.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Réinitialiser votre mot de passe',
    'reset_mail_intro'            => 'Quelqu’un a demandé la réinitialisation du mot de passe de votre compte.',
    'reset_mail_link'             => 'Choisir un nouveau mot de passe',
    'reset_mail_ignore'           => 'Si ce n’était pas vous, ignorez ce message — rien n’a changé.',

    // Errors shown to the visitor.
    'error_generic'               => 'La connexion via les réseaux sociaux est temporairement indisponible. Veuillez réessayer.',
    'error_oauth'                 => 'Le fournisseur a rejeté la demande. Veuillez réessayer.',
    'error_no_identifier'         => 'Le fournisseur n’a pas renvoyé d’identifiant utilisateur valide.',
    'error_no_user'               => 'Aucun compte n’est lié à cette identité. Connectez-vous avec votre mot de passe, puis liez le compte depuis votre profil.',
    'error_create_user'           => 'Impossible de créer le compte. Veuillez contacter un administrateur.',
    'error_login'                 => 'Échec de la connexion. Réessayez ou utilisez votre mot de passe.',
    'error_expired'               => 'Cette tentative de connexion a expiré. Veuillez réessayer.',
    'error_identity_taken'        => 'Ce compte :provider est déjà lié à un autre utilisateur.',
    'error_link_session'          => 'Votre session a changé pendant la liaison du compte. Reconnectez-vous et réessayez.',
    'error_link_signed_out'       => 'Connectez-vous avant de lier un autre compte.',
    'error_not_linked'            => 'Ce fournisseur n’est pas lié à votre compte.',
    'error_unlink_last'           => 'C’est votre seul moyen de connexion. Définissez un mot de passe ou liez un autre fournisseur avant de dissocier celui-ci.',

    // Credential form errors.
    'error_credentials_required'  => 'Veuillez saisir votre e-mail et votre mot de passe.',
    'error_credentials_invalid'   => 'Ces identifiants ne sont pas corrects.',
    'error_registration_disabled' => 'Les inscriptions ne sont pas ouvertes sur ce site.',
    'error_email_invalid'         => 'Veuillez saisir une adresse e-mail valide.',
    'error_email_taken'           => 'Un compte existe déjà pour cette adresse e-mail.',
    'error_password_short'        => 'Choisissez un mot de passe d’au moins :min caractères.',
    'error_password_mismatch'     => 'Les deux mots de passe ne correspondent pas.',
    'error_reset_token'           => 'Ce lien de réinitialisation est invalide ou a expiré. Demandez-en un nouveau.',
    'error_verify_token'          => 'Ce lien de confirmation est invalide ou a expiré. Demandez-en un nouveau.',
    'error_email_already_yours'   => 'Cette adresse est déjà sur votre compte.',
    'error_email_send'            => 'Le message de confirmation n’a pas pu être envoyé. Veuillez réessayer plus tard.',
];
