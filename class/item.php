<?php
/**
 * Item Class
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

class XcreateItem extends XoopsObject
{
    public function __construct()
    {
        $this->initVar('item_id', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('item_cat_id', XOBJ_DTYPE_INT, 0, true);
        $this->initVar('item_title', XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('item_description', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('item_uid', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('item_created', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('item_updated', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('item_published', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('item_slug', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('item_status', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('item_hits', XOBJ_DTYPE_INT, 0, false);
        // SEO Meta Alanları
        $this->initVar('item_meta_title', XOBJ_DTYPE_TXTBOX, '', false, 160);
        $this->initVar('item_meta_description', XOBJ_DTYPE_TXTBOX, '', false, 320);
        $this->initVar('item_meta_keywords', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('item_og_image', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('item_noindex', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('item_canonical', XOBJ_DTYPE_TXTBOX, '', false, 500);
    }
}

class XcreateItemHandler extends XoopsPersistableObjectHandler
{
    public function __construct($db)
    {
        parent::__construct($db, 'xcreate_items', 'XcreateItem', 'item_id', 'item_title');
    }

    public function getItemsByCategory($cat_id, $status = 1, $limit = 0, $start = 0)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('item_cat_id', $cat_id));
        if ($status !== null) {
            $criteria->add(new Criteria('item_status', $status));
        }
        $criteria->setSort('item_created');
        $criteria->setOrder('DESC');
        
        if ($limit > 0) {
            $criteria->setLimit($limit);
            $criteria->setStart($start);
        }
        
        return $this->getObjects($criteria);
    }

    public function getRecentItems($limit = 10, $status = 1)
    {
        $criteria = new CriteriaCompo();
        if ($status !== null) {
            $criteria->add(new Criteria('item_status', $status));
        }
        $criteria->setSort('item_created');
        $criteria->setOrder('DESC');
        $criteria->setLimit($limit);
        
        return $this->getObjects($criteria);
    }

    /**
     * Slug'a göre item döndür (kategori slug ile birlikte)
     */
    public function getBySlug($item_slug)
    {
        $safe = $this->db->escape($item_slug);
        $sql = "SELECT * FROM " . $this->table . " WHERE item_slug = '{$safe}' LIMIT 1";
        $result = $this->db->query($sql);
        if ($result && $this->db->getRowsNum($result) > 0) {
            $row = $this->db->fetchArray($result);
            $obj = $this->create(false);
            $obj->assignVars($row);
            return $obj;
        }
        return false;
    }

    /**
     * Item kaydedilirken slug otomatik oluştur
     */
    public function generateSlug($title, $exclude_id = 0)
    {
        if (!class_exists('XcreateSlug')) {
            include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/slug.php';
        }
        $base = XcreateSlug::create($title);
        if (empty($base)) {
            $base = 'item';
        }
        return XcreateSlug::makeUnique($this->db, 'xcreate_items', 'item_slug', 'item_id', $base, $exclude_id);
    }

    public function updateHits($item_id)
    {
        // IMPORTANT: $this->table is already prefixed by XoopsPersistableObjectHandler
        // Do NOT use $this->db->prefix($this->table) — that would double the prefix
        $sql = "UPDATE " . $this->table . " SET item_hits = item_hits + 1 WHERE item_id = " . intval($item_id);
        return $this->db->queryF($sql);
    }

    public function getFieldValues($item_id, $field_id = null)
    {
        global $xoopsDB;
        
        $sql = "SELECT * FROM " . $xoopsDB->prefix('xcreate_field_values') . " WHERE value_item_id = " . intval($item_id);
        
        if ($field_id !== null) {
            $sql .= " AND value_field_id = " . intval($field_id);
        }
        
        $sql .= " ORDER BY value_field_id, value_index";
        
        $result = $xoopsDB->query($sql);

        $values = array();
        if (!$result) {
            return $values;
        }
        while ($row = $xoopsDB->fetchArray($result)) {
            if (!isset($values[$row['value_field_id']])) {
                $values[$row['value_field_id']] = array();
            }
            $values[$row['value_field_id']][] = array(
                'value_id' => $row['value_id'],
                'value_text' => $row['value_text'],
                'value_file' => $row['value_file'],
                'value_index' => $row['value_index']
            );
        }
        
        return $values;
    }

    public function saveFieldValues($item_id, $field_values)
    {
        global $xoopsDB;
        
        
        if (empty($field_values)) {
            return true;
        }
        
        // Delete existing values
        $delete_sql = "DELETE FROM " . $xoopsDB->prefix('xcreate_field_values') . " WHERE value_item_id = " . intval($item_id);
        $delete_result = $xoopsDB->queryF($delete_sql);
        
        // Insert new values
        foreach ($field_values as $field_id => $values) {
            
            // Skip if empty
            if (empty($values)) {
                continue;
            }
            
            // Ensure array
            if (!is_array($values)) {
                $values = array($values);
            }
            
            // Process each value
            foreach ($values as $index => $value) {
                
                // Skip completely empty values
                if ($value === '' || $value === null || $value === false) {
                    continue;
                }
                
                $value_text = '';
                $value_file = '';
                
                // Determine if it's a file or text value
                if (is_array($value)) {
                    // Array of values (e.g., checkbox)
                    $value_text = implode(',', array_filter($value));
                } elseif (is_string($value)) {
                    // Check if it contains comma (might be gallery)
                    if (strpos($value, ',') !== false) {
                        // Check if it's a comma-separated list of files (gallery)
                        $possible_files = explode(',', $value);
                        $all_files = true;
                        foreach ($possible_files as $pf) {
                            $pf = trim($pf);
                            if (!preg_match('/^[a-f0-9]+_[0-9]+\.(jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx|zip|rar|txt|csv)$/i', $pf)) {
                                $all_files = false;
                                break;
                            }
                        }
                        
                        if ($all_files) {
                            // It's a gallery - store as file
                            $value_file = $value;
                        } else {
                            // It's text
                            $value_text = $value;
                        }
                    } elseif (preg_match('/^[a-f0-9]+_[0-9]+\.(jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx|zip|rar|txt|csv)$/i', $value)) {
                        // Single file
                        $value_file = $value;
                    } else {
                        // Text value
                        $value_text = $value;
                    }
                } else {
                    $value_text = (string)$value;
                }
                
                // Insert into database
                $sql = sprintf(
                    "INSERT INTO %s (value_item_id, value_field_id, value_index, value_text, value_file, value_created) VALUES (%d, %d, %d, %s, %s, %d)",
                    $xoopsDB->prefix('xcreate_field_values'),
                    intval($item_id),
                    intval($field_id),
                    intval($index),
                    $xoopsDB->quoteString($value_text),
                    $xoopsDB->quoteString($value_file),
                    time()
                );
                
                
                $insert_result = $xoopsDB->queryF($sql);
                if (!$insert_result) {
                    // Log error for debugging
                } else {
                }
            }
        }
        
        return true;
    }

    public function handleFileUpload($field_name, $field_type)
    {
        global $xoopsModuleConfig;
        
        if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] != UPLOAD_ERR_OK) {
            return '';
        }
        
        $upload_dir = XOOPS_ROOT_PATH . '/uploads/xcreate/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file = $_FILES[$field_name];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowed_ext = explode(',', $xoopsModuleConfig['upload_allowed_ext']);
        $allowed_ext = array_map('trim', $allowed_ext);
        
        if (!in_array($file_ext, $allowed_ext)) {
            return false;
        }
        
        // File size check
        $max_size = $xoopsModuleConfig['upload_maxsize'] * 1024; // Convert to bytes
        if ($file['size'] > $max_size) {
            return false;
        }
        
        // Generate unique filename
        $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return $new_filename;
        }
        
        return false;
    }

    /**
     * Delete an item and all its related data
     * @param object $item Item object to delete
     * @param bool $force Force deletion
     * @return bool Success status
     */
    public function delete($item, $force = false)
    {
        if (!is_object($item)) {
            return false;
        }

        $item_id = $item->getVar('item_id');
        
        // IMPORTANT: $this->table already contains the prefix!
        // Don't use $this->db->prefix($this->table) - it will double the prefix
        $sql = sprintf("DELETE FROM %s WHERE item_id = %u", 
            $this->table,  // Already prefixed by XoopsPersistableObjectHandler
            intval($item_id)
        );
        
        
        $result = $this->db->queryF($sql);
        
        if ($result) {
            return true;
        }
        
        return false;
    }
}

?>
