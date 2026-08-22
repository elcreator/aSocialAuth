<?php

return [
    // Login page.
    'or_sign_in_with'             => '或使用以下方式登录',
    'sign_in_with'                => '使用 :provider 登录',

    // Identities widget.
    'linked_accounts'             => '已关联的账号',
    'linked_accounts_intro'       => '其中任意一个都可以用来登录此账号。',
    'connect'                     => '关联 :provider',
    'disconnect'                  => '解除关联',
    'connect_more'                => '添加另一种登录方式',
    'no_linked_accounts'          => '尚未关联任何账号。',
    'linked_on'                   => '关联于 :date',
    'last_used'                   => '最近使用 :date',
    'never_used'                  => '从未用于登录',
    'linked'                      => ':provider 已关联到你的账号。',
    'unlinked'                    => ':provider 已解除关联。',
    'confirm_unlink'              => '要将 :provider 从此账号解除关联吗？',
    'placeholder_email_notice'    => '此账号没有真实的电子邮箱地址。请添加一个，以便在失去已关联账号时仍能找回访问权限。',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => '此账号没有电子邮箱地址。在授予管理员角色之前请添加一个真实地址，否则将无法找回访问权限。',
    'promote_placeholder_email'   => '此账号是通过不返回邮箱地址的服务商注册的，因此其地址只是占位符，无法接收邮件，密码找回将无从谈起。在授予管理员角色之前，请让账号所有者补充一个真实地址。',

    // E-mail and password forms.
    'sign_in'                     => '登录',
    'sign_in_heading'             => '登录',
    'email'                       => '电子邮箱',
    'username'                    => '用户名',
    'login_field'                 => '电子邮箱或用户名',
    'password'                    => '密码',
    'password_confirm'            => '重复密码',
    'full_name'                   => '姓名',
    'remember_me'                 => '保持登录状态',
    'forgot_password'             => '忘记密码？',
    'register'                    => '创建账号',
    'register_heading'            => '创建账号',
    'have_account'                => '已经有账号了？登录',
    'recover_heading'             => '重置密码',
    'recover_intro'               => '请输入你的电子邮箱地址，我们会发送一个设置新密码的链接。',
    'recover_submit'              => '发送链接',
    'recover_sent'                => '如果该地址已注册账号，链接已在发送途中。',
    'reset_heading'               => '设置新密码',
    'reset_submit'                => '保存密码',
    'reset_done'                  => '密码已更改，现在可以用它登录了。',
    'registered'                  => '账号已创建。',
    'back_to_sign_in'             => '返回登录',
    'close'                       => '关闭',

    // Adding and proving an e-mail address.
    'add_email'                   => '添加电子邮箱地址',
    'change_email'                => '更改电子邮箱地址',
    'verify_submit'               => '发送确认链接',
    'verify_sent'                 => '请查看收件箱，点击链接确认该地址。',
    'verify_pending'              => '正在等待你确认 :email。在确认之前，该地址不会绑定到此账号。',
    'verify_cancel'               => '取消',
    'verify_cancelled'            => '电子邮箱地址的更改已取消。',
    'verify_done'                 => ':email 已在你的账号上确认。',

    // Verification e-mail.
    'verify_mail_subject'         => '请确认你的电子邮箱地址',
    'verify_mail_intro'           => '请确认此地址，以便在你的账号中使用。',
    'verify_mail_link'            => '确认此地址',
    'verify_mail_ignore'          => '如果这不是你发起的，可以忽略此邮件——该地址不会被使用。',

    // Reset e-mail.
    'reset_mail_subject'          => '重置你的密码',
    'reset_mail_intro'            => '有人请求重置你账号的密码。',
    'reset_mail_link'             => '设置新密码',
    'reset_mail_ignore'           => '如果这不是你本人操作，可以忽略此邮件——没有任何改动。',

    // Errors shown to the visitor.
    'error_generic'               => '社交登录暂时不可用，请重试。',
    'error_oauth'                 => '服务商拒绝了此请求，请重试。',
    'error_no_identifier'         => '服务商没有返回有效的用户标识。',
    'error_no_user'               => '没有账号与此身份关联。请用密码登录，然后在个人资料中关联该账号。',
    'error_create_user'           => '无法创建账号，请联系管理员。',
    'error_login'                 => '登录失败。请重试或改用密码。',
    'error_expired'               => '本次登录尝试已过期，请重试。',
    'error_identity_taken'        => '该 :provider 账号已关联到其他用户。',
    'error_link_session'          => '关联账号时你的会话发生了变化。请重新登录后再试。',
    'error_link_signed_out'       => '请先登录，再关联其他账号。',
    'error_not_linked'            => '该服务商未与你的账号关联。',
    'error_unlink_last'           => '这是你唯一的登录方式。请先设置密码或关联其他服务商，再解除此关联。',

    // Credential form errors.
    'error_credentials_required'  => '请输入电子邮箱和密码。',
    'error_credentials_invalid'   => '登录信息不正确。',
    'error_registration_disabled' => '本站未开放注册。',
    'error_email_invalid'         => '请输入有效的电子邮箱地址。',
    'error_email_taken'           => '该电子邮箱地址已注册账号。',
    'error_password_short'        => '请设置至少 :min 个字符的密码。',
    'error_password_mismatch'     => '两次输入的密码不一致。',
    'error_reset_token'           => '该重置链接无效或已过期，请重新申请。',
    'error_verify_token'          => '该确认链接无效或已过期，请重新申请。',
    'error_email_already_yours'   => '该地址已在你的账号中。',
    'error_email_send'            => '确认邮件发送失败，请稍后再试。',
];
