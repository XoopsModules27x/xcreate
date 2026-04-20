<div class="xc-widget xc-widget-stats">
    <div class="xc-stats-grid">
        <div class="xc-stat-box">
            <span class="xc-stat-icon">📦</span>
            <span class="xc-stat-num"><{$block.total_items}></span>
            <span class="xc-stat-lbl">İçerik</span>
        </div>
        <div class="xc-stat-box">
            <span class="xc-stat-icon">🗂</span>
            <span class="xc-stat-num"><{$block.total_cats}></span>
            <span class="xc-stat-lbl">Kategori</span>
        </div>
        <{if $block.show_hits}>
        <div class="xc-stat-box">
            <span class="xc-stat-icon">👁</span>
            <span class="xc-stat-num"><{$block.total_hits}></span>
            <span class="xc-stat-lbl">Görüntüleme</span>
        </div>
        <{/if}>
        <div class="xc-stat-box">
            <span class="xc-stat-icon">🆕</span>
            <span class="xc-stat-num"><{$block.this_week}></span>
            <span class="xc-stat-lbl">Bu Hafta</span>
        </div>
    </div>
</div>
<style>
.xc-widget-stats{padding:8px 0}.xc-stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.xc-stat-box{background:#f8f9fa;border:1px solid #eee;border-radius:6px;padding:10px 6px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:3px}.xc-stat-icon{font-size:1.3em}.xc-stat-num{font-size:1.4em;font-weight:700;color:#0055aa;line-height:1}.xc-stat-lbl{font-size:.72em;color:#888;text-transform:uppercase;letter-spacing:.5px}
</style>
