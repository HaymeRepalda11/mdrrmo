<?php
require __DIR__ . '/_bootstrap.php';
require_method('POST');
guard_write();
$b = body();

$email    = strtolower(clean(isset($b['email']) ? $b['email'] : '', 190));
$password = isset($b['password']) && is_string($b['password']) ? $b['password'] : '';
$remember = !empty($b['remember']);
$ip       = client_ip();
if (!valid_email($email) || $password === '') fail('Incorrect email or password.', 401);

$pdo = db();

/* lock out after repeated failures (per email and per IP) */
$st = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE) AND email = ?");
$st->execute([$email]);
$byEmail = (int)$st->fetchColumn();
$st = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE) AND ip = ?");
$st->execute([$ip]);
$byIp = (int)$st->fetchColumn();
if ($byEmail >= 5 || $byIp >= 30) {
    fail('Too many failed attempts. Please wait 15 minutes and try again.', 429);
}

$st = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$st->execute([$email]);
$user = $st->fetch();

$good = password_verify($password, $user ? $user['password_hash'] : DUMMY_HASH);
$log = $pdo->prepare('INSERT INTO login_attempts (email, ip, success) VALUES (?,?,?)');

if (!$user) {
    /* email is not registered – still counted as a failed attempt so it stays rate-limited */
    $log->execute([$email, $ip, 0]);
    fail('This email is not yet registered. Please create an account first.', 401, ['code' => 'not_registered']);
}
if (!$good) {
    $log->execute([$email, $ip, 0]);
    fail('Incorrect email or password.', 401);
}
if (!(int)$user['is_active']) {
    fail('This account has been disabled. Please contact the MDRRMO office.', 403);
}

$log->execute([$email, $ip, 1]);
$pdo->prepare('DELETE FROM login_attempts WHERE email = ? AND success = 0')->execute([$email]);
if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
}
/* tidy old attempt rows now and then */
if (random_int(1, 50) === 1) { $pdo->exec("DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 7 DAY)"); }

start_login($user, $remember);
ok(['user' => user_public($user), 'csrf' => $_SESSION['csrf']]);
