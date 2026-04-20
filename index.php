<?php
/**
 * Main Index Page
 * SEO URL desteği: /modules/xcreate/kategori-slug/
 * Filtre desteği: xcf_FIELDID=VALUE, xcf_FIELDID_min=X, xcf_FIELDID_max=Y
 * Eren Yumak tarafından kodlanmıştır - Aymak
 */

include '../../mainfile.php';

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/slug.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/fields_helper.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/rating.php';

$categoryHandler = new XcreateCategoryHandler($GLOBALS['xoopsDB']);
$itemHandler     = new XcreateItemHandler($GLOBALS['xoopsDB']);
$ratingHandler   = new XcreateRatingHandler($GLOBALS['xoopsDB']);
$fieldHandler    = new XcreateFieldHandler($GLOBALS['xoopsDB']);
$xoopsDB         = $GLOBALS['xoopsDB'];

// --- Kategori belirleme: cat_slug (SEO) veya cat_id (eski) ---
$cat_id = 0;
$current_category = null;

if (!empty($_GET['cat_slug'])) {
    $current_category = $categoryHandler->getBySlug($_GET['cat_slug']);
    if ($current_category && !$current_category->isNew()) {
        $cat_id = $current_category->getVar('cat_id');
    }
} elseif (!empty($_GET['cat_id'])) {
    $cat_id = intval($_GET['cat_id']);
    if ($cat_id > 0) {
        $current_category = $categoryHandler->get($cat_id);
        // Eski ?cat_id= linkine gelindiyse, SEO URL'ye 301 yönlendir
        // NOT: Filtre parametreleri varsa yönlendirme yapma
        $has_filters = false;
        foreach ($_GET as $gk => $gv) {
            if (strpos($gk, 'xcf_') === 0) { $has_filters = true; break; }
        }
        if (!$has_filters && $current_category && !$current_category->isNew()) {
            $cs = $current_category->getVar('cat_slug');
            if (!empty($cs)) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: ' . XOOPS_URL . '/modules/xcreate/' . $cs . '/');
                exit();
            }
        }
    }
}

// --- xcf_* filtrelerini GET'ten oku ---
// Önce hangi field_id'ler var diye tüm aktif alanları çek
$all_field_ids = [];
$sql_fids = "SELECT field_id, field_type FROM " . $xoopsDB->prefix('xcreate_fields') . " WHERE field_status = 1";
$res_fids = $xoopsDB->query($sql_fids);
$field_type_map = [];
while ($row_fid = $xoopsDB->fetchArray($res_fids)) {
    $all_field_ids[]                         = (int)$row_fid['field_id'];
    $field_type_map[$row_fid['field_id']]    = $row_fid['field_type'];
}

$active_filters = []; // [field_id => value]
$active_ranges  = []; // [field_id => [min, max]]

foreach ($all_field_ids as $fid) {
    $key = 'xcf_' . $fid;
    if ($field_type_map[$fid] === 'number') {
        $min_key = $key . '_min';
        $max_key = $key . '_max';
        if (isset($_GET[$min_key]) || isset($_GET[$max_key])) {
            // min/max değerleri DB'den al (sınır olarak)
            $num_sql = "SELECT MIN(CAST(v.value_text AS DECIMAL(20,4))) AS vmin,
                               MAX(CAST(v.value_text AS DECIMAL(20,4))) AS vmax
                        FROM " . $xoopsDB->prefix('xcreate_field_values') . " v
                        INNER JOIN " . $xoopsDB->prefix('xcreate_items') . " i ON i.item_id = v.value_item_id
                        WHERE v.value_field_id = {$fid} AND i.item_status = 1";
            if ($cat_id > 0) $num_sql .= " AND i.item_cat_id = " . $cat_id;
            $num_res = $xoopsDB->query($num_sql);
            $num_row = $xoopsDB->fetchArray($num_res);
            $db_min  = isset($num_row['vmin']) ? floatval($num_row['vmin']) : 0;
            $db_max  = isset($num_row['vmax']) ? floatval($num_row['vmax']) : 999999;

            $active_ranges[$fid] = [
                'min' => isset($_GET[$min_key]) ? floatval($_GET[$min_key]) : $db_min,
                'max' => isset($_GET[$max_key]) ? floatval($_GET[$max_key]) : $db_max,
            ];
        }
    } elseif (isset($_GET[$key]) && $_GET[$key] !== '') {
        $active_filters[$fid] = htmlspecialchars(strip_tags(trim($_GET[$key])), ENT_QUOTES);
    }
}

$filter_active = !empty($active_filters) || !empty($active_ranges);

// --- Template belirleme ---
if ($current_category && !$current_category->isNew()) {
    $list_tpl = $current_category->getVar('cat_list_template');
    $xoopsOption['template_main'] = !empty($list_tpl) ? $list_tpl : 'xcreate_index.tpl';
} else {
    $xoopsOption['template_main'] = 'xcreate_index.tpl';
}

// ---- Kategori SEO META (header.php'den önce) ----
if ($current_category && !$current_category->isNew()) {
    $idx_meta_title = trim($current_category->getVar('cat_meta_title'));
    if (empty($idx_meta_title)) {
        $idx_meta_title = $current_category->getVar('cat_name') . ' — ' . $GLOBALS['xoopsConfig']['sitename'];
    }
    $idx_meta_desc = trim($current_category->getVar('cat_meta_description'));
    if (empty($idx_meta_desc)) {
        $raw = strip_tags($current_category->getVar('cat_description'));
        $idx_meta_desc = mb_substr(preg_replace('/\s+/', ' ', $raw), 0, 155);
        if (mb_strlen($raw) > 155) $idx_meta_desc .= '…';
    }
    $idx_meta_kw  = trim($current_category->getVar('cat_meta_keywords'));
    $idx_noindex  = (int)$current_category->getVar('cat_noindex');
    $idx_og_image = trim($current_category->getVar('cat_og_image'));

    $xoopsMeta['description'] = htmlspecialchars($idx_meta_desc, ENT_QUOTES);
    $xoopsMeta['keywords']    = htmlspecialchars($idx_meta_kw, ENT_QUOTES);
    $xoopsOption['xoops_pagetitle'] = htmlspecialchars($idx_meta_title, ENT_QUOTES);
}
// ---- SEO META SONU ----

include XOOPS_ROOT_PATH . '/header.php';

// Load language files
$language = $GLOBALS['xoopsConfig']['language'];
if (file_exists(XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/main.php")) {
    include_once XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/main.php";
} else {
    include_once XOOPS_ROOT_PATH . "/modules/xcreate/language/english/main.php";
}

// Get all categories (tree)
$categories    = $categoryHandler->getTree();
$category_list = array();
foreach ($categories as $category) {
    $cs = $category->getVar('cat_slug');
    $category_list[] = array(
        'id'          => $category->getVar('cat_id'),
        'slug'        => $cs,
        'pid'         => $category->getVar('cat_pid'),
        'name'        => $category->getVar('cat_name'),
        'description' => $category->getVar('cat_description'),
        'level'       => $category->getVar('level'),
        'image'       => $category->getVar('cat_image'),
        'url'         => !empty($cs)
                            ? XOOPS_URL . '/modules/xcreate/' . $cs . '/'
                            : XOOPS_URL . '/modules/xcreate/index.php?cat_id=' . $category->getVar('cat_id')
    );
}

// Pagination
$limit = isset($xoopsModuleConfig['items_per_page']) ? intval($xoopsModuleConfig['items_per_page']) : 10;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;

// Pagination URL — mevcut filtreler korunur
$page_extra_parts = [];
if ($current_category && !$current_category->isNew()) {
    $cs_page = $current_category->getVar('cat_slug');
    if (empty($cs_page)) $page_extra_parts[] = 'cat_id=' . $cat_id;
} elseif ($cat_id > 0) {
    $page_extra_parts[] = 'cat_id=' . $cat_id;
}
// Aktif filtreleri pagination URL'e ekle
foreach ($active_filters as $fid => $val) {
    $page_extra_parts[] = 'xcf_' . $fid . '=' . urlencode($val);
}
foreach ($active_ranges as $fid => $range) {
    $page_extra_parts[] = 'xcf_' . $fid . '_min=' . $range['min'];
    $page_extra_parts[] = 'xcf_' . $fid . '_max=' . $range['max'];
}
$page_extra = implode('&', $page_extra_parts);

// --- Item çekme: filtre varsa özel SQL, yoksa eski yöntem ---
if ($filter_active) {
    // Filtreli SQL: xcreate_items JOIN ile field_values kontrol
    $where = "i.item_status = 1";
    if ($cat_id > 0) $where .= " AND i.item_cat_id = " . intval($cat_id);

    foreach ($active_filters as $fid => $val) {
        $fid_safe = intval($fid);
        $val_safe = $xoopsDB->escape($val);
        $where .= " AND EXISTS (
            SELECT 1 FROM " . $xoopsDB->prefix('xcreate_field_values') . " fv
            WHERE fv.value_item_id = i.item_id
              AND fv.value_field_id = {$fid_safe}
              AND (fv.value_text = '{$val_safe}' OR FIND_IN_SET('{$val_safe}', fv.value_text))
        )";
    }
    foreach ($active_ranges as $fid => $range) {
        $fid_safe = intval($fid);
        $r_min    = floatval($range['min']);
        $r_max    = floatval($range['max']);
        $where .= " AND EXISTS (
            SELECT 1 FROM " . $xoopsDB->prefix('xcreate_field_values') . " fv
            WHERE fv.value_item_id = i.item_id
              AND fv.value_field_id = {$fid_safe}
              AND CAST(fv.value_text AS DECIMAL(20,4)) BETWEEN {$r_min} AND {$r_max}
        )";
    }

    // Toplam sayı
    $count_sql   = "SELECT COUNT(*) AS cnt FROM " . $xoopsDB->prefix('xcreate_items') . " i WHERE {$where}";
    $count_res   = $xoopsDB->query($count_sql);
    $count_row   = $xoopsDB->fetchArray($count_res);
    $total_items = intval($count_row['cnt']);

    // Sayfalı liste
    $items_sql = "SELECT i.* FROM " . $xoopsDB->prefix('xcreate_items') . " i
                  WHERE {$where}
                  ORDER BY i.item_created DESC
                  LIMIT " . intval($limit) . " OFFSET " . intval($start);
    $items_res  = $xoopsDB->query($items_sql);
    $items      = [];
    while ($items_row = $xoopsDB->fetchArray($items_res)) {
        $obj = $itemHandler->create(false);
        $obj->assignVars($items_row);
        $items[] = $obj;
    }

} elseif ($cat_id > 0) {
    $items       = $itemHandler->getItemsByCategory($cat_id, 1, $limit, $start);
    $total_items = $itemHandler->getCount(new Criteria('item_cat_id', $cat_id));
    if ($current_category) {
        $xoopsTpl->assign('current_category', array(
            'id'          => $current_category->getVar('cat_id'),
            'slug'        => $current_category->getVar('cat_slug'),
            'name'        => $current_category->getVar('cat_name'),
            'description' => $current_category->getVar('cat_description')
        ));
    }
} else {
    $items       = $itemHandler->getRecentItems($limit);
    $total_items = $itemHandler->getCount(new Criteria('item_status', 1));
}

// current_category filtreli sorgu için de ata
if ($filter_active && $current_category && !$current_category->isNew()) {
    $xoopsTpl->assign('current_category', array(
        'id'          => $current_category->getVar('cat_id'),
        'slug'        => $current_category->getVar('cat_slug'),
        'name'        => $current_category->getVar('cat_name'),
        'description' => $current_category->getVar('cat_description')
    ));
}

$item_list = array();
$items_raw = array();

foreach ($items as $item) {
    $author    = new XoopsUser($item->getVar('item_uid'));
    $item_cat  = $categoryHandler->get($item->getVar('item_cat_id'));
    $item_cs   = $item_cat ? $item_cat->getVar('cat_slug') : '';
    $item_s    = $item->getVar('item_slug');

    $item_list[] = array(
        'id'          => $item->getVar('item_id'),
        'slug'        => $item_s,
        'title'       => $item->getVar('item_title'),
        'description' => xoops_substr(strip_tags($item->getVar('item_description')), 0, 200),
        'author'      => $author ? $author->getVar('uname') : 'Guest',
        'created'     => formatTimestamp($item->getVar('item_created'), 's'),
        'hits'        => $item->getVar('item_hits'),
        'url'         => (!empty($item_cs) && !empty($item_s))
                            ? XOOPS_URL . '/modules/xcreate/' . $item_cs . '/' . $item_s
                            : XOOPS_URL . '/modules/xcreate/item.php?id=' . $item->getVar('item_id'),
        'rating'      => $ratingHandler->getStats($item->getVar('item_id')),
    );

    $items_raw[] = $item;
}

// İlave alanları her listeye ekle
XcreateFieldsHelper::appendFieldsToList($item_list, $items_raw);

// Pagination
include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
$pagenav = new XoopsPageNav($total_items, $limit, $start, 'start', $page_extra);

$xoopsTpl->assign('categories', $category_list);
$xoopsTpl->assign('items', $item_list);
$xoopsTpl->assign('pagenav', $pagenav->renderNav());
$xoopsTpl->assign('allow_submit', isset($xoopsModuleConfig['allow_user_submit']) ? $xoopsModuleConfig['allow_user_submit'] : 1);
$xoopsTpl->assign('module_url', XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname'));
$xoopsTpl->assign('filter_active', $filter_active);
$xoopsTpl->assign('total_filtered', $filter_active ? $total_items : null);

// ---- Kategori SEO Smarty Atamaları ----
if ($current_category && !$current_category->isNew()) {
    $cat_canonical = !empty($current_category->getVar('cat_slug'))
        ? XOOPS_URL . '/modules/xcreate/' . $current_category->getVar('cat_slug') . '/'
        : XOOPS_URL . '/modules/xcreate/index.php?cat_id=' . $current_category->getVar('cat_id');

    $xoopsTpl->assign('seo', array(
        'title'       => isset($idx_meta_title)  ? htmlspecialchars($idx_meta_title, ENT_QUOTES) : '',
        'description' => isset($idx_meta_desc)   ? htmlspecialchars($idx_meta_desc, ENT_QUOTES)  : '',
        'keywords'    => isset($idx_meta_kw)     ? htmlspecialchars($idx_meta_kw, ENT_QUOTES)    : '',
        'canonical'   => htmlspecialchars($cat_canonical, ENT_QUOTES),
        'og_image'    => isset($idx_og_image)    ? $idx_og_image : '',
        'noindex'     => isset($idx_noindex)     ? (bool)$idx_noindex : false,
        'og_type'     => 'website',
        'site_name'   => $GLOBALS['xoopsConfig']['sitename'],
    ));
}
// ---- SEO Smarty SONU ----

include XOOPS_ROOT_PATH . '/footer.php';
