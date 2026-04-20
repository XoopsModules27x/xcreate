<div class="xc-widget xc-widget-popular">
    <{if $block.items}>
        <ol class="xc-rank-list">
            <{foreach item=item from=$block.items}>
                <li class="xc-rank-item">
                    <span class="xc-rank-no"><{$item.rank}></span>
                    <div class="xc-item-body">
                        <a href="<{$item.url}>" class="xc-item-title"><{$item.title}></a>
                        <div class="xc-meta">
                            <a href="<{$item.category_url}>" class="xc-cat"><{$item.category}></a>
                            <span class="xc-hits">👁 <{$item.hits}></span>
                        </div>
                    </div>
                </li>
            <{/foreach}>
        </ol>
    <{else}>
        <p class="xc-empty">İçerik bulunamadı.</p>
    <{/if}>
</div>
<style>
.xc-widget-popular{padding:8px 0}.xc-rank-list{list-style:none;margin:0;padding:0;counter-reset:rank}.xc-rank-item{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid #eee}.xc-rank-item:last-child{border-bottom:none}.xc-rank-no{background:#0055aa;color:#fff;font-weight:700;font-size:.85em;min-width:24px;height:24px;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0}.xc-rank-item:nth-child(1) .xc-rank-no{background:#f5a623}.xc-rank-item:nth-child(2) .xc-rank-no{background:#9b9b9b}.xc-rank-item:nth-child(3) .xc-rank-no{background:#c87533}.xc-item-body{flex:1}.xc-item-title{font-weight:600;font-size:.88em;color:#222;text-decoration:none;display:block;margin-bottom:3px}.xc-item-title:hover{color:#0055aa}.xc-meta{font-size:.76em;color:#888}.xc-hits{color:#e07800;margin-left:8px}
</style>
