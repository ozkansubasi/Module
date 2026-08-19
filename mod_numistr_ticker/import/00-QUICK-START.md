# Ticker Module - Quick Start Guide

## 📋 Overview

Bu dizinde ticker modülü için gerekli tüm import dosyaları ve rehberler bulunmaktadır.

---

## 🎯 Adım Adım Kurulum

### 1️⃣ Custom Fields Oluştur (5-10 dakika)

**Dosya**: `01-custom-fields-setup.md`

Joomla'da 3 custom field oluşturmanız gerekiyor:
- ✅ `ancient_name` (Text) - Antik şehir adı
- ✅ `modern_name` (Text) - Modern yer adı
- ✅ `region_code` (List) - Bölge kodu

**Konum**: Content → Fields

**Önemli**: Field ID'lerini not alın!

---

### 2️⃣ Kategori Oluştur (2 dakika)

**Konum**: Content → Categories

**Yeni Kategori**:
- Başlık: `Darphane İsimleri`
- Alias: `darphane-isimleri`
- Status: Published

**Önemli**: Category ID'yi not alın!

---

### 3️⃣ CSV Dosyasını Hazırla (5 dakika)

**Seçenek A** (Basit): `02-ticker-content-simple.csv`
- Daha basit kolon yapısı
- Custom field isimleri doğrudan kullanılıyor

**Seçenek B** (Detaylı): `02-ticker-content-sample.csv`
- Author ID dahil
- Daha fazla kontrol

**Yapılacaklar**:
1. CSV dosyasını aç
2. `CATEGORY_ID` → Kendi category ID'nizi yazın (örn: `45`)
3. `AUTHOR_ID` (varsa) → Kendi user ID'nizi yazın (örn: `628`)
4. Dosyayı UTF-8 encoding ile kaydet

**Not**: Excel kullanıyorsanız "Save As" → More Options → Encoding: UTF-8

---

### 4️⃣ CSVI Import Template Oluştur (10 dakika)

**Dosya**: `03-csvi-import-guide.md` (Detaylı rehber)

**Kısa Özet**:
1. Components → CSVI Pro → Templates → New
2. Template adı: `Ticker Content Import`
3. Action: Import, Component: Content, Operation: Article
4. Field delimiter: `,` | Encoding: UTF-8 | Skip first line: Yes
5. Field mapping yap (title, alias, catid, custom fields)
6. Save

---

### 5️⃣ Import Et (5 dakika)

1. **Test Import (Dry Run)**:
   - Components → CSVI Pro → Import → New
   - CSV dosyasını yükle
   - Template seç: `Ticker Content Import`
   - Dry run: **Yes** ✅
   - Start Import
   - Hataları kontrol et

2. **Gerçek Import**:
   - Aynı işlemi tekrarla
   - Dry run: **No** ❌
   - Start Import
   - 62 article import edilecek

---

### 6️⃣ Doğrulama (5 dakika)

**Article Kontrolü**:
- Content → Articles
- Filter: Category = "Darphane İsimleri"
- 62 article görmelisiniz

**Bir Article Aç**:
- ✅ Title dolu
- ✅ Intro text dolu
- ✅ Custom field: Ancient Name dolu
- ✅ Custom field: Modern Name dolu
- ✅ Custom field: Region Code seçili

**Cache Temizle**:
- System → Clear Cache → Delete All

---

## 📂 Dosya Yapısı

```
import/
├── 00-QUICK-START.md                    # ← Bu dosya (hızlı başlangıç)
├── 01-custom-fields-setup.md            # Custom field oluşturma rehberi
├── 02-ticker-content-simple.csv         # BASİT format (önerilen)
├── 02-ticker-content-sample.csv         # DETAYLI format (alternatif)
└── 03-csvi-import-guide.md              # Detaylı CSVI import rehberi
```

---

## 🔢 İçerik Özeti

CSV dosyasında **62 darphane şehri** var:

| Bölge | Şehir Sayısı | Örnekler |
|-------|-------------|----------|
| Lydia | 1 | Sardeis |
| Ionia | 11 | Ephesos, Miletos, Smyrna |
| Mysia | 2 | Pergamon, Kyzikos |
| Troas | 1 | Alexandria Troas |
| Aeolis | 5 | Kyme, Mytilene |
| Caria | 6 | Halikarnassos, Knidos |
| Lycia | 5 | Xanthos, Patara, Myra |
| Pamphylia | 4 | Side, Perge, Aspendos |
| Cilicia | 5 | Tarsos, Soloi |
| Pisidia | 3 | Sagalassos, Termessos |
| Phrygia | 4 | Apameia, Laodikeia |
| Galatia | 3 | Ankyra, Pessinos |
| Cappadocia | 3 | Kaisareia, Tyana |
| Pontus | 4 | Sinope, Amisos |
| Bithynia | 5 | Nikaia, Nikomedia |

**Toplam**: 62 antik darphane şehri

---

## ✅ Checklist

### Hazırlık
- [ ] CSVI Pro installed (https://www.csvimproved.com/)
- [ ] Joomla admin access
- [ ] CSV editor (Excel, VS Code, etc.)

### Custom Fields
- [ ] ancient_name field created
- [ ] modern_name field created
- [ ] region_code field created
- [ ] Field IDs noted

### Category
- [ ] "Darphane İsimleri" category created
- [ ] Category ID noted
- [ ] Fields assigned to category

### CSV
- [ ] CSV file downloaded
- [ ] CATEGORY_ID replaced with actual ID
- [ ] AUTHOR_ID replaced (if using detailed version)
- [ ] File saved as UTF-8

### Import
- [ ] CSVI template created
- [ ] Field mappings configured
- [ ] Test import (dry run) successful
- [ ] Real import completed
- [ ] 62 articles imported

### Verification
- [ ] Articles visible in admin
- [ ] Custom fields filled correctly
- [ ] Cache cleared
- [ ] Module displays data

---

## 🆘 Sorun Giderme

### CSV açılmıyor / Türkçe karakterler bozuk
**Çözüm**: UTF-8 encoding ile kaydedin
- Excel: Save As → More Options → Encoding: UTF-8
- VS Code: Sağ alt köşede encoding seç

### Custom fields import olmuyor
**Çözüm 1**: Field isimlerini kontrol edin
- Joomla'da: Content → Fields → "Name" sütunu
- CSV'de: Tam olarak aynı isim olmalı

**Çözüm 2**: Field ID kullanın
- CSV kolonlarını `custom_field_123` formatına çevirin
- `123` yerine gerçek field ID'yi yazın

### Articles yanlış kategoride
**Çözüm**: CSV'deki `catid` değerini kontrol edin
- Category ID doğru mu?
- Numeric değer mi? (alias değil)

### Import hata veriyor
**Çözüm**: CSVI log'larını kontrol edin
- Components → CSVI Pro → Logs
- Hata mesajlarını okuyun
- Eksik required field var mı?

---

## 📞 Ek Kaynaklar

**CSVI Dokümantasyonu**:
- https://docs.csvimproved.com/

**Ticker Module Dokümantasyonu**:
- `Module/mod_numistr_ticker/README.md` (quick guide)
- `claudedocs/components/mod-numistr-ticker.md` (full documentation)

**Test Endpoint**:
```bash
# Import sonrası API test et
curl "https://www.numistr.org/api/index.php/v1/ticker?limit=5"
```

---

## ⏱️ Tahmini Süreler

| Adım | Süre | Toplam |
|------|------|--------|
| Custom fields oluştur | 10 dk | 10 dk |
| Kategori oluştur | 2 dk | 12 dk |
| CSV hazırla | 5 dk | 17 dk |
| CSVI template | 10 dk | 27 dk |
| Test import | 5 dk | 32 dk |
| Gerçek import | 5 dk | 37 dk |
| Doğrulama | 5 dk | **~42 dk** |

**Toplam**: Yaklaşık **40-45 dakika** (ilk defa yapıyorsanız)

---

## 🎉 Sonraki Adımlar

Import başarılı olduktan sonra:

1. **Modülü Kur**:
   - `Module/mod_numistr_ticker/` dizinini zip'le
   - Joomla'ya yükle ve publish et

2. **API Test Et**:
   ```bash
   curl "https://www.numistr.org/api/index.php/v1/ticker"
   ```

3. **Mobile App Entegrasyonu**:
   - Flutter TickerWidget ekle
   - API servisini bağla

4. **İçerik Ekle**:
   - Yeni darphane şehirleri
   - Diğer bölgesel içerikler
   - Tarihi olaylar, vs.

---

**Son Güncelleme**: 2025-10-29
**Versiyon**: 1.0.0
