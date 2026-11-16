<?php
require __DIR__ . '/_bootstrap.php';
require_method('POST');
guard_write();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => is_https()]);
}
session_destroy();
ok();
