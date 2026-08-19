# Custom Fields Setup for Ticker Module

## Step 1: Create Custom Fields in Joomla

Navigate to: **Content → Fields** and create these 3 fields:

---

### Field 1: Ancient Name (Antik İsim)

**Basic Settings**:
- Title: `Ancient Name` / `Antik İsim`
- Name: `ancient_name` (will auto-generate)
- Type: **Text**
- Label: `Ancient Name` / `Antik İsim`
- Description: `Historical place name (e.g., Sardeis, Ephesos)`

**Options**:
- Default Value: (leave empty)
- Placeholder: `e.g., Sardeis`
- Size: `40`
- Max Length: `255`

**Publishing**:
- Status: **Published**
- Access: Public
- Language: All

**Permissions**:
- Default

---

### Field 2: Modern Name (Modern İsim)

**Basic Settings**:
- Title: `Modern Name` / `Modern İsim`
- Name: `modern_name` (will auto-generate)
- Type: **Text**
- Label: `Modern Name` / `Modern İsim`
- Description: `Current location name (e.g., Sart, Manisa, Türkiye)`

**Options**:
- Default Value: (leave empty)
- Placeholder: `e.g., Sart, Manisa, Türkiye`
- Size: `60`
- Max Length: `255`

**Publishing**:
- Status: **Published**
- Access: Public
- Language: All

**Permissions**:
- Default

---

### Field 3: Region Code (Bölge Kodu)

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
other|Other
```

**Publishing**:
- Status: **Published**
- Access: Public
- Language: All

**Permissions**:
- Default

---

## Step 2: Get Field IDs

After creating fields, note their IDs:

1. Go to: **Content → Fields**
2. Hover over each field name
3. Note the ID from the URL (e.g., `...&id=123`)

**Record these IDs**:
- ancient_name field ID: `_____`
- modern_name field ID: `_____`
- region_code field ID: `_____`

You'll need these IDs for the CSVI import CSV file.

---

## Step 3: Create Category

Navigate to: **Content → Categories**

**Create New Category**:
- Title: `Darphane İsimleri`
- Alias: `darphane-isimleri` (will auto-generate)
- Parent: Root (or your preferred parent)
- Status: **Published**
- Access: Public
- Language: All
- Description: `Antik darphane şehirleri ve modern yerleşim yerleri`

**Note the Category ID**: `_____`

---

## Step 4: Field Assignment to Category

After creating the category:

1. Go back to: **Content → Fields**
2. Edit each field (ancient_name, modern_name, region_code)
3. In **Field Assignment** tab:
   - Select: `Darphane İsimleri` category
   - OR select: `All` to make available everywhere
4. Save & Close

---

## Verification

After setup, when you create a new article in "Darphane İsimleri" category, you should see these custom fields in the article edit form:

- ✅ Ancient Name (text input)
- ✅ Modern Name (text input)
- ✅ Region Code (dropdown)

---

## Field Structure Summary

| Field Name | Field Type | Required | Used For |
|------------|------------|----------|----------|
| ancient_name | Text | Yes | Display in ticker (left side) |
| modern_name | Text | Yes | Display in ticker (right side) |
| region_code | List | Yes | Region filtering |

**Ticker Display Format**:
```
Ancient Name → Modern Name
```

**Example**:
```
Sardeis → Sart, Manisa, Türkiye
```
