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
check('gruplar ayrışır, tekrar yok, ilgisiz atlanır', $g === array('yer' => array('sardes'), 'hukumdar' => array('alexander-iii-of-macedon')));

// ---- görselli önde
$s = $H::gorselliOnde(array(array('id' => 1, 'gorsel' => null), array('id' => 2, 'gorsel' => 'u2'), array('id' => 3, 'gorsel' => 'u3'),
    array('id' => 4, 'gorsel' => null)), 3);
check('görselli önde, sıra korunur, sınır', array_column($s, 'id') === array(2, 3, 1));

echo "\n{$gecti} passed, {$kaldi} failed\n";
exit($kaldi ? 1 : 0);
