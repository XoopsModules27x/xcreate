-- Conditional Fields özelliği için alan bağımlılık kolonu
ALTER TABLE `xcreate_fields` 
ADD COLUMN `field_condition` TEXT DEFAULT NULL COMMENT 'JSON: {"field_id":5,"operator":"==","value":"Kiralık"}' 
AFTER `field_repeatable`;
