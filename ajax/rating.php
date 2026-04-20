<?php
/**
 * AJAX: Rating kaydet / oku
 * Eren Yumak tarafından kodlanmıştır — Aymak
 */

// PHP hatalarını ve uyarıları tamamen kapat — JSON çıktısını bozmasın
@ini_set('display_errors', 0);
@ini_set('display_startup_errors', 0);
error_reporting(0);

// Output buffer'ı temizleyecek fonksiyon — XOOPS mainfile sonrası çağrılır
function xcreate_json_exit($data) {
    // Biriken tüm output buffer'ları temizle (XOOPS logger dahil)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store');
    // Veriyi UTF-8 güvenli encode et
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// mainfile.php yolunu güvenli bul
$mainfile = '';
$try = array(
    dirname(__FILE__) . '/../../../mainfile.php',
    dirname(__FILE__) . '/../../../../mainfile.php',
);
foreach ($try as $path) {
    if (file_exists($path)) {
        $mainfile = realpath($path);
        break;
    }
}

if (!$mainfile) {
    xcreate_json_exit(array('error' => 'mainfile_not_found'));
}

// XOOPS'u yükle — bu aşamada buffer başlayabilir, önemli değil
ob_start();
include $mainfile;
ob_end_clean(); // mainfile'dan gelen her türlü çıktıyı sil

if (!defined('XOOPS_ROOT_PATH')) {
    xcreate_json_exit(array('error' => 'xoops_not_loaded'));
}

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/rating.php';

$ratingHandler = new XcreateRatingHandler($GLOBALS['xoopsDB']);

$uid = (is_object($xoopsUser) && $xoopsUser->isActive()) ? (int)$xoopsUser->getVar('uid') : 0;
$ip  = '';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
} else {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
}

// ── GET: istatistik oku ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
    if ($item_id < 1) {
        xcreate_json_exit(array('error' => 'invalid_item'));
    }
    xcreate_json_exit($ratingHandler->getRatingData($item_id, $uid, $ip));
}

// ── POST: oy kaydet ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    xcreate_json_exit(array('error' => 'method_not_allowed'));
}

$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$score   = isset($_POST['score'])   ? intval($_POST['score'])   : 0;

if ($item_id < 1 || $score < 1 || $score > 5) {
    xcreate_json_exit(array('error' => 'invalid_params'));
}

$ok = $ratingHandler->saveVote($item_id, $score, $uid, $ip);

if ($ok) {
    $data            = $ratingHandler->getRatingData($item_id, $uid, $ip);
    $data['success'] = 1;
    xcreate_json_exit($data);
} else {
    xcreate_json_exit(array('error' => 'db_error', 'table_exists' => $ratingHandler->isTableReady()));
}
