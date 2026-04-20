<?php
/**
 * Xcreate — İçerik Dışa Aktarma (Export)
 *
 * Desteklenen formatlar: CSV, JSON
 * Filtreleme: kategori, durum, tarih aralığı
 *
 * Eren Yumak tarafından kodlanmıştır — Aymak
 */

include_once '../../../include/cp_header.php';

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

// Sadece admin erişebilir
if (!$GLOBALS['xoopsUser'] || !$GLOBALS['xoopsUser']->isAdmin()) {
    redirect_header('../../../index.php', 3, 'Yetkisiz erişim.');
}

// Dil dosyaları
$language = $GLOBALS['xoopsConfig']['language'];
foreach (['admin', 'main'] as $lf) {
    $path = XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/{$lf}.php";
    include_once file_exists($path) ? $path : XOOPS_ROOT_PATH . "/modules/xcreate/language/english/{$lf}.php";
}

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';

$xoopsDB         = $GLOBALS['xoopsDB'];
$categoryHandler = new XcreateCategoryHandler($xoopsDB);
$itemHandler     = new XcreateItemHandler($xoopsDB);
$fieldHandler    = new XcreateFieldHandler($xoopsDB);

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : 'form';

// ── EXPORT İŞLEMİ ──────────────────────────────────────────────────────────
if ($op === 'do_export') {
    if (!$GLOBALS['xoopsSecurity']->check()) {
        redirect_header('export.php', 3, 'Güvenlik hatası.');
    }

    $format    = isset($_POST['format'])    ? $_POST['format']    : 'csv';
    $cat_id    = isset($_POST['cat_id'])    ? intval($_POST['cat_id'])    : 0;
    $status    = isset($_POST['status'])    ? $_POST['status']            : 'all';
    $date_from = isset($_POST['date_from']) ? trim($_POST['date_from'])   : '';
    $date_to   = isset($_POST['date_to'])   ? trim($_POST['date_to'])     : '';
    $with_fields = isset($_POST['with_fields']) ? 1 : 0;

    // WHERE koşulları
    $where = "1=1";
    if ($cat_id > 0) {
        $where .= " AND i.item_cat_id = " . intval($cat_id);
    }
    if ($status === 'active') {
        $where .= " AND i.item_status = 1";
    } elseif ($status === 'pending') {
        $where .= " AND i.item_status = 0";
    }
    if (!empty($date_from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
        $where .= " AND i.item_created >= " . strtotime($date_from . ' 00:00:00');
    }
    if (!empty($date_to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
        $where .= " AND i.item_created <= " . strtotime($date_to . ' 23:59:59');
    }

    // İçerikleri çek
    $sql = "SELECT i.*, c.cat_name, c.cat_slug
            FROM "   . $xoopsDB->prefix('xcreate_items')      . " i
            LEFT JOIN " . $xoopsDB->prefix('xcreate_categories') . " c ON c.cat_id = i.item_cat_id
            WHERE {$where}
            ORDER BY i.item_created DESC";
    $res = $xoopsDB->query($sql);

    $rows = [];
    while ($row = $xoopsDB->fetchArray($res)) {
        $rows[] = $row;
    }

    if (empty($rows)) {
        redirect_header('export.php', 3, 'Dışa aktarılacak içerik bulunamadı.');
    }

    // Alan tanımlarını önceden yükle (kategori bazlı cache)
    $field_cache = []; // cat_id => [field_id => field_name]
    if ($with_fields) {
        $sql_f = "SELECT field_id, field_cat_id, field_name, field_label
                  FROM " . $xoopsDB->prefix('xcreate_fields') . "
                  WHERE field_status = 1 ORDER BY field_weight, field_label";
        $res_f = $xoopsDB->query($sql_f);
        while ($fr = $xoopsDB->fetchArray($res_f)) {
            $field_cache[$fr['field_cat_id']][$fr['field_id']] = [
                'name'  => $fr['field_name'],
                'label' => $fr['field_label'],
            ];
        }
    }

    // Alan değerlerini yükle (tek sorguda tüm item'lar için)
    $item_ids = array_column($rows, 'item_id');
    $fv_map   = []; // item_id => [field_id => [values]]
    if ($with_fields && !empty($item_ids)) {
        $ids_safe = implode(',', array_map('intval', $item_ids));
        $sql_fv = "SELECT value_item_id, value_field_id, value_text, value_file, value_index
                   FROM " . $xoopsDB->prefix('xcreate_field_values') . "
                   WHERE value_item_id IN ({$ids_safe})
                   ORDER BY value_item_id, value_field_id, value_index";
        $res_fv = $xoopsDB->query($sql_fv);
        while ($fvr = $xoopsDB->fetchArray($res_fv)) {
            $iid = $fvr['value_item_id'];
            $fid = $fvr['value_field_id'];
            $val = !empty($fvr['value_file']) ? $fvr['value_file'] : $fvr['value_text'];
            $fv_map[$iid][$fid][] = $val;
        }
    }

    // Veri setini oluştur
    $export_rows = [];
    foreach ($rows as $row) {
        $record = [
            'item_id'          => $row['item_id'],
            'item_title'       => $row['item_title'],
            'item_slug'        => $row['item_slug'],
            'item_description' => strip_tags($row['item_description']),
            'item_status'      => $row['item_status'] ? 'aktif' : 'beklemede',
            'item_hits'        => $row['item_hits'],
            'item_uid'         => $row['item_uid'],
            'item_created'     => date('Y-m-d H:i:s', $row['item_created']),
            'item_updated'     => date('Y-m-d H:i:s', $row['item_updated']),
            'cat_name'         => $row['cat_name'],
            'cat_slug'         => $row['cat_slug'],
        ];

        // Özel alan değerlerini ekle
        if ($with_fields) {
            $cat_id_row = $row['item_cat_id'];
            $fields_for_cat = isset($field_cache[$cat_id_row]) ? $field_cache[$cat_id_row] : [];
            foreach ($fields_for_cat as $fid => $finfo) {
                $vals = isset($fv_map[$row['item_id']][$fid]) ? $fv_map[$row['item_id']][$fid] : [];
                $record['field_' . $finfo['name']] = implode(' | ', $vals);
            }
        }

        $export_rows[] = $record;
    }

    $filename = 'xcreate_export_' . date('Ymd_His');

    // ── CSV ──────────────────────────────────────────────────────────────────
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM — Excel için
        fwrite($out, "\xEF\xBB\xBF");

        // Başlık satırı
        fputcsv($out, array_keys($export_rows[0]), ';');

        // Veri satırları
        foreach ($export_rows as $r) {
            fputcsv($out, array_values($r), ';');
        }
        fclose($out);
        exit();
    }

    // ── JSON ─────────────────────────────────────────────────────────────────
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = [
            'exported_at' => date('Y-m-d H:i:s'),
            'total'       => count($export_rows),
            'module'      => 'xcreate',
            'version'     => '1.6',
            'items'       => $export_rows,
        ];

        echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    redirect_header('export.php', 3, 'Geçersiz format.');
}

// ── FORM GÖSTER ────────────────────────────────────────────────────────────
xoops_cp_header();
$adminObject = \Xmf\Module\Admin::getInstance();
$adminObject->displayNavigation('export.php');

// Kategori listesi
$categories    = $categoryHandler->getTree();

// Item sayıları (kategori bazlı)
$count_sql = "SELECT item_cat_id, COUNT(*) AS cnt FROM " . $xoopsDB->prefix('xcreate_items') . " GROUP BY item_cat_id";
$count_res = $xoopsDB->query($count_sql);
$cat_counts = [];
while ($cr = $xoopsDB->fetchArray($count_res)) {
    $cat_counts[$cr['item_cat_id']] = $cr['cnt'];
}
$total_items = array_sum($cat_counts);

echo '<style>
.xio-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:24px; margin-bottom:20px; }
.xio-title { font-size:16px; font-weight:600; color:#111827; margin:0 0 16px; display:flex; align-items:center; gap:8px; }
.xio-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
.xio-group { display:flex; flex-direction:column; gap:6px; }
.xio-label { font-size:12px; font-weight:500; color:#6b7280; }
.xio-select, .xio-input { padding:9px 12px; border:1.5px solid #d1d5db; border-radius:7px; font-size:13px; background:#fff; outline:none; width:100%; box-sizing:border-box; }
.xio-select:focus, .xio-input:focus { border-color:#6366f1; }
.xio-format-row { display:flex; gap:12px; flex-wrap:wrap; }
.xio-format-btn { flex:1; min-width:140px; border:2px solid #e5e7eb; border-radius:10px; padding:16px; cursor:pointer; text-align:center; background:#fff; transition:all .15s; }
.xio-format-btn:hover { border-color:#6366f1; background:#f5f3ff; }
.xio-format-btn input[type=radio] { display:none; }
.xio-format-btn.selected { border-color:#6366f1; background:#f5f3ff; }
.xio-format-icon { font-size:28px; margin-bottom:6px; display:block; }
.xio-format-name { font-size:14px; font-weight:600; color:#111827; }
.xio-format-desc { font-size:12px; color:#6b7280; margin-top:3px; }
.xio-checkbox-row { display:flex; align-items:center; gap:8px; font-size:13px; color:#374151; cursor:pointer; }
.xio-checkbox-row input { width:15px; height:15px; cursor:pointer; }
.xio-actions { display:flex; gap:12px; align-items:center; margin-top:20px; }
.xio-btn-export { padding:11px 24px; background:#6366f1; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:500; cursor:pointer; }
.xio-btn-export:hover { background:#4f46e5; }
.xio-btn-cancel { padding:11px 18px; background:#fff; color:#374151; border:1.5px solid #d1d5db; border-radius:8px; font-size:14px; cursor:pointer; text-decoration:none; }
.xio-stat { font-size:13px; color:#6b7280; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:8px 14px; }
.xio-stat strong { color:#111827; }
.xio-info { font-size:12px; color:#9ca3af; margin-top:6px; }
</style>';

echo '<div class="xio-card">';
echo '<div class="xio-title">📤 ' . _AM_XCREATE_EXPORT_TITLE . '</div>';

echo '<p class="xio-stat">' . sprintf(_AM_XCREATE_EXPORT_TOTAL_ITEMS, intval($total_items)) . '</p>';

echo '<form method="post" action="export.php">';
echo $GLOBALS['xoopsSecurity']->getTokenHTML();
echo '<input type="hidden" name="op" value="do_export">';

echo '<div class="xio-grid" style="margin-top:20px;">';

// Kategori
echo '<div class="xio-group">';
echo '<label class="xio-label" for="xio-cat">' . _AM_XCREATE_EXPORT_CATEGORY . '</label>';
echo '<select name="cat_id" id="xio-cat" class="xio-select">';
echo '<option value="0">— ' . _AM_XCREATE_EXPORT_ALL_CATEGORIES . ' —</option>';
foreach ($categories as $cat) {
    $pfx   = str_repeat('— ', (int)$cat->getVar('level'));
    $cid   = $cat->getVar('cat_id');
    $cnt   = isset($cat_counts[$cid]) ? ' (' . $cat_counts[$cid] . _AM_XCREATE_EXPORT_CONTENT_COUNT . ')' : '';
    echo '<option value="' . $cid . '">' . $pfx . htmlspecialchars($cat->getVar('cat_name')) . $cnt . '</option>';
}
echo '</select></div>';

// Durum
echo '<div class="xio-group">';
echo '<label class="xio-label" for="xio-status">' . _AM_XCREATE_EXPORT_STATUS . '</label>';
echo '<select name="status" id="xio-status" class="xio-select">';
echo '<option value="all">' . _AM_XCREATE_EXPORT_STATUS_ALL . '</option>';
echo '<option value="active">' . _AM_XCREATE_EXPORT_STATUS_ACTIVE . '</option>';
echo '<option value="pending">' . _AM_XCREATE_EXPORT_STATUS_PENDING . '</option>';
echo '</select></div>';

// Tarih aralığı
echo '<div class="xio-group">';
echo '<label class="xio-label">' . _AM_XCREATE_EXPORT_DATE_FROM . '</label>';
echo '<input type="date" name="date_from" class="xio-input"></div>';

echo '<div class="xio-group">';
echo '<label class="xio-label">' . _AM_XCREATE_EXPORT_DATE_TO . '</label>';
echo '<input type="date" name="date_to" class="xio-input"></div>';

echo '</div>'; // .xio-grid

// Özel alanlar seçeneği
echo '<div style="margin-top:16px;">';
echo '<label class="xio-checkbox-row">';
echo '<input type="checkbox" name="with_fields" value="1" checked>';
echo _AM_XCREATE_EXPORT_INCLUDE_FIELDS;
echo '</label>';
echo '<p class="xio-info">' . _AM_XCREATE_EXPORT_INCLUDE_FIELDS_HELP . '</p>';
echo '</div>';

// Format seçimi
echo '<div style="margin-top:20px;">';
echo '<div class="xio-label" style="margin-bottom:10px;">' . _AM_XCREATE_EXPORT_FORMAT . '</div>';
echo '<div class="xio-format-row" id="xio-format-row">';

echo '<label class="xio-format-btn selected" id="xio-btn-csv" onclick="xioSelectFormat(\'csv\')">';
echo '<input type="radio" name="format" value="csv" checked>';
echo '<span class="xio-format-icon">📄</span>';
echo '<div class="xio-format-name">CSV</div>';
echo '<div class="xio-format-desc">' . _AM_XCREATE_EXPORT_FORMAT_CSV_DESC . '</div>';
echo '</label>';

echo '<label class="xio-format-btn" id="xio-btn-json" onclick="xioSelectFormat(\'json\')">';
echo '<input type="radio" name="format" value="json">';
echo '<span class="xio-format-icon">{ }</span>';
echo '<div class="xio-format-name">JSON</div>';
echo '<div class="xio-format-desc">' . _AM_XCREATE_EXPORT_FORMAT_JSON_DESC . '</div>';
echo '</label>';

echo '</div></div>'; // format

echo '<div class="xio-actions">';
echo '<button type="submit" class="xio-btn-export">⬇ ' . _AM_XCREATE_EXPORT_BUTTON . '</button>';
echo '<a href="items.php" class="xio-btn-cancel">' . _AM_XCREATE_CANCEL . '</a>';
echo '</div>';

echo '</form></div>'; // .xio-card

echo '<script>
function xioSelectFormat(fmt) {
    document.getElementById("xio-btn-csv").classList.toggle("selected", fmt === "csv");
    document.getElementById("xio-btn-json").classList.toggle("selected", fmt === "json");
    document.querySelector("[name=format][value=" + fmt + "]").checked = true;
}
</script>';

xoops_cp_footer();
