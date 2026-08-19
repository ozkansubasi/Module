# CSVI Import Guide for Ticker Content

## Prerequisites

1. ✅ CSVI Pro component installed in Joomla
2. ✅ Custom fields created (ancient_name, modern_name, region_code)
3. ✅ Category "Darphane İsimleri" created
4. ✅ Field IDs and Category ID noted

---

## Step 1: Prepare CSV File

### Get Required IDs

**A. Category ID**:
1. Go to: **Content → Categories**
2. Find "Darphane İsimleri"
3. Hover and note ID from URL: `...&id=XX`
4. Example: `45`

**B. Author ID** (your user ID):
1. Go to: **Users → Manage**
2. Find your admin user
3. Note ID from URL
4. Example: `628` (or use your super admin ID)

**C. Custom Field IDs**:
1. Go to: **Content → Fields**
2. For each field, note the ID:
   - `ancient_name` field ID: `___`
   - `modern_name` field ID: `___`
   - `region_code` field ID: `___`

### Update CSV File

Open `02-ticker-content-sample.csv` and replace:
- `CATEGORY_ID` → Your actual category ID (e.g., `45`)
- `AUTHOR_ID` → Your user ID (e.g., `628`)

**Important**: Do NOT change the custom field column names yet. We'll map them in CSVI.

---

## Step 2: Create CSVI Template

### Navigate to CSVI

**Path**: Components → CSVI Pro → Templates

### Create New Template

Click **New** button

### Template Configuration

**Tab 1: General**

| Setting | Value |
|---------|-------|
| Template name | `Ticker Content Import` |
| Action | `Import` |
| Component | `Content` |
| Operation | `Article` |

**Tab 2: Options**

| Setting | Value |
|---------|-------|
| Field delimiter | `,` (comma) |
| Field enclosure | `"` (double quote) |
| Encoding | `UTF-8` |
| Skip first line | `Yes` ✅ (because of headers) |
| Update existing | `Yes` (if reimporting) |

**Tab 3: Rules** (leave default)

**Tab 4: Template fields**

Click **Load available fields** button, then configure these mappings:

| CSV Column | Template Field | Required | Default Value |
|------------|---------------|----------|---------------|
| title | Title | Yes | - |
| alias | Alias | No | - |
| catid | Category | Yes | - |
| state | Published | No | `1` |
| access | Access | No | `1` |
| language | Language | No | `*` |
| introtext | Intro Text | No | - |
| custom_field_ancient_name | Custom: Ancient Name | Yes | - |
| custom_field_modern_name | Custom: Modern Name | Yes | - |
| custom_field_region_code | Custom: Region Code | Yes | - |
| created_by | Created By | No | - |

**Important**: For custom fields, you need to map using the field NAME, not ID.

**Tab 5: Custom**

Add these custom field mappings:

```
[Click "Add custom field"]

Custom Field 1:
- Field name: ancient_name
- CSV column: custom_field_ancient_name
- Process: Always

Custom Field 2:
- Field name: modern_name
- CSV column: custom_field_modern_name
- Process: Always

Custom Field 3:
- Field name: region_code
- CSV column: custom_field_region_code
- Process: Always
```

**Save Template**

---

## Step 3: Alternative - Simpler CSV Format

If CSVI custom field mapping is complex, use this simpler approach:

### Create Modified CSV

**File**: `02-ticker-content-simple.csv`

```csv
title,alias,catid,state,introtext,ancient_name,modern_name,region_code
"Sardeis","sardeis",45,1,"Lidya Krallığı'nın başkenti","Sardeis","Sart, Manisa, Türkiye","lydia-coins"
"Ephesos","ephesos",45,1,"İyonya'nın en büyük şehri","Ephesos","Selçuk, İzmir, Türkiye","ionia-coins"
```

### CSVI Template for Simple Format

**Template fields mapping**:
- title → Title
- alias → Alias
- catid → Category
- state → Published
- introtext → Intro Text
- ancient_name → Custom field: ancient_name
- modern_name → Custom field: modern_name
- region_code → Custom field: region_code

---

## Step 4: Import Process

### Start Import

1. Go to: **Components → CSVI Pro → Import**
2. Click **New** button

### Import Configuration

**Step 1: Select File**

- Upload your CSV file: `02-ticker-content-sample.csv`
- Or use file from server path

**Step 2: Select Template**

- Choose: `Ticker Content Import` (the template you created)

**Step 3: Import Settings**

| Setting | Value |
|---------|-------|
| Dry run | `Yes` (for first test) |
| Skip first line | `Yes` |
| Process in background | `No` (for small files) |

**Step 4: Review**

- Check preview shows correct data
- Verify field mappings look correct

**Step 5: Start Import**

- Click **Start Import**
- Watch progress

### Verify Test Import (Dry Run)

After dry run completes:
1. Review import log
2. Check for errors
3. Verify field mapping is correct

### Real Import

If dry run successful:
1. Go back to import
2. Set **Dry run** to `No`
3. Run import again
4. Monitor for completion

---

## Step 5: Verify Import Results

### Check Articles

1. Go to: **Content → Articles**
2. Filter by Category: `Darphane İsimleri`
3. You should see 62 new articles

### Check Individual Article

Open any article and verify:
- ✅ Title is correct
- ✅ Intro text is filled
- ✅ Custom field: Ancient Name is filled
- ✅ Custom field: Modern Name is filled
- ✅ Custom field: Region Code is selected

### Check Frontend

1. Install and publish ticker module
2. View your site
3. Ticker should display: `Ancient Name → Modern Name`

---

## Troubleshooting

### Issue: Custom fields not importing

**Solution 1**: Check field names match exactly
- In Joomla Fields: note the "Name" column (e.g., `ancient_name`)
- In CSV: column should be `ancient_name` or `custom_field_ancient_name`

**Solution 2**: Use field IDs instead
- Change CSV columns to: `custom_field_XX` where XX is field ID
- Example: `custom_field_123` for field ID 123

### Issue: Articles not in correct category

**Solution**:
- Verify `catid` value matches your category ID
- Use numeric ID, not alias

### Issue: CSV encoding problems (Turkish characters broken)

**Solution**:
- Save CSV as UTF-8 encoding
- In Excel: "Save As" → More options → Encoding: UTF-8
- Or use text editor (VS Code, Notepad++) with UTF-8

### Issue: Import shows errors

**Solution**:
1. Check CSVI log: Components → CSVI Pro → Logs
2. Read error messages
3. Common fixes:
   - Missing required fields
   - Invalid category ID
   - Wrong field names
   - Encoding issues

---

## Alternative: Manual Custom Field Import

If CSVI custom fields are problematic, import in two steps:

### Step 1: Import Basic Articles (Without Custom Fields)

CSV structure:
```csv
title,alias,catid,state,introtext
"Sardeis","sardeis",45,1,"Lidya başkenti"
```

### Step 2: Import Custom Fields Separately

Use CSVI "Field values" template:

CSV structure:
```csv
article_title,field_name,field_value
"Sardeis","ancient_name","Sardeis"
"Sardeis","modern_name","Sart, Manisa, Türkiye"
"Sardeis","region_code","lydia-coins"
```

---

## Post-Import Tasks

### 1. Clear Cache

- Go to: **System → Clear Cache**
- Select all
- Click **Delete**

### 2. Test Ticker Module

- Enable ticker module
- View frontend
- Verify items display correctly

### 3. Test API Endpoint

```bash
curl "https://www.numistr.org/api/index.php/v1/ticker?limit=5"
```

Expected: JSON with 5 ticker items

### 4. Test Region Filtering

```bash
curl "https://www.numistr.org/api/index.php/v1/ticker?region=lydia-coins"
```

Expected: Only Lydia region items

---

## Data Maintenance

### Adding New Items

**Option 1**: Add via Joomla backend
- Create article in "Darphane İsimleri" category
- Fill custom fields
- Publish

**Option 2**: Prepare new CSV
- Add new rows to CSV
- Import with CSVI (will skip existing, add new)

### Updating Existing Items

**Option 1**: Edit in Joomla backend

**Option 2**: CSV reimport
- Prepare updated CSV
- CSVI template: Enable "Update existing"
- Import (will match by alias and update)

---

## Sample Data Summary

The provided CSV contains **62 mint cities** covering:

| Region | Cities | Examples |
|--------|--------|----------|
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

**Total**: 62 ancient mint cities with modern locations

---

## Next Steps

1. ✅ Complete custom fields setup
2. ✅ Note all required IDs
3. ✅ Update CSV with your IDs
4. ✅ Create CSVI template
5. ✅ Run test import (dry run)
6. ✅ Run real import
7. ✅ Verify data
8. ✅ Test module and API

---

## Support

**CSVI Documentation**: https://docs.csvimproved.com/
**CSVI Forum**: https://www.csvimproved.com/forum/

**Ticker Module Documentation**:
- `Module/mod_numistr_ticker/README.md`
- `claudedocs/components/mod-numistr-ticker.md`
