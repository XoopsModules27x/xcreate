<div class="xc-widget xc-widget-recent">
    <{if $block.items}>
        <ul class="xc-list">
            <{foreach item=item from=$block.items}>
                <li class="xc-list-item">
                    <{if $block.show_thumb && $item.resim}>
                        <a href="<{$item.url}>" class="xc-thumb">
                            <img src="<{$item.resim}>" alt="<{$item.title}>" loading="lazy">
                        </a>
                    <{/if}>
                    <div class="xc-item-body">
                        <a href="<{$item.url}>" class="xc-item-title"><{$item.title}></a>
                        <div class="xc-meta">
                            <a href="<{$item.category_url}>" class="xc-cat"><{$item.category}></a>
                            <span class="xc-date"><{$item.created}></span>
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
.xc-widget{padding:8px 0}.xc-list{list-style:none;margin:0;padding:0}.xc-list-item{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #eee}.xc-list-item:last-child{border-bottom:none}.xc-thumb img{width:60px;height:60px;object-fit:cover;border-radius:4px;flex-shrink:0}.xc-item-body{flex:1;min-width:0}.xc-item-title{font-weight:600;font-size:.9em;color:#222;text-decoration:none;display:block;margin-bottom:3px}.xc-item-title:hover{color:#0055aa}.xc-meta{font-size:.78em;color:#888;margin-bottom:4px}.xc-cat{color:#0055aa;text-decoration:none;margin-right:6px}.xc-date{color:#aaa}.xc-desc{font-size:.82em;color:#666;margin:0;line-height:1.4}.xc-empty{color:#999;font-style:italic}
</style>
