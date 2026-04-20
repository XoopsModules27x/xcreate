-- ============================================================
-- Xcreate SEO Meta Alanları - Güncelleme Scripti
-- Eren Yumak tarafından kodlanmıştır — Aymak
-- ============================================================

-- xcreate_items tablosuna SEO alanları ekle
ALTER TABLE `xcreate_items`
  ADD COLUMN IF NOT EXISTS `item_meta_title`       VARCHAR(160)  DEFAULT NULL AFTER `item_slug`,
  ADD COLUMN IF NOT EXISTS `item_meta_description` VARCHAR(320)  DEFAULT NULL AFTER `item_meta_title`,
  ADD COLUMN IF NOT EXISTS `item_meta_keywords`    VARCHAR(255)  DEFAULT NULL AFTER `item_meta_description`,
  ADD COLUMN IF NOT EXISTS `item_og_image`         VARCHAR(255)  DEFAULT NULL AFTER `item_meta_keywords`,
  ADD COLUMN IF NOT EXISTS `item_noindex`          TINYINT(1)    NOT NULL DEFAULT '0' AFTER `item_og_image`,
  ADD COLUMN IF NOT EXISTS `item_canonical`        VARCHAR(500)  DEFAULT NULL AFTER `item_noindex`;

-- xcreate_categories tablosuna SEO alanları ekle
ALTER TABLE `xcreate_categories`
  ADD COLUMN IF NOT EXISTS `cat_meta_title`        VARCHAR(160)  DEFAULT NULL AFTER `cat_slug`,
  ADD COLUMN IF NOT EXISTS `cat_meta_description`  VARCHAR(320)  DEFAULT NULL AFTER `cat_meta_title`,
  ADD COLUMN IF NOT EXISTS `cat_meta_keywords`     VARCHAR(255)  DEFAULT NULL AFTER `cat_meta_description`,
  ADD COLUMN IF NOT EXISTS `cat_og_image`          VARCHAR(255)  DEFAULT NULL AFTER `cat_meta_keywords`,
  ADD COLUMN IF NOT EXISTS `cat_noindex`           TINYINT(1)    NOT NULL DEFAULT '0' AFTER `cat_og_image`;
