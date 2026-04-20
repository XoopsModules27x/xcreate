<?php
/**
 * Admin Items Management
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
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';

$categoryHandler = new XcreateCategoryHandler($xoopsDB);
$itemHandler = new XcreateItemHandler($xoopsDB);
$fieldHandler = new XcreateFieldHandler($xoopsDB);

// field_condition kolonu yoksa otomatik ekle
$_col_res2 = $xoopsDB->query("SHOW COLUMNS FROM " . $xoopsDB->prefix('xcreate_fields') . " LIKE 'field_condition'");
if (!$_col_res2 || !$xoopsDB->fetchArray($_col_res2)) {
    $xoopsDB->queryF("ALTER TABLE " . $xoopsDB->prefix('xcreate_fields') . " ADD COLUMN `field_condition` TEXT DEFAULT NULL AFTER `field_repeatable`");
}

// field_lookup kolonları yoksa otomatik ekle
$_lookup_res2 = $xoopsDB->query("SHOW COLUMNS FROM " . $xoopsDB->prefix('xcreate_fields') . " LIKE 'field_lookup_enabled'");
if (!$_lookup_res2 || !$xoopsDB->fetchArray($_lookup_res2)) {
    $xoopsDB->queryF("ALTER TABLE " . $xoopsDB->prefix('xcreate_fields') . " ADD COLUMN `field_lookup_enabled` TINYINT(1) NOT NULL DEFAULT '0' AFTER `field_condition`");
    $xoopsDB->queryF("ALTER TABLE " . $xoopsDB->prefix('xcreate_fields') . " ADD COLUMN `field_lookup_cat_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `field_lookup_enabled`");
    $xoopsDB->queryF("ALTER TABLE " . $xoopsDB->prefix('xcreate_fields') . " ADD COLUMN `field_lookup_field_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `field_lookup_cat_id`");
}

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : 'list';
$item_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

switch ($op) {
    case 'approve':
        if ($item_id <= 0) {
            redirect_header('items.php', 3, _AM_XCREATE_ITEM_INVALID_ID);
            exit;
        }
        $item = $itemHandler->get($item_id);
        if (!$item || $item->isNew()) {
            redirect_header('items.php', 3, _AM_XCREATE_ITEM_NOT_FOUND);
            exit;
        }
        $item->setVar('item_status', 1);
        $item->setVar('item_published', time());
        
        if ($itemHandler->insert($item)) {
            redirect_header('items.php', 2, _MD_XCREATE_SUCCESS_SAVE);
        } else {
            redirect_header('items.php', 3, _MD_XCREATE_ERROR_SAVE);
        }
        break;
        
    case 'delete':
        if (!isset($_REQUEST['ok']) || $_REQUEST['ok'] != 1) {
            xoops_cp_header();
            $adminObject = \Xmf\Module\Admin::getInstance();
            $adminObject->displayNavigation('items.php');
            
            $item = $itemHandler->get($item_id);
            $category = $categoryHandler->get($item->getVar('item_cat_id'));
            
            echo '<div class="panel panel-danger">';
            echo '<div class="panel-heading"><h3>' . _AM_XCREATE_ITEM_DELETE_TITLE . '</h3></div>';
            echo '<div class="panel-body">';
            echo '<p><strong>' . _AM_XCREATE_ITEM_DELETE_WARNING . '</strong></p>';
            echo '<p><strong>' . _MD_XCREATE_TITLE . ':</strong> ' . $item->getVar('item_title') . '</p>';
            echo '<p><strong>' . _MD_XCREATE_CATEGORY . ':</strong> ' . ($category ? $category->getVar('cat_name') : _AM_XCREATE_NOT_APPLICABLE) . '</p>';
            echo '<div class="alert alert-danger">';
            echo '<p>' . _AM_XCREATE_ITEM_DELETE_IRREVERSIBLE . '</p>';
            echo '</div>';
            
            echo '<div class="mt-3">';
            echo '<a href="items.php?op=delete&id=' . $item_id . '&ok=1" class="btn btn-danger" onclick="return confirm(\'' . addslashes(_AM_XCREATE_ITEM_DELETE_WARNING) . '\');">' . _MD_XCREATE_DELETE . '</a> ';
            echo '<a href="items.php" class="btn btn-secondary">' . _AM_XCREATE_CANCEL . '</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            xoops_cp_footer();
            exit;
        }
        
        // DELETE OPERATION STARTS - Debug
        
        // Delete item and all related field values
        $item = $itemHandler->get($item_id);
        if (!$item || $item->isNew()) {
            redirect_header('items.php', 3, _AM_XCREATE_ITEM_NOT_FOUND);
            exit;
        }
        
        
        // Delete field values first
        global $xoopsDB;
        $delete_values_sql = "DELETE FROM " . $xoopsDB->prefix('xcreate_field_values') . " WHERE value_item_id = " . intval($item_id);
        $result1 = $xoopsDB->queryF($delete_values_sql);
        
        // Delete item - Use force=true parameter
        $result2 = $itemHandler->delete($item, true);
        
        if ($result2) {
            redirect_header('items.php', 2, _MD_XCREATE_SUCCESS_DELETE);
        } else {
            redirect_header('items.php', 3, _MD_XCREATE_ERROR_DELETE);
        }
        break;
        
    case 'add':
    case 'edit':
        xoops_cp_header();
        echo '<link rel="stylesheet" href="' . XOOPS_URL . '/modules/xcreate/assets/css/admin.css">';
        $adminObject = \Xmf\Module\Admin::getInstance();
        $adminObject->displayNavigation('items.php');

        // Load existing item or create new
        if ($op === 'edit' && $item_id > 0) {
            $item = $itemHandler->get($item_id);
            if (!$item || $item->isNew()) {
                redirect_header('items.php', 3, _AM_XCREATE_ITEM_NOT_FOUND);
                exit;
            }
            $cat_id_form = $item->getVar('item_cat_id');
            $form_title = '✏️ ' . sprintf(_AM_XCREATE_ITEM_EDIT_TITLE, $item->getVar('item_title'));
            $existing_values = $itemHandler->getFieldValues($item_id);
        } else {
            $item = $itemHandler->create();
            $cat_id_form = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
            $form_title = '➕ ' . _AM_XCREATE_ITEM_ADD;
            $existing_values = array();
        }

        echo '<div class="xcreate-header"><h2>' . $form_title . '</h2></div>';

        // Category change URL for admin
        $item_id_param = $item_id > 0 ? '&id=' . $item_id : '';
        $op_param = $op;

        $form = new XoopsThemeForm($form_title, 'item_form', 'items.php', 'post', true);
        $form->setExtra('enctype="multipart/form-data"');

        // Category select
        $category_select = new XoopsFormSelect(_MD_XCREATE_CATEGORY, 'item_cat_id', $cat_id_form);
        $category_select->setExtra('id="item_cat_id" onchange="location.href=\'items.php?op=' . $op_param . $item_id_param . '&cat_id=\'+this.value"');
        $categories = $categoryHandler->getTree();
        if ($cat_id_form == 0) {
            $category_select->addOption(0, '-- ' . _MD_XCREATE_CATEGORY_SELECT . ' --');
        }
        foreach ($categories as $cat) {
            $level = intval($cat->getVar('level'));
            $prefix = str_repeat('--', $level);
            $category_select->addOption($cat->getVar('cat_id'), $prefix . ' ' . $cat->getVar('cat_name'));
        }
        $form->addElement($category_select, true);

        // Title
        $form->addElement(new XoopsFormText(_MD_XCREATE_TITLE, 'item_title', 50, 255, $item->getVar('item_title', 'e')), true);
        $form->addElement(new XoopsFormText(_AM_XCREATE_SEO_SLUG_HELP, 'item_slug', 50, 255, $item->getVar('item_slug', 'e')));

        // ---- SEO META ----
        $seo_meta_title = $item->getVar('item_meta_title', 'e');
        $seo_meta_desc  = $item->getVar('item_meta_description', 'e');
        $seo_meta_kw    = $item->getVar('item_meta_keywords', 'e');
        $seo_og_image   = $item->getVar('item_og_image', 'e');
        $seo_canonical  = $item->getVar('item_canonical', 'e');
        $seo_noindex    = (int)$item->getVar('item_noindex');

        ob_start(); ?>
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:18px;margin-top:6px;">
<div style="font-weight:700;color:#7c3aed;margin-bottom:14px;">&#128269; <?php echo _AM_XCREATE_SEO_META; ?></div>
<div style="margin-bottom:10px;">
  <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:3px;"><?php echo _AM_XCREATE_SEO_META_TITLE; ?> <small style="color:#94a3b8;">(<?php echo _AM_XCREATE_SEO_META_TITLE_ITEM_DESC; ?>)</small></label>
  <input type="text" name="item_meta_title" value="<?php echo htmlspecialchars($seo_meta_title, ENT_QUOTES); ?>" maxlength="160" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;box-sizing:border-box;" />
</div>
<div style="margin-bottom:10px;">
  <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:3px;"><?php echo _AM_XCREATE_SEO_META_DESC; ?> <small style="color:#94a3b8;">(<?php echo _AM_XCREATE_SEO_META_DESC_ITEM_DESC; ?>)</small></label>
  <textarea name="item_meta_description" class="no-editor" rows="3" maxlength="320" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;box-sizing:border-box;resize:vertical;"><?php echo htmlspecialchars($seo_meta_desc, ENT_QUOTES); ?></textarea>
</div>
<div style="margin-bottom:10px;">
  <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:3px;"><?php echo _AM_XCREATE_SEO_META_KW; ?> <small style="color:#94a3b8;">(<?php echo _AM_XCREATE_SEO_META_KW_ITEM_DESC; ?>)</small></label>
  <input type="text" name="item_meta_keywords" value="<?php echo htmlspecialchars($seo_meta_kw, ENT_QUOTES); ?>" maxlength="255" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;box-sizing:border-box;" />
</div>
<div style="margin-bottom:10px;">
  <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:3px;"><?php echo _AM_XCREATE_SEO_OG_IMAGE; ?> <small style="color:#94a3b8;">(<?php echo _AM_XCREATE_SEO_OG_IMAGE_ITEM_DESC; ?>)</small></label>
  <input type="text" name="item_og_image" value="<?php echo htmlspecialchars($seo_og_image, ENT_QUOTES); ?>" maxlength="255" placeholder="https://" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;box-sizing:border-box;" />
</div>
<div style="margin-bottom:10px;">
  <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:3px;"><?php echo _AM_XCREATE_SEO_CANONICAL; ?> <small style="color:#94a3b8;">(<?php echo _AM_XCREATE_SEO_CANONICAL_ITEM_DESC; ?>)</small></label>
  <input type="text" name="item_canonical" value="<?php echo htmlspecialchars($seo_canonical, ENT_QUOTES); ?>" maxlength="500" placeholder="https://" style="width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:5px;font-size:13px;box-sizing:border-box;" />
</div>
<div>
  <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;">
    <input type="checkbox" name="item_noindex" value="1" <?php echo $seo_noindex ? 'checked' : ''; ?> />
    <span><?php echo _AM_XCREATE_SEO_NOINDEX_LABEL; ?></span>
  </label>
</div>
</div>
<?php $seo_html = ob_get_clean();
        $form->addElement(new XoopsFormLabel('<span style="color:#7c3aed;font-weight:700;">&#128269; SEO</span>', $seo_html));
        // ---- SEO META SONU ----


        // Description
        $form->addElement(new XoopsFormDhtmlTextArea(_MD_XCREATE_DESCRIPTION, 'item_description', $item->getVar('item_description', 'e'), 10, 50));

        // Status (admin-only field)
        $status_select = new XoopsFormSelect(_AM_XCREATE_ITEM_STATUS, 'item_status', $item->getVar('item_status'));
        $status_select->addOption(0, _AM_XCREATE_STATUS_PENDING);
        $status_select->addOption(1, _AM_XCREATE_STATUS_APPROVED);
        $form->addElement($status_select);

        // Dynamic fields for selected category
        if ($cat_id_form > 0) {
            $fields = $fieldHandler->getFieldsByCategory($cat_id_form);
            foreach ($fields as $field) {
                $field_id    = $field->getVar('field_id');
                $field_label = $field->getVar('field_label');
                $field_type  = $field->getVar('field_type');
                $is_required = $field->getVar('field_required');
                $is_repeatable = $field->getVar('field_repeatable');
                $field_desc  = $field->getVar('field_description');

                $label_text = $field_label . ($is_required ? ' <span style="color:red;">*</span>' : '');
                $field_existing_values = isset($existing_values[$field_id]) ? $existing_values[$field_id] : array();

                if ($is_repeatable) {
                    $html = '<div class="repeatable-field-container">';
                    $html .= '<div id="repeatable_container_' . $field_id . '">';
                    $count = max(1, count($field_existing_values));
                    for ($i = 0; $i < $count; $i++) {
                        $value = '';
                        if (in_array($field_type, array('image', 'file', 'gallery'))) {
                            $value = isset($field_existing_values[$i]['value_file']) ? $field_existing_values[$i]['value_file'] : '';
                        } else {
                            $value = isset($field_existing_values[$i]['value_text']) ? $field_existing_values[$i]['value_text'] : '';
                        }
                        $html .= '<div class="repeatable-field-group" style="border:1px solid #ddd;padding:10px;margin-bottom:8px;position:relative;background:#f9f9f9;">';
                        $html .= '<span style="position:absolute;top:8px;right:10px;color:#7344ef;cursor:pointer;font-weight:bold;" onclick="removeRepeatableField(this)">✖</span>';
                        $html .= $fieldHandler->renderField($field, $value, $i);
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    $temp_field = clone $field;
                    $temp_field->setVar('field_required', 0);
                    $template_html = $fieldHandler->renderField($temp_field, '', 0);
                    $template_html = str_replace(array('[0]', '_0"', '_0 '), array('[__INDEX__]', '__INDEX__"', '__INDEX__ '), $template_html);
                    $html .= '<div id="repeatable_template_' . $field_id . '" class="repeatable-field-group" style="display:none;border:1px solid #ddd;padding:10px;margin-bottom:8px;position:relative;background:#f9f9f9;">';
                    $html .= '<span style="position:absolute;top:8px;right:10px;color:#7344ef;cursor:pointer;font-weight:bold;" onclick="removeRepeatableField(this)">✖</span>';
                    $html .= $template_html;
                    $html .= '</div>';
                    $is_file_upload = in_array($field_type, array('image', 'file'));
                    $html .= '<button type="button" class="btn-customfields" onclick="addRepeatableField(' . $field_id . ', \'field_' . $field_id . '\', ' . ($is_file_upload ? 'true' : 'false') . ')">+ ' . _AM_XCREATE_ADD_FIELD_BUTTON . '</button>';
                    $html .= '</div>';
                    $form->addElement(new XoopsFormLabel($label_text, $html));
                } else {
                    if (in_array($field_type, array('image', 'file', 'gallery'))) {
                        $value = isset($field_existing_values[0]['value_file']) ? $field_existing_values[0]['value_file'] : '';
                    } else {
                        $value = isset($field_existing_values[0]['value_text']) ? $field_existing_values[0]['value_text'] : '';
                    }

                    // Lookup butonu aktifse, input + buton birlikte göster
                    $lookup_enabled  = intval($field->getVar('field_lookup_enabled'));
                    $lookup_cat_id   = intval($field->getVar('field_lookup_cat_id'));
                    $lookup_field_id = intval($field->getVar('field_lookup_field_id'));

                    if ($lookup_enabled && $lookup_cat_id > 0 && !in_array($field_type, array('image','file','gallery','editor'))) {
                        $rendered_input = $fieldHandler->renderField($field, $value, 0);
                        // Input ID'si: field_{field_id}_0
                        $input_id = 'field_' . $field_id . '_0';
                        $lookup_html  = '<div style="display:flex;gap:8px;align-items:flex-start;">';
                        $lookup_html .= '<div style="flex:1;">' . $rendered_input . '</div>';
                        $lookup_html .= '<button type="button" class="btn-customfields" style="white-space:nowrap;background:#0d9488;color:#fff;border:none;padding:7px 14px;border-radius:6px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:5px;" ';
                        $lookup_html .= 'onclick="xcreateOpenLookup(' . $field_id . ', ' . $lookup_cat_id . ', ' . $lookup_field_id . ', \'' . $input_id . '\')">';
                        $lookup_html .= '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>';
                        $lookup_html .= ' ' . _AM_XCREATE_LOOKUP_SELECT . '</button>';
                        $lookup_html .= '</div>';
                        $form->addElement(new XoopsFormLabel($label_text, $lookup_html));
                    } else {
                        $form->addElement(new XoopsFormLabel($label_text, $fieldHandler->renderField($field, $value, 0)));
                    }
                }

                if ($field_desc) {
                    $form->addElement(new XoopsFormLabel('', '<small class="help-block">' . $field_desc . '</small>'));
                }
            }
        }

        // Hidden fields
        $form->addElement(new XoopsFormHidden('op', 'save_admin'));
        if ($item_id > 0) {
            $form->addElement(new XoopsFormHidden('id', $item_id));
        }

        // Buttons
        $button_tray = new XoopsFormElementTray('', '');
        $button_tray->addElement(new XoopsFormButton('', 'submit', _AM_XCREATE_SAVE, 'submit'));
        $button_tray->addElement(new XoopsFormButton('', 'cancel', _AM_XCREATE_CANCEL, 'button', 'onclick="history.go(-1)"'));
        $form->addElement($button_tray);

        // JS for repeatable fields
        echo '<script>
        function addRepeatableField(fieldId, fieldName, isFileUpload) {
            var container = document.getElementById("repeatable_container_" + fieldId);
            var template = document.getElementById("repeatable_template_" + fieldId);
            var newGroup = template.cloneNode(true);
            newGroup.style.display = "block";
            newGroup.id = "";
            var index = container.children.length;
            var html = newGroup.innerHTML;
            html = html.replace(/\[__INDEX__\]/g, "[" + index + "]");
            html = html.replace(/__INDEX__/g, "_" + index);
            newGroup.innerHTML = html;
            var inputs = newGroup.getElementsByTagName("input");
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i].type !== "hidden") { inputs[i].value = ""; }
                if (inputs[i].type === "checkbox" || inputs[i].type === "radio") { inputs[i].checked = false; }
            }
            var textareas = newGroup.getElementsByTagName("textarea");
            for (var i = 0; i < textareas.length; i++) { textareas[i].value = ""; }
            var selects = newGroup.getElementsByTagName("select");
            for (var i = 0; i < selects.length; i++) { selects[i].selectedIndex = 0; }
            container.appendChild(newGroup);
        }
        function removeRepeatableField(btn) {
            var group = btn.parentElement;
            var container = group.parentElement;
            if (container.children.length > 1) { group.remove(); }
            else { alert("' . addslashes(_AM_XCREATE_AT_LEAST_ONE_FIELD) . '"); }
        }
        </script>';

        // ── LOOKUP MODAL HTML + JS ───────────────────────────────────────────
        echo '
<div id="xcreate-lookup-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:99999;background:rgba(0,0,0,0.45);">
  <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:10px;box-shadow:0 8px 40px rgba(0,0,0,0.22);width:620px;max-width:96vw;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;">

    <!-- Header -->
    <div style="display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid #e5e7eb;background:#f8fafc;">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#0d9488" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M3 9h18M9 21V9"/></svg>
      <strong style="font-size:15px;color:#1e293b;" id="xcreate-lookup-title">' . _AM_XCREATE_FIELD_LOOKUP_MODAL_TITLE . '</strong>
      <button onclick="xcreateCloseLookup()" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:20px;color:#64748b;line-height:1;">×</button>
    </div>

    <!-- Search -->
    <div style="padding:14px 20px;border-bottom:1px solid #e5e7eb;display:flex;gap:8px;">
      <input type="text" id="xcreate-lookup-search" placeholder="' . addslashes(_AM_XCREATE_FIELD_LOOKUP_SEARCH) . '" style="flex:1;border:1px solid #d1d5db;border-radius:6px;padding:8px 12px;font-size:14px;outline:none;"
        oninput="xcreateDoLookupSearch()" onkeydown="if(event.key===\'Enter\'){event.preventDefault();xcreateDoLookupSearch();}">
      <button onclick="xcreateDoLookupSearch()" style="background:#0d9488;color:#fff;border:none;border-radius:6px;padding:8px 16px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:5px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
        ' . _AM_XCREATE_FIELD_LOOKUP_SEARCH_BUTTON . '
      </button>
    </div>

    <!-- Results -->
    <div id="xcreate-lookup-results" style="flex:1;overflow-y:auto;min-height:200px;max-height:420px;">
      <div id="xcreate-lookup-loading" style="display:none;text-align:center;padding:30px;color:#64748b;">
        <div style="font-size:24px;margin-bottom:8px;">⏳</div>' . _AM_XCREATE_FIELD_LOOKUP_LOADING . '
      </div>
      <div id="xcreate-lookup-empty" style="display:none;text-align:center;padding:30px;color:#64748b;">
        <div style="font-size:24px;margin-bottom:8px;">🔍</div>' . _AM_XCREATE_FIELD_LOOKUP_EMPTY . '
      </div>
      <table id="xcreate-lookup-table" style="width:100%;border-collapse:collapse;display:none;">
        <thead>
          <tr style="background:#f1f5f9;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">
            <th style="padding:10px 16px;text-align:left;width:60px;">ID</th>
            <th style="padding:10px 16px;text-align:left;">' . _AM_XCREATE_FIELD_LOOKUP_COLUMN_TITLE . '</th>
            <th id="xcreate-lookup-fvcol" style="padding:10px 16px;text-align:left;min-width:130px;display:none;">' . _AM_XCREATE_FIELD_LOOKUP_COLUMN_VALUE . '</th>
            <th style="padding:10px 16px;width:80px;"></th>
          </tr>
        </thead>
        <tbody id="xcreate-lookup-tbody"></tbody>
      </table>
    </div>

    <!-- Footer -->
    <div style="padding:10px 20px;border-top:1px solid #e5e7eb;background:#f8fafc;font-size:12px;color:#94a3b8;" id="xcreate-lookup-count"></div>
  </div>
</div>

<script>
var _xcrLookup = { fieldId: 0, catId: 0, fvId: 0, inputId: \'\', searchTimer: null };

function xcreateOpenLookup(fieldId, catId, fvId, inputId) {
    _xcrLookup.fieldId = fieldId;
    _xcrLookup.catId   = catId;
    _xcrLookup.fvId    = fvId;
    _xcrLookup.inputId = inputId;
    document.getElementById(\'xcreate-lookup-search\').value = \'\';
    document.getElementById(\'xcreate-lookup-modal\').style.display = \'block\';
    // Sütun başlığı: alan değeri varsa göster
    var fvCol = document.getElementById(\'xcreate-lookup-fvcol\');
    if (fvId > 0) { fvCol.style.display = \'\'; } else { fvCol.style.display = \'none\'; }
    xcreateDoLookupSearch();
    setTimeout(function(){ document.getElementById(\'xcreate-lookup-search\').focus(); }, 100);
}

function xcreateCloseLookup() {
    document.getElementById(\'xcreate-lookup-modal\').style.display = \'none\';
}

// ESC ile kapat
document.addEventListener(\'keydown\', function(e){ if(e.key===\'Escape\') xcreateCloseLookup(); });
// Modal dışına tıklayınca kapat
document.getElementById(\'xcreate-lookup-modal\').addEventListener(\'click\', function(e){
    if (e.target === this) xcreateCloseLookup();
});

function xcreateDoLookupSearch() {
    clearTimeout(_xcrLookup.searchTimer);
    _xcrLookup.searchTimer = setTimeout(function(){
        var q = document.getElementById(\'xcreate-lookup-search\').value;
        xcreateRunLookup(q);
    }, 280);
}

function xcreateRunLookup(q) {
    var loading = document.getElementById(\'xcreate-lookup-loading\');
    var tbl     = document.getElementById(\'xcreate-lookup-table\');
    var empty   = document.getElementById(\'xcreate-lookup-empty\');
    var count   = document.getElementById(\'xcreate-lookup-count\');
    loading.style.display = \'block\'; tbl.style.display = \'none\'; empty.style.display = \'none\'; count.textContent = \'\';

    var url = \'' . XOOPS_URL . '/modules/xcreate/ajax/lookup.php\'
            + \'?cat_id=\' + _xcrLookup.catId
            + \'&field_id=\' + _xcrLookup.fvId
            + \'&q=\' + encodeURIComponent(q);

    fetch(url)
        .then(function(r){ return r.json(); })
        .then(function(data){
            loading.style.display = \'none\';
            if (!data.success || !data.items || data.items.length === 0) {
                empty.style.display = \'block\'; return;
            }
            var tbody = document.getElementById(\'xcreate-lookup-tbody\');
            tbody.innerHTML = \'\';
            data.items.forEach(function(item, idx) {
                var tr = document.createElement(\'tr\');
                tr.style.cssText = \'border-bottom:1px solid #f1f5f9;cursor:pointer;\';
                tr.onmouseenter = function(){ this.style.background=\'#f0fdfa\'; };
                tr.onmouseleave = function(){ this.style.background=idx%2===0?\'#fff\':\'#fafafa\'; };
                tr.style.background = idx % 2 === 0 ? \'#fff\' : \'#fafafa\';

                var showVal = _xcrLookup.fvId > 0 ? item.field_value : item.title;

                tr.innerHTML =
                    \'<td style="padding:10px 16px;font-size:13px;color:#64748b;">#\' + item.id + \'</td>\' +
                    \'<td style="padding:10px 16px;font-size:14px;color:#1e293b;font-weight:500;">\' + xcreateEscHtml(item.title) + \'</td>\' +
                    (_xcrLookup.fvId > 0
                        ? \'<td style="padding:10px 16px;font-size:13px;color:#374151;font-weight:700;">\' + xcreateEscHtml(item.field_value || \'—\') + \'</td>\'
                        : \'\') +
                    \'<td style="padding:10px 16px;text-align:right;">\' +
                    \'<button type="button" onclick="xcreatePickLookup(\\\'\' + xcreateEscAttr(showVal) + \'\\\')" \' +
                    \'style="background:#0d9488;color:#fff;border:none;border-radius:5px;padding:5px 13px;cursor:pointer;font-size:12px;font-weight:600;">+ <?php echo addslashes(_AM_XCREATE_FIELD_LOOKUP_SELECT); ?></button>\' +
                    \'</td>\';
                tbody.appendChild(tr);
            });
            tbl.style.display = \'table\';
            count.textContent = data.items.length + \' kayıt listelendi\';
        })
        .catch(function(){ loading.style.display=\'none\'; empty.style.display=\'block\'; });
}

function xcreatePickLookup(value) {
    var input = document.getElementById(_xcrLookup.inputId);
    if (input) {
        input.value = value;
        // Değişiklik event\'i tetikle (conditional fields engine için)
        input.dispatchEvent(new Event(\'input\', { bubbles: true }));
        input.dispatchEvent(new Event(\'change\', { bubbles: true }));
    }
    xcreateCloseLookup();
}

function xcreateEscHtml(str) {
    if (!str) return \'\';
    return String(str).replace(/&/g,\'&amp;\').replace(/</g,\'&lt;\').replace(/>/g,\'&gt;\').replace(/"/g,\'&quot;\');
}
function xcreateEscAttr(str) {
    if (!str) return \'\';
    return String(str).replace(/\'/g, \'&#39;\').replace(/"/g, \'&quot;\');
}
</script>';
        // ── /LOOKUP MODAL ────────────────────────────────────────────────────

        echo $form->render();

        // Conditional fields JS motoru
        if ($cat_id_form > 0) {
            $conditions = $fieldHandler->getConditionsForCategory($cat_id_form);
            echo XcreateFieldHandler::renderConditionEngine($conditions);
        }

        xoops_cp_footer();
        break;

    case 'save_admin':
        // Save from admin panel form
        if (!$GLOBALS['xoopsSecurity']->check()) {
            redirect_header('items.php', 3, implode('<br>', $GLOBALS['xoopsSecurity']->getErrors()));
        }

        if (!isset($_POST['item_cat_id']) || intval($_POST['item_cat_id']) <= 0) {
            redirect_header('items.php', 3, _AM_XCREATE_ITEM_SELECT_CATEGORY);
            exit;
        }
        if (!isset($_POST['item_title']) || trim($_POST['item_title']) === '') {
            redirect_header('items.php?op=' . ($item_id > 0 ? 'edit&id=' . $item_id : 'add'), 3, _AM_XCREATE_ITEM_TITLE_REQUIRED);
            exit;
        }

        if ($item_id > 0) {
            $item = $itemHandler->get($item_id);
            if (!$item || $item->isNew()) {
                redirect_header('items.php', 3, _AM_XCREATE_ITEM_NOT_FOUND);
                exit;
            }
        } else {
            $item = $itemHandler->create();
            $item->setVar('item_created', time());
            $item->setVar('item_uid', $GLOBALS['xoopsUser']->getVar('uid'));
        }

        $item->setVar('item_cat_id', intval($_POST['item_cat_id']));
        $item->setVar('item_title', $_POST['item_title']);
        $item->setVar('item_description', $_POST['item_description']);

        // SEO Slug: admin formdaki değeri kullan, yoksa başlıktan üret
        if (!class_exists('XcreateSlug')) {
            include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/slug.php';
        }
        $submitted_slug = isset($_POST['item_slug']) ? trim($_POST['item_slug']) : '';
        $existing_slug  = $item->getVar('item_slug');
        $item_edit_id   = ($item_id > 0) ? $item_id : 0;
        if (!empty($submitted_slug)) {
            $base_slug = XcreateSlug::create($submitted_slug);
        } elseif (empty($existing_slug)) {
            $base_slug = XcreateSlug::create($_POST['item_title']);
        } else {
            $base_slug = '';
        }
        if (!empty($base_slug)) {
            $unique_slug = XcreateSlug::makeUnique($xoopsDB, 'xcreate_items', 'item_slug', 'item_id', $base_slug, $item_edit_id);
            $item->setVar('item_slug', $unique_slug);
        }

        // Admin her zaman onaylı kaydeder; formdan gelen status değerini kullan
        $new_status = intval($_POST['item_status']);
        $item->setVar('item_status', $new_status);
        $item->setVar('item_updated', time());

        // ---- SEO META KAYIT ----
        $item->setVar('item_meta_title',       isset($_POST['item_meta_title'])       ? trim(strip_tags($_POST['item_meta_title']))       : '');
        $item->setVar('item_meta_description', isset($_POST['item_meta_description']) ? trim(strip_tags($_POST['item_meta_description'])) : '');
        $item->setVar('item_meta_keywords',    isset($_POST['item_meta_keywords'])    ? trim(strip_tags($_POST['item_meta_keywords']))    : '');
        $item->setVar('item_og_image',         isset($_POST['item_og_image'])         ? trim($_POST['item_og_image'])                    : '');
        $item->setVar('item_canonical',        isset($_POST['item_canonical'])        ? trim($_POST['item_canonical'])                   : '');
        $item->setVar('item_noindex',          isset($_POST['item_noindex'])          ? 1 : 0);
        // ---- SEO META KAYIT SONU ----

        // Eğer onaylıysa ve daha önce yayınlanmamışsa yayın tarihini set et
        if ($new_status == 1 && !$item->getVar('item_published')) {
            $item->setVar('item_published', time());
        }

        if ($itemHandler->insert($item)) {
            $new_item_id = $item->getVar('item_id');

            // Process dynamic fields
            $fields = $fieldHandler->getFieldsByCategory(intval($_POST['item_cat_id']));
            $field_values = array();

            // Upload helper for admin
            $upload_dir = XOOPS_ROOT_PATH . '/uploads/xcreate/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $doUpload = function($file_array) use ($upload_dir) {
                $ext = strtolower(pathinfo($file_array['name'], PATHINFO_EXTENSION));
                $allowed = array('jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','zip','rar','txt','csv');
                if (!in_array($ext, $allowed)) return false;
                $new_fn = uniqid() . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file_array['tmp_name'], $upload_dir . $new_fn)) {
                    return $new_fn;
                }
                return false;
            };

            foreach ($fields as $field) {
                $fid        = $field->getVar('field_id');
                $fname      = 'field_' . $fid;
                $ftype      = $field->getVar('field_type');
                $frepeatable = (int)$field->getVar('field_repeatable');

                // Koşullu alan kontrolü
                $fcond_raw = $field->getVar('field_condition');
                if (!empty($fcond_raw)) {
                    $fcond = json_decode($fcond_raw, true);
                    if ($fcond && !empty($fcond['field_id'])) {
                        $ft_id  = intval($fcond['field_id']);
                        $ft_val = isset($_POST['field_' . $ft_id])
                            ? (is_array($_POST['field_' . $ft_id]) ? implode(',', $_POST['field_' . $ft_id]) : trim($_POST['field_' . $ft_id]))
                            : '';
                        $fc_val = isset($fcond['value']) ? $fcond['value'] : '';
                        $fc_op  = isset($fcond['operator']) ? $fcond['operator'] : '==';
                        $fmet   = false;
                        switch ($fc_op) {
                            case '==':        $fmet = ($ft_val === $fc_val); break;
                            case '!=':        $fmet = ($ft_val !== $fc_val); break;
                            case 'contains':  $fmet = (strpos($ft_val, $fc_val) !== false); break;
                            case 'not_empty': $fmet = ($ft_val !== ''); break;
                            default:          $fmet = true;
                        }
                        if (!$fmet) continue; // Koşul sağlanmıyor — kaydetme
                    }
                }

                if (in_array($ftype, array('image', 'file', 'gallery'))) {
                    $uploaded_files = array();
                    $base_fname = 'field_' . $fid; // her zaman "field_7" (index yok)

                    if ($ftype == 'gallery') {
                        // ── GALLERY ──────────────────────────────────────────
                        // Tekli gallery : $_FILES['field_7_gallery']  (multiple)
                        // Repeatable gallery: $_FILES['field_7_gallery_0'], ['field_7_gallery_1'] ...
                        if (!$frepeatable) {
                            $gallery_key = $base_fname . '_gallery';
                            if (isset($_FILES[$gallery_key]) && is_array($_FILES[$gallery_key]['name'])) {
                                foreach ($_FILES[$gallery_key]['name'] as $idx => $file_name) {
                                    if (!empty($file_name) && $_FILES[$gallery_key]['error'][$idx] == UPLOAD_ERR_OK) {
                                        $single = array(
                                            'name'     => $_FILES[$gallery_key]['name'][$idx],
                                            'tmp_name' => $_FILES[$gallery_key]['tmp_name'][$idx],
                                            'error'    => $_FILES[$gallery_key]['error'][$idx],
                                            'size'     => $_FILES[$gallery_key]['size'][$idx],
                                        );
                                        $result = $doUpload($single);
                                        if ($result) $uploaded_files[] = $result;
                                    }
                                }
                            }
                            // Varolan resimleri ekle
                            if (isset($_POST[$base_fname . '_existing']) && is_array($_POST[$base_fname . '_existing'])) {
                                foreach ($_POST[$base_fname . '_existing'] as $ex) {
                                    $ex = trim($ex);
                                    if (!empty($ex)) $uploaded_files[] = $ex;
                                }
                            }
                            // Hepsini tek value'da virgülle birleştir
                            if (!empty($uploaded_files)) {
                                $field_values[$fid] = array(implode(',', $uploaded_files));
                            }
                        } else {
                            // Repeatable gallery: her grup ayrı key
                            $group_idx = 0;
                            $group_results = array();
                            while (true) {
                                $gallery_key = $base_fname . '_gallery_' . $group_idx;
                                // Bu index için existing var mı kontrol et
                                $has_existing = isset($_POST[$base_fname . '_existing'][$group_idx]);
                                $has_upload   = isset($_FILES[$gallery_key]);
                                if (!$has_existing && !$has_upload) break;

                                $group_files = array();
                                if ($has_upload && is_array($_FILES[$gallery_key]['name'])) {
                                    foreach ($_FILES[$gallery_key]['name'] as $fi => $file_name) {
                                        if (!empty($file_name) && $_FILES[$gallery_key]['error'][$fi] == UPLOAD_ERR_OK) {
                                            $single = array(
                                                'name'     => $_FILES[$gallery_key]['name'][$fi],
                                                'tmp_name' => $_FILES[$gallery_key]['tmp_name'][$fi],
                                                'error'    => $_FILES[$gallery_key]['error'][$fi],
                                                'size'     => $_FILES[$gallery_key]['size'][$fi],
                                            );
                                            $result = $doUpload($single);
                                            if ($result) $group_files[] = $result;
                                        }
                                    }
                                }
                                if ($has_existing && is_array($_POST[$base_fname . '_existing'][$group_idx])) {
                                    foreach ($_POST[$base_fname . '_existing'][$group_idx] as $ex) {
                                        $ex = trim($ex);
                                        if (!empty($ex)) $group_files[] = $ex;
                                    }
                                }
                                if (!empty($group_files)) {
                                    $group_results[] = implode(',', $group_files);
                                }
                                $group_idx++;
                            }
                            if (!empty($group_results)) {
                                $field_values[$fid] = $group_results;
                            }
                        }
                    } else {
                        // ── IMAGE / FILE ─────────────────────────────────────
                        // Tekli   : $_FILES['field_7']         single file
                        //           existing: $_POST['field_7_existing']  string
                        // Repeatable: $_FILES['field_7']       ['name'][0], ['name'][1]...
                        //           existing: $_POST['field_7_existing'][0], [1]...
                        if (!$frepeatable) {
                            $f = $_FILES[$base_fname] ?? null;
                            if ($f && !empty($f['name']) && $f['error'] == UPLOAD_ERR_OK) {
                                $result = $doUpload($f);
                                if ($result) $uploaded_files[] = $result;
                            }
                            // Yeni dosya yoksa varolan koru
                            if (empty($uploaded_files) && !empty($_POST[$base_fname . '_existing'])) {
                                $uploaded_files[] = trim($_POST[$base_fname . '_existing']);
                            }
                            if (!empty($uploaded_files)) {
                                $field_values[$fid] = $uploaded_files;
                            }
                        } else {
                            // Repeatable image/file: $_FILES['field_7'] ['name'][0], ['name'][1]...
                            // field_name = "field_7[0]", "field_7[1]" ama FILES key "field_7"
                            $f = $_FILES[$base_fname] ?? null;
                            $existing_arr = isset($_POST[$base_fname . '_existing']) && is_array($_POST[$base_fname . '_existing'])
                                ? $_POST[$base_fname . '_existing'] : array();

                            $max_idx = max(
                                $f ? count($f['name']) : 0,
                                count($existing_arr)
                            );

                            for ($ri = 0; $ri < $max_idx; $ri++) {
                                $new_file = null;
                                if ($f && isset($f['name'][$ri]) && !empty($f['name'][$ri]) && $f['error'][$ri] == UPLOAD_ERR_OK) {
                                    $single = array(
                                        'name'     => $f['name'][$ri],
                                        'tmp_name' => $f['tmp_name'][$ri],
                                        'error'    => $f['error'][$ri],
                                        'size'     => $f['size'][$ri],
                                    );
                                    $new_file = $doUpload($single);
                                }
                                if ($new_file) {
                                    $uploaded_files[] = $new_file;
                                } elseif (isset($existing_arr[$ri]) && !empty(trim($existing_arr[$ri]))) {
                                    $uploaded_files[] = trim($existing_arr[$ri]);
                                }
                            }
                            if (!empty($uploaded_files)) {
                                $field_values[$fid] = $uploaded_files;
                            }
                        }
                    }

                } else {
                    // Text alanlar
                    if (isset($_POST[$fname])) {
                        $values = is_array($_POST[$fname]) ? $_POST[$fname] : array($_POST[$fname]);
                        $processed = array();
                        foreach ($values as $val) {
                            if ($ftype == 'checkbox' && is_array($val)) {
                                $val = implode(',', $val);
                            }
                            if (is_string($val)) $val = trim($val);
                            if ($val !== '' && $val !== null) $processed[] = $val;
                        }
                        if (!empty($processed)) $field_values[$fid] = $processed;
                    }
                }
            }

            if (!empty($field_values)) {
                $itemHandler->saveFieldValues($new_item_id, $field_values);
            }

            redirect_header('items.php', 2, _MD_XCREATE_SUCCESS_SAVE);
        } else {
            redirect_header('items.php', 3, _MD_XCREATE_ERROR_SAVE);
        }
        break;
        
    case 'list':
    default:
        xoops_cp_header();
        
        // Add modern CSS
        echo '<link rel="stylesheet" href="' . XOOPS_URL . '/modules/xcreate/assets/css/admin.css">';
        
        $adminObject = \Xmf\Module\Admin::getInstance();
        $adminObject->displayNavigation('items.php');
        
        echo '<div class="xcreate-header" style="display:flex;justify-content:space-between;align-items:center;">';
        echo '<div><h2>📄 ' . _AM_XCREATE_ITEMS . '</h2>';
        echo '<p>' . _AM_XCREATE_ITEM_MANAGE_HELP . '</p></div>';
        echo '<a href="items.php?op=add" class="btn-customfields" style="text-decoration:none;">➕ ' . _AM_XCREATE_ITEM_ADD . '</a>';
        echo '</div>';
        
        // Filters
        $status_filter = isset($_GET['status']) ? intval($_GET['status']) : -1;
        $cat_filter = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
        
        echo '<div class="panel-customfields" style="margin-bottom: 25px;">';
        echo '<div class="panel-body">';
        echo '<form method="get" action="items.php" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">';
        
        echo '<div>';
        echo '<label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">' . _AM_XCREATE_ITEM_STATUS . ':</label>';
        echo '<select name="status" class="form-control-customfields" style="min-width: 150px;">';
        echo '<option value="-1"' . ($status_filter == -1 ? ' selected' : '') . '>' . _AM_XCREATE_EXPORT_STATUS_ALL . '</option>';
        echo '<option value="0"' . ($status_filter == 0 ? ' selected' : '') . '>' . _AM_XCREATE_STATUS_PENDING . '</option>';
        echo '<option value="1"' . ($status_filter == 1 ? ' selected' : '') . '>' . _AM_XCREATE_STATUS_APPROVED . '</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div>';
        echo '<label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">' . _MD_XCREATE_CATEGORY . ':</label>';
        echo '<select name="cat_id" class="form-control-customfields" style="min-width: 200px;">';
        echo '<option value="0">' . _AM_XCREATE_EXPORT_STATUS_ALL . '</option>';
        $categories = $categoryHandler->getTree();
        foreach ($categories as $cat) {
            $level = intval($cat->getVar('level'));
            $prefix = str_repeat('--', $level);
            $selected = ($cat_filter == $cat->getVar('cat_id')) ? ' selected' : '';
            echo '<option value="' . $cat->getVar('cat_id') . '"' . $selected . '>' . $prefix . ' ' . $cat->getVar('cat_name') . '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        echo '<div>';
        echo '<button type="submit" class="btn-customfields">🔍 ' . _AM_XCREATE_FILTER . '</button>';
        if ($status_filter >= 0 || $cat_filter > 0) {
            echo ' <a href="items.php" class="btn-customfields btn-xcreate-secondary">✕ ' . _AM_XCREATE_CLEAR . '</a>';
        }
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
        echo '</div>';
        
        // Get items
        $criteria = new CriteriaCompo();
        if ($status_filter >= 0) {
            $criteria->add(new Criteria('item_status', $status_filter));
        }
        if ($cat_filter > 0) {
            $criteria->add(new Criteria('item_cat_id', $cat_filter));
        }
        $criteria->setSort('item_created');
        $criteria->setOrder('DESC');
        
        $items = $itemHandler->getObjects($criteria);
        
        if (count($items) > 0) {
            echo '<table class="table-customfields">';
            echo '<thead>';
            echo '<tr>';
            echo '<th style="width: 60px;">ID</th>';
            echo '<th>' . _MD_XCREATE_TITLE . '</th>';
            echo '<th style="width: 150px;">' . _MD_XCREATE_CATEGORY . '</th>';
            echo '<th style="width: 120px;">' . _AM_XCREATE_ITEM_AUTHOR . '</th>';
            echo '<th style="width: 110px; text-align: center;">' . _AM_XCREATE_ITEM_STATUS . '</th>';
            echo '<th style="width: 140px;">' . _AM_XCREATE_ITEM_CREATED . '</th>';
            echo '<th style="width: 80px; text-align: center;">' . _AM_XCREATE_ITEM_HITS . '</th>';
            echo '<th style="width: 220px; text-align: center;">' . _MD_XCREATE_VIEW . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($items as $item) {
                $category = $categoryHandler->get($item->getVar('item_cat_id'));
                $author = new XoopsUser($item->getVar('item_uid'));
                
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
                }
                
                echo '<tr>';
                echo '<td><span class="badge-customfields badge-info">#' . $item->getVar('item_id') . '</span></td>';
                echo '<td><strong>' . $item->getVar('item_title') . '</strong></td>';
                echo '<td>' . ($category ? $category->getVar('cat_name') : 'N/A') . '</td>';
                echo '<td>' . ($author ? $author->getVar('uname') : _AM_XCREATE_GUEST) . '</td>';
                echo '<td style="text-align: center;"><span class="badge-customfields ' . $status_badge . '">' . $status_text . '</span></td>';
                echo '<td><small style="color: #6b7280;">' . formatTimestamp($item->getVar('item_created'), 's') . '</small></td>';
                echo '<td style="text-align: center;"><span class="badge-customfields badge-secondary">' . $item->getVar('item_hits') . '</span></td>';
                echo '<td><div class="action-links" style="justify-content: center;">';
                echo '<a href="' . XOOPS_URL . '/modules/xcreate/item.php?id=' . $item->getVar('item_id') . '" target="_blank">👁️ ' . _MD_XCREATE_VIEW . '</a>';
                echo '<a href="items.php?op=edit&id=' . $item->getVar('item_id') . '">✏️ ' . _MD_XCREATE_EDIT . '</a>';
                if ($item->getVar('item_status') == 0) {
                    echo '<a href="items.php?op=approve&id=' . $item->getVar('item_id') . '" style="color: #10b981;">✓ ' . _AM_XCREATE_ITEM_APPROVE . '</a>';
                }
                echo '<a href="items.php?op=delete&id=' . $item->getVar('item_id') . '" class="delete-link" onclick="return confirm(\'' . _MD_XCREATE_CONFIRM_DELETE . '\')">🗑️ ' . _MD_XCREATE_DELETE . '</a>';
                echo '</div></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<div class="empty-state">';
            echo '<div class="empty-state-icon">📭</div>';
            echo '<div class="empty-state-text">' . _AM_XCREATE_ITEM_NOT_FOUND . '</div>';
            echo '<div class="empty-state-description">';
            if ($status_filter >= 0 || $cat_filter > 0) {
                echo _AM_XCREATE_EMPTY_FILTER_RESULTS;
            } else {
                echo _AM_XCREATE_EMPTY_NO_ITEMS;
            }
            echo '</div>';
            if ($status_filter >= 0 || $cat_filter > 0) {
                echo '<a href="items.php" class="btn-customfields btn-xcreate-secondary">' . _AM_XCREATE_CLEAR_FILTERS . '</a>';
            }
            echo '</div>';
        }
        
        xoops_cp_footer();
        break;
}

?>
