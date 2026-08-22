<?php

return [
    // Login page.
    'or_sign_in_with'             => 'o inicia sesión con',
    'sign_in_with'                => 'Iniciar sesión con :provider',

    // Identities widget.
    'linked_accounts'             => 'Cuentas vinculadas',
    'linked_accounts_intro'       => 'Cualquiera de ellas sirve para iniciar sesión en esta cuenta.',
    'connect'                     => 'Vincular :provider',
    'disconnect'                  => 'Desvincular',
    'connect_more'                => 'Añadir otra forma de iniciar sesión',
    'no_linked_accounts'          => 'Todavía no hay cuentas vinculadas.',
    'linked_on'                   => 'Vinculada el :date',
    'last_used'                   => 'Último uso :date',
    'never_used'                  => 'Nunca se ha usado para iniciar sesión',
    'linked'                      => ':provider ya está vinculado a tu cuenta.',
    'unlinked'                    => ':provider se ha desvinculado.',
    'confirm_unlink'              => '¿Desvincular :provider de esta cuenta?',
    'placeholder_email_notice'    => 'Esta cuenta no tiene una dirección de correo real. Añade una para poder recuperar el acceso si pierdes tus cuentas vinculadas.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Esta cuenta no tiene dirección de correo. Añade una real antes de darle un rol de gestor, o no habrá forma de recuperar el acceso.',
    'promote_placeholder_email'   => 'Esta cuenta se registró a través de un proveedor que no devuelve correo electrónico, así que su dirección es un marcador de posición que no puede recibir mensajes. Recuperar la contraseña sería imposible. Pide a su propietario que añada una dirección real antes de darle un rol de gestor.',

    // E-mail and password forms.
    'sign_in'                     => 'Iniciar sesión',
    'sign_in_heading'             => 'Iniciar sesión',
    'email'                       => 'Correo electrónico',
    'username'                    => 'Nombre de usuario',
    'login_field'                 => 'Correo electrónico o nombre de usuario',
    'password'                    => 'Contraseña',
    'password_confirm'            => 'Repite la contraseña',
    'full_name'                   => 'Nombre completo',
    'remember_me'                 => 'Mantener la sesión iniciada',
    'forgot_password'             => '¿Has olvidado tu contraseña?',
    'register'                    => 'Crear una cuenta',
    'register_heading'            => 'Crear una cuenta',
    'have_account'                => '¿Ya tienes una cuenta? Inicia sesión',
    'recover_heading'             => 'Restablecer tu contraseña',
    'recover_intro'               => 'Escribe tu dirección de correo y te enviaremos un enlace para establecer una contraseña nueva.',
    'recover_submit'              => 'Enviar enlace',
    'recover_sent'                => 'Si esa dirección tiene una cuenta, el enlace ya está en camino.',
    'reset_heading'               => 'Elige una contraseña nueva',
    'reset_submit'                => 'Establecer contraseña',
    'reset_done'                  => 'Tu contraseña se ha cambiado. Ya puedes iniciar sesión con ella.',
    'registered'                  => 'Tu cuenta se ha creado.',
    'back_to_sign_in'             => 'Volver al inicio de sesión',
    'close'                       => 'Cerrar',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Añadir una dirección de correo',
    'change_email'                => 'Cambiar tu dirección de correo',
    'verify_submit'               => 'Enviar enlace de confirmación',
    'verify_sent'                 => 'Revisa tu bandeja de entrada: sigue el enlace para confirmar la dirección.',
    'verify_pending'              => 'Esperando a que confirmes :email. Hasta que lo hagas, no está asociada a esta cuenta.',
    'verify_cancel'               => 'Cancelar',
    'verify_cancelled'            => 'El cambio de dirección de correo se ha cancelado.',
    'verify_done'                 => ':email ya está confirmada en tu cuenta.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Confirma tu dirección de correo',
    'verify_mail_intro'           => 'Confirma esta dirección para que pueda usarse en tu cuenta.',
    'verify_mail_link'            => 'Confirmar esta dirección',
    'verify_mail_ignore'          => 'Si no has solicitado esto, ignora este mensaje: la dirección no se usará.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Restablecer tu contraseña',
    'reset_mail_intro'            => 'Alguien ha pedido restablecer la contraseña de tu cuenta.',
    'reset_mail_link'             => 'Elegir una contraseña nueva',
    'reset_mail_ignore'           => 'Si no has sido tú, ignora este mensaje: no ha cambiado nada.',

    // Errors shown to the visitor.
    'error_generic'               => 'El inicio de sesión social no está disponible temporalmente. Inténtalo de nuevo.',
    'error_oauth'                 => 'El proveedor ha rechazado la solicitud. Inténtalo de nuevo.',
    'error_no_identifier'         => 'El proveedor no ha devuelto un identificador de usuario válido.',
    'error_no_user'               => 'No hay ninguna cuenta vinculada a esta identidad. Inicia sesión con tu contraseña y vincúlala desde tu perfil.',
    'error_create_user'           => 'No se ha podido crear la cuenta. Ponte en contacto con un administrador.',
    'error_login'                 => 'No se ha podido iniciar sesión. Inténtalo de nuevo o usa tu contraseña.',
    'error_expired'               => 'Ese intento de inicio de sesión ha caducado. Inténtalo de nuevo.',
    'error_identity_taken'        => 'Esa cuenta de :provider ya está vinculada a otro usuario.',
    'error_link_session'          => 'Tu sesión ha cambiado mientras vinculabas la cuenta. Inicia sesión de nuevo e inténtalo otra vez.',
    'error_link_signed_out'       => 'Inicia sesión antes de vincular otra cuenta.',
    'error_not_linked'            => 'Ese proveedor no está vinculado a tu cuenta.',
    'error_unlink_last'           => 'Es la única forma que tienes de iniciar sesión. Establece una contraseña o vincula otro proveedor antes de desvincular este.',

    // Credential form errors.
    'error_credentials_required'  => 'Escribe tu correo y tu contraseña.',
    'error_credentials_invalid'   => 'Esos datos de acceso no son correctos.',
    'error_registration_disabled' => 'El registro no está abierto en este sitio.',
    'error_email_invalid'         => 'Escribe una dirección de correo válida.',
    'error_email_taken'           => 'Ya existe una cuenta con esa dirección de correo.',
    'error_password_short'        => 'Elige una contraseña de al menos :min caracteres.',
    'error_password_mismatch'     => 'Las dos contraseñas no coinciden.',
    'error_reset_token'           => 'Ese enlace de restablecimiento no es válido o ha caducado. Solicita uno nuevo.',
    'error_verify_token'          => 'Ese enlace de confirmación no es válido o ha caducado. Solicita uno nuevo.',
    'error_email_already_yours'   => 'Esa dirección ya está en tu cuenta.',
    'error_email_send'            => 'No se ha podido enviar el mensaje de confirmación. Inténtalo más tarde.',
];
