<?php
/**
 * Xcreate — Alan Grubu Yönetimi
 * Admin panelinden sekme / bölüm tanımlar, alanlara grup atar.
 *
 * Eren Yumak tarafından kodlanmıştır — Aymak
 */

include_once '../../../include/cp_header.php';
include_once '../../../class/xoopsformloader.php';

if (!defined('XOOPS_ROOT_PATH')) { exit(); }

// Dil dosyaları
$language = $GLOBALS['xoopsConfig']['language'];
foreach (['admin', 'main'] as $lf) {
    $path = XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/{$lf}.php";
    include_once file_exists($path) ? $path : XOOPS_ROOT_PATH . "/modules/xcreate/language/english/{$lf}.php";
}

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/category.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/field.php';
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/group.php';

$categoryHandler = new XcreateCategoryHandler($xoopsDB);
$fieldHandler    = new XcreateFieldHandler($xoopsDB);
$groupHandler    = new XcreateGroupHandler($xoopsDB);  // ensureTable() burada çalışır

$op       = isset($_REQUEST['op'])     ? $_REQUEST['op']     : 'list';
$group_id = isset($_REQUEST['id'])     ? intval($_REQUEST['id']) : 0;
$cat_id   = isset($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;

// ── SAVE ───────────────────────────────────────────────────────────────────
if ($op === 'save') {
    if (!$GLOBALS['xoopsSecurity']->check()) {
        redirect_header('groups.php', 3, implode('<br>', $GLOBALS['xoopsSecurity']->getErrors()));
    }

    $group = ($group_id > 0) ? $groupHandler->get($group_id) : $groupHandler->create();
    if (!$group) { $group = $groupHandler->create(); }

    if (!$group_id) { $group->setVar('group_created', time()); }

    $group->setVar('group_cat_id',  intval($_POST['group_cat_id']));
    $group->setVar('group_name',    preg_replace('/[^a-z0-9_-]/', '', strtolower($_POST['group_name'])));
    $group->setVar('group_label',   htmlspecialchars(trim($_POST['group_label']), ENT_QUOTES));
    $group->setVar('group_icon',    htmlspecialchars(trim($_POST['group_icon']),  ENT_QUOTES));
    $group->setVar('group_color',   htmlspecialchars(trim($_POST['group_color']), ENT_QUOTES));
    $group->setVar('group_weight',  intval($_POST['group_weight']));
    $group->setVar('group_status',  intval($_POST['group_status']));

    if ($groupHandler->insert($group)) {
        redirect_header('groups.php?cat_id=' . intval($_POST['group_cat_id']), 2, _AM_XCREATE_GROUP_SAVE_SUCCESS);
    } else {
        redirect_header('groups.php', 3, sprintf(_AM_XCREATE_GROUP_SAVE_ERROR, $xoopsDB->error()));
    }
}

// ── DELETE ─────────────────────────────────────────────────────────────────
if ($op === 'delete' && $group_id > 0) {
    if (!$GLOBALS['xoopsSecurity']->check()) {
        redirect_header('groups.php', 3, implode('<br>', $GLOBALS['xoopsSecurity']->getErrors()));
    }
    $group = $groupHandler->get($group_id);
    $back_cat = $group ? (int)$group->getVar('group_cat_id') : 0;
    $groupHandler->deleteGroup($group_id);
    redirect_header('groups.php?cat_id=' . $back_cat, 2, _AM_XCREATE_GROUP_DELETE_SUCCESS);
}
// ── FIELD ASSIGN SAVE ──────────────────────────────────────────────────────
// Alanların grup atamalarını kaydet (toplu)
if ($op === 'assign_save') {
    if (!$GLOBALS['xoopsSecurity']->check()) {
        redirect_header('groups.php', 3, implode('<br>', $GLOBALS['xoopsSecurity']->getErrors()));
    }
    if (isset($_POST['field_groups']) && is_array($_POST['field_groups'])) {
        foreach ($_POST['field_groups'] as $fid => $gid) {
            $xoopsDB->queryF(
                "UPDATE " . $xoopsDB->prefix('xcreate_fields') .
                " SET field_group_id = " . intval($gid) .
                " WHERE field_id = " . intval($fid)
            );
        }
    }
    redirect_header('groups.php?op=assign&cat_id=' . intval($_POST['assign_cat_id']), 2, _AM_XCREATE_GROUP_ASSIGN_SUCCESS);
}

// ── ADD / EDIT FORM ────────────────────────────────────────────────────────
if ($op === 'add' || $op === 'edit') {
    xoops_cp_header();
    $adminObject = \Xmf\Module\Admin::getInstance();
    $adminObject->displayNavigation('groups.php');

    $group = ($group_id > 0) ? $groupHandler->get($group_id) : $groupHandler->create();
    if (!$group) { $group = $groupHandler->create(); }
    $form_title = ($group_id > 0) ? _AM_XCREATE_GROUP_EDIT_TITLE : _AM_XCREATE_GROUP_ADD_TITLE;
    if ($group_id > 0) { $cat_id = (int)$group->getVar('group_cat_id'); }

    echo '<style>
    .xg-form-hint { font-size:12px; color:#6b7280; margin-top:4px; }
    .xg-color-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
    .xg-color-btn { width:28px; height:28px; border-radius:50%; border:2px solid transparent;
        cursor:pointer; transition:all .15s; }
    .xg-color-btn.active, .xg-color-btn:hover { border-color:#1f2937; transform:scale(1.15); }
    </style>';

    $existing_color = $group->getVar('group_color');

    echo '<script>
    function xcgSlug() {
        var lbl = document.getElementById("group_label");
        var nm  = document.getElementById("group_name");
        if (!nm.value || nm.dataset.touched !== "1") {
            nm.value = lbl.value.toLowerCase()
                .replace(/ğ/g,"g").replace(/ü/g,"u").replace(/ş/g,"s")
                .replace(/ı/g,"i").replace(/ö/g,"o").replace(/ç/g,"c")
                .replace(/[^a-z0-9]+/g,"_").replace(/^_+|_+$/g,"");
        }
    }
    function xcgPickColor(hex) {
        document.getElementById("group_color").value = hex;
        document.querySelectorAll(".xg-color-btn").forEach(function(b){
            b.classList.toggle("active", b.dataset.color === hex);
        });
    }
    var _presetColors = ["#3b82f6","#10b981","#f59e0b","#ef4444","#8b5cf6",
        "#ec4899","#06b6d4","#f97316","#6b7280","#1d4ed8"];
    window.addEventListener("DOMContentLoaded", function(){
        var row = document.getElementById("color-row");
        _presetColors.forEach(function(c){
            var b = document.createElement("button");
            b.type="button"; b.className="xg-color-btn";
            b.style.background=c; b.dataset.color=c;
            b.title=c; b.onclick=function(){ xcgPickColor(c); };
            if (c === "' . addslashes($existing_color) . '") b.classList.add("active");
            row.appendChild(b);
        });
    });
    </script>';

    $form = new XoopsThemeForm($form_title, 'group_form', 'groups.php', 'post');

    // Kategori seçici
    $cat_sel = new XoopsFormSelect('Kategori', 'group_cat_id', $cat_id);
    $cat_sel->addOption(0, '— Seçiniz —');
    foreach ($categoryHandler->getTree() as $c) {
        $pfx = str_repeat('— ', (int)$c->getVar('level'));
        $cat_sel->addOption($c->getVar('cat_id'), $pfx . $c->getVar('cat_name'));
    }
    $form->addElement($cat_sel, true);

    // Etiket
    $form->addElement(new XoopsFormText('Grup Etiketi (Görünen Ad)', 'group_label', 50, 255,
        $group->getVar('group_label', 'e')), true);
    // Not: label üzerindeki JS otomatik slug üretir

    // Dahili ad
    $name_html = '<input type="text" name="group_name" id="group_name" class="form-control"
        value="' . htmlspecialchars($group->getVar('group_name'), ENT_QUOTES) . '"
        maxlength="100" pattern="[a-z0-9_-]+" placeholder="ornek_grup" oninput="this.dataset.touched=1">
        <p class="xg-form-hint">Yalnızca küçük harf, rakam, alt çizgi. Template\'de kullanılır:
        <code>{$groups.GRUP_ADI}</code></p>';
    $form->addElement(new XoopsFormLabel('Dahili Ad', $name_html));

    // İkon
    $icon_html = '<input type="text" name="group_icon" id="group_icon" class="form-control"
        value="' . htmlspecialchars($group->getVar('group_icon'), ENT_QUOTES) . '"
        maxlength="50" placeholder="📋  veya  fa-info-circle">
        <p class="xg-form-hint">Emoji veya Font Awesome sınıfı girin. Sekme başlığında görünür.</p>';
    $form->addElement(new XoopsFormLabel('İkon', $icon_html));

    // Renk
    $color_html = '<input type="text" name="group_color" id="group_color" class="form-control"
        value="' . htmlspecialchars($existing_color, ENT_QUOTES) . '"
        maxlength="20" placeholder="#3b82f6" style="max-width:180px;">
        <div id="color-row" class="xg-color-row"></div>
        <p class="xg-form-hint">Sekme çizgi / başlık rengini belirler. HEX veya CSS renk değeri.</p>';
    $form->addElement(new XoopsFormLabel('Renk', $color_html));

    // Sıralama
    $form->addElement(new XoopsFormText('Sıralama', 'group_weight', 5, 5, $group->getVar('group_weight')));

    // Durum
    $status_r = new XoopsFormRadio('Durum', 'group_status', $group->getVar('group_status'));
    $status_r->addOption(1, 'Aktif');
    $status_r->addOption(0, 'Pasif');
    $form->addElement($status_r);

    // Hidden
    $form->addElement(new XoopsFormHidden('op', 'save'));
    if ($group_id > 0) { $form->addElement(new XoopsFormHidden('id', $group_id)); }

    // JS: label→slug otomatik üretimi (label input'undan sonra çağır)
    $lbl_hack = '<script>
    (function(){
        var lbl = document.getElementById("group_label");
        if (lbl) lbl.addEventListener("input", xcgSlug);
    })();
    </script>';
    $form->addElement(new XoopsFormLabel('', $lbl_hack));

    $tray = new XoopsFormElementTray('', '');
    $tray->addElement(new XoopsFormButton('', 'submit', _AM_XCREATE_SAVE, 'submit'));
    $tray->addElement(new XoopsFormButton('', 'cancel', _AM_XCREATE_CANCEL, 'button', 'onclick="history.go(-1)"'));
    $form->addElement($tray);

    $form->display();
    xoops_cp_footer();
    exit();
}

// ── ASSIGN PAGE — Alanlara grup ata ───────────────────────────────────────
if ($op === 'assign') {
    xoops_cp_header();
    $adminObject = \Xmf\Module\Admin::getInstance();
    $adminObject->displayNavigation('groups.php');

    if ($cat_id <= 0) {
        echo '<p style="color:red;">' . _AM_XCREATE_GROUP_NO_CATEGORY_FIRST . '</p>';
        echo '<p><a href="groups.php">← ' . _AM_XCREATE_GROUP_BACK . '</a></p>';
        xoops_cp_footer();
        exit();
    }

    $category = $categoryHandler->get($cat_id);
    $groups   = $groupHandler->getGroupsByCategory($cat_id, null);
    $fields   = $fieldHandler->getFieldsByCategory($cat_id, null);

    // Mevcut atamaları çek
    $field_group_map = [];
    foreach ($fields as $f) {
        $field_group_map[(int)$f->getVar('field_id')] = (int)$f->getVar('field_group_id');
    }

    echo '<h3>' . _AM_XCREATE_GROUP_ASSIGNMENTS_TITLE . ' — <em>' . htmlspecialchars($category->getVar('cat_name')) . '</em></h3>';
    echo '<p style="color:#6b7280;margin-bottom:20px;">' . _AM_XCREATE_GROUP_ASSIGNMENTS_HELP . '</p>';

    if (empty($groups)) {
        echo '<div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:16px;">
            ⚠️ ' . _AM_XCREATE_GROUP_NONE_DEFINED . '
            <a href="groups.php?op=add&cat_id=' . $cat_id . '">' . _AM_XCREATE_GROUP_ADD_NEW_LINK . ' →</a>
        </div>';
        echo '<p><a href="groups.php?cat_id=' . $cat_id . '">← ' . _AM_XCREATE_IMPORT_BACK . '</a></p>';
        xoops_cp_footer();
        exit();
    }

    if (empty($fields)) {
        echo '<p>' . _AM_XCREATE_GROUP_NO_FIELDS . '</p>';
        xoops_cp_footer();
        exit();
    }

    echo '<form method="post" action="groups.php">';
    echo $GLOBALS['xoopsSecurity']->getTokenHTML();
    echo '<input type="hidden" name="op" value="assign_save">';
    echo '<input type="hidden" name="assign_cat_id" value="' . $cat_id . '">';

    echo '<table class="table-customfields">';
    echo '<thead><tr>
        <th>' . _AM_XCREATE_GROUP_FIELD . '</th><th>' . _AM_XCREATE_FIELD_TYPE . '</th><th>' . _AM_XCREATE_GROUP_CURRENT . '</th><th>' . _AM_XCREATE_GROUP_NEW . '</th>
    </tr></thead><tbody>';

    $field_types_map = XcreateField::getFieldTypes();

    foreach ($fields as $field) {
        $fid      = (int)$field->getVar('field_id');
        $cur_gid  = $field_group_map[$fid] ?? 0;
        $cur_lbl  = '— ' . _AM_XCREATE_UNGROUPED . ' —';
        foreach ($groups as $g) {
            if ((int)$g->getVar('group_id') === $cur_gid) {
                $cur_lbl = $g->getVar('group_label');
                break;
            }
        }
        echo '<tr>';
        echo '<td><strong>' . htmlspecialchars($field->getVar('field_label')) . '</strong>
            <br><small style="color:#9ca3af">' . htmlspecialchars($field->getVar('field_name')) . '</small></td>';
        $ft = $field->getVar('field_type');
        echo '<td><span class="badge-customfields badge-secondary">'
            . ($field_types_map[$ft] ?? $ft) . '</span></td>';
        echo '<td>' . htmlspecialchars($cur_lbl) . '</td>';
        echo '<td><select name="field_groups[' . $fid . ']" class="form-control-customfields">';
        echo '<option value="0"' . ($cur_gid === 0 ? ' selected' : '') . '>— ' . _AM_XCREATE_UNGROUPED . ' —</option>';
        foreach ($groups as $g) {
            $gid = (int)$g->getVar('group_id');
            $sel = ($cur_gid === $gid) ? ' selected' : '';
            echo '<option value="' . $gid . '"' . $sel . '>'
                . htmlspecialchars($g->getVar('group_label')) . '</option>';
        }
        echo '</select></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<div style="margin-top:16px;">';
    echo '<button type="submit" class="btn-customfields btn-xcreate-success">💾 ' . _AM_XCREATE_ASSIGNMENTS_SAVE . '</button>&nbsp;';
    echo '<a href="groups.php?cat_id=' . $cat_id . '" class="btn-customfields btn-xcreate-secondary">' . _AM_XCREATE_CANCEL . '</a>';
    echo '</div></form>';

    xoops_cp_footer();
    exit();
}

// ── LIST ───────────────────────────────────────────────────────────────────
xoops_cp_header();
$adminObject = \Xmf\Module\Admin::getInstance();
$adminObject->displayNavigation('groups.php');

// Kategori filtre formu
echo '<div class="xcreate-actions" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:20px;">';
echo '<a href="groups.php?op=add' . ($cat_id > 0 ? '&cat_id=' . $cat_id : '') . '"
    class="btn-customfields btn-xcreate-success">+ Yeni Grup Ekle</a>';

echo '<form method="get" action="groups.php" style="display:flex;gap:8px;align-items:center;">';
echo '<select name="cat_id" class="form-control-customfields" onchange="this.form.submit()" style="min-width:200px;">';
echo '<option value="0"' . ($cat_id == 0 ? ' selected' : '') . '>— ' . _AM_XCREATE_EXPORT_ALL_CATEGORIES . ' —</option>';
foreach ($categoryHandler->getTree() as $c) {
    $pfx = str_repeat('— ', (int)$c->getVar('level'));
    $sel = ($cat_id == $c->getVar('cat_id')) ? ' selected' : '';
    echo '<option value="' . $c->getVar('cat_id') . '"' . $sel . '>' . $pfx . $c->getVar('cat_name') . '</option>';
}
echo '</select>';
if ($cat_id > 0) {
    echo '<a href="groups.php" class="btn-customfields btn-xcreate-secondary">✕</a>';
    echo '<a href="groups.php?op=assign&cat_id=' . $cat_id . '"
        class="btn-customfields" style="background:#8b5cf6;color:#fff;border:none;">
        🔗 ' . _AM_XCREATE_GROUP_ASSIGNMENTS_TITLE . '</a>';
}
echo '</form>';
echo '</div>';

// Grupları listele
$criteria = new CriteriaCompo();
if ($cat_id > 0) {
    $criteria->add(new Criteria('group_cat_id', $cat_id));
}
$criteria->setSort('group_cat_id ASC, group_weight ASC, group_label');
$criteria->setOrder('ASC');
$groups = $groupHandler->getObjects($criteria);

if (!empty($groups)) {
    echo '<table class="table-customfields">';
    echo '<thead><tr>
        <th style="width:50px;">ID</th>
        <th>Etiket / Dahili Ad</th>
        <th style="width:160px;">Kategori</th>
        <th style="width:80px;text-align:center;">İkon</th>
        <th style="width:80px;text-align:center;">Renk</th>
        <th style="width:70px;text-align:center;">Sıra</th>
        <th style="width:80px;text-align:center;">Durum</th>
        <th style="width:200px;text-align:center;">' . _AM_XCREATE_ACTIONS . '</th>
    </tr></thead><tbody>';

    $cat_cache = [];
    foreach ($groups as $group) {
        $gid   = (int)$group->getVar('group_id');
        $gcid  = (int)$group->getVar('group_cat_id');
        if (!isset($cat_cache[$gcid])) {
            $co = $categoryHandler->get($gcid);
            $cat_cache[$gcid] = $co ? $co->getVar('cat_name') : '—';
        }
        $color = htmlspecialchars($group->getVar('group_color'), ENT_QUOTES);
        $icon  = htmlspecialchars($group->getVar('group_icon'),  ENT_QUOTES);

        echo '<tr>';
        echo '<td><span class="badge-customfields badge-info">#' . $gid . '</span></td>';
        echo '<td><strong>' . htmlspecialchars($group->getVar('group_label')) . '</strong>
            <br><small style="color:#9ca3af;font-family:monospace;">' . htmlspecialchars($group->getVar('group_name')) . '</small></td>';
        echo '<td><small>' . htmlspecialchars($cat_cache[$gcid]) . '</small></td>';
        echo '<td style="text-align:center;font-size:18px;">' . ($icon ?: '—') . '</td>';
        echo '<td style="text-align:center;">';
        if ($color) {
            echo '<span style="display:inline-block;width:22px;height:22px;border-radius:50%;
                background:' . $color . ';border:1px solid #e5e7eb;" title="' . $color . '"></span>';
        } else { echo '—'; }
        echo '</td>';
        echo '<td style="text-align:center;">' . (int)$group->getVar('group_weight') . '</td>';
        echo '<td style="text-align:center;">';
        echo $group->getVar('group_status')
            ? '<span class="status-indicator status-active">Aktif</span>'
            : '<span class="status-indicator status-inactive">Pasif</span>';
        echo '</td>';
        echo '<td><div class="action-links" style="justify-content:center;flex-wrap:wrap;">';
        echo '<a href="groups.php?op=assign&cat_id=' . $gcid . '"
            style="color:#8b5cf6;">🔗 Alanlar</a>';
        echo '<a href="groups.php?op=edit&id=' . $gid . '">✏️ ' . _MD_XCREATE_EDIT . '</a>';
        echo '<form method="post" action="groups.php" style="display:inline;"
            onsubmit="return confirm(\'Bu grubu silmek istediğinize emin misiniz?\nGruptaki alanlar grupsuz kalacak.\')">';
        echo $GLOBALS['xoopsSecurity']->getTokenHTML();
        echo '<input type="hidden" name="op" value="delete">';
        echo '<input type="hidden" name="id" value="' . $gid . '">';
        echo '<button type="submit" class="delete-link"
            style="background:none;border:none;cursor:pointer;padding:0;color:#d9534f;">
            🗑️ Sil</button>';
        echo '</form>';
        echo '</div></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<div class="empty-state">
        <div class="empty-state-icon">📂</div>
        <div class="empty-state-text">Henüz alan grubu oluşturulmamış</div>
        <div class="empty-state-description">
            ' . ($cat_id > 0 ? 'Bu kategori için' : '') . ' alan grubu ekleyerek
            formu sekmelere veya bölümlere ayırın.
        </div>
        <a href="groups.php?op=add' . ($cat_id > 0 ? '&cat_id=' . $cat_id : '') . '"
            class="btn-customfields btn-xcreate-success">+ İlk Grubu Oluştur</a>
    </div>';
}

xoops_cp_footer();
