<div class="xcreate-item-detail">
    <!-- Breadcrumb -->
    {if $breadcrumb}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{$xoops_url}/modules/xcreate/">{$smarty.const._MD_XCREATE_HOME}</a></li>
            {foreach item=bc from=$breadcrumb}
            <li class="breadcrumb-item"><a href="{$bc.url}">{$bc.name}</a></li>
            {/foreach}
            <li class="breadcrumb-item active" aria-current="page">{$item.title}</li>
        </ol>
    </nav>
    {/if}

    <!-- Item Header -->
    <div class="item-header mb-4">
        <h1 class="item-title">{$item.title}</h1>
        <div class="item-meta text-muted small">
            <span class="me-3"><i class="fa fa-user"></i> {$item.author}</span>
            <span class="me-3"><i class="fa fa-calendar"></i> {$item.created}</span>
            <span class="me-3"><i class="fa fa-eye"></i> {$item.hits} görüntüleme</span>
            {if $item.can_edit}
            <a href="{$xoops_url}/modules/xcreate/submit.php?id={$item.id}" class="btn btn-sm btn-primary">
                <i class="fa fa-edit"></i> {$smarty.const._MD_XCREATE_EDIT}
            </a>
            {/if}
        </div>
    </div>

    <!-- Item Description -->
    {if $item.description}
    <div class="item-description mb-4">
        {$item.description}
    </div>
    {/if}

    <!-- Custom Fields -->
    {if $custom_fields}
    <div class="custom-fields-section">
        <h3 class="mb-3">{$smarty.const._MD_XCREATE_DETAILS_LABEL}</h3>
        <div class="row">
            {foreach item=field from=$custom_fields}
            <div class="col-md-6 mb-3">
                <div class="field-group">
                    <label class="field-label fw-bold">{$field.label}:</label>
                    <div class="field-values">
                        {if $field.is_repeatable && count($field.values) > 1}
                            <ul class="list-unstyled">
                                {foreach item=value from=$field.values}
                                <li>{$value}</li>
                                {/foreach}
                            </ul>
                        {else}
                            {foreach item=value from=$field.values}
                            <div class="field-value">{$value}</div>
                            {/foreach}
                        {/if}
                    </div>
                </div>
            </div>
            {/foreach}
        </div>
    </div>
    {/if}

    <!-- Back Link -->
    <div class="mt-4">
        <a href="{$category.url}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> {$category.name} Kategorisine Dön
        </a>
    </div>
</div>

<style>
.xcreate-item-detail {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.item-title {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 10px;
}

.item-meta {
    padding: 10px 0;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 20px;
}

.item-description {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #34495e;
}

.custom-fields-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    margin-top: 30px;
}

.field-group {
    background: white;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #3498db;
}

.field-label {
    color: #2c3e50;
    margin-bottom: 8px;
    display: block;
}

.field-value {
    color: #555;
    line-height: 1.6;
}

.field-value img {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
    margin: 5px 0;
}
</style>
