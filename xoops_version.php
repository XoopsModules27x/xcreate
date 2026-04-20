<?php
/**
 * Xcreate Module for XOOPS
 * Kategori Bazlı Dinamik İçerik Yönetimi
 *
 * @package Xcreate
 * @version 1.51
 */

$modversion = array();

$modversion['name'] = _MI_XCREATE_NAME;
$modversion['version'] = 1.6;
$modversion['description'] = _MI_XCREATE_DESC;
$modversion['credits'] = "Xcreate Content Management Module";
$modversion['author'] = "Eren - Aymak";
$modversion['help'] = "";
$modversion['license'] = "GPL";
$modversion['official'] = 0;
$modversion['image'] = "images/logo.png";
$modversion['dirname'] = "xcreate";

// Admin Menu
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/index.php";
$modversion['adminmenu'] = "admin/menu.php";
$modversion['system_menu'] = 1;

// Main Menu
$modversion['hasMain'] = 1;

// Database Tables
$modversion['sqlfile']['mysql'] = "sql/mysql.sql";
$modversion['tables'][0] = "xcreate_categories";
$modversion['tables'][1] = "xcreate_items";
$modversion['tables'][2] = "xcreate_fields";
$modversion['tables'][3] = "xcreate_field_options";
$modversion['tables'][4] = "xcreate_field_values";
$modversion['tables'][5] = "xcreate_ratings";

// SEO URL güncelleme scripti (mevcut kurulumlar için)
$modversion['sqlfile']['mysql_update'] = "sql/update_seo_slugs.sql";

// Templates
$modversion['templates'][1]['file'] = 'xcreate_index.tpl';
$modversion['templates'][1]['description'] = 'Ana Sayfa';

$modversion['templates'][2]['file'] = 'xcreate_category.tpl';
$modversion['templates'][2]['description'] = 'Kategori Listesi';

$modversion['templates'][3]['file'] = 'xcreate_item.tpl';
$modversion['templates'][3]['description'] = 'İçerik Detay';

$modversion['templates'][4]['file'] = 'xcreate_submit.tpl';
$modversion['templates'][4]['description'] = 'İçerik Gönder';

// Dinamik template'ler için wildcard - özel kategoriler için
// XOOPS modül güncelleme sırasında templates/ klasöründeki tüm .tpl dosyalarını tarayacak
$i = 5;
$template_dir = XOOPS_ROOT_PATH . '/modules/xcreate/templates/';
if (is_dir($template_dir)) {
    $files = glob($template_dir . '*.tpl');
    foreach ($files as $file) {
        $filename = basename($file);
        // Ana template'leri tekrar ekleme
        if (!in_array($filename, array('xcreate_index.tpl', 'xcreate_category.tpl', 'xcreate_item.tpl', 'xcreate_submit.tpl'))) {
            $modversion['templates'][$i]['file'] = $filename;
            $modversion['templates'][$i]['description'] = 'Özel Template: ' . str_replace('.tpl', '', $filename);
            $i++;
        }
    }
}

// Admin Templates
$modversion['templates'][$i]['file'] = 'xcreate_admin_categories.tpl';
$modversion['templates'][$i]['description'] = 'Admin: Kategoriler';
$i++;

$modversion['templates'][$i]['file'] = 'xcreate_admin_fields.tpl';
$modversion['templates'][$i]['description'] = 'Admin: Özel Alanlar';
$i++;

$modversion['templates'][$i]['file']        = 'xcreate_search.tpl';
$modversion['templates'][$i]['description'] = 'Gelişmiş Arama Sayfası';
$i++;

$modversion['templates'][$i]['file'] = 'xcreate_admin_items.tpl';
$modversion['templates'][$i]['description'] = 'Admin: İçerikler';

// Config Options
$modversion['config'][1]['name'] = 'items_per_page';
$modversion['config'][1]['title'] = '_MI_XCREATE_ITEMSPERPAGE';
$modversion['config'][1]['description'] = '_MI_XCREATE_ITEMSPERPAGE_DESC';
$modversion['config'][1]['formtype'] = 'textbox';
$modversion['config'][1]['valuetype'] = 'int';
$modversion['config'][1]['default'] = 10;

$modversion['config'][2]['name'] = 'allow_user_submit';
$modversion['config'][2]['title'] = '_MI_XCREATE_ALLOWSUBMIT';
$modversion['config'][2]['description'] = '_MI_XCREATE_ALLOWSUBMIT_DESC';
$modversion['config'][2]['formtype'] = 'yesno';
$modversion['config'][2]['valuetype'] = 'int';
$modversion['config'][2]['default'] = 1;

$modversion['config'][3]['name'] = 'upload_maxsize';
$modversion['config'][3]['title'] = '_MI_XCREATE_UPLOADSIZE';
$modversion['config'][3]['description'] = '_MI_XCREATE_UPLOADSIZE_DESC';
$modversion['config'][3]['formtype'] = 'textbox';
$modversion['config'][3]['valuetype'] = 'int';
$modversion['config'][3]['default'] = 2048; // KB

$modversion['config'][4]['name'] = 'upload_allowed_ext';
$modversion['config'][4]['title'] = '_MI_XCREATE_ALLOWEDEXT';
$modversion['config'][4]['description'] = '_MI_XCREATE_ALLOWEDEXT_DESC';
$modversion['config'][4]['formtype'] = 'textbox';
$modversion['config'][4]['valuetype'] = 'text';
$modversion['config'][4]['default'] = 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip';

// Blocks
$modversion['blocks'][1]['file'] = "xcreate_blocks.php";
$modversion['blocks'][1]['name'] = _MI_XCREATE_BLOCK_RECENT;
$modversion['blocks'][1]['description'] = _MI_XCREATE_BLOCK_RECENT_DESC;
$modversion['blocks'][1]['show_func'] = "b_xcreate_recent_show";
$modversion['blocks'][1]['edit_func'] = "b_xcreate_recent_edit";
$modversion['blocks'][1]['options'] = "5|0";
$modversion['blocks'][1]['template'] = 'xcreate_block_recent.tpl';

$modversion['blocks'][2]['file']        = "xcreate_filter_block.php";
$modversion['blocks'][2]['name']        = _MI_XCREATE_BLOCK_FILTER;
$modversion['blocks'][2]['description'] = _MI_XCREATE_BLOCK_FILTER_DESC;
$modversion['blocks'][2]['show_func']   = "b_xcreate_filter_show";
$modversion['blocks'][2]['edit_func']   = "b_xcreate_filter_edit";
$modversion['blocks'][2]['options']     = "0|10||1|short|item_created|DESC";
$modversion['blocks'][2]['template']    = "xcreate_block_filter.tpl";


?>
