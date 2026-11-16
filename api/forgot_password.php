<?php
/* Step 1 of "Forgot password": create a 6-digit code (valid 10 minutes) and email it.
   The answer is the same whether or not the email exists, so this page can't be
   used to find out who has an account. The only time the visitor hears about a
   problem is when the mail server itself fails (that says nothing about accounts). */
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
        $resetId = (int)$pdo->lastInsertId();

        $name = $user['name'];
        $text = "Hello $name,\n\n"
              . "Your MDRRMO Los Baños password reset code is: $code\n\n"
              . "It expires in 10 minutes. Never share this code with anyone.\n"
              . "If you did not ask for this, you can safely ignore this message.\n\n"
              . "– MDRRMO Los Baños";
        $h = function ($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto;color:#1f2937">'
              . '<h2 style="color:#c62828;margin:0 0 12px">MDRRMO Los Baños</h2>'
              . '<p>Hello ' . $h($name) . ',</p>'
              . '<p>Use this code to reset your password:</p>'
              . '<p style="font-size:32px;font-weight:700;letter-spacing:8px;background:#f3f4f6;padding:14px 18px;border-radius:8px;text-align:center">' . $code . '</p>'
              . '<p>It expires in <strong>10 minutes</strong>. Never share this code with anyone.</p>'
              . '<p style="color:#6b7280;font-size:13px">If you did not ask for this, you can safely ignore this message.</p>'
              . '</div>';

        $sent = send_mail($email, 'MDRRMO Los Baños – your password reset code', $text, $html);

        if (!$sent) {
            if (APP_ENV !== 'dev') {
                /* nothing reached the user, so this code must not stay valid */
                $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$resetId]);
                fail("We couldn't send the email right now. Please try again in a few minutes.", 503);
            }
            /* dev: keep working without a mail server – the code is shown on the page below */
            $resp['mailError'] = true;
        }

        if (APP_ENV === 'dev') $resp['devCode'] = $code;   // dev only
    }
}
ok($resp);
