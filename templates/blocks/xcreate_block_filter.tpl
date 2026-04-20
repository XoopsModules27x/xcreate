<{* xcreate_block_filter.tpl v2.0 - Pill/Badge Dropdown Filtreleme *}>
<{* Eren Yumak tarafından kodlanmıştır - Aymak *}>

<{assign var="bid" value=$block.cat_id}>

<div class="xcf-wrap" id="xcf-wrap-<{$bid}>">

    <{* ─── FİLTRE ÇUBUĞU ─── *}>
    <div class="xcf-bar" id="xcf-bar-<{$bid}>">

        <{foreach item=field from=$block.filter_fields}>
            <{assign var="fid" value=$field.id}>

            <{* Aktif mi kontrol et *}>
            <{assign var="is_active" value=false}>
            <{if isset($block.active_filters[$fid]) && $block.active_filters[$fid] != ''}>
                <{assign var="is_active" value=true}>
            <{/if}>
            <{assign var="r_data" value=$block.active_ranges[$fid]}>
            <{if $r_data}>
                <{assign var="is_active" value=true}>
            <{/if}>

            <div class="xcf-pill-wrap" data-field="<{$fid}>">

                <{* Pill butonu *}>
                <button type="button"
                        class="xcf-pill<{if $is_active}> xcf-pill--active<{/if}>"
                        data-field="<{$fid}>"
                        data-type="<{$field.type}>"
                        aria-expanded="false">
                    <span class="xcf-pill-label">
                        <{if $is_active}>
                            <{if $field.type == 'number'}>
                                <{assign var="rv" value=$r_data}>
                                <{$field.label}>: <{$rv.min}> – <{$rv.max}>
                            <{else}>
                                <{assign var="sel_val" value=$block.active_filters[$fid]}>
                                <{assign var="sel_lbl" value=$sel_val}>
                                <{if $field.options}>
                                    <{foreach item=opt from=$field.options}>
                                        <{if $opt.value == $sel_val}>
                                            <{assign var="sel_lbl" value=$opt.label}>
                                        <{/if}>
                                    <{/foreach}>
                                <{/if}>
                                <{$field.label}>: <{$sel_lbl}>
                            <{/if}>
                        <{else}>
                            <{$field.label}>
                        <{/if}>
                    </span>
                    <svg class="xcf-pill-arrow" width="10" height="10" viewBox="0 0 10 10" fill="none">
                        <path d="M2 3.5L5 6.5L8 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <{if $is_active}>
                        <span class="xcf-pill-clear" data-field="<{$fid}>" title="<{$smarty.const._MB_XCREATE_CLEAR}>">✕</span>
                    <{/if}>
                </button>

                <{* Dropdown paneli *}>
                <div class="xcf-dropdown" id="xcf-drop-<{$fid}>" hidden>

                    <{if $field.type == 'select' || $field.type == 'radio' || $field.type == 'checkbox'}>
                        <{assign var="sel_val" value=$block.active_filters[$fid]}>
                        <div class="xcf-option-list">
                            <div class="xcf-option xcf-option-all<{if !$is_active}> xcf-option--selected<{/if}>" data-value="" data-field="<{$fid}>">
                                <span class="xcf-opt-check"><{if !$is_active}>✓<{/if}></span><{$smarty.const._MB_XCREATE_FILTER_ALL_OPTION}>
                            </div>
                            <{foreach item=opt from=$field.options}>
                                <{assign var="opt_active" value=false}>
                                <{if $sel_val == $opt.value}>
                                    <{assign var="opt_active" value=true}>
                                <{/if}>
                                <div class="xcf-option<{if $opt_active}> xcf-option--selected<{/if}>" data-value="<{$opt.value}>" data-field="<{$fid}>">
                                    <span class="xcf-opt-check"><{if $opt_active}>✓<{/if}></span><{$opt.label}>
                                </div>
                            <{/foreach}>
                        </div>

                    <{elseif $field.type == 'number'}>
                        <{assign var="f_min" value=$field.num_min}>
                        <{assign var="f_max" value=$field.num_max}>
                        <{if $r_data}>
                            <{assign var="cur_min" value=$r_data.min}>
                            <{assign var="cur_max" value=$r_data.max}>
                        <{else}>
                            <{assign var="cur_min" value=$f_min}>
                            <{assign var="cur_max" value=$f_max}>
                        <{/if}>
                        <div class="xcf-range-panel">
                            <div class="xcf-range-display-row">
                                <span class="xcf-range-val-label">
                                    <span id="xcf-rmin-<{$fid}>"><{$cur_min}></span>
                                    &nbsp;—&nbsp;
                                    <span id="xcf-rmax-<{$fid}>"><{$cur_max}></span>
                                </span>
                            </div>
                            <div class="xcf-slider-wrap">
                                <div class="xcf-slider-track">
                                    <div class="xcf-slider-fill" id="xcf-sfill-<{$fid}>"></div>
                                </div>
                                <input type="range" class="xcf-slider xcf-slider-lo"
                                       data-field="<{$fid}>"
                                       data-abs-min="<{$f_min}>" data-abs-max="<{$f_max}>"
                                       min="<{$f_min}>" max="<{$f_max}>" step="1"
                                       value="<{$cur_min}>">
                                <input type="range" class="xcf-slider xcf-slider-hi"
                                       data-field="<{$fid}>"
                                       data-abs-min="<{$f_min}>" data-abs-max="<{$f_max}>"
                                       min="<{$f_min}>" max="<{$f_max}>" step="1"
                                       value="<{$cur_max}>">
                            </div>
                            <div class="xcf-range-inputs-row">
                                <input type="number" class="xcf-range-num xcf-range-num-lo"
                                       data-field="<{$fid}>" min="<{$f_min}>" max="<{$f_max}>" value="<{$cur_min}>">
                                <span>—</span>
                                <input type="number" class="xcf-range-num xcf-range-num-hi"
                                       data-field="<{$fid}>" min="<{$f_min}>" max="<{$f_max}>" value="<{$cur_max}>">
                            </div>
                            <button type="button" class="xcf-apply-btn" data-field="<{$fid}>" data-action="range"><{$smarty.const._MB_XCREATE_FILTER_APPLY}></button>
                        </div>

                    <{elseif $field.type == 'date'}>
                        <{assign var="sel_val" value=$block.active_filters[$fid]}>
                        <div class="xcf-text-panel">
                            <input type="date" class="xcf-text-input" data-field="<{$fid}>" value="<{$sel_val}>">
                            <button type="button" class="xcf-apply-btn" data-field="<{$fid}>" data-action="text"><{$smarty.const._MB_XCREATE_FILTER_APPLY}></button>
                        </div>

                    <{else}>
                        <{assign var="sel_val" value=$block.active_filters[$fid]}>
                        <div class="xcf-text-panel">
                            <input type="text" class="xcf-text-input" data-field="<{$fid}>"
                                   placeholder="<{$field.label}> ara..."
                                   value="<{$sel_val}>"
                                   <{if $field.suggestions}>list="xcf-sugg-<{$fid}>"<{/if}>>
                            <{if $field.suggestions}>
                                <datalist id="xcf-sugg-<{$fid}>">
                                    <{foreach item=s from=$field.suggestions}>
                                        <option value="<{$s}>">
                                    <{/foreach}>
                                </datalist>
                            <{/if}>
                            <button type="button" class="xcf-apply-btn" data-field="<{$fid}>" data-action="text"><{$smarty.const._MB_XCREATE_FILTER_APPLY}></button>
                        </div>
                    <{/if}>

                </div>
            </div>
        <{/foreach}>

        <{if $block.filter_applied}>
            <a href="<{$block.base_url}>" class="xcf-pill xcf-pill--clear-all">✕ <{$smarty.const._MB_XCREATE_FILTER_CLEAR_ALL}></a>
        <{/if}>

        <button type="button" class="xcf-search-btn" id="xcf-search-<{$bid}>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <span id="xcf-cnt-<{$bid}>"><{if $block.filter_applied}><{$block.total_count}> <{$smarty.const._MD_XCREATE_SEARCH_RESULTS_SUFFIX}><{else}><{$smarty.const._MB_XCREATE_FILTER_SEARCH}><{/if}></span>
        </button>

    </div>

    <{* Aktif badge'ler *}>
    <div class="xcf-active-badges" id="xcf-badges-<{$bid}>" <{if !$block.active_badges}>style="display:none"<{/if}>>
        <span class="xcf-badges-label"><{$smarty.const._MB_XCREATE_FILTER_ACTIVE}></span>
        <{foreach item=badge from=$block.active_badges}>
            <span class="xcf-badge">
                <b><{$badge.label}>:</b> <{$badge.value}>
                <span class="xcf-badge-remove" data-field="<{$badge.fid}>" data-type="<{$badge.type}>">✕</span>
            </span>
        <{/foreach}>
    </div>

</div>

<style>
.xcf-wrap{font-family:inherit;position:relative}
.xcf-bar{display:flex;flex-wrap:wrap;gap:6px;align-items:center;padding:6px 0}
.xcf-pill{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border:1.5px solid #d1d5db;border-radius:999px;background:#fff;font-size:13px;font-weight:500;color:#374151;cursor:pointer;white-space:nowrap;transition:border-color .15s,background .15s,color .15s;text-decoration:none;user-select:none;position:relative}
.xcf-pill:hover{border-color:#6366f1;color:#4f46e5;background:#f5f3ff}
.xcf-pill--active{border-color:#f59e0b;background:#fffbeb;color:#92400e;font-weight:600}
.xcf-pill--active:hover{border-color:#d97706;background:#fef3c7}
.xcf-pill--clear-all{border-color:#fca5a5;background:#fff5f5;color:#b91c1c}
.xcf-pill--clear-all:hover{background:#fee2e2;border-color:#ef4444}
.xcf-pill-arrow{opacity:.5;transition:transform .2s;flex-shrink:0}
.xcf-pill[aria-expanded="true"] .xcf-pill-arrow{transform:rotate(180deg)}
.xcf-pill-clear{margin-left:2px;font-size:11px;opacity:.6;padding:1px 3px;border-radius:50%;line-height:1}
.xcf-pill-clear:hover{opacity:1;background:rgba(0,0,0,.1)}
.xcf-search-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 18px;background:#6366f1;color:#fff;border:none;border-radius:999px;font-size:13px;font-weight:700;cursor:pointer;transition:background .15s;white-space:nowrap}
.xcf-search-btn:hover{background:#4f46e5}
.xcf-pill-wrap{position:relative}
.xcf-dropdown{position:absolute;top:calc(100% + 6px);left:0;min-width:200px;max-width:290px;background:#fff;border:1.5px solid #e5e7eb;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.13);z-index:9999;overflow:hidden;animation:xcfIn .15s ease}
.xcf-dropdown[hidden]{display:none}
@keyframes xcfIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.xcf-option-list{max-height:240px;overflow-y:auto;padding:6px 0}
.xcf-option{display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:13px;color:#374151;cursor:pointer;transition:background .1s}
.xcf-option:hover{background:#f3f4f6}
.xcf-option--selected{background:#eff6ff;color:#1d4ed8;font-weight:600}
.xcf-option--selected:hover{background:#dbeafe}
.xcf-opt-check{width:16px;font-size:12px;color:#6366f1;flex-shrink:0}
.xcf-option-all{color:#9ca3af;font-style:italic}
.xcf-option-all.xcf-option--selected{color:#374151;font-style:normal}
.xcf-range-panel{padding:14px 16px;min-width:240px}
.xcf-range-display-row{display:flex;justify-content:center;margin-bottom:10px}
.xcf-range-val-label{background:#f3f4f6;border-radius:6px;padding:4px 12px;font-size:13px;font-weight:700;color:#4f46e5}
.xcf-slider-wrap{position:relative;height:32px;display:flex;align-items:center;margin-bottom:8px}
.xcf-slider-track{position:absolute;left:0;right:0;height:5px;background:#e5e7eb;border-radius:4px;pointer-events:none}
.xcf-slider-fill{position:absolute;height:100%;background:#6366f1;border-radius:4px}
.xcf-slider{position:absolute;width:100%;height:5px;background:transparent;-webkit-appearance:none;pointer-events:none;outline:none;margin:0;padding:0}
.xcf-slider::-webkit-slider-thumb{-webkit-appearance:none;width:18px;height:18px;border-radius:50%;background:#6366f1;border:2.5px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.25);pointer-events:all;cursor:grab}
.xcf-slider::-moz-range-thumb{width:16px;height:16px;border-radius:50%;background:#6366f1;border:2.5px solid #fff;pointer-events:all;cursor:grab}
.xcf-range-inputs-row{display:flex;align-items:center;gap:6px;margin-bottom:10px}
.xcf-range-num{flex:1;padding:5px 8px;border:1.5px solid #d1d5db;border-radius:7px;font-size:13px;text-align:center;outline:none}
.xcf-range-num:focus{border-color:#6366f1}
.xcf-range-inputs-row span{color:#9ca3af;font-size:12px}
.xcf-text-panel{padding:12px 14px;min-width:220px}
.xcf-text-input{width:100%;padding:7px 10px;border:1.5px solid #d1d5db;border-radius:7px;font-size:13px;outline:none;box-sizing:border-box;margin-bottom:8px}
.xcf-text-input:focus{border-color:#6366f1}
.xcf-apply-btn{width:100%;padding:7px;background:#6366f1;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s}
.xcf-apply-btn:hover{background:#4f46e5}
.xcf-active-badges{display:flex;flex-wrap:wrap;align-items:center;gap:6px;padding:4px 0}
.xcf-badges-label{font-size:11px;color:#9ca3af}
.xcf-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:999px;font-size:12px;color:#1e40af}
.xcf-badge b{font-weight:600}
.xcf-badge-remove{font-size:11px;cursor:pointer;opacity:.6;padding:1px 2px;border-radius:50%;line-height:1}
.xcf-badge-remove:hover{opacity:1;background:rgba(0,0,0,.1)}
.xcf-option-list::-webkit-scrollbar{width:4px}
.xcf-option-list::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:4px}
</style>

<script>
// Alan tanımları
var xcfFieldDefs_<{$bid}> = [
<{foreach item=field from=$block.filter_fields name=fl}>
{id:'<{$field.id}>',label:'<{$field.label|escape:"javascript"}>' ,type:'<{$field.type}>'<{if $field.options}>,options:[<{foreach item=opt from=$field.options name=ol}>{value:'<{$opt.value|escape:"javascript"}>', label:'<{$opt.label|escape:"javascript"}>'}  <{if !$smarty.foreach.ol.last}>,<{/if}><{/foreach}>]<{/if}>}<{if !$smarty.foreach.fl.last}>,<{/if}>
<{/foreach}>
];

(function(){
var BID         = '<{$bid}>';
var CAT_ID      = parseInt('<{$block.cat_id}>');
var AJAX_URL    = '<{$block.ajax_url}>';
var RESULT_BASE = '<{$block.result_base}>';
var FIELDS      = xcfFieldDefs_<{$bid}>;

var state = { filters:{}, ranges:{} };
<{foreach item=fv key=fk from=$block.active_filters}>state.filters[<{$fk}>] = '<{$fv|escape:"javascript"}>';<{/foreach}>
<{foreach item=rv key=rk from=$block.active_ranges}>state.ranges[<{$rk}>] = {min:<{$rv.min}>,max:<{$rv.max}>};<{/foreach}>

var wrap     = document.getElementById('xcf-wrap-' + BID);
var cntEl    = document.getElementById('xcf-cnt-'  + BID);
var ajaxT    = null;

function getFieldDef(fid){ return FIELDS.find(function(f){ return f.id == fid; }) || null; }

function escH(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// Dropdown aç/kapa
function toggleDrop(fid){
    wrap.querySelectorAll('.xcf-dropdown').forEach(function(d){
        if(d.id !== 'xcf-drop-'+fid){ d.hidden=true; var b=wrap.querySelector('.xcf-pill[data-field="'+d.id.replace('xcf-drop-','')+'"]'); if(b) b.setAttribute('aria-expanded','false'); }
    });
    var drop=document.getElementById('xcf-drop-'+fid);
    var btn=wrap.querySelector('.xcf-pill[data-field="'+fid+'"]');
    if(!drop) return;
    var opening = drop.hidden;
    drop.hidden = !opening;
    if(btn) btn.setAttribute('aria-expanded', String(opening));
    if(opening) initSlider(fid);
}
function closeAll(){
    wrap.querySelectorAll('.xcf-dropdown').forEach(function(d){ d.hidden=true; });
    wrap.querySelectorAll('.xcf-pill[aria-expanded]').forEach(function(b){ b.setAttribute('aria-expanded','false'); });
}

// Pill görünümü güncelle
function updatePill(fid){
    var pill = wrap.querySelector('.xcf-pill[data-field="'+fid+'"]');
    if(!pill) return;
    var lbl  = pill.querySelector('.xcf-pill-label');
    var clr  = pill.querySelector('.xcf-pill-clear');
    var fd   = getFieldDef(fid);
    var isActive = (state.filters[fid]!==undefined) || (state.ranges[fid]!==undefined);
    pill.classList.toggle('xcf-pill--active', isActive);
    if(isActive){
        var txt = fd ? fd.label : fid;
        if(state.ranges[fid]){ txt += ': '+state.ranges[fid].min+' – '+state.ranges[fid].max; }
        else if(state.filters[fid]){
            var dv=state.filters[fid];
            if(fd && fd.options){ fd.options.forEach(function(o){ if(o.value===dv) dv=o.label; }); }
            txt += ': '+dv;
        }
        lbl.textContent = txt;
        if(!clr){ clr=document.createElement('span'); clr.className='xcf-pill-clear'; clr.dataset.field=fid; clr.title='<{$smarty.const._MB_XCREATE_CLEAR|escape:"javascript"}>'; clr.textContent='✕'; pill.appendChild(clr); }
    } else {
        lbl.textContent = fd ? fd.label : fid;
        if(clr) clr.remove();
    }
}

// Badge'leri güncelle
function refreshBadges(){
    var cont = document.getElementById('xcf-badges-'+BID);
    if(!cont) return;
    var hasFilt = Object.keys(state.filters).length>0;
    var hasRange = Object.keys(state.ranges).length>0;
    if(!hasFilt && !hasRange){ cont.style.display='none'; return; }
    cont.style.display='flex';
    var html='<span class="xcf-badges-label"><{$smarty.const._MB_XCREATE_FILTER_ACTIVE|escape:"javascript"}></span>';
    Object.keys(state.filters).forEach(function(fid){
        var fd=getFieldDef(fid); var lbl=fd?fd.label:fid; var dv=state.filters[fid];
        if(fd&&fd.options){ fd.options.forEach(function(o){ if(o.value===dv) dv=o.label; }); }
        html+='<span class="xcf-badge"><b>'+escH(lbl)+':</b> '+escH(dv)+'<span class="xcf-badge-remove" data-field="'+fid+'" data-type="filter">✕</span></span>';
    });
    Object.keys(state.ranges).forEach(function(fid){
        var fd=getFieldDef(fid); var lbl=fd?fd.label:fid; var r=state.ranges[fid];
        html+='<span class="xcf-badge"><b>'+escH(lbl)+':</b> '+r.min+' – '+r.max+'<span class="xcf-badge-remove" data-field="'+fid+'" data-type="range">✕</span></span>';
    });
    cont.innerHTML=html;
}

// Slider init
function initSlider(fid){
    var lo=wrap.querySelector('.xcf-slider-lo[data-field="'+fid+'"]');
    var hi=wrap.querySelector('.xcf-slider-hi[data-field="'+fid+'"]');
    var fill=document.getElementById('xcf-sfill-'+fid);
    var minEl=document.getElementById('xcf-rmin-'+fid);
    var maxEl=document.getElementById('xcf-rmax-'+fid);
    var loN=wrap.querySelector('.xcf-range-num-lo[data-field="'+fid+'"]');
    var hiN=wrap.querySelector('.xcf-range-num-hi[data-field="'+fid+'"]');
    if(!lo||!hi||lo._xcfOk) return;
    lo._xcfOk=true;
    var aMin=parseFloat(lo.dataset.absMin), aMax=parseFloat(lo.dataset.absMax);
    function sync(){
        var l=parseFloat(lo.value), h=parseFloat(hi.value);
        var pL=((l-aMin)/(aMax-aMin))*100, pH=((h-aMin)/(aMax-aMin))*100;
        if(fill){ fill.style.left=pL+'%'; fill.style.width=(pH-pL)+'%'; }
        if(minEl) minEl.textContent=l; if(maxEl) maxEl.textContent=h;
        if(loN) loN.value=l; if(hiN) hiN.value=h;
    }
    lo.addEventListener('input',function(){ if(parseFloat(lo.value)>parseFloat(hi.value)) lo.value=hi.value; sync(); });
    hi.addEventListener('input',function(){ if(parseFloat(hi.value)<parseFloat(lo.value)) hi.value=lo.value; sync(); });
    if(loN) loN.addEventListener('change',function(){ var v=Math.max(aMin,Math.min(parseFloat(this.value)||aMin,parseFloat(hi.value))); lo.value=v; this.value=v; sync(); });
    if(hiN) hiN.addEventListener('change',function(){ var v=Math.min(aMax,Math.max(parseFloat(this.value)||aMax,parseFloat(lo.value))); hi.value=v; this.value=v; sync(); });
    sync();
}

// AJAX sayım
function scheduleAjax(){
    clearTimeout(ajaxT);
    if(cntEl) cntEl.textContent='...';
    ajaxT=setTimeout(function(){
        var body=new URLSearchParams();
        body.append('xcf2_ajax','1');
        body.append('cat_id',CAT_ID);
        Object.keys(state.filters).forEach(function(fid){ body.append('filters['+fid+']',state.filters[fid]); });
        Object.keys(state.ranges).forEach(function(fid){ body.append('ranges['+fid+'][min]',state.ranges[fid].min); body.append('ranges['+fid+'][max]',state.ranges[fid].max); });
        fetch(AJAX_URL,{method:'POST',body:body})
            .then(function(r){ return r.json(); })
            .then(function(d){ if(cntEl) cntEl.textContent=d.count+' <{$smarty.const._MD_XCREATE_SEARCH_RESULTS_SUFFIX|escape:"javascript"}>'; })
            .catch(function(){ if(cntEl) cntEl.textContent='<{$smarty.const._MB_XCREATE_FILTER_SEARCH|escape:"javascript"}>'; });
    }, 400);
}

// Ara → yönlendir
document.getElementById('xcf-search-'+BID).addEventListener('click',function(){
    var url=RESULT_BASE, sep=url.indexOf('?')!==-1?'&':'?';
    Object.keys(state.filters).forEach(function(fid){ url+=sep+'xcf_'+fid+'='+encodeURIComponent(state.filters[fid]); sep='&'; });
    Object.keys(state.ranges).forEach(function(fid){ url+=sep+'xcf_'+fid+'_min='+state.ranges[fid].min+'&xcf_'+fid+'_max='+state.ranges[fid].max; sep='&'; });
    window.location.href=url;
});

// Tüm tıklamalar
wrap.addEventListener('click',function(e){
    // Pill butonu
    var pill=e.target.closest('.xcf-pill[data-field]');
    if(pill && !e.target.closest('.xcf-pill-clear') && !pill.classList.contains('xcf-pill--clear-all')){
        e.stopPropagation(); toggleDrop(pill.dataset.field); return;
    }
    // Pill × temizle
    var pc=e.target.closest('.xcf-pill-clear');
    if(pc){ e.stopPropagation(); var fid=pc.dataset.field; delete state.filters[fid]; delete state.ranges[fid]; updatePill(fid); refreshBadges(); scheduleAjax(); return; }
    // Badge × temizle
    var br=e.target.closest('.xcf-badge-remove');
    if(br){ var fid=br.dataset.field; if(br.dataset.type==='range') delete state.ranges[fid]; else delete state.filters[fid]; updatePill(fid); refreshBadges(); scheduleAjax(); return; }
    // Seçenek seç
    var opt=e.target.closest('.xcf-option');
    if(opt){
        var fid=opt.dataset.field, val=opt.dataset.value;
        if(val==='') delete state.filters[fid]; else state.filters[fid]=val;
        var drop=document.getElementById('xcf-drop-'+fid);
        drop.querySelectorAll('.xcf-option').forEach(function(o){
            var sel=o.dataset.value===val; o.classList.toggle('xcf-option--selected',sel); o.querySelector('.xcf-opt-check').textContent=(sel&&val!=='')?'✓':'';
        });
        updatePill(fid); refreshBadges(); closeAll(); scheduleAjax(); return;
    }
    // Uygula (range veya text)
    var ap=e.target.closest('.xcf-apply-btn');
    if(ap){
        var fid=ap.dataset.field, action=ap.dataset.action;
        if(action==='range'){
            var lo=wrap.querySelector('.xcf-slider-lo[data-field="'+fid+'"]');
            var hi=wrap.querySelector('.xcf-slider-hi[data-field="'+fid+'"]');
            state.ranges[fid]={min:parseFloat(lo.value),max:parseFloat(hi.value)};
        } else {
            var inp=wrap.querySelector('.xcf-text-input[data-field="'+fid+'"]');
            if(inp&&inp.value.trim()) state.filters[fid]=inp.value.trim(); else delete state.filters[fid];
        }
        updatePill(fid); refreshBadges(); closeAll(); scheduleAjax(); return;
    }
});

// Enter = uygula (text input)
wrap.addEventListener('keydown',function(e){
    if(e.key!=='Enter') return;
    var inp=e.target.closest('.xcf-text-input');
    if(!inp) return;
    var fid=inp.dataset.field;
    if(inp.value.trim()) state.filters[fid]=inp.value.trim(); else delete state.filters[fid];
    updatePill(fid); refreshBadges(); closeAll(); scheduleAjax();
});

// Dışarı tıkla
document.addEventListener('click',function(e){ if(!wrap.contains(e.target)) closeAll(); });

// Başlangıç badge refresh
refreshBadges();

})();
</script>
