<?php
/* Admin: list / edit / disable / delete accounts.
   GET  ?q=&role=&page=1&per=25      (&export=csv to download)
   POST {action: update|set_password|delete, id, ...} */
require __DIR__ . '/_bootstrap.php';
require_method('GET', 'POST');
$me = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $q    = clean(isset($_GET['q']) ? $_GET['q'] : '', 100);
    $role = isset($_GET['role']) ? $_GET['role'] : '';
    $page = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
    $per  = min(100, max(5, (int)(isset($_GET['per']) ? $_GET['per'] : 25)));
    $csv  = isset($_GET['export']) && $_GET['export'] === 'csv';

    $where = []; $args = [];
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(name LIKE ? OR email LIKE ? OR barangay LIKE ? OR phone LIKE ?)';
        array_push($args, $like, $like, $like, $like);
    }
    if ($role === 'admin' || $role === 'user') { $where[] = 'role = ?'; $args[] = $role; }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $cols = 'id, name, email, role, phone, barangay, birthday, sex, blood, address, ec_name, ec_phone, is_active, created_at, last_login_at';

    if ($csv) {
        $st = $pdo->prepare("SELECT $cols FROM users $w ORDER BY id");
        $st->execute($args);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="mdrrmo-users-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");                       // so Excel reads the ñ correctly
        $first = true;
        while ($row = $st->fetch()) {
            if ($first) { fputcsv($out, array_keys($row), ',', '"', '\\'); $first = false; }
            foreach ($row as $k => $v) {                     // block spreadsheet formula injection
                if (is_string($v) && $v !== '' && strpos("=+-@\t\r", $v[0]) !== false) $row[$k] = "'" . $v;
            }
            fputcsv($out, $row, ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    $st = $pdo->prepare("SELECT COUNT(*) FROM users $w");
    $st->execute($args);
    $total = (int)$st->fetchColumn();
    $offset = ($page - 1) * $per;
    $st = $pdo->prepare("SELECT $cols FROM users $w ORDER BY id DESC LIMIT " . (int)$per . " OFFSET " . (int)$offset);
    $st->execute($args);
    ok(['users' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'per' => $per, 'meId' => (int)$me['id']]);
}

guard_write();
$b = body();
$action = isset($b['action']) ? $b['action'] : '';
$id = (int)(isset($b['id']) ? $b['id'] : 0);
$st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$id]);
$target = $st->fetch();
if (!$target) fail('Account not found.', 404);
$isSelf = $id === (int)$me['id'];

function other_active_admins($pdo, $exceptId) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND id <> ?");
    $st->execute([$exceptId]);
    return (int)$st->fetchColumn();
}

if ($action === 'update') {
    $name     = clean(isset($b['name']) ? $b['name'] : '', 120);
    $phone    = clean(isset($b['phone']) ? $b['phone'] : '', 20);
    $barangay = clean(isset($b['barangay']) ? $b['barangay'] : '', BRGY_MAX);
    $address  = clean(isset($b['address']) ? $b['address'] : '', 255);
    $role     = isset($b['role']) ? $b['role'] : $target['role'];
    $active   = isset($b['is_active']) ? (int)(!!$b['is_active']) : (int)$target['is_active'];

    if (mb_strlen($name) < 2)  fail('Name is too short.');
    if (!valid_phone($phone))  fail('Invalid phone number.');
    if (!in_array($role, ['user', 'admin'], true)) fail('Invalid role.');

    $losesAdmin = $target['role'] === 'admin' && ($role !== 'admin' || !$active);
    if ($isSelf && $losesAdmin) fail("You can't remove your own admin access or disable your own account.");
    if ($losesAdmin && other_active_admins($pdo, $id) < 1) fail('At least one active administrator must remain.');

    $pdo->prepare('UPDATE users SET name=?, phone=?, barangay=?, address=?, role=?, is_active=? WHERE id=?')
        ->execute([$name, $phone, $barangay, $address, $role, $active, $id]);
    ok();
}

if ($action === 'set_password') {
    $pw = isset($b['password']) ? $b['password'] : '';
    if (!valid_password($pw)) fail('Password must be at least 8 characters with letters and numbers.');
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
    ok();
}

if ($action === 'delete') {
    if ($isSelf) fail("You can't delete your own account.");
    if ($target['role'] === 'admin' && other_active_admins($pdo, $id) < 1) fail('At least one active administrator must remain.');
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    ok();
}

fail('Unknown action.');
