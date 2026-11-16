// mod_numistr_arama JS saf işlev testleri: node Module/tests/arama_js_test.js
'use strict';
const L = require('../mod_numistr_arama/media/js/arama.js');
let ok = 0, fail = 0;
function check(name, cond, info) {
  cond ? ok++ : fail++;
  console.log((cond ? '  ok   ' : '  FAIL ') + name + (cond || !info ? '' : ' -- ' + info));
}
const sTR = { bc: 'MÖ', ad: 'MS' }, sEN = { bc: 'BC', ad: 'AD' };

// bölge
check('bölge TR', L.regionLabel('mysia-coins', 'tr') === 'Misia');
check('bölge EN', L.regionLabel('mysia-coins', 'en') === 'Mysia');
check('bölge bilinmeyen', L.regionLabel('thrace-coins', 'tr') === 'Thrace');
check('bölge boş', L.regionLabel('', 'tr') === '');

// darphane
check('darphane tek sözcük', L.mintLabel('lampsacus') === 'Lampsacus');
check('darphane ad küçük kalır', L.mintLabel('prusa_ad_olympum') === 'Prusa ad Olympum');
check('darphane tire', L.mintLabel('soli-pompeiopolis') === 'Soli Pompeiopolis');

// malzeme
check('malzeme TR', L.materialLabel('electrum', 'tr') === 'elektrum');
check('malzeme EN', L.materialLabel('silver', 'en') === 'silver');
check('malzeme bilinmeyen olduğu gibi', L.materialLabel('Potin', 'tr') === 'potin');

// tarih
check('MÖ aralık', L.dateLabel(-520, -480, sTR, 'tr') === 'MÖ 520–480', L.dateLabel(-520, -480, sTR, 'tr'));
check('MS aralık', L.dateLabel(117, 138, sTR, 'tr') === 'MS 117–138');
check('MÖ→MS', L.dateLabel(-30, 14, sTR, 'tr') === 'MÖ 30 – MS 14', L.dateLabel(-30, 14, sTR, 'tr'));
check('EN BC', L.dateLabel(-520, -480, sEN, 'en') === '520–480 BC');
check('EN AD', L.dateLabel(117, 138, sEN, 'en') === 'AD 117–138');
check('EN BC→AD', L.dateLabel(-30, 14, sEN, 'en') === '30 BC – AD 14');
check('tek yıl', L.dateLabel(-336, null, sTR, 'tr') === 'MÖ 336');
check('tarih yok', L.dateLabel(null, null, sTR, 'tr') === '');

// vurgulama
const hp = (t, q) => L.highlightParts(t, q).map(p => (p.hit ? '[' + p.t + ']' : p.t)).join('');
check('vurgu: sözcük başı', hp("Kanatlı at ve çift yüzlü baş: Lampsakos'un", 'lampsakos') === "Kanatlı at ve çift yüzlü baş: [Lampsakos]'un");
check('vurgu: sözcük ortası DEĞİL', hp('Kanat açmış kartal', 'at') === 'Kanat açmış kartal');
check('vurgu: çok sözcük', hp('Sola dönük kanatlı atın ön yarısı', 'kanatlı at') === 'Sola dönük [kanatlı] [at]ın ön yarısı', hp('Sola dönük kanatlı atın ön yarısı', 'kanatlı at'));
check('vurgu: Türkçe büyük İ', hp('İSKENDER başı', 'iskender') === '[İSKENDER] başı');
check('vurgu: boş sorgu', hp('metin', '') === 'metin');
check('vurgu: uzunluk korunur', L.highlightParts('IŞIK ışık', 'ışık').map(p => p.t).join('') === 'IŞIK ışık');

// adres
check('buildUrl', L.buildUrl('/api/index.php/v1/search', 'kanatlı at', 'tr', 'coins', 12)
  === '/api/index.php/v1/search?q=kanatl%C4%B1%20at&lang=tr&type=coins&offset=12');
check('buildUrl offset 0 yok', L.buildUrl('/x', 'efes', 'en', 'all', 0) === '/x?q=efes&lang=en&type=all');
check('safeUrl https', L.safeUrl('https://www.numistr.org/a') === 'https://www.numistr.org/a');
check('safeUrl site içi', L.safeUrl('/tr/x') === '/tr/x');
check('safeUrl javascript reddedilir', L.safeUrl('javascript:alert(1)') === '');
check('safeUrl data reddedilir', L.safeUrl('data:text/html,x') === '');

console.log('\n' + ok + ' passed, ' + fail + ' failed');
process.exit(fail ? 1 : 0);
