<?php
/* =====================================================================
   MDRRMO Los Baños – backend settings
   Default XAMPP: user "root", empty password. If you set a MySQL
   password in phpMyAdmin, put it in DB_PASS.
   ===================================================================== */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'mdrrmo_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/* "dev"  = shows the password-reset code on the page and shows error details
   "prod" = emails the code only and hides error details.
   Switch to "prod" before the site goes public. */
define('APP_ENV', 'dev');

define('TIMEZONE', 'Asia/Manila');

/* Used by the forgot-password email and the contact-form notification.
   PHP mail() only works if XAMPP's sendmail is configured (see README). */
define('MAIL_FROM', 'mdrrmolb@gmail.com');   // must match SMTP_USER for Gmail
define('CONTACT_NOTIFY_TO', '');          // e.g. 'mdrrmolb@gmail.com' (leave '' to skip)

/* How long a "Keep me logged in" login lasts, in days */
define('REMEMBER_DAYS', 30);

/* ---------- Gmail SMTP (PHPMailer) ----------
   1) Turn on 2-Step Verification on the Gmail account
   2) Google Account -> Security -> App passwords -> create one (16 letters)
   3) Paste it below WITHOUT spaces. Never use your normal Gmail password. */
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);                          // 587 = STARTTLS
define('SMTP_USER', 'mdrrmolb@gmail.com');         // the Gmail that SENDS the code
define('SMTP_PASS', 'PASTE_16_LETTER_APP_PASSWORD_HERE');
define('SMTP_FROM_NAME', 'MDRRMO Los Baños');
