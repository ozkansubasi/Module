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

echo "\n{$gecti} passed, {$kaldi} failed\n";
exit($kaldi ? 1 : 0);
