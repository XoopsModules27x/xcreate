-- Xcreate Lookup Field özelliği için güncelleme
-- Bu SQL'i çalıştırarak xcreate_fields tablosuna lookup kolonlarını ekleyiniz.

ALTER TABLE `xcreate_fields`
    ADD COLUMN `field_lookup_enabled` TINYINT(1) NOT NULL DEFAULT '0' AFTER `field_condition`,
    ADD COLUMN `field_lookup_cat_id`  INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `field_lookup_enabled`,
    ADD COLUMN `field_lookup_field_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `field_lookup_cat_id`;
