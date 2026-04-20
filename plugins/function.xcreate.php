<?php
/**
 * Smarty plugin for Xcreate
 * Usage: {xcreate category="2" template="template_name" limit="10" order="date" from="0"}
 */

function smarty_function_xcreate($params, &$smarty) {
    global $xoopsDB;
    
    // Parameters
    $category = isset($params['category']) ? intval($params['category']) : 0;
    $template = isset($params['template']) ? $params['template'] : 'default';
    $limit = isset($params['limit']) ? intval($params['limit']) : 10;
    $from = isset($params['from']) ? intval($params['from']) : 0;
    $order = isset($params['order']) ? $params['order'] : 'date'; // date, random, hits
    $cache = isset($params['cache']) ? $params['cache'] : 'no';
    $available = isset($params['available']) ? $params['available'] : ''; // main, index, etc.
    
    // Build SQL query
    $sql = "SELECT i.*, c.cat_name, c.cat_slug 
            FROM " . $xoopsDB->prefix('xcreate_items') . " i 
            LEFT JOIN " . $xoopsDB->prefix('xcreate_categories') . " c ON i.item_cat_id = c.cat_id 
            WHERE i.item_status = 1";
    
    if ($category > 0) {
        $sql .= " AND i.item_cat_id = " . $category;
    }
    
    // Order
    switch ($order) {
        case 'random':
        case 'rand':
            $sql .= " ORDER BY RAND()";
            break;
        case 'hits':
        case 'views':
            $sql .= " ORDER BY i.item_hits DESC";
            break;
        case 'title':
            $sql .= " ORDER BY i.item_title ASC";
            break;
        case 'date':
        default:
            $sql .= " ORDER BY i.item_created DESC";
            break;
    }
    
    $sql .= " LIMIT " . $from . ", " . $limit;
    
    $result = $xoopsDB->query($sql);
    
    $items = array();
    while ($row = $xoopsDB->fetchArray($result)) {
        // Get dynamic fields for this item
        include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
        include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';
        
        $itemHandler = new XcreateItemHandler($xoopsDB);
        $fieldHandler = new XcreateFieldHandler($xoopsDB);
        
        $field_values = $itemHandler->getFieldValues($row['item_id']);
        $fields = $fieldHandler->getFieldsByCategory($row['item_cat_id']);
        
        $item_fields = array();
        foreach ($fields as $field) {
            $field_id = $field->getVar('field_id');
            $field_name = $field->getVar('field_name');
            $field_type = $field->getVar('field_type');
            
            if (isset($field_values[$field_id])) {
                $values = $field_values[$field_id];
                $raw_values = array();
                
                foreach ($values as $value) {
                    if ($value['value_file']) {
                        $raw_values[] = XOOPS_URL . '/uploads/xcreate/' . $value['value_file'];
                    } else {
                        $raw_values[] = $value['value_text'];
                    }
                }
                
                $safe_name = str_replace('-', '_', $field_name);
                if (count($raw_values) == 1) {
                    $item_fields[$safe_name] = $raw_values[0];
                } else {
                    $item_fields[$safe_name] = $raw_values;
                }
            }
        }
        
        $items[] = array_merge(array(
            'id' => $row['item_id'],
            'title' => $row['item_title'],
            'description' => $row['item_description'],
            'category_name' => $row['cat_name'],
            'category_id' => $row['item_cat_id'],
            'created' => $row['item_created'],
            'updated' => $row['item_updated'],
            'hits' => $row['item_hits'],
            'url' => (!empty($row['item_slug']) && !empty($row['cat_slug'])) ? XOOPS_URL . '/modules/xcreate/' . $row['cat_slug'] . '/' . $row['item_slug'] : XOOPS_URL . '/modules/xcreate/item.php?id=' . $row['item_id'],
            'category_url' => !empty($row['cat_slug']) ? XOOPS_URL . '/modules/xcreate/' . $row['cat_slug'] . '/' : XOOPS_URL . '/modules/xcreate/index.php?cat_id=' . $row['item_cat_id']
        ), $item_fields);
    }
    
    // Assign to template
    $smarty->assign('xcreate_items', $items);
    
    // Load template
    if ($template != 'default') {
        // Check if template file exists
        $tpl_file = XOOPS_ROOT_PATH . '/modules/xcreate/templates/custom/' . $template . '.tpl';
        if (file_exists($tpl_file)) {
            // Copy to cache if not exists
            $cache_tpl = XOOPS_ROOT_PATH . '/templates_c/xcreate_custom_' . $template . '.tpl';
            if (!file_exists($cache_tpl)) {
                copy($tpl_file, $cache_tpl);
            }
            
            // Fetch and return
            return $smarty->fetch('file:' . $tpl_file);
        } else {
            // Template not found, return error or empty
            return '<!-- Template not found: ' . $template . ' -->';
        }
    }
    
    // Return empty for default (items already assigned)
    return '';
}

?>
