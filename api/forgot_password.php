<?php
/* Step 1 of "Forgot password": create a 6-digit code (valid 10 minutes).
   The answer is always the same whether or not the email exists, so this
   page can't be used to find out who has an account. */
require __DIR__ . '/_bootstrap.php';
require_method('POST');
guard_write();
$b = body();
$email = strtolower(clean(isset($b['email']) ? $b['email'] : '', 190));
if (!valid_email($email)) fail('Please enter a valid email address.');

$pdo = db();
$resp = ['sent' => true];

$st = $pdo->prepare('SELECT id, name, is_active FROM users WHERE email = ?');
$st->execute([$email]);
$user = $st->fetch();

if ($user && (int)$user['is_active']) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)');
    $st->execute([$user['id']]);
    if ((int)$st->fetchColumn() < 5) {
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$user['id']]);
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pdo->prepare('INSERT INTO password_resets (user_id, code_hash, expires_at) VALUES (?,?, NOW() + INTERVAL 10 MINUTE)')
            ->execute([$user['id'], password_hash($code, PASSWORD_DEFAULT)]);

        send_mail($email, 'MDRRMO Los Baños – password reset code',
            "Hello " . $user['name'] . ",\n\nYour password reset code is: $code\n\n"
          . "It expires in 10 minutes. If you did not ask for this, you can ignore this message.\n\n– MDRRMO Los Baños");

        if (APP_ENV === 'dev') $resp['devCode'] = $code;   // dev only: no email server on XAMPP by default
    }
}
ok($resp);
