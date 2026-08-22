<?php

return [
    // Login page.
    'or_sign_in_with'             => 'או התחברו באמצעות',
    'sign_in_with'                => 'התחברות באמצעות :provider',

    // Identities widget.
    'linked_accounts'             => 'חשבונות מקושרים',
    'linked_accounts_intro'       => 'אפשר להשתמש בכל אחד מהם כדי להתחבר לחשבון הזה.',
    'connect'                     => 'קישור :provider',
    'disconnect'                  => 'ניתוק',
    'connect_more'                => 'הוספת דרך נוספת להתחבר',
    'no_linked_accounts'          => 'עדיין לא קושרו חשבונות.',
    'linked_on'                   => 'קושר בתאריך :date',
    'last_used'                   => 'שימוש אחרון :date',
    'never_used'                  => 'מעולם לא שימש להתחברות',
    'linked'                      => ':provider מקושר כעת לחשבון שלך.',
    'unlinked'                    => ':provider נותק.',
    'confirm_unlink'              => 'לנתק את :provider מהחשבון הזה?',
    'placeholder_email_notice'    => 'לחשבון הזה אין כתובת דוא״ל אמיתית. הוסיפו כתובת כדי שתוכלו לשחזר גישה אם תאבדו את החשבונות המקושרים.',

    // Shown to an administrator considering a role change.
    'promote_no_email'            => 'לחשבון הזה אין כתובת דוא״ל. הוסיפו כתובת אמיתית לפני שתעניקו לו תפקיד ניהולי, אחרת לא תהיה דרך לשחזר אליו גישה.',
    'promote_placeholder_email'   => 'החשבון הזה נרשם דרך ספק שאינו מחזיר כתובת דוא״ל, ולכן הכתובת שלו היא כתובת מציין מקום שאינה יכולה לקבל דואר. שחזור סיסמה יהיה בלתי אפשרי. בקשו מבעל החשבון להוסיף כתובת אמיתית לפני שתעניקו לו תפקיד ניהולי.',

    // E-mail and password forms.
    'sign_in'                     => 'התחברות',
    'sign_in_heading'             => 'התחברות',
    'email'                       => 'דוא״ל',
    'username'                    => 'שם משתמש',
    'login_field'                 => 'דוא״ל או שם משתמש',
    'password'                    => 'סיסמה',
    'password_confirm'            => 'חזרו על הסיסמה',
    'full_name'                   => 'שם מלא',
    'remember_me'                 => 'השאירו אותי מחובר',
    'forgot_password'             => 'שכחתם את הסיסמה?',
    'register'                    => 'יצירת חשבון',
    'register_heading'            => 'יצירת חשבון',
    'have_account'                => 'כבר יש לכם חשבון? התחברו',
    'recover_heading'             => 'איפוס הסיסמה',
    'recover_intro'               => 'הזינו את כתובת הדוא״ל שלכם ונשלח קישור לבחירת סיסמה חדשה.',
    'recover_submit'              => 'שליחת קישור',
    'recover_sent'                => 'אם קיים חשבון לכתובת הזו, הקישור כבר בדרך.',
    'reset_heading'               => 'בחרו סיסמה חדשה',
    'reset_submit'                => 'שמירת הסיסמה',
    'reset_done'                  => 'הסיסמה שונתה. אפשר להתחבר איתה עכשיו.',
    'registered'                  => 'החשבון שלכם נוצר.',
    'back_to_sign_in'             => 'חזרה להתחברות',
    'close'                       => 'סגירה',

    // Adding and proving an e-mail address.
    'add_email'                   => 'הוספת כתובת דוא״ל',
    'change_email'                => 'שינוי כתובת הדוא״ל',
    'verify_submit'               => 'שליחת קישור אישור',
    'verify_sent'                 => 'בדקו את תיבת הדואר — לחצו על הקישור כדי לאשר את הכתובת.',
    'verify_pending'              => 'ממתינים לאישור :email. עד שתאשרו, הכתובת אינה משויכת לחשבון.',
    'verify_cancel'               => 'ביטול',
    'verify_cancelled'            => 'שינוי כתובת הדוא״ל בוטל.',
    'verify_done'                 => ':email אושרה בחשבון שלכם.',

    // Verification e-mail.
    'verify_mail_subject'         => 'אשרו את כתובת הדוא״ל שלכם',
    'verify_mail_intro'           => 'אנא אשרו את הכתובת הזו כדי שאפשר יהיה להשתמש בה בחשבון שלכם.',
    'verify_mail_link'            => 'אישור הכתובת',
    'verify_mail_ignore'          => 'אם לא ביקשתם זאת, אפשר להתעלם מההודעה — הכתובת לא תיכנס לשימוש.',

    // Reset e-mail.
    'reset_mail_subject'          => 'איפוס הסיסמה',
    'reset_mail_intro'            => 'מישהו ביקש לאפס את הסיסמה לחשבון שלכם.',
    'reset_mail_link'             => 'בחירת סיסמה חדשה',
    'reset_mail_ignore'           => 'אם זה לא הייתם אתם, אפשר להתעלם מההודעה — דבר לא השתנה.',

    // Errors shown to the visitor.
    'error_generic'               => 'ההתחברות דרך רשתות חברתיות אינה זמינה כרגע. נסו שוב.',
    'error_oauth'                 => 'הספק דחה את הבקשה. נסו שוב.',
    'error_no_identifier'         => 'הספק לא החזיר מזהה משתמש תקין.',
    'error_no_user'               => 'אין חשבון המקושר לזהות הזו. התחברו עם הסיסמה וקשרו את החשבון מהפרופיל שלכם.',
    'error_create_user'           => 'יצירת החשבון נכשלה. פנו למנהל המערכת.',
    'error_login'                 => 'ההתחברות נכשלה. נסו שוב או השתמשו בסיסמה.',
    'error_expired'               => 'ניסיון ההתחברות הזה פג. נסו שוב.',
    'error_identity_taken'        => 'חשבון ה־:provider הזה כבר מקושר למשתמש אחר.',
    'error_link_session'          => 'הפעילות שלכם השתנתה בזמן קישור החשבון. התחברו שוב ונסו מחדש.',
    'error_link_signed_out'       => 'התחברו לפני קישור חשבון נוסף.',
    'error_not_linked'            => 'הספק הזה אינו מקושר לחשבון שלכם.',
    'error_unlink_last'           => 'זו הדרך היחידה שלכם להתחבר. הגדירו סיסמה או קשרו ספק אחר לפני שתנתקו את זה.',

    // Credential form errors.
    'error_credentials_required'  => 'הזינו כתובת דוא״ל וסיסמה.',
    'error_credentials_invalid'   => 'פרטי ההתחברות אינם נכונים.',
    'error_registration_disabled' => 'ההרשמה באתר הזה סגורה.',
    'error_email_invalid'         => 'הזינו כתובת דוא״ל תקינה.',
    'error_email_taken'           => 'כבר קיים חשבון עם כתובת הדוא״ל הזו.',
    'error_password_short'        => 'בחרו סיסמה באורך :min תווים לפחות.',
    'error_password_mismatch'     => 'שתי הסיסמאות אינן זהות.',
    'error_reset_token'           => 'קישור האיפוס אינו תקין או שפג תוקפו. בקשו קישור חדש.',
    'error_verify_token'          => 'קישור האישור אינו תקין או שפג תוקפו. בקשו קישור חדש.',
    'error_email_already_yours'   => 'הכתובת הזו כבר בחשבון שלכם.',
    'error_email_send'            => 'לא ניתן היה לשלוח את הודעת האישור. נסו שוב מאוחר יותר.',
];
