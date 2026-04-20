<?php
/**
 * Xcreate Widget Blocks (10 Adet)
 * 
 * Widget listesi:
 *  1. Son Eklenenler       - Liste görünümü
 *  2. En Çok Görüntülenen  - Sıralı liste
 *  3. En Yüksek Puanlı     - Rating ile birlikte
 *  4. Rastgele İçerik      - Her yüklemede farklı
 *  5. Öne Çıkan İçerik     - Resimli büyük kart
 *  6. Kategori Özeti       - Kategori başlıklarıyla gruplu
 *  7. Mini Sayaç           - İstatistik widget
 *  8. Slider/Carousel      - Kaydırmalı içerik
 *  9. Etiket Bulutu        - Tag cloud
 * 10. Son Yorum / Aktivite - Kullanıcı aktivitesi
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

$blockLanguage = XOOPS_ROOT_PATH . '/modules/xcreate/language/' . $GLOBALS['xoopsConfig']['language'] . '/blocks.php';
include_once file_exists($blockLanguage) ? $blockLanguage : XOOPS_ROOT_PATH . '/modules/xcreate/language/english/blocks.php';

// ─────────────────────────────────────────────────────────────
// Yardımcı: ortak include
// ─────────────────────────────────────────────────────────────
function _xcreate_widget_includes()
{
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/fields_helper.php';
}

// ─────────────────────────────────────────────────────────────
// Yardımcı: item dizisini hazırla
// ─────────────────────────────────────────────────────────────
function _xcreate_build_item_row($item, $category, $desc_len = 100)
{
    $cat_url = !empty($category->getVar('cat_slug'))
        ? XOOPS_URL . '/modules/xcreate/' . $category->getVar('cat_slug') . '/'
        : XOOPS_URL . '/modules/xcreate/index.php?cat_id=' . $category->getVar('cat_id');

    $item_url = (!empty($category->getVar('cat_slug')) && !empty($item->getVar('item_slug')))
        ? XOOPS_URL . '/modules/xcreate/' . $category->getVar('cat_slug') . '/' . $item->getVar('item_slug')
        : XOOPS_URL . '/modules/xcreate/item.php?id=' . $item->getVar('item_id');

    return [
        'id'           => $item->getVar('item_id'),
        'title'        => $item->getVar('item_title'),
        'description'  => xoops_substr(strip_tags($item->getVar('item_description')), 0, $desc_len),
        'category'     => $category->getVar('cat_name'),
        'category_url' => $cat_url,
        'created'      => formatTimestamp($item->getVar('item_created'), 's'),
        'updated'      => formatTimestamp($item->getVar('item_updated'), 's'),
        'hits'         => $item->getVar('item_hits'),
        'url'          => $item_url,
    ];
}

// ─────────────────────────────────────────────────────────────
// Yardımcı: kategori düzenleme formu (tekrar kullanılabilir)
// ─────────────────────────────────────────────────────────────
function _xcreate_category_select($field_name, $selected_id)
{
    global $xoopsDB;
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);
    $form = _MB_XCREATE_BLOCK_CATEGORY . ': <select name="' . $field_name . '">';
    $form .= '<option value="0"' . ($selected_id == 0 ? ' selected' : '') . '>' . _MB_XCREATE_BLOCK_ALL . '</option>';
    $categories = $categoryHandler->getTree();
    foreach ($categories as $category) {
        $prefix   = str_repeat('--', $category->getVar('level'));
        $selected = ($selected_id == $category->getVar('cat_id')) ? ' selected' : '';
        $form .= '<option value="' . $category->getVar('cat_id') . '"' . $selected . '>'
               . $prefix . ' ' . $category->getVar('cat_name') . '</option>';
    }
    $form .= '</select>';
    return $form;
}

// ═════════════════════════════════════════════════════════════
// WIDGET 1 – Son Eklenenler (Liste)
// ═════════════════════════════════════════════════════════════
function b_xcreate_w1_recent_show($options)
{
    global $xoopsDB;
    _xcreate_widget_includes();

    $limit  = isset($options[0]) ? intval($options[0]) : 5;
    $cat_id = isset($options[1]) ? intval($options[1]) : 0;
    $show_thumb = isset($options[2]) ? intval($options[2]) : 0;

    $itemHandler     = new XcreateItemHandler($xoopsDB);
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);

    $items = ($cat_id > 0)
        ? $itemHandler->getItemsByCategory($cat_id, 1, $limit)
        : $itemHandler->getRecentItems($limit);

    $block          = ['items' => [], 'show_thumb' => $show_thumb];
    $items_raw      = [];

    foreach ($items as $item) {
        $category        = $categoryHandler->get($item->getVar('item_cat_id'));
        $row             = _xcreate_build_item_row($item, $category, 120);
        $block['items'][] = $row;
        $items_raw[]     = $item;
    }

    XcreateFieldsHelper::appendFieldsToList($block['items'], $items_raw);
    return $block;
}

function b_xcreate_w1_recent_edit($options)
{
    $form  = 'Gösterilecek Sayı: <input type="text" name="options[0]" value="' . (isset($options[0]) ? $options[0] : 5) . '" size="5"><br>';
    $form .= _xcreate_category_select('options[1]', isset($options[1]) ? $options[1] : 0) . '<br>';
    $form .= 'Küçük Resim Göster: <select name="options[2]">';
    $form .= '<option value="0"' . (empty($options[2]) ? ' selected' : '') . '>Hayır</option>';
    $form .= '<option value="1"' . (!empty($options[2]) ? ' selected' : '') . '>Evet</option>';
    $form .= '</select>';
    return $form;
}

// ═════════════════════════════════════════════════════════════
// WIDGET 2 – En Çok Görüntülenen
// ═════════════════════════════════════════════════════════════
function b_xcreate_w2_popular_show($options)
{
    global $xoopsDB;
    _xcreate_widget_includes();

    $limit  = isset($options[0]) ? intval($options[0]) : 5;
    $cat_id = isset($options[1]) ? intval($options[1]) : 0;

    $itemHandler     = new XcreateItemHandler($xoopsDB);
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);

    $criteria = new CriteriaCompo();
    $criteria->add(new Criteria('item_status', 1));
    if ($cat_id > 0) {
        $criteria->add(new Criteria('item_cat_id', $cat_id));
    }
    $criteria->setSort('item_hits');
    $criteria->setOrder('DESC');
    $criteria->setLimit($limit);

    $items_obj = $itemHandler->getObjects($criteria);

    $block     = ['items' => []];
    $items_raw = [];
    $rank      = 1;

    foreach ($items_obj as $item) {
        $category        = $categoryHandler->get($item->getVar('item_cat_id'));
        $row             = _xcreate_build_item_row($item, $category, 80);
        $row['rank']      = $rank++;
        $block['items'][] = $row;
        $items_raw[]     = $item;
    }

    XcreateFieldsHelper::appendFieldsToList($block['items'], $items_raw);
    return $block;
}

function b_xcreate_w2_popular_edit($options)
{
    $form  = 'Gösterilecek Sayı: <input type="text" name="options[0]" value="' . (isset($options[0]) ? $options[0] : 5) . '" size="5"><br>';
    $form .= _xcreate_category_select('options[1]', isset($options[1]) ? $options[1] : 0);
    return $form;
}

// ═════════════════════════════════════════════════════════════
// WIDGET 3 – En Yüksek Puanlı
// ═════════════════════════════════════════════════════════════
function b_xcreate_w3_toprated_show($options)
{
    global $xoopsDB;
    _xcreate_widget_includes();
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/rating.php';

    $limit  = isset($options[0]) ? intval($options[0]) : 5;
    $cat_id = isset($options[1]) ? intval($options[1]) : 0;

    $ratingHandler   = new XcreateRatingHandler($xoopsDB);
    $itemHandler     = new XcreateItemHandler($xoopsDB);
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);

    // Rating tablosundan en yüksek ortalamaya göre item_id listesi çek
    $tbl_rating = $xoopsDB->prefix('xcreate_ratings');
    $tbl_items  = $xoopsDB->prefix('xcreate_items');

    $cat_sql = ($cat_id > 0) ? "AND i.item_cat_id = " . intval($cat_id) : "";

    $sql = "SELECT r.rating_item_id, AVG(r.rating_score) AS avg_score, COUNT(r.rating_id) AS vote_count
            FROM {$tbl_rating} r
            JOIN {$tbl_items} i ON i.item_id = r.rating_item_id AND i.item_status = 1
            WHERE 1=1 {$cat_sql}
            GROUP BY r.rating_item_id
            HAVING vote_count >= 1
            ORDER BY avg_score DESC, vote_count DESC
            LIMIT " . intval($limit);

    $result = $xoopsDB->query($sql);

    $block = ['items' => []];

    if (!$result || !is_object($result)) {
        return $block;
    }

    while ($row = $xoopsDB->fetchArray($result)) {
        $item = $itemHandler->get($row['rating_item_id']);
        if (!$item) continue;
        $category        = $categoryHandler->get($item->getVar('item_cat_id'));
        $r               = _xcreate_build_item_row($item, $category, 80);
        $r['avg_score']   = round($row['avg_score'], 1);
        $r['vote_count']  = $row['vote_count'];
        $r['stars_full']  = floor($row['avg_score']);
        $r['stars_half']  = (($row['avg_score'] - floor($row['avg_score'])) >= 0.5) ? 1 : 0;
        $r['stars_empty'] = 5 - $r['stars_full'] - $r['stars_half'];
        $block['items'][] = $r;
    }

    return $block;
}

function b_xcreate_w3_toprated_edit($options)
{
    $form  = 'Gösterilecek Sayı: <input type="text" name="options[0]" value="' . (isset($options[0]) ? $options[0] : 5) . '" size="5"><br>';
    $form .= _xcreate_category_select('options[1]', isset($options[1]) ? $options[1] : 0);
    return $form;
}

// ═════════════════════════════════════════════════════════════
// WIDGET 4 – Rastgele İçerik
// ═════════════════════════════════════════════════════════════
function b_xcreate_w4_random_show($options)
{
    global $xoopsDB;
    _xcreate_widget_includes();

    $limit  = isset($options[0]) ? intval($options[0]) : 5;
    $cat_id = isset($options[1]) ? intval($options[1]) : 0;

    $itemHandler     = new XcreateItemHandler($xoopsDB);
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);

    $criteria = new CriteriaCompo();
    $criteria->add(new Criteria('item_status', 1));
    if ($cat_id > 0) {
        $criteria->add(new Criteria('item_cat_id', $cat_id));
    }
    $criteria->setSort('RAND()');
    $criteria->setLimit($limit);

    $items_obj = $itemHandler->getObjects($criteria);

    $block     = ['items' => []];
    $items_raw = [];

    foreach ($items_obj as $item) {
        $category        = $categoryHandler->get($item->getVar('item_cat_id'));
        $block['items'][] = _xcreate_build_item_row($item, $category, 120);
        $items_raw[]     = $item;
    }

    XcreateFieldsHelper::appendFieldsToList($block['items'], $items_raw);
    return $block;
}

function b_xcreate_w4_random_edit($options)
{
    $form  = 'Gösterilecek Sayı: <input type="text" name="options[0]" value="' . (isset($options[0]) ? $options[0] : 5) . '" size="5"><br>';
    $form .= _xcreate_category_select('options[1]', isset($options[1]) ? $options[1] : 0);
    return $form;
}

// ═════════════════════════════════════════════════════════════
// WIDGET 5 – Öne Çıkan / Vitrin (Resimli Kart)
// ═════════════════════════════════════════════════════════════
function b_xcreate_w5_featured_show($options)
{
    global $xoopsDB;
    _xcreate_widget_includes();

    $limit      = isset($options[0]) ? intval($options[0]) : 3;
    $cat_id     = isset($options[1]) ? intval($options[1]) : 0;
    $image_field = isset($options[2]) ? trim($options[2]) : 'resim'; // İlave alan adı

    $itemHandler     = new XcreateItemHandler($xoopsDB);
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);

    $criteria = new CriteriaCompo();
    $criteria->add(new Criteria('item_status', 1));
    if ($cat_id > 0) {
        $criteria->add(new Criteria('item_cat_id', $cat_id));
    }
    $criteria->setSort('item_created');
    $criteria->setOrder('DESC');
    $criteria->setLimit($limit);

    $items_obj = $itemHandler->getObjects($criteria);

    $block     = ['items' => [], 'image_field' => $image_field];
    $items_raw = [];

    foreach ($items_obj as $item) {
        $category        = $categoryHandler->get($item->getVar('item_cat_id'));
        $row             = _xcreate_build_item_row($item, $category, 150);
        $block['items'][] = $row;
        $items_raw[]     = $item;
    }

    XcreateFieldsHelper::appendFieldsToList($block['items'], $items_raw);
    return $block;
}

function b_xcreate_w5_featured_edit($options)
{
    $form  = 'Gösterilecek Sayı: <input type="text" name="options[0]" value="' . (isset($options[0]) ? $options[0] : 3) . '" size="5"><br>';
    $form .= _xcreate_category_select('options[1]', isset($options[1]) ? $options[1] : 0) . '<br>';
    $form .= 'Resim İlave Alan Adı: <input type="text" name="options[2]" value="' . (isset($options[2]) ? htmlspecialchars($options[2]) : 'resim') . '" size="20"> <small>(ilave alan adı)</small>';
    return $form;
}

// ═════════════════════════════════════════════════════════════
// WIDGET 6 – Kategori Özeti (Gruplu Liste)
// ═════════════════════════════════════════════════════════════
function b_xcreate_w6_catgroup_show($options)
{
    global $xoopsDB;
    _xcreate_widget_includes();

    $per_cat   = isset($options[0]) ? intval($options[0]) : 3;
    $cat_ids_raw = isset($options[1]) ? $options[1] : '0'; // virgülle ayrılmış kategori id'leri, 0=tümü
    $max_cats  = isset($options[2]) ? intval($options[2]) : 4;

    $itemHandler     = new XcreateItemHandler($xoopsDB);
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);

    // Kategorileri al
    if (trim($cat_ids_raw) === '0' || trim($cat_ids_raw) === '') {
        $cats_obj = $categoryHandler->getTree();
        if ($max_cats > 0) {
            $cats_obj = array_slice($cats_obj, 0, $max_cats);
        }
    } else {
        $ids      = array_map('intval', explode(',', $cat_ids_raw));
        $cats_obj = [];
        foreach ($ids as $id) {
            $c = $categoryHandler->get($id);
            if ($c) $cats_obj[] = $c;
        }
    }

    $block = ['categories' => []];

    foreach ($cats_obj as $cat) {
        $cat_id   = $cat->getVar('cat_id');
        $items    = $itemHandler->getItemsByCategory($cat_id, 1, $per_cat);
        $cat_url  = !empty($cat->getVar('cat_slug'))
            ? XOOPS_URL . '/modules/xcreate/' . $cat->getVar('cat_slug') . '/'
            : XOOPS_URL . '/modules/xcreate/index.php?cat_id=' . $cat_id;

        $cat_data = [
            'id'    => $cat_id,
            'name'  => $cat->getVar('cat_name'),
            'url'   => $cat_url,
            'items' => []
        ];

        $items_raw = [];
        foreach ($items as $item) {
            $cat_data['items'][] = _xcreate_build_item_row($item, $cat, 80);
            $items_raw[]         = $item;
        }
        XcreateFieldsHelper::appendFieldsToList($cat_data['items'], $items_raw);

        $block['categories'][] = $cat_data;
    }

    return $block;
}

function b_xcreate_w6_catgroup_edit($options)
{
    $form  = 'Kategoride Gösterilecek İçerik: <input type="text" name="options[0]" value="' . (isset($options[0]) ? $options[0] : 3) . '" size="5"><br>';
    $form .= 'Kategori ID\'leri (virgülle, 0=tümü): <input type="text" name="options[1]" value="' . (isset($options[1]) ? htmlspecialchars($options[1]) : '0') . '" size="30"><br>';
    $form .= 'Maksimum Kategori Sayısı: <input type="text" name="options[2]" value="' . (isset($options[2]) ? $options[2] : 4) . '" size="5">';
    return $form;
}

// ═════════════════════════════════════════════════════════════
// WIDGET 7 – Mini İstatistik Sayacı
// ═════════════════════════════════════════════════════════════
function b_xcreate_w7_stats_show($options)
{
    global $xoopsDB;
    _xcreate_widget_includes();

    $cat_id = isset($options[0]) ? intval($options[0]) : 0;
    $show_hits = isset($options[1]) ? intval($options[1]) : 1;

    $tbl_items = $xoopsDB->prefix('xcreate_items');
    $tbl_cats  = $xoopsDB->prefix('xcreate_cats');

    $cat_where = ($cat_id > 0) ? "AND item_cat_id = " . intval($cat_id) : "";

    // Toplam içerik
    $sql    = "SELECT COUNT(*) AS cnt FROM {$tbl_items} WHERE item_status = 1 {$cat_where}";
    $res    = $xoopsDB->query($sql);
    $row    = $xoopsDB->fetchArray($res);
    $total  = $row['cnt'];

    // Toplam görüntülenme
    $total_hits = 0;
    if ($show_hits) {
        $sql2       = "SELECT COALESCE(SUM(item_hits),0) AS hits FROM {$tbl_items} WHERE item_status = 1 {$cat_where}";
        $res2       = $xoopsDB->query($sql2);
        $row2       = $xoopsDB->fetchArray($res2);
        $total_hits = $row2['hits'];
    }

    // Toplam kategori
    $sql3    = "SELECT COUNT(*) AS cnt FROM {$tbl_cats}";
    $res3    = $xoopsDB->query($sql3);
    $row3    = $xoopsDB->fetchArray($res3);
    $cat_cnt = $row3['cnt'];

    // Bu hafta eklenen
    $week_ago = time() - 7 * 86400;
    $sql4     = "SELECT COUNT(*) AS cnt FROM {$tbl_items} WHERE item_status = 1 AND item_created >= {$week_ago} {$cat_where}";
    $res4     = $xoopsDB->query($sql4);
    $row4     = $xoopsDB->fetchArray($res4);
    $this_week = $row4['cnt'];

    return [
        'total_items'  => $total,
        'total_hits'   => $total_hits,
        'total_cats'   => $cat_cnt,
        'this_week'    => $this_week,
        'show_hits'    => $show_hits,
    ];
}

function b_xcreate_w7_stats_edit($options)
{
    $form  = _xcreate_category_select('options[0]', isset($options[0]) ? $options[0] : 0) . '<br>';
    $form .= _AM_XCREATE_ITEM_HITS . ': <select name="options[1]">';
    $form .= '<option value="1"' . (!isset($options[1]) || $options[1] ? ' selected' : '') . '>' . _AM_XCREATE_YES . '</option>';
    $form .= '<option value="0"' . (isset($options[1]) && !$options[1] ? ' selected' : '') . '>' . _AM_XCREATE_NO . '</option>';
    $form .= '</select>';
    return $form;
}

// ═════════════════════════════════════════════════════════════
// WIDGET 8 – Slider / Carousel
// ═════════════════════════════════════════════════════════════
function b_xcreate_w8_slider_show($options)
{
    global $xoopsDB;
    _xcreate_widget_includes();

    $limit       = isset($options[0]) ? intval($options[0]) : 5;
    $cat_id      = isset($options[1]) ? intval($options[1]) : 0;
    $image_field = isset($options[2]) ? trim($options[2]) : 'resim';
    $auto_ms     = isset($options[3]) ? intval($options[3]) : 4000; // otomatik geçiş ms

    $itemHandler     = new XcreateItemHandler($xoopsDB);
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);

    $criteria = new CriteriaCompo();
    $criteria->add(new Criteria('item_status', 1));
    if ($cat_id > 0) {
        $criteria->add(new Criteria('item_cat_id', $cat_id));
    }
    $criteria->setSort('item_created');
    $criteria->setOrder('DESC');
    $criteria->setLimit($limit);

    $items_obj = $itemHandler->getObjects($criteria);

    $block     = [
        'items'       => [],
        'image_field' => $image_field,
        'auto_ms'     => $auto_ms,
        'unique_id'   => 'xcs_' . substr(md5(microtime()), 0, 6),
    ];
    $items_raw = [];

    foreach ($items_obj as $item) {
        $category        = $categoryHandler->get($item->getVar('item_cat_id'));
        $block['items'][] = _xcreate_build_item_row($item, $category, 100);
        $items_raw[]     = $item;
    }

    XcreateFieldsHelper::appendFieldsToList($block['items'], $items_raw);
    return $block;
}

function b_xcreate_w8_slider_edit($options)
{
    $form  = 'Slayt Sayısı: <input type="text" name="options[0]" value="' . (isset($options[0]) ? $options[0] : 5) . '" size="5"><br>';
    $form .= _xcreate_category_select('options[1]', isset($options[1]) ? $options[1] : 0) . '<br>';
    $form .= 'Resim İlave Alan Adı: <input type="text" name="options[2]" value="' . (isset($options[2]) ? htmlspecialchars($options[2]) : 'resim') . '" size="20"><br>';
    $form .= 'Otomatik Geçiş (ms, 0=kapalı): <input type="text" name="options[3]" value="' . (isset($options[3]) ? $options[3] : 4000) . '" size="8">';
    return $form;
}

// ═════════════════════════════════════════════════════════════
// WIDGET 9 – Etiket Bulutu (Tag Cloud) – İlave alan değerlerine göre
// ═════════════════════════════════════════════════════════════
function b_xcreate_w9_tagcloud_show($options)
{
    global $xoopsDB;
    _xcreate_widget_includes();

    $field_name = isset($options[0]) ? trim($options[0]) : 'etiket'; // ilave alan adı
    $max_tags   = isset($options[1]) ? intval($options[1]) : 30;
    $cat_id     = isset($options[2]) ? intval($options[2]) : 0;

    $tbl_fields  = $xoopsDB->prefix('xcreate_fields');
    $tbl_values  = $xoopsDB->prefix('xcreate_field_values');
    $tbl_items   = $xoopsDB->prefix('xcreate_items');

    // Alan ID'sini bul
    $sql = "SELECT field_id FROM {$tbl_fields} WHERE field_name = '" . $xoopsDB->escape($field_name) . "' LIMIT 1";
    $res = $xoopsDB->query($sql);
    if (!$res || !is_object($res)) return ['tags' => []];
    $row = $xoopsDB->fetchArray($res);
    if (!$row) return ['tags' => [], 'field_name' => $field_name];

    $field_id = intval($row['field_id']);

    $cat_join  = '';
    $cat_where = '';
    if ($cat_id > 0) {
        $cat_join  = "JOIN {$tbl_items} ii ON ii.item_id = v.value_item_id";
        $cat_where = "AND ii.item_cat_id = " . intval($cat_id) . " AND ii.item_status = 1";
    }

    $sql2 = "SELECT v.value_text AS tag_val, COUNT(*) AS tag_cnt
             FROM {$tbl_values} v
             {$cat_join}
             WHERE v.value_field_id = {$field_id}
             AND v.value_text IS NOT NULL AND v.value_text != ''
             {$cat_where}
             GROUP BY v.value_text
             ORDER BY tag_cnt DESC
             LIMIT " . intval($max_tags);

    $res2 = $xoopsDB->query($sql2);
    $tags  = [];

    if (!$res2 || !is_object($res2)) return ['tags' => []];

    $max_cnt = 1;
    $raw     = [];
    while ($r = $xoopsDB->fetchArray($res2)) {
        // virgülle ayrılmış değerleri de ayır
        $parts = array_map('trim', explode(',', $r['tag_val']));
        foreach ($parts as $p) {
            if ($p === '') continue;
            if (!isset($raw[$p])) $raw[$p] = 0;
            $raw[$p] += $r['tag_cnt'];
        }
    }

    arsort($raw);
    $raw  = array_slice($raw, 0, $max_tags, true);
    if (!empty($raw)) $max_cnt = max($raw);

    foreach ($raw as $tag => $cnt) {
        $size   = round(90 + ($cnt / $max_cnt) * 110); // 90% – 200%
        $weight = $cnt > ($max_cnt * 0.6) ? 'bold' : 'normal';
        $tags[] = [
            'name'   => htmlspecialchars($tag),
            'count'  => $cnt,
            'size'   => $size,
            'weight' => $weight,
            'url'    => XOOPS_URL . '/modules/xcreate/search.php?q=' . urlencode($tag),
        ];
    }

    return ['tags' => $tags, 'field_name' => $field_name];
}

function b_xcreate_w9_tagcloud_edit($options)
{
    $form  = 'Etiket İlave Alan Adı: <input type="text" name="options[0]" value="' . (isset($options[0]) ? htmlspecialchars($options[0]) : 'etiket') . '" size="20"><br>';
    $form .= 'Maksimum Etiket Sayısı: <input type="text" name="options[1]" value="' . (isset($options[1]) ? $options[1] : 30) . '" size="5"><br>';
    $form .= _xcreate_category_select('options[2]', isset($options[2]) ? $options[2] : 0);
    return $form;
}

// ═════════════════════════════════════════════════════════════
// WIDGET 10 – Son Aktivite / Güncellenen İçerikler
// ═════════════════════════════════════════════════════════════
function b_xcreate_w10_activity_show($options)
{
    global $xoopsDB;
    _xcreate_widget_includes();

    $limit      = isset($options[0]) ? intval($options[0]) : 5;
    $cat_id     = isset($options[1]) ? intval($options[1]) : 0;
    $sort_by    = isset($options[2]) ? $options[2] : 'updated'; // 'updated' ya da 'created'

    $itemHandler     = new XcreateItemHandler($xoopsDB);
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);

    $criteria = new CriteriaCompo();
    $criteria->add(new Criteria('item_status', 1));
    if ($cat_id > 0) {
        $criteria->add(new Criteria('item_cat_id', $cat_id));
    }
    $sort_col = ($sort_by === 'created') ? 'item_created' : 'item_updated';
    $criteria->setSort($sort_col);
    $criteria->setOrder('DESC');
    $criteria->setLimit($limit);

    $items_obj = $itemHandler->getObjects($criteria);

    $block     = ['items' => [], 'sort_by' => $sort_by];
    $items_raw = [];

    foreach ($items_obj as $item) {
        $category = $categoryHandler->get($item->getVar('item_cat_id'));
        $row      = _xcreate_build_item_row($item, $category, 80);

        // Aktivite etiketi
        $diff = time() - $item->getVar($sort_col === 'item_updated' ? 'item_updated' : 'item_created');
        if ($diff < 3600) {
            $row['time_label'] = round($diff / 60) . ' dk önce';
        } elseif ($diff < 86400) {
            $row['time_label'] = round($diff / 3600) . ' saat önce';
        } elseif ($diff < 604800) {
            $row['time_label'] = round($diff / 86400) . ' gün önce';
        } else {
            $row['time_label'] = $sort_by === 'updated'
                ? formatTimestamp($item->getVar('item_updated'), 's')
                : formatTimestamp($item->getVar('item_created'), 's');
        }

        $row['is_new']     = ($diff < 86400); // 24 saatten yeni ise "YENİ" etiketi
        $row['is_updated'] = ($sort_by === 'updated' && $item->getVar('item_updated') > $item->getVar('item_created') + 60);

        $block['items'][] = $row;
        $items_raw[]     = $item;
    }

    XcreateFieldsHelper::appendFieldsToList($block['items'], $items_raw);
    return $block;
}

function b_xcreate_w10_activity_edit($options)
{
    $form  = 'Gösterilecek Sayı: <input type="text" name="options[0]" value="' . (isset($options[0]) ? $options[0] : 5) . '" size="5"><br>';
    $form .= _xcreate_category_select('options[1]', isset($options[1]) ? $options[1] : 0) . '<br>';
    $form .= 'Sıralama: <select name="options[2]">';
    $form .= '<option value="updated"' . (!isset($options[2]) || $options[2] === 'updated' ? ' selected' : '') . '>Son Güncellenen</option>';
    $form .= '<option value="created"' . (isset($options[2]) && $options[2] === 'created' ? ' selected' : '') . '>Son Eklenen</option>';
    $form .= '</select>';
    return $form;
}
