<?php
/* Editable page content.
   GET  ?page=home                 -> saved regions for that page (public; pages apply them on load)
   POST {action:"save", page, regions:{".topbar":html,"main":html,".site-footer":html}}   (admin)
   POST {action:"reset", page}     -> go back to the page's original HTML                  (admin) */
require __DIR__ . '/_bootstrap.php';
require_method('GET', 'POST');

const PAGES   = ['home', 'about', 'services', 'news', 'contact', 'safety-tips'];
const REGIONS = ['.topbar', 'main', '.site-footer'];
const MAX_REGION_BYTES = 600000;

function sanitize_html($html) {
    $tags = 'script|iframe|object|embed|applet|base|meta|link';
    $html = preg_replace('#<\s*(' . $tags . ')\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
    $html = preg_replace('#<\s*/?\s*(' . $tags . ')\b[^>]*>#is', '', $html);
    $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
    $html = preg_replace('#\b(href|src|action|formaction|xlink:href)\s*=\s*(["\']?)\s*(javascript|vbscript|data\s*:\s*text/html)[^"\'>\s]*\2#i', '$1="#"', $html);
    return $html;
}

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    if (!in_array($page, PAGES, true)) ok(['regions' => new stdClass()]);
    $st = $pdo->prepare('SELECT region, html FROM page_content WHERE page = ?');
    $st->execute([$page]);
    $out = [];
    foreach ($st->fetchAll() as $r) { $out[$r['region']] = $r['html']; }
    header('Cache-Control: no-cache');
    ok(['regions' => $out ? $out : new stdClass()]);
}

guard_write();
$me = require_admin();
$b = body();
$action = isset($b['action']) ? $b['action'] : '';
$page = isset($b['page']) ? $b['page'] : '';
if (!in_array($page, PAGES, true)) fail('Unknown page.');

function archive_current($pdo, $page, $region, $uid) {
    $pdo->prepare('INSERT INTO page_content_history (page, region, html, saved_by)
                   SELECT page, region, html, ? FROM page_content WHERE page = ? AND region = ?')
        ->execute([$uid, $page, $region]);
    $pdo->prepare('DELETE FROM page_content_history WHERE page = ? AND region = ? AND id NOT IN
                   (SELECT id FROM (SELECT id FROM page_content_history WHERE page = ? AND region = ? ORDER BY id DESC LIMIT 20) t)')
        ->execute([$page, $region, $page, $region]);
}

if ($action === 'save') {
    $regions = isset($b['regions']) && is_array($b['regions']) ? $b['regions'] : [];
    $pdo->beginTransaction();
    $saved = 0;
    foreach ($regions as $region => $html) {
        if (!in_array($region, REGIONS, true) || !is_string($html)) continue;
        if (strlen($html) > MAX_REGION_BYTES) { $pdo->rollBack(); fail('That page is too large to save.'); }
        archive_current($pdo, $page, $region, $me['id']);
        $pdo->prepare('INSERT INTO page_content (page, region, html, updated_by) VALUES (?,?,?,?)
                       ON DUPLICATE KEY UPDATE html = VALUES(html), updated_by = VALUES(updated_by)')
            ->execute([$page, $region, sanitize_html($html), $me['id']]);
        $saved++;
    }
    $pdo->commit();
    ok(['saved' => $saved]);
}

if ($action === 'reset') {
    $pdo->beginTransaction();
    foreach (REGIONS as $region) { archive_current($pdo, $page, $region, $me['id']); }
    $pdo->prepare('DELETE FROM page_content WHERE page = ?')->execute([$page]);
    $pdo->commit();
    ok();
}

fail('Unknown action.');
