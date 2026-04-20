  
  <link rel="stylesheet" href="<{$module_url}>/assets/css/xcreate-rating.css">
<script src="<{$module_url}>/assets/js/xcreate-rating.js"></script>
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
<div class="content-page">
    <!-- İç padding ile çerçeve içinde nefes alanı -->
    <div class="page-inner">
        <!-- ÜSTTE YOUTUBE VİDEO OYNATICI - resimdeki gibi video player -->
        <div class="video-container">
            <div class="video-wrapper">
                <!-- YouTube embed: Scream 7 fragmanı (resmi fragman ID'si, gerçek fragman için değiştirilebilir) -->
			<iframe width="560" height="315" src="<{$f_movie_fragman}>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>

        <!-- FİLM BAŞLIĞI VE BİLGİLER -->
        <div class="film-header">
            <div class="title-section">
                <div class="main-title">
                    <h1><{$item.title}></h1>
                    <span class="year-badge"><{$f_vizyon_tarihi}></span>
                </div>
            </div>
            <div class="original-title">
                <{$item.title}>
            </div>
        </div>

        <!-- IMDb + Tür + Ülke + Yönetmen (resimdeki gibi bir satır) -->
        <div class="imdb-detail-row">
            <div class="imdb-box">
                <span class="imdb-star">★</span>
                <span class="imdb-score">5.6</span>
                <span class="imdb-max">/10</span>
            </div>
            <div class="detail-items">
                <span>🎬 <{$f_film_turu}></span>
            </div>
            <div class="director-info">
                🎥 Yönetmen: <strong><{$f_film_yonetmeni}></strong>
            </div>
        </div>

        <!-- YILDIZ DERECELENDİRMESİ: 7 / 10 (⭐ x7, ☆ x3) -->
        <div class="rating-area">
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

        <!-- FİLM AÇIKLAMASI (resimdeki metin) -->
        <div class="synopsis">
            <p>
			<{$item.description}>
            </p>
        </div>

        <!-- Ek bilgi alanı (isteğe bağlı) -->
        <div class="extra-info">
            <span>📅 Vizyon: <{$f_vizyon_tarihi}></span>
            <span>⏱️ Süre: <{$f_film_suresi}> dk (tahmini)</span>
            <span>🔞 Ülke: <{$f_film_ulkesi}></span>
            <span>🎭 Yapım: Spyglass Media Group</span>
        </div>
    </div>
</div>
</div>
  
  
  
  
  
  <style>

        /* Ana içerik sayfası - GRI ÇİZGİ ÇERÇEVE eklendi */
        .content-page {
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 28px;
            overflow: hidden;
            /* GRİ ÇERÇEVE - komple gri çizgi ile çevrili */
            border: 2px solid #e2e6ea;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.08);
            transition: box-shadow 0.2s ease;
        }

        .content-page:hover {
            box-shadow: 0 25px 40px -14px rgba(0, 0, 0, 0.12);
        }

        /* İç padding - içerik ile çerçeve arasında nefes alanı */
        .page-inner {
            padding: 24px 28px 32px 28px;
        }

        /* VIDEO OYNATICI BÖLÜMÜ - YouTube embed gibi */
        .video-container {
            position: relative;
            width: 100%;
            background: #000;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            margin-bottom: 28px;
        }

        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
        }

        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        /* FİLM BAŞLIK ALANI */
        .film-header {
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 16px;
        }

        .title-section {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .main-title {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0a0a0a;
            line-height: 1.2;
        }

        .main-title h1 {
            font-size: inherit;
            font-weight: 800;
            display: inline;
        }

        .year-badge {
            font-size: 1.1rem;
            font-weight: 500;
            color: #6c727f;
            background: #f0f2f5;
            padding: 4px 14px;
            border-radius: 40px;
            display: inline-block;
        }

        .original-title {
            font-size: 1rem;
            color: #6c727f;
            font-weight: 500;
            margin-top: 6px;
            letter-spacing: -0.2px;
        }

        /* IMDb ve detay satırı (resimdeki gibi) */
        .imdb-detail-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 20px;
            background: #f8f9fc;
            padding: 12px 18px;
            border-radius: 60px;
            margin: 18px 0;
            border: 1px solid #eceef2;
        }

        .imdb-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #1e1e2a;
            padding: 6px 16px;
            border-radius: 40px;
            color: white;
        }

        .imdb-star {
            color: #f5c518;
            font-weight: bold;
            font-size: 1rem;
        }

        .imdb-score {
            font-weight: 700;
            font-size: 1rem;
        }

        .imdb-max {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .detail-items {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .detail-items span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .director-info {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.85rem;
        }

        /* YILDIZ DERECELENDİRMESİ ★★★★★★★★★★ 7/10 */
        .rating-area {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
            margin: 20px 0 24px 0;
            padding: 8px 0;
        }

        .stars-block {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #fef9e6;
            padding: 8px 20px;
            border-radius: 60px;
        }

        .stars {
            display: flex;
            gap: 3px;
        }

        .star-full {
            color: #f5b50e;
            font-size: 1.2rem;
        }

        .star-empty {
            color: #e2e4e8;
            font-size: 1.2rem;
        }

        .rating-value {
            font-weight: 800;
            font-size: 1.1rem;
            color: #1f2937;
            margin-left: 6px;
        }

        .vote-count {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* BUTON GRUBU: Fragman, Beğen, Listeye Ekle (resimdeki gibi) */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin: 24px 0 28px 0;
            border-bottom: 1px solid #eceef2;
            padding-bottom: 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 28px;
            border-radius: 60px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            font-family: inherit;
            background: white;
        }

        .btn-trailer {
            background: #e50914;
            color: white;
            box-shadow: 0 2px 8px rgba(229, 9, 20, 0.2);
        }

        .btn-trailer:hover {
            background: #b00710;
            transform: scale(0.98);
            cursor: pointer;
        }

        .btn-like {
            background: #f0f2f5;
            color: #2d3748;
            border: 1px solid #e2e6ea;
        }

        .btn-like:hover {
            background: #ffe6e6;
            border-color: #fbc4c4;
            color: #b91c1c;
            cursor: pointer;
        }

        .btn-like .like-count {
            font-weight: 800;
            margin-left: 4px;
            color: #dc2626;
        }

        .btn-addlist {
            background: #f0f2f5;
            color: #2d3748;
            border: 1px solid #e2e6ea;
        }

        .btn-addlist:hover {
            background: #e6f0ff;
            border-color: #b0c4ff;
            cursor: pointer;
        }

        /* AÇIKLAMA BÖLÜMÜ (resimdeki özet metni) */
        .synopsis {
            background: #fafafc;
            padding: 24px 28px;
            border-radius: 28px;
            margin: 16px 0 20px 0;
            border: 1px solid #f0f1f3;
        }

        .synopsis p {
            font-size: 1rem;
            line-height: 1.6;
            color: #1f2a44;
            font-weight: 450;
        }

        /* EKSTRA BİLGİ ALANI (Opsiyonel, resimdeki gibi detaylar) */
        .extra-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 8px;
            font-size: 0.8rem;
            color: #6c727f;
            border-top: 1px solid #eceef2;
            padding-bottom: 12px;
        }

        /* Responsive */
        @media (max-width: 750px) {
            body {
                padding: 1rem;
            }
            .page-inner {
                padding: 18px 20px 24px 20px;
            }
            .main-title {
                font-size: 1.6rem;
            }
            .imdb-detail-row {
                border-radius: 24px;
                padding: 12px 16px;
            }
            .detail-items {
                gap: 12px;
                font-size: 0.8rem;
            }
            .btn {
                padding: 8px 20px;
                font-size: 0.8rem;
            }
            .synopsis {
                padding: 18px;
            }
            .synopsis p {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 550px) {
            .title-section {
                flex-direction: column;
                align-items: flex-start;
            }
            .imdb-detail-row {
                flex-direction: column;
                align-items: flex-start;
                border-radius: 20px;
            }
            .stars-block {
                padding: 6px 14px;
            }
            .action-buttons {
                gap: 12px;
            }
            .btn {
                padding: 8px 16px;
                font-size: 0.75rem;
            }
        }

        /* buton etkileşimleri için ek */
        .like-active {
            background: #ffe6e6;
            border-color: #fbc4c4;
        }

        .like-active .like-count {
            color: #dc2626;
        }
    </style>
