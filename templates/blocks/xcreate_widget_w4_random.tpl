<div class="xc-widget xc-widget-random">
    <{if $block.items}>
        <ul class="xc-list">
            <{foreach item=item from=$block.items}>
                <li class="xc-list-item">
                    <span class="xc-random-icon">🎲</span>
                    <div class="xc-item-body">
                        <a href="<{$item.url}>" class="xc-item-title"><{$item.title}></a>
                        <div class="xc-meta">
                            <a href="<{$item.category_url}>" class="xc-cat"><{$item.category}></a>
                        </div>
                        <{if $item.description}>
                            <p class="xc-desc"><{$item.description}>...</p>
                        <{/if}>
                    </div>
                </li>
            <{/foreach}>
        </ul>
    <{else}>
        <p class="xc-empty">İçerik bulunamadı.</p>
    <{/if}>
</div>
<style>
.xc-widget-random{padding:8px 0}.xc-widget-random .xc-list{list-style:none;margin:0;padding:0}.xc-widget-random .xc-list-item{display:flex;gap:8px;padding:9px 0;border-bottom:1px solid #eee}.xc-widget-random .xc-list-item:last-child{border-bottom:none}.xc-random-icon{font-size:1.2em;flex-shrink:0;margin-top:2px}.xc-item-body{flex:1}.xc-widget-random .xc-item-title{font-weight:600;font-size:.88em;color:#222;text-decoration:none;display:block;margin-bottom:3px}.xc-widget-random .xc-item-title:hover{color:#0055aa}.xc-widget-random .xc-meta{font-size:.76em;color:#888}.xc-cat{color:#0055aa;text-decoration:none}.xc-widget-random .xc-desc{font-size:.8em;color:#666;margin:3px 0 0 0}
</style>
