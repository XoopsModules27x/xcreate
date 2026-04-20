<{* Custom Fields - Top Games Template Example *}>

<div class="topgames-list">
    <{foreach item=item from=$xcreate_items}>
        <div class="game-item">
            <h3><a href="<{$item.url}>"><{$item.title}></a></h3>
            
            <{* Özel alanları kullanma örneği *}>
            <{if $item.resim}>
                <img src="<{$item.resim}>" alt="<{$item.title}>">
            <{/if}>
            
            <{if $item.puan}>
                <div class="rating">Puan: <{$item.puan}></div>
            <{/if}>
            
            <{if $item.link_adi}>
                <div class="link">Link: <{$item.link_adi}></div>
            <{/if}>
            
            <div class="description">
                <{$item.description|truncate:200}>
            </div>
            
            <div class="meta">
                <span class="category"><{$item.category_name}></span>
                <span class="views"><{$item.hits}> görüntülenme</span>
            </div>
        </div>
    <{/foreach}>
</div>

<style>
.topgames-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
.game-item { border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
.game-item img { max-width: 100%; height: auto; margin-bottom: 10px; }
.game-item h3 { margin: 0 0 10px 0; }
.rating { color: #f39c12; font-weight: bold; margin: 5px 0; }
.meta { font-size: 0.9em; color: #666; margin-top: 10px; }
</style>
