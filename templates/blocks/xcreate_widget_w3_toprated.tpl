<div class="xc-widget xc-widget-toprated">
    <{if $block.items}>
        <ul class="xc-list">
            <{foreach item=item from=$block.items}>
                <li class="xc-list-item">
                    <div class="xc-item-body">
                        <a href="<{$item.url}>" class="xc-item-title"><{$item.title}></a>
                        <div class="xc-stars">
                            <{section name=f loop=$item.stars_full}><span class="xc-star full">★</span><{/section}>
                            <{if $item.stars_half}><span class="xc-star half">★</span><{/if}>
                            <{section name=e loop=$item.stars_empty}><span class="xc-star empty">☆</span><{/section}>
                            <span class="xc-score"><{$item.avg_score}>/5</span>
                            <span class="xc-votes">(<{$item.vote_count}> oy)</span>
                        </div>
                        <div class="xc-meta">
                            <a href="<{$item.category_url}>" class="xc-cat"><{$item.category}></a>
                        </div>
                    </div>
                </li>
            <{/foreach}>
        </ul>
    <{else}>
        <p class="xc-empty">Henüz puanlanmış içerik yok.</p>
    <{/if}>
</div>
<style>
.xc-widget-toprated{padding:8px 0}.xc-widget-toprated .xc-list{list-style:none;margin:0;padding:0}.xc-widget-toprated .xc-list-item{padding:9px 0;border-bottom:1px solid #eee}.xc-widget-toprated .xc-list-item:last-child{border-bottom:none}.xc-widget-toprated .xc-item-title{font-weight:600;font-size:.88em;color:#222;text-decoration:none;display:block;margin-bottom:4px}.xc-widget-toprated .xc-item-title:hover{color:#0055aa}.xc-stars{margin-bottom:3px;line-height:1}.xc-star{font-size:1em}.xc-star.full,.xc-star.half{color:#f5a623}.xc-star.empty{color:#ddd}.xc-score{font-weight:700;font-size:.82em;color:#e07800;margin-left:4px}.xc-votes{font-size:.75em;color:#aaa;margin-left:2px}.xc-meta{font-size:.76em;color:#888}.xc-cat{color:#0055aa;text-decoration:none}
</style>
