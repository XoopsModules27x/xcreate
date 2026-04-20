<?php
/**
 * Category Class
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

class XcreateCategory extends XoopsObject
{
    public function __construct()
    {
        $this->initVar('cat_id', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('cat_pid', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('cat_name', XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('cat_slug', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('cat_description', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('cat_image', XOBJ_DTYPE_TXTBOX, '', false, 255);
        // SEO Meta Alanları
        $this->initVar('cat_meta_title', XOBJ_DTYPE_TXTBOX, '', false, 160);
        $this->initVar('cat_meta_description', XOBJ_DTYPE_TXTBOX, '', false, 320);
        $this->initVar('cat_meta_keywords', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('cat_og_image', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('cat_noindex', XOBJ_DTYPE_INT, 0, false);
        // SEO Meta Alanlari
        $this->initVar('cat_meta_title', XOBJ_DTYPE_TXTBOX, '', false, 160);
        $this->initVar('cat_meta_description', XOBJ_DTYPE_TXTBOX, '', false, 320);
        $this->initVar('cat_meta_keywords', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('cat_og_image', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('cat_noindex', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('cat_template', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('cat_list_template', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('cat_weight', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('cat_created', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('cat_updated', XOBJ_DTYPE_INT, 0, false);
    }
}

class XcreateCategoryHandler extends XoopsPersistableObjectHandler
{
    public function __construct($db)
    {
        parent::__construct($db, 'xcreate_categories', 'XcreateCategory', 'cat_id', 'cat_name');
    }

    public function getBySlug($cat_slug)
    {
        $safe = $this->db->escape($cat_slug);
        $sql = "SELECT * FROM " . $this->table . " WHERE cat_slug = '{$safe}' LIMIT 1";
        $result = $this->db->query($sql);
        if ($result && $this->db->getRowsNum($result) > 0) {
            $row = $this->db->fetchArray($result);
            $obj = $this->create(false);
            $obj->assignVars($row);
            return $obj;
        }
        return false;
    }

    public function generateSlug($name, $exclude_id = 0)
    {
        if (!class_exists('XcreateSlug')) {
            include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/slug.php';
        }
        $base = XcreateSlug::create($name);
        if (empty($base)) {
            $base = 'kategori';
        }
        return XcreateSlug::makeUnique($this->db, 'xcreate_categories', 'cat_slug', 'cat_id', $base, $exclude_id);
    }

    public function getTree($pid = 0, $level = 0)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('cat_pid', $pid));
        $criteria->setSort('cat_weight, cat_name');
        $criteria->setOrder('ASC');
        
        $categories = $this->getObjects($criteria);
        $tree = array();
        
        foreach ($categories as $category) {
            $category->setVar('level', $level);
            $tree[] = $category;
            $children = $this->getTree($category->getVar('cat_id'), $level + 1);
            $tree = array_merge($tree, $children);
        }
        
        return $tree;
    }

    public function getParentPath($cat_id)
    {
        $path = array();
        $category = $this->get($cat_id);
        
        if ($category && !$category->isNew()) {
            $path[] = $category;
            $pid = $category->getVar('cat_pid');
            
            while ($pid > 0) {
                $parent = $this->get($pid);
                if ($parent && !$parent->isNew()) {
                    array_unshift($path, $parent);
                    $pid = $parent->getVar('cat_pid');
                } else {
                    break;
                }
            }
        }
        
        return $path;
    }

    public function hasChildren($cat_id)
    {
        $criteria = new Criteria('cat_pid', $cat_id);
        return $this->getCount($criteria) > 0;
    }

    /**
     * Delete a category and all its related data
     * @param object $category Category object to delete
     * @param bool $force Force deletion
     * @return bool Success status
     */
    public function delete($category, $force = false)
    {
        if (!is_object($category)) {
            return false;
        }

        $cat_id = $category->getVar('cat_id');
        
        // IMPORTANT: $this->table already contains the prefix!
        // Don't use $this->db->prefix($this->table) - it will double the prefix
        $sql = sprintf("DELETE FROM %s WHERE cat_id = %u", 
            $this->table,  // Already prefixed by XoopsPersistableObjectHandler
            intval($cat_id)
        );
        
        
        $result = $this->db->queryF($sql);
        
        if ($result) {
            return true;
        }
        
        return false;
    }
}

?>
