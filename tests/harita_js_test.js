// mod_numistr_harita sikke haritası JS testleri (gerçek tarayıcı ve Google Maps olmadan): node Module/tests/harita_js_test.js
// Şablon PHP ile basılır, <script> içeriği sahte bir sayfada ve sahte google.maps ile çalıştırılır.
'use strict';
const { execFileSync } = require('child_process');
const vm = require('vm');
const path = require('path');

let ok = 0, fail = 0;
function check(name, cond, info) {
  cond ? ok++ : fail++;
  console.log((cond ? '  ok   ' : '  FAIL ') + name + (cond || info === undefined ? '' : ' -- ' + info));
}

const render = execFileSync('php', ['-r', `
  define('_JEXEC', 1);
  $anahtar = 'TEST key&1';
  include ${JSON.stringify(path.join(__dirname, '../mod_numistr_harita/tmpl/sikke.php'))};
`]).toString();
const SCRIPT = render.slice(render.indexOf('<script>') + 8, render.lastIndexOf('</script>'));

try { new vm.Script(SCRIPT); check('betik söz dizimi geçerli', true); } catch (e) { check('betik söz dizimi geçerli', false, e.message); }

// ---- en küçük sahte DOM
function el(tag) {
  const kids = {};
  const attrs = {};
  const cls = new Set();
  const e = {
    tagName: String(tag || 'div').toUpperCase(), style: {}, textContent: '', children: [], listeners: {},
    get className() { return [...cls].join(' '); },
    set className(v) { cls.clear(); String(v).split(/\s+/).filter(Boolean).forEach(c => cls.add(c)); },
    classList: {
      add: c => cls.add(c), remove: c => cls.delete(c), contains: c => cls.has(c),
      toggle: (c, f) => { const on = f === undefined ? !cls.has(c) : !!f; on ? cls.add(c) : cls.delete(c); return on; },
    },
    set innerHTML(v) { this._html = v; },
    get innerHTML() { return this._html || ''; },
    setAttribute(k, v) { attrs[k] = String(v); }, getAttribute(k) { return k in attrs ? attrs[k] : null; },
    removeAttribute(k) { delete attrs[k]; },
    querySelector(s) { return kids[s] || (kids[s] = el('span')); },
    appendChild(c) { this.children.push(c); c.parentNode = this; return c; },
    removeChild(c) { this.children = this.children.filter(x => x !== c); c.parentNode = null; },
    addEventListener(t, f) { (this.listeners[t] = this.listeners[t] || []).push(f); },
    getBoundingClientRect() { return { left: 0, top: 0, right: 0, bottom: 0, width: 0, height: 0 }; },
    closest() { return null; },
  };
  return e;
}

function sayfa({ lang = 'tr', yol = '/tr/anatolian-coins/aeolis-coins/1-x', satirlar = {}, gozcu = false, google = true, genislik, fetch } = {}) {
  const kap = el('div');
  if (genislik) { kap.offsetWidth = genislik; }
  kap.setAttribute('data-key', 'TEST key&1');
  const yer = el('div');
  kap.querySelector = s => (s === '.nmh-yer' ? yer : el('span'));
  const basliklar = Object.entries(satirlar).map(([t, v]) => {
    const b = el('div');
    b.textContent = '  ' + t + ' ';
    const icerik = el('div');
    icerik.textContent = v;
    const oge = el('div');
    oge.querySelector = s => (s === '.el-content' ? icerik : null);
    b.closest = s => (s === '.el-item' ? oge : null);
    return b;
  });
  const eklenen = [];
  const kayit = { harita: null, isaretler: [], dinleyici: {}, gozcuCb: null };

  const g = {
    LatLng: function (lat, lng) { this.lat = () => lat; this.lng = () => lng; },
    Map: function (k, o) { kayit.harita = { kap: k, o }; this.panBy = () => {}; this.addListener = (t, f) => { kayit.dinleyici[t] = f; }; },
    OverlayView: function () {},
  };
  g.OverlayView.preventMapHitsAndGesturesFrom = () => {};
  g.OverlayView.prototype.setMap = function (m) {
    this._map = m;
    if (m) { kayit.isaretler.push(this); this.onAdd(); this.draw(); } else { this.onRemove(); }
  };
  g.OverlayView.prototype.getMap = function () { return this._map || null; };
  g.OverlayView.prototype.getPanes = function () { return { overlayLayer: el(), overlayMouseTarget: el(), floatPane: el() }; };
  g.OverlayView.prototype.getProjection = function () { return { fromLatLngToDivPixel: () => ({ x: 1, y: 2 }), fromLatLngToContainerPixel: () => ({ x: 100, y: 50 }) }; };

  const window = {
    location: { pathname: yol },
    requestAnimationFrame: f => f(),
    fetch: fetch || (() => Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) })),
    Promise, setTimeout, console,
  };
  if (google) { window.google = { maps: g }; }
  if (gozcu) {
    window.IntersectionObserver = function (cb) { kayit.gozcuCb = cb; this.observe = () => {}; this.disconnect = () => {}; };
  }
  window.document = {
    documentElement: { lang },
    getElementById: id => (id === 'single-mint-map' ? kap : null),
    querySelectorAll: s => (s === '.el-title' ? basliklar : []),
    createElement: el,
    head: { appendChild: s => eklenen.push(s) },
    addEventListener: () => {},
  };
  window.window = window;
  vm.runInNewContext(SCRIPT, window);
  return { kap, yer, eklenen, kayit, window };
}
const bekle = () => new Promise(r => setImmediate(r));
const yakin = (a, b) => Math.abs(a - b) < 1e-6;

(async () => {
  // 1) 14983: koordinat ters yazılmış (boylam,enlem) → düzeltilir; anahtar biçimli ad okunur hâle gelir
  let s = sayfa({ satirlar: { 'Darphane': 'attaea', 'Darphane Koordinatları': '28.1159270,39.5159770' } });
  await bekle();
  let c = s.kayit.harita && s.kayit.harita.o.center;
  check('ters koordinat düzeltilir (14983)', c && yakin(c.lat, 39.515977) && yakin(c.lng, 28.115927), JSON.stringify(c));
  check('harita darphaneye 8 yakınlıkta açılır', s.kayit.harita && s.kayit.harita.o.zoom === 8);
  check('harita biçemi korunur (Map ID yok)', s.kayit.harita && Array.isArray(s.kayit.harita.o.styles) && !s.kayit.harita.o.mapId);
  const darphane = s.kayit.isaretler.find(i => i.el && i.el.classList.contains('nmh-darphane'));
  check('darphane işareti adıyla', darphane && darphane.el.querySelector('.nmh-ad').textContent === 'Attaea');
  const kentler = s.kayit.isaretler.filter(i => i.el && i.el.classList.contains('nmh-kent'));
  check('önemli kentler eklenir (130, darphaneye 8 km yakınlar hariç)', kentler.length > 100 && kentler.length <= 130, kentler.length);
  check('kentler önce gizli (yerleşimi idle belirler)', kentler.every(k => k.el.classList.contains('nmh-gizli')));
  check('idle dinleyicisi kurulur', typeof s.kayit.dinleyici.idle === 'function');
  const bolge = s.kayit.isaretler.filter(i => i.el && i.el.classList.contains('nmh-bolge'));
  check('20 bölge adı, TR sayfada Türkçe', bolge.length === 20 && bolge.some(b => b.el.textContent === 'AİOLİS'));

  // 2) düz koordinat olduğu gibi kalır
  s = sayfa({ yol: '/tr/anatolian-coins/lydia-coins/30671-x', satirlar: { 'Darphane': 'sardes', 'Darphane Koordinatları': '38.4875,28.0393' } });
  await bekle();
  c = s.kayit.harita.o.center;
  check('düz koordinat değişmez (Sardes)', yakin(c.lat, 38.4875) && yakin(c.lng, 28.0393), JSON.stringify(c));

  // 3) iki sıralama da Anadolu'da kalıyorsa sayfanın bölgesine yakın olan seçilir
  s = sayfa({ yol: '/tr/anatolian-coins/cappadocia-coins/1-x', satirlar: { 'Darphane': 'komana', 'Darphane Koordinatları': '36.400,38.417' } });
  await bekle();
  c = s.kayit.harita.o.center;
  check('belirsiz sırada bölgeye yakın olan (Kapadokya)', yakin(c.lat, 38.417) && yakin(c.lng, 36.4), JSON.stringify(c));
  s = sayfa({ yol: '/tr/anatolian-coins/cilicia-coins/1-x', satirlar: { 'Darphane': 'tarsus', 'Darphane Koordinatları': '36.917,34.895' } });
  await bekle();
  c = s.kayit.harita.o.center;
  check('belirsiz ama düz yazılmış koordinat korunur (Tarsus)', yakin(c.lat, 36.917) && yakin(c.lng, 34.895), JSON.stringify(c));

  // 4) EN sayfa: İngilizce başlıklar, dil parametresi, alt çizgili ad
  s = sayfa({ lang: 'en-GB', yol: '/en/anatolian-coins/bithynia-coins/1-x', satirlar: { 'Mint': 'prusa_ad_olympum', 'Mint Coordinates': '40.188,29.061' } });
  await bekle();
  const d2 = s.kayit.isaretler.find(i => i.el && i.el.classList.contains('nmh-darphane'));
  check('EN: ad "Prusa ad Olympum"', d2 && d2.el.querySelector('.nmh-ad').textContent === 'Prusa ad Olympum');
  check('EN: bölge adları İngilizce', s.kayit.isaretler.some(i => i.el && i.el.textContent === 'BITHYNIA'));

  // 5) zaten düzgün yazılmış ad korunur
  s = sayfa({ satirlar: { 'Darphane': 'Sardes (Sardis)', 'Darphane Koordinatları': '38.4875,28.0393' } });
  await bekle();
  const d3 = s.kayit.isaretler.find(i => i.el && i.el.classList.contains('nmh-darphane'));
  check('düzgün yazılmış ad korunur', d3 && d3.el.querySelector('.nmh-ad').textContent === 'Sardes (Sardis)');

  // 6) koordinat yok / geçersiz → kutu gizlenir, Maps yüklenmez
  s = sayfa({ google: false, satirlar: { 'Darphane': 'x' } });
  check('koordinat yoksa kutu gizlenir', s.kap.style.display === 'none' && s.eklenen.length === 0);
  s = sayfa({ google: false, satirlar: { 'Darphane': 'x', 'Darphane Koordinatları': '10,10' } });
  check('Anadolu dışı koordinatta kutu gizlenir', s.kap.style.display === 'none' && s.eklenen.length === 0);

  // 7) tembel yükleme: görünür alana yaklaşmadan Maps betiği eklenmez
  s = sayfa({ google: false, gozcu: true, satirlar: { 'Darphane': 'attaea', 'Darphane Koordinatları': '39.5,28.1' } });
  check('görünmeden Maps yüklenmez', s.eklenen.length === 0 && s.yer.textContent === 'Harita yükleniyor…');
  s.kayit.gozcuCb([{ isIntersecting: true }]);
  const src = s.eklenen[0] && s.eklenen[0].src;
  check('görünür olunca tek betik eklenir', s.eklenen.length === 1);
  check('betik adresi: anahtar kodlanmış, loading=async, dil, geri çağrı', src === 'https://maps.googleapis.com/maps/api/js?key=TEST%20key%261&loading=async&language=tr&callback=__nmhSikkeHarita', src);
  check('geri çağrı işlevi tanımlı', typeof s.window.__nmhSikkeHarita === 'function');

  // 8) aynı sayfada ikinci kez çalışmaz
  s = sayfa({ google: false, gozcu: true, satirlar: { 'Darphane': 'a', 'Darphane Koordinatları': '39.5,28.1' } });
  vm.runInNewContext(SCRIPT, s.window);
  check('ikinci çalıştırma yok sayılır', s.kap.getAttribute('data-nmh') === '1');

  // 9) dar ekranda (telefon) harita bir kademe uzaktan açılır
  s = sayfa({ genislik: 390, satirlar: { 'Darphane': 'attaea', 'Darphane Koordinatları': '39.5,28.1' } });
  await bekle();
  check('dar ekranda yakınlık 7', s.kayit.harita.o.zoom === 7, s.kayit.harita.o.zoom);

  // 10) kente tıklanınca aynı noktadaki kayıtlardan adı benzeyen + sikke basmış olan seçilir (Thyatira ≠ Mernouphyta)
  const istekler = [];
  s = sayfa({
    satirlar: { 'Darphane': 'attaea', 'Darphane Koordinatları': '39.5,28.1' },
    fetch: (u) => {
      istekler.push(u);
      const j = u.indexOf('/locations?') !== -1
        ? { data: [
            { id: 'LOC-1', name: 'Mernouphyta', lat: 38.918, lng: 27.84, has_coins: false, url: '/tr/x/1-mernouphyta' },
            { id: 'LOC-2', name: 'Thyateira', lat: 38.918, lng: 27.836, has_coins: true, url: '/tr/x/2-thyateira' },
            { id: 'LOC-3', name: 'Uzak', lat: 39.2, lng: 27.836, has_coins: true, url: '/tr/x/3' } ] }
        : { data: { summary: 'Lidya kenti.' } };
      return Promise.resolve({ ok: true, json: () => Promise.resolve(j) });
    },
  });
  await bekle();
  const thy = s.kayit.isaretler.find(i => i.el && i.el.classList.contains('nmh-kent') && i.el.querySelector('.nmh-ad').textContent === 'Thyatira');
  check('Thyatira kenti listede', !!thy);
  check('sayfa açılışında yerleşim isteği yok', istekler.length === 0, istekler.length);
  thy.el.listeners.click[0]();
  for (let i = 0; i < 5; i++) { await bekle(); }
  const kartI = s.kayit.isaretler.find(i => i.el && i.el.className === 'nmh-kart');
  check('kart açılır', !!kartI && kartI.getMap());
  check('kart başlığı ve türü', kartI && kartI.el.querySelector('h3').textContent === 'Thyatira' && kartI.el.querySelector('.nmh-tur').textContent === 'Antik kent');
  check('adı benzeyen darphane kaydı seçilir (Thyateira)', kartI && kartI.el.querySelector('.nmh-bag').href === '/tr/x/2-thyateira', kartI && kartI.el.querySelector('.nmh-bag').href);
  check('özet gelir', kartI && kartI.el.querySelector('.nmh-ozet').textContent === 'Lidya kenti.');
  check('özet isteği seçilen kayıt için', istekler.some(u => u.indexOf('/locations/LOC-2?lang=tr') !== -1), istekler.join(' | '));

  console.log('\n' + ok + ' passed, ' + fail + ' failed');
  process.exit(fail ? 1 : 0);
})();
