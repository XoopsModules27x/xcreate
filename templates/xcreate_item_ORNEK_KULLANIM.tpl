<{* ═══════════════════════════════════════════════════════════════════════
    xcreate_item_ORNEK_KULLANIM.tpl
    İlave alan kullanım örnekleri — item (detay) sayfası için
    Eren Yumak tarafından kodlanmıştır — Aymak
    ═══════════════════════════════════════════════════════════════════════ *}>

<div class="xcreate-item-detail">

    <h1><{$item.title}></h1>
    <p><{$smarty.const._MD_XCREATE_AUTHOR_LABEL}> <{$item.author}> | <{$smarty.const._MD_XCREATE_DATE_LABEL}> <{$item.created}></p>
    <div><{$item.description}></div>

    <hr>

    <{*
    ────────────────────────────────────────────────────────
    YÖNTEM 1 — Kısa alias (en kolay, tavsiye edilen)
    Alan adı admin panelinde ne ise o kullanılır.
    Örnek alan adları: fiyat, platform, website, logo

      {$f_ALANADI}   → ham değer (metin/sayı/URL)
      {$fd_ALANADI}  → display HTML (<a href>, <img>, galeri HTML)
      {$fl_ALANADI}  → etiket metni ("Fiyat", "Platform" vs.)
    ────────────────────────────────────────────────────────
    *}>

    <p><strong><{$fl_fiyat}>:</strong> <{$f_fiyat}> TL</p>
    <p><strong><{$fl_platform}>:</strong> <{$f_platform}></p>
    <p><strong>Web sitesi:</strong> <{$fd_website}></p>   <{* <a href="..."> otomatik gelir *}>
    <div><{$fd_logo}></div>                               <{* <img src="..."> otomatik gelir *}>
    <div><{$fd_galeri}></div>                             <{* galeri grid HTML'i otomatik gelir *}>

    <hr>

    <{*
    ────────────────────────────────────────────────────────
    YÖNTEM 2 — $field dizisi (daha fazla kontrol)
      {$field.ALANADI.value}           → ham değer
      {$field.ALANADI.value_display}   → display HTML
      {$field.ALANADI.label}           → etiket
      {$field.ALANADI.values}          → ham değerler dizisi (repeatable)
      {$field.ALANADI.values_display}  → HTML dizisi (repeatable)
      {$field.ALANADI.is_repeatable}   → 0 veya 1
      {$field.ALANADI.type}            → alan tipi (text, image, url ...)
    ────────────────────────────────────────────────────────
    *}>

    <p><strong><{$field.fiyat.label}>:</strong> <{$field.fiyat.value}> TL</p>

    <{* Koşullu gösterim — sadece doluysa göster *}>
    <{if $field.indirim.value}>
        <p><strong>İndirimli Fiyat:</strong> <{$field.indirim.value}> TL</p>
    <{/if}>

    <{* Repeatable alan *}>
    <{if $field.ozellikler.is_repeatable}>
        <ul>
        <{foreach item=v from=$field.ozellikler.values_display}>
            <li><{$v}></li>
        <{/foreach}>
        </ul>
    <{else}>
        <{$field.ozellikler.value_display}>
    <{/if}>

    <hr>

    <{*
    ────────────────────────────────────────────────────────
    YÖNTEM 3 — Eski $custom_fields döngüsü (hâlâ çalışır)
    Mevcut template'leri bozmadan eski kod aynen çalışmaya devam eder.
    ────────────────────────────────────────────────────────
    *}>

    <{if $custom_fields}>
        <div class="custom-fields">
            <{foreach item=cfield from=$custom_fields}>
                <div class="field-item">
                    <strong><{$cfield.label}>:</strong>
                    <{foreach item=val from=$cfield.values}><{$val}><{/foreach}>
                </div>
            <{/foreach}>
        </div>
    <{/if}>

</div>
