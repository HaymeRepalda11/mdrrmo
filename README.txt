MDRRMO Los Baños website – PHP + MySQL backend for XAMPP
=========================================================

WHAT'S NEW
  database.sql        MySQL tables (users, password resets, contact messages, go-bag,
                      editable page content + history, login attempts)
  api/                PHP backend (register, login, profile, forgot password, contact form,
                      go-bag, admin tools, saved page edits)
  admin.html          Admin dashboard (accounts, contact inbox, stats, CSV export)
  script.js / scriptedit.js   now talk to the API instead of the browser's localStorage
  (all other pages, styles and images are unchanged)

SETUP (5 minutes)
 1. Open XAMPP Control Panel -> Start BOTH "Apache" and "MySQL".

 2. Copy this whole folder to:   C:\xampp\htdocs\mdrrmo\
    (so that C:\xampp\htdocs\mdrrmo\index.html and C:\xampp\htdocs\mdrrmo\api\ exist)

 3. Create the database:
      - Open http://localhost/phpmyadmin
      - Click "Import" -> "Choose file" -> database.sql -> "Import"
      - You should now see the database "mdrrmo_db" with 7 tables.

 4. Create the administrator (once):
      - Open http://localhost/mdrrmo/api/setup_admin.php
      - Enter the admin name, email (default mdrrmolb@gmail.com) and a NEW password
      - Then DELETE api\setup_admin.php

 5. Open the site through XAMPP (NOT by double-clicking the files):
      http://localhost/mdrrmo/index.html

If your MySQL "root" user has a password, put it in api\config.php (DB_PASS).
If your folder isn't called "mdrrmo" nothing needs to change - all paths are relative.

HOW IT WORKS
  Residents    Register -> log in -> My Profile (details, emergency contact, change password).
               Their Safety-Tips go-bag checklist is saved to their account.
  Contact Us   Messages are saved in the database (table contact_messages) and show up in
               admin.html -> Messages (mark read / resolved, reply by email, delete).
  Admin        Log in with the admin account -> you land on the editable site (homeedit.html).
               The "⚙️ Admin" link in the menu opens the dashboard.
               "Edit Page" changes are now SAVED TO THE DATABASE and go live for every visitor
               (the page is restored from the database each time it loads). "Reset" restores
               the original page. The last 20 versions of each section are kept in the table
               page_content_history.
  Forgot       The user gets a 6-digit code by email that lasts 10 minutes (5 wrong tries max).
  password     While APP_ENV is "dev" (api\config.php) the code is ALSO shown on the page so you
               can test without email. Set APP_ENV to "prod" for the real site: the code is then
               emailed only. See "Sending real emails" below.

SECURITY NOTES
  - Passwords are hashed on the server (bcrypt via password_hash). Nothing sensitive is kept in the browser.
  - Login is limited to 5 wrong tries per email per 15 minutes.
  - Every change request carries a CSRF token; database access uses prepared statements.
  - Only administrators can use the editor, the dashboard and the admin API (checked on the server).
  - Google / Facebook sign-in is NOT active. The old version only simulated it, which would let
    anyone sign in as anyone once real accounts exist. To add it you need a Google OAuth Client ID /
    Facebook App ID and server-side token verification.
  - Before going public: use HTTPS, set APP_ENV to "prod", delete api\setup_admin.php,
    and give MySQL's root user a password (or create a separate MySQL user for this site).

SENDING REAL EMAILS (forgot-password code, contact notifications)
  The site sends mail with the bundled PHPMailer through Gmail SMTP. No XAMPP sendmail setup needed.
    1) Gmail account that SENDS the mail (default mdrrmolb@gmail.com): turn on 2-Step Verification.
    2) Google Account -> Security -> App passwords -> create one (16 letters).
    3) Copy api\config.local.example.php to api\config.local.php and put the app password in
       SMTP_PASS (no spaces). config.local.php is git-ignored so the password never goes to GitHub.
       (Never put your normal Gmail password here.)
    4) In C:\xampp\php\php.ini make sure   extension=openssl   is enabled (no ";" in front), then restart Apache.
    5) Test: Forgot password -> enter a registered email -> the code should arrive within a minute.
       Check Spam if it doesn't.
    6) Before going public add   define('APP_ENV', 'prod');   to api\config.local.php.
  If the email can't be sent, in "prod" the visitor sees "We couldn't send the email right now" and the
  real reason is written to the PHP error log (in "dev" the SMTP conversation is logged too).
  Optional: define('CONTACT_NOTIFY_TO', 'you@gmail.com'); in config.local.php to get an email for each new
  Contact Us message.

TROUBLESHOOTING
  Code email doesn't arrive
        -> Look in the PHP error log first. "SMTP_PASS is not set" = step 3 above; "Could not authenticate"
           = wrong app password or 2-Step Verification is off; "Could not connect"/timeout = openssl not
           enabled, or the network blocks port 587; also check the Spam folder.
  "Can't reach the server" / "did not answer correctly"
        -> Apache/MySQL not running, or the page was opened as a file. Use http://localhost/mdrrmo/
  "Cannot connect to the database"
        -> MySQL not started, database.sql not imported, or wrong DB_PASS in api\config.php.
  Errors are written to C:\xampp\php\logs\php_error_log
  Old edits made in the previous (localStorage) editor: the first time you open an edit page
  that has nothing saved in the database yet, your old browser copy is loaded - press Save to keep it.
