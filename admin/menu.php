<?php
/**
 * Admin Menu
 */

defined('XOOPS_ROOT_PATH') || die('XOOPS root path not defined');

use Xmf\Module\Admin;

// İkon yolunu al (basit yöntem)
$pathIcon32 = Admin::menuIconPath('');
$adminmenu = array();

$adminmenu[] = array(
    'title' => _MI_XCREATE_ADMENU_INDEX,
    'link' => 'admin/index.php',
    'icon' => $pathIcon32 . '/home.png'
);

$adminmenu[] = array(
    'title' => _MI_XCREATE_ADMENU_CATEGORIES,
    'link' => 'admin/categories.php',
    'icon' => $pathIcon32 . '/category.png'
);

$adminmenu[] = array(
    'title' => _MI_XCREATE_ADMENU_FIELDS,
    'link' => 'admin/fields.php',
    'icon' => $pathIcon32 . '/exec.png'
);

$adminmenu[] = array(
    'title' => _MI_XCREATE_ADMENU_ITEMS,
    'link' => 'admin/items.php',
    'icon' => $pathIcon32 . '/add.png'
);

$adminmenu[] = array(
    'title' => _MI_XCREATE_MENU_SUBMIT,
    'link' => '../xcreate/submit.php',
    'icon' => $pathIcon32 . '/globe.png'
);

$adminmenu[] = array(
    'title' => _MI_XCREATE_ADMENU_SEARCH,
    'link'  =>'/search.php',
	'icon' => $pathIcon32 . '/search.png'
);
// ── YENİ: Export / Import ───────────────────────────────────────────────────
$adminmenu[] = array(
    'title' => _MI_XCREATE_ADMENU_EXPORT,
    'link'  => 'admin/export.php',
    'icon'  => $pathIcon32 . '/export.png'
);

$adminmenu[] = array(
    'title' => _MI_XCREATE_ADMENU_IMPORT,
    'link'  => 'admin/import.php',
    'icon'  => $pathIcon32 . '/upload.png'
);
// ── YENİ SON ────────────────────────────────────────────────────────────────

$adminmenu[] = array(
    'title' => _MI_XCREATE_MENU_SUBMIT,
    'link'  => '../xcreate/submit.php',
    'icon'  => $pathIcon32 . '/globe.png'
);
?>
