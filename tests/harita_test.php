<?php
/**
 * mod_numistr_harita testleri (Joomla'sız): php Module/tests/harita_test.php
 * Modül iki haritayı doğru şablonla, anahtar ayardan gelerek basar; anahtar ya da tür yoksa hiçbir şey basmaz.
 * Şablonların canlıdaki Sourcerer çıktısıyla bayt eşitliği kurulumda bir kez denetlendi (runbook 2026-10-25-mod-numistr-harita).
 */

namespace Joomla\CMS\Helper {
    class ModuleHelper
    {
        public static function getLayoutPath($modul, $duzen)
        {
            return __DIR__ . '/../' . $modul . '/tmpl/' . $duzen . '.php';
        }
    }
}

namespace Joomla\CMS\Log {
    class Log
    {
        const WARNING = 4;
        public static $kayitlar = array();

        public static function add($mesaj, $seviye, $kategori) { self::$kayitlar[] = $mesaj; }
    }
}

namespace {
    define('_JEXEC', 1);

    $gecti = $kaldi = 0;

    function check($ad, $kosul, $ayrinti = '')
    {
        global $gecti, $kaldi;
        $kosul ? $gecti++ : $kaldi++;
        echo ($kosul ? '  ok   ' : '  FAIL ') . $ad . ($kosul || $ayrinti === '' ? '' : ' -- ' . $ayrinti) . "\n";
    }

    class Ayar
    {
        private $d;

        public function __construct(array $d) { $this->d = $d; }
        public function get($k, $v = null) { return $this->d[$k] ?? $v; }
    }

    function bas(array $ayar)
    {
        $params = new Ayar($ayar);
        $module = (object) array('id' => 130);
        ob_start();
        include __DIR__ . '/../mod_numistr_harita/mod_numistr_harita.php';

        return ob_get_clean();
    }

    $sarmal = '<div class="uk-margin-remove-last-child custom" ><p>';

    // ---- sikke detay haritası (1.1.0, C14)
    $c = bas(array('harita' => 'sikke', 'api_key' => 'TEST_anahtar-1'));
    check('sikke: sarmalayıcıyla başlar', strpos($c, '<div class="uk-margin-remove-last-child custom nmh-sikke">') === 0);
    check('sikke: harita kabı, anahtar ayardan', substr_count($c, '<div id="single-mint-map" data-key="TEST_anahtar-1">') === 1);
    check('sikke: başka harita yok', strpos($c, 'google-antik-harita') === false);
    check('sikke: script etiketleri dengeli', substr_count($c, '<script') === substr_count($c, '</script>'));
    check('sikke: kapanış', substr(rtrim($c), -6) === '</div>');
    check('sikke: TR ve EN başlıklarla arar', strpos($c, "satir(['Darphane', 'Mint'])") !== false
        && strpos($c, "satir(['Darphane Koordinatları', 'Mint Coordinates'])") !== false);
    check('sikke: Maps sayfa açılışında yüklenmez (script etiketi yok)', strpos($c, '<script async src="https://maps.googleapis.com') === false
        && strpos($c, 'IntersectionObserver') !== false && strpos($c, '&loading=async') !== false);
    check('sikke: kullanımdan kalkan google.maps.Marker yok', strpos($c, 'Marker(') === false);
    check('sikke: 500 ms bekleme yok', strpos($c, '}, 500)') === false);
    check('sikke: yerleşim kaydı /v1/locations ucundan', strpos($c, "'/api/index.php/v1/locations?ne_lat='") !== false
        && strpos($c, "'/api/index.php/v1/locations/' + encodeURIComponent(id)") !== false);
    check('sikke: koda gömülü darphane açıklaması kalmadı', strpos($c, 'mintDescriptions') === false);

    // Kentler: modüldeki veri = uygulamanın listesi = Antik Harita sayfasındaki liste (tek kaynak, C14 c)
    $modulVeri = json_decode(file_get_contents(__DIR__ . '/../mod_numistr_harita/data/ancient_major_cities.json'), true);
    check('kentler: modül verisi 130 kent', is_array($modulVeri) && count($modulVeri) === 130);
    preg_match('/var VERI = (\{.*?\});/s', $c, $m);
    $gomulu = isset($m[1]) ? json_decode($m[1], true) : null;
    $bekle = array_map(function ($k) { return array($k['name'], round($k['lat'], 3), round($k['lng'], 3)); }, (array) $modulVeri);
    check('kentler: sayfaya gömülen liste = modül verisi', is_array($gomulu) && $gomulu['cities'] == $bekle, isset($m[1]) ? '' : 'VERI yok');
    $uyg = __DIR__ . '/../../anatolian_coins_flutter/assets/data/ancient_major_cities.json';
    if (is_file($uyg)) {
        check('kentler: uygulamadaki dosyayla bayt bayt aynı', sha1_file($uyg) === sha1_file(__DIR__ . '/../mod_numistr_harita/data/ancient_major_cities.json'));
    } else {
        echo "  --   kentler: uygulama deposu yok, karşılaştırma atlandı\n";
    }
    $ana = file_get_contents(__DIR__ . '/../mod_numistr_harita/tmpl/anadolu.php');
    preg_match_all('/name: "([^"]+)", lat: ([\d.]+), lng: ([\d.]+)/', substr($ana, strpos($ana, 'const majorCities')), $mm, PREG_SET_ORDER);
    $anaListe = array_map(function ($x) { return array($x[1], round((float) $x[2], 3), round((float) $x[3], 3)); }, $mm);
    check('kentler: Antik Harita sayfasındaki listeyle aynı', $anaListe == $bekle, count($anaListe) . ' kent');

    // Bozuk veri dosyası sayfayı kırmaz (gerçek dosyaya dokunmadan, geçici kopyada)
    $gecici = sys_get_temp_dir() . '/nmh_harita_test_' . getmypid();
    @mkdir($gecici . '/tmpl', 0700, true);
    @mkdir($gecici . '/data', 0700, true);
    copy(__DIR__ . '/../mod_numistr_harita/tmpl/sikke.php', $gecici . '/tmpl/sikke.php');
    file_put_contents($gecici . '/data/ancient_major_cities.json', '{bozuk');
    $anahtar = 'K';
    ob_start();
    include $gecici . '/tmpl/sikke.php';
    $c2 = ob_get_clean();
    @unlink($gecici . '/data/ancient_major_cities.json');
    @unlink($gecici . '/tmpl/sikke.php');
    @rmdir($gecici . '/data');
    @rmdir($gecici . '/tmpl');
    @rmdir($gecici);
    check('kentler: veri okunamazsa boş listeyle çizer', strpos($c2, 'var VERI = {"cities":[]};') !== false);

    // ---- Anadolu haritası
    $c = bas(array('harita' => 'anadolu', 'api_key' => 'TEST_anahtar-1'));
    check('anadolu: sarmalayıcıyla başlar', strpos($c, $sarmal) === 0);
    check('anadolu: harita kabı', substr_count($c, 'id="google-antik-harita"') === 1);
    check('anadolu: anahtar ayardan', substr_count($c, 'maps/api/js?key=TEST_anahtar-1&callback=initGoogleMap') === 1);
    check('anadolu: markerclusterer 2.6.2 + SRI', substr_count($c, 'markerclusterer@2.6.2/dist/index.min.js" integrity="sha384-') === 1);
    check('anadolu: sürümsüz unpkg yok', strpos($c, 'markerclusterer/dist/') === false);
    check('anadolu: /v1/locations çağrısı', strpos($c, '/api/index.php/v1/locations') !== false);
    check('anadolu: script etiketleri dengeli', substr_count($c, '<script') === substr_count($c, '</script>'));

    // ---- ikisinde de
    foreach (array('sikke', 'anadolu') as $h) {
        $c = bas(array('harita' => $h, 'api_key' => 'X'));
        check("$h: Sourcerer etiketi yok", strpos($c, '{numistr') === false && strpos($c, '{/numistr') === false);
        check("$h: kaçışlı kod yok", strpos($c, '&lt;script') === false && strpos($c, '&lt;style') === false);
        check("$h: gömülü Google anahtarı yok", strpos(file_get_contents(__DIR__ . "/../mod_numistr_harita/tmpl/$h.php"), 'AIza') === false);
    }
    check('sikke: anahtar kaçışlı basılır', strpos(bas(array('harita' => 'sikke', 'api_key' => 'a"b<c')), 'data-key="a&quot;b&lt;c"') !== false);
    check('anadolu: anahtar kaçışlı basılır', strpos(bas(array('harita' => 'anadolu', 'api_key' => 'a"b<c')), 'key=a&quot;b&lt;c&') !== false);

    // ---- eksik ayar
    Joomla\CMS\Log\Log::$kayitlar = array();
    check('anahtar boş → çıktı yok', bas(array('harita' => 'sikke', 'api_key' => '  ')) === '');
    check('tür geçersiz → çıktı yok', bas(array('harita' => '../x', 'api_key' => 'K')) === '');
    check('eksik ayar günlüğe yazılır', count(Joomla\CMS\Log\Log::$kayitlar) === 2);
    check('varsayılan tür sikke', strpos(bas(array('api_key' => 'K')), 'single-mint-map') !== false);

    echo "\n{$gecti} passed, {$kaldi} failed\n";
    exit($kaldi ? 1 : 0);
}
