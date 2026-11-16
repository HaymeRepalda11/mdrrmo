<?php
require __DIR__ . '/_bootstrap.php';
require_method('POST');
guard_write();
$b = body();

/* honeypot: real visitors never fill this hidden field */
if (!empty($b['website'])) ok();

$name    = clean(isset($b['name']) ? $b['name'] : '', 120);
$email   = strtolower(clean(isset($b['email']) ? $b['email'] : '', 190));
$phone   = clean(isset($b['phone']) ? $b['phone'] : '', 20);
$topic   = clean(isset($b['topic']) ? $b['topic'] : '', 80);
$message = clean_multiline(isset($b['message']) ? $b['message'] : '', 3000);

if (mb_strlen($name) < 2)      fail('Please enter your name.');
if (!valid_email($email))      fail('Please enter a valid email address.');
if (!valid_phone($phone))      fail('Please enter a valid phone number.');
if ($topic === '')             fail('Please choose a subject.');
if (mb_strlen($message) < 5)   fail('Please write a message.');

$pdo = db();
$ip = client_ip();
$st = $pdo->prepare('SELECT COUNT(*) FROM contact_messages WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
$st->execute([$ip]);
if ((int)$st->fetchColumn() >= 5) fail('You have sent several messages already. Please try again in an hour.', 429);

$me = current_user();
$pdo->prepare('INSERT INTO contact_messages (user_id, name, email, phone, topic, message, ip) VALUES (?,?,?,?,?,?,?)')
    ->execute([$me ? $me['id'] : null, $name, $email, $phone, $topic, $message, $ip]);

if (CONTACT_NOTIFY_TO !== '') {
    send_mail(CONTACT_NOTIFY_TO, "New MDRRMO message: $topic",
        "From: $name <$email>\nPhone: $phone\nTopic: $topic\n\n$message");
}
ok();
