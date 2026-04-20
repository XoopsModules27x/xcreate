<div class="xc-widget xc-widget-tagcloud">
    <{if $block.tags}>
        <div class="xc-tagcloud">
            <{foreach item=tag from=$block.tags}>
                <a href="<{$tag.url}>"
                   class="xc-tag"
                   style="font-size:<{$tag.size}>%;font-weight:<{$tag.weight}>;"
                   title="<{$tag.count}> içerik">
                    <{$tag.name}>
                </a>
            <{/foreach}>
        </div>
    <{else}>
        <p class="xc-empty">Etiket bulunamadı.</p>
    <{/if}>
</div>
<style>
.xc-widget-tagcloud{padding:8px 0}.xc-tagcloud{display:flex;flex-wrap:wrap;gap:6px;line-height:1.6}.xc-tag{display:inline-block;background:#f0f4ff;color:#0055aa;text-decoration:none;padding:3px 9px;border-radius:20px;border:1px solid #d0dcff;transition:background .2s,color .2s}.xc-tag:hover{background:#0055aa;color:#fff;border-color:#0055aa}.xc-empty{color:#999;font-style:italic}
</style>
