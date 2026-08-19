# Mint Ticker Import Guide

## 📋 Overview

This guide covers importing 69 mint (darphane) city entries extracted from "Anadolu Antik Sikke Bölgeleri.xlsx" into Joomla for the ticker module.

**File**: `03-mint-ticker-content.csv`
**Total Entries**: 69 mint cities from 15 ancient regions
**Format**: fact_title → fact_description (flexible ticker format)

---

## 📊 Content Statistics

### Entries by Region

| Region | Mints | Region Code |
|--------|-------|-------------|
| Aeolis | 6 | aeolis-coins |
| Bithynia | 5 | bithynia-coins |
| Cappadocia | 2 | cappadocia-coins |
| Caria | 5 | caria-coins |
| Cilicia | 6 | cilicia-coins |
| Galatia | 3 | galatia-coins |
| Ionia | 6 | ionia-coins |
| Lycia | 5 | lycia-coins |
| Lydia | 1 | lydia-coins |
| Mysia | 4 | mysia-coins |
| Pamphylia | 4 | pamphylia-coins |
| Phrygia | 4 | phrygia-coins |
| Pisidia | 6 | pisidia-coins |
| Pontus | 6 | pontus-coins |
| Troas | 6 | troas-coins |

**Total**: 69 mints across 15 regions

---

## 🎯 Example Entries

### Ancient Cities with Historical Context

```
Assos: Athena Tapınağı ile ünlü, filozof Aristoteles'in bir süre yaşadığı, günümüz Çanakkale'nin Ayvacık ilçesine bağlı Behramkale köyünde yer alan liman kentidir.

Ephesos (Efes): İzmir'in Selçuk ilçesi sınırları içinde yer alan, Artemis Tapınağı ile ünlü, antik dünyanın yedi harikasından birine ev sahipliği yapmış olan en büyük İyon kentidir.

Pergamon (Bergama): Helenistik dönemde Bergama Krallığı'nın başkenti olan ve parşömenin icadıyla ünlenen bu şehir, günümüz İzmir'in Bergama ilçesinde bulunur.
```

### Modern Connections

```
Nikomedia (Nicomedia): Bitinya Krallığı'nın başkenti ve Roma İmparatorluğu'nun en önemli dört başkentinden biri olan Nikomedia, günümüz Kocaeli'nin İzmit ilçesidir.

Ankyra (Ancyra): Galatya'nın Roma dönemindeki başkenti olan Ankyra, günümüz Türkiye'sinin başkenti Ankara'dır.
```

---

## 🚀 Import Steps

### 1️⃣ Prepare CSV File (5 minutes)

**Required Change**: Replace `CATEGORY_ID` with your actual category ID

1. Open: `03-mint-ticker-content.csv` in text editor or Excel
2. Find: `"catid","CATEGORY_ID"`
3. Replace all: `CATEGORY_ID` → Your actual "Ticker Info" category ID (e.g., `45`)
4. Save: Ensure UTF-8 encoding is preserved

**Example**:
```csv
Before: "title","alias","CATEGORY_ID","state",...
After:  "title","alias","45","state",...
```

### 2️⃣ Create CSVI Template (10 minutes)

**Template Name**: `Mint Ticker Import`

**Field Mappings**:

| CSV Column | Joomla Field | Type | Notes |
|------------|--------------|------|-------|
| title | Title | Article | "CityName (Darphane)" |
| alias | Alias | Article | URL-safe slug |
| catid | Category | Article | Ticker Info category |
| state | Published | Article | 1 = Published |
| access | Access | Article | 1 = Public |
| language | Language | Article | * = All |
| introtext | Intro Text | Article | "Antik darphane" |
| fact_title | Custom field: fact_title | Text | Mint city name |
| fact_description | Custom field: fact_description | Textarea | Full description |
| region_code | Custom field: region_code | List | Region code for filtering |

**Custom Field Configuration**:
- `fact_title` → Type: Text → Field ID: [your field ID]
- `fact_description` → Type: Textarea → Field ID: [your field ID]
- `region_code` → Type: List → Field ID: [your field ID]

### 3️⃣ Test Import (Dry Run)

1. **Components** → **CSVI Pro** → **Import** → **New**
2. **Select File**: `03-mint-ticker-content.csv`
3. **Template**: Select "Mint Ticker Import"
4. **Dry Run**: ✅ **Yes**
5. **Start Import**

**Expected Result**:
```
✅ 69 articles ready to import
✅ All custom fields mapped correctly
✅ No errors detected
```

### 4️⃣ Real Import

1. **Dry run**: ❌ **No**
2. **Start Import**
3. **Wait**: ~1-2 minutes for 69 entries

**Expected Result**:
```
✅ 69 articles imported successfully
✅ Custom fields populated
✅ All regions represented
```

---

## ✅ Verification Steps

### 1. Check Article Count

**Content** → **Articles** → **Filter by Category**: "Ticker Info"

**Expected**: 69+ articles (69 mints + any previous content)

### 2. Verify Custom Fields

Pick a sample article (e.g., "Assos (Darphane)"):
- ✅ `fact_title`: "Assos"
- ✅ `fact_description`: "Athena Tapınağı ile ünlü..."
- ✅ `region_code`: "troas-coins"

### 3. Test Region Filtering

**API Test** (replace with your domain):
```bash
curl "https://www.numistr.org/api/index.php/v1/ticker?region=troas-coins&limit=5"
```

**Expected Response**:
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

### 4. Test Ticker Display

Visit a page with the ticker module installed:
- ✅ Mints are rotating smoothly
- ✅ Text is readable and properly formatted
- ✅ Modern location info is present
- ✅ Historical context included

---

## 🔧 Troubleshooting

### Issue: UTF-8 Characters Broken

**Symptom**: Turkish characters display as "Ã§", "Ã¼", etc.

**Solution**:
1. Re-save CSV with UTF-8 encoding (with BOM)
2. Or: Convert in terminal: `iconv -f UTF-8 -t UTF-8 file.csv > file_fixed.csv`

### Issue: Custom Fields Not Populating

**Symptom**: Articles imported but custom fields are empty

**Check**:
1. Field IDs match in CSVI template
2. Fields are assigned to "Ticker Info" category
3. Field names exactly match: `fact_title`, `fact_description`, `region_code`

**Fix**: Re-run import with corrected field mappings

### Issue: Category Not Found

**Symptom**: Import fails with "Category ID not found"

**Solution**:
1. Go to **Content** → **Categories**
2. Find "Ticker Info" category
3. Note the ID (visible in URL or ID column)
4. Update CSV with correct category ID

### Issue: Duplicate Entries

**Symptom**: Some mints imported twice

**Solution**:
1. **Content** → **Articles** → Filter by "Ticker Info"
2. Search for duplicate titles
3. Delete duplicates manually
4. Or: Use CSVI's "Update existing" option

---

## 📈 Next Steps

### 1. Add Missing Mints

Some important mints may not be in the Excel file. Consider adding:
- **Lydia**: Thyatira, Philadelphia, Magnesia ad Sipylum
- **Mysia**: Pitane
- **Caria**: Iasos
- **Phrygia**: Synnada, Hierapolis
- **Cilicia**: Aigai

### 2. Enhance Descriptions

For mints with minimal descriptions, add:
- Historical significance
- Famous coins or types
- Notable rulers
- Archaeological discoveries
- UNESCO status (if applicable)

### 3. Create Additional Content Types

The flexible ticker system supports more than mints:
- Famous numismatists
- Coin collecting terms
- Historical events (M.Ö. 547 - Lydya'nın sonu)
- Mythological symbols on coins
- Seven Wonders connections

### 4. Regional Content Packages

Create themed ticker content sets:
- "Lydia Week": All Lydian mints and related facts
- "Seven Wonders Tour": Cities with ancient wonders
- "Roman Capitals": Major administrative centers
- "Philosopher's Path": Cities where philosophers lived

---

## 📚 Data Source

**Original File**: `Anadolu Antik Sikke Bölgeleri.xlsx`
**Extraction Script**: `extract_mint_data_final.py`
**Extraction Date**: 2025-10-29
**Content Author**: User's existing research data

---

## 🎨 Display Examples

### Ticker Output Format

```
Assos: Athena Tapınağı ile ünlü, filozof Aristoteles'in bir süre yaşadığı, günümüz Çanakkale'nin Ayvacık ilçesine bağlı Behramkale köyünde yer alan liman kentidir.

Ephesos (Efes): İzmir'in Selçuk ilçesi sınırları içinde yer alan, Artemis Tapınağı ile ünlü, antik dünyanın yedi harikasından birine ev sahipliği yapmış olan en büyük İyon kentidir.

Pergamon (Bergama): Helenistik dönemde Bergama Krallığı'nın başkenti olan ve parşömenin icadıyla ünlenen bu şehir, günümüz İzmir'in Bergama ilçesinde bulunur.
```

### Region-Specific Display

When viewing a Troas coin page, ticker shows only Troas mints:
- Assos: Athena Tapınağı ile ünlü...
- Alexandria Troas: Büyük İskender'in generalleri...
- Troia (Ilium / Truva): Efsanevi savaşın merkezi...
- Tenedos (Bozcaada): Truva'nın tam karşısında...
- Abydos: Çanakkale Boğazı'nın en dar yerinde...
- Sigeion: Çanakkale Boğazı'nın Ege girişinde...

---

## ⏱️ Time Estimates

| Step | Duration | Total |
|------|----------|-------|
| Prepare CSV (replace CATEGORY_ID) | 5 min | 5 min |
| Create CSVI template | 10 min | 15 min |
| Test import (dry run) | 5 min | 20 min |
| Real import | 2 min | 22 min |
| Verification | 5 min | **27 min** |

**Total**: Approximately **25-30 minutes**

---

## 📞 Support

### CSVI Documentation
- Template creation: https://csvimproved.com/documentation
- Field mapping: https://csvimproved.com/documentation/field-mapping
- Custom fields: https://csvimproved.com/documentation/joomla-custom-fields

### Joomla Custom Fields
- Creating fields: https://docs.joomla.org/J3.x:Adding_custom_fields
- Field types: https://docs.joomla.org/J3.x:Custom_Fields_Types

---

**Last Updated**: 2025-10-29
**Version**: 1.0.0
**Status**: Ready for Import ✅
