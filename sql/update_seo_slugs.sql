-- SEO URL desteği için slug kolonları ekleme
-- xcreate modülü için

ALTER TABLE `xcreate_categories`
  ADD COLUMN `cat_slug` varchar(255) NOT NULL DEFAULT '' AFTER `cat_name`,
  ADD UNIQUE KEY `cat_slug` (`cat_slug`);

ALTER TABLE `xcreate_items`
  ADD COLUMN `item_slug` varchar(255) NOT NULL DEFAULT '' AFTER `item_title`,
  ADD UNIQUE KEY `item_slug` (`item_slug`);

-- Mevcut kategoriler için slug oluştur (Türkçe karakterler korunur, boşluk -> tire)
UPDATE `xcreate_categories` SET `cat_slug` = 
  LOWER(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    cat_name,
    'ğ','g'),'Ğ','g'),'ü','u'),'Ü','u'),'ş','s'),'Ş','s'),
    'ı','i'),'İ','i'),'ö','o'),'Ö','o'),'ç','c'),'Ç','c')
  )
WHERE cat_slug = '';

UPDATE `xcreate_categories` SET `cat_slug` = 
  REGEXP_REPLACE(
    REGEXP_REPLACE(TRIM(LOWER(cat_slug)), '[^a-z0-9\\-]', '-'),
    '-+', '-'
  )
WHERE cat_slug != '';

-- Mevcut içerikler için slug oluştur
UPDATE `xcreate_items` SET `item_slug` = 
  LOWER(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    item_title,
    'ğ','g'),'Ğ','g'),'ü','u'),'Ü','u'),'ş','s'),'Ş','s'),
    'ı','i'),'İ','i'),'ö','o'),'Ö','o'),'ç','c'),'Ç','c')
  )
WHERE item_slug = '';

UPDATE `xcreate_items` SET `item_slug` = 
  REGEXP_REPLACE(
    REGEXP_REPLACE(TRIM(LOWER(item_slug)), '[^a-z0-9\\-]', '-'),
    '-+', '-'
  )
WHERE item_slug != '';
