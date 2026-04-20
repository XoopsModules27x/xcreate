<?php
/**
 * Xcreate - Kategoriye ait alanları döner (Lookup ayarları için)
 * URL: /modules/xcreate/ajax/get_cat_fields.php?cat_id=N
 *
 * Dönen JSON:
 *   { success: true, fields: [ { id, label, type }, ... ] }
 */

@ini_set('display_errors', 0);
@ini_set('display_startup_errors', 0);
error_reporting(0);

function xcreate_gcf_exit($data) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

$mainfile = '';
$try = array(
    dirname(__FILE__) . '/../../../mainfile.php',
    dirname(__FILE__) . '/../../../../mainfile.php',
);
foreach ($try as $path) {
    if (file_exists($path)) { $mainfile = realpath($path); break; }
}
if (!$mainfile) { xcreate_gcf_exit(array('success' => false, 'error' => 'mainfile_not_found')); }

ob_start();
include $mainfile;
ob_end_clean();

if (!defined('XOOPS_ROOT_PATH')) {
    xcreate_gcf_exit(array('success' => false, 'error' => 'xoops_not_loaded'));
}

if (!is_object($xoopsUser) || !$xoopsUser->isAdmin()) {
    xcreate_gcf_exit(array('success' => false, 'error' => 'Yetkisiz erişim'));
}

$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

if ($cat_id <= 0) {
    xcreate_gcf_exit(array('success' => true, 'fields' => array()));
}

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';
$fieldHandler = new XcreateFieldHandler($GLOBALS['xoopsDB']);
$fields = $fieldHandler->getFieldsByCategory($cat_id, null);

$result = array();
foreach ($fields as $f) {
    $result[] = array(
        'id'    => (int)$f->getVar('field_id'),
        'label' => $f->getVar('field_label'),
        'type'  => $f->getVar('field_type'),
    );
}

xcreate_gcf_exit(array('success' => true, 'fields' => $result));
