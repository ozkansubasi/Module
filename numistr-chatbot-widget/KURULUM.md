# NumisTR AI Chatbot Widget - Joomla Kurulum Kılavuzu

## Hızlı Kurulum (Önerilen)

### Adım 1: JavaScript Dosyasını Yükle

1. cPanel File Manager'a giriş yapın
2. `public_html/media/` klasörüne gidin
3. Yeni klasör oluşturun: `numistr-chatbot`
4. `numistr-chatbot.js` dosyasını bu klasöre yükleyin

Sonuç: `https://www.numistr.org/media/numistr-chatbot/numistr-chatbot.js`

### Adım 2: Site Genelinde Etkinleştirme

**YooTheme Pro ile (Önerilen):**

1. YooTheme Pro → Settings → Custom Code
2. "Scripts" alanına ekleyin:
```html
<script src="/media/numistr-chatbot/numistr-chatbot.js" defer></script>
```

**VEYA index.php ile:**

1. `templates/yootheme/index.php` dosyasını düzenleyin
2. `</body>` etiketinden hemen önce ekleyin:
```html
<script src="/media/numistr-chatbot/numistr-chatbot.js" defer></script>
```

**VEYA Custom HTML Modülü ile:**

1. Extensions → Modules → New
2. Tür: "Custom"
3. Position: "debug" (sayfanın sonunda)
4. İçerik (HTML modunda):
```html
<script src="/media/numistr-chatbot/numistr-chatbot.js" defer></script>
```
5. Published: Yes
6. Tüm sayfalarda göster

---

## v2 (2026-08-21) — Backend değişti

Widget artık n8n webhook'una DEĞİL, aynı origin'deki Joomla plugin ucuna konuşur:
`POST /api/index.php/v1/assistant/chat` (ADR-003 Faz 1). n8n `numistr-kb-query` webhook'u
artık `X-NumisTR-KB` başlığı ister; tarayıcıdan doğrudan çağrılamaz.

- Anonim kimlik: `nt_aid` cookie (HttpOnly, SameSite=Lax) → widget **yalnız numistr.org** üzerinde çalışır.
- Dil: `<html lang>` (tr-TR / en-GB), yoksa `/en/` URL öneki.
- `conversation_id` localStorage'da (`numistr_assistant_conv`, dile göre); panel açılınca geçmiş
  `GET /v1/assistant/conversations/{id}` ile geri yüklenir (404 → sıfırdan başlar). "+" = yeni sohbet.
- Cevapta `sources[]` link listesi, `cta.register` düğmesi, `quota.remaining_today` alt bilgisi gösterilir.
- Markdown: **kalın**, [link](url), çıplak URL, listeler — HTML her zaman önce escape edilir.

Güncelleme: yalnız `public_html/media/numistr-chatbot/numistr-chatbot.js` dosyasını üzerine yaz
(tarayıcı cache'i için sürüm parametresi: `numistr-chatbot.js?v=2`).

## Özelleştirme

`numistr-chatbot.js` başındaki `CONFIG` (apiBase, renkler, konum, storageKey, restoreHistory) ve
`I18N` (tr/en metinler) nesnelerini düzenleyin.

## Sadece Belirli Sayfalarda Gösterme

Chatbot'u sadece belirli sayfalarda göstermek için koşullu yükleme kullanın:

```html
<script>
    // Sadece /sikkeler ve /hakkimizda sayfalarında göster
    const allowedPages = ['/sikkeler', '/hakkimizda', '/iletisim'];
    const currentPath = window.location.pathname;

    if (allowedPages.some(page => currentPath.startsWith(page))) {
        const script = document.createElement('script');
        script.src = '/media/numistr-chatbot/numistr-chatbot.js';
        document.body.appendChild(script);
    }
</script>
```

---

## Test Etme

1. Tarayıcıda siteyi açın
2. Sağ alt köşede altın renkli sohbet butonu görünmeli
3. Butona tıklayın, sohbet paneli açılmalı
4. Örnek soru sorun: "Sardis antik kenti hakkında bilgi ver"
5. 10-30 saniye içinde yanıt gelmeli

---

## Sorun Giderme

### Widget görünmüyor
- Browser console'da hata var mı kontrol edin (F12 → Console)
- Script yolu doğru mu?
- JavaScript dosyası yüklendi mi?

### API yanıt vermiyor
- https://n8n.aetelekom.com/webhook/numistr-kb-query erişilebilir mi?
- CORS hatası var mı? (n8n zaten tüm origin'lere izin veriyor)

### Yanıt çok uzun sürüyor
- RAG sistemi 10-30 saniye sürebilir
- İlk sorgu daha uzun sürebilir (cold start)

---

## Dosyalar

| Dosya | Açıklama |
|-------|----------|
| `numistr-chatbot.js` | Ana widget JavaScript dosyası |
| `test.html` | Lokal test sayfası |
| `KURULUM.md` | Bu dosya |

---

## API Detayları

**Endpoint:** `POST https://n8n.aetelekom.com/webhook/numistr-kb-query`

**Request:**
```json
{
    "query": "Kullanıcı sorusu",
    "session_id": "opsiyonel_session_id"
}
```

**Response:**
```json
{
    "query": "Kullanıcı sorusu",
    "answer": "AI yanıtı...",
    "session_id": "session_id",
    "result_count": 3,
    "model": "gpt-4o-mini-2024-07-18",
    "tokens_used": 250,
    "timestamp": "2025-12-10T21:37:14.388Z"
}
```

---

## İletişim

Sorunlar için: Claude Code Session Notes'a bakın veya n8n workflow'larını kontrol edin.
