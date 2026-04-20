<div class="xcreate-block">
    <{if $block.items}>
        <ul class="recent-items">
            <{foreach item=item from=$block.items}>
                <li class="item">
                    <h5><a href="<{$item.url}>"><{$item.title}></a></h5>
                    <div class="item-info">
                        <span class="category"><a href="<{$item.category_url}>"><{$item.category}></a></span>
                        <span class="date"><{$item.created}></span>
                    </div>
                    <{if $item.description}>
                        <p class="description"><{$item.description}>...</p>
                    <{/if}>
                </li>
            <{/foreach}>
        </ul>
    <{else}>
        <p>İçerik bulunamadı.</p>
    <{/if}>
</div>

<style>
.xcreate-block { padding: 10px; }
.recent-items { list-style: none; margin: 0; padding: 0; }
.recent-items .item { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
.recent-items .item:last-child { border-bottom: none; }
.recent-items h5 { margin: 0 0 5px 0; font-size: 1em; }
.recent-items .item-info { font-size: 0.85em; color: #666; margin-bottom: 5px; }
.recent-items .item-info .category { margin-right: 10px; }
.recent-items .description { font-size: 0.9em; color: #555; margin: 5px 0 0 0; }
</style>
