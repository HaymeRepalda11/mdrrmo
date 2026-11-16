<?php
require __DIR__ . '/_bootstrap.php';
require_method('GET');
require_admin();
$pdo = db();
$one = function ($sql) use ($pdo) { return (int)$pdo->query($sql)->fetchColumn(); };

ok([
    'users'        => $one("SELECT COUNT(*) FROM users"),
    'admins'       => $one("SELECT COUNT(*) FROM users WHERE role = 'admin'"),
    'newUsers7d'   => $one("SELECT COUNT(*) FROM users WHERE created_at > (NOW() - INTERVAL 7 DAY)"),
    'messages'     => $one("SELECT COUNT(*) FROM contact_messages"),
    'messagesNew'  => $one("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'"),
    'byBarangay'   => $pdo->query("SELECT barangay AS name, COUNT(*) AS total FROM users WHERE barangay <> '' GROUP BY barangay ORDER BY total DESC, barangay LIMIT 14")->fetchAll(),
    'recentUsers'  => $pdo->query("SELECT id, name, email, barangay, created_at FROM users ORDER BY id DESC LIMIT 5")->fetchAll(),
    'recentMessages' => $pdo->query("SELECT id, name, topic, status, created_at FROM contact_messages ORDER BY id DESC LIMIT 5")->fetchAll(),
]);
