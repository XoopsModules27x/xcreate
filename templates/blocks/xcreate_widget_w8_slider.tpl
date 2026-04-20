<{if $block.items}>
<div class="xc-widget xc-widget-slider">
    <div class="xc-slider" id="<{$block.unique_id}>">
        <div class="xc-slider-track">
            <{foreach item=item from=$block.items}>
                <div class="xc-slide">
                    <{assign var="img_field" value=$block.image_field}>
                    <{if $item[$img_field]}>
                        <div class="xc-slide-img">
                            <img src="<{$item[$img_field]}>" alt="<{$item.title}>" loading="lazy">
                        </div>
                    <{/if}>
                    <div class="xc-slide-body">
                        <a href="<{$item.category_url}>" class="xc-cat-badge"><{$item.category}></a>
                        <a href="<{$item.url}>" class="xc-slide-title"><{$item.title}></a>
                        <{if $item.description}>
                            <p class="xc-slide-desc"><{$item.description}>...</p>
                        <{/if}>
                    </div>
                </div>
            <{/foreach}>
        </div>
        <button class="xc-slider-btn xc-slider-prev" onclick="xcSliderMove('<{$block.unique_id}>',-1)">‹</button>
        <button class="xc-slider-btn xc-slider-next" onclick="xcSliderMove('<{$block.unique_id}>',1)">›</button>
        <div class="xc-slider-dots" id="<{$block.unique_id}>_dots"></div>
    </div>
</div>
<style>
.xc-widget-slider{padding:8px 0}.xc-slider{position:relative;overflow:hidden;border-radius:6px;border:1px solid #eee}.xc-slider-track{display:flex;transition:transform .35s ease}.xc-slide{min-width:100%;box-sizing:border-box}.xc-slide-img img{width:100%;height:150px;object-fit:cover;display:block}.xc-slide-body{padding:10px}.xc-cat-badge{display:inline-block;background:#0055aa;color:#fff;font-size:.7em;padding:2px 7px;border-radius:3px;text-decoration:none;margin-bottom:5px}.xc-slide-title{font-weight:700;font-size:.9em;color:#222;text-decoration:none;display:block;margin-bottom:4px;line-height:1.3}.xc-slide-title:hover{color:#0055aa}.xc-slide-desc{font-size:.8em;color:#666;margin:0;line-height:1.4}.xc-slider-btn{position:absolute;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.45);color:#fff;border:none;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:1.2em;line-height:1;display:flex;align-items:center;justify-content:center;z-index:10}.xc-slider-prev{left:5px}.xc-slider-next{right:5px}.xc-slider-dots{display:flex;justify-content:center;gap:5px;padding:6px 0;background:#fafafa}.xc-slider-dot{width:8px;height:8px;border-radius:50%;background:#ccc;border:none;cursor:pointer;padding:0}.xc-slider-dot.active{background:#0055aa}
</style>
<script>
(function(){
    var sliders = {};
    function init(id, count, autoMs) {
        sliders[id] = {cur: 0, count: count, auto: null};
        var dotsEl = document.getElementById(id + '_dots');
        if (dotsEl) {
            for (var i = 0; i < count; i++) {
                var d = document.createElement('button');
                d.className = 'xc-slider-dot' + (i === 0 ? ' active' : '');
                d.setAttribute('data-idx', i);
                d.setAttribute('data-slider', id);
                d.onclick = function(){ xcSliderGo(this.getAttribute('data-slider'), parseInt(this.getAttribute('data-idx'))); };
                dotsEl.appendChild(d);
            }
        }
        if (autoMs > 0) {
            sliders[id].auto = setInterval(function(){ xcSliderMove(id, 1); }, autoMs);
        }
    }
    window.xcSliderMove = function(id, dir) {
        if (!sliders[id]) return;
        var s = sliders[id];
        xcSliderGo(id, (s.cur + dir + s.count) % s.count);
    };
    window.xcSliderGo = function(id, idx) {
        if (!sliders[id]) return;
        var s = sliders[id];
        s.cur = idx;
        var track = document.querySelector('#' + id + ' .xc-slider-track');
        if (track) track.style.transform = 'translateX(-' + (idx * 100) + '%)';
        var dots = document.querySelectorAll('#' + id + '_dots .xc-slider-dot');
        dots.forEach(function(d, i){ d.className = 'xc-slider-dot' + (i === idx ? ' active' : ''); });
    };
    // Init çağrısı
    init('<{$block.unique_id}>', <{$block.items|count}>, <{$block.auto_ms}>);
})();
</script>
<{/if}>
