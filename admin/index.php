<?php
/**
 * Admin Dashboard
 */

include_once '../../../include/cp_header.php';
include_once '../../../class/xoopsformloader.php';

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Load language files
$xoopsModule = XoopsModule::getByDirname('xcreate');
$language = $GLOBALS['xoopsConfig']['language'];
if (file_exists(XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/admin.php")) {
    include_once XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/admin.php";
} else {
    include_once XOOPS_ROOT_PATH . "/modules/xcreate/language/english/admin.php";
}

if (file_exists(XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/main.php")) {
    include_once XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/main.php";
} else {
    include_once XOOPS_ROOT_PATH . "/modules/xcreate/language/english/main.php";
}

xoops_cp_header();

// ---- SEO Meta Kolon Migrasyonu (sadece admin panelinde çalışır) ----
$_seo_items_cols = array(
    'item_meta_title'       => "VARCHAR(160) DEFAULT NULL",
    'item_meta_description' => "VARCHAR(320) DEFAULT NULL",
    'item_meta_keywords'    => "VARCHAR(255) DEFAULT NULL",
    'item_og_image'         => "VARCHAR(255) DEFAULT NULL",
    'item_noindex'          => "TINYINT(1) NOT NULL DEFAULT '0'",
    'item_canonical'        => "VARCHAR(500) DEFAULT NULL",
);
$_seo_items_table = $xoopsDB->prefix('xcreate_items');
foreach ($_seo_items_cols as $_col => $_def) {
    $_chk = @$xoopsDB->query("SHOW COLUMNS FROM `{$_seo_items_table}` LIKE '{$_col}'");
    if (!$_chk || !$xoopsDB->fetchArray($_chk)) {
        @$xoopsDB->queryF("ALTER TABLE `{$_seo_items_table}` ADD COLUMN `{$_col}` {$_def}");
    }
}
$_seo_cats_cols = array(
    'cat_meta_title'       => "VARCHAR(160) DEFAULT NULL",
    'cat_meta_description' => "VARCHAR(320) DEFAULT NULL",
    'cat_meta_keywords'    => "VARCHAR(255) DEFAULT NULL",
    'cat_og_image'         => "VARCHAR(255) DEFAULT NULL",
    'cat_noindex'          => "TINYINT(1) NOT NULL DEFAULT '0'",
);
$_seo_cats_table = $xoopsDB->prefix('xcreate_categories');
foreach ($_seo_cats_cols as $_col => $_def) {
    $_chk = @$xoopsDB->query("SHOW COLUMNS FROM `{$_seo_cats_table}` LIKE '{$_col}'");
    if (!$_chk || !$xoopsDB->fetchArray($_chk)) {
        @$xoopsDB->queryF("ALTER TABLE `{$_seo_cats_table}` ADD COLUMN `{$_col}` {$_def}");
    }
}
unset($_seo_items_cols, $_seo_items_table, $_seo_cats_cols, $_seo_cats_table, $_col, $_def, $_chk);
// ---- SEO Migrasyon SONU ----

// Load modern CSS
echo '<link rel="stylesheet" href="' . XOOPS_URL . '/modules/xcreate/assets/css/admin.css">';

$adminObject = \Xmf\Module\Admin::getInstance();
$adminObject->displayNavigation('index.php');

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';

$categoryHandler = new XcreateCategoryHandler($xoopsDB);
$itemHandler = new XcreateItemHandler($xoopsDB);
$fieldHandler = new XcreateFieldHandler($xoopsDB);

// Statistics
$total_categories = $categoryHandler->getCount();
$total_items = $itemHandler->getCount();
$total_fields = $fieldHandler->getCount();

$criteria = new Criteria('item_status', 0);
$pending_items = $itemHandler->getCount($criteria);

$criteria2 = new Criteria('item_status', 1);
$approved_items = $itemHandler->getCount($criteria2);

// Header
echo '<div class="xcreate-header">';
echo '<h2>🏠 ' . _AM_XCREATE_DASHBOARD . '</h2>';
echo '<p>' . _AM_XCREATE_DASHBOARD_DESC . '</p>';
echo '</div>';

// Statistics Cards
echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">';

// Categories Card
echo '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
echo '<div style="display: flex; align-items: center; justify-content: space-between;">';
echo '<div>';
echo '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">📁 ' . _AM_XCREATE_CATEGORIES . '</div>';
echo '<div style="font-size: 36px; font-weight: 700;">' . $total_categories . '</div>';
echo '</div>';
echo '<div style="font-size: 48px; opacity: 0.3;">📁</div>';
echo '</div>';
echo '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2);">';
echo '<a href="categories.php" style="color: white; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 5px;">' . _AM_XCREATE_MANAGE . ' →</a>';
echo '</div>';
echo '</div>';

// Items Card
echo '<div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
echo '<div style="display: flex; align-items: center; justify-content: space-between;">';
echo '<div>';
echo '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">📄 ' . _AM_XCREATE_TOTAL_CONTENT . '</div>';
echo '<div style="font-size: 36px; font-weight: 700;">' . $total_items . '</div>';
echo '<div style="font-size: 12px; opacity: 0.8; margin-top: 5px;">' . sprintf(_AM_XCREATE_APPROVED_COUNT, $approved_items) . '</div>';
echo '</div>';
echo '<div style="font-size: 48px; opacity: 0.3;">📄</div>';
echo '</div>';
echo '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2);">';
echo '<a href="items.php" style="color: white; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 5px;">' . _AM_XCREATE_MANAGE . ' →</a>';
echo '</div>';
echo '</div>';

// Pending Card
echo '<div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
echo '<div style="display: flex; align-items: center; justify-content: space-between;">';
echo '<div>';
echo '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">⏳ ' . _AM_XCREATE_PENDING_REVIEW . '</div>';
echo '<div style="font-size: 36px; font-weight: 700;">' . $pending_items . '</div>';
echo '<div style="font-size: 12px; opacity: 0.8; margin-top: 5px;">' . _AM_XCREATE_WAITING_APPROVAL . '</div>';
echo '</div>';
echo '<div style="font-size: 48px; opacity: 0.3;">⏳</div>';
echo '</div>';
echo '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2);">';
echo '<a href="items.php?status=0" style="color: white; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 5px;">' . _MD_XCREATE_VIEW . ' →</a>';
echo '</div>';
echo '</div>';

// Fields Card
echo '<div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
echo '<div style="display: flex; align-items: center; justify-content: space-between;">';
echo '<div>';
echo '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">⚙️ ' . _AM_XCREATE_FIELDS . '</div>';
echo '<div style="font-size: 36px; font-weight: 700;">' . $total_fields . '</div>';
echo '<div style="font-size: 12px; opacity: 0.8; margin-top: 5px;">' . _AM_XCREATE_DEFINED_FIELDS . '</div>';
echo '</div>';
echo '<div style="font-size: 48px; opacity: 0.3;">⚙️</div>';
echo '</div>';
echo '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2);">';
echo '<a href="fields.php" style="color: white; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 5px;">' . _AM_XCREATE_MANAGE . ' →</a>';
echo '</div>';
echo '</div>';

echo '</div>'; // stats grid

// Quick Actions
echo '<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 30px;">';
echo '<h3 style="margin: 0 0 20px; color: #1f2937; font-size: 18px; font-weight: 600;">⚡ ' . _AM_XCREATE_QUICK_ACTIONS . '</h3>';
echo '<div style="display: flex; gap: 15px; flex-wrap: wrap;">';
echo '<a href="categories.php?op=add" class="btn-customfields btn-xcreate-success">📁 ' . _AM_XCREATE_NEW_CATEGORY . '</a>';
echo '<a href="fields.php?op=add" class="btn-customfields">🔧 ' . _AM_XCREATE_NEW_FIELD . '</a>';
echo '<a href="items.php?op=add" class="btn-customfields btn-xcreate-info">✍️ ' . _AM_XCREATE_NEW_ITEM . '</a>';
echo '</div>';
echo '</div>';

// Recent Items
$recent_items = $itemHandler->getRecentItems(10, null);

if (count($recent_items) > 0) {
    echo '<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">';
    echo '<h3 style="margin: 0 0 20px; color: #1f2937; font-size: 18px; font-weight: 600;">📋 ' . _MI_XCREATE_BLOCK_RECENT . '</h3>';
    
    echo '<table class="table-customfields">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width: 60px;">ID</th>';
    echo '<th>' . _MD_XCREATE_TITLE . '</th>';
    echo '<th style="width: 150px;">' . _AM_XCREATE_ITEM_AUTHOR . '</th>';
    echo '<th style="width: 120px; text-align: center;">' . _AM_XCREATE_ITEM_STATUS . '</th>';
    echo '<th style="width: 140px;">' . _AM_XCREATE_ITEM_CREATED . '</th>';
    echo '<th style="width: 200px; text-align: center;">' . _AM_XCREATE_ACTIONS . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($recent_items as $item) {
        $status_badge = '';
        $status_text = '';
        
        switch ($item->getVar('item_status')) {
            case 0:
                $status_badge = 'badge-warning';
                $status_text = _AM_XCREATE_STATUS_PENDING;
                break;
            case 1:
                $status_badge = 'badge-success';
                $status_text = _AM_XCREATE_STATUS_APPROVED;
                break;
            case 2:
                $status_badge = 'badge-danger';
                $status_text = _AM_XCREATE_STATUS_REJECTED;
                break;
        }
        
        $author = new XoopsUser($item->getVar('item_uid'));
        
        echo '<tr>';
        echo '<td><span class="badge-customfields badge-info">#' . $item->getVar('item_id') . '</span></td>';
        echo '<td><strong>' . $item->getVar('item_title') . '</strong></td>';
        echo '<td>' . ($author ? $author->getVar('uname') : _AM_XCREATE_GUEST) . '</td>';
        echo '<td style="text-align: center;"><span class="badge-customfields ' . $status_badge . '">' . $status_text . '</span></td>';
        echo '<td><small style="color: #6b7280;">' . formatTimestamp($item->getVar('item_created'), 's') . '</small></td>';
        echo '<td><div class="action-links" style="justify-content: center;">';
        echo '<a href="items.php?op=edit&id=' . $item->getVar('item_id') . '">✏️ ' . _MD_XCREATE_EDIT . '</a>';
        echo '<a href="' . XOOPS_URL . '/modules/xcreate/item.php?id=' . $item->getVar('item_id') . '" target="_blank">👁️ ' . _MD_XCREATE_VIEW . '</a>';
        echo '</div></td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    echo '<div style="margin-top: 20px; text-align: center;">';
    echo '<a href="items.php" class="btn-customfields btn-xcreate-secondary">' . _AM_XCREATE_VIEW_ALL_ITEMS . ' →</a>';
    echo '</div>';
    
    echo '</div>';
} else {
    echo '<div class="empty-state">';
    echo '<div class="empty-state-icon">📭</div>';
    echo '<div class="empty-state-text">' . _AM_XCREATE_NO_CONTENT_YET . '</div>';
    echo '<div class="empty-state-description">' . _AM_XCREATE_CREATE_FIRST_CONTENT . '</div>';
    echo '<a href="items.php?op=add" class="btn-customfields btn-xcreate-success">+ ' . _AM_XCREATE_CREATE_FIRST_CONTENT_BUTTON . '</a>';
    echo '</div>';
}

xoops_cp_footer();

?>
