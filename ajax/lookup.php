<?php
/**
 * Xcreate - Lookup Field AJAX Arama Endpoint'i
 * URL: /modules/xcreate/ajax/lookup.php
 *
 * GET parametreleri:
 *   cat_id   - Hangi kategoride aranacak
 *   field_id - Hangi ilave alanın değeri gösterilecek (ör: İş-Emri alanı)
 *   q        - Arama metni (item_title içinde aranır)
 *
 * Dönen JSON:
 *   { success: true, items: [ { id, title, field_value }, ... ] }
 */

// PHP hatalarını kapat — JSON çıktısını bozmasın
@ini_set('display_errors', 0);
@ini_set('display_startup_errors', 0);
error_reporting(0);

// Temiz JSON çıkış fonksiyonu
function xcreate_lookup_exit($data) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// mainfile.php yolunu bul (Xoops kurulum yapısına göre iki olasılık)
$mainfile = '';
$try = array(
    dirname(__FILE__) . '/../../../mainfile.php',
    dirname(__FILE__) . '/../../../../mainfile.php',
);
foreach ($try as $path) {
    if (file_exists($path)) { $mainfile = realpath($path); break; }
}
if (!$mainfile) { xcreate_lookup_exit(array('success' => false, 'error' => 'mainfile_not_found')); }

// XOOPS'u yükle — mainfile'ın HTML çıktısını temizle
ob_start();
include $mainfile;
ob_end_clean();

if (!defined('XOOPS_ROOT_PATH')) {
    xcreate_lookup_exit(array('success' => false, 'error' => 'xoops_not_loaded'));
}

// Sadece giriş yapmış adminler
if (!is_object($xoopsUser) || !$xoopsUser->isAdmin()) {
    xcreate_lookup_exit(array('success' => false, 'error' => 'Yetkisiz erişim'));
}

$db = $GLOBALS['xoopsDB'];

$cat_id   = isset($_GET['cat_id'])   ? intval($_GET['cat_id'])   : 0;
$field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;
$q        = isset($_GET['q'])        ? trim($_GET['q'])          : '';

if ($cat_id <= 0) {
    xcreate_lookup_exit(array('success' => false, 'error' => 'Kategori belirtilmedi'));
}

$items_tbl = $db->prefix('xcreate_items');
$fv_tbl    = $db->prefix('xcreate_field_values');
$q_esc     = $db->escape($q);

if ($field_id > 0) {
    $sql = "
        SELECT
            i.item_id,
            i.item_title,
            COALESCE(fv.value_text, '') AS field_value
        FROM {$items_tbl} AS i
        LEFT JOIN {$fv_tbl} AS fv
            ON fv.value_item_id = i.item_id
           AND fv.value_field_id = {$field_id}
           AND fv.value_index = 0
        WHERE i.item_cat_id = {$cat_id}
    ";
} else {
    $sql = "
        SELECT
            i.item_id,
            i.item_title,
            '' AS field_value
        FROM {$items_tbl} AS i
        WHERE i.item_cat_id = {$cat_id}
    ";
}

if ($q !== '') {
    $sql .= " AND i.item_title LIKE '%{$q_esc}%' ";
}

$sql .= " ORDER BY i.item_title ASC LIMIT 50";

$result = $db->query($sql);

$items = array();
if ($result) {
    while ($row = $db->fetchArray($result)) {
        $items[] = array(
            'id'          => (int)$row['item_id'],
            'title'       => $row['item_title'],
            'field_value' => $row['field_value'],
        );
    }
}

xcreate_lookup_exit(array('success' => true, 'items' => $items));
