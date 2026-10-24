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

    // ---- sikke detay haritası
    $c = bas(array('harita' => 'sikke', 'api_key' => 'TEST_anahtar-1'));
    check('sikke: sarmalayıcıyla başlar', strpos($c, $sarmal) === 0);
    check('sikke: harita kabı', substr_count($c, 'id="single-mint-map"') === 1);
    check('sikke: anahtar ayardan', substr_count($c, 'maps/api/js?key=TEST_anahtar-1&callback=initSingleLocationMap') === 1);
    check('sikke: başka harita yok', strpos($c, 'google-antik-harita') === false);
    check('sikke: script etiketleri dengeli', substr_count($c, '<script') === substr_count($c, '</script>'));
    check('sikke: mod_custom kapanışı', substr(rtrim($c), -10) === '</p></div>');

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
    check('anahtar kaçışlı basılır', strpos(bas(array('harita' => 'sikke', 'api_key' => 'a"b<c')), 'key=a&quot;b&lt;c&') !== false);

    // ---- eksik ayar
    Joomla\CMS\Log\Log::$kayitlar = array();
    check('anahtar boş → çıktı yok', bas(array('harita' => 'sikke', 'api_key' => '  ')) === '');
    check('tür geçersiz → çıktı yok', bas(array('harita' => '../x', 'api_key' => 'K')) === '');
    check('eksik ayar günlüğe yazılır', count(Joomla\CMS\Log\Log::$kayitlar) === 2);
    check('varsayılan tür sikke', strpos(bas(array('api_key' => 'K')), 'single-mint-map') !== false);

    echo "\n{$gecti} passed, {$kaldi} failed\n";
    exit($kaldi ? 1 : 0);
}
