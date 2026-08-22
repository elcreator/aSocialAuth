<?php

return [
    // Login page.
    'or_sign_in_with'             => 'ou inicie sessão com',
    'sign_in_with'                => 'Iniciar sessão com :provider',

    // Identities widget.
    'linked_accounts'             => 'Contas associadas',
    'linked_accounts_intro'       => 'Qualquer uma delas pode ser usada para iniciar sessão nesta conta.',
    'connect'                     => 'Associar :provider',
    'disconnect'                  => 'Desassociar',
    'connect_more'                => 'Adicionar outra forma de iniciar sessão',
    'no_linked_accounts'          => 'Ainda não há contas associadas.',
    'linked_on'                   => 'Associada a :date',
    'last_used'                   => 'Última utilização :date',
    'never_used'                  => 'Nunca usada para iniciar sessão',
    'linked'                      => ':provider está agora associado à sua conta.',
    'unlinked'                    => ':provider foi desassociado.',
    'confirm_unlink'              => 'Desassociar :provider desta conta?',
    'placeholder_email_notice'    => 'Esta conta não tem um endereço de e-mail real. Adicione um para poder recuperar o acesso se perder as contas associadas.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Esta conta não tem endereço de e-mail. Adicione um endereço real antes de lhe dar um papel de gestor, ou não haverá forma de recuperar o acesso.',
    'promote_placeholder_email'   => 'Esta conta registou-se através de um fornecedor que não devolve e-mail, por isso o seu endereço é um substituto que não recebe mensagens. A recuperação da palavra-passe seria impossível. Peça ao proprietário que adicione um endereço real antes de lhe dar um papel de gestor.',

    // E-mail and password forms.
    'sign_in'                     => 'Iniciar sessão',
    'sign_in_heading'             => 'Iniciar sessão',
    'email'                       => 'E-mail',
    'username'                    => 'Nome de utilizador',
    'login_field'                 => 'E-mail ou nome de utilizador',
    'password'                    => 'Palavra-passe',
    'password_confirm'            => 'Repita a palavra-passe',
    'full_name'                   => 'Nome completo',
    'remember_me'                 => 'Manter a sessão iniciada',
    'forgot_password'             => 'Esqueceu-se da palavra-passe?',
    'register'                    => 'Criar uma conta',
    'register_heading'            => 'Criar uma conta',
    'have_account'                => 'Já tem conta? Inicie sessão',
    'recover_heading'             => 'Repor a palavra-passe',
    'recover_intro'               => 'Introduza o seu endereço de e-mail e enviaremos uma ligação para definir uma nova palavra-passe.',
    'recover_submit'              => 'Enviar ligação',
    'recover_sent'                => 'Se esse endereço tiver uma conta, a ligação já vai a caminho.',
    'reset_heading'               => 'Escolha uma nova palavra-passe',
    'reset_submit'                => 'Definir palavra-passe',
    'reset_done'                  => 'A sua palavra-passe foi alterada. Já pode iniciar sessão com ela.',
    'registered'                  => 'A sua conta foi criada.',
    'back_to_sign_in'             => 'Voltar ao início de sessão',
    'close'                       => 'Fechar',

    // Adding and proving an e-mail address.
    'add_email'                   => 'Adicionar um endereço de e-mail',
    'change_email'                => 'Alterar o seu endereço de e-mail',
    'verify_submit'               => 'Enviar ligação de confirmação',
    'verify_sent'                 => 'Verifique a sua caixa de entrada — siga a ligação para confirmar o endereço.',
    'verify_pending'              => 'A aguardar a confirmação de :email. Até lá, não está associado a esta conta.',
    'verify_cancel'               => 'Cancelar',
    'verify_cancelled'            => 'A alteração do endereço de e-mail foi cancelada.',
    'verify_done'                 => ':email está agora confirmado na sua conta.',

    // Verification e-mail.
    'verify_mail_subject'         => 'Confirme o seu endereço de e-mail',
    'verify_mail_intro'           => 'Confirme este endereço para que possa ser usado na sua conta.',
    'verify_mail_link'            => 'Confirmar este endereço',
    'verify_mail_ignore'          => 'Se não pediu isto, ignore esta mensagem — o endereço não será usado.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Repor a palavra-passe',
    'reset_mail_intro'            => 'Alguém pediu a reposição da palavra-passe da sua conta.',
    'reset_mail_link'             => 'Escolher uma nova palavra-passe',
    'reset_mail_ignore'           => 'Se não foi você, ignore esta mensagem — nada foi alterado.',

    // Errors shown to the visitor.
    'error_generic'               => 'O início de sessão social está temporariamente indisponível. Tente novamente.',
    'error_oauth'                 => 'O fornecedor rejeitou o pedido. Tente novamente.',
    'error_no_identifier'         => 'O fornecedor não devolveu um identificador de utilizador válido.',
    'error_no_user'               => 'Não há nenhuma conta associada a esta identidade. Inicie sessão com a sua palavra-passe e associe a conta no seu perfil.',
    'error_create_user'           => 'Não foi possível criar a conta. Contacte um administrador.',
    'error_login'                 => 'O início de sessão falhou. Tente novamente ou use a sua palavra-passe.',
    'error_expired'               => 'Essa tentativa de início de sessão expirou. Tente novamente.',
    'error_identity_taken'        => 'Essa conta :provider já está associada a outro utilizador.',
    'error_link_session'          => 'A sua sessão mudou enquanto associava a conta. Inicie sessão novamente e tente outra vez.',
    'error_link_signed_out'       => 'Inicie sessão antes de associar outra conta.',
    'error_not_linked'            => 'Esse fornecedor não está associado à sua conta.',
    'error_unlink_last'           => 'Esta é a única forma que tem de iniciar sessão. Defina uma palavra-passe ou associe outro fornecedor antes de desassociar este.',

    // Credential form errors.
    'error_credentials_required'  => 'Introduza o seu e-mail e a sua palavra-passe.',
    'error_credentials_invalid'   => 'Esses dados de acesso não estão corretos.',
    'error_registration_disabled' => 'O registo não está aberto neste site.',
    'error_email_invalid'         => 'Introduza um endereço de e-mail válido.',
    'error_email_taken'           => 'Já existe uma conta com esse endereço de e-mail.',
    'error_password_short'        => 'Escolha uma palavra-passe com pelo menos :min caracteres.',
    'error_password_mismatch'     => 'As duas palavras-passe não coincidem.',
    'error_reset_token'           => 'Essa ligação de reposição é inválida ou expirou. Peça uma nova.',
    'error_verify_token'          => 'Essa ligação de confirmação é inválida ou expirou. Peça uma nova.',
    'error_email_already_yours'   => 'Esse endereço já está na sua conta.',
    'error_email_send'            => 'Não foi possível enviar a mensagem de confirmação. Tente mais tarde.',
];
