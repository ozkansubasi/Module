# Custom Fields Setup for Ticker Module (Updated)

## Genel Yapı - Esnek İçerik Sistemi

Ticker modülü artık sadece darphane isimleriyle sınırlı değil. Her bölgeyle ilgili **ilginç bilgiler** için kullanılabilir:

- 🏛️ Tarihi yerler ve özellikleri
- 🏺 Arkeolojik keşifler
- 👤 Ünlü kişiler ve yaşadıkları yerler
- 🎭 Mitolojik hikayeler
- 🏙️ Antik şehirler
- 💰 Darphane bilgileri
- 🎨 Sanat ve mimari eserler
- 📚 Felsefe ve bilim merkezleri

---

## Step 1: Create Custom Fields in Joomla

Navigate to: **Content → Fields** and create these 3 fields:

---

### Field 1: Fact Title (Kısa Başlık)

**Basic Settings**:
- Title: `Fact Title` / `Bilgi Başlığı`
- Name: `fact_title` (will auto-generate)
- Type: **Text**
- Label: `Fact Title` / `Bilgi Başlığı`
- Description: `Short title for the ticker item (e.g., city name, person name, term)`

**Options**:
- Default Value: (leave empty)
- Placeholder: `e.g., Assos, Aristoteles, Elektron`
- Size: `60`
- Max Length: `255`

**Publishing**:
- Status: **Published**
- Access: Public
- Language: All

**Permissions**:
- Default

**Purpose**: Ticker'da kalın/vurgulu gösterilecek ana başlık

---

### Field 2: Fact Description (Açıklama)

**Basic Settings**:
- Title: `Fact Description` / `Bilgi Açıklaması`
- Name: `fact_description` (will auto-generate)
- Type: **Textarea**
- Label: `Fact Description` / `Bilgi Açıklaması`
- Description: `Brief interesting fact (1-2 sentences)`

**Options**:
- Rows: `3`
- Columns: `60`
- Max Length: `500`
- Filter: `No filtering` (allow all HTML)

**Publishing**:
- Status: **Published**
- Access: Public
- Language: All

**Permissions**:
- Default

**Purpose**: Ticker'da başlıktan sonra gösterilecek açıklama

---

### Field 3: Region Code (Bölge Kodu) - UNCHANGED

**Basic Settings**:
- Title: `Region Code` / `Bölge Kodu`
- Name: `region_code` (will auto-generate)
- Type: **List**
- Label: `Region Code` / `Bölge Kodu`
- Description: `Coin region for filtering`

**Options**:
- Multiple: **No**
- List options (one per line):
```
lydia-coins|Lydia
ionia-coins|Ionia
mysia-coins|Mysia
troas-coins|Troas
aeolis-coins|Aeolis
caria-coins|Caria
lycia-coins|Lycia
pamphylia-coins|Pamphylia
cilicia-coins|Cilicia
pisidia-coins|Pisidia
phrygia-coins|Phrygia
galatia-coins|Galatia
cappadocia-coins|Cappadocia
pontus-coins|Pontus
bithynia-coins|Bithynia
general|Genel (Bölge bağımsız)
```

**Publishing**:
- Status: **Published**
- Access: Public
- Language: All

**Permissions**:
- Default

**Purpose**: Bölgeye göre filtreleme için

---

## Step 2: Get Field IDs

After creating fields, note their IDs:

1. Go to: **Content → Fields**
2. Hover over each field name
3. Note the ID from the URL (e.g., `...&id=123`)

**Record these IDs**:
- fact_title field ID: `_____`
- fact_description field ID: `_____`
- region_code field ID: `_____`

---

## Step 3: Create Category

Navigate to: **Content → Categories**

**Create New Category**:
- Title: `Ticker Info`
- Alias: `ticker-info` (will auto-generate)
- Parent: Root (or your preferred parent)
- Status: **Published**
- Access: Public
- Language: All
- Description: `Bölgeler hakkında ilginç bilgiler, tarihi olaylar ve ilgi çekici gerçekler`

**Note the Category ID**: `_____`

---

## Step 4: Field Assignment to Category

After creating the category:

1. Go back to: **Content → Fields**
2. Edit each field (fact_title, fact_description, region_code)
3. In **Field Assignment** tab:
   - Select: `Ticker Info` category
   - OR select: `All` to make available everywhere
4. Save & Close

---

## Verification

After setup, when you create a new article in "Ticker Info" category, you should see these custom fields in the article edit form:

- ✅ Fact Title (text input) - Kısa başlık
- ✅ Fact Description (textarea) - Açıklama
- ✅ Region Code (dropdown) - Bölge

---

## Field Structure Summary

| Field Name | Field Type | Required | Used For |
|------------|------------|----------|----------|
| fact_title | Text | Yes | Main heading in ticker (bold) |
| fact_description | Textarea | Yes | Description text in ticker |
| region_code | List | Yes | Region filtering |

---

## Ticker Display Formats

### Format 1: Şehir + Özellik
```
Assos: Athena Tapınağı ile ünlü, filozof Aristoteles'in bir süre yaşadığı liman kenti.
```
- fact_title: `Assos`
- fact_description: `Athena Tapınağı ile ünlü, filozof Aristoteles'in bir süre yaşadığı, Çanakkale'nin Ayvacık ilçesine bağlı Behramkale köyünde yer alan liman kentidir.`

### Format 2: Darphane Bilgisi
```
Sardeis: Lidya Krallığı'nın başkenti, dünyanın ilk sikke darphanesinin bulunduğu şehir.
```
- fact_title: `Sardeis`
- fact_description: `Lidya Krallığı'nın başkenti, dünyanın ilk sikke darphanesinin bulunduğu şehir. Günümüz Manisa ilinin Sart köyünde.`

### Format 3: Kişi Bilgisi
```
Aristoteles: Assos'ta 3 yıl yaşadı, "Politika" eserinin bir bölümünü burada yazdı.
```
- fact_title: `Aristoteles`
- fact_description: `Assos'ta 3 yıl yaşadı ve "Politika" eserinin bir bölümünü burada yazdı. Öğrencileriyle birlikte doğa gözlemleri yaptı.`

### Format 4: Numizmatik Terim
```
Elektron: Doğal altın-gümüş alaşımı, ilk sikkelerin yapıldığı metal.
```
- fact_title: `Elektron`
- fact_description: `Doğal altın-gümüş alaşımı, ilk sikkelerin yapıldığı귀metal. Lydialılar tarafından sikke basımında kullanıldı.`

### Format 5: Tarihi Olay
```
M.Ö. 547: Pers Kralı Kyros, Sardeis'i ele geçirdi ve Lydya Krallığı sona erdi.
```
- fact_title: `M.Ö. 547`
- fact_description: `Pers Kralı Kyros, Sardeis'i ele geçirdi ve Lydya Krallığı sona erdi. Sikke basımı Pers kontrolüne geçti.`

---

## Content Categories (İçerik Türleri)

Ticker Info kategorisinde şu tür içerikler olabilir:

### 🏛️ Antik Şehirler
- Önemli özellikleri
- Modern konumları
- Tarihi önemi

### 💰 Darphane Bilgileri
- Basılan sikke tipleri
- Darphane dönemi
- Özel özellikler

### 👤 Ünlü Kişiler
- Filozoflar, bilim insanları
- Krallar, yöneticiler
- Sanatçılar, yazarlar

### 🎭 Mitolojik Öğeler
- Tanrı ve tanrıça hikayeleri
- Efsaneler
- Mitolojik yaratıklar

### 🏺 Arkeolojik Keşifler
- Önemli buluntular
- Kazı çalışmaları
- Müze eserleri

### 📚 Numizmatik Terimler
- Sikke terimleri
- Metaller ve alaşımlar
- Teknik bilgiler

### 🌍 Coğrafi Bilgiler
- Antik ve modern isimler
- Konumlar
- Bölge özellikleri

---

## Template Helper Methods Update

Helper.php dosyasındaki `processItem()` metodu yeni field'larla çalışacak şekilde güncellendi:

```php
private function processItem($item)
{
    $customFields = $this->getCustomFields($item->id);

    // Yeni field yapısı
    $factTitle = $customFields['fact_title'] ?? $item->title;
    $factDescription = $customFields['fact_description'] ?? strip_tags($item->introtext);
    $regionCode = $customFields['region_code'] ?? null;

    return [
        'id' => (int) $item->id,
        'fact_title' => $factTitle,
        'fact_description' => $factDescription,
        'region_code' => $regionCode,
        'category' => [
            'id' => (int) $item->catid,
            'title' => $item->category_title ?? null,
            'alias' => $item->category_alias ?? null,
        ],
        // Ticker display: "Title: Description"
        'full_text' => $factTitle . ': ' . $factDescription,
    ];
}
```

---

## Migration Note

**Eski yapıdan geçiş**:
- `ancient_name` → `fact_title`
- `modern_name` → `fact_description`
- `region_code` → `region_code` (değişmedi)

**Backward compatibility**: Helper metodu her iki yapıyı da destekliyor.

---

## Example Data Structure

```
Article 1:
- Title: Assos
- Category: Ticker Info
- fact_title: Assos
- fact_description: Athena Tapınağı ile ünlü, filozof Aristoteles'in bir süre yaşadığı, günümüz Çanakkale'nin Ayvacık ilçesine bağlı Behramkale köyünde yer alan liman kentidir.
- region_code: troas-coins

Article 2:
- Title: Elektron Sikke
- Category: Ticker Info
- fact_title: Elektron
- fact_description: Doğal altın-gümüş alaşımı, Lidyalılar tarafından dünyanın ilk sikkelerinin basımında kullanıldı. M.Ö. 7. yüzyılda Sardeis'te üretildi.
- region_code: lydia-coins

Article 3:
- Title: Artemis Pergaia
- Category: Ticker Info
- fact_title: Artemis Pergaia
- fact_description: Perge'nin koruyucu tanrıçası. Sikkelerde ok ve yay taşıyan, bazen avcı kıyafetli olarak betimlenir.
- region_code: pamphylia-coins
```

---

## Next Steps

1. ✅ Create custom fields (fact_title, fact_description, region_code)
2. ✅ Create "Ticker Info" category
3. ✅ Assign fields to category
4. ✅ Note all IDs
5. ✅ Proceed to CSV preparation

---

**Version**: 2.0.0 (Updated for flexible content)
**Last Updated**: 2025-10-29
