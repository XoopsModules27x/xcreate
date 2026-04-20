<link rel="stylesheet" href="<{$module_url}>/assets/css/xcreate-rating.css">
<script src="<{$module_url}>/assets/js/xcreate-rating.js"></script>


<style>
/* ═══════════════════════════════════════════════
   Detail page — mobil düzeltmeleri
═══════════════════════════════════════════════ */

.detail-header {
  display: flex;
  gap: 24px;
  align-items: flex-start;
  padding: 24px 28px;
  border-bottom: 1px solid var(--border);
}
.detail-info { flex: 1; min-width: 0; }
.detail-title { font-size: 24px; font-weight: 800; margin-bottom: 8px; line-height: 1.3; }
.detail-tagline { color: var(--text-2); font-size: 15px; margin-bottom: 16px; line-height: 1.7; }

.detail-btns-row { display: flex; gap: 8px; flex-wrap: wrap; }
.detail-btns-row .btn { flex: 1; min-width: 90px; justify-content: center; font-size: 13px; padding: 9px 10px; }

.detail-stats-wrap { padding: 18px 24px; }
.detail-tab-content { padding: 24px; }
.detail-sidebar { display: flex; flex-direction: column; gap: 16px; }

.table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 16px; }
.table-scroll .mh-table { min-width: 400px; }

.screenshots-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
.ss-item {
  aspect-ratio: 16/10; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 36px; border: 1px solid var(--border); cursor: pointer;
  transition: var(--transition);
}
.ss-item:hover { transform: scale(1.02); }

.dl-stats-row {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: var(--text-3);
  padding: 0 2px;
  flex-wrap: wrap;
  gap: 6px;
}

/* ═══ TABLET ≤ 900px ═══ */
@media (max-width: 900px) {
  .mh-layout-detail { grid-template-columns: 1fr !important; }
}

/* ═══ MOBILE ≤ 768px ═══ */
@media (max-width: 768px) {

  .detail-header {
    flex-direction: column;
    padding: 16px;
    gap: 14px;
  }
  .mh-preview-gallery { width: 100% !important; }
  .detail-info { width: 100%; }

  .detail-title { font-size: 20px !important; }
  .detail-tagline { font-size: 14px; margin-bottom: 12px; }

  .detail-badges { gap: 6px !important; flex-wrap: wrap; }
  .detail-badges .badge { font-size: 10px; padding: 2px 8px; }

  .mh-rating-row { flex-wrap: wrap; gap: 6px; margin-bottom: 12px !important; }

  /* Meta grid 2 kolon */
  .mh-meta-grid { grid-template-columns: 1fr 1fr !important; margin-bottom: 14px !important; }

  /* Butonlar dikey */
  .detail-btns-row {
    flex-direction: column;
    gap: 8px;
  }
  .detail-btns-row .btn {
    width: 100%;
    min-width: 0;
    flex: none;
  }

  /* Stats bar 3'lü satır */
  .mh-stats-bar { flex-wrap: wrap; }
  .mh-stat-box {
    flex: 1 1 30%;
    border-bottom: 1px solid var(--border);
    border-right: 1px solid var(--border) !important;
  }
  .mh-stat-box:nth-child(3) { border-right: none !important; }
  .mh-stat-box:nth-last-child(-n+2) { border-bottom: none !important; }
  .mh-stat-val { font-size: 17px; }
  .mh-stat-lbl { font-size: 9px; }
  .detail-stats-wrap { padding: 12px 16px; }

  /* Tab içerik */
  .detail-tab-content { padding: 16px !important; }

  /* Feature grid tek kolon */
  .mh-feature-grid { grid-template-columns: 1fr !important; }

  /* Gereksinim grid de tek kolon olsun */
  .req-grid { grid-template-columns: 1fr !important; }

  /* Screenshots 2 kolon */
  .screenshots-grid { grid-template-columns: 1fr 1fr !important; }

  /* Changelog wrap */
  .mh-changelog-item { flex-wrap: wrap; }
  .mh-cl-version { min-width: auto !important; }

  /* Yorum formu dikey */
  .mh-form-row { flex-direction: column; gap: 0; }
  .mh-form-row .mh-form-group { margin-bottom: 12px; }

  /* Sidebar */
  .detail-sidebar .mh-card-body { padding: 14px; }
  .detail-sidebar .mh-card-header { padding: 12px 14px; font-size: 13px; }

  /* Donate */
  .btn-donate { width: 100%; justify-content: center; }

  /* DL stats */
  .dl-stats-row { justify-content: flex-start; }

  /* Container */
  .mh-container { padding: 12px; }
  .mh-breadcrumb-bar { padding: 10px 12px; }

  /* Download btn */
  .btn-download { font-size: 14px; padding: 12px; }

  /* Code block kaydırılabilir */
  .mh-code { font-size: 12px; padding: 14px; overflow-x: auto; }

  /* Author stats */
  .mh-as-val { font-size: 16px !important; }
}

/* ═══ SMALL MOBILE ≤ 480px ═══ */
@media (max-width: 480px) {

  /* Meta grid tek kolon */
  .mh-meta-grid { grid-template-columns: 1fr !important; }
  .mh-meta-item {
    border-right: none !important;
    border-bottom: 1px solid var(--border) !important;
  }
  .mh-meta-item:last-child { border-bottom: none !important; }
  /* nth-last-child(2) border'ı geri koy */
  .mh-meta-item:nth-last-child(2) { border-bottom: 1px solid var(--border) !important; }

  /* Stats 2+2+1 */
  .mh-stat-box { flex: 1 1 45%; }
  .mh-stat-box:nth-child(2) { border-right: none !important; }
  .mh-stat-box:nth-child(3) { border-right: 1px solid var(--border) !important; }
  .mh-stat-val { font-size: 15px; }

  /* Screenshots tek kolon */
  .screenshots-grid { grid-template-columns: 1fr !important; }
  .ss-item { aspect-ratio: 16/9; }

  /* Başlık */
  .detail-title { font-size: 18px !important; }
  .detail-tagline { font-size: 13px; }

  /* Nav */
  .mh-nav-logo { font-size: 17px; }

  /* Version badge */
  .mh-version-badge { font-size: 12px; }

  /* Info list value font */
  .il-value { font-size: 13px !important; }

  /* Tags */
  .mh-tag { font-size: 11px; padding: 4px 10px; }

  /* Related item */
  .mh-related-name { font-size: 12px; }

  /* Footer */
  .mh-footer { padding: 28px 16px 16px; }
  .mh-footer-bottom { flex-direction: column; text-align: center; }
}
.screenshots-grid {
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
</style>

<{if $seo}>
<{if $seo.canonical}><link rel="canonical" href="<{$seo.canonical}>" /><{/if}>
<{if $seo.noindex}><meta name="robots" content="noindex, nofollow" /><{else}><meta name="robots" content="index, follow" /><{/if}>
<meta property="og:type"        content="<{$seo.og_type|default:'article'}>" />
<meta property="og:title"       content="<{$seo.title}>" />
<meta property="og:description" content="<{$seo.description}>" />
<{if $seo.canonical}><meta property="og:url" content="<{$seo.canonical}>" /><{/if}>
<meta property="og:site_name"   content="<{$seo.site_name}>" />
<{if $seo.og_image}>
<meta property="og:image"        content="<{$seo.og_image}>" />
<meta property="og:image:width"  content="1200" />
<meta property="og:image:height" content="630" />
<{/if}>
<meta name="twitter:card"        content="<{if $seo.og_image}>summary_large_image<{else}>summary<{/if}>" />
<meta name="twitter:title"       content="<{$seo.title}>" />
<meta name="twitter:description" content="<{$seo.description}>" />
<{if $seo.og_image}><meta name="twitter:image" content="<{$seo.og_image}>" /><{/if}>
<script type="application/ld+json">
<{literal}>{"@context":"https://schema.org","@type":"Article","headline":"<{/literal}><{$seo.title|escape:'javascript'}><{literal}>","description":"<{/literal}><{$seo.description|escape:'javascript'}><{literal}>","url":"<{/literal}><{$seo.canonical|escape:'javascript'}><{literal}>","author":{"@type":"Person","name":"<{/literal}><{$item.author|escape:'javascript'}><{literal}>"},"publisher":{"@type":"Organization","name":"<{/literal}><{$seo.site_name|escape:'javascript'}><{literal}>"},"datePublished":"<{/literal}><{$item.created}><{literal}>","dateModified":"<{/literal}><{$item.updated}><{literal}>"}<{/literal}>
</script>
<{/if}>


<!-- BREADCRUMB -->
<{if $breadcrumb}>
<div class="mh-breadcrumb-bar">
  <div class="mh-breadcrumb">
    <a href="<{$module_url}>/index.php"><{$smarty.const._MD_XCREATE_HOME}></a>
	<{foreach item=crumb from=$breadcrumb}>
		&raquo; <a href="<{$crumb.url}>"><{$crumb.name}></a>
	<{/foreach}>
    <span><{$item.title}></span>
  </div>
</div>
<{/if}>

<div class="mh-container">
<div class="mh-layout-detail">

  <!-- LEFT -->
  <div>
    <div class="mh-card" style="margin-bottom:20px">

      <!-- Module Header -->
      <div class="detail-header">

        <!-- Preview -->
        <div class="mh-preview-gallery">
          <div class="mh-preview-main">
            <div class="big-icon"><{$fd_modul_ikonu}></div>
			
            <span style="font-size:13px"><{$item.title}></span>
          </div>
		  	<div class="xcreate-rating-widget"
			 data-item-id="<{$item.id}>"
			 data-user-vote="<{$rating.user_vote}>"
			 data-average="<{$rating.average}>"
			 data-count="<{$rating.count}>"
			 data-ajax-url="<{$module_url}>/ajax/rating.php"
			 data-token="<{$xoops_token}>"
			 data-mode="full">
			</div>
        </div>

        <!-- Info -->
        <div class="detail-info">

          <div class="detail-badges d-flex gap-8 mb-12" style="flex-wrap:wrap">
            <span class="badge badge-cms"><strong>🔷 <{$fl_anahtar_tag}>:</strong> <{$f_anahtar_tag}></span>
            <span class="badge badge-cat">📂 <{$category.name}></span>
            <span class="badge badge-pro">👑 <{$f_ucret_durumu}></span>
          </div>

          <h1 class="detail-title"><{$item.title}></h1>
          <p class="detail-tagline"><{$item.description}></p>


          <div class="mh-meta-grid mb-16">
            <div class="mh-meta-item"><span class="mh-meta-icon">👤</span><div><div class="mh-meta-label">Geliştirici</div><div class="mh-meta-value"><a href="<{$f_gelistirici_linki}>"><{$f_modul_gelistiricisi}></a></div></div></div>
            <div class="mh-meta-item"><span class="mh-meta-icon">📅</span><div><div class="mh-meta-label"><{$fl_yayin_tarihi}></div><div class="mh-meta-value"><{$f_yayin_tarihi}></div></div></div>
            <div class="mh-meta-item"><span class="mh-meta-icon">🔄</span><div><div class="mh-meta-label">Güncelleme</div><div class="mh-meta-value">28 Şub 2025</div></div></div>
            <div class="mh-meta-item"><span class="mh-meta-icon">📦</span><div><div class="mh-meta-label">Sürüm</div><div class="mh-meta-value">v2.1.0</div></div></div>
            <div class="mh-meta-item"><span class="mh-meta-icon">🌐</span><div><div class="mh-meta-label">CMS Sürümü</div><div class="mh-meta-value">XOOPS 2.5.x</div></div></div>
            <div class="mh-meta-item"><span class="mh-meta-icon">📄</span><div><div class="mh-meta-label">Lisans</div><div class="mh-meta-value">GPL v2</div></div></div>
          </div>

          <div style="display:flex;flex-direction:column;gap:10px">
            <a href="<{$f_dosya_alani}>" class="btn-download">⬇ Download <span class="dl-size">(v2.1.0 · 48 KB)</span></a>
            <div class="detail-btns-row">
              <a href="#" class="btn btn-secondary">📖 Öğretici</a>
              <a href="#" class="btn btn-secondary">🐛 Hata Bildir</a>
              <a href="<{$f_githup_link}>" class="btn btn-secondary">📂 <{$fl_githup_link}></a>
            </div>
            <div class="dl-stats-row">
              <span>👁 <{$item.hits}> görüntülenme</span>
              <span>⭐ <{$rating.count}> Oy</span>
              <span>🔁 45 fork</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Bar -->
      <div class="detail-stats-wrap">
        <div class="mh-stats-bar">
          <div class="mh-stat-box"><div class="mh-stat-val">3.241</div><div class="mh-stat-lbl">İndirme</div></div>
          <div class="mh-stat-box"><div class="mh-stat-val"><{$item.hits}></div><div class="mh-stat-lbl"><{$smarty.const._MD_XCREATE_VIEWS}></div></div>
          <div class="mh-stat-box"><div class="mh-stat-val">24</div><div class="mh-stat-lbl">Yorum</div></div>
          <div class="mh-stat-box"><div class="mh-stat-val"><{$rating.user_vote}>★</div><div class="mh-stat-lbl">Puan</div></div>
          <div class="mh-stat-box"><div class="mh-stat-val"><{$rating.count}></div><div class="mh-stat-lbl">Oylama</div></div>
        </div>
      </div>

      <!-- Tabs -->
      <div id="detailTabs">
        <div class="mh-tabs">
          <div class="mh-tab active" onclick="mhShowTab('detailTabs',0)">📋 <{$smarty.const._MD_XCREATE_DESCRIPTION_LABEL}></div>
          <div class="mh-tab" onclick="mhShowTab('detailTabs',1)">📸 Ekranlar</div>
          <div class="mh-tab" onclick="mhShowTab('detailTabs',2)">🔄 Değişiklikler</div>
          <div class="mh-tab" onclick="mhShowTab('detailTabs',3)">💬 Yorumlar (24)</div>
          <div class="mh-tab" onclick="mhShowTab('detailTabs',4)">📖 Kurulum</div>
        </div>

        <!-- TAB 0 — Açıklama -->
        <div class="mh-tab-pane active detail-tab-content">
		  <p class="detail-tagline">
		   <{$f_genel_aciklama}>
		   </p>

          <div class="mh-section-title">Temel Özellikler</div>
		 <{if $field.temel_ozellikler.is_repeatable}>
			   <div class="mh-feature-grid">
			   <{foreach item=v from=$field.temel_ozellikler.values_display}>
				<div class="mh-feature-item">✅ <{$v}></div>
				<{/foreach}>
			  </div>
			<{else}>
			<{$field.temel_ozellikler.value_display}>
		<{/if}>

          <div class="mh-section-title">Uyumluluk</div>
          <div class="table-scroll">
            <table class="mh-table">
              <thead><tr><th>Platform</th><th>Sürüm</th><th>PHP</th><th>Durum</th></tr></thead>
              <tbody>
                <tr><td>XOOPS</td><td>2.5.x</td><td>7.4 – 8.2</td><td><span class="mh-dot mh-dot-green"></span>Tam Uyumlu</td></tr>
                <tr><td>DataLife Engine</td><td>16.x – 17.x</td><td>7.4 – 8.1</td><td><span class="mh-dot mh-dot-green"></span>Tam Uyumlu</td></tr>
                <tr><td>DataLife Engine</td><td>14.x – 15.x</td><td>7.2 – 7.4</td><td><span class="mh-dot mh-dot-yellow"></span>Kısmen</td></tr>
                <tr><td>XOOPS</td><td>2.4.x</td><td>5.6 – 7.2</td><td><span class="mh-dot mh-dot-red"></span>Desteklenmiyor</td></tr>
              </tbody>
            </table>
          </div>

          <div class="mh-section-title">Gereksinimler</div>
          <div class="mh-feature-grid req-grid" style="grid-template-columns:repeat(3,1fr)">
            <div class="mh-feature-item">🐘 PHP 7.4+</div>
            <div class="mh-feature-item">🗄️ MySQL 5.7+</div>
            <div class="mh-feature-item">🌐 jQuery 3.x</div>
          </div>
        </div>

        <!-- TAB 1 — Ekranlar -->
        <div class="mh-tab-pane detail-tab-content">
        
           <{$fd_resim_galerisi}>
         
        </div>

        <!-- TAB 2 — Değişiklikler -->
        <div class="mh-tab-pane detail-tab-content">
          <div class="mh-changelog-item">
            <div class="mh-cl-version latest">v2.1.0</div>
            <div>
              <div class="mh-cl-date">28 Şubat 2025</div>
              <ul class="mh-cl-notes">
                <li>AJAX filtreleme performansı %40 iyileştirildi</li>
                <li>Yeni alan tipi: Renk seçici eklendi</li>
                <li>PHP 8.2 uyumluluk güncellemesi</li>
                <li>Çoklu seçim kaydetme hatası düzeltildi</li>
              </ul>
            </div>
          </div>
          <div class="mh-changelog-item">
            <div class="mh-cl-version">v2.0.0</div>
            <div>
              <div class="mh-cl-date">10 Kasım 2024</div>
              <ul class="mh-cl-notes">
                <li>Tamamen yeniden yazılan filtreleme motoru</li>
                <li>Tekrarlanabilir alan grupları desteği</li>
                <li>DLE 17.x uyumluluk eklendi</li>
              </ul>
            </div>
          </div>
          <div class="mh-changelog-item">
            <div class="mh-cl-version">v1.5.2</div>
            <div>
              <div class="mh-cl-date">15 Ocak 2024</div>
              <ul class="mh-cl-notes">
                <li>İlk kararlı sürüm yayınlandı</li>
                <li>Temel filtreleme ve özel alan desteği</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- TAB 3 — Yorumlar -->
        <div class="mh-tab-pane detail-tab-content">
          <div style="margin-bottom:20px">
            <div class="mh-form-group">
              <label class="mh-label">Yorumunuz</label>
              <textarea class="mh-textarea" placeholder="Deneyiminizi paylaşın..."></textarea>
            </div>
            <div class="mh-form-row" style="gap:10px">
              <div class="mh-form-group" style="flex:1">
                <label class="mh-label">Adınız</label>
                <input class="mh-input" placeholder="Ad Soyad">
              </div>
              <div class="mh-form-group" style="flex:1">
                <label class="mh-label">E-posta</label>
                <input class="mh-input" type="email" placeholder="ornek@mail.com">
              </div>
            </div>
            <button class="btn btn-primary" onclick="mhToast('Yorumunuz gönderildi!')">💬 Yorum Gönder</button>
          </div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <div class="mh-comment">
              <div class="mh-comment-avatar">M</div>
              <div>
                <div class="mh-comment-header">
                  <strong class="mh-comment-author">mehmet_dev</strong>
                  <span class="mh-comment-time">3 gün önce</span>
                </div>
                <p class="mh-comment-text">Harika modül! AJAX filtreleme gerçekten hızlı çalışıyor. Kurulum da çok kolaydı.</p>
                <div class="mh-stars mt-8" style="font-size:14px">
                  <span class="mh-star on">★</span><span class="mh-star on">★</span>
                  <span class="mh-star on">★</span><span class="mh-star on">★</span><span class="mh-star on">★</span>
                </div>
              </div>
            </div>
            <div class="mh-comment">
              <div class="mh-comment-avatar" style="background:linear-gradient(135deg,#16a34a,#0d9488)">A</div>
              <div>
                <div class="mh-comment-header">
                  <strong class="mh-comment-author">ahmet_cms</strong>
                  <span class="mh-comment-time">1 hafta önce</span>
                </div>
                <p class="mh-comment-text">Tekrarlanabilir alan tam aradığım şeydi. DLE 17.x ile de sorunsuz çalışıyor.</p>
                <div class="mh-stars mt-8" style="font-size:14px">
                  <span class="mh-star on">★</span><span class="mh-star on">★</span>
                  <span class="mh-star on">★</span><span class="mh-star on">★</span><span class="mh-star">★</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 4 — Kurulum -->
        <div class="mh-tab-pane detail-tab-content">
          <div class="mh-section-title" style="margin-top:0">Kurulum Adımları</div>
          <div style="display:flex;flex-direction:column;gap:18px;margin-bottom:24px">
            <div class="mh-step">
              <div class="mh-step-num">1</div>
              <div>
                <div class="mh-step-title">ZIP dosyasını indirin ve çıkartın</div>
                <div class="mh-step-desc">İndirilen ZIP'i çıkartarak <code>xfilter_pro</code> klasörünü elde edin.</div>
              </div>
            </div>
            <div class="mh-step">
              <div class="mh-step-num">2</div>
              <div>
                <div class="mh-step-title">modules dizinine yükleyin</div>
                <div class="mh-step-desc">FTP ile <code>/modules/xfilter_pro/</code> dizinine yükleyin.</div>
              </div>
            </div>
            <div class="mh-step">
              <div class="mh-step-num">3</div>
              <div>
                <div class="mh-step-title">Admin panelinden aktif edin</div>
                <div class="mh-step-desc">XOOPS Admin → Modüller → Yükle bölümünden aktif edin.</div>
              </div>
            </div>
          </div>
          <div class="mh-section-title">Şablon Kullanımı</div>
          <div class="mh-code" style="overflow-x:auto">
            <button class="mh-copy-btn" onclick="mhCopyCode(this)">📋 Kopyala</button>
<span class="cm">&lt;!-- theme.tpl içinde --&gt;</span>
{xfilter category_id=<span class="st">"5"</span> style=<span class="st">"sidebar"</span>}

{foreach from=$xfilter_results item=<span class="kw">item</span>}
  &lt;div class=<span class="st">"item"</span>&gt;{$item.title}&lt;/div&gt;
{/foreach}
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- SIDEBAR -->
  <div class="detail-sidebar">

    <div class="mh-card">
      <div class="mh-card-header">📦 Sürüm Bilgisi</div>
      <div class="mh-card-body">
        <div class="mh-version-badge">🆕 v2.1.0 — Kararlı</div>
        <ul class="mh-info-list">
          <li><span class="il-label">📅 Yayın Tarihi</span><span class="il-value">28 Şub 2025</span></li>
          <hr class="mh-info-divider">
          <li><span class="il-label">📁 Dosya</span><span class="il-value" style="font-size:12px;word-break:break-all">xfilter_pro_v2.1.0.zip</span></li>
          <hr class="mh-info-divider">
          <li><span class="il-label">📏 Boyut</span><span class="il-value">48.3 KB</span></li>
          <hr class="mh-info-divider">
          <li><span class="il-label">🏷️ Lisans</span><span class="il-value">GPL v2</span></li>
          <hr class="mh-info-divider">
          <li><span class="il-label">🔢 Sürüm Sayısı</span><span class="il-value">7</span></li>
          <hr class="mh-info-divider">
          <li><span class="il-label">📥 Toplam İndirme</span><span class="il-value">3.241</span></li>
        </ul>
        <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
          <a href="#" class="btn-download" style="padding:11px;font-size:14px">⬇ İndir v2.1.0</a>
          <a href="#" class="btn btn-secondary btn-block" style="font-size:13px">📜 Tüm Sürümler</a>
        </div>
      </div>
    </div>

    <div class="mh-card">
      <div class="mh-card-header">👤 Geliştirici</div>
      <div class="mh-card-body">
        <div class="mh-author-profile">
          <div class="mh-author-avatar">E</div>
          <div>
            <div class="mh-author-name">erenyumak</div>
            <div class="mh-author-role">🇹🇷 CMS Modül Geliştirici</div>
          </div>
        </div>
        <div class="mh-author-stats">
          <div class="mh-as"><div class="mh-as-val">24</div><div class="mh-as-lbl">Modül</div></div>
          <div class="mh-as"><div class="mh-as-val">41K</div><div class="mh-as-lbl">İndirme</div></div>
          <div class="mh-as"><div class="mh-as-val">4.7★</div><div class="mh-as-lbl">Ort.</div></div>
        </div>
        <button class="btn-follow">+ Takip Et</button>
      </div>
    </div>

    <div class="mh-card">
      <div class="mh-card-header">🏷️ Etiketler</div>
      <div class="mh-card-body">
        <div class="mh-tags-cloud">
          <span class="mh-tag">filtreleme</span><span class="mh-tag">xoops</span>
          <span class="mh-tag">dle</span><span class="mh-tag">özel alan</span>
          <span class="mh-tag">ajax</span><span class="mh-tag">arama</span>
          <span class="mh-tag">kategori</span><span class="mh-tag">php</span>
          <span class="mh-tag">modül</span><span class="mh-tag">form</span>
        </div>
      </div>
    </div>

    <div class="mh-card">
      <div class="mh-card-header">🔗 İlgili Modüller</div>
      <div class="mh-card-body">
        <div style="display:flex;flex-direction:column;gap:4px">
		<{xcreate category="1" template="ilgili_moduller" order="random" limit="3"}>
        </div>
      </div>
    </div>

    <div class="mh-card">
      <div class="mh-card-header">☕ Destek Ol</div>
      <div class="mh-card-body text-center">
        <p class="text-sm text-muted mb-12">Bu modül işinize yaradıysa geliştirmeye devam edebilmem için destek olabilirsiniz.</p>
        <button class="btn-donate" onclick="mhToast('Teşekkürler! ☕','success')">☕ Kahve Ismarla</button>
      </div>
    </div>

  </div>
</div>
</div>
