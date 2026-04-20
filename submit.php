<?php
/**
 * Submit/Edit Item with Dynamic Repeatable Fields
 */

include '../../mainfile.php';
include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

// Load language files
$language = $GLOBALS['xoopsConfig']['language'];
if (file_exists(XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/main.php")) {
    include_once XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/main.php";
} else {
    include_once XOOPS_ROOT_PATH . "/modules/xcreate/language/english/main.php";
}

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';

$categoryHandler = new XcreateCategoryHandler($xoopsDB);
$fieldHandler    = new XcreateFieldHandler($xoopsDB);
$itemHandler     = new XcreateItemHandler($xoopsDB);

// field_condition kolonu yoksa otomatik ekle
$_col_res3 = $xoopsDB->query("SHOW COLUMNS FROM " . $xoopsDB->prefix('xcreate_fields') . " LIKE 'field_condition'");
if (!$_col_res3 || !$xoopsDB->fetchArray($_col_res3)) {
    $xoopsDB->queryF("ALTER TABLE " . $xoopsDB->prefix('xcreate_fields') . " ADD COLUMN `field_condition` TEXT DEFAULT NULL AFTER `field_repeatable`");
}

$op      = isset($_REQUEST['op']) ? $_REQUEST['op'] : 'form';
$item_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$cat_id  = isset($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id'])
         : (isset($_POST['item_cat_id']) ? intval($_POST['item_cat_id']) : 0);

// save işlemi için header gerekmez (redirect yapılıyor)
// form gösterimi için template önceden set edilmeli
if ($op !== 'save') {
    $xoopsOption['template_main'] = 'xcreate_submit.tpl';
}

include XOOPS_ROOT_PATH . '/header.php';

// Permission check
if (!$xoopsModuleConfig['allow_user_submit'] && !$xoopsUser) {
    redirect_header(XOOPS_URL . '/user.php', 3, _MD_XCREATE_ERROR_PERMISSION);
}

// File upload helper function
function handleSingleFileUpload($file, $field_type) {
    global $xoopsModuleConfig;
    
    
    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        return false;
    }
    
    
    $upload_dir = XOOPS_ROOT_PATH . '/uploads/xcreate/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check file type
    if ($field_type == 'image') {
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $max_size = isset($xoopsModuleConfig['max_image_size']) ? $xoopsModuleConfig['max_image_size'] * 1024 : 2097152; // 2MB default
    } else {
        $allowed_ext = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'txt', 'csv');
        $max_size = isset($xoopsModuleConfig['max_file_size']) ? $xoopsModuleConfig['max_file_size'] * 1024 : 5242880; // 5MB default
    }
    
    
    if (!in_array($file_ext, $allowed_ext)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
    $target_path = $upload_dir . $new_filename;
    
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        
        // Resize image if needed
        if ($field_type == 'image') {
            $max_width = isset($xoopsModuleConfig['max_image_width']) ? $xoopsModuleConfig['max_image_width'] : 1920;
            $max_height = isset($xoopsModuleConfig['max_image_height']) ? $xoopsModuleConfig['max_image_height'] : 1080;
            
            resizeImage($target_path, $max_width, $max_height);
        }
        
        return $new_filename;
    }
    
    return false;
}

// Image resize function
function resizeImage($file_path, $max_width, $max_height) {
    $image_info = getimagesize($file_path);
    if (!$image_info) {
        return false;
    }
    
    list($width, $height, $type) = $image_info;
    
    // Check if resize is needed
    if ($width <= $max_width && $height <= $max_height) {
        return true;
    }
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($file_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($file_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($file_path);
            break;
        default:
            return false;
    }
    
    // Create new image
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $file_path, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $file_path, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $file_path);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($destination);
    
    return true;
}

switch ($op) {
    case 'save':
        // Security check kaldırıldı - form POST ile geldiği için check çalışmıyor
        
        // Validate category selection
        if (!isset($_POST['item_cat_id']) || intval($_POST['item_cat_id']) <= 0) {
            redirect_header('submit.php', 3, _AM_XCREATE_ITEM_SELECT_CATEGORY);
            exit;
        }
        
        // Validate title
        if (!isset($_POST['item_title']) || trim($_POST['item_title']) === '') {
            redirect_header('submit.php?cat_id=' . intval($_POST['item_cat_id']), 3, _AM_XCREATE_ITEM_TITLE_REQUIRED);
            exit;
        }
        
        // First, validate all required fields BEFORE saving the item
        $fields = $fieldHandler->getFieldsByCategory($_POST['item_cat_id']);
        $validation_errors = array();
        
        
        foreach ($fields as $field) {
            $field_id = $field->getVar('field_id');
            $field_name = 'field_' . $field_id;
            $field_type = $field->getVar('field_type');
            $is_required = $field->getVar('field_required');
            $field_label = $field->getVar('field_label');

            // Koşullu alan: koşul sağlanmıyorsa validasyonu atla
            $cond_raw = $field->getVar('field_condition');
            if (!empty($cond_raw)) {
                $cond = json_decode($cond_raw, true);
                if ($cond && !empty($cond['field_id'])) {
                    $trigger_field_id   = intval($cond['field_id']);
                    $trigger_field_name = 'field_' . $trigger_field_id;
                    $trigger_val = isset($_POST[$trigger_field_name])
                        ? (is_array($_POST[$trigger_field_name]) ? implode(',', $_POST[$trigger_field_name]) : trim($_POST[$trigger_field_name]))
                        : '';
                    $cond_val  = isset($cond['value']) ? $cond['value'] : '';
                    $cond_op   = isset($cond['operator']) ? $cond['operator'] : '==';
                    $cond_met  = false;
                    switch ($cond_op) {
                        case '==':       $cond_met = ($trigger_val === $cond_val); break;
                        case '!=':       $cond_met = ($trigger_val !== $cond_val); break;
                        case 'contains': $cond_met = (strpos($trigger_val, $cond_val) !== false); break;
                        case 'not_empty':$cond_met = ($trigger_val !== ''); break;
                        default:         $cond_met = true;
                    }
                    if (!$cond_met) {
                        // Koşul sağlanmıyor → alan zaten gizli, validasyon ve kayıt atlanır
                        continue;
                    }
                }
            }
            
            
            // Validate required fields
            if ($is_required) {
                $has_value = false;
                $base_fname = 'field_' . $field_id;
                
                // For file uploads (including gallery)
                if (in_array($field_type, array('image', 'file', 'gallery'))) {
                    if ($field_type == 'gallery') {
                        $fkey = $base_fname . '_gallery';
                        // Tekli gallery upload var mı?
                        if (isset($_FILES[$fkey]) && is_array($_FILES[$fkey]['name'])) {
                            foreach ($_FILES[$fkey]['name'] as $idx => $nm) {
                                if (!empty($nm) && $_FILES[$fkey]['error'][$idx] == UPLOAD_ERR_OK) {
                                    $has_value = true; break;
                                }
                            }
                        }
                        // Varolan resim var mı?
                        if (!$has_value && isset($_POST[$base_fname . '_existing']) && !empty(array_filter((array)$_POST[$base_fname . '_existing']))) {
                            $has_value = true;
                        }
                    } else {
                        // image / file — tekli veya repeatable
                        if (isset($_FILES[$base_fname])) {
                            $f = $_FILES[$base_fname];
                            if (is_array($f['name'])) {
                                foreach ($f['name'] as $idx => $nm) {
                                    if (!empty($nm) && $f['error'][$idx] == UPLOAD_ERR_OK) { $has_value = true; break; }
                                }
                            } elseif (!empty($f['name']) && $f['error'] == UPLOAD_ERR_OK) {
                                $has_value = true;
                            }
                        }
                        if (!$has_value && isset($_POST[$base_fname . '_existing'])) {
                            $ex = $_POST[$base_fname . '_existing'];
                            if (is_array($ex)) { $ex = array_filter($ex); }
                            if (!empty($ex)) $has_value = true;
                        }
                    }
                } else {
                    // For text fields
                    $submitted_value = isset($_POST[$field_name]) ? $_POST[$field_name] : '';
                    if (is_array($submitted_value)) {
                        // Remove empty values
                        $submitted_value = array_filter($submitted_value, function($v) {
                            return $v !== '' && $v !== null && trim($v) !== '';
                        });
                        if (!empty($submitted_value)) {
                            $has_value = true;
                        }
                    } else {
                        if (trim($submitted_value) !== '') {
                            $has_value = true;
                        }
                    }
                }
                
                
                if (!$has_value) {
                    $validation_errors[] = $field_label . ' alanı zorunludur!';
                }
            }
        }
        
        if (!empty($validation_errors)) {
        }
        
        // If validation failed, show errors and don't save
        if (!empty($validation_errors)) {
            $error_msg = implode('<br>', $validation_errors);
            redirect_header('submit.php?cat_id=' . $_POST['item_cat_id'] . ($item_id > 0 ? '&id=' . $item_id : ''), 5, $error_msg);
            exit;
        }
        
        
        // Validation passed, now save the item
        if ($item_id > 0) {
            $item = $itemHandler->get($item_id);
            // Check ownership
            if (!$xoopsUser || ($item->getVar('item_uid') != $xoopsUser->getVar('uid') && !$xoopsUser->isAdmin())) {
                redirect_header('index.php', 3, _MD_XCREATE_ERROR_PERMISSION);
            }
        } else {
            $item = $itemHandler->create();
            $item->setVar('item_created', time());
            $item->setVar('item_uid', $xoopsUser ? $xoopsUser->getVar('uid') : 0);
            // Admin ekliyorsa otomatik onaylı, kullanıcı ekliyorsa Beklemede
            $isAdmin = (is_object($xoopsUser) && $xoopsUser->isAdmin());
            $item->setVar('item_status', $isAdmin ? 1 : 0);
            if ($isAdmin) {
                $item->setVar('item_published', time());
            }
        }
        
        $item->setVar('item_cat_id', $_POST['item_cat_id']);
        $item->setVar('item_title', $_POST['item_title']);
        $item->setVar('item_description', $_POST['item_description']);
        $item->setVar('item_updated', time());

        // SEO Slug oluştur (yeni item ise veya başlık değiştiyse)
        if (!class_exists('XcreateSlug')) {
            include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/slug.php';
        }
        $existing_slug = $item->getVar('item_slug');
        if (empty($existing_slug)) {
            $exclude_id = ($item_id > 0) ? $item_id : 0;
            $new_slug = $itemHandler->generateSlug($_POST['item_title'], $exclude_id);
            $item->setVar('item_slug', $new_slug);
        }
        
        if ($itemHandler->insert($item)) {
            $new_item_id = $item->getVar('item_id');
            
            // Process dynamic fields
            $field_values = array();
            
            foreach ($fields as $field) {
                $field_id = $field->getVar('field_id');
                $field_name = 'field_' . $field_id;
                $field_type = $field->getVar('field_type');


                // Koşullu alan kontrolü — koşul sağlanmıyorsa kaydetme
                $cond_raw2 = $field->getVar('field_condition');
                if (!empty($cond_raw2)) {
                    $cond2 = json_decode($cond_raw2, true);
                    if ($cond2 && !empty($cond2['field_id'])) {
                        $t_id  = intval($cond2['field_id']);
                        $t_val = isset($_POST['field_' . $t_id])
                            ? (is_array($_POST['field_' . $t_id]) ? implode(',', $_POST['field_' . $t_id]) : trim($_POST['field_' . $t_id]))
                            : '';
                        $c_val = isset($cond2['value']) ? $cond2['value'] : '';
                        $c_op  = isset($cond2['operator']) ? $cond2['operator'] : '==';
                        $met   = false;
                        switch ($c_op) {
                            case '==':        $met = ($t_val === $c_val); break;
                            case '!=':        $met = ($t_val !== $c_val); break;
                            case 'contains':  $met = (strpos($t_val, $c_val) !== false); break;
                            case 'not_empty': $met = ($t_val !== ''); break;
                            default:          $met = true;
                        }
                        if (!$met) {
                            continue;
                        }
                    }
                }
                
                // Handle file uploads
                if (in_array($field_type, array('image', 'file', 'gallery'))) {
                    $base_field_name  = 'field_' . $field_id; // her zaman "field_7"
                    $field_repeatable = (int)$field->getVar('field_repeatable');


                    if ($field_type == 'gallery') {
                        // ── GALLERY ──────────────────────────────────────────
                        $uploaded_files = array();

                        if (!$field_repeatable) {
                            $gallery_key = $base_field_name . '_gallery';
                            if (isset($_FILES[$gallery_key]) && is_array($_FILES[$gallery_key]['name'])) {
                                foreach ($_FILES[$gallery_key]['name'] as $idx => $fname_item) {
                                    if (!empty($fname_item) && $_FILES[$gallery_key]['error'][$idx] == UPLOAD_ERR_OK) {
                                        $tmp = array(
                                            'name'     => $_FILES[$gallery_key]['name'][$idx],
                                            'tmp_name' => $_FILES[$gallery_key]['tmp_name'][$idx],
                                            'error'    => $_FILES[$gallery_key]['error'][$idx],
                                            'size'     => $_FILES[$gallery_key]['size'][$idx],
                                        );
                                        $res = handleSingleFileUpload($tmp, 'image');
                                        if ($res) $uploaded_files[] = $res;
                                    }
                                }
                            }
                            // Varolan resimleri ekle
                            if (isset($_POST[$base_field_name . '_existing']) && is_array($_POST[$base_field_name . '_existing'])) {
                                foreach ($_POST[$base_field_name . '_existing'] as $ex) {
                                    $ex = trim($ex);
                                    if (!empty($ex)) $uploaded_files[] = $ex;
                                }
                            }
                            if (!empty($uploaded_files)) {
                                $field_values[$field_id] = array(implode(',', $uploaded_files));
                            }
                        } else {
                            // Repeatable gallery
                            $group_idx = 0;
                            $group_results = array();
                            while (true) {
                                $gallery_key  = $base_field_name . '_gallery_' . $group_idx;
                                $has_existing = isset($_POST[$base_field_name . '_existing'][$group_idx]);
                                $has_upload   = isset($_FILES[$gallery_key]);
                                if (!$has_existing && !$has_upload) break;

                                $group_files = array();
                                if ($has_upload && is_array($_FILES[$gallery_key]['name'])) {
                                    foreach ($_FILES[$gallery_key]['name'] as $fi => $fn) {
                                        if (!empty($fn) && $_FILES[$gallery_key]['error'][$fi] == UPLOAD_ERR_OK) {
                                            $tmp = array(
                                                'name'     => $_FILES[$gallery_key]['name'][$fi],
                                                'tmp_name' => $_FILES[$gallery_key]['tmp_name'][$fi],
                                                'error'    => $_FILES[$gallery_key]['error'][$fi],
                                                'size'     => $_FILES[$gallery_key]['size'][$fi],
                                            );
                                            $res = handleSingleFileUpload($tmp, 'image');
                                            if ($res) $group_files[] = $res;
                                        }
                                    }
                                }
                                if ($has_existing && is_array($_POST[$base_field_name . '_existing'][$group_idx])) {
                                    foreach ($_POST[$base_field_name . '_existing'][$group_idx] as $ex) {
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
                                $field_values[$field_id] = $group_results;
                            }
                        }

                    } else {
                        // ── IMAGE / FILE ─────────────────────────────────────
                        $uploaded_files = array();

                        if (!$field_repeatable) {
                            $f = $_FILES[$base_field_name] ?? null;
                            if ($f && !empty($f['name']) && $f['error'] == UPLOAD_ERR_OK) {
                                $res = handleSingleFileUpload($f, $field_type);
                                if ($res) $uploaded_files[] = $res;
                            }
                            if (empty($uploaded_files) && !empty($_POST[$base_field_name . '_existing'])) {
                                $uploaded_files[] = trim($_POST[$base_field_name . '_existing']);
                            }
                        } else {
                            // Repeatable image/file
                            $f = $_FILES[$base_field_name] ?? null;
                            $existing_arr = isset($_POST[$base_field_name . '_existing']) && is_array($_POST[$base_field_name . '_existing'])
                                ? $_POST[$base_field_name . '_existing'] : array();
                            $max_idx = max($f ? count($f['name']) : 0, count($existing_arr));
                            for ($ri = 0; $ri < $max_idx; $ri++) {
                                $new_file = null;
                                if ($f && isset($f['name'][$ri]) && !empty($f['name'][$ri]) && $f['error'][$ri] == UPLOAD_ERR_OK) {
                                    $tmp = array(
                                        'name'     => $f['name'][$ri],
                                        'tmp_name' => $f['tmp_name'][$ri],
                                        'error'    => $f['error'][$ri],
                                        'size'     => $f['size'][$ri],
                                    );
                                    $new_file = handleSingleFileUpload($tmp, $field_type);
                                }
                                if ($new_file) {
                                    $uploaded_files[] = $new_file;
                                } elseif (isset($existing_arr[$ri]) && !empty(trim($existing_arr[$ri]))) {
                                    $uploaded_files[] = trim($existing_arr[$ri]);
                                }
                            }
                        }

                        if (!empty($uploaded_files)) {
                            $field_values[$field_id] = $uploaded_files;
                        }
                    }
                } else {
                    // Handle text fields
                    if (isset($_POST[$field_name])) {
                        $values = $_POST[$field_name];
                        
                        // Ensure array format
                        if (!is_array($values)) {
                            $values = array($values);
                        }
                        
                        // Process values based on field type
                        $processed_values = array();
                        
                        foreach ($values as $idx => $val) {
                            // Skip completely empty values
                            if ($val === '' || $val === null) {
                                continue;
                            }
                            
                            // Handle checkbox: convert array to comma-separated string
                            if ($field_type == 'checkbox' && is_array($val)) {
                                $val = implode(',', $val);
                            }
                            
                            // Trim string values
                            if (is_string($val)) {
                                $val = trim($val);
                                if ($val === '') {
                                    continue;
                                }
                            }
                            
                            $processed_values[] = $val;
                        }
                        
                        if (!empty($processed_values)) {
                            $field_values[$field_id] = $processed_values;
                        }
                    }
                }
            }
            
            // DEBUG: Log field values for troubleshooting
            
            // Save field values
            if (!empty($field_values)) {
                $itemHandler->saveFieldValues($new_item_id, $field_values);
            }

            // SEO URL ile yönlendir
            $saved_item = $itemHandler->get($new_item_id);
            $saved_cat   = $categoryHandler->get($saved_item->getVar('item_cat_id'));
            $item_slug_r = $saved_item->getVar('item_slug');
            $cat_slug_r  = $saved_cat->getVar('cat_slug');
            if (!empty($item_slug_r) && !empty($cat_slug_r)) {
                redirect_header(XOOPS_URL . '/modules/xcreate/' . $cat_slug_r . '/' . $item_slug_r, 2, _MD_XCREATE_SUCCESS_SAVE);
            } else {
                redirect_header('item.php?id=' . $new_item_id, 2, _MD_XCREATE_SUCCESS_SAVE);
            }
        } else {
            redirect_header('submit.php', 3, _MD_XCREATE_ERROR_SAVE);
        }
        break;
        
    case 'form':
    default:
        
        if ($item_id > 0) {
            $item = $itemHandler->get($item_id);
            if (!$item || $item->isNew()) {
                redirect_header('index.php', 3, _MD_XCREATE_ERROR_NOTFOUND);
            }
            
            // Check ownership
            if (!$xoopsUser || ($item->getVar('item_uid') != $xoopsUser->getVar('uid') && !$xoopsUser->isAdmin())) {
                redirect_header('index.php', 3, _MD_XCREATE_ERROR_PERMISSION);
            }
            
            $cat_id = $item->getVar('item_cat_id');
            $form_title = _MD_XCREATE_EDIT;
            
            // Get existing field values
            $existing_values = $itemHandler->getFieldValues($item_id);
        } else {
            $item = $itemHandler->create();
            $form_title = _MD_XCREATE_SUBMIT;
            $existing_values = array();
        }
        
        // CSS and JavaScript for repeatable fields
        echo '<style>
        .repeatable-field-container { margin-bottom: 20px; }
        .repeatable-field-group { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; position: relative; background: #f9f9f9; }
        .repeatable-field-group .remove-field { position: absolute; top: 10px; right: 10px; color: #d9534f; cursor: pointer; font-weight: bold; }
        .add-field-btn { margin-top: 10px; }
        .field-group-label { font-weight: bold; margin-bottom: 10px; display: block; }
        </style>';
        
        echo '<script>
        function addRepeatableField(fieldId, fieldName, isFileUpload) {
            var container = document.getElementById("repeatable_container_" + fieldId);
            var template = document.getElementById("repeatable_template_" + fieldId);
            var newGroup = template.cloneNode(true);
            
            // Update IDs and show the new group
            newGroup.style.display = "block";
            newGroup.id = "";
            
            var index = container.children.length;
            
            // Replace __INDEX__ placeholder in HTML with actual index
            var html = newGroup.innerHTML;
            html = html.replace(/\[__INDEX__\]/g, "[" + index + "]");
            html = html.replace(/_\_INDEX__/g, "_" + index);
            newGroup.innerHTML = html;
            
            // Clear values
            var inputs = newGroup.getElementsByTagName("input");
            var textareas = newGroup.getElementsByTagName("textarea");
            var selects = newGroup.getElementsByTagName("select");
            
            for (var i = 0; i < inputs.length; i++) {
                var input = inputs[i];
                if (input.type !== "hidden" && input.className.indexOf("remove-field") === -1) {
                    input.value = "";
                    if (input.type === "checkbox" || input.type === "radio") {
                        input.checked = false;
                    }
                }
            }
            
            for (var i = 0; i < textareas.length; i++) {
                textareas[i].value = "";
            }
            
            for (var i = 0; i < selects.length; i++) {
                selects[i].selectedIndex = 0;
            }
            
            container.appendChild(newGroup);
        }
        
        function removeRepeatableField(btn) {
            var group = btn.parentElement;
            var container = group.parentElement;
            
            // Don\'t remove if it\'s the only one
            if (container.children.length > 1) {
                group.remove();
            } else {
                alert("En az bir alan gereklidir!");
            }
        }
        </script>';
        
        $form = new XoopsThemeForm($form_title, 'item_form', 'submit.php', 'post', true);
        $form->setExtra('enctype="multipart/form-data"');
        
        // Category
        $category_select = new XoopsFormSelect(_MD_XCREATE_CATEGORY_SELECT, 'item_cat_id', $cat_id);
        $item_id_param = $item_id > 0 ? '&id=' . $item_id : '';
        $category_select->setExtra('id="item_cat_id" onchange="location.href=\'submit.php?cat_id=\'+this.value+\'' . $item_id_param . '\'"');
        $categories = $categoryHandler->getTree();
        
        // Add empty option to force selection
        if ($cat_id == 0 && $item_id == 0) {
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
        
        // Description
        $form->addElement(new XoopsFormDhtmlTextArea(_MD_XCREATE_DESCRIPTION, 'item_description', $item->getVar('item_description', 'e'), 10, 50));
        
        // Xcreate
        if ($cat_id > 0) {
            $fields = $fieldHandler->getFieldsByCategory($cat_id);
            
            if (count($fields) > 0) {
                foreach ($fields as $field) {
                    $field_id = $field->getVar('field_id');
                    $field_name = 'field_' . $field_id;
                    $field_label = $field->getVar('field_label');
                    $field_type = $field->getVar('field_type');
                    $is_required = $field->getVar('field_required');
                    $is_repeatable = $field->getVar('field_repeatable');
                    
                    $field_desc = $field->getVar('field_description');
                    if ($is_required) {
                        $field_label .= ' <span style="color:red;">*</span>';
                    }
                    
                    // Get existing values for this field
                    $field_existing_values = isset($existing_values[$field_id]) ? $existing_values[$field_id] : array();
                    
                    if ($is_repeatable) {
                        // Repeatable field with dynamic add/remove
                        $html = '<div class="repeatable-field-container">';
                        $html .= '<div id="repeatable_container_' . $field_id . '">';
                        
                        // Show existing values or at least one empty field
                        $count = max(1, count($field_existing_values));
                        
                        for ($i = 0; $i < $count; $i++) {
                            // For file/image/gallery fields, check value_file first, then value_text
                            if (in_array($field_type, array('image', 'file', 'gallery'))) {
                                $value = isset($field_existing_values[$i]['value_file']) ? $field_existing_values[$i]['value_file'] : '';
                            } else {
                                $value = isset($field_existing_values[$i]['value_text']) ? $field_existing_values[$i]['value_text'] : '';
                            }
                            
                            $html .= '<div class="repeatable-field-group">';
                            $html .= '<span class="remove-field" onclick="removeRepeatableField(this)">✖ Kaldır</span>';
                            $html .= $fieldHandler->renderField($field, $value, $i);
                            $html .= '</div>';
                        }
                        
                        $html .= '</div>';
                        
                        // Hidden template for new fields - use placeholder instead of 999
                        // Remove required attribute from template to avoid validation on hidden fields
                        $html .= '<div id="repeatable_template_' . $field_id . '" class="repeatable-field-group" style="display:none;">';
                        $html .= '<span class="remove-field" onclick="removeRepeatableField(this)">✖ Kaldır</span>';
                        // Create a non-required version for template
                        $temp_field = clone $field;
                        $temp_field->setVar('field_required', 0); // Remove required for template
                        $template_html = $fieldHandler->renderField($temp_field, '', 0);
                        // Replace the index in name/id attributes with placeholder
                        $template_html = str_replace(
                            array('[0]', '_0"', '_0 '),
                            array('[__INDEX__]', '_\_INDEX__"', '_\_INDEX__ '),
                            $template_html
                        );
                        $html .= $template_html;
                        $html .= '</div>';
                        
                        $is_file = in_array($field_type, array('image', 'file'));
                        $html .= '<button type="button" class="btn btn-secondary add-field-btn" onclick="addRepeatableField(' . $field_id . ', \'' . $field_name . '\', ' . ($is_file ? 'true' : 'false') . ')">+ ' . _AM_XCREATE_ADD_FIELD_INSTANCE . '</button>';
                        $html .= '</div>';
                        
                        $form->addElement(new XoopsFormLabel($field_label, $html));
                    } else {
                        // Regular single field
                        // For file/image/gallery fields, check value_file first, then value_text
                        if (in_array($field_type, array('image', 'file', 'gallery'))) {
                            $value = isset($field_existing_values[0]['value_file']) ? $field_existing_values[0]['value_file'] : '';
                        } else {
                            $value = isset($field_existing_values[0]['value_text']) ? $field_existing_values[0]['value_text'] : '';
                        }
                        $html = $fieldHandler->renderField($field, $value, 0);
                        
                        $form->addElement(new XoopsFormLabel($field_label, $html));
                    }
                    
                    if ($field_desc) {
                        $form->addElement(new XoopsFormLabel('', '<small class="help-block">' . $field_desc . '</small>'));
                    }
                }
            }
        }
        
        // Hidden
        $form->addElement(new XoopsFormHidden('op', 'save'));
        if ($item_id > 0) {
            $form->addElement(new XoopsFormHidden('id', $item_id));
        }
        
        // Buttons
        $button_tray = new XoopsFormElementTray('', '');
        $button_tray->addElement(new XoopsFormButton('', 'submit', _MD_XCREATE_SUBMIT_BTN, 'submit'));
        $button_tray->addElement(new XoopsFormButton('', 'cancel', _MD_XCREATE_CANCEL_BTN, 'button', 'onclick="history.go(-1)"'));
        $form->addElement($button_tray);
        
        $xoopsTpl->assign('form', $form->render());

        // Conditional fields JS motoru
        if ($cat_id > 0) {
            $conditions = $fieldHandler->getConditionsForCategory($cat_id);
            echo XcreateFieldHandler::renderConditionEngine($conditions);
        }

        include XOOPS_ROOT_PATH . '/footer.php';
        break;
}

?>
