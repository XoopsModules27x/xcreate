/**
 * xcreate-rating.js  —  Yıldızlı oylama widget
 * Eren Yumak tarafından kodlanmıştır — Aymak
 *
 * Template kullanımı:
 *
 *   <!-- Detay sayfası: etkileşimli -->
 *   <div class="xcreate-rating-widget"
 *        data-item-id="{$item.id}"
 *        data-user-vote="{$rating.user_vote}"
 *        data-average="{$rating.average}"
 *        data-count="{$rating.count}"
 *        data-ajax-url="{$module_url}/ajax/rating.php"
 *        data-mode="full">
 *   </div>
 *
 *   <!-- Liste sayfası: sadece gösterim -->
 *   <div class="xcreate-rating-widget"
 *        data-item-id="{$item.id}"
 *        data-average="{$item.rating.average}"
 *        data-count="{$item.rating.count}"
 *        data-mode="compact"
 *        data-readonly="1">
 *   </div>
 *
 *   <script src="{$module_url}/assets/js/xcreate-rating.js"></script>
 */

(function () {
    'use strict';

    /* ── Yardımcılar ─────────────────────────────────────── */

    function buildStars(average, selectedVote) {
        var html = '<span class="xcreate-stars">';
        for (var i = 1; i <= 5; i++) {
            var cls = 'star empty';
            if (selectedVote > 0) {
                cls = i <= selectedVote ? 'star full selected' : 'star empty';
            } else {
                if (i <= Math.floor(average)) {
                    cls = 'star full';
                } else if (i === Math.ceil(average) && (average % 1) >= 0.25) {
                    cls = 'star half';
                }
            }
            html += '<span class="' + cls + '" data-score="' + i + '">&#9733;</span>';
        }
        html += '</span>';
        return html;
    }

    function buildCompact(average, count) {
        var html = '<span class="xcreate-rating-compact">';
        html += buildStars(average, 0);
        html += '<span class="avg-score">' + (count > 0 ? Number(average).toFixed(1) : '—') + '</span>';
        html += '<span class="max-score">/ 5</span>';
        if (count > 0) {
            html += '<span class="vote-count">(' + count + ')</span>';
        }
        html += '</span>';
        return html;
    }

    function buildDistribution(dist, count) {
        var html = '<div class="xcreate-rating-distribution">';
        for (var s = 5; s >= 1; s--) {
            var cnt = (dist && dist[s]) ? dist[s] : 0;
            var pct = count > 0 ? Math.round((cnt / count) * 100) : 0;
            html += '<div class="dist-row">';
            html += '<span class="dist-label">' + s + '★</span>';
            html += '<div class="dist-bar-wrap"><div class="dist-bar" style="width:' + pct + '%"></div></div>';
            html += '<span class="dist-count">' + cnt + '</span>';
            html += '</div>';
        }
        html += '</div>';
        return html;
    }

    /* ── Full widget render ───────────────────────────────── */

    function renderFull(el, data) {
        var average  = parseFloat(data.average)  || 0;
        var count    = parseInt(data.count)       || 0;
        var userVote = parseInt(data.user_vote)   || 0;
        var readonly = el.getAttribute('data-readonly') === '1';
        var voted    = userVote > 0;

        var html = '';
        html += buildStars(average, userVote);

        // Özet
        html += '<div class="xcreate-rating-summary">';
        html += '<span class="avg-score">' + (count > 0 ? Number(average).toFixed(1) : '—') + '</span>';
        html += '<span class="max-score">/ 5</span>';
        html += '<span class="vote-count">' + count + ' değerlendirme</span>';
        html += '</div>';

        // Dağılım (sadece etkileşimli modda)
        if (!readonly && data.distribution) {
            html += buildDistribution(data.distribution, count);
        }

        // Mesaj satırı
        if (!readonly) {
            var msg = voted
                ? ('Oyunuz: ' + userVote + ' yıldız — değiştirmek için tıklayın')
                : 'Oylayın';
            html += '<div class="xcreate-rating-msg">' + msg + '</div>';
        }

        el.innerHTML = html;

        // CSS sınıfları
        el.classList.add('xcreate-rating');
        if (!readonly) {
            el.classList.add('xcreate-rating-interactive');
            if (voted) el.classList.add('voted'); else el.classList.remove('voted');
        }

        if (!readonly) {
            attachStarEvents(el, data);
        }
    }

    /* ── Yıldız etkileşimi ───────────────────────────────── */

    function attachStarEvents(el, data) {
        var stars   = el.querySelectorAll('.xcreate-stars .star');
        var msgEl   = el.querySelector('.xcreate-rating-msg');
        var ajaxUrl = (el.getAttribute('data-ajax-url') || '').trim();

        if (!ajaxUrl) {
            if (msgEl) { msgEl.textContent = 'Hata: ajax URL tanımsız.'; msgEl.className = 'xcreate-rating-msg error'; }
            return;
        }

        stars.forEach(function (star) {

            /* Hover efekti */
            star.addEventListener('mouseenter', function () {
                if (el.classList.contains('loading')) return;
                var score = parseInt(star.getAttribute('data-score'));
                stars.forEach(function (s) {
                    var v = parseInt(s.getAttribute('data-score'));
                    s.className = 'star ' + (v <= score ? 'full hover' : 'empty');
                });
                if (msgEl) msgEl.textContent = score + ' yıldız';
            });

            star.addEventListener('mouseleave', function () {
                var uv  = parseInt(data.user_vote || 0);
                var avg = parseFloat(data.average) || 0;
                stars.forEach(function (s) {
                    var v = parseInt(s.getAttribute('data-score'));
                    if (uv > 0) {
                        s.className = 'star ' + (v <= uv ? 'full selected' : 'empty');
                    } else {
                        if (v <= Math.floor(avg)) s.className = 'star full';
                        else if (v === Math.ceil(avg) && (avg % 1) >= 0.25) s.className = 'star half';
                        else s.className = 'star empty';
                    }
                });
                if (msgEl) {
                    msgEl.textContent = uv > 0
                        ? ('Oyunuz: ' + uv + ' yıldız — değiştirmek için tıklayın')
                        : 'Oylayın';
                    msgEl.className = 'xcreate-rating-msg';
                }
            });

            /* Tıklama — oy gönder */
            star.addEventListener('click', function () {
                if (el.classList.contains('loading')) return;

                var score  = parseInt(star.getAttribute('data-score'));
                var itemId = el.getAttribute('data-item-id');

                el.classList.add('loading');
                if (msgEl) { msgEl.textContent = 'Kaydediliyor...'; msgEl.className = 'xcreate-rating-msg'; }

                var formData = new FormData();
                formData.append('item_id', itemId);
                formData.append('score',   score);

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.text(); // önce text al, sonra parse et
                })
                .then(function (text) {
                    el.classList.remove('loading');
                    var resp;
                    try {
                        resp = JSON.parse(text);
                    } catch (e) {
                        // Sunucu JSON dışı bir şey döndürdü (PHP hatası olabilir)
                        if (msgEl) {
                            msgEl.textContent = 'Sunucu hatası.';
                            msgEl.className = 'xcreate-rating-msg error';
                        }
                        console.error('xcreate-rating: invalid JSON response:', text.substring(0, 200));
                        return;
                    }

                    if (resp.success) {
                        data.average      = resp.average;
                        data.count        = resp.count;
                        data.user_vote    = resp.user_vote;
                        data.distribution = resp.distribution;
                        el.setAttribute('data-user-vote', resp.user_vote);
                        el.setAttribute('data-average',   resp.average);
                        el.setAttribute('data-count',     resp.count);
                        renderFull(el, data);
                    } else {
                        var errMap = {
                            'invalid_params':   'Geçersiz parametre.',
                            'db_error':         'Veritabanı hatası.',
                            'invalid_token':    'Oturum hatası, sayfayı yenileyin.',
                            'mainfile_not_found': 'Sunucu yapılandırma hatası.',
                        };
                        var errMsg = errMap[resp.error] || ('Hata: ' + (resp.error || 'bilinmiyor'));
                        if (msgEl) { msgEl.textContent = errMsg; msgEl.className = 'xcreate-rating-msg error'; }
                        console.error('xcreate-rating error:', resp);
                    }
                })
                .catch(function (err) {
                    el.classList.remove('loading');
                    if (msgEl) { msgEl.textContent = 'Bağlantı hatası (' + err.message + ').'; msgEl.className = 'xcreate-rating-msg error'; }
                    console.error('xcreate-rating fetch error:', err);
                });
            });

        }); // end forEach stars
    }

    /* ── Widget başlatma ─────────────────────────────────── */

    function initWidget(el) {
        var data = {
            average:      parseFloat(el.getAttribute('data-average'))   || 0,
            count:        parseInt(el.getAttribute('data-count'))        || 0,
            user_vote:    parseInt(el.getAttribute('data-user-vote'))    || 0,
            distribution: null,
        };

        var mode = el.getAttribute('data-mode') || 'full';

        if (mode === 'compact') {
            el.innerHTML = buildCompact(data.average, data.count);
        } else {
            renderFull(el, data);
        }
    }

    function initAll() {
        var widgets = document.querySelectorAll('.xcreate-rating-widget');
        widgets.forEach(function (el) {
            initWidget(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

})();
