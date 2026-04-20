<?php
/**
 * Xcreate — Gelişmiş Arama Sayfası
 *
 * Arama kapsamı:
 *   • item_title     — LIKE tam metin
 *   • item_description — LIKE tam metin
 *   • xcreate_field_values.value_text — tüm text tabanlı alan değerleri
 *
 * URL örnekleri:
 *   /modules/xcreate/search.php?q=laptop
 *   /modules/xcreate/search.php?q=laptop&cat_id=3&field_id=7&field_val=Asus&date_from=2024-01-01
 *
 * Eren Yumak tarafından kodlanmıştır — Aymak
 */

include '../../mainfile.php';

$xoopsOption['template_main'] = 'xcreate_search.tpl';

// ── Dil dosyaları ────────────────────────────────────────────────────────────
$language = $GLOBALS['xoopsConfig']['language'];
foreach (['main', 'admin'] as $lf) {
    $path = XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/{$lf}.php";
    include_once file_exists($path) ? $path : XOOPS_ROOT_PATH . "/modules/xcreate/language/english/{$lf}.php";
}

// ── SEO meta — header.php'den önce ──────────────────────────────────────────
$xoopsOption['xoops_pagetitle'] = 'Arama — ' . $GLOBALS['xoopsConfig']['sitename'];
$xoopsMeta['description']       = 'İçerik, başlık ve özel alanlar üzerinde gelişmiş arama.';

include XOOPS_ROOT_PATH . '/header.php';

// ── Handler'lar ──────────────────────────────────────────────────────────────
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/fields_helper.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/rating.php';

$xoopsDB         = $GLOBALS['xoopsDB'];
$categoryHandler = new XcreateCategoryHandler($xoopsDB);
$itemHandler     = new XcreateItemHandler($xoopsDB);
$fieldHandler    = new XcreateFieldHandler($xoopsDB);
$ratingHandler   = new XcreateRatingHandler($xoopsDB);

// ── Girdi temizleme ──────────────────────────────────────────────────────────
$q          = isset($_GET['q'])          ? trim(strip_tags($_GET['q']))          : '';
$cat_id     = isset($_GET['cat_id'])     ? intval($_GET['cat_id'])               : 0;
$field_id   = isset($_GET['field_id'])   ? intval($_GET['field_id'])             : 0;
$field_val  = isset($_GET['field_val'])  ? trim(strip_tags($_GET['field_val']))  : '';
$date_from  = isset($_GET['date_from'])  ? trim($_GET['date_from'])              : '';
$date_to    = isset($_GET['date_to'])    ? trim($_GET['date_to'])                : '';
$sort       = isset($_GET['sort'])       ? $_GET['sort']                         : 'relevance';
$start      = isset($_GET['start'])      ? max(0, intval($_GET['start']))        : 0;
$limit      = isset($xoopsModuleConfig['items_per_page']) ? intval($xoopsModuleConfig['items_per_page']) : 10;

// Güvenli sıralama değerleri
$allowed_sorts = ['relevance', 'newest', 'oldest', 'title_asc', 'title_desc', 'hits'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'relevance';
}

// Tarih doğrulama
$ts_from = 0;
$ts_to   = 0;
if (!empty($date_from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $ts_from = strtotime($date_from . ' 00:00:00');
}
if (!empty($date_to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $ts_to = strtotime($date_to . ' 23:59:59');
}

// ── Kategoriler (form için) ──────────────────────────────────────────────────
$categories    = $categoryHandler->getTree();
$category_list = [];
foreach ($categories as $cat) {
    $pfx = str_repeat('— ', (int)$cat->getVar('level'));
    $category_list[] = [
        'id'   => $cat->getVar('cat_id'),
        'name' => $pfx . $cat->getVar('cat_name'),
    ];
}

// ── Aranabilir alanlar (form için, text tabanlılar) ──────────────────────────
$searchable_types = ['text', 'textarea', 'email', 'url', 'number', 'select', 'radio', 'date', 'datetime'];
$sql_fields = "SELECT field_id, field_label, field_type, field_cat_id
               FROM " . $xoopsDB->prefix('xcreate_fields') . "
               WHERE field_status = 1
                 AND field_type IN ('" . implode("','", $searchable_types) . "')";
if ($cat_id > 0) {
    $sql_fields .= " AND field_cat_id = " . intval($cat_id);
}
$sql_fields .= " ORDER BY field_cat_id, field_label";
$res_fields  = $xoopsDB->query($sql_fields);
$field_list  = [];
while ($row = $xoopsDB->fetchArray($res_fields)) {
    $field_list[] = $row;
}

// ── Arama yapılıyor mu? ──────────────────────────────────────────────────────
$search_performed = ($q !== '' || $cat_id > 0 || ($field_id > 0 && $field_val !== '') || $ts_from || $ts_to);
$results     = [];
$total_found = 0;
$search_took = 0;

if ($search_performed) {
    $t_start = microtime(true);

    // ── WHERE koşulları ──────────────────────────────────────────────────────
    $where_parts = ["i.item_status = 1"];

    // Kategori filtresi
    if ($cat_id > 0) {
        $where_parts[] = "i.item_cat_id = " . intval($cat_id);
    }

    // Tarih aralığı
    if ($ts_from > 0) {
        $where_parts[] = "i.item_created >= " . intval($ts_from);
    }
    if ($ts_to > 0) {
        $where_parts[] = "i.item_created <= " . intval($ts_to);
    }

    // Alan filtresi (EXISTS subquery)
    if ($field_id > 0 && $field_val !== '') {
        $fid_safe = intval($field_id);
        $fv_safe  = $xoopsDB->escape($field_val);
        $where_parts[] = "EXISTS (
            SELECT 1 FROM " . $xoopsDB->prefix('xcreate_field_values') . " fv2
            WHERE fv2.value_item_id = i.item_id
              AND fv2.value_field_id = {$fid_safe}
              AND fv2.value_text LIKE '%{$fv_safe}%'
        )";
    }

    // ── Full-text arama: başlık + açıklama + alan değerleri ─────────────────
    if ($q !== '') {
        $q_safe = $xoopsDB->escape($q);
        // Başlık veya açıklamada eşleşme VEYA herhangi bir alan değerinde eşleşme
        $where_parts[] = "(
            i.item_title       LIKE '%{$q_safe}%'
            OR i.item_description LIKE '%{$q_safe}%'
            OR EXISTS (
                SELECT 1 FROM " . $xoopsDB->prefix('xcreate_field_values') . " fv
                WHERE fv.value_item_id = i.item_id
                  AND fv.value_text    LIKE '%{$q_safe}%'
            )
        )";
    }

    $where_sql = implode(' AND ', $where_parts);

    // ── Sıralama ─────────────────────────────────────────────────────────────
    // "relevance": başlıkta eşleşme önce gelsin
    if ($sort === 'relevance' && $q !== '') {
        $q_safe_ord = $xoopsDB->escape($q);
        $order_sql  = "(i.item_title LIKE '%{$q_safe_ord}%') DESC, i.item_created DESC";
    } elseif ($sort === 'oldest') {
        $order_sql = "i.item_created ASC";
    } elseif ($sort === 'title_asc') {
        $order_sql = "i.item_title ASC";
    } elseif ($sort === 'title_desc') {
        $order_sql = "i.item_title DESC";
    } elseif ($sort === 'hits') {
        $order_sql = "i.item_hits DESC";
    } else {
        $order_sql = "i.item_created DESC";
    }

    // ── Toplam sayı ──────────────────────────────────────────────────────────
    $count_sql = "SELECT COUNT(DISTINCT i.item_id) AS cnt
                  FROM " . $xoopsDB->prefix('xcreate_items') . " i
                  WHERE {$where_sql}";
    $count_res   = $xoopsDB->query($count_sql);
    $count_row   = $xoopsDB->fetchArray($count_res);
    $total_found = intval($count_row['cnt']);

    // ── Sayfalı sonuçlar ─────────────────────────────────────────────────────
    $items_sql = "SELECT DISTINCT i.*
                  FROM " . $xoopsDB->prefix('xcreate_items') . " i
                  WHERE {$where_sql}
                  ORDER BY {$order_sql}
                  LIMIT " . intval($limit) . " OFFSET " . intval($start);
    $items_res = $xoopsDB->query($items_sql);

    $items_raw  = [];
    $item_list  = [];

    while ($row = $xoopsDB->fetchArray($items_res)) {
        $obj = $itemHandler->create(false);
        $obj->assignVars($row);

        $cat      = $categoryHandler->get($obj->getVar('item_cat_id'));
        $cat_slug = $cat ? $cat->getVar('cat_slug') : '';
        $itm_slug = $obj->getVar('item_slug');

        // Özet: açıklamadan snippet üret, arama terimini vurgula
        $raw_desc = strip_tags($obj->getVar('item_description'));
        $snippet  = _xcreate_snippet($raw_desc, $q, 200);

        $item_list[] = [
            'id'          => $obj->getVar('item_id'),
            'slug'        => $itm_slug,
            'title'       => _xcreate_highlight($obj->getVar('item_title'), $q),
            'title_raw'   => $obj->getVar('item_title'),
            'description' => $snippet,
            'author'      => '',   // appendFieldsToList aşağıda doldurur
            'created'     => formatTimestamp($obj->getVar('item_created'), 's'),
            'hits'        => $obj->getVar('item_hits'),
            'cat_name'    => $cat ? $cat->getVar('cat_name') : '',
            'cat_slug'    => $cat_slug,
            'url'         => (!empty($cat_slug) && !empty($itm_slug))
                                ? XOOPS_URL . '/modules/xcreate/' . $cat_slug . '/' . $itm_slug
                                : XOOPS_URL . '/modules/xcreate/item.php?id=' . $obj->getVar('item_id'),
            'rating'      => $ratingHandler->getStats($obj->getVar('item_id')),
        ];
        $items_raw[] = $obj;
    }

    // İlave alan değerlerini ekle (snippet içinde highlight için kullanılacak)
    XcreateFieldsHelper::appendFieldsToList($item_list, $items_raw);

    // Alan değeri snippet'larına da highlight uygula
    if ($q !== '') {
        foreach ($item_list as &$itm) {
            if (!empty($itm['fields'])) {
                foreach ($itm['fields'] as &$fld) {
                    if (!empty($fld['value'])) {
                        $fld['value_highlighted'] = _xcreate_highlight(htmlspecialchars($fld['value'], ENT_QUOTES), $q);
                    }
                }
                unset($fld);
            }
        }
        unset($itm);
    }

    $results    = $item_list;
    $search_took = round((microtime(true) - $t_start) * 1000); // ms
}

// ── Yardımcı fonksiyonlar ────────────────────────────────────────────────────

/**
 * Metni kısaltır, arama terimini ortada tutar.
 */
function _xcreate_snippet($text, $q, $maxlen = 200)
{
    $text = preg_replace('/\s+/', ' ', trim($text));
    if ($q === '' || mb_strlen($text) <= $maxlen) {
        return htmlspecialchars(mb_substr($text, 0, $maxlen), ENT_QUOTES) . (mb_strlen($text) > $maxlen ? '…' : '');
    }
    $pos = mb_stripos($text, $q);
    if ($pos === false) {
        return htmlspecialchars(mb_substr($text, 0, $maxlen), ENT_QUOTES) . '…';
    }
    $pad   = intval(($maxlen - mb_strlen($q)) / 2);
    $from  = max(0, $pos - $pad);
    $chunk = mb_substr($text, $from, $maxlen);
    $html  = htmlspecialchars($chunk, ENT_QUOTES);
    $html  = _xcreate_highlight($html, htmlspecialchars($q, ENT_QUOTES));
    return ($from > 0 ? '…' : '') . $html . '…';
}

/**
 * Arama terimini <mark> ile vurgular (XSS-safe: input zaten htmlspecialchars'lı olmalı).
 */
function _xcreate_highlight($html, $q)
{
    if ($q === '') return $html;
    $safe_q = preg_quote(htmlspecialchars($q, ENT_QUOTES), '/');
    return preg_replace('/(' . $safe_q . ')/iu', '<mark>$1</mark>', $html);
}

// ── Pagination ───────────────────────────────────────────────────────────────
$page_extra = 'q=' . urlencode($q)
    . ($cat_id   ? '&cat_id='    . $cat_id    : '')
    . ($field_id ? '&field_id='  . $field_id  : '')
    . ($field_val !== '' ? '&field_val=' . urlencode($field_val) : '')
    . ($date_from !== '' ? '&date_from=' . urlencode($date_from) : '')
    . ($date_to   !== '' ? '&date_to='   . urlencode($date_to)   : '')
    . ($sort !== 'relevance' ? '&sort=' . $sort : '');

include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
$pagenav = $search_performed
    ? (new XoopsPageNav($total_found, $limit, $start, 'start', $page_extra))->renderNav()
    : '';

// ── Smarty atamaları ─────────────────────────────────────────────────────────
$xoopsTpl->assign('xcreate_search', [
    'q'                => htmlspecialchars($q, ENT_QUOTES),
    'cat_id'           => $cat_id,
    'field_id'         => $field_id,
    'field_val'        => htmlspecialchars($field_val, ENT_QUOTES),
    'date_from'        => htmlspecialchars($date_from, ENT_QUOTES),
    'date_to'          => htmlspecialchars($date_to,   ENT_QUOTES),
    'sort'             => $sort,
    'categories'       => $category_list,
    'fields'           => $field_list,
    'results'          => $results,
    'total'            => $total_found,
    'start'            => $start,
    'limit'            => $limit,
    'search_performed' => $search_performed,
    'search_took_ms'   => $search_took,
    'pagenav'          => $pagenav,
    'module_url'       => XOOPS_URL . '/modules/xcreate',
    'sorts'            => [
        'relevance'  => 'İlgililik',
        'newest'     => 'En Yeni',
        'oldest'     => 'En Eski',
        'title_asc'  => 'Başlık A-Z',
        'title_desc' => 'Başlık Z-A',
        'hits'       => 'Çok Görüntülenen',
    ],
]);

include XOOPS_ROOT_PATH . '/footer.php';
