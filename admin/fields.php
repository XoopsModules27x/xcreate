<?php
/**
 * Admin Xcreate Management
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
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';

$categoryHandler = new XcreateCategoryHandler($xoopsDB);
$fieldHandler = new XcreateFieldHandler($xoopsDB);

// field_condition kolonu yoksa otomatik ekle
$_col_res = $xoopsDB->query("SHOW COLUMNS FROM " . $xoopsDB->prefix('xcreate_fields') . " LIKE 'field_condition'");
if (!$_col_res || !$xoopsDB->fetchArray($_col_res)) {
    $xoopsDB->queryF("ALTER TABLE " . $xoopsDB->prefix('xcreate_fields') . " ADD COLUMN `field_condition` TEXT DEFAULT NULL AFTER `field_repeatable`");
}

// field_lookup kolonları yoksa otomatik ekle
$_lookup_res = $xoopsDB->query("SHOW COLUMNS FROM " . $xoopsDB->prefix('xcreate_fields') . " LIKE 'field_lookup_enabled'");
if (!$_lookup_res || !$xoopsDB->fetchArray($_lookup_res)) {
    $xoopsDB->queryF("ALTER TABLE " . $xoopsDB->prefix('xcreate_fields') . " ADD COLUMN `field_lookup_enabled` TINYINT(1) NOT NULL DEFAULT '0' AFTER `field_condition`");
    $xoopsDB->queryF("ALTER TABLE " . $xoopsDB->prefix('xcreate_fields') . " ADD COLUMN `field_lookup_cat_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `field_lookup_enabled`");
    $xoopsDB->queryF("ALTER TABLE " . $xoopsDB->prefix('xcreate_fields') . " ADD COLUMN `field_lookup_field_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `field_lookup_cat_id`");
}

/**
 * Conditional fields için JS opsiyonları üretir
 * Döner: {"5":[{"value":"Kiralık","label":"Kiralık"},...],...}
 */
function xcreate_build_field_options_json($fields, $fieldHandler) {
    $map = array();
    foreach ($fields as $f) {
        $ftype = $f->getVar('field_type');
        if (in_array($ftype, array('select','radio','checkbox'))) {
            $opts = $fieldHandler->getFieldOptions($f->getVar('field_id'));
            $map[(string)$f->getVar('field_id')] = $opts;
        }
    }
    return json_encode($map);
}

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : 'list';
$field_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$cat_id = isset($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;

switch ($op) {
    case 'save':
        if (!$GLOBALS['xoopsSecurity']->check()) {
            redirect_header('fields.php', 3, implode('<br>', $GLOBALS['xoopsSecurity']->getErrors()));
        }
        
        // Debug: POST verilerini logla
        
        if ($field_id > 0) {
            $field = $fieldHandler->get($field_id);
        } else {
            $field = $fieldHandler->create();
            $field->setVar('field_created', time());
        }
        
        $field->setVar('field_cat_id', $_POST['field_cat_id']);
        $field->setVar('field_name', $_POST['field_name']);
        $field->setVar('field_label', $_POST['field_label']);
        $field->setVar('field_type', $_POST['field_type']);
        $field->setVar('field_description', $_POST['field_description']);
        
        // Required - Basit checkbox kontrolü
        $field->setVar('field_required', isset($_POST['field_required']) && $_POST['field_required'] == 1 ? 1 : 0);
        
        // Repeatable - Basit checkbox kontrolü  
        $field->setVar('field_repeatable', isset($_POST['field_repeatable']) && $_POST['field_repeatable'] == 1 ? 1 : 0);
        
        $field->setVar('field_default_value', isset($_POST['field_default_value']) ? $_POST['field_default_value'] : '');
        $field->setVar('field_validation', isset($_POST['field_validation']) ? $_POST['field_validation'] : '');
        $field->setVar('field_weight', isset($_POST['field_weight']) ? intval($_POST['field_weight']) : 0);
        $field->setVar('field_status', isset($_POST['field_status']) ? intval($_POST['field_status']) : 1);

        // Lookup field ayarlarını kaydet
        $lookup_enabled  = (isset($_POST['field_lookup_enabled']) && $_POST['field_lookup_enabled'] == 1) ? 1 : 0;
        $lookup_cat_id   = isset($_POST['field_lookup_cat_id'])   ? intval($_POST['field_lookup_cat_id'])   : 0;
        $lookup_field_id = isset($_POST['field_lookup_field_id']) ? intval($_POST['field_lookup_field_id']) : 0;
        $field->setVar('field_lookup_enabled',  $lookup_enabled);
        $field->setVar('field_lookup_cat_id',   $lookup_cat_id);
        $field->setVar('field_lookup_field_id', $lookup_field_id);

        // Conditional field logic
        $condition_field_id = isset($_POST['condition_field_id']) ? intval($_POST['condition_field_id']) : 0;
        if ($condition_field_id > 0) {
            $condition_data = array(
                'field_id' => $condition_field_id,
                'operator' => isset($_POST['condition_operator']) ? $_POST['condition_operator'] : '==',
                'value'    => isset($_POST['condition_value']) ? trim($_POST['condition_value']) : '',
            );
            $field->setVar('field_condition', json_encode($condition_data));
        } else {
            $field->setVar('field_condition', '');
        }
        
        if ($fieldHandler->insert($field)) {
            $new_field_id = $field->getVar('field_id');
            
            // Save field options for select, checkbox, radio
            if (in_array($_POST['field_type'], array('select', 'checkbox', 'radio'))) {
                $options = array();
                if (isset($_POST['option_value']) && is_array($_POST['option_value'])) {
                    foreach ($_POST['option_value'] as $i => $value) {
                        if (!empty($value) && !empty($_POST['option_label'][$i])) {
                            $options[] = array(
                                'value' => $value,
                                'label' => $_POST['option_label'][$i]
                            );
                        }
                    }
                }
                $fieldHandler->saveFieldOptions($new_field_id, $options);
            }
            
            redirect_header('fields.php?cat_id=' . $_POST['field_cat_id'], 2, _MD_XCREATE_SUCCESS_SAVE);
        } else {
            redirect_header('fields.php', 3, _MD_XCREATE_ERROR_SAVE);
        }
        break;
        
    case 'delete':
        if (!isset($_REQUEST['ok']) || $_REQUEST['ok'] != 1) {
            xoops_cp_header();
            $adminObject = \Xmf\Module\Admin::getInstance();
            $adminObject->displayNavigation('fields.php');
            
            $field = $fieldHandler->get($field_id);
            $cat_id = $field->getVar('field_cat_id');
            
            echo '<div class="panel panel-danger">';
            echo '<div class="panel-heading"><h3>' . _AM_XCREATE_FIELD_DELETE_TITLE . '</h3></div>';
            echo '<div class="panel-body">';
            echo '<p><strong>' . _AM_XCREATE_FIELD_DELETE_WARNING . '</strong></p>';
            echo '<p><strong>' . _AM_XCREATE_FIELD_LABEL . ':</strong> ' . $field->getVar('field_label') . '</p>';
            
            // Check for data
            global $xoopsDB;
            $sql = "SELECT COUNT(*) as count FROM " . $xoopsDB->prefix('xcreate_field_values') . " WHERE value_field_id = " . $field_id;
            $result = $xoopsDB->query($sql);
            $row = $result ? $xoopsDB->fetchArray($result) : array('count' => 0);
            $value_count = isset($row['count']) ? $row['count'] : 0;
            
            if ($value_count > 0) {
                echo '<div class="alert alert-danger">';
                echo '<p><strong>' . sprintf(_AM_XCREATE_FIELD_DELETE_HAS_DATA, $value_count) . '</strong></p>';
                echo '<p>' . _AM_XCREATE_FIELD_DELETE_DATA_NOTE . '</p>';
                echo '</div>';
            }
            
            echo '<div class="mt-3">';
            echo '<a href="fields.php?op=delete&id=' . $field_id . '&ok=1" class="btn btn-danger" onclick="return confirm(\'' . addslashes(_AM_XCREATE_FIELD_DELETE_WARNING) . '\');">' . _MD_XCREATE_DELETE . '</a> ';
            echo '<a href="fields.php?cat_id=' . $cat_id . '" class="btn btn-secondary">' . _AM_XCREATE_CANCEL . '</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            xoops_cp_footer();
            exit;
        }
        
        // DELETE OPERATION STARTS - Debug
        
        $field = $fieldHandler->get($field_id);
        if (!$field || $field->isNew()) {
            redirect_header('fields.php', 3, _AM_XCREATE_FIELD_NOT_FOUND);
            exit;
        }
        
        
        $cat_id = $field->getVar('field_cat_id');
        
        // Delete field options
        global $xoopsDB;
        $result1 = $xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('xcreate_field_options') . " WHERE option_field_id = " . intval($field_id));
        
        // Delete field values - CRITICAL: Use xcreate_field_values, not xcreate_item_values
        $result2 = $xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('xcreate_field_values') . " WHERE value_field_id = " . intval($field_id));
        
        // Delete field - Use force=true parameter
        $result3 = $fieldHandler->delete($field, true);
        
        if ($result3) {
            redirect_header('fields.php?cat_id=' . $cat_id, 2, _MD_XCREATE_SUCCESS_DELETE);
        } else {
            redirect_header('fields.php?cat_id=' . $cat_id, 3, _MD_XCREATE_ERROR_DELETE);
        }
        break;
        
    case 'edit':
    case 'add':
        xoops_cp_header();
        
        $adminObject = \Xmf\Module\Admin::getInstance();
        $adminObject->displayNavigation('fields.php');
        
        if ($field_id > 0) {
            $field = $fieldHandler->get($field_id);
            $form_title = _AM_XCREATE_FIELD_EDIT;
            $cat_id = $field->getVar('field_cat_id');
        } else {
            $field = $fieldHandler->create();
            $form_title = _AM_XCREATE_FIELD_ADD;
        }
        
        echo '<style>
        .option-row { margin-bottom: 10px; display: flex; gap: 10px; align-items: center; }
        .option-row input { flex: 1; }
        .btn-remove-option { color: #d9534f; cursor: pointer; padding: 5px 10px; }
        .btn-add-option { margin-top: 10px; }
        #options-container { margin-top: 10px; }
        </style>';
        
        echo '<script>
        function toggleOptions() {
            var fieldType = document.getElementById("field_type").value;
            var optionsDiv = document.getElementById("options-div");
            
            if (fieldType == "select" || fieldType == "checkbox" || fieldType == "radio") {
                optionsDiv.style.display = "block";
            } else {
                optionsDiv.style.display = "none";
            }
        }
        
        function addOption() {
            var container = document.getElementById("options-container");
            var index = container.children.length;
            
            var div = document.createElement("div");
            div.className = "option-row";
            div.innerHTML = `
                <input type="text" name="option_value[]" class="form-control" placeholder="' . addslashes(_AM_XCREATE_FIELD_OPTION_VALUE) . '" required>
                <input type="text" name="option_label[]" class="form-control" placeholder="' . addslashes(_AM_XCREATE_FIELD_OPTION_LABEL) . '" required>
                <span class="btn-remove-option" onclick="removeOption(this)">✖</span>
            `;
            
            container.appendChild(div);
        }
        
        function removeOption(btn) {
            btn.parentElement.remove();
        }
        
        window.onload = function() {
            toggleOptions();
        };
        </script>';
        
        $form = new XoopsThemeForm($form_title, 'field_form', 'fields.php', 'post');
        
        // Category
        $category_select = new XoopsFormSelect(_MD_XCREATE_CATEGORY, 'field_cat_id', $cat_id);
        $categories = $categoryHandler->getTree();
        foreach ($categories as $cat) {
            $level = intval($cat->getVar('level'));
            $prefix = str_repeat('--', $level);
            $category_select->addOption($cat->getVar('cat_id'), $prefix . ' ' . $cat->getVar('cat_name'));
        }
        $form->addElement($category_select, true);
        
        // Name (internal name)
        $form->addElement(new XoopsFormText(_AM_XCREATE_FIELD_NAME, 'field_name', 50, 255, $field->getVar('field_name', 'e')), true);
        
        // Label (display name)
        $form->addElement(new XoopsFormText(_AM_XCREATE_FIELD_LABEL, 'field_label', 50, 255, $field->getVar('field_label', 'e')), true);
        
        // Field Type
        $type_select = new XoopsFormSelect(_AM_XCREATE_FIELD_TYPE, 'field_type', $field->getVar('field_type'));
        $type_select->setExtra('id="field_type" onchange="toggleOptions()"');
        foreach (XcreateField::getFieldTypes() as $type => $label) {
            $type_select->addOption($type, $label);
        }
        $form->addElement($type_select, true);
        
        // Description
        $form->addElement(new XoopsFormTextArea(_AM_XCREATE_FIELD_DESC, 'field_description', $field->getVar('field_description', 'e'), 3, 50));
        
        // Required - Modern HTML checkbox
        $required_checked = $field->getVar('field_required') ? 'checked' : '';
        $required_html = '<div class="form-check" style="padding: 10px; background: #f9fafb; border-radius: 6px;">';
        $required_html .= '<input type="checkbox" name="field_required" id="field_required" value="1" ' . $required_checked . ' style="width: 18px; height: 18px; cursor: pointer; margin-right: 8px;">';
        $required_html .= '<label for="field_required" style="cursor: pointer; font-weight: 500;">' . _AM_XCREATE_FIELD_REQUIRED . '</label>';
        $required_html .= '<div style="margin-top: 5px; margin-left: 26px; color: #6b7280; font-size: 13px;">' . _AM_XCREATE_FIELD_REQUIRED_HELP . '</div>';
        $required_html .= '</div>';
        $form->addElement(new XoopsFormLabel(_AM_XCREATE_FIELD_REQUIRED, $required_html));
        
        // Repeatable - Modern HTML checkbox
        $repeatable_checked = $field->getVar('field_repeatable') ? 'checked' : '';
        $repeatable_html = '<div class="form-check" style="padding: 10px; background: #f9fafb; border-radius: 6px;">';
        $repeatable_html .= '<input type="checkbox" name="field_repeatable" id="field_repeatable" value="1" ' . $repeatable_checked . ' style="width: 18px; height: 18px; cursor: pointer; margin-right: 8px;">';
        $repeatable_html .= '<label for="field_repeatable" style="cursor: pointer; font-weight: 500;">' . _AM_XCREATE_FIELD_REPEATABLE_LABEL . '</label>';
        $repeatable_html .= '<div style="margin-top: 5px; margin-left: 26px; color: #6b7280; font-size: 13px;">' . _AM_XCREATE_FIELD_REPEATABLE_HELP . '</div>';
        $repeatable_html .= '</div>';
        $form->addElement(new XoopsFormLabel(_AM_XCREATE_FIELD_REPEATABLE, $repeatable_html));
        
        // ── ALAN BAĞIMLILIĞI (Conditional Fields) ──────────────────────────
        // Edit durumunda cat_id field'dan al
        $cond_cat_id = ($field_id > 0) ? $field->getVar('field_cat_id') : $cat_id;
        $all_cat_fields = array();
        if ($cond_cat_id > 0) {
            $all_cat_fields = $fieldHandler->getFieldsByCategory($cond_cat_id, null);
        }

        // Mevcut koşulu parse et
        $existing_cond = array('field_id' => 0, 'operator' => '==', 'value' => '');
        $cond_raw = $field->getVar('field_condition');
        if (!empty($cond_raw)) {
            $parsed = json_decode($cond_raw, true);
            if ($parsed) $existing_cond = $parsed;
        }
        $ec_fid = intval($existing_cond['field_id']);
        $ec_op  = htmlspecialchars($existing_cond['operator'], ENT_QUOTES);
        $ec_val = htmlspecialchars($existing_cond['value'], ENT_QUOTES);

        $cond_html  = '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:18px;margin-top:5px;">';
        $cond_html .= '<p style="margin:0 0 14px;color:#0369a1;font-size:13px;">💡 ' . _AM_XCREATE_FIELD_DEPENDENCY_HELP . '</p>';
        $cond_html .= '<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">';

        // Tetikleyici alan seçici
        $cond_html .= '<div style="flex:2;min-width:180px;">';
        $cond_html .= '<label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;">' . _AM_XCREATE_FIELD_DEPENDENCY_TRIGGER . '</label>';
        $cond_html .= '<select id="ui_cond_fid" class="form-control" style="width:100%;" onchange="xcreateCond()">';
        $cond_html .= '<option value="0">— ' . _AM_XCREATE_FIELD_DEPENDENCY_NONE . ' —</option>';
        foreach ($all_cat_fields as $cf) {
            $cf_id   = (int)$cf->getVar('field_id');
            $cf_type = $cf->getVar('field_type');
            if ($field_id > 0 && $cf_id == $field_id) continue;
            $sel   = ($ec_fid == $cf_id) ? 'selected' : '';
            $badge = in_array($cf_type, array('select','radio','checkbox')) ? ' ★' : '';
            $cond_html .= '<option value="' . $cf_id . '" ' . $sel . '>' . htmlspecialchars($cf->getVar('field_label')) . $badge . '</option>';
        }
        $cond_html .= '</select>';
        $cond_html .= '<small style="color:#6b7280;font-size:12px;margin-top:4px;display:block;">★ = ' . _AM_XCREATE_FIELD_DEPENDENCY_BEST_FIT . '</small>';
        $cond_html .= '</div>';

        // Operatör
        $cond_html .= '<div style="flex:1;min-width:120px;">';
        $cond_html .= '<label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;">' . _AM_XCREATE_FIELD_DEPENDENCY_OPERATOR . '</label>';
        $cond_html .= '<select id="ui_cond_op" class="form-control" onchange="xcreateCond()">';
        foreach (array('==' => _AM_XCREATE_FIELD_DEPENDENCY_OP_EQ, '!=' => _AM_XCREATE_FIELD_DEPENDENCY_OP_NEQ, 'contains' => _AM_XCREATE_FIELD_DEPENDENCY_OP_CONTAINS, 'not_empty' => _AM_XCREATE_FIELD_DEPENDENCY_OP_NOT_EMPTY) as $ov => $ol) {
            $sel = ($ec_op == $ov) ? 'selected' : '';
            $cond_html .= '<option value="' . $ov . '" ' . $sel . '>' . $ol . '</option>';
        }
        $cond_html .= '</select>';
        $cond_html .= '</div>';

        // Değer
        $cond_html .= '<div style="flex:2;min-width:180px;">';
        $cond_html .= '<label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;">' . _AM_XCREATE_FIELD_DEPENDENCY_VALUE . '</label>';
        $cond_html .= '<div id="ui_cond_val_wrap">';
        $cond_html .= '<input type="text" id="ui_cond_val" class="form-control" value="' . $ec_val . '" placeholder="' . _AM_XCREATE_FIELD_DEPENDENCY_VALUE_PLACEHOLDER . '" oninput="xcreateCond()">';
        $cond_html .= '</div>';
        $cond_html .= '</div>';
        $cond_html .= '</div>';
        $cond_html .= '<div id="cond_preview" style="margin-top:12px;padding:10px;background:#fff;border-radius:6px;font-size:13px;color:#374151;display:none;"></div>';
        $cond_html .= '</div>';

        $cond_html .= '<script>';
        $cond_html .= 'var _xcFO=' . xcreate_build_field_options_json($all_cat_fields, $fieldHandler) . ';';
        $cond_html .= 'var _xcIV=' . json_encode($existing_cond['value']) . ';';
        $cond_html .= '
function xcreateCond() {
    var fid = document.getElementById("ui_cond_fid").value;
    var op  = document.getElementById("ui_cond_op").value;
    var wrap = document.getElementById("ui_cond_val_wrap");

    // Gizli inputları doğrudan ID ile bul (JS inject edildi)
    var hFid = document.getElementById("hc_cond_field_id");
    var hOp  = document.getElementById("hc_cond_operator");
    var hVal = document.getElementById("hc_cond_value");
    if (hFid) hFid.value = fid;
    if (hOp)  hOp.value  = op;

    var curVal = hVal ? hVal.value : _xcIV;

    if (_xcFO[fid] && _xcFO[fid].length > 0 && op !== "not_empty") {
        if (!wrap.querySelector("select")) {
            var s = document.createElement("select");
            s.id = "ui_cond_val_sel"; s.className = "form-control";
            s.onchange = function(){ if(document.getElementById("hc_cond_value")) document.getElementById("hc_cond_value").value=this.value; };
            var e0 = document.createElement("option"); e0.value=""; e0.textContent="' . addslashes(_AM_XCREATE_CHOOSE_OPTION) . '"; s.appendChild(e0);
            _xcFO[fid].forEach(function(o){
                var oe=document.createElement("option"); oe.value=o.value; oe.textContent=o.label;
                if(o.value===curVal) oe.selected=true; s.appendChild(oe);
            });
            wrap.innerHTML=""; wrap.appendChild(s);
            if(hVal) hVal.value=s.value;
        }
    } else {
        if (!document.getElementById("ui_cond_val")) {
            wrap.innerHTML="<input type=\"text\" id=\"ui_cond_val\" class=\"form-control\" value=\""+curVal+"\" placeholder=\"' . addslashes(_AM_XCREATE_FIELD_DEPENDENCY_VALUE_PLACEHOLDER) . '\" oninput=\"if(document.getElementById(\'hc_cond_value\'))document.getElementById(\'hc_cond_value\').value=this.value;\">";
        }
        if (hVal) hVal.value = op==="not_empty" ? "" : (document.getElementById("ui_cond_val") ? document.getElementById("ui_cond_val").value : "");
    }

    var preview = document.getElementById("cond_preview");
    if (fid==="0") { preview.style.display="none"; return; }
    var fSel=document.getElementById("ui_cond_fid");
    var fLabel=fSel.options[fSel.selectedIndex].textContent;
    var opMap={"==":"' . addslashes(_AM_XCREATE_FIELD_DEPENDENCY_PREVIEW_EQ) . '","!=":"' . addslashes(_AM_XCREATE_FIELD_DEPENDENCY_PREVIEW_NEQ) . '","contains":"' . addslashes(_AM_XCREATE_FIELD_DEPENDENCY_PREVIEW_CONTAINS) . '","not_empty":"' . addslashes(_AM_XCREATE_FIELD_DEPENDENCY_PREVIEW_NOT_EMPTY) . '"};
    var val=hVal?hVal.value:"";
    preview.style.display="block";
    preview.innerHTML="📋 <strong>' . addslashes(_AM_XCREATE_FIELD_DEPENDENCY_PREVIEW) . ':</strong> <em>\""+fLabel+"\"</em> "+(opMap[op]||op)+(op!==\"not_empty\"?\" <strong>\\\"\"+val+\"\\\"</strong>\":\"\")+\" → ' . addslashes(_AM_XCREATE_FIELD_DEPENDENCY_PREVIEW_NOT_EMPTY) . '\";";
}

function xcreateCondInit() {
    var form = document.querySelector("form[name=field_form]");
    if (!form) { form = document.getElementById("field_form"); }
    if (!form) { setTimeout(xcreateCondInit, 200); return; }
    if (!document.getElementById("hc_cond_field_id")) {
        function mh(id,nm,val){ var i=document.createElement("input"); i.type="hidden"; i.id=id; i.name=nm; i.value=val; form.appendChild(i); }
        mh("hc_cond_field_id","condition_field_id","' . $ec_fid . '");
        mh("hc_cond_operator", "condition_operator","' . $ec_op . '");
        mh("hc_cond_value",    "condition_value",   "' . $ec_val . '");
    }
    xcreateCond();
}
if(document.readyState==="loading"){ document.addEventListener("DOMContentLoaded",xcreateCondInit); } else { xcreateCondInit(); }
';
        $cond_html .= '</script>';

        $form->addElement(new XoopsFormLabel('🔗 ' . _AM_XCREATE_FIELD_DEPENDENCY, $cond_html));
        // ── /ALAN BAĞIMLILIĞI ──────────────────────────────────────────────

        // ── VERİTABANI LOOKUP (İlişkili İçerik Seçici) ─────────────────────
        $lookup_enabled  = $field->getVar('field_lookup_enabled');
        $lookup_cat_id   = intval($field->getVar('field_lookup_cat_id'));
        $lookup_field_id = intval($field->getVar('field_lookup_field_id'));

        // Tüm kategorileri getir (lookup için)
        $all_lookup_cats = $categoryHandler->getTree();

        // Seçili kategorinin alanlarını getir
        $lookup_cat_fields = array();
        if ($lookup_cat_id > 0) {
            $lookup_cat_fields = $fieldHandler->getFieldsByCategory($lookup_cat_id, null);
        }

        $lookup_html  = '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:18px;margin-top:5px;">';
        $lookup_html .= '<p style="margin:0 0 14px;color:#15803d;font-size:13px;">🔗 ' . _AM_XCREATE_FIELD_LOOKUP_HELP . '</p>';

        // Checkbox
        $lkp_checked = $lookup_enabled ? 'checked' : '';
        $lookup_html .= '<div style="margin-bottom:14px;">';
        $lookup_html .= '<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">';
        $lookup_html .= '<input type="checkbox" name="field_lookup_enabled" id="field_lookup_enabled" value="1" ' . $lkp_checked . ' onchange="xcreateToggleLookup()" style="width:18px;height:18px;">';
        $lookup_html .= _AM_XCREATE_FIELD_LOOKUP_ENABLE;
        $lookup_html .= '</label>';
        $lookup_html .= '</div>';

        // Ayarlar (sadece checkbox işaretliyse göster)
        $lkp_display = $lookup_enabled ? 'block' : 'none';
        $lookup_html .= '<div id="xcreate_lookup_settings" style="display:' . $lkp_display . ';border-top:1px solid #bbf7d0;padding-top:14px;">';
        $lookup_html .= '<div style="display:flex;gap:12px;flex-wrap:wrap;">';

        // Kategori seçici
        $lookup_html .= '<div style="flex:1;min-width:200px;">';
        $lookup_html .= '<label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;">' . _AM_XCREATE_FIELD_LOOKUP_CATEGORY . '</label>';
        $lookup_html .= '<select name="field_lookup_cat_id" id="field_lookup_cat_id" class="form-control" onchange="xcreateLoadLookupFields(this.value)">';
        $lookup_html .= '<option value="0">— ' . _AM_XCREATE_FIELD_LOOKUP_CATEGORY_NONE . ' —</option>';
        foreach ($all_lookup_cats as $lc) {
            $lc_id  = $lc->getVar('cat_id');
            $level  = intval($lc->getVar('level'));
            $prefix = str_repeat('— ', $level);
            $sel    = ($lc_id == $lookup_cat_id) ? 'selected' : '';
            $lookup_html .= '<option value="' . $lc_id . '" ' . $sel . '>' . $prefix . htmlspecialchars($lc->getVar('cat_name')) . '</option>';
        }
        $lookup_html .= '</select>';
        $lookup_html .= '<small style="color:#6b7280;font-size:12px;margin-top:4px;display:block;">' . _AM_XCREATE_FIELD_LOOKUP_CATEGORY_HELP . '</small>';
        $lookup_html .= '</div>';

        // Alan seçici (arama sonucunda gösterilecek değer sütunu)
        $lookup_html .= '<div style="flex:1;min-width:200px;">';
        $lookup_html .= '<label style="display:block;margin-bottom:5px;font-weight:600;font-size:13px;">' . _AM_XCREATE_FIELD_LOOKUP_TARGET . '</label>';
        $lookup_html .= '<select name="field_lookup_field_id" id="field_lookup_field_id" class="form-control">';
        $lookup_html .= '<option value="0">— ' . _AM_XCREATE_FIELD_LOOKUP_TARGET_DEFAULT . ' —</option>';
        foreach ($lookup_cat_fields as $lf) {
            $lf_id  = $lf->getVar('field_id');
            $lf_lbl = htmlspecialchars($lf->getVar('field_label'));
            $sel    = ($lf_id == $lookup_field_id) ? 'selected' : '';
            $lookup_html .= '<option value="' . $lf_id . '" ' . $sel . '>' . $lf_lbl . '</option>';
        }
        $lookup_html .= '</select>';
        $lookup_html .= '<small style="color:#6b7280;font-size:12px;margin-top:4px;display:block;">' . _AM_XCREATE_FIELD_LOOKUP_TARGET_HELP . '</small>';
        $lookup_html .= '</div>';

        $lookup_html .= '</div>'; // flex
        $lookup_html .= '</div>'; // #xcreate_lookup_settings
        $lookup_html .= '</div>'; // outer

        // JS - toggle + AJAX alan yükleme
        $lookup_html .= '<script>';
        $lookup_html .= 'function xcreateToggleLookup() {';
        $lookup_html .= '  var cb = document.getElementById("field_lookup_enabled");';
        $lookup_html .= '  document.getElementById("xcreate_lookup_settings").style.display = cb.checked ? "block" : "none";';
        $lookup_html .= '}';

        $lookup_html .= 'function xcreateLoadLookupFields(catId) {';
        $lookup_html .= '  var sel = document.getElementById("field_lookup_field_id");';
        $lookup_html .= '  sel.innerHTML = \'<option value="0">— ' . addslashes(_AM_XCREATE_FIELD_LOOKUP_TARGET_DEFAULT) . ' —</option>\';';
        $lookup_html .= '  if (!catId || catId == "0") return;';
        $lookup_html .= '  var url = "' . XOOPS_URL . '/modules/xcreate/ajax/get_cat_fields.php?cat_id=" + catId;';
        $lookup_html .= '  fetch(url).then(function(r){ return r.json(); }).then(function(data){';
        $lookup_html .= '    if (data.success && data.fields) {';
        $lookup_html .= '      data.fields.forEach(function(f){';
        $lookup_html .= '        var opt = document.createElement("option");';
        $lookup_html .= '        opt.value = f.id; opt.textContent = f.label;';
        $lookup_html .= '        sel.appendChild(opt);';
        $lookup_html .= '      });';
        $lookup_html .= '    }';
        $lookup_html .= '  });';
        $lookup_html .= '}';
        $lookup_html .= '</script>';

        $form->addElement(new XoopsFormLabel('🔗 ' . _AM_XCREATE_FIELD_LOOKUP, $lookup_html));
        // ── /VERİTABANI LOOKUP ───────────────────────────────────────────────

        // Default Value
        $form->addElement(new XoopsFormText(_AM_XCREATE_FIELD_DEFAULT, 'field_default_value', 50, 255, $field->getVar('field_default_value', 'e')));
        
        // Validation
        $form->addElement(new XoopsFormText(_AM_XCREATE_FIELD_VALIDATION, 'field_validation', 50, 100, $field->getVar('field_validation', 'e')));
        
        // Weight
        $form->addElement(new XoopsFormText(_AM_XCREATE_FIELD_WEIGHT, 'field_weight', 10, 10, $field->getVar('field_weight')));
        
        // Status - Select kullanarak daha güvenilir hale getiriyoruz
        $status_value = $field->getVar('field_status');
        // Yeni alan eklenirken varsayılan olarak aktif olsun
        if ($field_id == 0) {
            $status_value = 1;
        }
        $status_select = new XoopsFormRadio(_AM_XCREATE_ITEM_STATUS, 'field_status', $status_value);
        $status_select->addOption(1, _AM_XCREATE_STATUS_ACTIVE);
        $status_select->addOption(0, _AM_XCREATE_STATUS_INACTIVE);
        $form->addElement($status_select);
        
        // Options (for select, checkbox, radio)
        $options_html = '<div id="options-div" style="display:none;">';
        $options_html .= '<div class="form-group">';
        $options_html .= '<label>' . _AM_XCREATE_FIELD_OPTIONS . '</label>';
        $options_html .= '<div id="options-container">';
        
        if ($field_id > 0) {
            $existing_options = $fieldHandler->getFieldOptions($field_id);
            foreach ($existing_options as $option) {
                $options_html .= '<div class="option-row">';
                $options_html .= '<input type="text" name="option_value[]" class="form-control" placeholder="' . _AM_XCREATE_FIELD_OPTION_VALUE . '" value="' . htmlspecialchars($option['value']) . '" required>';
                $options_html .= '<input type="text" name="option_label[]" class="form-control" placeholder="' . _AM_XCREATE_FIELD_OPTION_LABEL . '" value="' . htmlspecialchars($option['label']) . '" required>';
                $options_html .= '<span class="btn-remove-option" onclick="removeOption(this)">✖</span>';
                $options_html .= '</div>';
            }
        }
        
        $options_html .= '</div>';
        $options_html .= '<button type="button" class="btn btn-secondary btn-add-option" onclick="addOption()">' . _AM_XCREATE_ADD_OPTION . '</button>';
        $options_html .= '<div class="help-block">' . _AM_XCREATE_FIELD_OPTIONS_DESC . '</div>';
        $options_html .= '</div>';
        $options_html .= '</div>';
        
        $form->addElement(new XoopsFormLabel('', $options_html));
        
        // Hidden
        $form->addElement(new XoopsFormHidden('op', 'save'));
        if ($field_id > 0) {
            $form->addElement(new XoopsFormHidden('id', $field_id));
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
        $adminObject->displayNavigation('fields.php');
        
        echo '<div class="xcreate-header">';
        echo '<h2>📝 ' . _AM_XCREATE_FIELDS . '</h2>';
        echo '<p>' . _AM_XCREATE_FIELD_MANAGE_HELP . '</p>';
        echo '</div>';
        
        // Category filter
        if ($cat_id > 0) {
            $category = $categoryHandler->get($cat_id);
            echo '<div class="alert-customfields alert-info">';
            echo '<strong>' . _MD_XCREATE_CATEGORY . ':</strong> ' . $category->getVar('cat_name');
            echo ' <a href="fields.php" style="margin-left: 15px; color: #3b82f6;">Tüm Alanlar →</a>';
            echo '</div>';
        }
        
        echo '<div class="xcreate-actions" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">';
        echo '<a href="fields.php?op=add' . ($cat_id > 0 ? '&cat_id=' . $cat_id : '') . '" class="btn-customfields btn-xcreate-success">+ ' . _AM_XCREATE_FIELD_ADD . '</a>';
        echo '<form method="get" action="fields.php" style="display:flex;gap:8px;align-items:center;">';
        echo '<select name="cat_id" class="form-control-customfields" onchange="this.form.submit()" style="min-width:200px;">';
        echo '<option value="0"' . ($cat_id == 0 ? ' selected' : '') . '>— ' . _AM_XCREATE_FIELD_ALL_CATEGORIES . ' —</option>';
        $all_cats = $categoryHandler->getTree();
        foreach ($all_cats as $ac) {
            $level  = intval($ac->getVar('level'));
            $prefix = str_repeat('— ', $level);
            $sel    = ($cat_id == $ac->getVar('cat_id')) ? ' selected' : '';
            echo '<option value="' . $ac->getVar('cat_id') . '"' . $sel . '>' . $prefix . $ac->getVar('cat_name') . '</option>';
        }
        echo '</select>';
        if ($cat_id > 0) {
            echo '<a href="fields.php" class="btn-customfields btn-xcreate-secondary">✕ ' . _AM_XCREATE_CLEAR . '</a>';
        }
        echo '</form>';
        echo '</div>';
        
        if ($cat_id > 0) {
            $fields = $fieldHandler->getFieldsByCategory($cat_id, null);
        } else {
            $fields = $fieldHandler->getAll();
        }

        // Kategori önbelleği — her alan için tekrar sorgu yapmamak için
        $cat_cache = array();
        
        if (count($fields) > 0) {
            echo '<table class="table-customfields">';
            echo '<thead>';
            echo '<tr>';
            echo '<th style="width: 60px;">ID</th>';
            echo '<th>' . _AM_XCREATE_FIELD_LABEL . '</th>';
            echo '<th style="width: 160px;">Kategori</th>';
            echo '<th style="width: 140px;">' . _AM_XCREATE_FIELD_TYPE . '</th>';
            echo '<th style="width: 90px; text-align: center;">' . _AM_XCREATE_FIELD_REQUIRED . '</th>';
            echo '<th style="width: 110px; text-align: center;">' . _AM_XCREATE_FIELD_REPEATABLE . '</th>';
            echo '<th style="width: 80px; text-align: center;">' . _AM_XCREATE_ITEM_STATUS . '</th>';
            echo '<th style="width: 160px; text-align: center;">' . _MD_XCREATE_VIEW . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            $field_types = XcreateField::getFieldTypes();
            
            foreach ($fields as $field) {
                $fc_id = (int)$field->getVar('field_cat_id');
                if (!isset($cat_cache[$fc_id])) {
                    $cat_obj = $categoryHandler->get($fc_id);
                    $cat_cache[$fc_id] = $cat_obj ? $cat_obj->getVar('cat_name') : '—';
                }
                $cat_name = $cat_cache[$fc_id];

                echo '<tr>';
                echo '<td><span class="badge-customfields badge-info">#' . $field->getVar('field_id') . '</span></td>';
                echo '<td><strong>' . $field->getVar('field_label') . '</strong><br><small style="color: #9ca3af;">' . $field->getVar('field_name') . '</small></td>';
                echo '<td><a href="fields.php?cat_id=' . $fc_id . '" style="color:#3b82f6;text-decoration:none;font-size:13px;">📁 ' . htmlspecialchars($cat_name) . '</a></td>';
                echo '<td><span class="badge-customfields badge-secondary">' . $field_types[$field->getVar('field_type')] . '</span></td>';
                echo '<td style="text-align: center;">' . ($field->getVar('field_required') ? '<span class="badge-customfields badge-danger">Zorunlu</span>' : '<span class="badge-customfields badge-secondary">Opsiyonel</span>') . '</td>';
                echo '<td style="text-align: center;">' . ($field->getVar('field_repeatable') ? '<span class="badge-customfields badge-success">✓ Tekrarlanabilir</span>' : '<span class="badge-customfields badge-secondary">Tek</span>') . '</td>';
                echo '<td style="text-align: center;">' . ($field->getVar('field_status') ? '<span class="status-indicator status-active">' . _AM_XCREATE_STATUS_ACTIVE . '</span>' : '<span class="status-indicator status-inactive">' . _AM_XCREATE_STATUS_INACTIVE . '</span>') . '</td>';
                echo '<td><div class="action-links" style="justify-content: center;">';
                echo '<a href="fields.php?op=edit&id=' . $field->getVar('field_id') . '">✏️ ' . _MD_XCREATE_EDIT . '</a>';
                echo '<a href="fields.php?op=delete&id=' . $field->getVar('field_id') . '" class="delete-link">🗑️ ' . _MD_XCREATE_DELETE . '</a>';
                echo '</div></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<div class="empty-state">';
            echo '<div class="empty-state-icon">📋</div>';
            echo '<div class="empty-state-text">' . _AM_XCREATE_FIELDS_EMPTY_TITLE . '</div>';
            echo '<div class="empty-state-description">';
            if ($cat_id > 0) {
                echo _AM_XCREATE_FIELDS_EMPTY_DESC_CATEGORY;
            } else {
                echo _AM_XCREATE_FIELDS_EMPTY_DESC_ALL;
            }
            echo '</div>';
            echo '<a href="fields.php?op=add' . ($cat_id > 0 ? '&cat_id=' . $cat_id : '') . '" class="btn-customfields btn-xcreate-success">+ ' . _AM_XCREATE_FIELD_ADD . '</a>';
            echo '</div>';
        }
        
        xoops_cp_footer();
        break;
}

?>
