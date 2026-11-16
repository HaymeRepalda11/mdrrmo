<?php
/* =====================================================================
   MDRRMO Los Baños – backend settings
   Default XAMPP: user "root", empty password. If you set a MySQL
   password in phpMyAdmin, put it in DB_PASS.

   SECRETS (SMTP app password, DB password): do NOT commit them to GitHub.
   Copy config.local.example.php to config.local.php, put your real values
   there, and leave this file with placeholders. config.local.php is
   git-ignored and, if it exists, is loaded first and wins.
   ===================================================================== */
if (is_file(__DIR__ . '/config.local.php')) { require __DIR__ . '/config.local.php'; }

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'mdrrmo_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

/* "dev"  = ALSO shows the password-reset code on the page and shows error details
   "prod" = emails the code only and hides error details.
   Switch to "prod" before the site goes public. */
if (!defined('APP_ENV')) define('APP_ENV', 'dev');

if (!defined('TIMEZONE')) define('TIMEZONE', 'Asia/Manila');

/* Used by the forgot-password email and the contact-form notification. */
if (!defined('MAIL_FROM'))         define('MAIL_FROM', 'mdrrmolb@gmail.com');   // must match SMTP_USER for Gmail
if (!defined('CONTACT_NOTIFY_TO')) define('CONTACT_NOTIFY_TO', '');          // e.g. 'mdrrmolb@gmail.com' (leave '' to skip)

/* How long a "Keep me logged in" login lasts, in days */
if (!defined('REMEMBER_DAYS')) define('REMEMBER_DAYS', 30);

/* ---------- Gmail SMTP (PHPMailer) ----------
   1) Turn on 2-Step Verification on the Gmail account
   2) Google Account -> Security -> App passwords -> create one (16 letters)
   3) Put it in config.local.php as SMTP_PASS WITHOUT spaces.
      Never use your normal Gmail password. */
if (!defined('SMTP_HOST'))      define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT'))      define('SMTP_PORT', 587);                          // 587 = STARTTLS
if (!defined('SMTP_USER'))      define('SMTP_USER', 'mdrrmolb@gmail.com');         // the Gmail that SENDS the code
if (!defined('SMTP_PASS'))      define('SMTP_PASS', 'PASTE_16_LETTER_APP_PASSWORD_HERE');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'MDRRMO Los Baños');
if (!defined('SMTP_TIMEOUT'))   define('SMTP_TIMEOUT', 12);                        // seconds; stops the page hanging if Gmail is unreachable
