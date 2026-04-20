<?php
/**
 * Xcreate SEO Slug Helper
 * Türkçe karakterleri destekleyen slug üretici
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

class XcreateSlug
{
    /**
     * Metinden SEO dostu slug üretir
     * Türkçe karakterleri Latin karşılıklarına dönüştürür
     */
    public static function create($text)
    {
        // Türkçe karakter dönüşümü
        $tr = array('ğ','Ğ','ü','Ü','ş','Ş','ı','İ','ö','Ö','ç','Ç');
        $en = array('g','g','u','u','s','s','i','i','o','o','c','c');
        $text = str_replace($tr, $en, $text);

        // Küçük harf
        $text = mb_strtolower($text, 'UTF-8');

        // Sadece harf, rakam, tire bırak
        $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);

        // Boşlukları tireye çevir
        $text = preg_replace('/[\s\-]+/', '-', trim($text));

        return $text;
    }

    /**
     * Slug'ın unique olmasını sağlar; çakışma varsa -2, -3... ekler
     * $table: 'xcreate_items' veya 'xcreate_categories'
     * $slug_col: 'item_slug' veya 'cat_slug'
     * $id_col: 'item_id' veya 'cat_id'
     * $exclude_id: güncelleme sırasında kendi ID'sini hariç tutar
     */
    public static function makeUnique($db, $table, $slug_col, $id_col, $base_slug, $exclude_id = 0)
    {
        $slug = $base_slug;
        $counter = 1;

        do {
            $safe_slug = $db->escape($slug);
            $full_table = $db->prefix($table);
            $sql = "SELECT COUNT(*) FROM {$full_table} WHERE {$slug_col} = '{$safe_slug}'";
            if ($exclude_id > 0) {
                $sql .= " AND {$id_col} != " . intval($exclude_id);
            }
            $result = $db->query($sql);
            list($count) = $db->fetchRow($result);

            if ($count == 0) {
                return $slug;
            }

            $counter++;
            $slug = $base_slug . '-' . $counter;
        } while (true);
    }

    /**
     * URL'yi oluşturur: /modules/xcreate/kategori-slug/icerik-slug
     * Sadece index sayfası için: /modules/xcreate/kategori-slug/
     */
    public static function itemUrl($cat_slug, $item_slug)
    {
        return XOOPS_URL . '/modules/xcreate/' . $cat_slug . '/' . $item_slug;
    }

    public static function categoryUrl($cat_slug)
    {
        return XOOPS_URL . '/modules/xcreate/' . $cat_slug . '/';
    }
}
