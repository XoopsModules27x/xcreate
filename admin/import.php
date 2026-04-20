<?php
/**
 * Xcreate — İçerik İçe Aktarma (Import)
 *
 * Desteklenen formatlar: CSV, JSON
 * - CSV: export.php çıktısıyla birebir uyumlu; başlık satırı zorunlu.
 * - JSON: export.php çıktısıyla birebir uyumlu; items[] dizisi okunur.
 *
 * Çakışma stratejisi: slug eşleşirse güncelle, yoksa yeni oluştur.
 *
 * Eren Yumak tarafından kodlanmıştır — Aymak
 */

include_once '../../../include/cp_header.php';

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

if (!$GLOBALS['xoopsUser'] || !$GLOBALS['xoopsUser']->isAdmin()) {
    redirect_header('../../../index.php', 3, 'Yetkisiz erişim.');
}

$language = $GLOBALS['xoopsConfig']['language'];
foreach (['admin', 'main'] as $lf) {
    $path = XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/{$lf}.php";
    include_once file_exists($path) ? $path : XOOPS_ROOT_PATH . "/modules/xcreate/language/english/{$lf}.php";
}

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/slug.php';

$xoopsDB         = $GLOBALS['xoopsDB'];
$categoryHandler = new XcreateCategoryHandler($xoopsDB);
$itemHandler     = new XcreateItemHandler($xoopsDB);
$fieldHandler    = new XcreateFieldHandler($xoopsDB);

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : 'form';

// ── ÖNZLEME (Parse + Göster, kayıt yok) ────────────────────────────────────
if ($op === 'preview' || $op === 'do_import') {
    if (!$GLOBALS['xoopsSecurity']->check()) {
        redirect_header('import.php', 3, 'Güvenlik hatası.');
    }

    // Dosyayı oku (preview'de upload, do_import'da session'dan)
    $parsed_rows = [];
    $parse_errors = [];
    $format = 'csv';

    if ($op === 'preview') {
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            redirect_header('import.php', 3, 'Dosya yükleme hatası. Lütfen tekrar deneyin.');
        }

        $file     = $_FILES['import_file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $max_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($ext, ['csv', 'json'])) {
            redirect_header('import.php', 3, 'Sadece CSV ve JSON dosyaları desteklenir.');
        }
        if ($file['size'] > $max_size) {
            redirect_header('import.php', 3, 'Dosya çok büyük. Maksimum 5 MB.');
        }

        $format  = $ext;
        $content = file_get_contents($file['tmp_name']);

        // BOM temizle
        $content = ltrim($content, "\xEF\xBB\xBF");

        if ($format === 'csv') {
            $parsed_rows = _xcreate_parse_csv($content, $parse_errors);
        } else {
            $parsed_rows = _xcreate_parse_json($content, $parse_errors);
        }

        // Session'a sakla (do_import için)
        if (empty($parse_errors) && !empty($parsed_rows)) {
            $_SESSION['xcreate_import_rows']   = $parsed_rows;
            $_SESSION['xcreate_import_format'] = $format;
        }

    } elseif ($op === 'do_import') {
        if (empty($_SESSION['xcreate_import_rows'])) {
            redirect_header('import.php', 3, _AM_XCREATE_IMPORT_DATA_MISSING);
        }
        $parsed_rows = $_SESSION['xcreate_import_rows'];
        $format      = $_SESSION['xcreate_import_format'];
        unset($_SESSION['xcreate_import_rows'], $_SESSION['xcreate_import_format']);
    }

    // Kategori slug → id haritası
    $cat_slug_map = [];
    $cat_name_map = [];
    foreach ($categoryHandler->getTree() as $cat) {
        $cat_slug_map[$cat->getVar('cat_slug')] = $cat->getVar('cat_id');
        $cat_name_map[strtolower($cat->getVar('cat_name'))] = $cat->getVar('cat_id');
    }

    // Alan adı haritası: field_name → field_id (tüm kategoriler)
    $field_name_map = []; // "field_NAME" => field_id
    $sql_fa = "SELECT field_id, field_name, field_cat_id FROM " . $xoopsDB->prefix('xcreate_fields') . " WHERE field_status = 1";
    $res_fa = $xoopsDB->query($sql_fa);
    while ($far = $xoopsDB->fetchArray($res_fa)) {
        $field_name_map[$far['field_cat_id']]['field_' . $far['field_name']] = $far['field_id'];
    }

    // ── DO_IMPORT: Gerçek kayıt ─────────────────────────────────────────────
    if ($op === 'do_import') {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $uid   = $GLOBALS['xoopsUser']->getVar('uid');

        foreach ($parsed_rows as $idx => $row) {
            $line = $idx + 2; // başlık satırı için +1, 0-index için +1

            // Kategori çözümle
            $cat_id = 0;
            if (!empty($row['cat_slug']) && isset($cat_slug_map[$row['cat_slug']])) {
                $cat_id = $cat_slug_map[$row['cat_slug']];
            } elseif (!empty($row['cat_name']) && isset($cat_name_map[strtolower($row['cat_name'])])) {
                $cat_id = $cat_name_map[strtolower($row['cat_name'])];
            }

            if (!$cat_id) {
                $stats['skipped']++;
                $stats['errors'][] = "Satır {$line}: Kategori bulunamadı (cat_slug='{$row['cat_slug']}', cat_name='{$row['cat_name']}') — atlandı.";
                continue;
            }

            $title = trim($row['item_title'] ?? '');
            if ($title === '') {
                $stats['skipped']++;
                $stats['errors'][] = sprintf(_AM_XCREATE_IMPORT_ROW_TITLE_EMPTY, $line);
                continue;
            }

            // Slug'a göre mevcut item'ı bul
            $slug    = trim($row['item_slug'] ?? '');
            $item    = null;
            $is_new  = true;

            if ($slug !== '') {
                $existing = $itemHandler->getBySlug($slug);
                if ($existing && !$existing->isNew()) {
                    $item   = $existing;
                    $is_new = false;
                }
            }

            if ($is_new) {
                $item = $itemHandler->create();
                $item->setVar('item_created', time());
                $item->setVar('item_uid',     $uid);
            }

            $item->setVar('item_title',       $title);
            $item->setVar('item_cat_id',      $cat_id);
            $item->setVar('item_description', $row['item_description'] ?? '');
            $item->setVar('item_status',      ($row['item_status'] ?? 'aktif') === 'aktif' ? 1 : 0);
            $item->setVar('item_updated',     time());

            // Slug üret/güncelle
            if ($is_new || $slug === '') {
                $new_slug = $itemHandler->generateSlug($title, $is_new ? 0 : $item->getVar('item_id'));
                $item->setVar('item_slug', $new_slug);
            }

            if (!$itemHandler->insert($item)) {
                $stats['errors'][] = "Satır {$line}: DB kayıt hatası — '{$title}'.";
                $stats['skipped']++;
                continue;
            }

            $item_id = $item->getVar('item_id');

            // Özel alan değerlerini kaydet
            $field_vals = [];
            $cat_fields = isset($field_name_map[$cat_id]) ? $field_name_map[$cat_id] : [];
            foreach ($row as $col => $val) {
                if (strpos($col, 'field_') !== 0) continue;
                if (!isset($cat_fields[$col])) continue;
                $fid = $cat_fields[$col];
                // Pipe ile ayrılmış çoklu değerleri dizi yap
                $field_vals[$fid] = array_map('trim', explode(' | ', (string)$val));
            }
            if (!empty($field_vals)) {
                $itemHandler->saveFieldValues($item_id, $field_vals);
            }

            $is_new ? $stats['created']++ : $stats['updated']++;
        }

        // Sonuç göster
        xoops_cp_header();
        $adminObject = \Xmf\Module\Admin::getInstance();
        $adminObject->displayNavigation('import.php');

        $total = $stats['created'] + $stats['updated'] + $stats['skipped'];
        $color = empty($stats['errors']) ? '#166534' : '#92400e';
        $bg    = empty($stats['errors']) ? '#f0fdf4' : '#fffbeb';
        $border= empty($stats['errors']) ? '#bbf7d0' : '#fde68a';

        echo '<div style="background:' . $bg . ';border:1px solid ' . $border . ';border-radius:10px;padding:20px;margin-bottom:20px;">';
        echo '<h3 style="color:' . $color . ';margin:0 0 12px;">' . _AM_XCREATE_IMPORT_COMPLETE . '</h3>';
        echo '<table style="border-collapse:collapse;font-size:14px;">';
        echo '<tr><td style="padding:4px 16px 4px 0;color:#6b7280;">Toplam işlenen:</td><td><strong>' . $total . '</strong></td></tr>';
        echo '<tr><td style="padding:4px 16px 4px 0;color:#6b7280;">Yeni eklendi:</td><td style="color:#166534;"><strong>' . $stats['created'] . '</strong></td></tr>';
        echo '<tr><td style="padding:4px 16px 4px 0;color:#6b7280;">Güncellendi:</td><td style="color:#1d4ed8;"><strong>' . $stats['updated'] . '</strong></td></tr>';
        echo '<tr><td style="padding:4px 16px 4px 0;color:#6b7280;">Atlandı/Hata:</td><td style="color:#dc2626;"><strong>' . $stats['skipped'] . '</strong></td></tr>';
        echo '</table>';

        if (!empty($stats['errors'])) {
            echo '<details style="margin-top:12px;"><summary style="cursor:pointer;font-size:13px;color:#92400e;">' . sprintf(_AM_XCREATE_IMPORT_ERROR_DETAILS, count($stats['errors'])) . '</summary>';
            echo '<ul style="font-size:12px;color:#78350f;margin-top:8px;">';
            foreach ($stats['errors'] as $err) {
                echo '<li>' . htmlspecialchars($err) . '</li>';
            }
            echo '</ul></details>';
        }

        echo '<div style="margin-top:16px;display:flex;gap:10px;">';
        echo '<a href="items.php" style="padding:9px 18px;background:#6366f1;color:#fff;border-radius:7px;text-decoration:none;font-size:13px;">' . _AM_XCREATE_IMPORT_GO_ITEMS . '</a>';
        echo '<a href="import.php" style="padding:9px 18px;background:#fff;border:1.5px solid #d1d5db;color:#374151;border-radius:7px;text-decoration:none;font-size:13px;">' . _AM_XCREATE_IMPORT_NEW . '</a>';
        echo '</div></div>';

        xoops_cp_footer();
        exit();
    }

    // ── PREVIEW: Önizleme tablosu ───────────────────────────────────────────
    xoops_cp_header();
    $adminObject = \Xmf\Module\Admin::getInstance();
    $adminObject->displayNavigation('import.php');

    if (!empty($parse_errors)) {
        echo '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px;margin-bottom:16px;">';
        echo '<strong style="color:#dc2626;">' . _AM_XCREATE_IMPORT_PARSE_ERRORS . '</strong><ul style="margin:8px 0 0;font-size:13px;color:#991b1b;">';
        foreach ($parse_errors as $err) {
            echo '<li>' . htmlspecialchars($err) . '</li>';
        }
        echo '</ul></div>';
    }

    if (empty($parsed_rows)) {
        echo '<p>' . _AM_XCREATE_IMPORT_NO_DATA . '</p>';
        echo '<p><a href="import.php">← ' . _AM_XCREATE_IMPORT_BACK . '</a></p>';
        xoops_cp_footer();
        exit();
    }

    $preview_count = min(10, count($parsed_rows));
    $all_cols = array_keys($parsed_rows[0]);

    echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:20px;margin-bottom:16px;">';
    echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">';
    echo '<div>';
    echo '<strong>' . count($parsed_rows) . ' satır okundu</strong>';
    echo ' &nbsp;|&nbsp; <span style="font-size:13px;color:#6b7280;">Format: ' . strtoupper($format) . '</span>';
    echo ' &nbsp;|&nbsp; <span style="font-size:13px;color:#6b7280;">Önizleme: ilk ' . $preview_count . ' satır</span>';
    echo '</div></div>';

    echo '<div style="overflow-x:auto;">';
    echo '<table style="border-collapse:collapse;font-size:12px;width:100%;">';
    echo '<thead><tr>';
    foreach ($all_cols as $col) {
        echo '<th style="padding:7px 10px;background:#f9fafb;border:1px solid #e5e7eb;text-align:left;white-space:nowrap;font-size:11px;color:#6b7280;">' . htmlspecialchars($col) . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach (array_slice($parsed_rows, 0, $preview_count) as $row) {
        echo '<tr>';
        foreach ($all_cols as $col) {
            $val = $row[$col] ?? '';
            $display = mb_strlen($val) > 60 ? mb_substr($val, 0, 57) . '…' : $val;
            echo '<td style="padding:6px 10px;border:1px solid #e5e7eb;color:#374151;">' . htmlspecialchars($display) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';

    // Onay formu
    echo '<form method="post" action="import.php" style="margin-top:16px;display:flex;gap:10px;align-items:center;">';
    echo $GLOBALS['xoopsSecurity']->getTokenHTML();
    echo '<input type="hidden" name="op" value="do_import">';
    echo '<button type="submit" style="padding:10px 22px;background:#6366f1;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;">✓ ' . sprintf(_AM_XCREATE_IMPORT_CONFIRM_BUTTON, count($parsed_rows)) . '</button>';
    echo '<a href="import.php" style="padding:10px 18px;background:#fff;border:1.5px solid #d1d5db;color:#374151;border-radius:8px;font-size:14px;text-decoration:none;">' . _AM_XCREATE_CANCEL . '</a>';
    echo '</form>';

    echo '</div>'; // card

    xoops_cp_footer();
    exit();
}

// ── FORM GÖSTER ────────────────────────────────────────────────────────────
xoops_cp_header();
$adminObject = \Xmf\Module\Admin::getInstance();
$adminObject->displayNavigation('import.php');

echo '<style>
.xio-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:24px;margin-bottom:20px}
.xio-title{font-size:16px;font-weight:600;color:#111827;margin:0 0 16px}
.xio-drop{border:2px dashed #d1d5db;border-radius:10px;padding:36px 24px;text-align:center;cursor:pointer;transition:all .15s;background:#fafafa}
.xio-drop:hover,.xio-drop.drag-over{border-color:#6366f1;background:#f5f3ff}
.xio-drop-icon{font-size:36px;margin-bottom:10px}
.xio-drop-text{font-size:14px;color:#6b7280}
.xio-drop-text strong{color:#6366f1;cursor:pointer}
.xio-file-input{display:none}
.xio-selected-file{display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:7px;padding:10px 14px;font-size:13px;color:#166534;margin-top:10px;align-items:center;gap:8px}
.xio-note{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 16px;font-size:13px;color:#1e40af;margin-top:16px}
.xio-note h4{margin:0 0 8px;font-size:13px;font-weight:600}
.xio-note ul{margin:0;padding-left:18px}
.xio-note li{margin-bottom:4px}
.xio-actions{display:flex;gap:12px;margin-top:20px;align-items:center}
.xio-btn-upload{padding:11px 24px;background:#6366f1;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer}
.xio-btn-upload:hover{background:#4f46e5}
.xio-btn-cancel{padding:11px 18px;background:#fff;color:#374151;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;cursor:pointer;text-decoration:none}
</style>';

echo '<div class="xio-card">';
echo '<div class="xio-title">📥 ' . _AM_XCREATE_IMPORT_TITLE . '</div>';

echo '<form method="post" action="import.php" enctype="multipart/form-data" id="xio-form">';
echo $GLOBALS['xoopsSecurity']->getTokenHTML();
echo '<input type="hidden" name="op" value="preview">';

// Sürükle-bırak dosya alanı
echo '<div class="xio-drop" id="xio-drop" onclick="document.getElementById(\'xio-file\').click()">';
echo '<div class="xio-drop-icon">📂</div>';
echo '<div class="xio-drop-text">' . _AM_XCREATE_IMPORT_DROP_TEXT . '</div>';
echo '<div style="font-size:12px;color:#9ca3af;margin-top:6px;">' . _AM_XCREATE_IMPORT_DROP_HELP . '</div>';
echo '<input type="file" name="import_file" id="xio-file" class="xio-file-input" accept=".csv,.json" onchange="xioFileSelected(this)">';
echo '</div>';

echo '<div class="xio-selected-file" id="xio-selected">📄 <span id="xio-fname"></span></div>';

// Notlar
echo '<div class="xio-note"><h4>' . _AM_XCREATE_IMPORT_NOTES . '</h4><ul>';
echo '<li>' . _AM_XCREATE_IMPORT_NOTE_CSV . '</li>';
echo '<li>' . _AM_XCREATE_IMPORT_NOTE_JSON . '</li>';
echo '<li>' . _AM_XCREATE_IMPORT_NOTE_CATEGORY . '</li>';
echo '<li>' . _AM_XCREATE_IMPORT_NOTE_UPDATE . '</li>';
echo '<li>' . _AM_XCREATE_IMPORT_NOTE_FIELDS . '</li>';
echo '</ul></div>';

echo '<div class="xio-actions">';
echo '<button type="submit" class="xio-btn-upload" id="xio-submit" disabled>' . _AM_XCREATE_IMPORT_UPLOAD_PREVIEW . '</button>';
echo '<a href="export.php" class="xio-btn-cancel">' . _AM_XCREATE_EXPORT_BUTTON . '</a>';
echo '</div>';

echo '</form></div>';

echo '<script>
document.getElementById("xio-drop").addEventListener("dragover", function(e){
    e.preventDefault(); this.classList.add("drag-over");
});
document.getElementById("xio-drop").addEventListener("dragleave", function(){
    this.classList.remove("drag-over");
});
document.getElementById("xio-drop").addEventListener("drop", function(e){
    e.preventDefault(); this.classList.remove("drag-over");
    var f = e.dataTransfer.files[0];
    if (f) { document.getElementById("xio-file").files = e.dataTransfer.files; xioFileSelected(document.getElementById("xio-file")); }
});
function xioFileSelected(input){
    var sf = document.getElementById("xio-selected");
    var btn = document.getElementById("xio-submit");
    if (input.files && input.files[0]){
        document.getElementById("xio-fname").textContent = input.files[0].name + " (" + (input.files[0].size/1024).toFixed(1) + " KB)";
        sf.style.display = "flex";
        btn.disabled = false;
    }
}
</script>';

xoops_cp_footer();

// ── YARDIMCI FONKSİYONLAR ───────────────────────────────────────────────────

function _xcreate_parse_csv($content, &$errors)
{
    $rows   = [];
    $lines  = explode("\n", str_replace("\r\n", "\n", str_replace("\r", "\n", $content)));
    $header = null;

    foreach ($lines as $i => $line) {
        $line = trim($line);
        if ($line === '') continue;

        // PHP'nin str_getcsv'si ile parse et (noktalı virgül ayırıcı)
        $cols = str_getcsv($line, ';');

        if ($header === null) {
            $header = array_map('trim', $cols);
            // Başlık doğrulaması
            if (!in_array('item_title', $header)) {
                $errors[] = _AM_XCREATE_IMPORT_CSV_MISSING_TITLE;
                return [];
            }
            continue;
        }

        if (count($cols) !== count($header)) {
            $errors[] = "Satır " . ($i + 1) . ": Sütun sayısı uyuşmuyor (" . count($cols) . " / " . count($header) . "). Atlandı.";
            continue;
        }

        $row = array_combine($header, array_map('trim', $cols));
        $rows[] = $row;
    }

    return $rows;
}

function _xcreate_parse_json($content, &$errors)
{
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "JSON parse hatası: " . json_last_error_msg();
        return [];
    }

    // Export çıktısı formatı: {items: [...]}
    if (isset($data['items']) && is_array($data['items'])) {
        return $data['items'];
    }

    // Düz dizi de kabul et: [{...}, ...]
    if (is_array($data) && isset($data[0])) {
        return $data;
    }

    $errors[] = "JSON formatı tanınamadı. 'items' dizisi veya düz dizi bekleniyor.";
    return [];
}
