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
<{if $items}>
<div class="mh-container">
<div class="mh-module-grid" style="grid-template-columns:repeat(3,1fr)">
<{foreach item=item from=$items}>

      <a href="<{$item.url}>" class="mh-module-card" style="text-decoration:none">
        <div class="mc-thumb" style="background:linear-gradient(135deg,#1e293b,#1e3a5f)">
		   <{if $item.fields.modul_ikonu.value}>
                <img src="<{$item.fields.modul_ikonu.value}>" alt="<{$item.title}>">
            <{/if}>

			
		<{if $item.fields.rozet_etikerleri.values}>	
	     <div class="mc-badges-tl">
		  <{foreach item=v from=$item.fields.rozet_etikerleri.values_display}>
			<span class="badge badge-cms" style="font-size:10px"><{$v}></span>
		  <{/foreach}>
		  </div>
		  <{/if}>
		  
		 <{if $item.fields.ucret_durumu.value}>
			<div class="mc-badges-tr"><span class="badge badge-free" style="font-size:10px"><{$item.fields.ucret_durumu.value}></span></div>
		<{/if}>
        </div>
		
        <div class="mc-body">
          <div class="mc-title"><{$item.title}></div>
          <div class="mc-desc"><{$item.description}></div>
         
		 	<{if $item.fields.anahtar_tag.values}>	
			 <div class="mc-tags">
			 <{foreach item=v from=$item.fields.anahtar_tag.values_display}>
			 <span class="mc-tag"><{$v}></span>
			<{/foreach}>
			 </div>
			<{/if}>
		 
          <div class="mc-meta">
            <div class="d-flex align-center gap-8"><div class="mh-stars" style="font-size:13px"><span class="mh-star on">★</span><span class="mh-star on">★</span><span class="mh-star on">★</span><span class="mh-star on">★</span><span class="mh-star">★</span></div><span style="font-size:12px;color:var(--text-3)">4.0 (87)</span></div>
            <div class="mc-downloads">📥 3.241</div>
          </div>
          <div class="mc-footer"><div class="mc-author"><div class="mc-avatar">E</div><span class="mc-author-name"><{$item.author}></span></div><span class="mc-date"><{$item.created}></span></div>
        </div>
		 
      </a>
	  <{/foreach}>
	  </div>
	  </div>
	  <{/if}>