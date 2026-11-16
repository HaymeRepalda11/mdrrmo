<?php
require __DIR__ . '/_bootstrap.php';
require_method('POST');
guard_write();
$me = require_login();
$b = body();
$cur = isset($b['current']) && is_string($b['current']) ? $b['current'] : '';
$new = isset($b['password']) ? $b['password'] : '';

if ($cur === '' || !password_verify($cur, $me['password_hash'])) fail('Your current password is incorrect.', 403);
if (!valid_password($new))  fail('New password must be at least 8 characters with letters and numbers.');
if ($new === $cur)          fail('Your new password must be different from your current password.');

db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
    ->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
session_regenerate_id(true);
ok();
