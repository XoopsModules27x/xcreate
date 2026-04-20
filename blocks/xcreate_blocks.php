<?php
/**
 * Blocks for Xcreate Module
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

$blockLanguage = XOOPS_ROOT_PATH . '/modules/xcreate/language/' . $GLOBALS['xoopsConfig']['language'] . '/blocks.php';
include_once file_exists($blockLanguage) ? $blockLanguage : XOOPS_ROOT_PATH . '/modules/xcreate/language/english/blocks.php';

function b_xcreate_recent_show($options)
{
    global $xoopsDB;
    
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/fields_helper.php';
    
    $itemHandler = new XcreateItemHandler($xoopsDB);
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);
    
    $limit = isset($options[0]) ? intval($options[0]) : 5;
    $cat_id = isset($options[1]) ? intval($options[1]) : 0;
    
    if ($cat_id > 0) {
        $items = $itemHandler->getItemsByCategory($cat_id, 1, $limit);
    } else {
        $items = $itemHandler->getRecentItems($limit);
    }
    
    $block = array();
    $block['items'] = array();
    $items_raw_block = array();

    foreach ($items as $item) {
        $category = $categoryHandler->get($item->getVar('item_cat_id'));

        $block['items'][] = array(
            'id'           => $item->getVar('item_id'),
            'title'        => $item->getVar('item_title'),
            'description'  => xoops_substr(strip_tags($item->getVar('item_description')), 0, 100),
            'category'     => $category->getVar('cat_name'),
            'category_url' => !empty($category->getVar('cat_slug')) ? XOOPS_URL . '/modules/xcreate/' . $category->getVar('cat_slug') . '/' : XOOPS_URL . '/modules/xcreate/index.php?cat_id=' . $category->getVar('cat_id'),
            'created'      => formatTimestamp($item->getVar('item_created'), 's'),
            'url'          => (!empty($category->getVar('cat_slug')) && !empty($item->getVar('item_slug'))) ? XOOPS_URL . '/modules/xcreate/' . $category->getVar('cat_slug') . '/' . $item->getVar('item_slug') : XOOPS_URL . '/modules/xcreate/item.php?id=' . $item->getVar('item_id')
        );

        $items_raw_block[] = $item;
    }

    // İlave alanları blok item listesine ekle
    XcreateFieldsHelper::appendFieldsToList($block['items'], $items_raw_block);
    
    return $block;
}

function b_xcreate_recent_edit($options)
{
    global $xoopsDB;
    
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
    $categoryHandler = new XcreateCategoryHandler($xoopsDB);
    
    $form = _MB_XCREATE_RECENT_LIMIT . ': <input type="text" name="options[0]" value="' . $options[0] . '" size="5"><br>';
    
    $form .= _MB_XCREATE_BLOCK_CATEGORY . ': <select name="options[1]">';
    $form .= '<option value="0"' . ($options[1] == 0 ? ' selected' : '') . '>' . _MB_XCREATE_BLOCK_ALL . '</option>';
    
    $categories = $categoryHandler->getTree();
    foreach ($categories as $category) {
        $prefix = str_repeat('--', $category->getVar('level'));
        $selected = ($options[1] == $category->getVar('cat_id')) ? ' selected' : '';
        $form .= '<option value="' . $category->getVar('cat_id') . '"' . $selected . '>' . $prefix . ' ' . $category->getVar('cat_name') . '</option>';
    }
    
    $form .= '</select>';
    
    return $form;
}

?>
