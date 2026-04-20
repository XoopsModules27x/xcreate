<?php
/**
 * Admin Categories Management
 */

include_once '../../../include/cp_header.php';
include_once '../../../class/xoopsformloader.php';

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Load language files
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

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';

$categoryHandler = new XcreateCategoryHandler($xoopsDB);

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : 'list';
$cat_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

// Helper function to get category tree
function getCategoryTree($parent_id, $handler) {
    $criteria = new Criteria('cat_pid', $parent_id);
    $subcats = $handler->getObjects($criteria);
    $result = array();
    
    foreach ($subcats as $subcat) {
        $result[] = $subcat;
        $children = getCategoryTree($subcat->getVar('cat_id'), $handler);
        $result = array_merge($result, $children);
    }
    
    return $result;
}

switch ($op) {
    case 'save':
        if (!$GLOBALS['xoopsSecurity']->check()) {
            redirect_header('categories.php', 3, implode('<br>', $GLOBALS['xoopsSecurity']->getErrors()));
        }
        
        if ($cat_id > 0) {
            $category = $categoryHandler->get($cat_id);
        } else {
            $category = $categoryHandler->create();
            $category->setVar('cat_created', time());
        }
        
        $category->setVar('cat_pid', $_POST['cat_pid']);
        $category->setVar('cat_name', $_POST['cat_name']);

        // SEO Slug: admin formdaki değeri kullan, yoksa cat_name'den üret
        if (!class_exists('XcreateSlug')) {
            include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/slug.php';
        }
        $submitted_slug = isset($_POST['cat_slug']) ? trim($_POST['cat_slug']) : '';
        $existing_slug  = $category->getVar('cat_slug');
        if (!empty($submitted_slug)) {
            $base_slug = XcreateSlug::create($submitted_slug);
        } elseif (empty($existing_slug)) {
            $base_slug = XcreateSlug::create($_POST['cat_name']);
        } else {
            $base_slug = ''; // değiştirilmeyecek
        }
        if (!empty($base_slug)) {
            $unique_slug = XcreateSlug::makeUnique($xoopsDB, 'xcreate_categories', 'cat_slug', 'cat_id', $base_slug, $cat_id);
            $category->setVar('cat_slug', $unique_slug);
        }
        $category->setVar('cat_description', $_POST['cat_description']);
        $category->setVar('cat_template', isset($_POST['cat_template']) ? trim($_POST['cat_template']) : '');
        $category->setVar('cat_list_template', isset($_POST['cat_list_template']) ? trim($_POST['cat_list_template']) : '');
        $category->setVar('cat_weight', $_POST['cat_weight']);

        // ---- SEO META KAYIT ----
        $category->setVar('cat_meta_title',       isset($_POST['cat_meta_title'])       ? trim(strip_tags($_POST['cat_meta_title']))       : '');
        $category->setVar('cat_meta_description', isset($_POST['cat_meta_description']) ? trim(strip_tags($_POST['cat_meta_description'])) : '');
        $category->setVar('cat_meta_keywords',    isset($_POST['cat_meta_keywords'])    ? trim(strip_tags($_POST['cat_meta_keywords']))    : '');
        $category->setVar('cat_og_image',         isset($_POST['cat_og_image'])         ? trim($_POST['cat_og_image'])                    : '');
        $category->setVar('cat_noindex',          isset($_POST['cat_noindex'])          ? 1 : 0);
        // ---- SEO META KAYIT SONU ----
        $category->setVar('cat_updated', time());
        
        // Handle custom template creation
        if (!empty($_POST['cat_template'])) {
            $template_name = trim($_POST['cat_template']);
            // Ensure .tpl extension
            if (substr($template_name, -4) !== '.tpl') {
                $template_name .= '.tpl';
            }
            
            $template_dir = XOOPS_ROOT_PATH . '/modules/xcreate/templates/';
            if (!is_dir($template_dir)) {
                mkdir($template_dir, 0755, true);
            }
            
            $template_path = $template_dir . $template_name;
            
            // Create template file if it doesn't exist
            if (!file_exists($template_path)) {
                // Copy from default template
                $default_template = XOOPS_ROOT_PATH . '/modules/xcreate/templates/xcreate_item.tpl';
                if (file_exists($default_template)) {
                    copy($default_template, $template_path);
                } else {
                    // Create basic template
                    $template_content = <<<'EOT'
<div class="xcreate-item">
    <div class="item-header">
        <h2>{$item.title}</h2>
    </div>
    
    <div class="item-content">
        <div class="item-description">
            {$item.description}
        </div>
        
        {if $custom_fields}
        <div class="custom-fields">
            {foreach item=field from=$custom_fields}
            <div class="field-group">
                <label class="field-label">{$field.label}:</label>
                <div class="field-values">
                    {foreach item=value from=$field.values}
                    <div class="field-value">{$value}</div>
                    {/foreach}
                </div>
            </div>
            {/foreach}
        </div>
        {/if}
    </div>
</div>
EOT;
                    file_put_contents($template_path, $template_content);
                }
            }
            
            $category->setVar('cat_template', $template_name);
        }
        
        // Handle custom list template creation
        if (!empty($_POST['cat_list_template'])) {
            $list_template_name = trim($_POST['cat_list_template']);
            // Ensure .tpl extension
            if (substr($list_template_name, -4) !== '.tpl') {
                $list_template_name .= '.tpl';
            }
            
            $template_dir = XOOPS_ROOT_PATH . '/modules/xcreate/templates/';
            if (!is_dir($template_dir)) {
                mkdir($template_dir, 0755, true);
            }
            
            $list_template_path = $template_dir . $list_template_name;
            
            // Create list template file if it doesn't exist
            if (!file_exists($list_template_path)) {
                // Copy from default template
                $default_list_template = XOOPS_ROOT_PATH . '/modules/xcreate/templates/xcreate_index.tpl';
                if (file_exists($default_list_template)) {
                    copy($default_list_template, $list_template_path);
                } else {
                    // Create basic list template
                    $list_template_content = <<<'EOT'
<div class="xcreate-category">
    <div class="category-header">
        <h1>{$category.name}</h1>
        {if $category.description}
        <p class="category-description">{$category.description}</p>
        {/if}
    </div>
    
    {if $items}
    <div class="items-grid">
        {foreach item=item from=$items}
        <div class="item-card">
            <h3><a href="item.php?id={$item.id}">{$item.title}</a></h3>
            <div class="item-excerpt">{$item.description|truncate:150}</div>
            <a href="item.php?id={$item.id}" class="read-more">{$smarty.const._MD_XCREATE_READ_MORE} →</a>
        </div>
        {/foreach}
    </div>
    {else}
    <p class="no-items">{$smarty.const._MD_XCREATE_NO_CONTENT}</p>
    {/if}
</div>
EOT;
                    file_put_contents($list_template_path, $list_template_content);
                }
            }
            
            $category->setVar('cat_list_template', $list_template_name);
        }
        
        // Handle image upload
        if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = XOOPS_ROOT_PATH . '/uploads/xcreate/categories/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['cat_image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = 'cat_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['cat_image']['tmp_name'], $target_path)) {
                    $category->setVar('cat_image', $new_filename);
                }
            }
        }
        
        if ($categoryHandler->insert($category)) {
            redirect_header('categories.php', 2, _MD_XCREATE_SUCCESS_SAVE);
        } else {
            redirect_header('categories.php', 3, _MD_XCREATE_ERROR_SAVE);
        }
        break;
        
    case 'delete':
        if (!isset($_REQUEST['ok']) || $_REQUEST['ok'] != 1) {
            xoops_cp_header();
            $adminObject = \Xmf\Module\Admin::getInstance();
            $adminObject->displayNavigation('categories.php');
            
            $category = $categoryHandler->get($cat_id);
            
            echo '<div class="panel panel-danger">';
            echo '<div class="panel-heading"><h3>' . _AM_XCREATE_CATEGORY_DELETE_TITLE . '</h3></div>';
            echo '<div class="panel-body">';
            echo '<p><strong>' . _AM_XCREATE_CATEGORY_DELETE_WARNING . '</strong></p>';
            echo '<p><strong>' . _MD_XCREATE_CATEGORY . ':</strong> ' . $category->getVar('cat_name') . '</p>';
            
            // Check for subcategories
            $criteria = new Criteria('cat_pid', $cat_id);
            $subcategories = $categoryHandler->getObjects($criteria);
            
            if (count($subcategories) > 0) {
                echo '<div class="alert alert-warning">';
                echo '<p><strong>' . sprintf(_AM_XCREATE_CATEGORY_DELETE_HAS_CHILDREN, count($subcategories)) . '</strong></p>';
                echo '<p>' . _AM_XCREATE_CATEGORY_DELETE_CHILDREN_NOTE . '</p>';
                echo '</div>';
            }
            
            // Check for items
            global $xoopsDB;
            $sql = "SELECT COUNT(*) as count FROM " . $xoopsDB->prefix('xcreate_items') . " WHERE item_cat_id = " . $cat_id;
            $result = $xoopsDB->query($sql);
            $row = $result ? $xoopsDB->fetchArray($result) : array('count' => 0);
            $item_count = isset($row['count']) ? $row['count'] : 0;
            
            if ($item_count > 0) {
                echo '<div class="alert alert-danger">';
                echo '<p><strong>' . sprintf(_AM_XCREATE_CATEGORY_DELETE_HAS_ITEMS, $item_count) . '</strong></p>';
                echo '<p>' . _AM_XCREATE_CATEGORY_DELETE_ITEMS_NOTE . '</p>';
                echo '</div>';
            }
            
            echo '<div class="mt-3">';
            echo '<a href="categories.php?op=delete&id=' . $cat_id . '&ok=1" class="btn btn-danger" onclick="return confirm(\'' . addslashes(_AM_XCREATE_CATEGORY_DELETE_WARNING) . '\');">' . _MD_XCREATE_DELETE . '</a> ';
            echo '<a href="categories.php" class="btn btn-secondary">' . _AM_XCREATE_CANCEL . '</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            xoops_cp_footer();
            exit;
        }
        
        // DELETE OPERATION STARTS - Debug
        
        // Delete category and all related data
        $category = $categoryHandler->get($cat_id);
        
        if (!$category || $category->isNew()) {
            redirect_header('categories.php', 3, _AM_XCREATE_CATEGORY_NOT_FOUND);
            exit;
        }
        
        
        // Get all subcategories recursively
        $all_cat_ids = array($cat_id);
        $subcats = getCategoryTree($cat_id, $categoryHandler);
        foreach ($subcats as $subcat) {
            $all_cat_ids[] = $subcat->getVar('cat_id');
        }
        
        // Delete all items in these categories
        global $xoopsDB;
        $cat_ids_str = implode(',', $all_cat_ids);
        
        // Get all item IDs
        $sql = "SELECT item_id FROM " . $xoopsDB->prefix('xcreate_items') . " WHERE item_cat_id IN ($cat_ids_str)";
        $result = $xoopsDB->query($sql);
        if ($result) while ($row = $xoopsDB->fetchArray($result)) {
            $item_id = $row['item_id'];
            // Delete field values
            $xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('xcreate_field_values') . " WHERE value_item_id = " . $item_id);
        }
        
        // Delete items
        $xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('xcreate_items') . " WHERE item_cat_id IN ($cat_ids_str)");
        
        // Delete fields
        $sql = "SELECT field_id FROM " . $xoopsDB->prefix('xcreate_fields') . " WHERE field_cat_id IN ($cat_ids_str)";
        $result = $xoopsDB->query($sql);
        if ($result) while ($row = $xoopsDB->fetchArray($result)) {
            $field_id = $row['field_id'];
            // Delete field options
            $xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('xcreate_field_options') . " WHERE option_field_id = " . $field_id);
        }
        $xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('xcreate_fields') . " WHERE field_cat_id IN ($cat_ids_str)");
        
        // Delete categories
        foreach ($all_cat_ids as $delete_cat_id) {
            $del_cat = $categoryHandler->get($delete_cat_id);
            if ($del_cat && !$del_cat->isNew()) {
                $result = $categoryHandler->delete($del_cat, true);
                if (!$result) {
                }
            }
        }
        
        redirect_header('categories.php', 2, _MD_XCREATE_SUCCESS_DELETE);
        break;
        
    case 'edit':
    case 'add':
        xoops_cp_header();
        
        $adminObject = \Xmf\Module\Admin::getInstance();
        $adminObject->displayNavigation('categories.php');
        
        if ($cat_id > 0) {
            $category = $categoryHandler->get($cat_id);
            $form_title = _AM_XCREATE_CATEGORY_EDIT;
        } else {
            $category = $categoryHandler->create();
            $form_title = _AM_XCREATE_CATEGORY_ADD;
        }
        
        $form = new XoopsThemeForm($form_title, 'category_form', 'categories.php', 'post', true);
        $form->setExtra('enctype="multipart/form-data"');
        
        // Parent category
        $parent_select = new XoopsFormSelect(_AM_XCREATE_CATEGORY_PARENT, 'cat_pid', $category->getVar('cat_pid'));
        $parent_select->addOption(0, _AM_XCREATE_CATEGORY_NONE);
        
        $categories = $categoryHandler->getTree();
        foreach ($categories as $cat) {
            if ($cat->getVar('cat_id') != $cat_id) {
                $level = intval($cat->getVar('level'));
                $prefix = str_repeat('--', $level);
                $parent_select->addOption($cat->getVar('cat_id'), $prefix . ' ' . $cat->getVar('cat_name'));
            }
        }
        $form->addElement($parent_select);
        
        // Name
        $form->addElement(new XoopsFormText(_AM_XCREATE_CATEGORY_NAME, 'cat_name', 50, 255, $category->getVar('cat_name', 'e')), true);
        $form->addElement(new XoopsFormText(_AM_XCREATE_SEO_SLUG_HELP, 'cat_slug', 50, 255, $category->getVar('cat_slug', 'e')));
        
        // Description
        $form->addElement(new XoopsFormTextArea(_AM_XCREATE_CATEGORY_DESC, 'cat_description', $category->getVar('cat_description', 'e'), 5, 50));
        
        // Custom Template (Detail View)
        $template_help = _AM_XCREATE_SEO_ITEM_TEMPLATE_HELP;
        $form->addElement(new XoopsFormText('📄 ' . _AM_XCREATE_SEO_ITEM_TEMPLATE, 'cat_template', 50, 255, $category->getVar('cat_template', 'e')));
        $form->addElement(new XoopsFormLabel('', '<small style="color: #666;">' . $template_help . '</small>'));
        
        // Custom List Template (Index View)
        $list_template_help = _AM_XCREATE_SEO_LIST_TEMPLATE_HELP;
        $form->addElement(new XoopsFormText('📋 ' . _AM_XCREATE_SEO_LIST_TEMPLATE, 'cat_list_template', 50, 255, $category->getVar('cat_list_template', 'e')));
        $form->addElement(new XoopsFormLabel('', '<small style="color: #666;">' . $list_template_help . '<br><em>' . _AM_XCREATE_SEO_LIST_TEMPLATE_TIP . '</em></small>'));
        
        // Image
        $form->addElement(new XoopsFormFile(_AM_XCREATE_CATEGORY_IMAGE, 'cat_image', 2097152));
        if ($category->getVar('cat_image')) {
            $form->addElement(new XoopsFormLabel('', '<img src="' . XOOPS_URL . '/uploads/xcreate/categories/' . $category->getVar('cat_image') . '" style="max-width: 200px;">'));
        }
        
        // Weight

        // ---- SEO META ----
        $cat_seo_mt = $category->getVar('cat_meta_title', 'e');
        $cat_seo_md = $category->getVar('cat_meta_description', 'e');
        $cat_seo_kw = $category->getVar('cat_meta_keywords', 'e');
        $cat_seo_og = $category->getVar('cat_og_image', 'e');
        $cat_seo_ni = (int)$category->getVar('cat_noindex');

        ob_start(); ?>
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:18px;">
<div style="font-weight:700;color:#7c3aed;margin-bottom:12px;">&#128269; <?php echo _AM_XCREATE_SEO_META; ?></div>
<div style="margin-bottom:10px;">
  <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:3px;"><?php echo _AM_XCREATE_SEO_META_TITLE; ?> <small style="color:#94a3b8;">(<?php echo _AM_XCREATE_SEO_META_TITLE_DESC; ?>)</small></label>
  <input type="text" name="cat_meta_title" value="<?php echo htmlspecialchars($cat_seo_mt, ENT_QUOTES); ?>" maxlength="160" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;box-sizing:border-box;" />
</div>
<div style="margin-bottom:10px;">
  <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:3px;"><?php echo _AM_XCREATE_SEO_META_DESC; ?> <small style="color:#94a3b8;">(<?php echo _AM_XCREATE_SEO_META_DESC_DESC; ?>)</small></label>
  <textarea name="cat_meta_description" class="no-editor" rows="3" maxlength="320" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;box-sizing:border-box;resize:vertical;"><?php echo htmlspecialchars($cat_seo_md, ENT_QUOTES); ?></textarea>
</div>
<div style="margin-bottom:10px;">
  <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:3px;"><?php echo _AM_XCREATE_SEO_META_KW; ?></label>
  <input type="text" name="cat_meta_keywords" value="<?php echo htmlspecialchars($cat_seo_kw, ENT_QUOTES); ?>" maxlength="255" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;box-sizing:border-box;" />
</div>
<div style="margin-bottom:10px;">
  <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:3px;"><?php echo _AM_XCREATE_SEO_OG_IMAGE; ?></label>
  <input type="text" name="cat_og_image" value="<?php echo htmlspecialchars($cat_seo_og, ENT_QUOTES); ?>" maxlength="255" placeholder="https://" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;box-sizing:border-box;" />
</div>
<div>
  <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;">
    <input type="checkbox" name="cat_noindex" value="1" <?php echo $cat_seo_ni ? 'checked' : ''; ?> />
    <span><?php echo _AM_XCREATE_SEO_NOINDEX_LABEL; ?></span>
  </label>
</div>
</div>
<?php $cat_seo_html = ob_get_clean();
        $form->addElement(new XoopsFormLabel('<span style="color:#7c3aed;font-weight:700;">&#128269; SEO</span>', $cat_seo_html));
        // ---- SEO META SONU ----

        $form->addElement(new XoopsFormText(_AM_XCREATE_CATEGORY_WEIGHT, 'cat_weight', 10, 10, $category->getVar('cat_weight')));
        
        // Hidden
        $form->addElement(new XoopsFormHidden('op', 'save'));
        if ($cat_id > 0) {
            $form->addElement(new XoopsFormHidden('id', $cat_id));
        }
        
        // Buttons
        $button_tray = new XoopsFormElementTray('', '');
        $button_tray->addElement(new XoopsFormButton('', 'submit', _MD_XCREATE_SUBMIT_BTN, 'submit'));
        $button_tray->addElement(new XoopsFormButton('', 'cancel', _MD_XCREATE_CANCEL_BTN, 'button', 'onclick="history.go(-1)"'));
        $form->addElement($button_tray);
        
        $form->display();
        
        xoops_cp_footer();
        break;
        
    case 'list':
    default:
        xoops_cp_header();
        
        // Add modern CSS
        echo '<link rel="stylesheet" href="' . XOOPS_URL . '/modules/xcreate/assets/css/admin.css">';
        
        $adminObject = \Xmf\Module\Admin::getInstance();
        $adminObject->displayNavigation('categories.php');
        
        echo '<div class="xcreate-header">';
        echo '<h2>📁 ' . _AM_XCREATE_CATEGORIES . '</h2>';
        echo '<p>' . _AM_XCREATE_CATEGORY_MANAGE_HELP . '</p>';
        echo '</div>';
        
        echo '<div class="xcreate-actions">';
        echo '<a href="categories.php?op=add" class="btn-customfields btn-xcreate-success">+ ' . _AM_XCREATE_CATEGORY_ADD . '</a>';
        echo '</div>';
        
        $categories = $categoryHandler->getTree();
        
        if (count($categories) > 0) {
            echo '<table class="table-customfields">';
            echo '<thead>';
            echo '<tr>';
            echo '<th style="width: 80px;">ID</th>';
            echo '<th>' . _AM_XCREATE_CATEGORY_NAME . '</th>';
            echo '<th>' . _AM_XCREATE_CATEGORY_DESC . '</th>';
            echo '<th style="width: 100px; text-align: center;">' . _AM_XCREATE_CATEGORY_WEIGHT . '</th>';
            echo '<th style="width: 250px; text-align: center;">' . _MD_XCREATE_VIEW . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($categories as $category) {
                $level = intval($category->getVar('level'));
                $prefix = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                $indent_icon = str_repeat('└─ ', $level);
                
                echo '<tr>';
                echo '<td><span class="badge-customfields badge-info">#' . $category->getVar('cat_id') . '</span></td>';
                echo '<td><strong>' . $indent_icon . $prefix . $category->getVar('cat_name') . '</strong></td>';
                echo '<td style="color: #6b7280;">' . xoops_substr($category->getVar('cat_description'), 0, 100) . '</td>';
                echo '<td style="text-align: center;"><span class="badge-customfields badge-secondary">' . $category->getVar('cat_weight') . '</span></td>';
                echo '<td><div class="action-links" style="justify-content: center;">';
                echo '<a href="categories.php?op=edit&id=' . $category->getVar('cat_id') . '">✏️ ' . _MD_XCREATE_EDIT . '</a>';
                echo '<a href="fields.php?cat_id=' . $category->getVar('cat_id') . '">📋 ' . _AM_XCREATE_FIELDS . '</a>';
                echo '<a href="categories.php?op=delete&id=' . $category->getVar('cat_id') . '" class="delete-link">🗑️ ' . _MD_XCREATE_DELETE . '</a>';
                echo '</div></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<div class="empty-state">';
            echo '<div class="empty-state-icon">📂</div>';
            echo '<div class="empty-state-text">' . _AM_XCREATE_CATEGORYS_EMPTY_TITLE . '</div>';
            echo '<div class="empty-state-description">' . _AM_XCREATE_CATEGORYS_EMPTY_DESC . '</div>';
            echo '<a href="categories.php?op=add" class="btn-customfields btn-xcreate-success">+ ' . _AM_XCREATE_CATEGORY_ADD . '</a>';
            echo '</div>';
        }
        
        xoops_cp_footer();
        break;
}

?>
