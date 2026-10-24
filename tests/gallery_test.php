<?php
/**
 * mod_numistr_gallery testleri (DB'siz, Joomla'sız): php Module/tests/gallery_test.php
 * 1. Saf mantık: satır düzeni, ölçü yazısının görünürlüğü, filigran seçimi, Pro grubu.
 * 2. Eşdeğerlik: eski betik (custom/numistr_gallery.php, Sourcerer ile çağrılan) ile yeni modül şablonu aynı görsellerden
 *    aynı DOM'u üretir — misafir ve Pro oturumunda, çift (detay) görsel, eksik ölçü, yalnız kaynak / yalnız lisans,
 *    özel karakterler. Etiketler arası yalnız-boşluk metin düğümleri karşılaştırma dışıdır (görünümü etkilemez).
 */

namespace Joomla\CMS {
    class Factory
    {
        public static $app;
        public static $db;

        public static function getApplication() { return self::$app; }
        public static function getDbo() { return self::$db; }
    }
}

namespace Joomla\CMS\Uri {
    class Uri
    {
        public static function root() { return 'https://www.numistr.org/'; }
    }
}

namespace Joomla\CMS\Component {
    class ComponentHelper
    {
        public static function getParams($ad)
        {
            return new class {
                public function get($k, $v = null) { return $k === 'pro_group_id' ? 10 : $v; }
            };
        }
    }
}

namespace Joomla\CMS\Language {
    class Text
    {
        public static $dizin = array();

        public static function _($k) { return self::$dizin[$k] ?? $k; }
    }
}

namespace {
    define('_JEXEC', 1);
    define('JPATH_SITE', sys_get_temp_dir());

    require __DIR__ . '/../mod_numistr_gallery/helper.php';
    require __DIR__ . '/../custom/numistr_gallery.php';

    \Joomla\CMS\Language\Text::$dizin = parse_ini_file(__DIR__ . '/../mod_numistr_gallery/language/tr-TR/mod_numistr_gallery.ini');

    $H = 'ModNumistrGalleryHelper';
    $gecti = $kaldi = 0;

    function check($ad, $kosul, $ayrinti = '')
    {
        global $gecti, $kaldi;
        $kosul ? $gecti++ : $kaldi++;
        echo ($kosul ? '  ok   ' : '  FAIL ') . $ad . ($kosul || $ayrinti === '' ? '' : ' -- ' . $ayrinti) . "\n";
    }

    function g($id, $tur = '', $w = '', $d = '', $kaynak = '', $lisans = '', $ok = 0)
    {
        return (object) array('image_id' => $id, 'image_type' => $tur, 'weight' => $w, 'diameter' => $d, 'ordering' => $id,
            'filename' => 'x.jpg', 'remote_url' => '', 'source_credit' => $kaynak, 'license' => $lisans, 'license_ok' => $ok);
    }

    /** Eski betiğin istediği kadar Joomla: zincirlenen sorgu nesnesi, sabit sonuç. */
    class SahteDb
    {
        public $gorseller = array();
        public $kolon = array();

        public function getQuery($yeni = false) { return $this; }
        public function __call($ad, $arg) { return $this; }
        public function quoteName($x, $as = null) { return $x; }
        public function quote($x) { return "'" . $x . "'"; }
        public function setQuery($q) { return $this; }
        public function loadObjectList() { return $this->gorseller; }
        public function loadColumn() { return $this->kolon; }
        public function loadResult() { return ''; }
    }

    class SahteKimlik
    {
        public $guest;
        private $gruplar;

        public function __construct($misafir, array $gruplar) { $this->guest = $misafir; $this->gruplar = $gruplar; }
        public function getAuthorisedGroups() { return $this->gruplar; }
    }

    class SahteUygulama
    {
        public $input;
        private $kimlik;

        public function __construct($id, $kimlik)
        {
            $this->kimlik = $kimlik;
            $this->input  = new class($id) {
                private $id;
                public function __construct($id) { $this->id = $id; }
                public function getInt($k, $v = 0) { return $k === 'id' ? $this->id : $v; }
            };
        }

        public function getIdentity() { return $this->kimlik; }
    }

    /** Galeri sarmalayıcısının kanonik biçimi: yalnız-boşluk metin düğümleri atılır, örnek kimliği sabitlenir. */
    function kanonik($html)
    {
        $html = preg_replace('/numistr-gallery-\d+-[0-9a-f]{6}/', 'UID', $html);
        $dom  = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_NOBLANKS);
        $xp = new DOMXPath($dom);

        foreach (iterator_to_array($xp->query('//text()[normalize-space(.) = ""]')) as $t) {
            $t->parentNode->removeChild($t);
        }

        $w = $xp->query('//div[contains(@class,"coin-gallery-wrapper")]')->item(0);

        return $w ? $w->C14N() : '';
    }

    function eskiCikti($id, array $gorseller, $kimlik)
    {
        \Joomla\CMS\Factory::$app = new SahteUygulama($id, $kimlik);
        \Joomla\CMS\Factory::$db  = new SahteDb();
        \Joomla\CMS\Factory::$db->gorseller = $gorseller;
        ob_start();
        numistr_render_gallery();
        $c = ob_get_clean();
        $bas = strpos($c, '<div id="numistr-gallery-');

        return $bas === false ? '' : substr($c, $bas, strpos($c, '<script>', $bas) - $bas);
    }

    function yeniCikti($id, array $gorseller, $kimlik)
    {
        $app    = new SahteUygulama($id, $kimlik);
        $ogeler = ModNumistrGalleryHelper::ogeler($gorseller, ModNumistrGalleryHelper::popupWm($app), rtrim(\Joomla\CMS\Uri\Uri::root(), '/'));
        $uid    = 'numistr-gallery-' . $id . '-' . substr(md5((string) mt_rand()), 0, 6);
        ob_start();
        require __DIR__ . '/../mod_numistr_gallery/tmpl/default.php';

        return ob_get_clean();
    }

    // ---- saf mantık: satır düzeni ve ölçü yazısı
    $o = $H::ogeler(array(g(1), g(2), g(3, 'Detay'), g(4), g(5), g(6)), 1, 'https://k');
    check('öğe sayısı', count($o) === 6);
    check('çift (detay, büyük harf) tam satır', $o[2]['sinif'] === 'gallery-item pair' && $o[0]['sinif'] === 'gallery-item');
    check('yazı: 1. görünür, 2. gizli, detay görünür, sonra yeni satır', array_column($o, 'yazi') === array(true, false, true, true, false, true));
    check('küçük görsel wm=0', $o[0]['kucuk'] === 'https://k/index.php?option=com_numistr&view=gorsel&format=raw&id=1&wm=0');

    // ---- filigran
    $o = $H::ogeler(array(g(1, '', '', '', '', '', 1), g(2, '', '', '', '', '', 0), g(3)), 2, 'https://k');
    check('Pro + serbest lisans → wm=2', substr($o[0]['buyuk'], -5) === '&wm=2');
    check('Pro + NC/doğrulanmamış → wm=1', substr($o[1]['buyuk'], -5) === '&wm=1' && substr($o[2]['buyuk'], -5) === '&wm=1');
    $o = $H::ogeler(array(g(1, '', '', '', '', '', 1)), 1, 'https://k');
    check('misafir + serbest lisans → wm=1', substr($o[0]['buyuk'], -5) === '&wm=1');

    // ---- Pro grubu
    check('Pro grubunda → 2', $H::wmSec(false, array(2, 10), 10) === 2);
    check('grup kimliği metin → 2', $H::wmSec(false, array('2', '10'), 10) === 2);
    check('Pro grubunda değil → 1', $H::wmSec(false, array(2), 10) === 1);
    check('misafir → 1', $H::wmSec(true, array(10), 10) === 1);
    check('grup tanımsız (0) → 1', $H::wmSec(false, array(10), 0) === 1);
    check('popupWm: kimlik yok → 1', $H::popupWm(new SahteUygulama(1, null)) === 1);

    // ---- EN → TR karşılık seçimi
    check('görselli en küçük aday', $H::sahipSec(array(9468, 22614), array(22614, 9468)) === 9468);
    check('küçük adayın görseli yoksa sonraki', $H::sahipSec(array('9468', '22614'), array(22614)) === 22614);
    check('görselli aday yok → 0', $H::sahipSec(array(5, 6), array()) === 0);
    check('aday yok → 0', $H::sahipSec(array(), array(1)) === 0);

    // ---- DB katmanı (sahte DB): gruplama ve karşılık görselleri
    $db = new SahteDb();
    \Joomla\CMS\Factory::$db = $db;
    $r = function ($coin, $img) { $o = g($img); $o->coin_id = $coin; return $o; };
    $db->gorseller = array($r(3404, 1), $r(3404, 2));
    check('gorseller: kendi görselleri', array_column($H::gorseller(3404), 'image_id') === array(1, 2));
    check('gorseller: görsel yok → boş', $H::gorseller(9999) === array());
    $db->kolon = array('22614', '9468');
    $db->gorseller = array($r(9468, 5), $r(9468, 6), $r(22614, 7));
    check('EN: en küçük görselli TR karşılığın görselleri', array_column($H::trKarsilikGorselleri(28213), 'image_id') === array(5, 6));
    $db->kolon = array();
    check('EN: karşılık yok → boş', $H::trKarsilikGorselleri(28213) === array());
    $db->kolon = array('22614');
    $db->gorseller = array();
    check('EN: karşılığın görseli yok → boş', $H::trKarsilikGorselleri(28213) === array());

    // ---- eşdeğerlik: eski betik ↔ yeni şablon
    $set = array(
        g(11, '', '12.31', '27', 'Classical Numismatic Group', 'CC BY-SA 3.0', 1),
        g(12, '', '12.31', '27', 'Classical Numismatic Group', 'CC BY-SA 3.0', 1),
        g(13, 'detay', '', '24.5', 'Gallica / BnF', '', 0),
        g(14, '', '', '', '', 'Kamu malı', 1),
        g(15, '', '3.9', '', 'Münzkabinett <Berlin> & "Ş"', 'CC BY-NC-SA 4.0', 0),
        g(16, ' DETAY ', '8', '20', '', '', 0),
        g(17),
    );
    $misafir = new SahteKimlik(true, array(1, 9));
    $pro     = new SahteKimlik(false, array(2, 10));

    foreach (array('misafir' => $misafir, 'Pro' => $pro) as $ad => $kim) {
        $eski = kanonik(eskiCikti(3404, $set, $kim));
        $yeni = kanonik(yeniCikti(3404, $set, $kim));
        check("eşdeğer DOM ($ad)", $eski !== '' && $eski === $yeni, "\n eski: " . substr($eski, 0, 600) . "\n yeni: " . substr($yeni, 0, 600));
    }

    $tek = array(g(21, '', '1.1', '9', 'X', 'Y', 1));
    check('eşdeğer DOM (tek görsel)', kanonik(eskiCikti(7, $tek, $misafir)) === kanonik(yeniCikti(7, $tek, $misafir)));
    check('Pro çıktısında wm=2 var, misafirde yok',
        strpos(kanonik(yeniCikti(1, $set, $pro)), 'wm=2') !== false && strpos(kanonik(yeniCikti(1, $set, $misafir)), 'wm=2') === false);
    check('özel karakterler kaçışlı', strpos(yeniCikti(1, $set, $misafir), 'Münzkabinett &lt;Berlin&gt; &amp; &quot;Ş&quot;') !== false);

    echo "\n{$gecti} passed, {$kaldi} failed\n";
    exit($kaldi ? 1 : 0);
}
