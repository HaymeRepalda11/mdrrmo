<?php
/* GET/POST -> the logged-in user's go-bag checklist (list of ticked item numbers) */
require __DIR__ . '/_bootstrap.php';
require_method('GET', 'POST');
$me = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $st = $pdo->prepare('SELECT items FROM gobag_progress WHERE user_id = ?');
    $st->execute([$me['id']]);
    $row = $st->fetch();
    $items = $row ? json_decode($row['items'], true) : [];
    ok(['items' => is_array($items) ? array_values($items) : []]);
}

guard_write();
$b = body();
$in = isset($b['items']) && is_array($b['items']) ? $b['items'] : [];
$items = [];
foreach ($in as $n) {
    if (is_int($n) && $n >= 0 && $n < 100) $items[$n] = $n;
}
$items = array_values($items);
sort($items);
$pdo->prepare('INSERT INTO gobag_progress (user_id, items) VALUES (?,?) ON DUPLICATE KEY UPDATE items = VALUES(items)')
    ->execute([$me['id'], json_encode($items)]);
ok(['items' => $items]);
