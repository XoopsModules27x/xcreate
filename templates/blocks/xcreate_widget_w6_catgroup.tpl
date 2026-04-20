<div class="xc-widget xc-widget-catgroup">
    <{if $block.categories}>
        <{foreach item=cat from=$block.categories}>
            <div class="xc-catgroup-section">
                <div class="xc-catgroup-header">
                    <a href="<{$cat.url}>" class="xc-catgroup-name"><{$cat.name}></a>
                    <a href="<{$cat.url}>" class="xc-catgroup-more">Tümü →</a>
                </div>
                <{if $cat.items}>
                    <ul class="xc-catgroup-list">
                        <{foreach item=item from=$cat.items}>
                            <li>
                                <a href="<{$item.url}>"><{$item.title}></a>
                                <span class="xc-catgroup-hits">👁 <{$item.hits}></span>
                            </li>
                        <{/foreach}>
                    </ul>
                <{else}>
                    <p class="xc-empty">Bu kategoride içerik yok.</p>
                <{/if}>
            </div>
        <{/foreach}>
    <{else}>
        <p class="xc-empty">Kategori bulunamadı.</p>
    <{/if}>
</div>
<style>
.xc-widget-catgroup{padding:8px 0}.xc-catgroup-section{margin-bottom:14px;border:1px solid #eee;border-radius:5px;overflow:hidden}.xc-catgroup-header{background:#f5f5f5;display:flex;justify-content:space-between;align-items:center;padding:7px 10px;border-bottom:1px solid #eee}.xc-catgroup-name{font-weight:700;font-size:.9em;color:#0055aa;text-decoration:none}.xc-catgroup-more{font-size:.75em;color:#888;text-decoration:none}.xc-catgroup-more:hover{color:#0055aa}.xc-catgroup-list{list-style:none;margin:0;padding:0}.xc-catgroup-list li{display:flex;justify-content:space-between;align-items:center;padding:6px 10px;border-bottom:1px solid #f5f5f5;font-size:.85em}.xc-catgroup-list li:last-child{border-bottom:none}.xc-catgroup-list a{color:#333;text-decoration:none;flex:1;padding-right:8px}.xc-catgroup-list a:hover{color:#0055aa}.xc-catgroup-hits{color:#bbb;font-size:.78em;white-space:nowrap}
</style>
