<?php

return [
    // Login page.
    'or_sign_in_with'             => 'və ya bununla daxil olun',
    'sign_in_with'                => ':provider ilə daxil ol',

    // Identities widget.
    'linked_accounts'             => 'Bağlanmış hesablar',
    'linked_accounts_intro'       => 'Bunlardan hər hansı biri ilə bu hesaba daxil ola bilərsiniz.',
    'connect'                     => ':provider hesabını bağla',
    'disconnect'                  => 'Bağlantını kəs',
    'connect_more'                => 'Daxil olmaq üçün başqa üsul əlavə edin',
    'no_linked_accounts'          => 'Hələ heç bir hesab bağlanmayıb.',
    'linked_on'                   => 'Bağlanıb :date',
    'last_used'                   => 'Son istifadə :date',
    'never_used'                  => 'Daxil olmaq üçün heç vaxt istifadə edilməyib',
    'linked'                      => ':provider indi hesabınıza bağlandı.',
    'unlinked'                    => ':provider ilə bağlantı kəsildi.',
    'confirm_unlink'              => ':provider bu hesabdan ayrılsın?',
    'placeholder_email_notice'    => 'Bu hesabın həqiqi e-poçt ünvanı yoxdur. Bağlanmış hesablarınızı itirsəniz girişi bərpa edə bilmək üçün bir ünvan əlavə edin.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'Bu hesabın e-poçt ünvanı yoxdur. Ona menecer rolu verməzdən əvvəl həqiqi ünvan əlavə edin, əks halda girişi bərpa etmək mümkün olmayacaq.',
    'promote_placeholder_email'   => 'Bu hesab e-poçt ünvanı qaytarmayan provayder vasitəsilə yaradılıb, ona görə ünvanı şərtidir və məktub qəbul edə bilmir. Parolun bərpası mümkün olmazdı. Ona menecer rolu verməzdən əvvəl sahibindən həqiqi ünvan əlavə etməsini xahiş edin.',

    // E-mail and password forms.
    'sign_in'                     => 'Daxil ol',
    'sign_in_heading'             => 'Daxil ol',
    'email'                       => 'E-poçt',
    'username'                    => 'İstifadəçi adı',
    'login_field'                 => 'E-poçt və ya istifadəçi adı',
    'password'                    => 'Parol',
    'password_confirm'            => 'Parolu təkrarlayın',
    'full_name'                   => 'Tam ad',
    'remember_me'                 => 'Sistemdə qal',
    'forgot_password'             => 'Parolu unutmusunuz?',
    'register'                    => 'Hesab yarat',
    'register_heading'            => 'Hesab yarat',
    'have_account'                => 'Artıq hesabınız var? Daxil olun',
    'recover_heading'             => 'Parolu sıfırlayın',
    'recover_intro'               => 'E-poçt ünvanınızı yazın, yeni parol təyin etmək üçün sizə keçid göndərəcəyik.',
    'recover_submit'              => 'Keçid göndər',
    'recover_sent'                => 'Əgər bu ünvana aid hesab varsa, keçid artıq yoldadır.',
    'reset_heading'               => 'Yeni parol seçin',
    'reset_submit'                => 'Parolu təyin et',
    'reset_done'                  => 'Parolunuz dəyişdirildi. İndi onunla daxil ola bilərsiniz.',
    'registered'                  => 'Hesabınız yaradıldı.',
    'back_to_sign_in'             => 'Girişə qayıt',
    'close'                       => 'Bağla',

    // Adding and proving an e-mail address.
    'add_email'                   => 'E-poçt ünvanı əlavə edin',
    'change_email'                => 'E-poçt ünvanınızı dəyişin',
    'verify_submit'               => 'Təsdiq keçidi göndər',
    'verify_sent'                 => 'Poçtunuzu yoxlayın — ünvanı təsdiqləmək üçün keçidə keçin.',
    'verify_pending'              => ':email ünvanını təsdiqləməyiniz gözlənilir. Təsdiqlənənədək o, bu hesaba bağlı deyil.',
    'verify_cancel'               => 'Ləğv et',
    'verify_cancelled'            => 'E-poçt ünvanının dəyişdirilməsi ləğv edildi.',
    'verify_done'                 => ':email hesabınızda təsdiqləndi.',

    // Verification e-mail.
    'verify_mail_subject'         => 'E-poçt ünvanınızı təsdiqləyin',
    'verify_mail_intro'           => 'Bu ünvanın hesabınız üçün istifadə oluna bilməsi üçün onu təsdiqləyin.',
    'verify_mail_link'            => 'Bu ünvanı təsdiqlə',
    'verify_mail_ignore'          => 'Bunu siz istəməmisinizsə, məktuba məhəl qoymayın — ünvan istifadə edilməyəcək.',

    // Reset e-mail.
    'reset_mail_subject'          => 'Parolunuzu sıfırlayın',
    'reset_mail_intro'            => 'Kimsə hesabınızın parolunun sıfırlanmasını istəyib.',
    'reset_mail_link'             => 'Yeni parol seçin',
    'reset_mail_ignore'           => 'Bu siz deyildinizsə, məktuba məhəl qoymayın — heç nə dəyişməyib.',

    // Errors shown to the visitor.
    'error_generic'               => 'Sosial şəbəkə ilə giriş müvəqqəti əlçatan deyil. Yenidən cəhd edin.',
    'error_oauth'                 => 'Provayder sorğunu rədd etdi. Yenidən cəhd edin.',
    'error_no_identifier'         => 'Provayder etibarlı istifadəçi identifikatoru qaytarmadı.',
    'error_no_user'               => 'Bu kimliyə bağlı hesab yoxdur. Parolunuzla daxil olun və hesabı profilinizdən bağlayın.',
    'error_create_user'           => 'Hesab yaradıla bilmədi. Administratorla əlaqə saxlayın.',
    'error_login'                 => 'Giriş alınmadı. Yenidən cəhd edin və ya parolunuzdan istifadə edin.',
    'error_expired'               => 'Bu giriş cəhdinin vaxtı bitdi. Yenidən cəhd edin.',
    'error_identity_taken'        => 'Bu :provider hesabı artıq başqa istifadəçiyə bağlıdır.',
    'error_link_session'          => 'Hesabı bağlayarkən sessiyanız dəyişdi. Yenidən daxil olub təkrar cəhd edin.',
    'error_link_signed_out'       => 'Başqa hesab bağlamazdan əvvəl daxil olun.',
    'error_not_linked'            => 'Bu provayder hesabınıza bağlı deyil.',
    'error_unlink_last'           => 'Bu, daxil ola biləcəyiniz yeganə üsuldur. Bunu ayırmazdan əvvəl parol təyin edin və ya başqa provayder bağlayın.',

    // Credential form errors.
    'error_credentials_required'  => 'E-poçt və parolunuzu daxil edin.',
    'error_credentials_invalid'   => 'Bu giriş məlumatları düzgün deyil.',
    'error_registration_disabled' => 'Bu saytda qeydiyyat açıq deyil.',
    'error_email_invalid'         => 'Etibarlı e-poçt ünvanı daxil edin.',
    'error_email_taken'           => 'Bu e-poçt ünvanı ilə hesab artıq mövcuddur.',
    'error_password_short'        => 'Ən azı :min simvoldan ibarət parol seçin.',
    'error_password_mismatch'     => 'İki parol uyğun gəlmir.',
    'error_reset_token'           => 'Bu sıfırlama keçidi etibarsızdır və ya vaxtı keçib. Yenisini istəyin.',
    'error_verify_token'          => 'Bu təsdiq keçidi etibarsızdır və ya vaxtı keçib. Yenisini istəyin.',
    'error_email_already_yours'   => 'Bu ünvan artıq hesabınızdadır.',
    'error_email_send'            => 'Təsdiq məktubu göndərilə bilmədi. Sonra yenidən cəhd edin.',
];
