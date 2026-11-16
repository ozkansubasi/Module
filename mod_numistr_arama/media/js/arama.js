/*!
 * NumisTR site araması — sonuç sayfası (mod_numistr_arama 1.0.3, 16 Kas 2026)
 * Veri: GET /api/index.php/v1/search (plg_webservices_numistr 1.26.0). Toplam sonuç sayısı gösterilmez.
 * DOM yalnız textContent ile kurulur (API verisi HTML olarak basılmaz).
 * Saf işlevler Node ile test edilir: node Module/tests/arama_js_test.js
 */
(function (root, factory) {
  'use strict';
  var lib = factory();
  if (typeof module === 'object' && module.exports) {
    module.exports = lib;
  } else {
    root.NumisTRAramaLib = lib;
    if (root.document) {
      if (root.document.readyState === 'loading') {
        root.document.addEventListener('DOMContentLoaded', function () { lib.boot(root); });
      } else {
        lib.boot(root);
      }
    }
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var REGIONS = {
    'pisidia-coins': ['Pisidya', 'Pisidia'], 'lydia-coins': ['Lidya', 'Lydia'], 'ionia-coins': ['İyonya', 'Ionia'],
    'caria-coins': ['Karya', 'Caria'], 'lycia-coins': ['Likya', 'Lycia'], 'phrygia-coins': ['Frigya', 'Phrygia'],
    'mysia-coins': ['Misia', 'Mysia'], 'bithynia-coins': ['Bitinya', 'Bithynia'], 'pamphylia-coins': ['Pamfilya', 'Pamphylia'],
    'cilicia-coins': ['Kilikya', 'Cilicia'], 'cappadocia-coins': ['Kapadokya', 'Cappadocia'], 'galatia-coins': ['Galatya', 'Galatia'],
    'troas-coins': ['Troas', 'Troas'], 'aeolis-coins': ['Aiolis', 'Aeolis'], 'paphlagonia-coins': ['Paflagonya', 'Paphlagonia'],
    'pontus-coins': ['Pontus', 'Pontus'], 'other-ancient-regions-coins': ['Diğer antik bölgeler', 'Other ancient regions']
  };
  var MATERIALS = {
    electrum: ['elektrum', 'electrum'], silver: ['gümüş', 'silver'], gold: ['altın', 'gold'], bronze: ['bronz', 'bronze'],
    billon: ['biyon', 'billon'], lead: ['kurşun', 'lead'], iron: ['demir', 'iron'], orichalcum: ['oreikhalkos', 'orichalcum']
  };
  var SMALL_WORDS = { ad: 1, de: 1, et: 1, sub: 1, in: 1 };

  function li(lang) { return lang === 'en' ? 1 : 0; }

  function regionLabel(code, lang) {
    code = String(code || '');
    if (REGIONS[code]) { return REGIONS[code][li(lang)]; }
    var s = code.replace(/-coins$/, '').replace(/[-_]+/g, ' ').trim();
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
  }

  /** "prusa_ad_olympum" → "Prusa ad Olympum", "lampsacus" → "Lampsacus" */
  function mintLabel(m) {
    var words = String(m || '').replace(/[_-]+/g, ' ').trim().split(/\s+/);
    return words.filter(Boolean).map(function (w, i) {
      var lw = w.toLowerCase();
      return (i > 0 && SMALL_WORDS[lw]) ? lw : lw.charAt(0).toUpperCase() + lw.slice(1);
    }).join(' ');
  }

  function materialLabel(k, lang) {
    k = String(k || '').toLowerCase();
    return MATERIALS[k] ? MATERIALS[k][li(lang)] : k;
  }

  /** (-520,-480,s) → "MÖ 520–480"; (-30,14) → "MÖ 30 – MS 14"; (117,138) → "MS 117–138"; EN: "520–480 BC", "AD 117–138" */
  function dateLabel(from, to, s, lang) {
    var en = lang === 'en';
    var isNum = function (v) { return typeof v === 'number' && isFinite(v); };
    if (!isNum(from) && !isNum(to)) { return ''; }
    if (!isNum(from)) { from = to; }
    if (!isNum(to)) { to = from; }
    var era = function (v) { return v < 0 ? s.bc : s.ad; };
    var abs = function (v) { return String(Math.abs(v)); };
    var span = from === to ? abs(from) : abs(from) + '–' + abs(to);
    if ((from < 0) === (to < 0)) {
      return en ? (from < 0 ? span + ' ' + s.bc : s.ad + ' ' + span) : era(from) + ' ' + span;
    }
    return en ? abs(from) + ' ' + s.bc + ' – ' + s.ad + ' ' + abs(to) : s.bc + ' ' + abs(from) + ' – ' + s.ad + ' ' + abs(to);
  }

  function trLower(str) {
    return String(str).replace(/I/g, 'ı').replace(/İ/g, 'i').toLowerCase();
  }

  /**
   * Metni, sorgu sözcüklerinin (≥2 harf) SÖZCÜK BAŞINDAKİ geçişlerine göre parçalar:
   * [{t:'...', hit:false}, {t:'Lampsakos', hit:true}, ...]. Uzunluk korunur (trLower birebir).
   */
  function highlightParts(text, q) {
    text = String(text || '');
    var terms = trLower(String(q || '')).split(/\s+/).filter(function (w) { return w.length >= 2; });
    if (!text || !terms.length) { return [{ t: text, hit: false }]; }
    var low = trLower(text);
    var marks = [];
    terms.forEach(function (t) {
      var from = 0, i;
      while ((i = low.indexOf(t, from)) !== -1) {
        var prev = i > 0 ? low.charAt(i - 1) : ' ';
        if (!/[0-9a-zçğıöşüâîû]/i.test(prev)) { marks.push([i, i + t.length]); }
        from = i + t.length;
      }
    });
    if (!marks.length) { return [{ t: text, hit: false }]; }
    marks.sort(function (a, b) { return a[0] - b[0]; });
    var merged = [];
    marks.forEach(function (m) {
      var last = merged[merged.length - 1];
      if (last && m[0] <= last[1]) { last[1] = Math.max(last[1], m[1]); } else { merged.push(m.slice()); }
    });
    var out = [], pos = 0;
    merged.forEach(function (m) {
      if (m[0] > pos) { out.push({ t: text.slice(pos, m[0]), hit: false }); }
      out.push({ t: text.slice(m[0], m[1]), hit: true });
      pos = m[1];
    });
    if (pos < text.length) { out.push({ t: text.slice(pos), hit: false }); }
    return out;
  }

  function buildUrl(api, q, lang, type, offset) {
    var sep = api.indexOf('?') === -1 ? '?' : '&';
    return api + sep + 'q=' + encodeURIComponent(q) + '&lang=' + (lang === 'en' ? 'en' : 'tr')
      + '&type=' + encodeURIComponent(type || 'all') + (offset ? '&offset=' + (offset | 0) : '');
  }

  /** "Ön yüz: Kanatlı at" + "Arka yüz: …" → "Ön yüz: Kanatlı at. Arka yüz: …" (zaten noktalıysa ek nokta yok). */
  function joinDesc(a, b) {
    a = String(a || '').trim(); b = String(b || '').trim();
    if (!a || !b) { return a || b; }
    return (/[.!?…;:]$/.test(a) ? a : a + '.') + ' ' + b;
  }

  /** Sorgu temizliği (sunucudaki cleanQuery ile aynı): kontrol karakteri yok, boşluklar tek, en çok 60 karakter. */
  function cleanQuery(q) {
    var t = String(q === null || q === undefined ? '' : q).replace(/[\u0000-\u001F\u007F]+/g, ' ').replace(/\s+/g, ' ').trim();
    return Array.from(t).slice(0, 60).join('').trim();
  }

  /** Yalnız http(s) adresleri bağlantı/görsel olur. */
  function safeUrl(u) {
    u = String(u || '');
    return /^https?:\/\//i.test(u) || u.charAt(0) === '/' ? u : '';
  }

  // ------------------------------------------------------------------ DOM
  function el(doc, tag, cls, text) {
    var n = doc.createElement(tag);
    if (cls) { n.className = cls; }
    if (text !== undefined && text !== null) { n.textContent = text; }
    return n;
  }

  function appendHighlighted(doc, parent, text, q) {
    highlightParts(text, q).forEach(function (p) {
      if (p.hit) { parent.appendChild(el(doc, 'mark', 'nma-hit', p.t)); } else { parent.appendChild(doc.createTextNode(p.t)); }
    });
  }

  function thumb(doc, src, alt, cls) {
    var box = el(doc, 'div', cls);
    var s = safeUrl(src);
    if (s) {
      var img = doc.createElement('img');
      img.src = s; img.alt = alt || ''; img.loading = 'lazy'; img.decoding = 'async';
      box.appendChild(img);
    } else {
      box.classList.add('nma-thumb--bos');
    }
    return box;
  }

  function coinRow(doc, it, cfg) {
    var s = cfg.s, lang = cfg.lang, q = cfg.q;
    var a = el(doc, 'a', 'nma-coin');
    a.href = safeUrl(it.url) || '#';
    a.appendChild(thumb(doc, it.thumb_url, it.title, 'nma-thumb'));
    var body = el(doc, 'div', 'nma-coin-body');
    var l1 = el(doc, 'span', 'nma-coin-ad');
    var parts = [];
    if (it.mint) { parts.push(mintLabel(it.mint)); }
    if (it.material) { parts.push(materialLabel(it.material, lang)); }
    if (it.mint && it.match === 'mint') {
      // darphane eşleşmesi: ad Latince yazılsa da (lampsakos → Lampsacus) vurgulanır
      l1.appendChild(el(doc, 'mark', 'nma-hit', parts[0]));
      if (parts.length > 1) { l1.appendChild(doc.createTextNode(' · ' + parts.slice(1).join(' · '))); }
    } else {
      appendHighlighted(doc, l1, parts.join(' · ') || it.title, q);
    }
    body.appendChild(l1);
    var meta = [];
    if (it.authority) { meta.push(it.authority); }
    var d = dateLabel(it.date_from, it.date_to, s, lang);
    if (d) { meta.push(d); }
    var r = regionLabel(it.region, lang);
    if (r) { meta.push(r); }
    if (meta.length) {
      var l2 = el(doc, 'span', 'nma-coin-meta');
      appendHighlighted(doc, l2, meta.join(' · '), q);
      body.appendChild(l2);
    }
    var desc = joinDesc(it.obverse ? s.obverse + ': ' + it.obverse : '', it.reverse ? s.reverse + ': ' + it.reverse : '');
    if (desc) {
      var l3 = el(doc, 'span', 'nma-coin-desc');
      appendHighlighted(doc, l3, desc, q);
      body.appendChild(l3);
    }
    if (parts.length) {
      var l4 = el(doc, 'span', 'nma-coin-ref');
      appendHighlighted(doc, l4, it.title, q);
      body.appendChild(l4);
    }
    a.appendChild(body);
    return a;
  }

  function settlementCard(doc, it, cfg) {
    var a = el(doc, 'a', 'nma-kart');
    a.href = safeUrl(it.url) || '#';
    var body = el(doc, 'div', 'nma-kart-body');
    var t = el(doc, 'span', 'nma-kart-baslik');
    appendHighlighted(doc, t, it.title, cfg.q);
    body.appendChild(t);
    if (it.region) { body.appendChild(el(doc, 'span', 'nma-kart-meta', it.region)); }
    if (it.summary) { body.appendChild(el(doc, 'span', 'nma-kart-ozet', it.summary)); }
    a.appendChild(body);
    return a;
  }

  function articleCard(doc, it, cfg) {
    var a = el(doc, 'a', 'nma-kart nma-kart--yazi');
    a.href = safeUrl(it.url) || '#';
    a.appendChild(thumb(doc, it.image, '', 'nma-thumb'));
    var body = el(doc, 'div', 'nma-kart-body');
    if (it.date) {
      var dt = new Date(it.date + 'T12:00:00Z');
      var txt = isNaN(dt) ? it.date : dt.toLocaleDateString(cfg.lang === 'en' ? 'en-GB' : 'tr-TR', { day: 'numeric', month: 'long', year: 'numeric' });
      body.appendChild(el(doc, 'span', 'nma-kart-tarih', txt));
    }
    var t = el(doc, 'span', 'nma-kart-baslik');
    appendHighlighted(doc, t, it.title, cfg.q);
    body.appendChild(t);
    a.appendChild(body);
    return a;
  }

  var SECTIONS = [
    { key: 'coins', h: 'h_coins', render: coinRow, list: 'nma-liste' },
    { key: 'settlements', h: 'h_settlements', render: settlementCard, list: 'nma-kartlar' },
    { key: 'articles', h: 'h_articles', render: articleCard, list: 'nma-kartlar' }
  ];

  function init(win, cfg) {
    var doc = win.document;
    var rootEl = doc.getElementById(cfg.root);
    if (!rootEl) { return; }
    var s = cfg.s;
    var tabsEl = rootEl.querySelector('.nma-tabs');
    var outEl = rootEl.querySelector('.nma-sonuclar');
    var params = new win.URLSearchParams(win.location.search);
    var state = { type: ({ coins: 1, settlements: 1, articles: 1 })[params.get('tur')] ? params.get('tur') : 'all', data: null, busy: false };

    // Sorgu ADRESTEN okunur: Joomla makale önbelleği sayfayı ilk sorguyla saklayabiliyor (16 Kas canlı bulgu),
    // sunucunun bastığı cfg.q ve kutu değeri başka bir aramaya ait olabilir.
    cfg.q = cleanQuery(params.get('q') !== null ? params.get('q') : cfg.q);
    var input = rootEl.querySelector('.nma-input');
    if (input) { input.value = cfg.q; }

    if (cfg.q.length < 2) { message(s.short); return; }

    function setTab(type) {
      state.type = type;
      try {
        var p = new win.URLSearchParams(win.location.search);
        p.set('q', cfg.q);
        if (type === 'all') { p.delete('tur'); } else { p.set('tur', type); }
        win.history.replaceState(null, '', win.location.pathname + '?' + p.toString());
      } catch (e) { /* adres güncellenemezse sorun değil */ }
      drawTabs();
      load(type, 0, false);
    }

    function drawTabs() {
      tabsEl.textContent = '';
      [['all', s.tab_all], ['coins', s.tab_coins], ['settlements', s.tab_settlements], ['articles', s.tab_articles]].forEach(function (t) {
        var b = el(doc, 'button', 'nma-tab' + (state.type === t[0] ? ' nma-tab--etkin' : ''), t[1]);
        b.type = 'button';
        b.setAttribute('aria-pressed', state.type === t[0] ? 'true' : 'false');
        b.addEventListener('click', function () { if (state.type !== t[0] && !state.busy) { setTab(t[0]); } });
        tabsEl.appendChild(b);
      });
    }

    function message(text, cls) {
      outEl.textContent = '';
      outEl.appendChild(el(doc, 'p', cls || 'nma-not', text));
    }

    function tip() {
      var box = el(doc, 'div', 'nma-ipucu');
      box.appendChild(el(doc, 'strong', '', s.tip_title));
      box.appendChild(el(doc, 'span', '', s.tip));
      return box;
    }

    function draw() {
      var d = state.data;
      outEl.textContent = '';
      var any = false;
      SECTIONS.forEach(function (sec) {
        if (state.type !== 'all' && state.type !== sec.key) { return; }
        var block = d[sec.key] || { items: [] };
        if (!block.items || !block.items.length) { return; }
        any = true;
        var wrap = el(doc, 'section', 'nma-bolum nma-bolum--' + sec.key);
        wrap.appendChild(el(doc, 'h2', 'nma-h', s[sec.h]));
        var list = el(doc, 'div', sec.list);
        block.items.forEach(function (it) { list.appendChild(sec.render(doc, it, cfg)); });
        wrap.appendChild(list);
        if (block.has_more) {
          var more = el(doc, 'button', 'nma-daha', state.type === 'all'
            ? (sec.key === 'coins' ? s.all_coins : s.more) : (sec.key === 'coins' ? s.more_coins : s.more));
          more.type = 'button';
          more.addEventListener('click', function () {
            if (state.busy) { return; }
            if (state.type === 'all') { setTab(sec.key); } else { load(sec.key, block.next_offset || 0, true); }
          });
          wrap.appendChild(more);
        }
        outEl.appendChild(wrap);
      });
      if (!any) { outEl.appendChild(el(doc, 'p', 'nma-bos', s.empty)); }
      outEl.appendChild(tip());
    }

    function load(type, offset, append) {
      state.busy = true;
      if (!append) { message(s.loading, 'nma-not nma-yukleniyor'); }
      win.fetch(buildUrl(cfg.api, cfg.q, cfg.lang, type, offset), { headers: { Accept: 'application/json' }, credentials: 'omit' })
        .then(function (r) {
          if (r.status === 429) { throw { rate: true }; }
          if (!r.ok) { throw { http: r.status }; }
          return r.json();
        })
        .then(function (j) {
          var d = (j && j.data) || {};
          if (append && state.data && state.data[type]) {
            var old = state.data[type];
            old.items = old.items.concat((d[type] && d[type].items) || []);
            old.has_more = !!(d[type] && d[type].has_more);
            old.next_offset = d[type] ? d[type].next_offset : null;
          } else {
            state.data = d;
          }
          state.busy = false;
          draw();
        })
        .catch(function (e) {
          state.busy = false;
          message(e && e.rate ? s.rate : s.error, 'nma-not nma-hata');
        });
    }

    drawTabs();
    load(state.type, 0, false);
  }

  function boot(win) {
    var list = win.NumisTRAramaConfig || [];
    for (var i = 0; i < list.length; i++) {
      if (!list[i] || list[i]._booted) { continue; }
      list[i]._booted = true;
      try { init(win, list[i]); } catch (e) { /* modül hatası sayfayı bozmaz */ }
    }
  }

  return {
    regionLabel: regionLabel, mintLabel: mintLabel, materialLabel: materialLabel, dateLabel: dateLabel,
    highlightParts: highlightParts, joinDesc: joinDesc, cleanQuery: cleanQuery, buildUrl: buildUrl, safeUrl: safeUrl, trLower: trLower, boot: boot
  };
});
