<div class="xcreate-search-wrap">

<{* ── Arama Formu ──────────────────────────────────────────────────────── *}>
<div class="xcs-form-card">
  <form method="get" action="<{$xcreate_search.module_url}>/search.php" class="xcs-form" id="xcs-form">

    <div class="xcs-main-row">
      <div class="xcs-search-box">
        <svg class="xcs-icon-search" viewBox="0 0 20 20" fill="none">
          <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.5"/>
          <path d="M13 13l3.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <input type="text" name="q" id="xcs-q" value="<{$xcreate_search.q}>"
               placeholder="<{$smarty.const._MD_XCREATE_SEARCH_PLACEHOLDER}>"
               class="xcs-input-main" autocomplete="off">
        <{if $xcreate_search.q}>
          <button type="button" class="xcs-clear-btn"
                  onclick="document.getElementById('xcs-q').value='';document.getElementById('xcs-form').submit();">
            <svg viewBox="0 0 16 16" fill="none">
              <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </button>
        <{/if}>
      </div>
      <button type="submit" class="xcs-btn-search"><{$smarty.const._MD_XCREATE_SEARCH}></button>
    </div>

    <div class="xcs-filters-toggle" onclick="xcsToggleFilters()">
      <svg viewBox="0 0 16 16" fill="none">
        <path d="M2 4h12M4 8h8M6 12h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <{$smarty.const._MD_XCREATE_SEARCH_ADVANCED}>
      <{if $xcreate_search.cat_id || $xcreate_search.field_id || $xcreate_search.date_from || $xcreate_search.date_to}>
        <span class="xcs-filter-badge"><{$smarty.const._MD_XCREATE_SEARCH_ACTIVE}></span>
      <{/if}>
    </div>

    <{if $xcreate_search.cat_id || $xcreate_search.field_id || $xcreate_search.date_from || $xcreate_search.date_to}>
      <{assign var=xcs_filters_open value=1}>
    <{else}>
      <{assign var=xcs_filters_open value=0}>
    <{/if}>

    <div class="xcs-filters-panel" id="xcs-filters"
         style="<{if $xcs_filters_open}>display:block<{else}>display:none<{/if}>">

      <div class="xcs-filter-grid">

        <div class="xcs-filter-group">
          <label class="xcs-label" for="xcs-cat"><{$smarty.const._MD_XCREATE_CATEGORY}></label>
          <select name="cat_id" id="xcs-cat" class="xcs-select">
            <option value="0"><{$smarty.const._MD_XCREATE_SEARCH_ALL_CATEGORIES}></option>
            <{foreach item=cat from=$xcreate_search.categories}>
              <option value="<{$cat.id}>"<{if $xcreate_search.cat_id == $cat.id}> selected<{/if}>>
                <{$cat.name}>
              </option>
            <{/foreach}>
          </select>
        </div>

        <div class="xcs-filter-group">
          <label class="xcs-label" for="xcs-field"><{$smarty.const._MD_XCREATE_SEARCH_FIELD}></label>
          <select name="field_id" id="xcs-field" class="xcs-select">
            <option value="0"><{$smarty.const._MD_XCREATE_SEARCH_SELECT_FIELD}></option>
            <{foreach item=f from=$xcreate_search.fields}>
              <option value="<{$f.field_id}>"<{if $xcreate_search.field_id == $f.field_id}> selected<{/if}>>
                <{$f.field_label}>
              </option>
            <{/foreach}>
          </select>
        </div>

        <div class="xcs-filter-group">
          <label class="xcs-label" for="xcs-fval"><{$smarty.const._MD_XCREATE_SEARCH_FIELD_VALUE}></label>
          <input type="text" name="field_val" id="xcs-fval" class="xcs-input"
                 value="<{$xcreate_search.field_val}>" placeholder="<{$smarty.const._MD_XCREATE_SEARCH_FIELD_VALUE_PLACEHOLDER}>">
        </div>

        <div class="xcs-filter-group">
          <label class="xcs-label"><{$smarty.const._MD_XCREATE_SEARCH_DATE_RANGE}></label>
          <div class="xcs-date-row">
            <input type="date" name="date_from" class="xcs-input" value="<{$xcreate_search.date_from}>">
            <span class="xcs-date-sep">-</span>
            <input type="date" name="date_to" class="xcs-input" value="<{$xcreate_search.date_to}>">
          </div>
        </div>

      </div>

      <div class="xcs-filter-actions">
        <button type="submit" class="xcs-btn-apply"><{$smarty.const._MD_XCREATE_SEARCH_APPLY_FILTERS}></button>
        <a href="<{$xcreate_search.module_url}>/search.php" class="xcs-btn-reset"><{$smarty.const._MD_XCREATE_SEARCH_RESET}></a>
      </div>
    </div>

  </form>
</div>

<{if $xcreate_search.search_performed}>

  <div class="xcs-results-header">
    <div class="xcs-result-summary">
      <{if $xcreate_search.total gt 0}>
        <strong><{$xcreate_search.total}></strong> <{$smarty.const._MD_XCREATE_SEARCH_RESULTS_SUFFIX}>
        <{if $xcreate_search.q}> -- <{$smarty.const._MD_XCREATE_SEARCH_RESULTS_FOR}> <em><{$xcreate_search.q}></em><{/if}>
        <span class="xcs-took"><{$xcreate_search.search_took_ms}> ms</span>
      <{else}>
        <{if $xcreate_search.q}>
          "<{$xcreate_search.q}>" <{$smarty.const._MD_XCREATE_SEARCH_NO_RESULTS_FOR}>.
        <{else}>
          <{$smarty.const._MD_XCREATE_SEARCH_NO_FILTER_RESULTS}>
        <{/if}>
      <{/if}>
    </div>

    <{if $xcreate_search.total gt 0}>
      <form method="get" action="<{$xcreate_search.module_url}>/search.php" class="xcs-sort-form">
        <input type="hidden" name="q"         value="<{$xcreate_search.q}>">
        <input type="hidden" name="cat_id"    value="<{$xcreate_search.cat_id}>">
        <input type="hidden" name="field_id"  value="<{$xcreate_search.field_id}>">
        <input type="hidden" name="field_val" value="<{$xcreate_search.field_val}>">
        <input type="hidden" name="date_from" value="<{$xcreate_search.date_from}>">
        <input type="hidden" name="date_to"   value="<{$xcreate_search.date_to}>">
        <label class="xcs-label" for="xcs-sort"><{$smarty.const._MD_XCREATE_SEARCH_SORT}></label>
        <select name="sort" id="xcs-sort" class="xcs-select" onchange="this.form.submit()">
          <{foreach key=sval item=slabel from=$xcreate_search.sorts}>
            <option value="<{$sval}>"<{if $xcreate_search.sort eq $sval}> selected<{/if}>>
              <{$slabel}>
            </option>
          <{/foreach}>
        </select>
      </form>
    <{/if}>
  </div>

  <{if $xcreate_search.results}>
    <ul class="xcs-results">
    <{foreach item=item from=$xcreate_search.results}>
      <li class="xcs-result-item">

        <div class="xcs-result-meta-top">
          <{if $item.cat_name}>
            <a href="<{$xcreate_search.module_url}>/<{$item.cat_slug}>/" class="xcs-cat-tag">
              <{$item.cat_name}>
            </a>
          <{/if}>
          <span class="xcs-result-date"><{$item.created}></span>
          <{if $item.hits}>
            <span class="xcs-hits"><{$item.hits}> <{$smarty.const._MD_XCREATE_VIEWS}></span>
          <{/if}>
          <{if $item.rating}>
            <{if $item.rating.count gt 0}>
              <span class="xcs-rating">* <{$item.rating.average}> (<{$item.rating.count}>)</span>
            <{/if}>
          <{/if}>
        </div>

        <h3 class="xcs-result-title">
          <a href="<{$item.url}>"><{$item.title}></a>
        </h3>

        <{if $item.description}>
          <p class="xcs-result-snippet"><{$item.description}></p>
        <{/if}>

        <{if $xcreate_search.q}>
          <{if $item.fields}>
            <div class="xcs-field-matches">
              <{foreach item=fld from=$item.fields}>
                <{if $fld.value_highlighted}>
                  <span class="xcs-field-match">
                    <span class="xcs-field-label"><{$fld.label}>:</span>
                    <{$fld.value_highlighted}>
                  </span>
                <{/if}>
              <{/foreach}>
            </div>
          <{/if}>
        <{/if}>

        <a href="<{$item.url}>" class="xcs-result-link"><{$item.url}></a>

      </li>
    <{/foreach}>
    </ul>

    <{if $xcreate_search.pagenav}>
      <div class="xcs-pagenav"><{$xcreate_search.pagenav}></div>
    <{/if}>

  <{else}>
    <div class="xcs-empty">
      <p><{$smarty.const._MD_XCREATE_SEARCH_NO_MATCH}></p>
      <ul class="xcs-suggestions">
        <li><{$smarty.const._MD_XCREATE_SEARCH_TRY_KEYWORDS}></li>
        <li><{$smarty.const._MD_XCREATE_SEARCH_TRY_BROADER}></li>
        <li><{$smarty.const._MD_XCREATE_SEARCH_TRY_REMOVE_FILTERS}></li>
      </ul>
    </div>
  <{/if}>

<{else}>
  <div class="xcs-tips">
    <p><strong><{$smarty.const._MD_XCREATE_SEARCH_TIPS_TITLE}></strong></p>
    <ul class="xcs-tips-list">
      <li><{$smarty.const._MD_XCREATE_SEARCH_TIP_INDEX}></li>
      <li><{$smarty.const._MD_XCREATE_SEARCH_TIP_FILTERS}></li>
      <li><{$smarty.const._MD_XCREATE_SEARCH_TIP_EMPTY}></li>
    </ul>
  </div>
<{/if}>

</div>

<style>
.xcreate-search-wrap{max-width:820px;margin:0 auto;padding:0 0 40px}
.xcs-form-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-bottom:20px}
.xcs-main-row{display:flex;gap:10px;align-items:stretch}
.xcs-search-box{position:relative;flex:1}
.xcs-icon-search{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#9ca3af;pointer-events:none}
.xcs-input-main{width:100%;box-sizing:border-box;padding:11px 36px 11px 38px;border:1.5px solid #d1d5db;border-radius:8px;font-size:15px;outline:none}
.xcs-input-main:focus{border-color:#6366f1}
.xcs-clear-btn{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#9ca3af;display:flex}
.xcs-clear-btn svg{width:14px;height:14px}
.xcs-btn-search{padding:0 22px;background:#6366f1;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer}
.xcs-btn-search:hover{background:#4f46e5}
.xcs-filters-toggle{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7280;cursor:pointer;margin-top:12px;user-select:none}
.xcs-filters-toggle svg{width:14px;height:14px}
.xcs-filter-badge{background:#ede9fe;color:#7c3aed;font-size:11px;font-weight:500;padding:1px 7px;border-radius:99px}
.xcs-filters-panel{margin-top:14px;padding-top:14px;border-top:1px solid #f3f4f6}
.xcs-filter-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.xcs-filter-group{display:flex;flex-direction:column;gap:5px}
.xcs-label{font-size:12px;font-weight:500;color:#6b7280}
.xcs-input,.xcs-select{padding:8px 10px;border:1.5px solid #d1d5db;border-radius:6px;font-size:13px;outline:none;background:#fff}
.xcs-input:focus,.xcs-select:focus{border-color:#6366f1}
.xcs-date-row{display:flex;align-items:center;gap:6px}
.xcs-date-sep{color:#9ca3af;font-size:12px}
.xcs-date-row .xcs-input{flex:1;min-width:0}
.xcs-filter-actions{display:flex;gap:10px;margin-top:14px;align-items:center}
.xcs-btn-apply{padding:8px 16px;background:#6366f1;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:500;cursor:pointer}
.xcs-btn-apply:hover{background:#4f46e5}
.xcs-btn-reset{font-size:13px;color:#6b7280;text-decoration:none}
.xcs-results-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px}
.xcs-result-summary{font-size:14px;color:#374151}
.xcs-result-summary strong{font-weight:600;color:#111827}
.xcs-took{font-size:12px;color:#9ca3af;margin-left:8px}
.xcs-sort-form{display:flex;align-items:center;gap:8px}
.xcs-results{list-style:none;margin:0;padding:0}
.xcs-result-item{border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;margin-bottom:10px;background:#fff}
.xcs-result-item:hover{border-color:#c7d2fe;box-shadow:0 2px 8px rgba(99,102,241,.08)}
.xcs-result-meta-top{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:6px}
.xcs-cat-tag{font-size:11px;font-weight:500;background:#ede9fe;color:#7c3aed;padding:2px 9px;border-radius:99px;text-decoration:none}
.xcs-result-date,.xcs-hits,.xcs-rating{font-size:12px;color:#9ca3af}
.xcs-result-title{margin:0 0 6px;font-size:16px;font-weight:600}
.xcs-result-title a{color:#1d4ed8;text-decoration:none}
.xcs-result-title a:hover{text-decoration:underline}
.xcs-result-snippet{font-size:13px;color:#4b5563;line-height:1.6;margin:0 0 8px}
.xcs-result-item mark{background:#fef08a;color:inherit;border-radius:2px;padding:0 1px}
.xcs-field-matches{display:flex;flex-wrap:wrap;gap:8px;margin:6px 0}
.xcs-field-match{font-size:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:5px;padding:3px 9px}
.xcs-field-label{font-weight:500;color:#166534;margin-right:4px}
.xcs-result-link{font-size:12px;color:#16a34a;text-decoration:none;word-break:break-all}
.xcs-empty{text-align:center;padding:48px 24px;color:#6b7280}
.xcs-suggestions{text-align:left;display:inline-block;font-size:13px;margin-top:12px}
.xcs-tips{background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:20px 24px;margin-top:8px}
.xcs-tips-list{font-size:13px;color:#6b7280;margin:8px 0 0;padding-left:20px}
.xcs-tips-list li{margin-bottom:5px}
.xcs-pagenav{margin-top:24px;text-align:center}
@media(max-width:600px){.xcs-main-row{flex-direction:column}.xcs-filter-grid{grid-template-columns:1fr}.xcs-results-header{flex-direction:column;align-items:flex-start}}
</style>

<script>
function xcsToggleFilters(){
    var p=document.getElementById('xcs-filters');
    p.style.display=(p.style.display==='none')?'block':'none';
}
</script>
