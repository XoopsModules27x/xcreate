
Yıldızlı Oy Verme

<link rel="stylesheet" href="<{$module_url}>/assets/css/xcreate-rating.css">
<script src="<{$module_url}>/assets/js/xcreate-rating.js"></script>


İçerik de TPL Kullanımı:

			<div class="xcreate-rating-widget"
				 data-item-id="<{$item.id}>"
				 data-user-vote="<{$rating.user_vote}>"
				 data-average="<{$rating.average}>"
				 data-count="<{$rating.count}>"
				 data-ajax-url="https://turkish.erenyumak.com/modules/xcreate/ajax/rating.php"
				 data-token="<{$xoops_token}>"
				 data-mode="full">
			</div>

{* Ayrı ayrı da kullanabilirsiniz *}

Ortalama: <{$rating.average_str}> / 5
Toplam oy: <{$rating.count}>
Kullanıcı oyu: <{$rating.user_vote}>
5★: <{$rating.distribution.5}> oy (<{$rating.dist_pct.5}%>)
4★: <{$rating.distribution.4}> oy


Modül özet sayfasında kullanım:

<div class="xcreate-rating-widget"
     data-item-id="<{$item.id}>"
     data-average="<{$item.rating.average}>"
     data-count="<{$item.rating.count}>"
     data-mode="compact"
     data-readonly="1">
</div>

<{$item.rating.average_str}> ★ <({$item.rating.count} oy>)


Olası Oylama tablosu:

CREATE TABLE `xcreate_ratings` (
  `rating_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rating_item_id` int(11) unsigned NOT NULL,
  `rating_uid` int(11) unsigned NOT NULL DEFAULT '0',
  `rating_ip` varchar(45) NOT NULL DEFAULT '',
  `rating_score` tinyint(1) NOT NULL DEFAULT '0',
  `rating_created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rating_id`),
  KEY `rating_item_id` (`rating_item_id`),
  KEY `rating_uid` (`rating_uid`),
  UNIQUE KEY `unique_member_vote` (`rating_item_id`,`rating_uid`),
  KEY `rating_ip` (`rating_ip`)
) ENGINE=InnoDB;