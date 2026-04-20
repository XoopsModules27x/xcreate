<?php
/**
 * XcreateGroup — Alan Grubu Sınıfı
 *
 * Her kategori için alanları sekmelere / accordion bölümlerine ayırır.
 * Bir grup: bir sekme başlığı, isteğe bağlı ikon, sıralama ve renk.
 *
 * Eren Yumak tarafından kodlanmıştır — Aymak
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

class XcreateGroup extends XoopsObject
{
    public function __construct()
    {
        $this->initVar('group_id',      XOBJ_DTYPE_INT,    0,   false);
        $this->initVar('group_cat_id',  XOBJ_DTYPE_INT,    0,   true);
        $this->initVar('group_name',    XOBJ_DTYPE_TXTBOX, '',  true, 100);
        $this->initVar('group_label',   XOBJ_DTYPE_TXTBOX, '',  true, 255);
        $this->initVar('group_icon',    XOBJ_DTYPE_TXTBOX, '',  false, 50);
        $this->initVar('group_color',   XOBJ_DTYPE_TXTBOX, '',  false, 20);
        $this->initVar('group_weight',  XOBJ_DTYPE_INT,    0,   false);
        $this->initVar('group_status',  XOBJ_DTYPE_INT,    1,   false);
        $this->initVar('group_created', XOBJ_DTYPE_INT,    0,   false);
    }
}

class XcreateGroupHandler extends XoopsPersistableObjectHandler
{
    public function __construct($db)
    {
        parent::__construct($db, 'xcreate_field_groups', 'XcreateGroup', 'group_id', 'group_label');
        $this->ensureTable();
    }

    /**
     * Tablo yoksa otomatik oluştur (eski kurulumlar için güvenli)
     */
    protected function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . $this->db->prefix('xcreate_field_groups') . "` (
            `group_id`      int(11) unsigned NOT NULL AUTO_INCREMENT,
            `group_cat_id`  int(11) unsigned NOT NULL,
            `group_name`    varchar(100) NOT NULL,
            `group_label`   varchar(255) NOT NULL,
            `group_icon`    varchar(50) NOT NULL DEFAULT '',
            `group_color`   varchar(20) NOT NULL DEFAULT '',
            `group_weight`  int(11) NOT NULL DEFAULT '0',
            `group_status`  tinyint(1) NOT NULL DEFAULT '1',
            `group_created` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`group_id`),
            KEY `group_cat_id` (`group_cat_id`),
            KEY `group_weight` (`group_weight`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->queryF($sql);

        // xcreate_fields tablosuna field_group_id kolonu yoksa ekle
        $col_res = $this->db->query("SHOW COLUMNS FROM " . $this->db->prefix('xcreate_fields') . " LIKE 'field_group_id'");
        if (!$col_res || !$this->db->fetchArray($col_res)) {
            $this->db->queryF("ALTER TABLE " . $this->db->prefix('xcreate_fields') .
                " ADD COLUMN `field_group_id` int(11) unsigned NOT NULL DEFAULT '0' AFTER `field_cat_id`");
        }
    }

    /**
     * Bir kategoriye ait grupları sıralı döndür
     */
    public function getGroupsByCategory($cat_id, $status = 1)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('group_cat_id', intval($cat_id)));
        if ($status !== null) {
            $criteria->add(new Criteria('group_status', intval($status)));
        }
        $criteria->setSort('group_weight, group_label');
        $criteria->setOrder('ASC');
        return $this->getObjects($criteria);
    }

    /**
     * Bir kategori için alanları gruplarına göre organize et.
     * Grupsuz alanlar "Genel" grubuna atar.
     *
     * Döner:
     * [
     *   [
     *     'group_id'    => 0,
     *     'group_name'  => 'genel',
     *     'group_label' => 'Genel',
     *     'group_icon'  => '',
     *     'group_color' => '',
     *     'fields'      => [ XcreateField, ... ]
     *   ],
     *   ...
     * ]
     */
    public function getGroupsWithFields($cat_id)
    {
        $groups  = $this->getGroupsByCategory($cat_id);
        $grouped = array();

        // Tanımlı grupları dizi haline getir
        $group_map = array(); // group_id => index in $grouped
        foreach ($groups as $group) {
            $gid = (int)$group->getVar('group_id');
            $idx = count($grouped);
            $group_map[$gid] = $idx;
            $grouped[] = array(
                'group_id'    => $gid,
                'group_name'  => $group->getVar('group_name'),
                'group_label' => $group->getVar('group_label'),
                'group_icon'  => $group->getVar('group_icon'),
                'group_color' => $group->getVar('group_color'),
                'fields'      => array(),
            );
        }

        // Grupsuz alanlar için "Genel" slotu — her zaman en sona eklenir
        $default_idx = null;

        // Alanları çek
        global $xoopsDB;
        $sql = "SELECT * FROM " . $xoopsDB->prefix('xcreate_fields') .
               " WHERE field_cat_id = " . intval($cat_id) .
               " AND field_status = 1" .
               " ORDER BY field_group_id ASC, field_weight ASC, field_label ASC";
        $res = $xoopsDB->query($sql);

        while ($row = $xoopsDB->fetchArray($res)) {
            $gid = (int)$row['field_group_id'];

            // Alan nesnesini oluştur
            $field = new XcreateField();
            $field->assignVars($row);

            if ($gid > 0 && isset($group_map[$gid])) {
                $grouped[$group_map[$gid]]['fields'][] = $field;
            } else {
                // Gruba atanmamış alan → varsayılan "Genel" grubu
                if ($default_idx === null) {
                    $default_idx = count($grouped);
                    $grouped[] = array(
                        'group_id'    => 0,
                        'group_name'  => 'genel',
                        'group_label' => 'Genel',
                        'group_icon'  => '',
                        'group_color' => '',
                        'fields'      => array(),
                    );
                }
                $grouped[$default_idx]['fields'][] = $field;
            }
        }

        // Boş grupları filtrele (isteğe bağlı: alanı olmayan gruplar gösterilmez)
        $grouped = array_filter($grouped, function($g) {
            return count($g['fields']) > 0;
        });

        return array_values($grouped);
    }

    /**
     * Grup sil (ilgili alanların group_id'sini sıfırla)
     */
    public function deleteGroup($group_id)
    {
        global $xoopsDB;
        $group_id = intval($group_id);

        // Alanlardaki referansı temizle
        $xoopsDB->queryF(
            "UPDATE " . $xoopsDB->prefix('xcreate_fields') .
            " SET field_group_id = 0 WHERE field_group_id = " . $group_id
        );

        return $this->delete($this->get($group_id), true);
    }

    /**
     * Bir kategorideki grupları basit id=>label dizisi olarak döndür (form için)
     */
    public function getGroupsForSelect($cat_id)
    {
        $groups = $this->getGroupsByCategory($cat_id);
        $result = array(0 => '— Grupsuz —');
        foreach ($groups as $g) {
            $result[(int)$g->getVar('group_id')] = $g->getVar('group_label');
        }
        return $result;
    }
}
