<?php
/* Step 2 of "Forgot password": check the code, set the new password. */
require __DIR__ . '/_bootstrap.php';
require_method('POST');
guard_write();
$b = body();
$email = strtolower(clean(isset($b['email']) ? $b['email'] : '', 190));
$code  = isset($b['code']) && is_string($b['code']) ? trim($b['code']) : '';
$new   = isset($b['password']) ? $b['password'] : '';

if (!valid_email($email) || !preg_match('/^\d{6}$/', $code)) fail('Please enter the 6-digit code.');
if (!valid_password($new)) fail('Password must be at least 8 characters with letters and numbers.');

$pdo = db();
$st = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1');
$st->execute([$email]);
$user = $st->fetch();
$bad = "That code isn't correct or has expired. Please request a new one.";
if (!$user) fail($bad, 400);

$st = $pdo->prepare('SELECT * FROM password_resets WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
$st->execute([$user['id']]);
$r = $st->fetch();
if (!$r) fail($bad, 400);
if ((int)$r['attempts'] >= 5) {
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$r['id']]);
    fail('Too many incorrect attempts. Please request a new code.', 429);
}
if (!password_verify($code, $r['code_hash'])) {
    $pdo->prepare('UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?')->execute([$r['id']]);
    $left = max(0, 4 - (int)$r['attempts']);
    fail("That code isn't correct. $left attempt(s) left.", 400);
}

$pdo->beginTransaction();
$pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
$pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$user['id']]);
$pdo->commit();
ok();
