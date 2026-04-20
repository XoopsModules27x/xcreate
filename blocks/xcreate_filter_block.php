<?php
/**
 * Xcreate Filtreleme Bloğu v2.0
 * Eren Yumak tarafından kodlanmıştır - Aymak
 *
 * options[0] = cat_id        (kategori ID, 0=tümü)
 * options[1] = limit         (sonuç limiti, varsayılan 20)
 * options[2] = filter_fields (virgülle ayrılmış field_id'ler, boş=tümü)
 * options[3] = result_page   (sonuç sayfası URL, boş=index.php)
 * options[4] = sort_field    (item_created|item_title|item_hits)
 * options[5] = sort_order    (ASC|DESC)
 */

if (!defined('XOOPS_ROOT_PATH')) exit();

$blockLanguage = XOOPS_ROOT_PATH . '/modules/xcreate/language/' . $GLOBALS['xoopsConfig']['language'] . '/blocks.php';
include_once file_exists($blockLanguage) ? $blockLanguage : XOOPS_ROOT_PATH . '/modules/xcreate/language/english/blocks.php';

// ── Distinct alan değerlerini getir ──────────────────────────
function _xcf2_field_values($field_id, $cat_id = 0)
{
    global $xoopsDB;
    $field_id = intval($field_id);
    $sql = "SELECT DISTINCT v.value_text
            FROM {$xoopsDB->prefix('xcreate_field_values')} v
            INNER JOIN {$xoopsDB->prefix('xcreate_items')} i ON i.item_id = v.value_item_id
            WHERE v.value_field_id = {$field_id}
              AND v.value_text != '' AND v.value_text IS NOT NULL
              AND i.item_status = 1";
    if ($cat_id > 0) $sql .= " AND i.item_cat_id = " . intval($cat_id);
    $sql .= " ORDER BY v.value_text ASC";
    $res = $xoopsDB->query($sql);
    $out = [];
    while ($row = $xoopsDB->fetchArray($res)) {
        foreach (explode(',', $row['value_text']) as $p) {
            $p = trim($p);
            if ($p !== '') $out[$p] = $p;
        }
    }
    return $out;
}

// ── Number alanı min/max ─────────────────────────────────────
function _xcf2_num_range($field_id, $cat_id = 0)
{
    global $xoopsDB;
    $field_id = intval($field_id);
    $sql = "SELECT MIN(CAST(v.value_text AS DECIMAL(20,4))) AS vmin,
                   MAX(CAST(v.value_text AS DECIMAL(20,4))) AS vmax
            FROM {$xoopsDB->prefix('xcreate_field_values')} v
            INNER JOIN {$xoopsDB->prefix('xcreate_items')} i ON i.item_id = v.value_item_id
            WHERE v.value_field_id = {$field_id}
              AND v.value_text != '' AND i.item_status = 1";
    if ($cat_id > 0) $sql .= " AND i.item_cat_id = " . intval($cat_id);
    $res = $xoopsDB->query($sql);
    $row = $xoopsDB->fetchArray($res);
    return [
        'min' => isset($row['vmin']) ? floatval($row['vmin']) : 0,
        'max' => isset($row['vmax']) ? floatval($row['vmax']) : 100,
    ];
}

// ── Filtrelenmiş item sayısını getir (AJAX preview için) ─────
function _xcf2_count_items($cat_id, $filters, $number_ranges)
{
    global $xoopsDB;
    $where = "i.item_status = 1";
    if ($cat_id > 0) $where .= " AND i.item_cat_id = " . intval($cat_id);

    foreach ($filters as $fid => $val) {
        $fid = intval($fid);
        $val = $xoopsDB->escape(trim($val));
        $where .= " AND EXISTS (
            SELECT 1 FROM {$xoopsDB->prefix('xcreate_field_values')} fv
            WHERE fv.value_item_id = i.item_id AND fv.value_field_id = {$fid}
              AND (fv.value_text = '{$val}' OR FIND_IN_SET('{$val}', fv.value_text))
        )";
    }
    foreach ($number_ranges as $fid => $range) {
        $fid = intval($fid);
        $min = floatval($range['min']); $max = floatval($range['max']);
        $where .= " AND EXISTS (
            SELECT 1 FROM {$xoopsDB->prefix('xcreate_field_values')} fv
            WHERE fv.value_item_id = i.item_id AND fv.value_field_id = {$fid}
              AND CAST(fv.value_text AS DECIMAL(20,4)) BETWEEN {$min} AND {$max}
        )";
    }

    $sql = "SELECT COUNT(*) AS cnt FROM {$xoopsDB->prefix('xcreate_items')} i WHERE {$where}";
    $res = $xoopsDB->query($sql);
    $row = $xoopsDB->fetchArray($res);
    return intval($row['cnt']);
}

// ── AJAX handler: filtre değişince sonuç sayısını döndür ─────
function _xcf2_ajax_count()
{
    global $xoopsDB;
    if (!defined('XOOPS_ROOT_PATH')) exit();

    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';

    $cat_id       = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
    $filters_raw  = isset($_POST['filters']) && is_array($_POST['filters']) ? $_POST['filters'] : [];
    $ranges_raw   = isset($_POST['ranges'])  && is_array($_POST['ranges'])  ? $_POST['ranges']  : [];

    $filters = [];
    foreach ($filters_raw as $fid => $val) {
        $fid = intval($fid);
        $val = trim(strip_tags($val));
        if ($val !== '') $filters[$fid] = $val;
    }

    $ranges = [];
    foreach ($ranges_raw as $fid => $r) {
        $fid = intval($fid);
        if (isset($r['min']) || isset($r['max'])) {
            $ranges[$fid] = ['min' => floatval($r['min']), 'max' => floatval($r['max'])];
        }
    }

    $count = _xcf2_count_items($cat_id, $filters, $ranges);
    header('Content-Type: application/json');
    echo json_encode(['count' => $count]);
    exit();
}

// AJAX isteği mi?
if (defined('XOOPS_ROOT_PATH') && isset($_POST['xcf2_ajax']) && $_POST['xcf2_ajax'] === '1') {
    _xcf2_ajax_count();
}

// ── SHOW ─────────────────────────────────────────────────────
function b_xcreate_filter_show($options)
{
    global $xoopsDB;

    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';

    $cat_id        = isset($options[0]) ? intval($options[0])   : 0;
    $limit         = isset($options[1]) ? max(1, intval($options[1])) : 20;
    $filter_fields = isset($options[2]) ? trim($options[2])     : '';
    $result_page   = isset($options[3]) && $options[3] !== ''
                        ? trim($options[3])
                        : XOOPS_URL . '/modules/xcreate/index.php';
    $sort_field    = isset($options[4]) ? trim($options[4])     : 'item_created';
    $sort_order    = isset($options[5]) ? trim($options[5])     : 'DESC';

    $fieldHandler    = new XcreateFieldHandler($xoopsDB);
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);

    // Hangi alanlar?
    if ($filter_fields !== '') {
        $fids = array_filter(array_map('intval', explode(',', $filter_fields)));
    } else {
        $fids = [];
        if ($cat_id > 0) {
            foreach ($fieldHandler->getFieldsByCategory($cat_id) as $f) {
                $fids[] = (int)$f->getVar('field_id');
            }
        } else {
            // Tüm kategorilerdeki alanlar
            $sql = "SELECT field_id FROM {$xoopsDB->prefix('xcreate_fields')} WHERE field_status=1 ORDER BY field_weight ASC";
            $res = $xoopsDB->query($sql);
            while ($row = $xoopsDB->fetchArray($res)) $fids[] = $row['field_id'];
        }
    }

    // Alan verilerini hazırla
    $filter_field_data = [];
    foreach ($fids as $fid) {
        $field = $fieldHandler->get($fid);
        if (!$field || !$field->getVar('field_status')) continue;

        $ftype  = $field->getVar('field_type');
        $entry  = [
            'id'    => $fid,
            'name'  => $field->getVar('field_name'),
            'label' => $field->getVar('field_label'),
            'type'  => $ftype,
        ];

        if (in_array($ftype, ['select', 'radio', 'checkbox'])) {
            $sql = "SELECT option_value, option_label FROM {$xoopsDB->prefix('xcreate_field_options')}
                    WHERE option_field_id = {$fid} ORDER BY option_weight ASC, option_label ASC";
            $res  = $xoopsDB->query($sql);
            $opts = [];
            while ($row = $xoopsDB->fetchArray($res)) {
                $opts[] = ['value' => $row['option_value'], 'label' => $row['option_label']];
            }
            if (empty($opts)) {
                foreach (_xcf2_field_values($fid, $cat_id) as $v) {
                    $opts[] = ['value' => $v, 'label' => $v];
                }
            }
            $entry['options'] = $opts;
        }

        if ($ftype === 'number') {
            $range = _xcf2_num_range($fid, $cat_id);
            $entry['num_min'] = $range['min'];
            $entry['num_max'] = $range['max'];
        }

        if (in_array($ftype, ['text', 'textarea', 'email', 'url'])) {
            $entry['suggestions'] = array_keys(_xcf2_field_values($fid, $cat_id));
        }

        $filter_field_data[] = $entry;
    }

    // GET'ten aktif filtreleri oku
    $active_filters = [];
    $active_ranges  = [];
    foreach ($filter_field_data as $fd) {
        $key = 'xcf_' . $fd['id'];
        if ($fd['type'] === 'number') {
            $min_key = $key . '_min';
            $max_key = $key . '_max';
            if (isset($_GET[$min_key]) || isset($_GET[$max_key])) {
                $active_ranges[$fd['id']] = [
                    'min' => isset($_GET[$min_key]) ? floatval($_GET[$min_key]) : $fd['num_min'],
                    'max' => isset($_GET[$max_key]) ? floatval($_GET[$max_key]) : $fd['num_max'],
                ];
            }
        } elseif (isset($_GET[$key]) && $_GET[$key] !== '') {
            $active_filters[$fd['id']] = htmlspecialchars(strip_tags($_GET[$key]), ENT_QUOTES);
        }
    }

    $filter_applied = !empty($active_filters) || !empty($active_ranges);
    $total_count    = _xcf2_count_items($cat_id, $active_filters, $active_ranges);

    // Aktif filtre badge'leri (label için)
    $active_badges = [];
    foreach ($active_filters as $fid => $val) {
        $label = '';
        foreach ($filter_field_data as $fd) {
            if ($fd['id'] == $fid) { $label = $fd['label']; break; }
        }
        // Seçenek label'ı bul
        $display_val = $val;
        foreach ($filter_field_data as $fd) {
            if ($fd['id'] == $fid && isset($fd['options'])) {
                foreach ($fd['options'] as $opt) {
                    if ($opt['value'] == $val) { $display_val = $opt['label']; break 2; }
                }
            }
        }
        $active_badges[] = ['fid' => $fid, 'label' => $label, 'value' => $display_val, 'type' => 'filter'];
    }
    foreach ($active_ranges as $fid => $range) {
        $label = '';
        foreach ($filter_field_data as $fd) {
            if ($fd['id'] == $fid) { $label = $fd['label']; break; }
        }
        $active_badges[] = ['fid' => $fid, 'label' => $label, 'value' => $range['min'] . ' – ' . $range['max'], 'type' => 'range'];
    }

    // Kategori adı
    $cat_name = '';
    if ($cat_id > 0) {
        $cat = $categoryHandler->get($cat_id);
        if ($cat) $cat_name = $cat->getVar('cat_name');
    }

    // AJAX endpoint URL'i (blok dosyasının kendisi)
    $ajax_url = XOOPS_URL . '/modules/xcreate/blocks/xcreate_filter_block.php';

    // Sonuç sayfası URL'i - GET parametrelerini temizle, sadece cat_id ekle
    $result_base = $result_page;
    if ($cat_id > 0 && strpos($result_base, 'cat_id') === false) {
        $result_base .= (strpos($result_base, '?') !== false ? '&' : '?') . 'cat_id=' . $cat_id;
    }

    return [
        'cat_id'         => $cat_id,
        'cat_name'       => $cat_name,
        'limit'          => $limit,
        'sort_field'     => $sort_field,
        'sort_order'     => $sort_order,
        'filter_fields'  => $filter_field_data,
        'active_filters' => $active_filters,
        'active_ranges'  => $active_ranges,
        'active_badges'  => $active_badges,
        'filter_applied' => $filter_applied,
        'total_count'    => $total_count,
        'result_base'    => htmlspecialchars($result_base, ENT_QUOTES),
        'ajax_url'       => htmlspecialchars($ajax_url, ENT_QUOTES),
        'base_url'       => htmlspecialchars(strtok($_SERVER['REQUEST_URI'] ?? '', '?'), ENT_QUOTES),
    ];
}

// ── EDIT ─────────────────────────────────────────────────────
function b_xcreate_filter_edit($options)
{
    global $xoopsDB;
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';

    $cat_id        = isset($options[0]) ? intval($options[0])   : 0;
    $limit         = isset($options[1]) ? intval($options[1])   : 20;
    $filter_fields = isset($options[2]) ? trim($options[2])     : '';
    $result_page   = isset($options[3]) ? trim($options[3])     : '';
    $sort_field    = isset($options[4]) ? trim($options[4])     : 'item_created';
    $sort_order    = isset($options[5]) ? trim($options[5])     : 'DESC';

    $categoryHandler = new XcreateCategoryHandler($xoopsDB);
    $categories      = $categoryHandler->getTree();

    $form  = '<table border="0" cellpadding="6" cellspacing="0" style="width:100%">';

    // Kategori
    $form .= '<tr><td width="180"><b>' . _MB_XCREATE_FILTER_CATEGORY . ':</b></td><td>';
    $form .= '<select name="options[0]">';
    $form .= '<option value="0"' . ($cat_id == 0 ? ' selected' : '') . '>-- ' . _MB_XCREATE_FILTER_ALL_CATEGORIES . ' --</option>';
    foreach ($categories as $cat) {
        $prefix   = str_repeat('&nbsp;&nbsp;', $cat->getVar('level'));
        $selected = ($cat_id == $cat->getVar('cat_id')) ? ' selected' : '';
        $form .= '<option value="' . $cat->getVar('cat_id') . '"' . $selected . '>' . $prefix . htmlspecialchars($cat->getVar('cat_name'), ENT_QUOTES) . '</option>';
    }
    $form .= '</select></td></tr>';

    // Limit
    $form .= '<tr><td><b>' . _MB_XCREATE_FILTER_RESULT_LIMIT . ':</b></td><td>';
    $form .= '<input type="number" name="options[1]" value="' . $limit . '" size="5" min="1" max="200"> ' . _MB_XCREATE_FILTER_RECORDS . '</td></tr>';

    // Filtrelenecek alanlar
    $form .= '<tr><td valign="top"><b>' . _MB_XCREATE_FILTER_FIELDS . ':</b><br><small>' . _MB_XCREATE_FILTER_FIELDS_HELP . '</small></td><td>';
    $sql = "SELECT f.field_id, f.field_name, f.field_label, f.field_type, c.cat_name
            FROM {$xoopsDB->prefix('xcreate_fields')} f
            LEFT JOIN {$xoopsDB->prefix('xcreate_categories')} c ON c.cat_id = f.field_cat_id
            WHERE f.field_status = 1" . ($cat_id > 0 ? " AND f.field_cat_id = {$cat_id}" : "") . "
            ORDER BY c.cat_name, f.field_weight ASC";
    $res         = $xoopsDB->query($sql);
    $checked_ids = array_filter(array_map('intval', explode(',', $filter_fields)));
    while ($row = $xoopsDB->fetchArray($res)) {
        $checked = in_array($row['field_id'], $checked_ids) ? ' checked' : '';
        $form .= '<label style="display:block;margin-bottom:3px">';
        $form .= '<input type="checkbox" class="xcf-admin-check" value="' . $row['field_id'] . '"' . $checked . '> ';
        $form .= '<b>' . htmlspecialchars($row['field_label'], ENT_QUOTES) . '</b>';
        $form .= ' <small style="color:#888">(' . $row['field_type'] . ' – ' . htmlspecialchars($row['cat_name'] ?? '', ENT_QUOTES) . ')</small>';
        $form .= '</label>';
    }
    $form .= '<input type="hidden" name="options[2]" id="xcf-fids-hidden" value="' . htmlspecialchars($filter_fields, ENT_QUOTES) . '">';
    $form .= '</td></tr>';

    // Sonuç sayfası URL
    $form .= '<tr><td><b>' . _MB_XCREATE_FILTER_RESULT_URL . ':</b><br><small>' . _MB_XCREATE_FILTER_RESULT_URL_HELP . '</small></td><td>';
    $form .= '<input type="text" name="options[3]" value="' . htmlspecialchars($result_page, ENT_QUOTES) . '" style="width:100%" placeholder="' . XOOPS_URL . '/modules/xcreate/index.php"></td></tr>';

    // Sıralama
    $sort_opts = ['item_created' => _MB_XCREATE_FILTER_SORT_CREATED, 'item_title' => _MB_XCREATE_FILTER_SORT_TITLE, 'item_hits' => _MB_XCREATE_FILTER_SORT_HITS];
    $form .= '<tr><td><b>' . _MB_XCREATE_FILTER_DEFAULT_SORT . ':</b></td><td>';
    $form .= '<select name="options[4]">';
    foreach ($sort_opts as $v => $l) {
        $form .= '<option value="' . $v . '"' . ($sort_field === $v ? ' selected' : '') . '>' . $l . '</option>';
    }
    $form .= '</select> <select name="options[5]">';
    $form .= '<option value="DESC"' . ($sort_order === 'DESC' ? ' selected' : '') . '>' . _MB_XCREATE_FILTER_SORT_DESC . '</option>';
    $form .= '<option value="ASC"' . ($sort_order === 'ASC' ? ' selected' : '') . '>' . _MB_XCREATE_FILTER_SORT_ASC . '</option>';
    $form .= '</select></td></tr>';

    $form .= '</table>';
    $form .= '<script>
document.addEventListener("change", function(e) {
    if (!e.target.classList.contains("xcf-admin-check")) return;
    var ids = [];
    document.querySelectorAll(".xcf-admin-check:checked").forEach(function(el) { ids.push(el.value); });
    document.getElementById("xcf-fids-hidden").value = ids.join(",");
});
</script>';

    return $form;
}
