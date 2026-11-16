<?php
/* GET  -> who is logged in + the CSRF token the page must send with every POST */
require __DIR__ . '/_bootstrap.php';
require_method('GET');
$u = current_user();
ok([
    'user'          => $u ? user_public($u) : null,
    'csrf'          => $_SESSION['csrf'],
    'showResetCode' => APP_ENV === 'dev',
]);
