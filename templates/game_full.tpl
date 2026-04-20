<div class="xcreate-item">
    <{if $breadcrumb}>
        <nav class="breadcrumb">
            <a href="<{$module_url}>/index.php"><{$smarty.const._MD_XCREATE_HOME}></a>
            <{foreach item=crumb from=$breadcrumb}>
                &raquo; <a href="<{$crumb.url}>"><{$crumb.name}></a>
            <{/foreach}>
            &raquo; <{$item.title}>
        </nav>
    <{/if}>
    
    <div class="item-header">
        <h1><{$item.title}></h1>
        <div class="item-meta">
            <span class="author"><{$smarty.const._MD_XCREATE_AUTHOR_LABEL}> <{$item.author}></span> |
            <span class="date"><{$smarty.const._MD_XCREATE_DATE_LABEL}> <{$item.created}></span> |
            <span class="category"><{$smarty.const._MD_XCREATE_CATEGORY_LABEL}> <a href="<{$category.url}>"><{$category.name}></a></span> |
            <span class="hits"><{$smarty.const._MD_XCREATE_VIEWS_LABEL}> <{$item.hits}></span>
        </div>
        
        <{if $item.can_edit}>
            <div class="item-actions">
                <a href="<{$module_url}>/submit.php?id=<{$item.id}>" class="btn btn-sm btn-warning">
                    <i class="fa fa-edit"></i> <{$smarty.const._MD_XCREATE_EDIT}>
                </a>
            </div>
        <{/if}>
    </div>
    
    <div class="item-content">
        <div class="item-description">
            <h3><{$smarty.const._MD_XCREATE_DESCRIPTION_LABEL}></h3>
            <{$item.description}>
        </div>
        
        <{if $custom_fields}>
            <div class="custom-fields">
                <h3><{$smarty.const._MD_XCREATE_DETAILS_LABEL}></h3>
                <{foreach item=field from=$custom_fields}>
                    <div class="field-item <{if $field.is_repeatable}>repeatable-field<{/if}>">
                        <strong class="field-label"><{$field.label}>:</strong>
                        <div class="field-value">
                            <{if $field.is_repeatable && count($field.values) > 1}>
                                <ul class="field-values-list">
                                    <{foreach item=val from=$field.values}>
                                        <li><{$val}></li>
                                    <{/foreach}>
                                </ul>
                            <{else}>
                                <{foreach item=val from=$field.values}>
                                    <{$val}>
                                <{/foreach}>
                            <{/if}>
                        </div>
                    </div>
                <{/foreach}>
            </div>
        <{/if}>
    </div>
    
    <div class="item-footer">
        <{if $item.updated != $item.created}>
            <p class="last-update"><{$smarty.const._MD_XCREATE_LAST_UPDATE}> <{$item.updated}></p>
        <{/if}>
        
        <div class="back-link">
            <a href="<{$category.url}>" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> <{$smarty.const._MD_XCREATE_BACK_TO_CATEGORY}>
            </a>
            <a href="<{$module_url}>/index.php" class="btn btn-default">
                <i class="fa fa-home"></i> <{$smarty.const._MD_XCREATE_BACK_TO_HOME}>
            </a>
        </div>
    </div>
</div>

<style>
.xcreate-item { padding: 20px; background: #fff; }
.breadcrumb { padding: 10px 0; margin-bottom: 20px; }
.item-header { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #eee; }
.item-header h1 { margin: 0 0 10px 0; }
.item-meta { color: #666; font-size: 0.9em; margin: 10px 0; }
.item-actions { margin-top: 15px; }
.item-content { margin-bottom: 30px; }
.item-description { margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px; }
.custom-fields { padding: 20px; background: #f5f5f5; border-radius: 5px; }
.custom-fields h3 { margin-top: 0; }
.field-item { margin-bottom: 20px; padding: 15px; background: #fff; border-left: 4px solid #007bff; }
.field-item.repeatable-field { border-left-color: #28a745; }
.field-label { display: block; margin-bottom: 8px; color: #333; font-size: 1.1em; }
.field-value { color: #555; }
.field-values-list { margin: 10px 0; padding-left: 25px; }
.field-values-list li { margin-bottom: 8px; }
.field-value img { max-width: 100%; height: auto; border-radius: 5px; margin-top: 10px; }
.item-footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
.last-update { font-style: italic; color: #666; font-size: 0.9em; }
.back-link { margin-top: 15px; }
</style>
