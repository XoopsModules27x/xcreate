<?php
/**
 * Field Class
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

class XcreateField extends XoopsObject
{
    public function __construct()
    {
        $this->initVar('field_id', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('field_cat_id', XOBJ_DTYPE_INT, 0, true);
        $this->initVar('field_name', XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('field_label', XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('field_type', XOBJ_DTYPE_TXTBOX, 'text', true, 50);
        $this->initVar('field_description', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('field_required', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('field_repeatable', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('field_condition', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('field_lookup_enabled',  XOBJ_DTYPE_INT, 0, false);
        $this->initVar('field_lookup_cat_id',   XOBJ_DTYPE_INT, 0, false);
        $this->initVar('field_lookup_field_id', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('field_default_value', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('field_validation', XOBJ_DTYPE_TXTBOX, '', false, 100);
        $this->initVar('field_weight', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('field_status', XOBJ_DTYPE_INT, 1, false);
        $this->initVar('field_created', XOBJ_DTYPE_INT, 0, false);
    }

    public static function getFieldTypes()
    {
        return array(
            'text' => _AM_XCREATE_FIELD_TYPE_TEXT,
            'textarea' => _AM_XCREATE_FIELD_TYPE_TEXTAREA,
            'editor' => _AM_XCREATE_FIELD_TYPE_EDITOR,
            'image' => _AM_XCREATE_FIELD_TYPE_IMAGE,
            'gallery' => _AM_XCREATE_FIELD_TYPE_GALLERY,
            'file' => _AM_XCREATE_FIELD_TYPE_FILE,
            'select' => _AM_XCREATE_FIELD_TYPE_SELECT,
            'checkbox' => _AM_XCREATE_FIELD_TYPE_CHECKBOX,
            'radio' => _AM_XCREATE_FIELD_TYPE_RADIO,
            'date' => _AM_XCREATE_FIELD_TYPE_DATE,
            'datetime' => _AM_XCREATE_FIELD_TYPE_DATETIME,
            'number' => _AM_XCREATE_FIELD_TYPE_NUMBER,
            'email' => _AM_XCREATE_FIELD_TYPE_EMAIL,
            'url' => _AM_XCREATE_FIELD_TYPE_URL,
            'color' => _AM_XCREATE_FIELD_TYPE_COLOR
        );
    }
}

class XcreateFieldHandler extends XoopsPersistableObjectHandler
{
    public function __construct($db)
    {
        parent::__construct($db, 'xcreate_fields', 'XcreateField', 'field_id', 'field_label');
    }

    /**
     * Conditional fields için JS motorunu üretir.
     * $conditions: getConditionsForCategory() çıktısı
     */
    public static function renderConditionEngine($conditions)
    {
        if (empty($conditions)) return '';

        $json = json_encode($conditions, JSON_UNESCAPED_UNICODE);

        $js  = '<script>' . "\n";
        $js .= '(function() {' . "\n";
        $js .= '    var xcreateConditions = ' . $json . ';' . "\n";
        $js .= '
    function xcreateGetFieldValue(fieldId) {
        var sel = document.querySelector("[name=\'field_" + fieldId + "\'], [name^=\'field_" + fieldId + "[\']");
        if (!sel) return "";
        if (sel.tagName === "SELECT") return sel.value;
        if (sel.type === "radio") {
            var r = document.querySelector("[name=\'field_" + fieldId + "\']:checked");
            return r ? r.value : "";
        }
        if (sel.type === "checkbox") {
            var checked = document.querySelectorAll("[name=\'field_" + fieldId + "[]\']:checked");
            return Array.prototype.slice.call(checked).map(function(c){ return c.value; }).join(",");
        }
        return sel.value || "";
    }

    function xcreateEvaluateCondition(cond) {
        var triggerVal = xcreateGetFieldValue(cond.trigger_field_id);
        var testVal    = (cond.value || "").toString();
        switch (cond.operator) {
            case "==":       return triggerVal === testVal;
            case "!=":       return triggerVal !== testVal;
            case "contains": return triggerVal.indexOf(testVal) !== -1;
            case "not_empty":return triggerVal.trim() !== "";
            default:         return true;
        }
    }

    function xcreateApplyConditions() {
        for (var i = 0; i < xcreateConditions.length; i++) {
            var cond = xcreateConditions[i];
            var el = document.getElementById("field_" + cond.target_field_id + "_0");
            if (!el) continue;
            var show = xcreateEvaluateCondition(cond);
            var row = el;
            while (row && row.tagName !== "TR") { row = row.parentElement; }
            if (row) {
                row.style.display = show ? "" : "none";
                var prev = row.previousElementSibling;
                if (prev && prev.tagName === "TR" && prev.querySelector("label")) {
                    prev.style.display = show ? "" : "none";
                }
            } else {
                var wrapper = el.parentElement;
                if (wrapper) wrapper.style.display = show ? "" : "none";
            }
        }
    }

    function xcreateInitConditions() {
        xcreateApplyConditions();
        var form = document.getElementById("item_form");
        if (form) {
            form.addEventListener("change", xcreateApplyConditions);
            form.addEventListener("input", xcreateApplyConditions);
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", xcreateInitConditions);
    } else {
        xcreateInitConditions();
    }
';
        $js .= '})();' . "\n";
        $js .= '</script>' . "\n";

        return $js;
    }

    public function getFieldsByCategory($cat_id, $status = 1)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('field_cat_id', $cat_id));
        if ($status !== null) {
            $criteria->add(new Criteria('field_status', $status));
        }
        $criteria->setSort('field_weight, field_label');
        $criteria->setOrder('ASC');
        
        return $this->getObjects($criteria);
    }

    /**
     * Bir kategorideki tüm alanların koşul verilerini JS için hazırlar
     * Döner: [{"target_field_id":7,"trigger_field_id":5,"operator":"==","value":"Kiralık"}, ...]
     */
    public function getConditionsForCategory($cat_id)
    {
        $fields = $this->getFieldsByCategory($cat_id);
        $conditions = array();
        foreach ($fields as $field) {
            $cond_raw = $field->getVar('field_condition');
            if (empty($cond_raw)) continue;
            $cond = json_decode($cond_raw, true);
            if (!$cond || empty($cond['field_id'])) continue;
            $conditions[] = array(
                'target_field_id'  => (int)$field->getVar('field_id'),
                'trigger_field_id' => (int)$cond['field_id'],
                'operator'         => isset($cond['operator']) ? $cond['operator'] : '==',
                'value'            => isset($cond['value']) ? $cond['value'] : '',
            );
        }
        return $conditions;
    }

    public function renderField($field, $value = '', $index = 0, $name_suffix = '')
    {
        $field_id_num = $field->getVar('field_id');
        $field_name = 'field_' . $field_id_num . $name_suffix;
        $is_repeatable = $field->getVar('field_repeatable');
        
        // For repeatable fields with index > 0, add array notation
        if ($is_repeatable && $index >= 0) {
            $field_name .= '[' . $index . ']';
        }
        
        $field_id = 'field_' . $field_id_num . '_' . $index;
        $field_type = $field->getVar('field_type');
        $required = $field->getVar('field_required') ? 'required' : '';
        
        $html = '';
        
        switch ($field_type) {
            case 'text':
                $html .= '<input type="text" name="' . $field_name . '" id="' . $field_id . '" class="form-control" value="' . htmlspecialchars($value, ENT_QUOTES) . '" ' . $required . '>';
                break;
                
            case 'textarea':
                $html .= '<textarea name="' . $field_name . '" id="' . $field_id . '" class="form-control" rows="5" ' . $required . '>' . htmlspecialchars($value, ENT_QUOTES) . '</textarea>';
                break;
                
            case 'editor':
                include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';
                $editor = new XoopsFormDhtmlTextArea('', $field_name, $value, 10, 50);
                $html .= $editor->render();
                break;
                
            case 'image':
            case 'file':
                /*
                 * İsim şeması:
                 *   Tekli (non-repeatable) : name="field_7"          → $_FILES['field_7']
                 *                            existing: name="field_7_existing"
                 *   Repeatable             : name="field_7[0]"       → $_FILES['field_7'][name][0]
                 *                            existing: name="field_7_existing[0]"
                 *
                 * $field_name zaten repeatable ise "field_7[0]" gelir (üstte eklendi).
                 * Tekli ise "field_7" gelir.
                 * Existing ismi için köşeli parantezi sıyırıp base ismi kullanıyoruz.
                 */
                $base_field_name = 'field_' . $field_id_num; // her zaman "field_7"
                $html .= '<input type="file" name="' . $field_name . '" id="' . $field_id . '" class="form-control">';
                if ($value) {
                    $upload_url = XOOPS_URL . '/uploads/xcreate/';
                    if ($field_type == 'image') {
                        $html .= '<div class="mt-2"><img src="' . $upload_url . $value . '" alt="" style="max-width: 200px; max-height: 200px; border-radius:6px;"></div>';
                    } else {
                        $html .= '<div class="mt-2"><a href="' . $upload_url . $value . '" target="_blank">' . $value . '</a></div>';
                    }
                    if ($is_repeatable) {
                        // Repeatable: existing[index] → $_POST['field_7_existing'][0]
                        $html .= '<input type="hidden" name="' . $base_field_name . '_existing[' . $index . ']" value="' . htmlspecialchars($value, ENT_QUOTES) . '">';
                    } else {
                        // Tekli: existing → $_POST['field_7_existing']
                        $html .= '<input type="hidden" name="' . $base_field_name . '_existing" value="' . htmlspecialchars($value, ENT_QUOTES) . '">';
                    }
                }
                break;
                
            case 'gallery':
                /*
                 * İsim şeması:
                 *   Tekli (non-repeatable) : name="field_7_gallery[]"          → $_FILES['field_7_gallery'][]
                 *                            existing: name="field_7_existing[]"
                 *   Repeatable             : name="field_7_gallery[0][]"       — PHP desteklemez!
                 *                            Bu yüzden repeatable gallery'de her grup ayrı isim alır:
                 *                            name="field_7_gallery_0[]"        → $_FILES['field_7_gallery_0'][]
                 *                            existing: name="field_7_existing[0][]"  (bir grup içindeki resimler)
                 *
                 * NOT: Repeatable gallery nadiren kullanılır; ana senaryo tekli gallery.
                 */
                $base_field_name = 'field_' . $field_id_num;
                if ($is_repeatable) {
                    $gallery_upload_name = $base_field_name . '_gallery_' . $index . '[]';
                    $existing_name       = $base_field_name . '_existing[' . $index . '][]';
                } else {
                    $gallery_upload_name = $base_field_name . '_gallery[]';
                    $existing_name       = $base_field_name . '_existing[]';
                }

                $html .= '<input type="file" name="' . $gallery_upload_name . '" id="' . $field_id . '" class="form-control" multiple accept="image/*">';
                $html .= '<small class="form-text text-muted" style="display:block;margin-top:4px;">Birden fazla resim seçebilirsiniz (Ctrl/Cmd ile çoklu seçim)</small>';
                
                if ($value) {
                    $images = is_array($value) ? $value : explode(',', $value);
                    $upload_url = XOOPS_URL . '/uploads/xcreate/';
                    $unique_id = 'gallery_' . $field_id_num . '_' . $index;
                    $html .= '<div class="mt-3 gallery-preview" id="' . $unique_id . '" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">';
                    foreach ($images as $img) {
                        $img = trim($img);
                        if (!empty($img)) {
                            $html .= '<div class="gallery-item" style="position: relative; display: inline-block;">';
                            $html .= '<img src="' . $upload_url . $img . '" alt="" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb;">';
                            $html .= '<button type="button" onclick="xcreateRemoveGalleryItem(this)" style="position: absolute; top: 4px; right: 4px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 22px; height: 22px; cursor: pointer; font-size: 11px; line-height: 1; padding:0;">✕</button>';
                            $html .= '<input type="hidden" name="' . $existing_name . '" value="' . htmlspecialchars($img, ENT_QUOTES) . '">';
                            $html .= '</div>';
                        }
                    }
                    $html .= '</div>';
                }
                
                // Galeri silme JS (bir kez yeterli, çoklu yüklemede sorun olmaz)
                $html .= '<script>
                if (typeof xcreateRemoveGalleryItem === "undefined") {
                    function xcreateRemoveGalleryItem(btn) {
                        if (confirm("Bu resmi kaldırmak istediğinizden emin misiniz?")) {
                            btn.closest(".gallery-item").remove();
                        }
                    }
                }
                </script>';
                break;
                
            case 'select':
                $html .= $this->renderSelectField($field, $field_name, $field_id, $value, $required);
                break;
                
            case 'checkbox':
                $html .= $this->renderCheckboxField($field, $field_name, $field_id, $value);
                break;
                
            case 'radio':
                $html .= $this->renderRadioField($field, $field_name, $field_id, $value, $required);
                break;
                
            case 'date':
                $html .= '<input type="date" name="' . $field_name . '" id="' . $field_id . '" class="form-control" value="' . htmlspecialchars($value, ENT_QUOTES) . '" ' . $required . '>';
                break;
                
            case 'datetime':
                $html .= '<input type="datetime-local" name="' . $field_name . '" id="' . $field_id . '" class="form-control" value="' . htmlspecialchars($value, ENT_QUOTES) . '" ' . $required . '>';
                break;
                
            case 'number':
                $html .= '<input type="number" name="' . $field_name . '" id="' . $field_id . '" class="form-control" value="' . htmlspecialchars($value, ENT_QUOTES) . '" ' . $required . '>';
                break;
                
            case 'email':
                $html .= '<input type="email" name="' . $field_name . '" id="' . $field_id . '" class="form-control" value="' . htmlspecialchars($value, ENT_QUOTES) . '" ' . $required . '>';
                break;
                
            case 'url':
                $html .= '<input type="url" name="' . $field_name . '" id="' . $field_id . '" class="form-control" value="' . htmlspecialchars($value, ENT_QUOTES) . '" ' . $required . '>';
                break;
                
            case 'color':
                $html .= '<input type="color" name="' . $field_name . '" id="' . $field_id . '" class="form-control" value="' . htmlspecialchars($value, ENT_QUOTES) . '" ' . $required . '>';
                break;
        }
        
        return $html;
    }

    private function renderSelectField($field, $field_name, $field_id, $value, $required)
    {
        $options = $this->getFieldOptions($field->getVar('field_id'));
        $html = '<select name="' . $field_name . '" id="' . $field_id . '" class="form-control" ' . $required . '>';
        $html .= '<option value="">Seçiniz...</option>';
        
        foreach ($options as $option) {
            $selected = ($option['value'] == $value) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($option['value'], ENT_QUOTES) . '" ' . $selected . '>' . htmlspecialchars($option['label'], ENT_QUOTES) . '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }

    private function renderCheckboxField($field, $field_name, $field_id, $value)
    {
        $options = $this->getFieldOptions($field->getVar('field_id'));
        $values = is_array($value) ? $value : explode(',', $value);
        $html = '<div class="checkbox-group">';
        
        foreach ($options as $i => $option) {
            $checked = in_array($option['value'], $values) ? 'checked' : '';
            $html .= '<div class="form-check">';
            $html .= '<input type="checkbox" name="' . $field_name . '[]" id="' . $field_id . '_' . $i . '" class="form-check-input" value="' . htmlspecialchars($option['value'], ENT_QUOTES) . '" ' . $checked . '>';
            $html .= '<label class="form-check-label" for="' . $field_id . '_' . $i . '">' . htmlspecialchars($option['label'], ENT_QUOTES) . '</label>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    private function renderRadioField($field, $field_name, $field_id, $value, $required)
    {
        $options = $this->getFieldOptions($field->getVar('field_id'));
        $html = '<div class="radio-group">';
        
        foreach ($options as $i => $option) {
            $checked = ($option['value'] == $value) ? 'checked' : '';
            $html .= '<div class="form-check">';
            $html .= '<input type="radio" name="' . $field_name . '" id="' . $field_id . '_' . $i . '" class="form-check-input" value="' . htmlspecialchars($option['value'], ENT_QUOTES) . '" ' . $checked . ' ' . $required . '>';
            $html .= '<label class="form-check-label" for="' . $field_id . '_' . $i . '">' . htmlspecialchars($option['label'], ENT_QUOTES) . '</label>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    public function getFieldOptions($field_id)
    {
        global $xoopsDB;
        
        $sql = "SELECT * FROM " . $xoopsDB->prefix('xcreate_field_options') . " WHERE option_field_id = " . intval($field_id) . " ORDER BY option_weight, option_label";
        $result = $xoopsDB->query($sql);
        
        $options = array();
        if (!$result) {
            return $options;
        }
        while ($row = $xoopsDB->fetchArray($result)) {
            $options[] = array(
                'id' => $row['option_id'],
                'value' => $row['option_value'],
                'label' => $row['option_label'],
                'weight' => $row['option_weight']
            );
        }
        
        return $options;
    }

    public function saveFieldOptions($field_id, $options)
    {
        global $xoopsDB;
        
        // Delete existing options
        $xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('xcreate_field_options') . " WHERE option_field_id = " . intval($field_id));
        
        // Insert new options
        if (is_array($options) && count($options) > 0) {
            foreach ($options as $i => $option) {
                if (!empty($option['value']) && !empty($option['label'])) {
                    $sql = sprintf(
                        "INSERT INTO %s (option_field_id, option_value, option_label, option_weight) VALUES (%d, %s, %s, %d)",
                        $xoopsDB->prefix('xcreate_field_options'),
                        intval($field_id),
                        $xoopsDB->quoteString($option['value']),
                        $xoopsDB->quoteString($option['label']),
                        intval($i)
                    );
                    $xoopsDB->queryF($sql);
                }
            }
        }
        
        return true;
    }

    /**
     * Delete a field and all its related data
     * @param object $field Field object to delete
     * @param bool $force Force deletion
     * @return bool Success status
     */
    public function delete($field, $force = false)
    {
        if (!is_object($field)) {
            return false;
        }

        $field_id = $field->getVar('field_id');
        
        // IMPORTANT: $this->table already contains the prefix!
        // Don't use $this->db->prefix($this->table) - it will double the prefix
        $sql = sprintf("DELETE FROM %s WHERE field_id = %u", 
            $this->table,  // Already prefixed by XoopsPersistableObjectHandler
            intval($field_id)
        );
        
        
        $result = $this->db->queryF($sql);
        
        if ($result) {
            return true;
        }
        
        return false;
    }
}

?>
