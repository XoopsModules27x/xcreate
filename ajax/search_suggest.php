<?php
/**
 * Xcreate — Arama Otomatik Tamamlama (Autocomplete)
 *
 * GET ?q=kelime  →  JSON  [{id, title, url, cat_name}, ...]
 * Maksimum 8 öneri döner. Kimlik doğrulama gerekmez (herkese açık içerikler).
 *
 * Eren Yumak tarafından kodlanmıştır — Aymak
 */

// XOOPS output buffering ile çakışmasın
define('XOOPS_NOEDIT', 1);

include '../../mainfile.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$q = isset($_GET['q']) ? trim(strip_tags($_GET['q'])) : '';

// En az 2 karakter
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

$xoopsDB = $GLOBALS['xoopsDB'];
$q_safe  = $xoopsDB->escape($q);
$limit   = 8;

// Başlık araması önce, sonra açıklama araması
$sql = "SELECT i.item_id, i.item_title, i.item_slug,
               c.cat_name, c.cat_slug
        FROM "   . $xoopsDB->prefix('xcreate_items')      . " i
        JOIN "   . $xoopsDB->prefix('xcreate_categories') . " c ON c.cat_id = i.item_cat_id
        WHERE i.item_status = 1
          AND (i.item_title LIKE '%{$q_safe}%' OR i.item_description LIKE '%{$q_safe}%')
        ORDER BY (i.item_title LIKE '%{$q_safe}%') DESC, i.item_hits DESC
        LIMIT " . intval($limit);

$res  = $xoopsDB->query($sql);
$out  = [];

while ($row = $xoopsDB->fetchArray($res)) {
    $url = (!empty($row['cat_slug']) && !empty($row['item_slug']))
        ? XOOPS_URL . '/modules/xcreate/' . $row['cat_slug'] . '/' . $row['item_slug']
        : XOOPS_URL . '/modules/xcreate/item.php?id=' . $row['item_id'];

    $out[] = [
        'id'       => (int)$row['item_id'],
        'title'    => $row['item_title'],
        'cat_name' => $row['cat_name'],
        'url'      => $url,
    ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
exit();
