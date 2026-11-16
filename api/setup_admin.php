<?php
/* ONE-TIME SETUP – creates the first administrator.
   Works only while no admin exists. Delete this file when you're done. */
require __DIR__ . '/_bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');

$pdo = db();
$hasAdmin = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
$msg = ''; $done = false;

if (!$hasAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    $name  = clean(isset($_POST['name']) ? $_POST['name'] : '', 120);
    $email = strtolower(clean(isset($_POST['email']) ? $_POST['email'] : '', 190));
    $pw    = isset($_POST['password']) ? $_POST['password'] : '';
    $pw2   = isset($_POST['password2']) ? $_POST['password2'] : '';

    if (!hash_equals($_SESSION['csrf'], $sent))      $msg = 'Session expired – reload the page.';
    elseif (mb_strlen($name) < 2)                    $msg = 'Please enter a name.';
    elseif (!valid_email($email))                    $msg = 'Please enter a valid email.';
    elseif (!valid_password($pw))                    $msg = 'Password needs 8+ characters with letters and numbers.';
    elseif ($pw !== $pw2)                            $msg = 'Passwords do not match.';
    else {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($row = $st->fetch()) {
            $pdo->prepare("UPDATE users SET name=?, password_hash=?, role='admin', is_active=1 WHERE id=?")->execute([$name, $hash, $row['id']]);
        } else {
            $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?, 'admin')")->execute([$name, $email, $hash]);
        }
        $done = true; $hasAdmin = true;
    }
}
$e = function ($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MDRRMO – Admin setup</title>
<style>
 body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:#f7f8fc;color:#16213e;display:grid;place-items:center;min-height:100vh;margin:0}
 .box{background:#fff;border:1px solid #e2e6f0;border-radius:14px;padding:28px;width:min(420px,92vw);box-shadow:0 10px 30px rgba(11,42,111,.12)}
 h1{margin:0 0 6px;font-size:22px;color:#0b2a6f} p{color:#5b6785;font-size:14px}
 label{display:block;font-weight:600;font-size:13px;margin:12px 0 4px}
 input{width:100%;padding:10px;border:1px solid #cfd6e6;border-radius:8px;font-size:15px;box-sizing:border-box}
 button{margin-top:18px;width:100%;padding:12px;border:0;border-radius:8px;background:#d62828;color:#fff;font-weight:700;font-size:15px;cursor:pointer}
 .err{background:#fde8e8;color:#a81c1c;padding:10px;border-radius:8px;font-size:14px}
 .ok{background:#e6f7ee;color:#157347;padding:10px;border-radius:8px;font-size:14px}
</style></head><body><div class="box">
<h1>MDRRMO admin setup</h1>
<?php if ($done): ?>
  <p class="ok">✅ Administrator account ready. <strong>Now delete <code>api/setup_admin.php</code>.</strong></p>
  <p><a href="../index.html">Go to the login page →</a></p>
<?php elseif ($hasAdmin): ?>
  <p class="ok">Setup is already finished – an administrator exists.</p>
  <p>For safety, delete <code>api/setup_admin.php</code>.</p>
<?php else: ?>
  <p>Create the first administrator. If an account with this email already exists, it becomes the administrator and gets this password.</p>
  <?php if ($msg): ?><p class="err"><?= $e($msg) ?></p><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
    <label>Name</label><input name="name" value="<?= $e(isset($_POST['name']) ? $_POST['name'] : 'MDRRMO Los Baños') ?>" required>
    <label>Email</label><input type="email" name="email" value="<?= $e(isset($_POST['email']) ? $_POST['email'] : 'mdrrmolb@gmail.com') ?>" required>
    <label>Password</label><input type="password" name="password" required minlength="8">
    <label>Confirm password</label><input type="password" name="password2" required minlength="8">
    <button>Create administrator</button>
  </form>
<?php endif; ?>
</div></body></html>
