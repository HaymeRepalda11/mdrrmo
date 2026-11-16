<?php
/* POST -> update the logged-in user's own details (email and role can't be changed here) */
require __DIR__ . '/_bootstrap.php';
require_method('POST');
guard_write();
$me = require_login();
$b = body();

$name     = clean(isset($b['name']) ? $b['name'] : '', 120);
$birthday = clean(isset($b['birthday']) ? $b['birthday'] : '', 10);
$sex      = clean(isset($b['sex']) ? $b['sex'] : '', 20);
$blood    = clean(isset($b['blood']) ? $b['blood'] : '', 5);
$phone    = clean(isset($b['phone']) ? $b['phone'] : '', 20);
$barangay = clean(isset($b['barangay']) ? $b['barangay'] : '', BRGY_MAX);
$address  = clean(isset($b['address']) ? $b['address'] : '', 255);
$ecName   = clean(isset($b['ecName']) ? $b['ecName'] : '', 120);
$ecPhone  = clean(isset($b['ecPhone']) ? $b['ecPhone'] : '', 20);

if (mb_strlen($name) < 2)                      fail('Please enter your full name.');
if (!valid_date_or_empty($birthday))           fail('Please enter a valid birthday.');
if (!in_array($sex, ['', 'Female', 'Male'], true)) fail('Invalid value for sex.');
if (!in_array($blood, ['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) fail('Invalid blood type.');
if (!valid_phone($phone))                      fail('Please enter a valid phone number.');
if ($barangay === '')                          fail('Please select your barangay.');
if (!valid_phone($ecPhone))                    fail('Please enter a valid emergency contact number.');

db()->prepare('UPDATE users SET name=?, birthday=?, sex=?, blood=?, phone=?, barangay=?, address=?, ec_name=?, ec_phone=? WHERE id=?')
    ->execute([$name, $birthday === '' ? null : $birthday, $sex, $blood, $phone, $barangay, $address, $ecName, $ecPhone, $me['id']]);

$st = db()->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$me['id']]);
ok(['user' => user_public($st->fetch())]);
