-- Xcreate Module - Migration from Customfields to Xcreate
-- Version: 1.51
-- Date: 2024-11-25

-- ÖNEMLI: Bu SQL dosyası sadece mevcut CustomFields modülünden Xcreate'e geçiş yapıyorsanız kullanılmalıdır
-- YENİ KURULUM İÇİN BU DOSYAYI KULLANMAYIN!

-- =====================================================
-- TABLO İSİMLERİNİ DEĞİŞTİRME
-- =====================================================

-- Yedek tablolar oluştur (Opsiyonel - güvenlik için)
-- DROP TABLE IF EXISTS customfields_categories_backup;
-- CREATE TABLE customfields_categories_backup LIKE customfields_categories;
-- INSERT INTO customfields_categories_backup SELECT * FROM customfields_categories;

-- Tablo isimlerini değiştir
RENAME TABLE customfields_categories TO xcreate_categories;
RENAME TABLE customfields_fields TO xcreate_fields;
RENAME TABLE customfields_field_options TO xcreate_field_options;
RENAME TABLE customfields_items TO xcreate_items;
RENAME TABLE customfields_field_values TO xcreate_field_values;

-- =====================================================
-- XOOPS MODÜL KAYITLARINI GÜNCELLEME
-- =====================================================

-- Modül kaydını güncelle
UPDATE `#prefix#_modules` 
SET 
    dirname = 'xcreate',
    name = 'Xcreate'
WHERE dirname = 'customfields';

-- Template kayıtlarını güncelle
UPDATE `#prefix#_tplfile` 
SET 
    tpl_module = 'xcreate',
    tpl_file = REPLACE(tpl_file, 'customfields_', 'xcreate_')
WHERE tpl_module = 'customfields';

-- Config kayıtlarını güncelle
UPDATE `#prefix#_config` 
SET 
    conf_modid = (SELECT mid FROM `#prefix#_modules` WHERE dirname = 'xcreate')
WHERE conf_modid = (SELECT mid FROM `#prefix#_modules` WHERE dirname = 'customfields');

-- Block kayıtlarını güncelle
UPDATE `#prefix#_newblocks` 
SET 
    dirname = 'xcreate',
    func_file = REPLACE(func_file, 'customfields_blocks', 'xcreate_blocks'),
    show_func = REPLACE(show_func, 'b_customfields_', 'b_xcreate_'),
    edit_func = REPLACE(edit_func, 'b_customfields_', 'b_xcreate_'),
    template = REPLACE(template, 'customfields_', 'xcreate_')
WHERE dirname = 'customfields';

-- =====================================================
-- UPLOAD KLASÖRLERİNİ TAŞIMA (Manuel Yapılmalı)
-- =====================================================

-- Bu işlem manuel olarak yapılmalıdır:
-- mv uploads/customfields uploads/xcreate

-- =====================================================
-- VERİFİKASYON SORULARI
-- =====================================================

-- Tablo sayısını kontrol et (5 olmalı)
SELECT COUNT(*) as table_count 
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name LIKE 'xcreate_%';

-- Modül kaydını kontrol et
SELECT mid, name, dirname, version 
FROM `#prefix#_modules` 
WHERE dirname = 'xcreate';

-- Template kayıtlarını kontrol et
SELECT COUNT(*) as template_count 
FROM `#prefix#_tplfile` 
WHERE tpl_module = 'xcreate';

-- Block kayıtlarını kontrol et
SELECT COUNT(*) as block_count 
FROM `#prefix#_newblocks` 
WHERE dirname = 'xcreate';

-- =====================================================
-- NOTLAR
-- =====================================================

/*
ÖNEMLI NOTLAR:

1. Bu dosyayı çalıştırmadan önce MUTLAKA veritabanı yedeği alın:
   mysqldump -u root -p xoops_db > xcreate_migration_backup.sql

2. #prefix# değerini kendi XOOPS prefix'inizle değiştirin (genellikle 'xoops')

3. Upload klasörünü manuel taşıyın:
   mv uploads/customfields uploads/xcreate

4. Eski customfields modül dosyalarını silin:
   rm -rf modules/customfields

5. Xcreate modülünü yerleştirin:
   mv xcreate modules/

6. XOOPS Admin panelinden modülü güncelleyin:
   Admin > Modüller > Xcreate > Güncelle

7. Template cache'i temizleyin:
   rm -rf cache/*
   rm -rf templates_c/*

8. Tarayıcı cache'ini temizleyin (Ctrl+F5)

SORUN GİDERME:

Eğer migration sonrası sorun yaşarsanız:

1. Yedekten geri yükleyin:
   mysql -u root -p xoops_db < xcreate_migration_backup.sql

2. Tablo isimlerini manuel kontrol edin:
   SHOW TABLES LIKE 'xcreate_%';

3. Modül kaydını kontrol edin:
   SELECT * FROM xoops_modules WHERE dirname = 'xcreate';

4. Debug mode açın ve hataları kontrol edin:
   define('XOOPS_DEBUG_MODE', 1);

BAŞARILI MİGRASYON KONTROLÜ:

✓ 5 tablo xcreate_ prefix'i ile var
✓ xoops_modules tablosunda 'xcreate' kaydı var
✓ Template kayıtları güncellendi
✓ Block kayıtları güncellendi
✓ uploads/xcreate klasörü var
✓ modules/xcreate klasörü var
✓ Eski customfields klasörü silindi
✓ XOOPS admin panelde modül görünüyor
✓ Frontend'de içerikler görüntüleniyor

*/

-- =====================================================
-- ROLLBACK (İPTAL) SORULARI
-- =====================================================

-- Eğer geri almak isterseniz:
/*
RENAME TABLE xcreate_categories TO customfields_categories;
RENAME TABLE xcreate_fields TO customfields_fields;
RENAME TABLE xcreate_field_options TO customfields_field_options;
RENAME TABLE xcreate_items TO customfields_items;
RENAME TABLE xcreate_field_values TO customfields_field_values;

UPDATE xoops_modules SET dirname = 'customfields', name = 'Custom Fields' WHERE dirname = 'xcreate';
UPDATE xoops_tplfile SET tpl_module = 'customfields', tpl_file = REPLACE(tpl_file, 'xcreate_', 'customfields_') WHERE tpl_module = 'xcreate';
UPDATE xoops_newblocks SET dirname = 'customfields' WHERE dirname = 'xcreate';
*/
