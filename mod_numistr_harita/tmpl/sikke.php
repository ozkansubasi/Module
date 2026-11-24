<?php
/**
 * @package     NumisTR
 * @subpackage  mod_numistr_harita
 *
 * Sikke detay sayfasının darphane haritası (modül #130, YOOtheme "Sikke Layout" şablonları). 1.1.0 (C14, 16 Kas 2026):
 *  - Sikkenin darphanesi vurgulu işaret + adıyla; tıklanınca en yakın darphane kaydının özeti ve yazı bağlantısı.
 *  - Bölgelerin önemli antik kentleri (130; uygulamanın `assets/data/ancient_major_cities.json` dosyasıyla aynı veri,
 *    `data/ancient_major_cities.json`). Adlar birbirinin üstüne binmez; tıklanınca en yakın yerleşim kaydı (5 km)
 *    özet + yazı bağlantısıyla açılır. Kayıtlar Antik Harita sayfasıyla aynı uçtan gelir (`/v1/locations`).
 *  - Google Maps yalnız harita görünür alana yaklaşınca yüklenir (her yükleme ücretli kalem).
 *  - `google.maps.Marker` (kullanımdan kalkıyor) yok: işaretler OverlayView + HTML; harita biçemi (styles) korunur,
 *    AdvancedMarkerElement'in istediği Map ID gerekmez.
 *  - Koordinat sayfadaki "Darphane Koordinatları" / "Mint Coordinates" satırından okunur; ters yazılmışsa
 *    (boylam,enlem) Anadolu aralığına ve sayfanın bölgesine bakılarak düzeltilir.
 * Antik Harita sayfası (anadolu.php) bu sürümde değişmez.
 * 1.2.0 (25 Kas 2026, PRO değer analizi H1): haritanın altında "Google Haritalar'da göster". PRO oturumda darphane
 *  koordinatıyla Google Haritalar'ı açar; ücretsiz / misafirde PRO rozetiyle fiyat sayfasına gider (uygulamadaki kuralla aynı).
 *  PRO bilgisi sunucudan (`$proMu`, galeri modülüyle aynı yol): sayfa önbelleği yalnız misafire uygulanır, Cloudflare HTML'i önbelleğe almaz.
 */

defined('_JEXEC') or die;

$h = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

$kentler = array();
$kaynak  = @file_get_contents(__DIR__ . '/../data/ancient_major_cities.json');
$liste   = is_string($kaynak) ? json_decode($kaynak, true) : null;

if (is_array($liste)) {
    foreach ($liste as $k) {
        if (isset($k['name'], $k['lat'], $k['lng']) && is_numeric($k['lat']) && is_numeric($k['lng'])) {
            $kentler[] = array((string) $k['name'], round((float) $k['lat'], 3), round((float) $k['lng'], 3));
        }
    }
}

$veri = (string) json_encode(
    array('cities' => $kentler),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<div class="uk-margin-remove-last-child custom nmh-sikke">
<style>
.nmh-sikke #single-mint-map{position:relative;width:100%;height:400px;overflow:hidden;border-radius:8px;background:#f3e0c5}
.nmh-sikke .nmh-yer{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;text-align:center;color:#6d4c41;font-size:14px}
.nmh-sikke .nmh-bolge{position:absolute;transform:translate(-50%,-50%);font-weight:700;font-size:13px;line-height:1;letter-spacing:.12em;color:rgba(109,76,65,.78);white-space:nowrap;pointer-events:none;text-shadow:0 0 3px #f3e0c5,0 0 3px #f3e0c5}
.nmh-sikke .nmh-kent,.nmh-sikke .nmh-darphane{position:absolute;display:flex;align-items:center;gap:5px;margin:0;padding:0;border:0;background:none;cursor:pointer;font-family:inherit;white-space:nowrap;-webkit-tap-highlight-color:transparent}
.nmh-sikke .nmh-kent{transform:translate(-5px,-50%);font-size:12px;font-weight:600;line-height:1.2;color:#4e342e}
.nmh-sikke .nmh-kent .nmh-nokta{flex:none;box-sizing:border-box;width:9px;height:9px;border-radius:50%;background:#fcf5ed;border:2px solid #8d6e63}
.nmh-sikke .nmh-kent .nmh-ad{text-shadow:0 0 3px #fcf5ed,0 0 3px #fcf5ed,0 0 2px #fcf5ed}
.nmh-sikke .nmh-kent:hover .nmh-ad,.nmh-sikke .nmh-kent:focus-visible .nmh-ad{text-decoration:underline}
.nmh-sikke .nmh-darphane{z-index:5;transform:translate(-11px,-50%);font-size:14px;font-weight:700;line-height:1.2;color:#3e2723}
.nmh-sikke .nmh-darphane .nmh-nokta{flex:none;box-sizing:border-box;width:22px;height:22px;border-radius:50%;background:#b7791f;border:4px solid #fcf5ed;box-shadow:0 0 0 2px #7b5307,0 2px 6px rgba(0,0,0,.35)}
.nmh-sikke .nmh-darphane .nmh-ad{padding:3px 9px;border-radius:12px;background:#fcf5ed;box-shadow:0 1px 4px rgba(0,0,0,.25)}
.nmh-sikke .nmh-kent:focus-visible,.nmh-sikke .nmh-darphane:focus-visible{outline:2px solid #8a5f1a;outline-offset:2px}
.nmh-sikke .nmh-gizli{visibility:hidden}
.nmh-sikke .nmh-kart{position:absolute;z-index:10;box-sizing:border-box;width:260px;max-width:72vw;padding:14px 16px;border-radius:8px;background:#fcf5ed;box-shadow:0 5px 15px rgba(0,0,0,.3);transform:translate(-50%,calc(-100% - 18px));cursor:auto;white-space:normal}
.nmh-sikke .nmh-kart::after{content:'';position:absolute;bottom:-10px;left:50%;transform:translateX(-50%);border-left:10px solid transparent;border-right:10px solid transparent;border-top:10px solid #fcf5ed}
.nmh-sikke .nmh-tur{display:block;margin-bottom:2px;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#8a5f1a}
.nmh-sikke .nmh-kart h3{margin:0 24px 6px 0;font-size:16px;line-height:1.3;color:#333}
.nmh-sikke .nmh-ozet{margin:0;font-size:13px;line-height:1.45;color:#555;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:4;line-clamp:4;overflow:hidden}
.nmh-sikke .nmh-bag{display:inline-block;margin-top:8px;font-size:13px;font-weight:600;color:#8a5f1a}
.nmh-sikke .nmh-gm-satir{margin:8px 0 0;text-align:right}
.nmh-sikke .nmh-gm{font-size:13px;font-weight:600;color:#8a5f1a}
.nmh-sikke .nmh-pro{display:inline-block;margin-left:6px;padding:1px 7px;border-radius:9px;background:#b7791f;color:#fcf5ed;font-size:10px;line-height:1.6;letter-spacing:.06em;vertical-align:1px}
.nmh-sikke .nmh-kapat{position:absolute;top:4px;right:4px;width:32px;height:32px;padding:0;border:0;background:none;cursor:pointer;color:#6d4c41;font-size:22px;line-height:1}
</style>
<div id="single-mint-map" data-key="<?php echo $h($anahtar); ?>"><div class="nmh-yer"></div></div>
<p class="nmh-gm-satir" hidden><a id="nmh-gm" class="nmh-gm" data-pro="<?php echo empty($proMu) ? '0' : '1'; ?>"></a></p>
<script>
(function () {
    'use strict';

    var KAP = document.getElementById('single-mint-map');
    if (!KAP || KAP.getAttribute('data-nmh') === '1') { return; }
    KAP.setAttribute('data-nmh', '1');

    var VERI = <?php echo $veri; ?>;
    var EN = (document.documentElement.lang || 'tr').toLowerCase().indexOf('en') === 0;
    var DIL = EN ? 'en' : 'tr';
    var T = EN ? {
        harita: 'Mint map', yukleniyor: 'Loading the map…', hata: 'The map cannot be loaded right now.',
        darphane: 'Mint', kent: 'Ancient city', ozetYukleniyor: 'Loading…', bag: 'Open article', kapat: 'Close',
        gm: 'Show on Google Maps', gmPro: 'Available with PRO', fiyat: '/en/plans'
    } : {
        harita: 'Darphane haritası', yukleniyor: 'Harita yükleniyor…', hata: 'Harita şu anda yüklenemiyor.',
        darphane: 'Darphane', kent: 'Antik kent', ozetYukleniyor: 'Yükleniyor…', bag: 'Makaleyi aç', kapat: 'Kapat',
        gm: 'Google Haritalar\'da göster', gmPro: 'PRO üyelikle', fiyat: '/tr/abonelikler'
    };
    // [EN ad, TR ad, enlem, boylam, bölge sayfası kısa adı (sikke adresindeki)]
    var BOLGELER = [
        ['LYDIA', 'LİDYA', 38.6, 28.2, 'lydia-coins'], ['IONIA', 'İYONYA', 37.9, 27.3, 'ionia-coins'],
        ['CARIA', 'KARYA', 37.5, 28.3, 'caria-coins'], ['LYCIA', 'LİKYA', 36.6, 29.8, 'lycia-coins'],
        ['PHRYGIA', 'FRİGYA', 38.8, 30.5, 'phrygia-coins'], ['CILICIA', 'KİLİKYA', 37.0, 35.5, 'cilicia-coins'],
        ['PAMPHYLIA', 'PAMFİLYA', 37.0, 31.0, 'pamphylia-coins'], ['MYSIA', 'MİSYA', 39.9, 27.9, 'mysia-coins'],
        ['PISIDIA', 'PİSİDYA', 37.7, 30.5, 'pisidia-coins'], ['BITHYNIA', 'BİTİNYA', 40.5, 31.0, 'bithynia-coins'],
        ['CAPPADOCIA', 'KAPADOKYA', 38.6, 35.5, 'cappadocia-coins'], ['GALATIA', 'GALATYA', 39.9, 32.8, 'galatia-coins'],
        ['TROAS', 'TROAS', 39.8, 26.5, 'troas-coins'], ['AEOLIS', 'AİOLİS', 39.2, 27.0, 'aeolis-coins'],
        ['PONTUS', 'PONTUS', 40.8, 37.5, 'pontus-coins'], ['PAPHLAGONIA', 'PAFLAGONYA', 41.5, 34.0, 'paphlagonia-coins'],
        ['COMMAGENE', 'KOMMAGENE', 37.9, 38.5, ''], ['ISAURIA', 'İSAURYA', 37.1, 32.2, ''],
        ['LYCAONIA', 'LİKAONYA', 37.5, 33.5, ''], ['SOPHENE', 'SOFENE', 38.7, 39.5, '']
    ];
    var DARPHANE_KM = 8;  // uygulamadaki _sameMintKm: darphane kaydı eşleşmesi ve darphaneye çok yakın kentler gizlenir
    var KENT_KM = 5;      // uygulamadaki _cityMatchKm: kentin yerleşim kaydı

    var yer = KAP.querySelector('.nmh-yer');
    KAP.setAttribute('role', 'region');
    KAP.setAttribute('aria-label', T.harita);

    function satir(etiketler) {
        var basliklar = document.querySelectorAll('.el-title');
        var i, j, t, oge;
        for (j = 0; j < 2; j++) {           // önce birebir eşleşme, sonra içerme
            for (i = 0; i < basliklar.length; i++) {
                t = basliklar[i].textContent.trim();
                if (etiketler.some(function (e) { return j === 0 ? t === e : t.indexOf(e) !== -1; })) {
                    oge = basliklar[i].closest('.el-item');
                    if (oge && oge.querySelector('.el-content')) { return oge.querySelector('.el-content').textContent.trim(); }
                }
            }
        }
        return '';
    }

    function km(a1, o1, a2, o2) {
        var r = Math.PI / 180, dA = (a2 - a1) * r, dO = (o2 - o1) * r;
        var x = Math.sin(dA / 2) * Math.sin(dA / 2) + Math.cos(a1 * r) * Math.cos(a2 * r) * Math.sin(dO / 2) * Math.sin(dO / 2);
        return 12742 * Math.asin(Math.sqrt(x));
    }

    function gecerli(lat, lng) { return lat >= 34 && lat <= 43.5 && lng >= 25 && lng <= 46; }

    // "enlem,boylam" beklenir; katalogda ters yazılmış kayıtlar var (ör. 14983: "28.11,39.51").
    function konum(metin) {
        var p = (metin || '').split(',');
        if (p.length < 2) { return null; }
        var a = parseFloat(p[0]), b = parseFloat(p[1]);
        if (isNaN(a) || isNaN(b)) { return null; }
        var duz = gecerli(a, b), ters = gecerli(b, a);
        if (duz && ters) {
            var yol = location.pathname.toLowerCase();
            var bolge = BOLGELER.filter(function (g) { return g[4] && yol.indexOf('/' + g[4] + '/') !== -1; })[0];
            if (bolge && km(b, a, bolge[2], bolge[3]) < km(a, b, bolge[2], bolge[3])) { return { lat: b, lng: a }; }
            return { lat: a, lng: b };
        }
        if (duz) { return { lat: a, lng: b }; }
        if (ters) { return { lat: b, lng: a }; }
        return null;
    }

    // Katalog alanı çoğu sayfada anahtar ("attaea", "prusa_ad_olympum"): arama modülündeki mintLabel kuralıyla
    // okunur ada çevrilir. Ad zaten büyük harfle yazılmışsa ("Sardes (Sardis)") olduğu gibi kalır.
    var KUCUK = { ad: 1, de: 1, et: 1, sub: 1, 'in': 1 };
    function ad(s) {
        s = String(s || '').trim();
        if (/[A-ZÇĞİÖŞÜ]/.test(s) && s.indexOf('_') === -1) { return s; }
        return s.replace(/[_-]+/g, ' ').split(/\s+/).filter(Boolean).map(function (w, i) {
            var k = w.toLowerCase();
            return (i > 0 && KUCUK[k]) ? k : k.charAt(0).toUpperCase() + k.slice(1);
        }).join(' ');
    }

    var darphaneAdi = ad(satir(['Darphane', 'Mint']));
    var nokta = konum(satir(['Darphane Koordinatları', 'Mint Coordinates']));

    if (!darphaneAdi || !nokta) {
        KAP.style.display = 'none';      // koordinat yoksa boş 400 px kutu bırakma
        return;
    }
    yer.textContent = T.yukleniyor;

    // "Google Haritalar'da göster": PRO'da darphane koordinatı, ücretsizde PRO rozetiyle fiyat sayfası (uygulamayla aynı kural)
    var GM = document.getElementById('nmh-gm');
    if (GM && GM.parentNode) {
        GM.textContent = T.gm;
        if (GM.getAttribute('data-pro') === '1') {
            GM.setAttribute('href', 'https://www.google.com/maps/search/?api=1&query=' + nokta.lat + ',' + nokta.lng);
            GM.setAttribute('target', '_blank');
            GM.setAttribute('rel', 'noopener');
        } else {
            GM.setAttribute('href', T.fiyat);
            GM.setAttribute('title', T.gmPro);
            var rozet = document.createElement('span');
            rozet.className = 'nmh-pro';
            rozet.textContent = 'PRO';
            GM.appendChild(rozet);
        }
        GM.parentNode.hidden = false;
    }

    function hata() { yer.textContent = T.hata; yer.style.display = ''; }
    window.gm_authFailure = window.gm_authFailure || hata;

    // ---- Yerleşim kaydı: Antik Harita ile aynı uç (/v1/locations); yalnız tıklanınca istenir
    var ozetler = {};
    // Ad karşılaştırması için sadeleştirme: Thyatira ~ Thyateira, Kyzikos ~ Cyzicus, Ephesos ~ Ephesus
    function sade(s) {
        return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ı/g, 'i')
            .replace(/ph/g, 'f').replace(/c/g, 'k').replace(/y/g, 'i').replace(/ou/g, 'u').replace(/ei/g, 'i')
            .replace(/ae|ai/g, 'e').replace(/[^a-z]/g, '');
    }
    function benzer(a, b) {
        a = sade(a); b = sade(b);
        var n = Math.min(4, a.length, b.length);
        return n >= 3 && a.slice(0, n) === b.slice(0, n);
    }
    // Aynı noktada birden çok kayıt olabilir (ör. Thyatira'da "Thyateira" ile yeri belirsiz "Mernouphyta", ikisi 0,5 km):
    // önce adı benzeyen, sonra sikke basmış, en son en yakın kayıt seçilir.
    function yakinKayit(lat, lng, enCok, yalnizDarphane, adi) {
        var dA = enCok / 111, dO = dA / Math.cos(lat * Math.PI / 180);
        var url = '/api/index.php/v1/locations?ne_lat=' + (lat + dA).toFixed(4) + '&ne_lng=' + (lng + dO).toFixed(4)
            + '&sw_lat=' + (lat - dA).toFixed(4) + '&sw_lng=' + (lng - dO).toFixed(4) + '&lang=' + DIL + '&limit=200';
        return fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (j) {
                var en = null, enPuan = -1, enKm = Infinity;
                ((j && j.data) || []).forEach(function (l) {
                    if (yalnizDarphane && !l.has_coins) { return; }
                    var d = km(lat, lng, l.lat, l.lng);
                    if (d > enCok) { return; }
                    var p = (benzer(adi, l.name) ? 4 : 0) + (l.has_coins ? 2 : 0);
                    if (p > enPuan || (p === enPuan && d < enKm)) { en = l; enPuan = p; enKm = d; }
                });
                return en;
            });
    }
    function ozet(id) {
        if (ozetler[id] !== undefined) { return Promise.resolve(ozetler[id]); }
        return fetch('/api/index.php/v1/locations/' + encodeURIComponent(id) + '?lang=' + DIL, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { ozetler[id] = (d && d.data && d.data.summary) ? String(d.data.summary) : ''; return ozetler[id]; });
    }

    function ciz() {
        var g = google.maps;
        yer.style.display = 'none';

        var harita = new g.Map(KAP, {
            // dar ekranda (telefon) bir kademe uzaktan: bölgenin önemli kentleri sığsın
            center: nokta, zoom: KAP.offsetWidth < 600 ? 7 : 8, mapTypeId: 'terrain', disableDefaultUI: true, zoomControl: true,
            gestureHandling: 'cooperative', clickableIcons: false,
            styles: [{"featureType":"all","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"landscape.natural","elementType":"geometry","stylers":[{"color":"#e7c8ae"}]},{"featureType":"landscape.natural.landcover","elementType":"geometry","stylers":[{"color":"#f3e0c5"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#d1ad6f"}]},{"featureType":"road","stylers":[{"visibility":"off"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]},{"featureType":"administrative","stylers":[{"visibility":"off"}]},{"featureType":"transit","stylers":[{"visibility":"off"}]}]
        });

        function Isaret(konumu, el, katman) {
            this.konumu = new g.LatLng(konumu.lat, konumu.lng);
            this.el = el;
            this.katman = katman;
            if (katman !== 'overlayLayer') { g.OverlayView.preventMapHitsAndGesturesFrom(el); }
            this.setMap(harita);
        }
        Isaret.prototype = new g.OverlayView();
        Isaret.prototype.onAdd = function () { this.getPanes()[this.katman].appendChild(this.el); };
        Isaret.prototype.onRemove = function () { if (this.el.parentNode) { this.el.parentNode.removeChild(this.el); } };
        Isaret.prototype.draw = function () {
            var p = this.getProjection().fromLatLngToDivPixel(this.konumu);
            if (p) { this.el.style.left = p.x + 'px'; this.el.style.top = p.y + 'px'; }
        };

        function dugme(sinif, metin) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = sinif;
            b.innerHTML = '<span class="nmh-nokta" aria-hidden="true"></span><span class="nmh-ad"></span>';
            b.querySelector('.nmh-ad').textContent = metin;
            return b;
        }

        // ---- Bilgi kartı
        var kartEl = document.createElement('div');
        kartEl.className = 'nmh-kart';
        kartEl.setAttribute('role', 'dialog');
        kartEl.innerHTML = '<button type="button" class="nmh-kapat">&times;</button><span class="nmh-tur"></span><h3></h3>'
            + '<p class="nmh-ozet" aria-live="polite"></p><a class="nmh-bag"></a>';
        kartEl.querySelector('.nmh-kapat').setAttribute('aria-label', T.kapat);
        var kart = null, kartSira = 0;

        function kartAcik() { return !!(kart && kart.getMap()); }
        function kartKapat() { kartSira++; if (kartAcik()) { kart.setMap(null); } }
        function sonra(f) { requestAnimationFrame(function () { requestAnimationFrame(f); }); }
        function kartYaz(tur, baslik, metin, url) {
            kartEl.querySelector('.nmh-tur').textContent = tur;
            kartEl.querySelector('h3').textContent = baslik;
            kartEl.setAttribute('aria-label', baslik);
            var o = kartEl.querySelector('.nmh-ozet');
            o.textContent = metin || '';
            o.style.display = metin ? '' : 'none';
            var a = kartEl.querySelector('.nmh-bag');
            if (url) { a.href = url; a.textContent = T.bag; a.style.display = ''; } else { a.removeAttribute('href'); a.style.display = 'none'; }
        }
        // Kart harita kabından taşıyorsa haritayı BİR KEZ kaydır (içerik son hâlini alınca). Taşma ekrandan değil
        // haritanın koordinat dönüşümünden hesaplanır: panBy animasyonu sürerken ölçüp kaymaları biriktirmesin.
        var sigdirildi = false;
        function kartSigdir() {
            if (!kartAcik() || sigdirildi) { return; }
            var pr = kart.getProjection(), a = pr && pr.fromLatLngToContainerPixel(kart.konumu);
            var w = kartEl.offsetWidth, h = kartEl.offsetHeight;
            if (!a || !w || !h) { return; }
            var m = 10, ust = a.y - h - 18, sol = a.x - w / 2, sag = sol + w, dx = 0, dy = 0;
            if (ust < m) { dy = ust - m; }
            if (sol < m) { dx = sol - m; } else if (sag > KAP.offsetWidth - m) { dx = sag - (KAP.offsetWidth - m); }
            sigdirildi = true;
            if (dx || dy) { harita.panBy(Math.round(dx), Math.round(dy)); }
        }
        function kartAc(tur, baslik, konumu, enCok, yalnizDarphane) {
            kartKapat();
            var sira = kartSira;
            kartYaz(tur, baslik, T.ozetYukleniyor, '');
            sigdirildi = false;
            if (!kart) {
                kart = new Isaret(konumu, kartEl, 'floatPane');
            } else {
                kart.konumu = new g.LatLng(konumu.lat, konumu.lng);
                kart.setMap(harita);
            }
            yakinKayit(konumu.lat, konumu.lng, enCok, yalnizDarphane, baslik).then(function (l) {
                if (sira !== kartSira) { return; }
                if (!l) { kartYaz(tur, baslik, '', ''); sonra(kartSigdir); return; }
                kartYaz(tur, baslik, T.ozetYukleniyor, l.url || '');
                return ozet(l.id).then(function (s) {
                    if (sira === kartSira) { kartYaz(tur, baslik, s, l.url || ''); sonra(kartSigdir); }
                });
            }).catch(function () { if (sira === kartSira) { kartYaz(tur, baslik, '', ''); sonra(kartSigdir); } });
        }
        kartEl.querySelector('.nmh-kapat').addEventListener('click', kartKapat);
        harita.addListener('click', kartKapat);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && kartAcik()) { kartKapat(); } });

        // ---- Bölge adları (tıklanmaz)
        var bolgeler = BOLGELER.map(function (b) {
            var el = document.createElement('div');
            el.className = 'nmh-bolge';
            el.textContent = EN ? b[0] : b[1];
            el.setAttribute('aria-hidden', 'true');
            return { el: el, o: new Isaret({ lat: b[2], lng: b[3] }, el, 'overlayLayer') };
        });

        // ---- Önemli antik kentler: darphaneye en yakından uzağa; darphaneyle aynı yerdekiler (8 km) gösterilmez
        var kentler = (VERI.cities || []).map(function (c) {
            return { ad: c[0], lat: c[1], lng: c[2], d: km(nokta.lat, nokta.lng, c[1], c[2]) };
        }).filter(function (c) { return c.d > DARPHANE_KM; }).sort(function (a, b) { return a.d - b.d; }).map(function (c) {
            var el = dugme('nmh-kent nmh-gizli', c.ad);
            el.addEventListener('click', function () { kartAc(T.kent, c.ad, { lat: c.lat, lng: c.lng }, KENT_KM, false); });
            c.el = el;
            c.o = new Isaret({ lat: c.lat, lng: c.lng }, el, 'overlayMouseTarget');
            return c;
        });

        // ---- Sikkenin darphanesi
        var darphaneEl = dugme('nmh-darphane', darphaneAdi);
        darphaneEl.addEventListener('click', function () { kartAc(T.darphane, darphaneAdi, nokta, DARPHANE_KM, true); });
        new Isaret(nokta, darphaneEl, 'overlayMouseTarget');

        // ---- Adlar üst üste binmesin: darphane > bölge adları > kentler (yakından uzağa)
        function kesisir(a, b) { return a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom; }
        function pay(r, d) { return { left: r.left - d, top: r.top - d, right: r.right + d, bottom: r.bottom + d }; }
        var bekleyen = false, deneme = 0;
        function planla() {
            if (bekleyen) { return; }
            bekleyen = true;
            sonra(function () { bekleyen = false; yerlestir(); });
        }
        function yerlestir() {
            var c = KAP.getBoundingClientRect();
            var m = darphaneEl.getBoundingClientRect();
            // İlk idle, işaretler sayfaya çizilmeden gelebilir (boyutlar 0): çizilene dek sonraki kareye ertele
            if (!m.width || !c.width) { if (++deneme < 120) { planla(); } return; }
            deneme = 0;
            // Dolu sayılan alanlar: darphane, alttaki Google yazısı şeridi, sağ alttaki yakınlaştırma düğmeleri
            var dolu = [pay(m, 4),
                { left: c.left, top: c.bottom - 26, right: c.right, bottom: c.bottom },
                { left: c.right - 60, top: c.bottom - 130, right: c.right, bottom: c.bottom }];
            var ic = { left: c.left + 4, top: c.top + 4, right: c.right - 4, bottom: c.bottom - 4 };
            var sabit = dolu.slice();
            bolgeler.forEach(function (b) {
                var r = b.el.getBoundingClientRect();
                var gizle = r.left < ic.left || r.right > ic.right || r.top < ic.top || r.bottom > ic.bottom
                    || sabit.some(function (d) { return kesisir(r, d); });
                b.el.classList.toggle('nmh-gizli', gizle);
                if (!gizle) { dolu.push(r); }
            });
            kentler.forEach(function (k) {
                var g0 = k.el.getBoundingClientRect(), r = pay(g0, 3);
                // adı tamamen haritanın içinde olmayan kent gösterilmez (kenarda yarım ad kalmasın)
                var goster = g0.width > 0 && g0.left >= ic.left && g0.right <= ic.right && g0.top >= ic.top && g0.bottom <= ic.bottom
                    && !dolu.some(function (d) { return kesisir(r, d); });
                k.el.classList.toggle('nmh-gizli', !goster);
                if (goster) { dolu.push(r); }
            });
        }
        harita.addListener('idle', planla);
    }

    function baslat() {
        var g = window.google && window.google.maps;
        if (!g) { hata(); return; }
        var hazir = g.importLibrary ? Promise.all([g.importLibrary('core'), g.importLibrary('maps')]) : Promise.resolve();
        hazir.then(ciz).catch(hata);
    }

    function yukle() {
        if (window.google && window.google.maps && window.google.maps.Map) { baslat(); return; }
        window.__nmhSikkeHarita = baslat;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(KAP.getAttribute('data-key') || '')
            + '&loading=async&language=' + DIL + '&callback=__nmhSikkeHarita';
        s.onerror = hata;
        document.head.appendChild(s);
    }

    // Harita görünür alana yaklaşınca yüklenir: sayfa açılışında Google Maps isteği yok
    if ('IntersectionObserver' in window) {
        var gozcu = new IntersectionObserver(function (girdiler) {
            if (girdiler.some(function (e) { return e.isIntersecting; })) { gozcu.disconnect(); yukle(); }
        }, { rootMargin: '300px 0px' });
        gozcu.observe(KAP);
    } else {
        yukle();
    }
})();
</script>
</div>
