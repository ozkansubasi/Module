# Ticker Module - Quick Start Guide (Updated)

## 📋 Genel Bakış

**Ticker Info**: Bölgeler hakkında ilginç bilgiler, tarihi olaylar, ünlü kişiler, numizmatik terimler ve daha fazlası!

**Yeni Yapı**: Sadece darphane isimleri değil, çok daha esnek içerik sistemi:
- 🏛️ Antik şehirler ve özellikleri
- 👤 Ünlü kişiler (Aristoteles, Strabon, vb.)
- 💰 Numizmatik terimler (Tetradrachm, Elektron, vb.)
- 🎭 Mitolojik öğeler (Zeus, Herakles, vb.)
- 📚 Tarihi olaylar
- 🏺 Arkeolojik keşifler

---

## 🎯 Adım Adım Kurulum

### 1️⃣ Custom Fields Oluştur (5-10 dakika)

**Dosya**: `01-custom-fields-setup-UPDATED.md`

**3 Field oluşturun**:
- ✅ `fact_title` (Text) - Kısa başlık (örn: "Assos", "Aristoteles", "Elektron")
- ✅ `fact_description` (Textarea) - Açıklama (1-2 cümle)
- ✅ `region_code` (List) - Bölge kodu (filtreleme için)

**Konum**: Content → Fields

**Önemli**: Field ID'lerini not alın!

---

### 2️⃣ Kategori Oluştur (2 dakika)

**Konum**: Content → Categories

**Yeni Kategori**:
- Başlık: `Ticker Info`
- Alias: `ticker-info`
- Status: Published
- Description: `Bölgeler hakkında ilginç bilgiler, tarihi olaylar ve ilgi çekici gerçekler`

**Önemli**: Category ID'yi not alın!

---

### 3️⃣ CSV Dosyasını Hazırla (5 dakika)

**Dosya**: `02-ticker-content-DIVERSE.csv` (ÖNERİLEN - 55+ çeşitli içerik)

**İçerik Türleri**:
- Antik şehirler (Assos, Ephesos, Pergamon)
- Ünlü kişiler (Aristoteles, Diogenes, Strabon)
- Numizmatik terimler (Tetradrachm, Stater, Drahmi)
- Mitolojik öğeler (Zeus, Athena, Herakles)
- Tarihi olaylar (M.Ö. 547 - Lydya'nın sonu)
- Özel bilgiler (İlk sikke, Parşömen icadı)

**Yapılacaklar**:
1. CSV dosyasını aç
2. `CATEGORY_ID` → Kendi category ID'nizi yazın (örn: `45`)
3. Dosyayı UTF-8 encoding ile kaydet

---

### 4️⃣ CSVI Import Template Oluştur (10 dakika)

**Field Mapping** (Yeni yapı):
- `title` → Title
- `alias` → Alias
- `catid` → Category
- `state` → Published
- `introtext` → Intro Text
- `fact_title` → Custom field: fact_title
- `fact_description` → Custom field: fact_description
- `region_code` → Custom field: region_code

---

### 5️⃣ Import Et (5 dakika)

1. **Test Import (Dry Run)**:
   - Components → CSVI Pro → Import → New
   - CSV dosyasını yükle: `02-ticker-content-DIVERSE.csv`
   - Template seç
   - Dry run: **Yes** ✅
   - 55+ article import edilecek

2. **Gerçek Import**:
   - Dry run: **No** ❌
   - Start Import

---

## 📊 İçerik Özeti (55+ Çeşitli Bilgi)

### 🏛️ Antik Şehirler (15+)
- Assos, Ephesos, Pergamon, Sardeis
- Miletos, Smyrna, Phokaia, Knidos
- Xanthos, Myra, Tarsos, ve daha fazlası

### 👤 Ünlü Kişiler (8+)
- Aristoteles (Assos'ta yaşadı)
- Diogenes (Sinope'li filozof)
- Strabon (Amaseia'lı coğrafyacı)
- Homer (Smyrna)
- Apollonios (Tyana)
- Aziz Paulus (Tarsos)
- Aziz Nikolaos (Myra)

### 💰 Numizmatik Terimler (10+)
- Elektron (altın-gümüş alaşımı)
- Tetradrachm (4 drahmilik sikke)
- Stater (temel para birimi)
- Drahmi, Obol
- İncuse tekniği
- Magistrat isimleri

### 🎭 Mitolojik Öğeler (12+)
- Zeus, Athena, Apollon
- Herakles, Artemis, Aphrodite
- Pegasos, Kentaur, Gorgon
- Triskeles, Quadriga

### 📚 Özel Bilgiler (10+)
- Dünyanın ilk sikkesi (Sardeis)
- Parşömen icadı (Pergamon)
- Yedi Harika (Artemis Tapınağı, Mausoleum)
- Nuh'un gemisi sikkeleri (Apameia)
- Pluto Kapısı (Hierapolis)

---

## 🎨 Ticker Görünüm Örnekleri

### Şehir Bilgisi
```
Assos: Athena Tapınağı ile ünlü, filozof Aristoteles'in bir süre yaşadığı liman kentidir.
```

### Kişi Bilgisi
```
Aristoteles: Assos'ta 3 yıl yaşadı ve "Politika" eserinin bir bölümünü burada yazdı.
```

### Numizmatik Terim
```
Elektron: Doğal altın-gümüş alaşımı, Lidyalılar tarafından ilk sikkelerin basımında kullanıldı.
```

### Mitolojik Öğe
```
Zeus: Tanrıların babası, sikkelerde en sık betimlenen tanrıdır.
```

### Tarihi Olay
```
M.Ö. 547: Pers Kralı Kyros, Sardeis'i ele geçirdi ve Lydya Krallığı sona erdi.
```

---

## 🔄 Eski Yapıdan Geçiş

**Backward Compatibility**: Kod hem eski hem yeni yapıyı destekliyor!

| Eski Field | Yeni Field | Not |
|------------|------------|-----|
| ancient_name | fact_title | Kısa başlık |
| modern_name | fact_description | Açıklama |
| region_code | region_code | Değişmedi ✅ |

**Display Format**:
- **Eski**: `Ancient Name → Modern Name`
- **Yeni**: `Fact Title: Fact Description`

---

## ✅ Checklist

### Hazırlık
- [ ] CSVI Pro installed
- [ ] Joomla admin access
- [ ] UTF-8 compatible CSV editor

### Custom Fields
- [ ] fact_title field created (Text)
- [ ] fact_description field created (Textarea)
- [ ] region_code field created (List)
- [ ] Field IDs noted

### Category
- [ ] "Ticker Info" category created (NOT "Darphane İsimleri")
- [ ] Category ID noted
- [ ] Fields assigned to category

### CSV
- [ ] `02-ticker-content-DIVERSE.csv` downloaded
- [ ] CATEGORY_ID replaced with actual ID
- [ ] File saved as UTF-8

### Import
- [ ] CSVI template created with new field names
- [ ] Field mappings: fact_title, fact_description, region_code
- [ ] Test import successful (55+ items)
- [ ] Real import completed

### Verification
- [ ] 55+ articles imported
- [ ] Custom fields filled correctly
- [ ] Various content types present
- [ ] Cache cleared

---

## 🆘 Önemli Değişiklikler

### ⚠️ Field İsimleri Değişti!

**ESKI yapı** (artık kullanılmıyor):
- ❌ `ancient_name` → Modern name
- ❌ `modern_name` → Location

**YENİ yapı** (esnek içerik):
- ✅ `fact_title` → Any short title (city, person, term)
- ✅ `fact_description` → Interesting fact (1-2 sentences)

### 📂 Kategori Adı Değişti!

- ❌ ESKI: "Darphane İsimleri" (sadece mint cities)
- ✅ YENİ: "Ticker Info" (any interesting facts)

### 🔄 Backward Compatibility

Kod **hem eski hem yeni** yapıyı destekliyor:
```php
// Önce yeni field'ları dene
$factTitle = $customFields['fact_title']
    ?? $customFields['ancient_name']  // Eski format fallback
    ?? $item->title;                   // Son fallback
```

---

## 📞 Test ve Doğrulama

### API Test (Yeni Format)
```bash
curl "https://www.numistr.org/api/index.php/v1/ticker?limit=5"
```

**Beklenen Response**:
```json
{
  "data": {
    "items": [
      {
        "id": 123,
        "fact_title": "Assos",
        "fact_description": "Athena Tapınağı ile ünlü...",
        "region_code": "troas-coins",
        "full_text": "Assos: Athena Tapınağı ile ünlü..."
      }
    ]
  }
}
```

### Ticker Display Test

Modül çıktısı şu formatta olmalı:
```
Assos: Athena Tapınağı ile ünlü, filozof Aristoteles'in yaşadığı liman kenti.
Elektron: Doğal altın-gümüş alaşımı, ilk sikkelerin yapıldığı metal.
Aristoteles: Assos'ta 3 yıl yaşadı ve "Politika" eserini yazdı.
```

---

## 🎯 Sonraki Adımlar

### 1. Daha Fazla İçerik Ekle

**Kolayca genişletilebilir kategoriler**:
- 🏺 Arkeolojik keşifler
- 🎨 Sanat eserleri
- ⚔️ Savaşlar ve zafeler
- 🏛️ Mimari yapılar
- 📜 Yazıtlar ve belgeler
- 🎭 Festivaller ve kutlamalar

### 2. Bölge Bazlı İçerik

Her bölge için özel bilgiler:
- Lydia: Karun, ilk sikke, elektron
- Ionia: Filozoflar, bilim, kolonizasyon
- Mysia: Pergamon, kütüphane, parşömen
- Troas: Homer, Assos, Aristoteles

### 3. Tematik Koleksiyonlar

Farklı kategoriler oluştur:
- "Darphane İsimleri" (eski format)
- "Ünlü Filozoflar"
- "Numizmatik Terimler"
- "Mitolojik Semboller"
- "Yedi Harika"

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

**Toplam**: Yaklaşık **40-45 dakika**

---

## 📚 Dosya Referansları

```
import/
├── 00-QUICK-START-UPDATED.md           # ← Bu dosya
├── 01-custom-fields-setup-UPDATED.md   # Yeni field yapısı
├── 02-ticker-content-DIVERSE.csv       # 55+ çeşitli içerik
└── 03-csvi-import-guide.md             # CSVI rehberi (güncellenecek)
```

---

**Son Güncelleme**: 2025-10-29
**Versiyon**: 2.0.0 (Esnek içerik sistemi)
**Durum**: Production Ready ✅
