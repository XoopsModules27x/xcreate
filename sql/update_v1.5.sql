-- CustomFields Module Update v1.5
-- Kategori özel template desteği

-- Add cat_template field to categories table
ALTER TABLE `xcreate_categories` 
ADD COLUMN `cat_template` varchar(255) DEFAULT NULL AFTER `cat_image`;
