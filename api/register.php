<?php
require __DIR__ . '/_bootstrap.php';
require_method('POST');
guard_write();
$b = body();

$name     = clean(isset($b['name']) ? $b['name'] : '', 120);
$email    = strtolower(clean(isset($b['email']) ? $b['email'] : '', 190));
$phone    = clean(isset($b['phone']) ? $b['phone'] : '', 20);
$barangay = clean(isset($b['barangay']) ? $b['barangay'] : '', BRGY_MAX);
$password = isset($b['password']) ? $b['password'] : '';

if (mb_strlen($name) < 2)         fail('Please enter your full name.');
if (!valid_email($email))         fail('Please enter a valid email address.');
if (!valid_phone($phone))         fail('Please enter a valid phone number.');
if ($barangay === '')             fail('Please select your barangay.');
if (!valid_password($password))   fail('Password must be at least 8 characters with letters and numbers.');
if (empty($b['terms']))           fail('Please agree to the Terms of Use and Privacy Notice.');

$pdo = db();
$st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$st->execute([$email]);
if ($st->fetch()) fail('An account with this email already exists. Please log in.', 409);

$pdo->prepare('INSERT INTO users (name, email, password_hash, phone, barangay) VALUES (?,?,?,?,?)')
    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $barangay]);
$id = (int)$pdo->lastInsertId();

$st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$id]);
$user = $st->fetch();
start_login($user, true);
ok(['user' => user_public($user), 'csrf' => $_SESSION['csrf']]);
