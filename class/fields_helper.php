<?php
/**
 * XcreateFieldsHelper
 *
 * İlave alanları herhangi bir sayfada (item.php, index.php, bloklar, özel TPL)
 * kolayca Smarty'e atamak için merkezi yardımcı sınıf.
 *
 * Kullanım — tek item için (item.php, özel TPL):
 *   XcreateFieldsHelper::assignItemFields($xoopsTpl, $item_id, $cat_id);
 *
 * Kullanım — liste için (index.php, bloklar):
 *   $item_list = XcreateFieldsHelper::appendFieldsToList($item_list, $items_raw);
 *   // Artık her $item_list elemanında 'fields' anahtarı ve 'f_ALAN_ADI' anahtarları var.
 *
 * Eren Yumak tarafından kodlanmıştır — Aymak
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

if (!class_exists('XcreateFieldHandler')) {
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';
}
if (!class_exists('XcreateItemHandler')) {
    include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
}

class XcreateFieldsHelper
{
    /**
     * Bir item'ın ilave alanlarını işleyip yapılandırılmış bir dizi döner.
     *
     * Dönen dizi her alan için:
     *   [
     *     'name'          => 'fiyat',          // alan_adi (field_name)
     *     'label'         => 'Fiyat',           // etiket
     *     'type'          => 'text',             // alan tipi
     *     'value'         => 'xxx',              // tekli değer (ham, ilk değer)
     *     'value_display' => '<a ...>xxx</a>',   // görüntüleme HTML'i (tekli)
     *     'values'        => ['x','y'],          // ham değerler dizisi (repeatable)
     *     'values_display'=> ['<..>','<..>'],    // görüntüleme HTML'i dizisi
     *     'is_repeatable' => 0|1,
     *   ]
     *
     * @param int $item_id
     * @param int $cat_id
     * @return array
     */
    public static function buildFields($item_id, $cat_id)
    {
        global $xoopsDB;

        $fieldHandler = new XcreateFieldHandler($xoopsDB);
        $itemHandler  = new XcreateItemHandler($xoopsDB);

        $fields       = $fieldHandler->getFieldsByCategory($cat_id);
        $field_values = $itemHandler->getFieldValues($item_id);

        $result = array();

        foreach ($fields as $field) {
            $field_id      = $field->getVar('field_id');
            $field_name    = $field->getVar('field_name');
            $field_type    = $field->getVar('field_type');
            $is_repeatable = (int)$field->getVar('field_repeatable');

            if (!isset($field_values[$field_id])) {
                continue;
            }

            $values         = $field_values[$field_id];
            $display_values = array();
            $raw_values     = array();

            foreach ($values as $value) {
                $val_text = $value['value_text'];
                $val_file = $value['value_file'];

                // Ham değer (URL veya metin)
                if ($val_file) {
                    $raw_values[] = XOOPS_URL . '/uploads/xcreate/' . $val_file;
                } else {
                    $raw_values[] = $val_text;
                }

                // Görüntüleme HTML'i
                if ($field_type === 'gallery') {
                    if ($val_file) {
                        $gallery_images = explode(',', $val_file);
                        $upload_url     = XOOPS_URL . '/uploads/xcreate/';
                        $gallery_html   = '<div class="xcreate-gallery" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:15px;margin:15px 0;">';
                        foreach ($gallery_images as $img) {
                            $img = trim($img);
                            if ($img !== '') {
                                $gallery_html .= '<div class="gallery-item" style="position:relative;overflow:hidden;border-radius:8px;border:2px solid #e5e7eb;">'
                                    . '<a href="' . $upload_url . $img . '" target="_blank" rel="noopener">'
                                    . '<img src="' . $upload_url . $img . '" alt="" style="width:100%;height:200px;object-fit:cover;display:block;">'
                                    . '</a></div>';
                                // Galeri için raw_values her resmi ayrı ekle
                                $raw_values[] = $upload_url . $img;
                            }
                        }
                        $gallery_html   .= '</div>';
                        $display_values[] = $gallery_html;
                    }
                } elseif ($field_type === 'image') {
                    if ($val_file) {
                        $display_values[] = '<img src="' . XOOPS_URL . '/uploads/xcreate/' . $val_file . '" alt="" class="xcreate-field-image" style="max-width:100%;">';
                    }
                } elseif ($field_type === 'file') {
                    if ($val_file) {
                        $display_values[] = '<a href="' . XOOPS_URL . '/uploads/xcreate/' . $val_file . '" target="_blank">' . htmlspecialchars($val_file) . '</a>';
                    }
                } elseif ($field_type === 'checkbox') {
                    $display_values[] = str_replace(',', ', ', $val_text);
                } elseif ($field_type === 'url') {
                    $display_values[] = '<a href="' . htmlspecialchars($val_text, ENT_QUOTES) . '" target="_blank">' . htmlspecialchars($val_text) . '</a>';
                } elseif ($field_type === 'email') {
                    $display_values[] = '<a href="mailto:' . htmlspecialchars($val_text, ENT_QUOTES) . '">' . htmlspecialchars($val_text) . '</a>';
                } elseif ($field_type === 'editor') {
                    $display_values[] = $val_text; // HTML içerik, kaçırma
                } else {
                    $display_values[] = htmlspecialchars($val_text);
                }
            }

            if (empty($display_values) && empty($raw_values)) {
                continue;
            }

            // Tekli değer kolaylığı: ilk değeri döndür
            $first_raw     = isset($raw_values[0])     ? $raw_values[0]     : '';
            $first_display = isset($display_values[0]) ? $display_values[0] : '';

            $safe_name = str_replace('-', '_', $field_name);

            $result[$safe_name] = array(
                'name'           => $safe_name,
                'label'          => $field->getVar('field_label'),
                'type'           => $field_type,
                'value'          => $first_raw,         // ham, tekli kullanım için
                'value_display'  => $first_display,     // HTML, tekli kullanım için
                'values'         => $raw_values,         // ham dizi (repeatable)
                'values_display' => $display_values,    // HTML dizi (repeatable)
                'is_repeatable'  => $is_repeatable,
            );
        }

        return $result;
    }

    /**
     * Item detail sayfası için Smarty'e atar.
     *
     * Template'de kullanım:
     *   Tekli:      {$field.fiyat.value_display}
     *   Ham değer:  {$field.fiyat.value}
     *   Etiket:     {$field.fiyat.label}
     *   Repeatable: {foreach item=v from=$field.fiyat.values_display}{$v}{/foreach}
     *
     *   Ayrıca kısa alias:
     *   {$f_fiyat}          → ham değer (value)
     *   {$fd_fiyat}         → display HTML (value_display)
     *   {$fl_fiyat}         → etiket (label)
     *
     *   Eski uyumluluk (mevcut döngü):
     *   {$custom_fields}    → hâlâ çalışır
     *
     * @param object $xoopsTpl  Smarty nesnesi
     * @param int    $item_id
     * @param int    $cat_id
     * @return array            buildFields() çıktısı (gerekirse kullanmak için)
     */
    public static function assignItemFields(&$xoopsTpl, $item_id, $cat_id)
    {
        $fields = self::buildFields($item_id, $cat_id);

        // $field dizisi: {$field.fiyat.value_display}
        $xoopsTpl->assign('field', $fields);

        // Kısa alias değişkenler: {$f_fiyat}, {$fd_fiyat}, {$fl_fiyat}
        foreach ($fields as $name => $data) {
            $xoopsTpl->assign('f_'  . $name, $data['value']);
            $xoopsTpl->assign('fd_' . $name, $data['value_display']);
            $xoopsTpl->assign('fl_' . $name, $data['label']);
        }

        // Eski uyumluluk: $custom_fields dizisi (mevcut foreach döngüsü çalışmaya devam eder)
        $custom_fields = array();
        foreach ($fields as $data) {
            $custom_fields[] = array(
                'name'          => $data['name'],
                'label'         => $data['label'],
                'type'          => $data['type'],
                'values'        => $data['values_display'],
                'raw_values'    => $data['values'],
                'is_repeatable' => $data['is_repeatable'],
            );
        }
        $xoopsTpl->assign('custom_fields', $custom_fields);

        return $fields;
    }

    /**
     * Liste sayfaları (index.php, bloklar) için item dizisine 'fields' ekler.
     *
     * $items_raw : XcreateItem nesneleri dizisi (getItemsByCategory() çıktısı)
     * $item_list : zaten oluşturulmuş id/title/url/... dizisi (referans olarak güncellenir)
     *
     * Template'de kullanım (xcreate_index.tpl veya özel liste TPL):
     *   {foreach item=item from=$items}
     *     {$item.f_fiyat}                           ← ham değer
     *     {$item.fd_fiyat}                          ← display HTML
     *     {$item.fl_fiyat}                          ← etiket
     *     {$item.fields.fiyat.value_display}        ← uzun form
     *     {foreach item=v from=$item.fields.fiyat.values_display}{$v}{/foreach}
     *   {/foreach}
     *
     * @param array $item_list  Mevcut liste dizisi (referans)
     * @param array $items_raw  XcreateItem nesneleri
     * @return array            Güncellenmiş $item_list
     */
    public static function appendFieldsToList(array &$item_list, array $items_raw)
    {
        foreach ($items_raw as $idx => $item) {
            if (!isset($item_list[$idx])) {
                continue;
            }

            $item_id = $item->getVar('item_id');
            $cat_id  = $item->getVar('item_cat_id');

            $fields = self::buildFields($item_id, $cat_id);

            // Tam fields dizisi
            $item_list[$idx]['fields'] = $fields;

            // Kısa aliaslar doğrudan item anahtarı olarak
            foreach ($fields as $name => $data) {
                $item_list[$idx]['f_'  . $name] = $data['value'];
                $item_list[$idx]['fd_' . $name] = $data['value_display'];
                $item_list[$idx]['fl_' . $name] = $data['label'];
            }
        }

        return $item_list;
    }
}
