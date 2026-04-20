<div class="xc-widget xc-widget-activity">
    <{if $block.items}>
        <ul class="xc-activity-list">
            <{foreach item=item from=$block.items}>
                <li class="xc-activity-item">
                    <div class="xc-activity-dot
                        <{if $item.is_new}> dot-new<{elseif $item.is_updated}> dot-updated<{else}> dot-normal<{/if}>">
                    </div>
                    <div class="xc-item-body">
                        <a href="<{$item.url}>" class="xc-item-title"><{$item.title}></a>
                        <div class="xc-meta">
                            <a href="<{$item.category_url}>" class="xc-cat"><{$item.category}></a>
                            <span class="xc-time-label">
                                <{if $item.is_new}><span class="xc-badge new">YENİ</span><{elseif $item.is_updated}><span class="xc-badge updated">GÜNCELLENDİ</span><{/if}>
                                <{$item.time_label}>
                            </span>
                        </div>
                    </div>
                </li>
            <{/foreach}>
        </ul>
    <{else}>
        <p class="xc-empty">Aktivite bulunamadı.</p>
    <{/if}>
</div>
<style>
.xc-widget-activity{padding:8px 0}.xc-activity-list{list-style:none;margin:0;padding:0}.xc-activity-item{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid #eee}.xc-activity-item:last-child{border-bottom:none}.xc-activity-dot{width:10px;height:10px;border-radius:50%;margin-top:5px;flex-shrink:0}.xc-activity-dot.dot-new{background:#22bb55}.xc-activity-dot.dot-updated{background:#f5a623}.xc-activity-dot.dot-normal{background:#ccc}.xc-item-body{flex:1;min-width:0}.xc-item-title{font-weight:600;font-size:.88em;color:#222;text-decoration:none;display:block;margin-bottom:3px}.xc-item-title:hover{color:#0055aa}.xc-meta{font-size:.76em;color:#888;display:flex;flex-wrap:wrap;gap:5px;align-items:center}.xc-cat{color:#0055aa;text-decoration:none}.xc-time-label{color:#aaa;display:flex;align-items:center;gap:4px}.xc-badge{display:inline-block;font-size:.7em;font-weight:700;padding:1px 5px;border-radius:3px;text-transform:uppercase;letter-spacing:.3px}.xc-badge.new{background:#d4edda;color:#155724}.xc-badge.updated{background:#fff3cd;color:#856404}.xc-empty{color:#999;font-style:italic}
</style>
