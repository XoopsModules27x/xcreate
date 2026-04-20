<{* ================================================================
    XCREATE KATEGORİ SEO META BLOĞU — Eren Yumak / Aymak
    ================================================================ *}>
<{if $seo}>
<{if $seo.canonical}><link rel="canonical" href="<{$seo.canonical}>" /><{/if}>
<{if $seo.noindex}><meta name="robots" content="noindex, nofollow" /><{else}><meta name="robots" content="index, follow" /><{/if}>
<meta property="og:type"        content="website" />
<meta property="og:title"       content="<{$seo.title}>" />
<meta property="og:description" content="<{$seo.description}>" />
<{if $seo.canonical}><meta property="og:url" content="<{$seo.canonical}>" /><{/if}>
<meta property="og:site_name"   content="<{$seo.site_name}>" />
<{if $seo.og_image}><meta property="og:image" content="<{$seo.og_image}>" /><{/if}>
<meta name="twitter:card"        content="summary" />
<meta name="twitter:title"       content="<{$seo.title}>" />
<meta name="twitter:description" content="<{$seo.description}>" />
<{if $seo.og_image}><meta name="twitter:image" content="<{$seo.og_image}>" /><{/if}>
<{/if}>
<{* ================================================================ *}>

<div class="xcreate-module">
    <h1><{$smarty.const._MI_XCREATE_NAME}></h1>
    
    <{if $allow_submit && $xoops_isuser}>
        <div class="submit-button">
            <a href="<{$module_url}>/submit.php" class="btn btn-primary">
                <i class="fa fa-plus"></i> <{$smarty.const._MD_XCREATE_SUBMIT}>
            </a>
        </div>
    <{/if}>
    
    <{if $current_category}>
        <div class="current-category">
            <h2><{$current_category.name}></h2>
            <{if $current_category.description}>
                <p><{$current_category.description}></p>
            <{/if}>
        </div>
    <{/if}>
    
    <{if $categories}>
        <div class="categories-list">
            <h3><{$smarty.const._MD_XCREATE_CATEGORIES}></h3>
            <div class="row">
                <{foreach item=cat from=$categories}>
                    <div class="col-md-4 col-sm-6">
                        <div class="category-item">
                            <{if $cat.image}>
                                <img src="<{$xoops_upload_url}>/xcreate/categories/<{$cat.image}>" alt="<{$cat.name}>" class="img-responsive">
                            <{/if}>
                            <h4><a href="<{$cat.url}>"><{$cat.name}></a></h4>
                            <{if $cat.description}>
                                <p><{$cat.description|truncate:100}></p>
                            <{/if}>
                        </div>
                    </div>
                <{/foreach}>
            </div>
        </div>
    <{/if}>
    
    <{if $items}>
        <div class="items-list">
            <h3><{$smarty.const._MD_XCREATE_ITEMS}></h3>
            <{foreach item=item from=$items}>
                <div class="item-summary">
                    <h4><a href="<{$item.url}>"><{$item.title}></a></h4>
                    <div class="item-meta">
                        <span class="author"><{$smarty.const._MD_XCREATE_AUTHOR_LABEL}> <{$item.author}></span> |
                        <span class="date"><{$item.created}></span> |
                        <span class="hits"><{$smarty.const._MD_XCREATE_VIEWS_LABEL}> <{$item.hits}></span>
                    </div>
                    <p><{$item.description}>...</p>
                    <a href="<{$item.url}>" class="btn btn-sm btn-default"><{$smarty.const._MD_XCREATE_READ_MORE}></a>
                </div>
            <{/foreach}>
            
            <div class="pagination">
                <{$pagenav}>
            </div>
        </div>
    <{else}>
        <div class="alert alert-info">
            <{$smarty.const._MD_XCREATE_NO_CONTENT}>
        </div>
    <{/if}>
</div>

<style>
.xcreate-module { padding: 20px; }
.submit-button { margin-bottom: 20px; }
.category-item { padding: 15px; border: 1px solid #ddd; margin-bottom: 20px; border-radius: 5px; }
.category-item img { margin-bottom: 10px; max-height: 150px; }
.category-item h4 { margin-top: 0; }
.item-summary { padding: 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.item-summary:last-child { border-bottom: none; }
.item-meta { color: #666; font-size: 0.9em; margin: 10px 0; }
.pagination { margin-top: 20px; text-align: center; }
</style>
