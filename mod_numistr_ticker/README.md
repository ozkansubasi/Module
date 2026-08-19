# NumisTR Ticker Module

News ticker module displaying ancient and modern place names with regional filtering support.

## Quick Start

### 1. Installation

```bash
# Package the module
cd Module/
zip -r mod_numistr_ticker.zip mod_numistr_ticker/

# Install via Joomla
# Navigate to: System → Extensions → Install
# Upload mod_numistr_ticker.zip
```

### 2. Create Content Category

1. Navigate to: Content → Categories
2. Create new category: "Darphane İsimleri" (Mint Names)
3. Note the category ID or alias

### 3. Create Custom Fields

Navigate to: Content → Fields and create:

1. **ancient_name** (Text)
   - Label: Ancient Name
   - Required: Yes

2. **modern_name** (Text)
   - Label: Modern Name
   - Required: Yes

3. **region_code** (List)
   - Label: Region Code
   - Options: lydia-coins, ionia-coins, mysia-coins, etc.
   - Required: Yes

### 4. Create Sample Content

Create articles in "Darphane İsimleri" category:

**Example 1**:
- Title: Sardeis
- ancient_name: Sardeis
- modern_name: Sart, Manisa, Türkiye
- region_code: lydia-coins

**Example 2**:
- Title: Ephesos
- ancient_name: Ephesos
- modern_name: Efes, İzmir, Türkiye
- region_code: ionia-coins

### 5. Configure Module

1. Navigate to: Content → Site Modules
2. Find "NumisTR Ticker"
3. Settings:
   - **Position**: top (or your preferred position)
   - **Ticker Category**: Select "Darphane İsimleri"
   - **Region Filter**: All Regions
   - **Number of Items**: 20
   - **Status**: Published

### 6. Test

Visit your site homepage - the ticker should display at the configured position.

## REST API Usage

### Endpoint

```
GET https://www.numistr.org/api/index.php/v1/ticker
```

### Query Parameters

- `category`: Category alias (default: `darphane-isimleri`)
- `region`: Region code (default: `all`)
- `limit`: Number of items (max: 200, default: 50)
- `random`: Randomize order (default: `true`)

### Examples

```bash
# All mint names
curl "https://www.numistr.org/api/index.php/v1/ticker"

# Lydia region only
curl "https://www.numistr.org/api/index.php/v1/ticker?region=lydia-coins"

# Ordered by article ordering
curl "https://www.numistr.org/api/index.php/v1/ticker?random=false&limit=30"
```

### Response Format

```json
{
  "data": {
    "items": [
      {
        "id": 123,
        "ancient_name": "Sardeis",
        "modern_name": "Sart, Manisa, Türkiye",
        "region_code": "lydia-coins",
        "full_text": "Sardeis → Sart, Manisa, Türkiye"
      }
    ],
    "count": 1,
    "region": "all",
    "category": "darphane-isimleri",
    "randomized": true
  }
}
```

## Features

- ✅ Category-based content management
- ✅ Region-specific filtering
- ✅ Auto-detection of current page region
- ✅ Randomized or manual ordering
- ✅ Configurable animation speed
- ✅ Responsive design (desktop, tablet, mobile)
- ✅ Accessibility support (keyboard, screen readers, reduced motion)
- ✅ Dark mode support
- ✅ Cache optimization
- ✅ REST API integration
- ✅ Touch controls for mobile
- ✅ Pause on hover

## Configuration Options

### Basic Settings

| Setting | Default | Description |
|---------|---------|-------------|
| Ticker Category | - | Content category to display |
| Region Filter | All Regions | Filter by specific region |
| Auto-detect Region | Yes | Auto-detect from page context |
| Number of Items | 20 | How many items to show |
| Randomize Items | Yes | Random order |
| Cache Duration | 1 hour | Cache ticker items |

### Appearance Settings

| Setting | Default | Description |
|---------|---------|-------------|
| Animation Speed | 60 seconds | Complete scroll duration |
| Ticker Height | 40 pixels | Bar height |
| Show Icon | Yes | Display info icon |

## Region Codes

Available region filters:
- `lydia-coins` - Lydia
- `ionia-coins` - Ionia
- `mysia-coins` - Mysia
- `troas-coins` - Troas
- `aeolis-coins` - Aeolis
- `caria-coins` - Caria
- `lycia-coins` - Lycia
- `pamphylia-coins` - Pamphylia
- `cilicia-coins` - Cilicia
- `pisidia-coins` - Pisidia
- `phrygia-coins` - Phrygia
- `galatia-coins` - Galatia
- `cappadocia-coins` - Cappadocia
- `pontus-coins` - Pontus
- `bithynia-coins` - Bithynia

## File Structure

```
mod_numistr_ticker/
├── mod_numistr_ticker.xml          # Module manifest
├── mod_numistr_ticker.php          # Entry point
├── helper.php                       # Business logic
├── README.md                        # This file
├── tmpl/
│   └── default.php                 # Display template
├── assets/
│   ├── css/
│   │   └── ticker.css              # Styling
│   └── js/
│       └── ticker.js               # JavaScript
└── language/
    ├── en-GB/                       # English translations
    └── tr-TR/                       # Turkish translations
```

## Mobile App Integration

The ticker is integrated into the mobile app via the REST API endpoint.

### Flutter Package Required

```yaml
dependencies:
  marquee: ^2.2.3  # For scrolling text animation
```

### Usage Example

```dart
// Fetch ticker items
final items = await tickerApi.getTickerItems(region: 'lydia-coins');

// Display in widget
TickerWidget(region: currentRegion)
```

See full implementation in: `claudedocs/components/mod-numistr-ticker.md`

## Troubleshooting

### Ticker not showing?

1. Check module is **Published**
2. Verify module **Position** is valid
3. Ensure category has **published articles**
4. Clear **Joomla cache**

### No items displayed?

1. Check articles exist in selected category
2. Verify custom fields are set
3. Check region filter matches article region codes
4. Enable **debug mode** and check logs

### API returns empty?

1. Test endpoint directly: `/api/index.php/v1/ticker`
2. Check category alias is correct
3. Verify plugin is enabled
4. Check rate limiting (default: 60 requests/minute)

## Documentation

Full documentation available in:
- `claudedocs/components/mod-numistr-ticker.md`
- `claudedocs/architecture/joomla-frontend-architecture.md`

## Version

**1.0.0** - Initial release (2025-10-29)

## License

GNU General Public License version 2 or later

## Copyright

Copyright (C) 2025 NumisTR. All rights reserved.
