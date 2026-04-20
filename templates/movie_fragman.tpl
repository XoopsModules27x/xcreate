<div class="mh-container">
<{xoBlock id=82}>
</div>
  <{if $items}>
   <div class="mh-container">
   <div class="mh-module-grid" style="grid-template-columns:repeat(3,1fr)">
<{foreach item=item from=$items}>
  <div class="movie-card">
    <!-- POSTER BÖLÜMÜ: ÜZERİNE GELİNCE BİLGİLER GÖRÜNÜYOR -->
    <div class="poster-container">
        <!-- Normal poster görünümü (başlangıç) -->
        <div class="poster-default">
            <div class="film-icon">
		   <{if $item.fields.film_kapak.value}>
                <img src="<{$item.fields.film_kapak.value}>" alt="<{$item.title}>">
            <{/if}>

            </div>
            <div class="poster-label"><{$item.title}></div>
        </div>
        
        <!-- HOVER ile açılan bilgi katmanı (resmin üstünde film detayları) -->
        <div class="poster-hover-info">
            <div class="hover-title">
                <{$item.title}>
                <span class="hover-imdb">★ <{$item.f_film_imbd}> IMDb</span>
            </div>
            <div class="hover-meta">
                <span><{$item.f_film_turu}></span>
                <span><{$item.f_film_ulkesi}></span>
            </div>
            <div class="hover-director">
                <strong>Yönetmen:</strong> 
				<{$item.f_film_yonetmeni}>				
            </div>
            
				
			<{if $item.fields.film_oyunculari.values}>	
		<div class="hover-cast">
			 <strong>Oyuncular:</strong>
			 <{foreach item=v from=$item.fields.film_oyunculari.values_display}>
			 <{$v}>,
			 <{/foreach}>
			</div>
			<{/if}>
				
				
            
            <div class="hover-versions">
                <span class="hover-version-badge">HD</span>
                <span class="hover-version-badge">DUAL</span>
            </div>
            <div class="hover-duration-list">
                <span class="duration-chip">2026 · 120 dk</span>
                <span class="duration-chip">2026 · 83 dk</span>
            </div>
            <div style="margin-top: 10px; font-size: 0.7rem; opacity:0.7;">
                🎭 Farklı versiyon süreleri
            </div>
        </div>
    </div>

    <div class="card-content">
        <!-- Başlık ve IMDB puanı (kart alt kısmında da mevcut) -->
        <div class="title-row">
            <h1 class="movie-title"><{$item.title}></h1>
            <div class="rating-badge">
                <span class="star-icon">★</span> IMDb <{$item.f_film_imbd}>
            </div>
        </div>

        <!-- Süre, yaş sınırı, tür -->
        <div class="meta-details">
            <div class="meta-item"><strong><{$item.f_film_suresi}> dk</strong></div>
            <div class="meta-item"><{$item.f_film_turu}></div>
        </div>



        
        <div class="action-link">
            <a href="<{$item.url}>" class="watch-link" aria-label="<{$smarty.const._MD_XCREATE_DETAILS_LABEL}>">
                🎞️ <{$smarty.const._MD_XCREATE_DETAILS_LABEL}>
            </a>
        </div>
    </div>
</div>
   <{/foreach}>
   </div>
     </div>
  <{/if}>


  
  
  
  
  
  
  <style>

        /* Ana kart */
        .movie-card {
            max-width: 420px;
            width: 100%;
            background: white;
            border-radius: 28px;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }

        .movie-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 28px 40px -16px rgba(0, 0, 0, 0.12);
        }

        /* POSTER ALANI - HOVER ÖZELLİĞİ BURADA */
        .poster-container {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #0a0c15;
            overflow: hidden;
            cursor: pointer;
			height: 310px;
        }

        /* Normal durumdaki poster görseli (minimal ikon + başlık) */
        .poster-default {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #0e121f 0%, #080b12 100%);
            transition: opacity 0.3s ease;
            z-index: 1;
        }

        .film-icon {
            font-size: 64px;
            letter-spacing: 6px;
            margin-bottom: 12px;
            opacity: 0.85;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4));
        }

        .poster-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 600;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(4px);
            padding: 5px 16px;
            border-radius: 60px;
            letter-spacing: 1.5px;
            color: #f0f0f0;
        }

        /* HOVER ile görünen BİLGİ KATMANI (resmin üstünde detaylar) */
        .poster-hover-info {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(8, 12, 22, 0.92);
            backdrop-filter: blur(8px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 1.2rem;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 2;
            color: white;
            overflow-y: auto;
        }

        /* hover olduğunda bilgi katmanı görünür, default katman kaybolur (isteğe bağlı) */
        .poster-container:hover .poster-hover-info {
            opacity: 1;
            visibility: visible;
        }

        .poster-container:hover .poster-default {
            opacity: 0;
        }

        /* hover bilgi içeriği stilleri */
        .hover-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.2px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 8px;
        }

        .hover-imdb {
            background: #f5c518;
            color: #1e1e2a;
            padding: 3px 10px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .hover-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 12px 0 10px 0;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .hover-meta span {
            background: rgba(255,255,255,0.15);
            padding: 4px 10px;
            border-radius: 30px;
        }

        .hover-director, .hover-cast {
            font-size: 0.75rem;
            line-height: 1.4;
            margin-top: 8px;
            color: #e0e4ec;
        }

        .hover-director strong, .hover-cast strong {
            color: white;
            font-weight: 600;
        }

        .hover-versions {
            display: flex;
            gap: 10px;
            margin: 14px 0 12px 0;
            flex-wrap: wrap;
        }

        .hover-version-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .hover-duration-list {
            margin-top: 8px;
            font-size: 0.7rem;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 12px;
        }

        .duration-chip {
            background: rgba(0,0,0,0.5);
            padding: 4px 10px;
            border-radius: 24px;
            font-weight: 500;
        }

        /* içerik bölümü (kartın alt kısmı) */
        .card-content {
            padding: 1.4rem 1.5rem 1.8rem 1.5rem;
        }

        .title-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .movie-title {
            font-size: 1.85rem;
            font-weight: 700;
            letter-spacing: -0.3px;
            color: #111;
            line-height: 1.2;
        }

        .rating-badge {
            background: #f5f5f7;
            padding: 5px 10px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e1e2a;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .star-icon {
            color: #f5b50e;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .meta-details {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
            margin-bottom: 16px;
            border-bottom: 1px solid #eceef2;
            padding-bottom: 14px;
        }

        .meta-item {
            font-size: 0.85rem;
            font-weight: 500;
            color: #4b5563;
            background: #f9f9fb;
            padding: 4px 12px;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .country-director {
            margin: 14px 0 10px 0;
            font-size: 0.85rem;
            color: #374151;
            line-height: 1.4;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .country-director span {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .label-light {
            font-weight: 500;
            color: #6c7280;
            min-width: 68px;
        }

        .value {
            font-weight: 500;
            color: #1f2a3e;
        }

        .cast {
            font-size: 0.85rem;
            color: #374151;
            margin-top: 8px;
            margin-bottom: 22px;
            border-bottom: 1px solid #eceef2;
            padding-bottom: 14px;
        }

        .version-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 12px 0 18px 0;
            align-items: center;
        }

        .version-badge {
            background: #f0f2f5;
            padding: 5px 14px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #1f2937;
        }

        .version-badge.hd {
            background: #eef2ff;
            color: #1e40af;
        }

        .version-badge.dual {
            background: #e6f7ec;
            color: #166534;
        }

        .version-badge.humint {
            background: #fef3c7;
            color: #92400e;
        }

        .duration-years {
            background: #fafafc;
            border-radius: 20px;
            padding: 12px 14px;
            margin-top: 8px;
            border: 1px solid #f0f1f3;
        }

        .duration-years h4 {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            color: #6c727f;
            margin-bottom: 12px;
        }

        .info-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 16px 28px;
            align-items: baseline;
        }

        .info-chip {
            display: flex;
            align-items: baseline;
            gap: 6px;
            background: white;
            padding: 4px 10px;
            border-radius: 32px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #1f2a44;
            border: 0.5px solid #e9ebef;
        }

        .info-chip .year {
            font-weight: 600;
            color: #4b5563;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 40px;
            font-size: 0.7rem;
        }

        .info-chip .duration {
            font-weight: 700;
            color: #0f172a;
        }

        .action-link {
            margin-top: 22px;
            text-align: right;
        }

        .watch-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #2563eb;
            text-decoration: none;
            background: #eff6ff;
            padding: 6px 18px;
            border-radius: 40px;
            transition: background 0.2s;
        }

        .watch-link:hover {
            background: #e0e7ff;
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }
            .card-content {
                padding: 1.2rem;
            }
            .movie-title {
                font-size: 1.6rem;
            }
            .hover-title {
                font-size: 1.2rem;
            }
            .poster-hover-info {
                padding: 1rem;
            }
        }
    </style>
