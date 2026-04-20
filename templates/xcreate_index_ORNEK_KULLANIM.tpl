<{* ═══════════════════════════════════════════════════════════════════════
    xcreate_index_ORNEK_KULLANIM.tpl
    İlave alan kullanım örnekleri — kategori / liste sayfası için
    Eren Yumak tarafından kodlanmıştır — Aymak
    ═══════════════════════════════════════════════════════════════════════ *}>

<{if $current_category}>
    <h1><{$current_category.name}></h1>
<{/if}>

<div class="xcreate-item-list">
<{foreach item=item from=$items}>

    <div class="item-card">

        <h2><a href="<{$item.url}>"><{$item.title}></a></h2>
        <p><{$item.description}></p>

        <{*
        ────────────────────────────────────────────────────────
        YÖNTEM 1 — Kısa alias: {$item.f_ALANADI}
          f_ALANADI   → ham değer
          fd_ALANADI  → display HTML
          fl_ALANADI  → etiket
        ────────────────────────────────────────────────────────
        *}>

        <p><strong><{$item.fl_fiyat}>:</strong> <{$item.f_fiyat}> TL</p>
        <p><strong>Platform:</strong> <{$item.f_platform}></p>
        <div><{$item.fd_logo}></div>       <{* <img> HTML otomatik *}>
        <div><{$item.fd_website}></div>    <{* <a href> HTML otomatik *}>

        <{*
        ────────────────────────────────────────────────────────
        YÖNTEM 2 — $item.fields.ALANADI (tam kontrol)
        ────────────────────────────────────────────────────────
        *}>

        <{if $item.fields.fiyat.value}>
            <p><strong><{$item.fields.fiyat.label}>:</strong>
               <{$item.fields.fiyat.value}> TL</p>
        <{/if}>

        <{* Repeatable alan listesi *}>
        <{if $item.fields.ozellikler.values}>
            <ul>
            <{foreach item=v from=$item.fields.ozellikler.values_display}>
                <li><{$v}></li>
            <{/foreach}>
            </ul>
        <{/if}>

        <a href="<{$item.url}>"><{$smarty.const._MD_XCREATE_READ_MORE}></a>

    </div>

<{/foreach}>
</div>

<div class="pagination"><{$pagenav}></div>
