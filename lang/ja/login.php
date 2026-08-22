<?php

return [
    // Login page.
    'or_sign_in_with'             => 'または次のアカウントでログイン',
    'sign_in_with'                => ':provider でログイン',

    // Identities widget.
    'linked_accounts'             => '連携済みアカウント',
    'linked_accounts_intro'       => 'いずれのアカウントでもこのアカウントにログインできます。',
    'connect'                     => ':provider を連携',
    'disconnect'                  => '連携を解除',
    'connect_more'                => 'ログイン方法をもう一つ追加',
    'no_linked_accounts'          => 'まだ連携されたアカウントはありません。',
    'linked_on'                   => '連携日 :date',
    'last_used'                   => '最終利用 :date',
    'never_used'                  => 'ログインに使用されたことはありません',
    'linked'                      => ':provider をアカウントに連携しました。',
    'unlinked'                    => ':provider の連携を解除しました。',
    'confirm_unlink'              => ':provider の連携をこのアカウントから解除しますか？',
    'placeholder_email_notice'    => 'このアカウントには実在のメールアドレスがありません。連携アカウントを失ってもアクセスを回復できるよう、アドレスを追加してください。',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'このアカウントにはメールアドレスがありません。マネージャー権限を与える前に実在のアドレスを追加してください。さもないとアクセスを回復する手段がありません。',
    'promote_placeholder_email'   => 'このアカウントはメールアドレスを返さないプロバイダー経由で登録されたため、アドレスはメールを受け取れない仮のものです。パスワードの再設定はできません。マネージャー権限を与える前に、所有者に実在のアドレスを追加してもらってください。',

    // E-mail and password forms.
    'sign_in'                     => 'ログイン',
    'sign_in_heading'             => 'ログイン',
    'email'                       => 'メールアドレス',
    'username'                    => 'ユーザー名',
    'login_field'                 => 'メールアドレスまたはユーザー名',
    'password'                    => 'パスワード',
    'password_confirm'            => 'パスワード（確認）',
    'full_name'                   => '氏名',
    'remember_me'                 => 'ログイン状態を保持する',
    'forgot_password'             => 'パスワードをお忘れですか？',
    'register'                    => 'アカウントを作成',
    'register_heading'            => 'アカウントを作成',
    'have_account'                => 'すでにアカウントをお持ちですか？ログイン',
    'recover_heading'             => 'パスワードの再設定',
    'recover_intro'               => 'メールアドレスを入力してください。新しいパスワードを設定するリンクをお送りします。',
    'recover_submit'              => 'リンクを送信',
    'recover_sent'                => 'そのアドレスのアカウントがあれば、リンクを送信しました。',
    'reset_heading'               => '新しいパスワードを設定',
    'reset_submit'                => 'パスワードを設定',
    'reset_done'                  => 'パスワードを変更しました。新しいパスワードでログインできます。',
    'registered'                  => 'アカウントを作成しました。',
    'back_to_sign_in'             => 'ログインに戻る',
    'close'                       => '閉じる',

    // Adding and proving an e-mail address.
    'add_email'                   => 'メールアドレスを追加',
    'change_email'                => 'メールアドレスを変更',
    'verify_submit'               => '確認リンクを送信',
    'verify_sent'                 => '受信トレイを確認し、リンクからアドレスを確認してください。',
    'verify_pending'              => ':email の確認を待っています。確認するまで、このアカウントには登録されません。',
    'verify_cancel'               => 'キャンセル',
    'verify_cancelled'            => 'メールアドレスの変更をキャンセルしました。',
    'verify_done'                 => ':email がアカウントで確認されました。',

    // Verification e-mail.
    'verify_mail_subject'         => 'メールアドレスをご確認ください',
    'verify_mail_intro'           => 'このアドレスをアカウントで使用できるよう、確認をお願いします。',
    'verify_mail_link'            => 'このアドレスを確認する',
    'verify_mail_ignore'          => 'お心当たりがない場合は、このメールを無視してください。アドレスは使用されません。',

    // Reset e-mail.
    'reset_mail_subject'          => 'パスワードの再設定',
    'reset_mail_intro'            => 'アカウントのパスワード再設定がリクエストされました。',
    'reset_mail_link'             => '新しいパスワードを設定する',
    'reset_mail_ignore'           => 'お心当たりがない場合は、このメールを無視してください。何も変更されていません。',

    // Errors shown to the visitor.
    'error_generic'               => 'ソーシャルログインは一時的に利用できません。もう一度お試しください。',
    'error_oauth'                 => 'プロバイダーがリクエストを拒否しました。もう一度お試しください。',
    'error_no_identifier'         => 'プロバイダーから有効なユーザー識別子が返されませんでした。',
    'error_no_user'               => 'このIDに連携されたアカウントはありません。パスワードでログインし、プロフィールから連携してください。',
    'error_create_user'           => 'アカウントを作成できませんでした。管理者にお問い合わせください。',
    'error_login'                 => 'ログインに失敗しました。もう一度お試しになるか、パスワードをご利用ください。',
    'error_expired'               => 'このログイン試行は期限切れです。もう一度お試しください。',
    'error_identity_taken'        => 'その :provider アカウントは別のユーザーに連携されています。',
    'error_link_session'          => '連携中にセッションが変わりました。ログインし直してもう一度お試しください。',
    'error_link_signed_out'       => '別のアカウントを連携する前にログインしてください。',
    'error_not_linked'            => 'そのプロバイダーはアカウントに連携されていません。',
    'error_unlink_last'           => 'これが唯一のログイン方法です。解除する前にパスワードを設定するか、別のプロバイダーを連携してください。',

    // Credential form errors.
    'error_credentials_required'  => 'メールアドレスとパスワードを入力してください。',
    'error_credentials_invalid'   => 'ログイン情報が正しくありません。',
    'error_registration_disabled' => 'このサイトでは登録を受け付けていません。',
    'error_email_invalid'         => '有効なメールアドレスを入力してください。',
    'error_email_taken'           => 'そのメールアドレスのアカウントはすでに存在します。',
    'error_password_short'        => ':min 文字以上のパスワードを設定してください。',
    'error_password_mismatch'     => '2つのパスワードが一致しません。',
    'error_reset_token'           => 'その再設定リンクは無効か期限切れです。新しいリンクをリクエストしてください。',
    'error_verify_token'          => 'その確認リンクは無効か期限切れです。新しいリンクをリクエストしてください。',
    'error_email_already_yours'   => 'そのアドレスはすでにアカウントに登録されています。',
    'error_email_send'            => '確認メールを送信できませんでした。しばらくしてからお試しください。',
];
