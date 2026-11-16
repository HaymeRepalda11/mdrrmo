<?php
/* Admin: Contact Us inbox.
   GET  ?status=new|read|resolved&q=&page=1
   POST {action: status|delete, id, status} */
require __DIR__ . '/_bootstrap.php';
require_method('GET', 'POST');
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $q      = clean(isset($_GET['q']) ? $_GET['q'] : '', 100);
    $page   = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
    $per    = 20;

    $where = []; $args = [];
    if (in_array($status, ['new', 'read', 'resolved'], true)) { $where[] = 'status = ?'; $args[] = $status; }
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(name LIKE ? OR email LIKE ? OR topic LIKE ? OR message LIKE ?)';
        array_push($args, $like, $like, $like, $like);
    }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $st = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $w");
    $st->execute($args);
    $total = (int)$st->fetchColumn();
    $st = $pdo->prepare("SELECT id, user_id, name, email, phone, topic, message, status, created_at FROM contact_messages $w ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
    $st->execute($args);
    ok(['messages' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'per' => $per]);
}

guard_write();
$b = body();
$action = isset($b['action']) ? $b['action'] : '';
$id = (int)(isset($b['id']) ? $b['id'] : 0);

if ($action === 'status') {
    $status = isset($b['status']) ? $b['status'] : '';
    if (!in_array($status, ['new', 'read', 'resolved'], true)) fail('Invalid status.');
    $pdo->prepare('UPDATE contact_messages SET status = ? WHERE id = ?')->execute([$status, $id]);
    ok();
}
if ($action === 'delete') {
    $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
    ok();
}
fail('Unknown action.');
