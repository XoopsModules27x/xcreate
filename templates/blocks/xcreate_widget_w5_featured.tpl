<div class="xc-widget xc-widget-featured">
    <{if $block.items}>
        <div class="xc-featured-grid">
            <{foreach item=item from=$block.items}>
                <div class="xc-featured-card">
                    <{assign var="img_field" value=$block.image_field}>
                    <{if $item[$img_field]}>
                        <a href="<{$item.url}>" class="xc-featured-img-wrap">
                            <img src="<{$item[$img_field]}>" alt="<{$item.title}>" loading="lazy">
                        </a>
                    <{else}>
                        <a href="<{$item.url}>" class="xc-featured-img-wrap xc-no-img">
                            <span>📄</span>
                        </a>
                    <{/if}>
                    <div class="xc-featured-body">
                        <a href="<{$item.category_url}>" class="xc-cat-badge"><{$item.category}></a>
                        <a href="<{$item.url}>" class="xc-featured-title"><{$item.title}></a>
                        <{if $item.description}>
                            <p class="xc-featured-desc"><{$item.description}>...</p>
                        <{/if}>
                        <span class="xc-featured-date"><{$item.created}></span>
                    </div>
                </div>
            <{/foreach}>
        </div>
    <{else}>
        <p class="xc-empty">İçerik bulunamadı.</p>
    <{/if}>
</div>
<style>
.xc-widget-featured{padding:8px 0}.xc-featured-grid{display:flex;flex-direction:column;gap:12px}.xc-featured-card{border:1px solid #eee;border-radius:6px;overflow:hidden;display:flex;flex-direction:column}.xc-featured-img-wrap{display:block;height:120px;overflow:hidden;background:#f0f0f0}.xc-featured-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform .3s}.xc-featured-card:hover img{transform:scale(1.04)}.xc-no-img{display:flex;align-items:center;justify-content:center;font-size:2em;color:#ccc}.xc-featured-body{padding:10px}.xc-cat-badge{display:inline-block;background:#0055aa;color:#fff;font-size:.7em;padding:2px 7px;border-radius:3px;text-decoration:none;margin-bottom:5px}.xc-featured-title{font-weight:700;font-size:.9em;color:#222;text-decoration:none;display:block;margin-bottom:5px;line-height:1.3}.xc-featured-title:hover{color:#0055aa}.xc-featured-desc{font-size:.8em;color:#666;margin:0 0 5px 0;line-height:1.4}.xc-featured-date{font-size:.75em;color:#aaa}
</style>
