<?php
/* Shared helpers for every API endpoint (PHP 7.4+). */
require __DIR__ . '/config.php';

/* never print PHP notices into JSON responses – they go to the PHP error log instead */
ini_set('display_errors', '0');
error_reporting(E_ALL);

date_default_timezone_set(TIMEZONE);
mb_internal_encoding('UTF-8');

const BRGY_MAX = 60;

/* ---------------------------------------------------------------- output */
function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function fail($msg, $code = 400, $extra = []) {
    json_out(array_merge(['ok' => false, 'error' => $msg], $extra), $code);
}
function ok($extra = []) {
    json_out(array_merge(['ok' => true], $extra));
}

set_exception_handler(function ($e) {
    error_log('[mdrrmo] ' . $e->getMessage());
    $extra = APP_ENV === 'dev' ? ['detail' => $e->getMessage()] : [];
    fail('Server error. Please try again.', 500, $extra);
});

/* --------------------------------------------------------------- session */
function is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

$__sessDir = __DIR__ . '/_sessions';
if (!is_dir($__sessDir)) { @mkdir($__sessDir, 0700, true); }
if (is_dir($__sessDir) && is_writable($__sessDir)) { session_save_path($__sessDir); }
ini_set('session.gc_maxlifetime', (string)(86400 * (REMEMBER_DAYS + 1)));
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('MDRRMOSESS');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => is_https(),
]);
session_start();

/* Sessions without "Keep me logged in" end after 8 idle hours */
if (!empty($_SESSION['uid']) && empty($_SESSION['remember'])
    && isset($_SESSION['seen']) && time() - $_SESSION['seen'] > 8 * 3600) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['seen'] = time();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }

/* -------------------------------------------------------------- database */
function db() {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec("SET time_zone = '+08:00'");
    } catch (PDOException $e) {
        error_log('[mdrrmo] DB connect: ' . $e->getMessage());
        $extra = APP_ENV === 'dev' ? ['detail' => $e->getMessage()] : [];
        fail('Cannot connect to the database. Make sure MySQL is running in XAMPP and database.sql was imported.', 500, $extra);
    }
    return $pdo;
}

/* ----------------------------------------------------------- request I/O */
function require_method() {
    $allowed = func_get_args();
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        fail('Method not allowed.', 405);
    }
}

function body() {
    static $data = null;
    if ($data !== null) return $data;
    $raw = file_get_contents('php://input');
    $data = [];
    if ($raw !== '' && $raw !== false) {
        $j = json_decode($raw, true);
        if (is_array($j)) $data = $j;
    }
    return $data;
}

/* CSRF + same-origin check for anything that changes data */
function guard_write() {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    if ($origin !== '') {
        $oh = parse_url($origin, PHP_URL_HOST);
        $op = parse_url($origin, PHP_URL_PORT);
        $host = $oh . ($op ? ':' . $op : '');
        if (strcasecmp($host, $_SERVER['HTTP_HOST']) !== 0) fail('Cross-site request blocked.', 403);
    }
    $sent = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
    if ($sent === '' || !hash_equals($_SESSION['csrf'], $sent)) {
        fail('Your session expired. Please refresh the page and try again.', 403);
    }
}

function client_ip() {
    return substr(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0', 0, 45);
}

/* ------------------------------------------------------------ validation */
function clean($v, $max) {
    $v = is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v)) : '';
    return mb_substr($v, 0, $max);
}
function clean_multiline($v, $max) {
    $v = is_string($v) ? trim(str_replace("\r\n", "\n", $v)) : '';
    return mb_substr($v, 0, $max);
}
function valid_email($e) { return (bool)filter_var($e, FILTER_VALIDATE_EMAIL) && mb_strlen($e) <= 190; }
function valid_phone($p) { return $p === '' || (bool)preg_match('/^[0-9+\-\s()]{7,15}$/', $p); }
function valid_password($p) {
    return is_string($p) && strlen($p) >= 8 && strlen($p) <= 200
        && preg_match('/[A-Za-z]/', $p) && preg_match('/\d/', $p);
}
function valid_date_or_empty($d) {
    if ($d === '' || $d === null) return true;
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) return false;
    if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return false;
    return $d <= date('Y-m-d') && (int)$m[1] >= 1900;
}

/* ---------------------------------------------------------------- users */
function user_public($u) {
    return [
        'id'       => (int)$u['id'],
        'name'     => $u['name'],
        'email'    => $u['email'],
        'role'     => $u['role'],
        'phone'    => $u['phone'],
        'barangay' => $u['barangay'],
        'birthday' => $u['birthday'] ?: '',
        'sex'      => $u['sex'],
        'blood'    => $u['blood'],
        'address'  => $u['address'],
        'ecName'   => $u['ec_name'],
        'ecPhone'  => $u['ec_phone'],
        'created'  => $u['created_at'] ? date('c', strtotime($u['created_at'])) : '',
    ];
}

function current_user() {
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $st->execute([(int)$_SESSION['uid']]);
        $row = $st->fetch();
        if ($row) { $cache = $row; }
        else { unset($_SESSION['uid']); }
    }
    return $cache;
}
function require_login() {
    $u = current_user();
    if (!$u) fail('Please log in first.', 401);
    return $u;
}
function require_admin() {
    $u = require_login();
    if ($u['role'] !== 'admin') fail('Administrator access only.', 403);
    return $u;
}
function start_login($user, $remember) {
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    $_SESSION['remember'] = $remember ? 1 : 0;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    if ($remember) {
        setcookie(session_name(), session_id(), [
            'expires'  => time() + 86400 * REMEMBER_DAYS,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => is_https(),
        ]);
    }
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
}

/* A syntactically valid hash used to keep login timing constant */
const DUMMY_HASH = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/../PHPmailer/src/Exception.php';
require_once __DIR__ . '/../PHPmailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPmailer/src/SMTP.php';

/* True when the SMTP app password was never filled in. */
function mail_is_configured() {
    return SMTP_PASS !== '' && strpos(SMTP_PASS, 'PASTE_') !== 0;
}

/* Sends an email through Gmail SMTP (PHPMailer). Returns true/false.
   $text is the plain-text body; $html (optional) is an HTML version.
   Failures are written to the PHP error log, never shown to the visitor. */
function send_mail($to, $subject, $text, $html = null) {
    if (!mail_is_configured()) {
        error_log('[mdrrmo] mail not sent: SMTP_PASS is not set (see api/config.local.example.php)');
        return false;
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = defined('SMTP_AUTH') ? (bool)SMTP_AUTH : true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $secure           = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPSecure = $secure;
        $mail->SMTPAutoTLS = $secure !== '';
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = SMTP_TIMEOUT;
        $mail->CharSet    = 'UTF-8';

        /* In dev only, log the SMTP conversation to the PHP error log (PHPMailer hides the credentials) */
        if (APP_ENV === 'dev') {
            $mail->SMTPDebug   = 2;
            $mail->Debugoutput = function ($str, $level) { error_log('[mdrrmo smtp] ' . trim($str)); };
        }

        $mail->setFrom(MAIL_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        if ($html !== null) {
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = $text;
        } else {
            $mail->Body = $text;
        }
        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('[mdrrmo] mail error: ' . ($mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage()));
        return false;
    }
}
