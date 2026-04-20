# Xcreate - Dynamic Content Creation Module for XOOPS

**Version:** 1.51  
**Author:** Eren - Aymak  
**License:** GPL  
**XOOPS:** 2.5.11+  
**PHP:** 7.4 - 8.2

---

## 🎯 Xcreate Nedir?

Xcreate, XOOPS için geliştirilmiş profesyonel bir içerik yönetim modülüdür. Kategori bazlı dinamik özel alanlar ile esnek içerik yapıları oluşturmanızı sağlar.

### Ana Özellikler

✅ **Dinamik İçerik Yapısı** - Her kategori için farklı alan setleri  
✅ **14 Farklı Alan Tipi** - Text, textarea, select, checkbox, radio, date, file, image ve daha fazlası  
✅ **Kategori Hiyerarşisi** - Sınırsız alt kategori desteği  
✅ **Özel Template'ler** - Her kategori için özel görünüm  
✅ **Tekrarlanabilir Alanlar** - Birden fazla değer girişi  
✅ **Dosya Yönetimi** - Resim ve dosya yükleme sistemi  
✅ **Multi-language** - Türkçe ve İngilizce dil desteği  
✅ **Kullanıcı İzinleri** - Grup bazlı yetkilendirme  

---

## 🚀 Hızlı Kurulum

### 1. Modülü Yükleyin
```bash
# ZIP dosyasını XOOPS ana dizinine açın
unzip xcreate.zip
mv xcreate modules/

# İzinleri ayarlayın
chmod -R 755 modules/xcreate
```

### 2. Modülü Kurun
1. XOOPS Admin paneline girin
2. **Sistem > Modüller** bölümüne gidin
3. **Xcreate** modülünü bulun
4. **Kur** butonuna tıklayın

### 3. İlk Yapılandırma
1. **Xcreate > Kategoriler** - İlk kategorinizi oluşturun
2. **Xcreate > Özel Alanlar** - Kategori için alanlar tanımlayın
3. **Xcreate > İçerikler** - İçerik eklemeye başlayın

---

## 📋 Alan Tipleri

| Alan Tipi | Açıklama | Kullanım Alanı |
|-----------|----------|----------------|
| **text** | Tek satır metin | Başlık, ad, telefon |
| **textarea** | Çok satırlı metin | Açıklama, adres |
| **editor** | HTML editör | Zengin içerik |
| **select** | Açılır liste | Seçim yapma |
| **checkbox** | Çoklu seçim | Birden fazla seçenek |
| **radio** | Tek seçim | Bir seçenek |
| **date** | Tarih seçici | Doğum tarihi, tarih |
| **datetime** | Tarih+Saat | Etkinlik zamanı |
| **number** | Sayı girişi | Fiyat, miktar |
| **email** | E-posta | İletişim |
| **url** | Web adresi | Link |
| **color** | Renk seçici | Tema rengi |
| **image** | Resim yükleme | Görsel içerik |
| **file** | Dosya yükleme | Döküman, PDF |

---

## 💡 Kullanım Örnekleri

### Örnek 1: Gayrimenkul İlanları

**Kategori:** Satılık Daireler

**Özel Alanlar:**
- Fiyat (number)
- Metrekare (number)
- Oda Sayısı (select: 1+1, 2+1, 3+1, 4+1)
- Kat (number)
- Isıtma (select: Kombi, Merkezi, Klima)
- Cephe (radio: Kuzey, Güney, Doğu, Batı)
- Özellikler (checkbox: Asansör, Otopark, Balkon, Güvenlik)
- Fotoğraflar (image - tekrarlanabilir)

### Örnek 2: İş İlanları

**Kategori:** Yazılım

**Özel Alanlar:**
- Pozisyon (text)
- Deneyim (select: 0-2 yıl, 2-5 yıl, 5+ yıl)
- Maaş Aralığı (text)
- Şirket (text)
- Çalışma Şekli (radio: Ofis, Uzaktan, Hibrit)
- Yetenekler (checkbox - tekrarlanabilir)
- Başvuru Tarihi (date)
- Logo (image)

### Örnek 3: Ürün Kataloğu

**Kategori:** Elektronik

**Özel Alanlar:**
- Marka (select)
- Model (text)
- Fiyat (number)
- Stok (number)
- Renk Seçenekleri (checkbox)
- Garanti Süresi (number)
- Ürün Görselleri (image - tekrarlanabilir)
- Teknik Döküman (file)

---

## 🎨 Template Sistemi

### Varsayılan Template'ler

Xcreate aşağıdaki template dosyalarını kullanır:
- `xcreate_index.tpl` - Ana sayfa listesi
- `xcreate_item.tpl` - İçerik detay sayfası
- `xcreate_submit.tpl` - İçerik gönderme formu

### Özel Template Oluşturma

Her kategori için özel template tanımlayabilirsiniz:

1. Kategori düzenlerken "Özel Template" alanına template adını yazın (örn: `gayrimenkul`)
2. Template otomatik olarak `templates/gayrimenkul.tpl` konumunda oluşturulur
3. Template'i özelleştirin

**Örnek Template Kodu:**
```smarty
<div class="xcreate-item">
    <h1>{$item.title}</h1>
    
    <div class="item-description">
        {$item.description}
    </div>
    
    {if $custom_fields}
    <div class="custom-fields">
        {foreach item=field from=$custom_fields}
        <div class="field-group">
            <label>{$field.label}:</label>
            <div class="field-values">
                {foreach item=value from=$field.values}
                <span class="field-value">{$value}</span>
                {/foreach}
            </div>
        </div>
        {/foreach}
    </div>
    {/if}
</div>
```

---

## 🔧 Tekrarlanabilir Alanlar

Tekrarlanabilir alanlar, kullanıcıların aynı alana birden fazla değer girmesini sağlar.

### Kullanım Alanları:
- Birden fazla telefon numarası
- Çoklu resim yükleme
- Birden fazla e-posta adresi
- Çoklu sosyal medya linki

### Nasıl Etkinleştirilir:
1. Özel alan oluştururken "Tekrarlanabilir" kutucuğunu işaretleyin
2. Kullanıcı formda "+ Ekle" butonuyla yeni alan ekleyebilir
3. Template'de tüm değerler array olarak gelir

**Template'de Kullanım:**
```smarty
{foreach item=phone from=$field.values}
<a href="tel:{$phone}">{$phone}</a>
{/foreach}
```

---

## 🔐 İzinler ve Güvenlik

### Kullanıcı Yetkileri
- **Görüntüleme:** Hangi kategorileri görebilir
- **İçerik Gönderme:** Hangi kategorilere içerik ekleyebilir
- **Düzenleme:** Kendi içeriklerini düzenleyebilir
- **Silme:** Kendi içeriklerini silebilir

### Admin Yetkileri
- Tüm kategorilere tam erişim
- Tüm içerikleri yönetme
- Özel alan tanımlama
- İzin ayarları

### Güvenlik Önlemleri
✅ SQL Injection koruması  
✅ XSS (Cross-Site Scripting) koruması  
✅ CSRF token kontrolü  
✅ Dosya yükleme güvenliği  
✅ Extension kontrolü  
✅ Boyut limiti  

---

## 📊 Veritabanı Yapısı

### Tablolar

**xcreate_categories**
- Kategori bilgileri
- Hiyerarşik yapı (parent_id)
- Özel template bilgisi

**xcreate_fields**
- Özel alan tanımları
- Alan tipi ve özellikleri
- Sıralama bilgisi

**xcreate_field_options**
- Select, checkbox, radio seçenekleri

**xcreate_items**
- İçerik kayıtları
- Kategori ilişkisi
- Durum bilgisi

**xcreate_field_values**
- Alan değerleri
- Çoklu değer desteği
- Dosya bilgileri

---

## 🎛️ Modül Ayarları

Admin panelinden yapılandırılabilir ayarlar:

| Ayar | Açıklama | Varsayılan |
|------|----------|------------|
| **Sayfa Başına İçerik** | Liste sayfalarında gösterilecek içerik sayısı | 10 |
| **Kullanıcı Gönderimi** | Kullanıcıların içerik göndermesine izin ver | Evet |
| **Maksimum Dosya Boyutu** | Yüklenebilecek maksimum dosya boyutu (KB) | 2048 |
| **İzin Verilen Uzantılar** | Yüklenebilecek dosya tipleri | jpg,jpeg,png,gif,pdf,doc,docx |

---

## 🔄 Güncelleme

### v1.51'e Güncelleme (Önceki Customfields'den)

1. **Yedek alın:**
```bash
cp -r modules/customfields modules/customfields.backup
mysqldump -u root -p xoops_db > backup.sql
```

2. **Dosyaları güncelleyin:**
```bash
unzip xcreate.zip
rm -rf modules/customfields
mv xcreate modules/
```

3. **Veritabanını güncelleyin:**
```sql
-- Tablo isimlerini değiştirin
RENAME TABLE customfields_categories TO xcreate_categories;
RENAME TABLE customfields_fields TO xcreate_fields;
RENAME TABLE customfields_field_options TO xcreate_field_options;
RENAME TABLE customfields_items TO xcreate_items;
RENAME TABLE customfields_field_values TO xcreate_field_values;
```

4. **XOOPS'ta modülü güncelleyin:**
   - Admin > Modüller > Xcreate > Güncelle

---

## 🐛 Sorun Giderme

### Silme İşlemi Çalışmıyor

**Çözüm:** v1.51'de düzeltildi. Eğer hala sorun varsa:
```php
// Debug mode açın
define('XOOPS_DEBUG_MODE', 1);

// Veritabanı hatalarını kontrol edin
echo $xoopsDB->error();
```

### Constant Already Defined Hatası

**Çözüm:** v1.51'de düzeltildi. Cache temizleyin:
```bash
rm -rf cache/*
rm -rf templates_c/*
```

### Dosya Yükleme Çalışmıyor

**Kontrol listesi:**
- PHP upload_max_filesize yeterli mi?
- uploads/xcreate klasörü var mı?
- İzinler doğru mu? (755)
- Dosya uzantısı izin verilen listede mi?

---

## 📚 API ve Entegrasyon

### Smarty Plugin Kullanımı

Template'lerde Xcreate verilerini göstermek için:

```smarty
{* Kategoriye ait son 5 içeriği göster *}
{xcreate cat_id=1 limit=5 assign=items}

{foreach item=item from=$items}
    <h3>{$item.title}</h3>
    <p>{$item.description}</p>
{/foreach}
```

### PHP'de Kullanım

```php
// Handler'ı yükle
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
$itemHandler = new XcreateItemHandler($xoopsDB);

// Son içerikleri getir
$items = $itemHandler->getRecentItems(10);

foreach ($items as $item) {
    echo $item->getVar('item_title');
}
```

---

## 📞 Destek ve İletişim

### Dokümantasyon
- **README.md** - Bu dosya
- **CHANGELOG.md** - Versiyon geçmişi
- XOOPS forumları

### Hata Bildirimi
Sorun yaşarsanız:
1. XOOPS debug mode'u açın
2. PHP error log'larını kontrol edin
3. Veritabanı hatalarını inceleyin
4. Sorunu detaylı açıklayarak bildirin

---

## ✅ Özellik Listesi

### ✅ Tamamlanan Özellikler
- [x] Kategori yönetimi (hiyerarşik)
- [x] 14 farklı alan tipi
- [x] Tekrarlanabilir alanlar
- [x] Özel template sistemi
- [x] Dosya ve resim yükleme
- [x] Multi-language desteği
- [x] Kullanıcı izinleri
- [x] Admin paneli
- [x] Smarty plugin
- [x] Silme işlemleri (v1.51)
- [x] Hata düzeltmeleri (v1.51)

### 🔜 Gelecek Özellikler (v1.6)
- [ ] REST API
- [ ] Import/Export
- [ ] Toplu işlemler
- [ ] Alan validasyonu geliştirmeleri
- [ ] Icon setleri
- [ ] Daha fazla alan tipi

---

## 📜 Lisans

Bu modül GPL (GNU General Public License) lisansı altında dağıtılmaktadır.

---

## 🙏 Teşekkürler

- XOOPS topluluğuna
- Modülü test eden kullanıcılara
- Geri bildirim sağlayan herkese

---

**Xcreate ile profesyonel içerik yönetimi! 🚀**

**Version:** 1.51  
**Date:** 25 Kasım 2024  
**Developer:** Eren - Aymak  
**Website:** https://aymak.com.tr
