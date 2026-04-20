<?php
/**
 * Item Detail Page
 * SEO URL desteği: /modules/xcreate/kategori-slug/icerik-slug
 * Geriye dönük: /modules/xcreate/item.php?id=X
 */

include '../../mainfile.php';

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/slug.php';

$itemHandler     = new XcreateItemHandler($GLOBALS['xoopsDB']);
$categoryHandler = new XcreateCategoryHandler($GLOBALS['xoopsDB']);

// --- ID veya Slug ile item bul ---
$item    = null;
$item_id = 0;

if (!empty($_GET['item_slug'])) {
    // SEO URL: item_slug ve cat_slug GET parametreleri (.htaccess tarafından set edilir)
    $item = $itemHandler->getBySlug($_GET['item_slug']);
    if ($item && !$item->isNew()) {
        $item_id = $item->getVar('item_id');
    }
} elseif (!empty($_GET['id'])) {
    // Geriye dönük uyumluluk: ?id=X
    $item_id = intval($_GET['id']);
    $item    = $itemHandler->get($item_id);
}

// Template belirleme
if ($item && !$item->isNew()) {
    $category       = $categoryHandler->get($item->getVar('item_cat_id'));
    $custom_template = $category ? $category->getVar('cat_template') : '';
    $xoopsOption['template_main'] = !empty($custom_template) ? $custom_template : 'xcreate_item.tpl';
} else {
    $xoopsOption['template_main'] = 'xcreate_item.tpl';
}

// ---- SEO META: header.php'den önce xoops meta değişkenlerini ata ----
// (XOOPS header.php bu değişkenlere bakarak <head> çıktısını üretir)
// Önce item'ın mevcut meta başlığını geçici olarak yükleyebilmek için
// sadece item'ı okuyoruz; $item aşağıda tam yükleniyor.
// Burada sadece template belirleme yapılmış, item henüz tam yüklü değil.
// Gerçek meta ataması header.php SONRASI item yüklendikten sonra yapılır,
// ancak XOOPS'ta xoopsMeta header.php öncesinde set edilmeli.
// Bu nedenle item'ı erken yükleyip meta'yı set ediyoruz.

// Erken item yüklemesi (sadece meta için — aşağıda tekrar kullanılır)
if (!isset($item) || !$item) {
    if (!empty($_GET['item_slug'])) {
        $item = $itemHandler->getBySlug($_GET['item_slug']);
        if ($item && !$item->isNew()) {
            $item_id = $item->getVar('item_id');
        }
    } elseif (!empty($_GET['id'])) {
        $item_id = intval($_GET['id']);
        $item = $itemHandler->get($item_id);
    }
}

if ($item && !$item->isNew()) {
    $category = isset($category) ? $category : $categoryHandler->get($item->getVar('item_cat_id'));

    // --- Meta Title ---
    $meta_title = trim($item->getVar('item_meta_title'));
    if (empty($meta_title)) {
        $meta_title = $item->getVar('item_title');
        if ($category && !$category->isNew()) {
            $meta_title .= ' — ' . $category->getVar('cat_name');
        }
    }

    // --- Meta Description ---
    $meta_desc = trim($item->getVar('item_meta_description'));
    if (empty($meta_desc)) {
        $raw_desc = strip_tags($item->getVar('item_description'));
        $meta_desc = mb_substr(preg_replace('/\s+/', ' ', $raw_desc), 0, 155);
        if (mb_strlen($raw_desc) > 155) $meta_desc .= '…';
    }

    // --- Meta Keywords ---
    $meta_kw = trim($item->getVar('item_meta_keywords'));

    // --- Canonical URL ---
    $canonical_url = trim($item->getVar('item_canonical'));
    if (empty($canonical_url)) {
        $cat_slug_early  = ($category && !$category->isNew()) ? $category->getVar('cat_slug') : '';
        $item_slug_early = $item->getVar('item_slug');
        if (!empty($cat_slug_early) && !empty($item_slug_early)) {
            $canonical_url = XOOPS_URL . '/modules/xcreate/' . $cat_slug_early . '/' . $item_slug_early;
        } else {
            $canonical_url = XOOPS_URL . '/modules/xcreate/item.php?id=' . $item->getVar('item_id');
        }
    }

    // --- OG Image ---
    $og_image = trim($item->getVar('item_og_image'));
    if (empty($og_image) && $category && !$category->isNew() && $category->getVar('cat_og_image')) {
        $og_image = $category->getVar('cat_og_image');
    }

    // --- noindex ---
    $item_noindex = (int)$item->getVar('item_noindex');

    // XOOPS meta atamaları
    $xoopsMeta['description'] = htmlspecialchars($meta_desc, ENT_QUOTES);
    $xoopsMeta['keywords']    = htmlspecialchars($meta_kw, ENT_QUOTES);

    // Page title — XOOPS bunu <title> için kullanır
    $xoopsOption['xoops_pagetitle'] = htmlspecialchars($meta_title, ENT_QUOTES);
}
// ----- SEO META SONU -----

include XOOPS_ROOT_PATH . '/header.php';

// Load language files
$language = $GLOBALS['xoopsConfig']['language'];
if (file_exists(XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/main.php")) {
    include_once XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/main.php";
} else {
    include_once XOOPS_ROOT_PATH . "/modules/xcreate/language/english/main.php";
}

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/fields_helper.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/rating.php';

$fieldHandler  = new XcreateFieldHandler($xoopsDB);
$ratingHandler = new XcreateRatingHandler($xoopsDB);

if ($item_id == 0 || !$item || $item->isNew()) {
    redirect_header(XOOPS_URL . '/modules/xcreate/', 3, _MD_XCREATE_ERROR_NOTFOUND);
}

// Check status
$isAdmin = (is_object($xoopsUser) && $xoopsUser->isAdmin());
if ($item->getVar('item_status') != 1 && !$isAdmin) {
    if (!is_object($xoopsUser) || $item->getVar('item_uid') != $xoopsUser->getVar('uid')) {
        redirect_header(XOOPS_URL . '/modules/xcreate/', 3, _MD_XCREATE_ERROR_PERMISSION);
    }
}

// Canonical SEO URL yönlendirmesi:
// Eğer eski ?id= formatıyla girilmişse, SEO URL'ye kalıcı yönlendir
if (!empty($_GET['id']) && empty($_GET['item_slug'])) {
    $cat_s  = $category ? $category->getVar('cat_slug') : '';
    $item_s = $item->getVar('item_slug');
    if (!empty($cat_s) && !empty($item_s)) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . XOOPS_URL . '/modules/xcreate/' . $cat_s . '/' . $item_s);
        exit();
    }
}

// Update hits
$itemHandler->updateHits($item_id);

// Get category (zaten yüklü olabilir)
if (!isset($category) || !$category) {
    $category = $categoryHandler->get($item->getVar('item_cat_id'));
}

// Get author
$author = new XoopsUser($item->getVar('item_uid'));


// İlave alanları helper ile oluştur
// $item_fields: $item dizisine merge edilecek ham değerler (geriye uyumluluk)
$built_fields = XcreateFieldsHelper::buildFields($item_id, $item->getVar('item_cat_id'));
$item_fields  = array();
foreach ($built_fields as $name => $data) {
    $item_fields[$name] = $data['value'];
}


// Breadcrumb — SEO URL ile
$breadcrumb = array();
$path = $categoryHandler->getParentPath($item->getVar('item_cat_id'));
foreach ($path as $cat) {
    $cat_slug_bc = $cat->getVar('cat_slug');
    $breadcrumb[] = array(
        'name' => $cat->getVar('cat_name'),
        'url'  => !empty($cat_slug_bc)
                    ? XOOPS_URL . '/modules/xcreate/' . $cat_slug_bc . '/'
                    : XOOPS_URL . '/modules/xcreate/index.php?cat_id=' . $cat->getVar('cat_id')
    );
}

// SEO URL'ler
$cat_slug_val  = $category ? $category->getVar('cat_slug') : '';
$item_slug_val = $item->getVar('item_slug');

$xoopsTpl->assign('item', array_merge(array(
    'id'          => $item->getVar('item_id'),
    'slug'        => $item_slug_val,
    'title'       => $item->getVar('item_title'),
    'description' => $item->getVar('item_description'),
    'author'      => $author ? $author->getVar('uname') : 'Guest',
    'author_id'   => $item->getVar('item_uid'),
    'created'     => formatTimestamp($item->getVar('item_created'), 's'),
    'updated'     => formatTimestamp($item->getVar('item_updated'), 's'),
    'hits'        => $item->getVar('item_hits'),
    'url'         => (!empty($cat_slug_val) && !empty($item_slug_val))
                        ? XOOPS_URL . '/modules/xcreate/' . $cat_slug_val . '/' . $item_slug_val
                        : XOOPS_URL . '/modules/xcreate/item.php?id=' . $item->getVar('item_id'),
    'can_edit'    => (is_object($xoopsUser) && ($xoopsUser->isAdmin() || $item->getVar('item_uid') == $xoopsUser->getVar('uid')))
), $item_fields));

$xoopsTpl->assign('category', array(
    'id'   => $category->getVar('cat_id'),
    'slug' => $cat_slug_val,
    'name' => $category->getVar('cat_name'),
    'url'  => !empty($cat_slug_val)
                ? XOOPS_URL . '/modules/xcreate/' . $cat_slug_val . '/'
                : XOOPS_URL . '/modules/xcreate/index.php?cat_id=' . $category->getVar('cat_id')
));

$xoopsTpl->assign('breadcrumb', $breadcrumb);
$xoopsTpl->assign('module_url', XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname'));

// Helper ile tüm alan değişkenlerini Smarty'e ata
XcreateFieldsHelper::assignItemFields($xoopsTpl, $item_id, $item->getVar('item_cat_id'));

// Rating verilerini Smarty'e ata
$uid_current = is_object($xoopsUser) ? (int)$xoopsUser->getVar('uid') : 0;
$ip_current  = isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0])
                : $_SERVER['REMOTE_ADDR'];
$rating_data = $ratingHandler->getRatingData($item_id, $uid_current, $ip_current);
$xoopsTpl->assign('rating', $rating_data);
$xoopsTpl->assign('xoops_token', $GLOBALS['xoopsSecurity']->createToken());


// ---- SEO Smarty Atamaları (template'de kullanım için) ----
if (isset($item) && $item && !$item->isNew()) {
    $xoopsTpl->assign('seo', array(
        'title'       => isset($meta_title)   ? htmlspecialchars($meta_title, ENT_QUOTES)  : '',
        'description' => isset($meta_desc)    ? htmlspecialchars($meta_desc, ENT_QUOTES)   : '',
        'keywords'    => isset($meta_kw)      ? htmlspecialchars($meta_kw, ENT_QUOTES)     : '',
        'canonical'   => isset($canonical_url)? htmlspecialchars($canonical_url, ENT_QUOTES): '',
        'og_image'    => isset($og_image)     ? $og_image                                  : '',
        'noindex'     => isset($item_noindex) ? (bool)$item_noindex                        : false,
        'og_type'     => 'article',
        'site_name'   => $GLOBALS['xoopsConfig']['sitename'],
    ));
}
// ---- SEO Smarty Atamaları SONU ----

include XOOPS_ROOT_PATH . '/footer.php';
