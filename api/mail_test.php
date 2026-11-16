<?php
/* =====================================================================
   Email diagnostic – open http://localhost/mdrrmo/api/mail_test.php
   Works ONLY from the computer running XAMPP (localhost).
   It checks your mail settings step by step, can send a test email and
   shows exactly why Gmail refuses it. DELETE THIS FILE before going public.
   ===================================================================== */
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/_bootstrap.php';
header_remove('Content-Type');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('This page only works from the computer running XAMPP (localhost). Delete api/mail_test.php before going public.');
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* ---------- 1. settings checks ---------- */
$checks = [];
function chk(&$list, $ok, $label, $detail = '', $fix = '') { $list[] = [$ok, $label, $detail, $fix]; }

chk($checks, version_compare(PHP_VERSION, '7.4.0', '>='), 'PHP version', PHP_VERSION, 'Use a newer XAMPP (PHP 7.4 or higher).');
chk($checks, extension_loaded('openssl'), 'PHP openssl extension', extension_loaded('openssl') ? 'enabled' : 'MISSING',
    'Open C:\\xampp\\php\\php.ini, find ";extension=openssl", remove the ";" so it reads "extension=openssl", save, then restart Apache in the XAMPP Control Panel.');
$localFile = is_file(__DIR__ . '/config.local.php');
chk($checks, $localFile, 'api/config.local.php exists', $localFile ? 'yes' : 'NOT FOUND',
    'Copy api\\config.local.example.php to api\\config.local.php (same folder), then put your Gmail App Password in SMTP_PASS.');
$passOk = mail_is_configured();
chk($checks, $passOk, 'SMTP_PASS is filled in', $passOk ? 'yes (' . strlen(SMTP_PASS) . ' characters)' : 'still the placeholder / empty',
    'Put your 16-letter Google App Password in api\\config.local.php as SMTP_PASS.');
if ($passOk) {
    $lenOk = strlen(SMTP_PASS) === 16 && !preg_match('/\s/', SMTP_PASS);
    chk($checks, $lenOk, 'SMTP_PASS looks like an App Password', strlen(SMTP_PASS) . ' characters',
        'A Google App Password is exactly 16 letters with NO spaces. Re-copy it without the spaces. Your normal Gmail password will not work.');
}
chk($checks, strcasecmp(SMTP_USER, MAIL_FROM) === 0, 'MAIL_FROM matches SMTP_USER', MAIL_FROM . ' / ' . SMTP_USER,
    'For Gmail they must be the same address (set both in config.local.php).');
chk($checks, true, 'APP_ENV', APP_ENV);
chk($checks, true, 'SMTP server', SMTP_HOST . ':' . SMTP_PORT);

/* ---------- 2. can this computer reach Gmail? ---------- */
$net = null;
if (extension_loaded('openssl') || true) {
    $errno = 0; $errstr = '';
    $t = microtime(true);
    $fp = @fsockopen(SMTP_HOST, (int)SMTP_PORT, $errno, $errstr, 8);
    $ms = round((microtime(true) - $t) * 1000);
    if ($fp) { fclose($fp); $net = [true, "Connected to " . SMTP_HOST . ":" . SMTP_PORT . " in {$ms} ms"]; }
    else     { $net = [false, "Cannot connect to " . SMTP_HOST . ":" . SMTP_PORT . " ($errstr, code $errno)"]; }
}
chk($checks, $net[0], 'Network path to the mail server', $net[1],
    'Something is blocking the connection: Windows Firewall, antivirus ("email shield"), or your network/ISP blocks port 587. Try another network or a phone hotspot, or switch to port 465 by adding  define(\'SMTP_PORT\', 465); define(\'SMTP_SECURE\', \'ssl\');  to config.local.php.');

/* ---------- 3. optional: send a real test email ---------- */
$sendResult = null; $debugLines = []; $hint = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if ($sent === '' || !hash_equals($_SESSION['csrf'], $sent)) {
        $sendResult = [false, 'Form expired – reload the page and try again.'];
    } else {
        $to = trim(isset($_POST['to']) ? $_POST['to'] : '');
        if (!valid_email($to)) {
            $sendResult = [false, 'Please enter a valid email address.'];
        } elseif (!$passOk) {
            $sendResult = [false, 'SMTP_PASS is not set yet (see the red items above).'];
        } else {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host        = SMTP_HOST;
                $mail->SMTPAuth    = defined('SMTP_AUTH') ? (bool)SMTP_AUTH : true;
                $mail->Username    = SMTP_USER;
                $mail->Password    = SMTP_PASS;
                $secure            = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPSecure  = $secure;
                $mail->SMTPAutoTLS = $secure !== '';
                $mail->Port        = SMTP_PORT;
                $mail->Timeout     = SMTP_TIMEOUT;
                $mail->CharSet     = 'UTF-8';
                $mail->SMTPDebug   = 2;
                $mail->Debugoutput = function ($str, $level) use (&$debugLines) { $debugLines[] = rtrim($str); };
                $mail->setFrom(MAIL_FROM, SMTP_FROM_NAME);
                $mail->addAddress($to);
                $mail->Subject = 'MDRRMO test email';
                $mail->Body    = "If you can read this, your MDRRMO email settings work.\n\nSent " . date('Y-m-d H:i:s') . ".";
                $mail->send();
                $sendResult = [true, 'Sent! Check the inbox of ' . $to . ' (and the Spam folder). Your forgot-password emails will work too.'];
            } catch (\Throwable $ex) {
                $why = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $ex->getMessage();
                $sendResult = [false, $why];
                $all = $why . ' ' . implode(' ', $debugLines);
                if (preg_match('/Could not authenticate|535|Username and Password not accepted|Application-specific password|BadCredentials/i', $all)) {
                    $hint = 'Gmail rejected the login. Check: (1) 2-Step Verification is ON for ' . SMTP_USER . '; (2) you created an APP PASSWORD (not your normal password) at myaccount.google.com → Security → App passwords; (3) it is pasted with no spaces; (4) SMTP_USER is the same Gmail account that created it.';
                } elseif (preg_match('/certificate verify failed|SSL operation failed|error:1416F086|unable to get local issuer/i', $all)) {
                    $hint = 'XAMPP cannot verify Google\'s certificate (very common on Windows). Download https://curl.se/ca/cacert.pem, save it as C:\\xampp\\php\\extras\\ssl\\cacert.pem, then in C:\\xampp\\php\\php.ini set  openssl.cafile="C:\\xampp\\php\\extras\\ssl\\cacert.pem"  (and curl.cainfo the same), restart Apache. Do NOT turn certificate checking off.';
                } elseif (preg_match('/Could not connect|timed out|10060|Connection refused|Network is unreachable|Failed to connect/i', $all)) {
                    $hint = 'This computer cannot reach Gmail on port ' . SMTP_PORT . '. Check Windows Firewall / antivirus email protection, try another network or a phone hotspot, or switch to port 465: add  define(\'SMTP_PORT\', 465); define(\'SMTP_SECURE\', \'ssl\');  to api\\config.local.php.';
                } elseif (preg_match('/openssl|STARTTLS/i', $all) && !extension_loaded('openssl')) {
                    $hint = 'Enable extension=openssl in php.ini and restart Apache.';
                }
            }
        }
    }
}
$csrf = $_SESSION['csrf'];
$allOk = true; foreach ($checks as $c) { if (!$c[0]) $allOk = false; }
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Email diagnostic – MDRRMO</title>
<style>
 body{font:15px/1.5 Arial,Helvetica,sans-serif;max-width:860px;margin:24px auto;padding:0 16px;color:#1f2937}
 h1{color:#c62828;margin-bottom:4px} .muted{color:#6b7280}
 .row{border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;margin:8px 0}
 .ok{border-left:6px solid #16a34a} .bad{border-left:6px solid #dc2626;background:#fef2f2}
 .fix{margin-top:6px;color:#991b1b} code,pre{background:#f3f4f6;border-radius:4px}
 pre{padding:10px;overflow:auto;font-size:12.5px;max-height:340px}
 input[type=email]{padding:8px;width:300px;max-width:100%} button{padding:8px 16px;background:#c62828;color:#fff;border:0;border-radius:6px;cursor:pointer}
 .warn{background:#fffbeb;border:1px solid #f59e0b;border-radius:8px;padding:10px 14px;margin:10px 0}
</style></head><body>
<h1>Email diagnostic</h1>
<p class="muted">Localhost only. <strong>Delete <code>api/mail_test.php</code> before putting the site online.</strong></p>

<h2>1. Settings check</h2>
<?php foreach ($checks as $c): ?>
  <div class="row <?= $c[0] ? 'ok' : 'bad' ?>">
    <strong><?= $c[0] ? '✅' : '❌' ?> <?= e($c[1]) ?></strong>
    <?php if ($c[2] !== ''): ?> — <?= e($c[2]) ?><?php endif; ?>
    <?php if (!$c[0] && $c[3] !== ''): ?><div class="fix">How to fix: <?= e($c[3]) ?></div><?php endif; ?>
  </div>
<?php endforeach; ?>

<h2>2. Send a test email</h2>
<?php if (!$allOk): ?><div class="warn">Fix the red items above first, then try sending.</div><?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <label>Send to: <input type="email" name="to" value="<?= e(isset($_POST['to']) ? $_POST['to'] : SMTP_USER) ?>" required></label>
  <button type="submit">Send test email</button>
</form>

<?php if ($sendResult): ?>
  <div class="row <?= $sendResult[0] ? 'ok' : 'bad' ?>">
    <strong><?= $sendResult[0] ? '✅ ' : '❌ Failed: ' ?></strong><?= e($sendResult[1]) ?>
    <?php if ($hint): ?><div class="fix">How to fix: <?= e($hint) ?></div><?php endif; ?>
  </div>
  <?php if ($debugLines): ?>
    <h3>What the mail server said</h3>
    <pre><?= e(implode("\n", $debugLines)) ?></pre>
    <p class="muted">Your password is hidden in this log. You can copy this and send it to whoever is helping you.</p>
  <?php endif; ?>
<?php endif; ?>
</body></html>
