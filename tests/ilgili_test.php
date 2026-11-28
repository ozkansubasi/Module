<?php
/**
 * mod_numistr_ilgili saf mantık testleri (DB'siz, Joomla'sız): php Module/tests/ilgili_test.php
 * Etiket alias'ı ↔ darphane anahtarı ↔ hükümdar adı dönüşümleri, sayfa türü, etiket grupları, görselli sıralama,
 * eşleme verisinin tutarlılığı.
 */

define('_JEXEC', 1);
require __DIR__ . '/../mod_numistr_ilgili/helper.php';

$H = 'ModNumistrIlgiliHelper';
$gecti = $kaldi = 0;

function check($ad, $kosul, $ayrinti = '')
{
    global $gecti, $kaldi;
    $kosul ? $gecti++ : $kaldi++;
    echo ($kosul ? '  ok   ' : '  FAIL ') . $ad . ($kosul || $ayrinti === '' ? '' : ' -- ' . $ayrinti) . "\n";
}

// ---- sade (hükümdar etiketi alias'ı)
check('Alexander III of Macedon', $H::sade('Alexander III of Macedon') === 'alexander-iii-of-macedon');
check('boşluk + büyük harf', $H::sade('  Antiochus II Theos ') === 'antiochus-ii-theos');
check('Türkçe harfler', $H::sade('Büyük İskender Çağı Şahı Ğ') === 'buyuk-iskender-cagi-sahi-g');
check('noktalama', $H::sade('Ptolemy I (Soter), King') === 'ptolemy-i-soter-king');
check('boş', $H::sade('') === '');

// ---- darphane anahtarı ↔ yer etiketi
$bilinen = array('magnesia_ad_maeandrum', 'soli-pompeiopolis', 'cyzicus');
check('alt çizgi → tire', $H::yerEtiketi('magnesia_ad_maeandrum') === 'magnesia-ad-maeandrum');
check('tire → bilinen anahtar (alt çizgili)', $H::darphaneAnahtari('magnesia-ad-maeandrum', $bilinen) === 'magnesia_ad_maeandrum');
check('tireli anahtar korunur (soli-pompeiopolis)', $H::darphaneAnahtari('soli-pompeiopolis', $bilinen) === 'soli-pompeiopolis');
check('büyük harf alias', $H::darphaneAnahtari('Cyzicus', $bilinen) === 'cyzicus');
check('bilinmeyen alias → tire alt çizgi', $H::darphaneAnahtari('nea-polis', $bilinen) === 'nea_polis');

// ---- eşleme verisi
$h = $H::harita();
check('eşleme yüklendi (156 darphane)', count($h) === 156, (string) count($h));
check('her darphane en az bir yerleşim', !array_filter($h, function ($v) { return !$v; }));
$t = $H::tersHarita($h);
check('ters eşleme 160 yerleşim', count($t) === 160, (string) count($t));
check('Cyzicus 31960 → cyzicus', in_array('cyzicus', $t[31960] ?? array(), true));
$etiketler = array_map(array($H, 'yerEtiketi'), array_keys($h));
check('yer etiketi aliasları çakışmıyor', count($etiketler) === count(array_unique($etiketler)));
$geri = array_filter(array_keys($h), function ($d) use ($H) { return $H::darphaneAnahtari($H::yerEtiketi($d)) !== $d; });
check('her darphane etiket üzerinden geri döner', !$geri, implode(',', $geri));
check('anahtarlar küçük harf ve sade', !array_filter(array_keys($h), function ($d) { return !preg_match('/^[a-z0-9_-]+$/', $d); }));

// ---- sayfa türü
check('sikke (mint-name)', $H::sayfaTuru(array('mint-name' => 'cyzicus'), array()) === 'sikke');
check('sikke önceliği (iki alan da dolu)', $H::sayfaTuru(array('mint-name' => 'x', 'loc-id' => 'LOC-1'), array()) === 'sikke');
check('yerleşim (loc-id)', $H::sayfaTuru(array('loc-id' => 'LOC-1270'), array()) === 'yerlesim');
check('boş mint-name yerleşimi bozmaz', $H::sayfaTuru(array('mint-name' => '  ', 'loc-id' => 'LOC-1'), array()) === 'yerlesim');
check('yazı (yer etiketi)', $H::sayfaTuru(array(), array(array('alias' => 'sardes', 'ust' => 'yerler'))) === 'yazi');
check('yazı (hükümdar etiketi)', $H::sayfaTuru(array(), array(array('alias' => 'x', 'ust' => 'hukumdarlar'))) === 'yazi');
check('ilgisiz etiket → blok yok', $H::sayfaTuru(array(), array(array('alias' => 'darphane-belirsiz', 'ust' => 'root'))) === '');
check('hiçbiri → blok yok', $H::sayfaTuru(array(), array()) === '');

// ---- dil
check('EN sayfa → en-GB sikkeler', $H::sikkeDilleri('en-GB') === array('en-GB'));
check('TR sayfa → * + tr-TR', $H::sikkeDilleri('tr-TR') === array('*', 'tr-TR'));

// ---- etiket grupları
$g = $H::etiketGruplari(array(
    array('alias' => 'sardes', 'ust' => 'yerler'), array('alias' => 'alexander-iii-of-macedon', 'ust' => 'hukumdarlar'),
    array('alias' => 'sardes', 'ust' => 'yerler'), array('alias' => 'darphane-belirsiz', 'ust' => 'root'), array('alias' => '', 'ust' => 'yerler'),
));
check('gruplar ayrışır, tekrar yok, ilgisiz atlanır', $g === array('yer' => array('sardes'), 'hukumdar' => array('alexander-iii-of-macedon'), 'bolge' => array(), 'donem' => array()));

// ---- görselli önde
$s = $H::gorselliOnde(array(array('id' => 1, 'gorsel' => null), array('id' => 2, 'gorsel' => 'u2'), array('id' => 3, 'gorsel' => 'u3'),
    array('id' => 4, 'gorsel' => null)), 3);
check('görselli önde, sıra korunur, sınır', array_column($s, 'id') === array(2, 3, 1));


// ---- 1.1.0: blog yazısı yardımcıları
check('kategori listesi boş → varsayılan 12', count($H::kategoriListesi('')) === 12 && in_array(39, $H::kategoriListesi(''), true));
check('kategori listesi ayrıştırma', $H::kategoriListesi(' 35, 36;x,,40 ') === array(35, 36, 40));
check('kelime sayısı HTML', $H::kelimeSayisi('<p>Bir iki <b>üç</b></p><script>var a=1 2 3;</script><p>dört&nbsp;beş</p>') === 5);
check('kelime sayısı boş', $H::kelimeSayisi('<p> </p>') === 0);
check('okuma süresi en az 1', $H::okumaSuresi(0) === 1 && $H::okumaSuresi(150) === 1);
check('okuma süresi yuvarlama', $H::okumaSuresi(401) === 3 && $H::okumaSuresi(400) === 2);
check('okuma süresi hız', $H::okumaSuresi(600, 300) === 2);
check('giriş görseli joomlaImage eki', $H::introGorsel('{"image_intro":"images/a b.jpg#joomlaImage://local-images/a.jpg?width=1&height=1"}') === '');
check('giriş görseli yerel yol köke', $H::introGorsel('{"image_intro":"images/blog/a.jpg#joomlaImage://x"}') === '/images/blog/a.jpg');
check('giriş görseli tam adres', $H::introGorsel('{"image_intro":"https://www.numistr.org/index.php?option=com_numistr&view=gorsel&id=5&wm=0"}') === 'https://www.numistr.org/index.php?option=com_numistr&view=gorsel&id=5&wm=0');
check('giriş görseli yok / bozuk JSON', $H::introGorsel('{"image_intro":""}') === '' && $H::introGorsel('x') === '');
check('arama adresi TR', $H::aramaUrl('tr-TR', 'Kroisos') === '/tr/arama?q=Kroisos');
check('arama adresi EN + kodlama', $H::aramaUrl('en-GB', 'Alyattes I of Lydia') === '/en/search?q=Alyattes%20I%20of%20Lydia');
check('uygulama adresi utm', $H::uygulamaUrl('tr-TR', 13034) === '/tr/app?utm_source=numistr&utm_medium=blog&utm_campaign=blog-cta&utm_content=13034');
check('uygulama adresi EN', strpos($H::uygulamaUrl('en-GB', 1), '/en/app?') === 0);

// --- C18-D: bölge / dönem etiketleri
check('notCoz', $H::notCoz('en=Lydia;kat=17') === array('en' => 'Lydia', 'kat' => '17'));
check('notCoz boş / bozuk', $H::notCoz('') === array() && $H::notCoz('x;=y;a=b=c') === array('a' => 'b=c'));
$lid = array('id' => 303, 'alias' => 'bolge-lydia', 'ad' => 'Lidya', 'not' => 'en=Lydia;kat=17', 'ust' => 'bolgeler');
$ark = array('id' => 319, 'alias' => 'donem-arkaik', 'ad' => 'Arkaik', 'not' => 'en=Archaic;aralik=MÖ ~650–480', 'ust' => 'donemler');
check('etiketAdi TR', $H::etiketAdi($lid, 'tr-TR') === 'Lidya');
check('etiketAdi EN', $H::etiketAdi($lid, 'en-GB') === 'Lydia');
check('etiketAdi EN notsuz → başlık', $H::etiketAdi(array('ad' => 'Troas', 'not' => ''), 'en-GB') === 'Troas');
$g2 = $H::etiketGruplari(array($lid, $ark, $lid, array('alias' => 'sardes', 'ust' => 'yerler')));
check('gruplar: bölge tekil', count($g2['bolge']) === 1 && $g2['bolge'][0]['id'] === 303);
check('gruplar: dönem', count($g2['donem']) === 1 && $g2['donem'][0]['alias'] === 'donem-arkaik');
check('gruplar: yer korunur', $g2['yer'] === array('sardes') && $g2['hukumdar'] === array());
check('sayfaTuru: yalnız bölge/dönem etiketi yazı yapmaz', $H::sayfaTuru(array(), array($lid, $ark)) === '');

// --- 1.3.0: video bloğu
check('sureYaz 52', $H::sureYaz(52) === '0:52');
check('sureYaz 125', $H::sureYaz(125) === '2:05');
check('sureYaz boş', $H::sureYaz(null) === '' && $H::sureYaz(0) === '');
check('gizlilik TR/EN', $H::gizlilikUrl('tr-TR') === '/tr/kvkk-ve-gizlilik-politikasi' && $H::gizlilikUrl('en-GB') === '/en/privacy-policy-gdpr');
$vs = array('youtube_id' => 'dQw4w9WgXcQ', 'bicim' => 'shorts', 'baslik' => ' Zela ', 'aciklama' => 'Üç sözcük',
    'kunye' => 'Berlin · Brahms', 'sure_sn' => 52, 'kucuk_resim' => 'images/videolar/dQw4w9WgXcQ.jpg', 'yayin_tarihi' => '2026-11-27 16:30:00');
$vo = $H::videoOgesi($vs, 'https://www.numistr.org/');
check('video öğesi: resim tam adres', $vo['resim'] === 'https://www.numistr.org/images/videolar/dQw4w9WgXcQ.jpg');
check('video öğesi: shorts izleme adresi', $vo['izle'] === 'https://www.youtube.com/shorts/dQw4w9WgXcQ');
check('video öğesi: başlık kırpılır, süre', $vo['baslik'] === 'Zela' && $vo['sure'] === '0:52' && $vo['sure_sn'] === 52);
$vv = $H::videoOgesi(array('bicim' => 'video') + $vs, 'https://x');
check('video öğesi: 16:9 izleme adresi', $vv['bicim'] === 'video' && $vv['izle'] === 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
check('video öğesi: bilinmeyen biçim → shorts', $H::videoOgesi(array('bicim' => 'x') + $vs, '')['bicim'] === 'shorts');
check('video öğesi: geçersiz kimlik → null', $H::videoOgesi(array('youtube_id' => 'abc"><x') + $vs, '') === null);
check('video öğesi: dış resim yolu reddedilir', $H::videoOgesi(array('kucuk_resim' => 'https://evil/x.jpg') + $vs, 'https://x')['resim'] === ''
    && $H::videoOgesi(array('kucuk_resim' => 'images/videolar/../../configuration.php') + $vs, 'https://x')['resim'] === '');
$ld = $H::videoJsonLd($vo);
check('JSON-LD alanları', $ld['@type'] === 'VideoObject' && $ld['uploadDate'] === '2026-11-27T16:30:00+00:00'
    && $ld['duration'] === 'PT52S' && $ld['embedUrl'] === 'https://www.youtube.com/embed/dQw4w9WgXcQ' && $ld['description'] === 'Üç sözcük');
check('JSON-LD: tarih yoksa null', $H::videoJsonLd(array('tarih' => '') + $vo) === null);
check('JSON-LD: resim yoksa null', $H::videoJsonLd(array('resim' => '') + $vo) === null);
check('JSON-LD: açıklama yoksa başlık', $H::videoJsonLd(array('aciklama' => '') + $vo)['description'] === 'Zela');

// --- 1.4.0: sikke sayfası yazı seçimi (dönem süzgeci + sıra; örneklem 2026-11-28)
check('dönem: Sardes MÖ 323–319 → helenistik', $H::sikkeDonemleri('-0323', '-0319') === array('helenistik'));
check('dönem: İskender MÖ 328–323 → yalnız helenistik', $H::sikkeDonemleri('-0328', '-0323') === array('helenistik'));
check('dönem: Tarsos MÖ 400–380 → klasik', $H::sikkeDonemleri('-0400', '-0380') === array('klasik'));
check('dönem: Phokaia MÖ 600–522 → arkaik', $H::sikkeDonemleri('-0600', '-0522') === array('arkaik'));
check('dönem: MÖ 88–30 → helenistik + roma', $H::sikkeDonemleri('-0088', '-0030') === array('helenistik', 'roma'));
check('dönem: MS 337–340 → roma', $H::sikkeDonemleri('0337', '0340') === array('roma'));
check('dönem: MS 600 → bizans', $H::sikkeDonemleri('0600', '') === array('bizans'));
check('dönem: MÖ 480 sınırı → klasik', $H::sikkeDonemleri('-0480', '-0480') === array('klasik'));
check('dönem: ters aralık düzeltilir', $H::sikkeDonemleri('-0200', '-0250') === array('helenistik'));
check('dönem: başlangıç yok / sayı değil → boş', $H::sikkeDonemleri('', '-0300') === array() && $H::sikkeDonemleri('abc', '') === array());
check('dönem uyar: kesişim', $H::donemUyar(array('helenistik', 'roma'), array('roma')));
check('dönem uyar: kesişmez', !$H::donemUyar(array('helenistik'), array('arkaik')));
check('dönem uyar: dönemsiz yazı / dönemsiz sikke', $H::donemUyar(array(), array('arkaik')) && $H::donemUyar(array('roma'), array()));
check('bölge özeti: AEOLİS', $H::bolgeOzeti(array('baslik' => 'AEOLİS', 'catid' => 36, 'donem' => array(), 'yer_sayisi' => 8)));
check('bölge özeti: karışık harfli başlık değil', !$H::bolgeOzeti(array('baslik' => 'Kyzikos, Orkinos', 'catid' => 36, 'donem' => array(), 'yer_sayisi' => 8)));
check('bölge özeti: sikkeler kategorisi değil', !$H::bolgeOzeti(array('baslik' => 'AEOLİS', 'catid' => 39, 'donem' => array(), 'yer_sayisi' => 8)));
check('bölge özeti: dönem etiketli değil', !$H::bolgeOzeti(array('baslik' => 'LİDYA', 'catid' => 36, 'donem' => array('arkaik'), 'yer_sayisi' => 3)));
check('bölge özeti: az yer etiketi değil', !$H::bolgeOzeti(array('baslik' => 'LİDYA', 'catid' => 36, 'donem' => array(), 'yer_sayisi' => 1)));

$y = function ($id, $eslesme, $donem, $catid, $etiket, $tarih, $baslik = 'Yazı', $yer = 1) {
    return array('id' => $id, 'eslesme' => $eslesme, 'donem' => $donem, 'catid' => $catid, 'etiket_sayisi' => $etiket,
        'publish_up' => $tarih, 'baslik' => $baslik, 'yer_sayisi' => $yer);
};
$ids = function (array $l) { return array_column($l, 'id'); };

// Phokaia elektrumu (arkaik): helenistik Seleukos yazıları elenir, hekte yazısı öne, bölge özeti sona
$phokaia = array(
    $y(34044, 'darphane', array('helenistik'), 39, 6, '2026-10-31 07:05:00'),
    $y(34032, 'darphane', array('helenistik'), 39, 12, '2026-10-17 07:05:00'),
    $y(30676, 'darphane', array(), 36, 9, '2025-12-03 07:05:00', 'İYONYA', 9),
    $y(34030, 'darphane', array('arkaik'), 39, 4, '2026-09-25 07:05:00'),
    $y(13042, 'darphane', array('arkaik'), 39, 1, '2025-10-22 07:05:00'),
    $y(12996, 'darphane', array('arkaik'), 36, 2, '2025-10-21 07:05:00'),
);
check('sıra: Phokaia', $ids($H::sikkeYazilariniSirala($phokaia, array('arkaik'), 6)) === array(13042, 34030, 12996, 30676),
    json_encode($ids($H::sikkeYazilariniSirala($phokaia, array('arkaik'), 6))));

// Lampsakos İskender tetradrahmisi (MÖ 328–323): ikisi › hükümdar; klasik satrap ve arkaik Lampsakos elenir; dönemsiz anlatı dönemlilerden sonra
$iskender = array(
    $y(34101, 'hukumdar', array(), 40, 2, '2026-11-22 07:05:00'),
    $y(34073, 'darphane', array('arkaik'), 39, 1, '2026-11-13 07:05:00'),
    $y(34033, 'ikisi', array('klasik'), 39, 18, '2026-10-17 07:05:00'),
    $y(34036, 'ikisi', array('helenistik'), 39, 6, '2026-10-17 07:05:00'),
    $y(13038, 'hukumdar', array('helenistik'), 39, 1, '2025-10-22 07:05:00'),
    $y(13030, 'hukumdar', array('helenistik'), 35, 1, '2025-10-22 07:05:00'),
    $y(12980, 'hukumdar', array('helenistik'), 40, 1, '2025-10-21 07:05:00'),
);
$sd = $H::sikkeDonemleri('-0328', '-0323');
check('sıra: İskender', $ids($H::sikkeYazilariniSirala($iskender, $sd, 6)) === array(34036, 13038, 13030, 12980, 34101),
    json_encode($ids($H::sikkeYazilariniSirala($iskender, $sd, 6))));
check('sıra: sınır 3', $ids($H::sikkeYazilariniSirala($iskender, $sd, 3)) === array(34036, 13038, 13030));

// Dönemi bilinmeyen sikke: süzgeç yok; eşleşme ve kategori sırası; aynı sınıfta yeni önce
$tarihsiz = array(
    $y(1, 'darphane', array('roma'), 39, 2, '2026-01-01 00:00:00'),
    $y(2, 'darphane', array('arkaik'), 39, 2, '2026-06-01 00:00:00'),
    $y(3, 'hukumdar', array('klasik'), 40, 5, '2025-01-01 00:00:00'),
);
check('sıra: dönemsiz sikke', $ids($H::sikkeYazilariniSirala($tarihsiz, array(), 6)) === array(3, 2, 1));
check('sıra: boş liste', $H::sikkeYazilariniSirala(array(), array('roma'), 6) === array());

// --- 1.4.1: Price tipi yazı hükümdarı + yer etiketi notundan darphane anahtarı
check('yazı hükümdarı: dolu alan korunur', $H::yaziHukumdari('Price 1353', 'Lysimachus') === 'Lysimachus');
check('yazı hükümdarı: Price düz numara → İskender', $H::yaziHukumdari('Price 1689', '') === 'Alexander III of Macedon');
check('yazı hükümdarı: Price harfli numara → İskender', $H::yaziHukumdari(' Price 2889A ', '') === 'Alexander III of Macedon');
check('yazı hükümdarı: Price P numarası boşsa boş', $H::yaziHukumdari('Price P122', '') === '');
check('yazı hükümdarı: başka katalog boş', $H::yaziHukumdari('CN Type 21332', '') === '' && $H::yaziHukumdari('Seleucid Coins 311', '') === '');
$yb = $H::yerEtiketBilgisi(array(
    array('alias' => 'soli-pompeiopolis', 'ust' => 'yerler', 'ad' => 'Soli/Pompeiopolis', 'not' => 'darphane: soli-pompeiopolis'),
    array('alias' => 'magnesia-ad-maeandrum', 'ust' => 'yerler', 'ad' => 'Magnesia', 'not' => ''),
    array('alias' => 'rhodes', 'ust' => 'yerler', 'ad' => '', 'not' => 'darphane: rhodes'),
    array('alias' => 'augustus', 'ust' => 'hukumdarlar', 'ad' => 'Augustus', 'not' => ''),
), array('magnesia_ad_maeandrum'));
check('yer bilgisi: nottaki tireli anahtar', $yb['soli-pompeiopolis']['darphane'] === 'soli-pompeiopolis' && $yb['soli-pompeiopolis']['ad'] === 'Soli/Pompeiopolis');
check('yer bilgisi: notsuz → darphaneAnahtari', $yb['magnesia-ad-maeandrum']['darphane'] === 'magnesia_ad_maeandrum');
check('yer bilgisi: başlıksız → alias', $yb['rhodes']['ad'] === 'rhodes' && $yb['rhodes']['darphane'] === 'rhodes');
check('yer bilgisi: hükümdar etiketi alınmaz', !isset($yb['augustus']) && count($yb) === 3);

echo "\n{$gecti} passed, {$kaldi} failed\n";
exit($kaldi ? 1 : 0);
