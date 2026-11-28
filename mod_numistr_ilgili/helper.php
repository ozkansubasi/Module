<?php
/**
 * @package     NumisTR
 * @subpackage  mod_numistr_ilgili
 *
 * Yer / hükümdar bağlantılı "ilgili içerik" blokları (ADR-010).
 *
 * Sayfadaki makalenin türünü kendisi bulur ve buna göre blok üretir:
 *   - sikke    (mint-name dolu): darphanenin yerleşim sayfası, aynı darphanenin / hükümdarın sikkeleri, ilgili yazılar
 *   - yerleşim (loc-id dolu)    : bu kentte basılan sikkeler, burada sikke bastıranlar, kentten söz eden yazılar
 *   - yazı     (yer / hükümdar etiketi): etiketli yerlerin yerleşimi + sikkeleri, etiketli hükümdarların sikkeleri
 *
 * Sikke ↔ yerleşim bağı alanlardan + data/darphane_yerlesim.json'dan; yazı bağı etiketten (üst etiketler
 * "yerler" / "hukumdarlar"; yer etiketinin alias'ı darphane anahtarı, hükümdarınki authority-name'in sade biçimi).
 *
 * Dil: TR sayfada sikkelerin '*' kopyaları, EN sayfada 'en-GB' kopyaları; yerleşim ve yazılar sayfanın dilinde.
 * EN sikkenin görseli coin-id alanıyla TR karşılığından alınır (coins_images yalnız TR kopyalara bağlı).
 * Görsel yalnız lisans kapısından geçerse (NumisTRImageLicense::sqlVisible ile aynı kural).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\ParameterType;

class ModNumistrIlgiliHelper
{
    const ALAN_DARPHANE = 'mint-name';
    const ALAN_HUKUMDAR = 'authority-name';
    const ALAN_YERLESIM = 'loc-id';
    const ALAN_SIKKE_KIMLIK = 'coin-id';
    const ALAN_BASLANGIC = 'start-date';
    const ALAN_BITIS     = 'end-date';

    const UST_YER      = 'yerler';
    const UST_HUKUMDAR = 'hukumdarlar';
    const UST_BOLGE    = 'bolgeler';    // C18-D: not "en=Lydia;kat=17" (katalog bölge kategorisi)
    const UST_DONEM    = 'donemler';    // C18-D: not "en=Archaic;aralik=…"

    /** Yazı sayfasında en çok kaç yer / hükümdar etiketi için blok üretilir. */
    const ETIKET_SINIRI = 3;
    const CIP_SINIRI    = 2;   // üst bilgide tür başına çip (bölge / yer / hükümdar)

    /** Blog kategorileri (TR 35–40, EN 107–112): yazı sayfası olarak işlenir (etiket olmasa da seri / uygulama blokları). */
    const BLOG_KATEGORILERI = '35,36,37,38,39,40,107,108,109,110,111,112';

    /** Okuma süresi için dakikadaki kelime. */
    const OKUMA_HIZI = 200;

    /** Sayfa başına en çok video (numistr_videolar; 1.3.0, BACKLOG 12b V1). */
    const VIDEO_SINIRI = 2;
    const VIDEO_TABLO  = 'numistr_videolar';

    /**
     * Sikke sayfasında yazı seçimi (1.4.0, 28 Kas 2026). Örneklem ve benzetim: claudedocs/web/ilgili-yazi-orneklem-2026-11-28.md.
     * Dönem aralıkları yıl olarak (MÖ eksi), [alt, üst). Helenistik sikke MÖ 336'dan (İskender) başlar: blog İskender'i
     * helenistik etiketliyor; MÖ 323 sınırında İskender sikkeleri klasiği de tutup satrap yazılarını geri getiriyordu.
     */
    const DONEM_ARALIKLARI = array(
        'arkaik'     => array(-10000, -480),
        'klasik'     => array(-480, -336),
        'helenistik' => array(-336, -31),
        'roma'       => array(-31, 491),
        'bizans'     => array(491, 10000),
    );

    /** Blog kategorisi → sıra önceliği (küçük önce): sikkeler › semboller / numizmatik › atlas / tarihsel anlatı › diğer. */
    const KATEGORI_ONCELIGI = array(39 => 0, 111 => 0, 35 => 1, 107 => 1, 37 => 1, 109 => 1, 36 => 2, 108 => 2, 40 => 2, 112 => 2);

    /** Anadolu Atlası (TR / EN): bölge özet yazıları (AEOLİS, İYONYA …) burada. */
    const ATLAS_KATEGORILERI = array(36, 108);

    // ------------------------------------------------------------------ saf mantık (DB'siz test edilir)

    /** authority-name / ad → etiket alias'ı: küçük harf, Türkçe harfler sade, harf-rakam dışı → tire. */
    public static function sade($s)
    {
        $s = strtr((string) $s, array(
            'İ' => 'i', 'I' => 'i', 'ı' => 'i', 'Ş' => 's', 'ş' => 's', 'Ğ' => 'g', 'ğ' => 'g',
            'Ü' => 'u', 'ü' => 'u', 'Ö' => 'o', 'ö' => 'o', 'Ç' => 'c', 'ç' => 'c',
            'Â' => 'a', 'â' => 'a', 'Î' => 'i', 'î' => 'i', 'Û' => 'u', 'û' => 'u',
        ));
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);

        return trim($s, '-');
    }

    /**
     * Yer etiketi alias'ı → darphane anahtarı (mint-name): `magnesia-ad-maeandrum` → `magnesia_ad_maeandrum`.
     * Anahtarların bir kısmı tire taşıdığı için (`soli-pompeiopolis`) körlemesine tire → alt çizgi YAPILMAZ: önce bilinen
     * anahtarlar (eşleme tablosu) arasında yerEtiketi() karşılığı aranır; bulunamazsa tire → alt çizgi.
     */
    public static function darphaneAnahtari($alias, ?array $bilinen = null)
    {
        $alias = strtolower(trim((string) $alias));

        foreach ($bilinen ?? array_keys(self::harita()) as $d) {
            if (self::yerEtiketi($d) === $alias) {
                return (string) $d;
            }
        }

        return str_replace('-', '_', $alias);
    }

    /** Darphane anahtarı → yer etiketi alias'ı (darphaneAnahtari'nın tersi). */
    public static function yerEtiketi($darphane)
    {
        return str_replace('_', '-', strtolower(trim((string) $darphane)));
    }

    /** data/darphane_yerlesim.json → [darphane => [TR yerleşim kimlikleri]] */
    public static function harita($yol = null)
    {
        static $onbellek = array();
        $yol = $yol ?: __DIR__ . '/data/darphane_yerlesim.json';

        if (!isset($onbellek[$yol])) {
            $j = is_file($yol) ? json_decode((string) file_get_contents($yol), true) : null;
            $h = array();

            foreach ((array) ($j['darphane'] ?? array()) as $d => $ids) {
                $ids = array_values(array_filter(array_map('intval', (array) $ids)));

                if ($ids) {
                    $h[(string) $d] = $ids;
                }
            }

            $onbellek[$yol] = $h;
        }

        return $onbellek[$yol];
    }

    /** [darphane => [ids]] → [id => [darphaneler]] */
    public static function tersHarita(array $h)
    {
        $t = array();

        foreach ($h as $d => $ids) {
            foreach ($ids as $id) {
                $t[(int) $id][] = (string) $d;
            }
        }

        return $t;
    }

    /**
     * Sayfa türü.
     *
     * @param   array  $alanlar    [alan adı => değer]
     * @param   array  $etiketler  [['alias' => .., 'ust' => ..], ...]
     *
     * @return  string  sikke | yerlesim | yazi | '' (blok yok)
     */
    public static function sayfaTuru(array $alanlar, array $etiketler)
    {
        if (trim((string) ($alanlar[self::ALAN_DARPHANE] ?? '')) !== '') {
            return 'sikke';
        }

        if (trim((string) ($alanlar[self::ALAN_YERLESIM] ?? '')) !== '') {
            return 'yerlesim';
        }

        foreach ($etiketler as $e) {
            if (in_array($e['ust'] ?? '', array(self::UST_YER, self::UST_HUKUMDAR), true)) {
                return 'yazi';
            }
        }

        return '';
    }

    /** Sikke makalelerinin dil kümesi: EN sayfada yalnız en-GB kopyalar, diğer dillerde '*' (TR) ve tr-TR. */
    public static function sikkeDilleri($dil)
    {
        return $dil === 'en-GB' ? array('en-GB') : array('*', 'tr-TR');
    }

    /** Etiket listesini üst etikete göre ayırır: ['yer' => [alias..], 'hukumdar' => [alias..]] (sıra korunur, tekrar yok). */
    public static function etiketGruplari(array $etiketler)
    {
        $g = array('yer' => array(), 'hukumdar' => array(), 'bolge' => array(), 'donem' => array());

        foreach ($etiketler as $e) {
            $a = (string) ($e['alias'] ?? '');

            if ($a === '') {
                continue;
            }

            // bölge / dönem: tam kayıt (id, ad, not) — çip, dönem metni ve benzer yazı sorgusu için
            foreach (array('bolge' => self::UST_BOLGE, 'donem' => self::UST_DONEM) as $grup => $ust) {
                if (($e['ust'] ?? '') === $ust && !in_array($a, array_column($g[$grup], 'alias'), true)) {
                    $g[$grup][] = $e;
                }
            }

            if (($e['ust'] ?? '') === self::UST_YER && !in_array($a, $g['yer'], true)) {
                $g['yer'][] = $a;
            } elseif (($e['ust'] ?? '') === self::UST_HUKUMDAR && !in_array($a, $g['hukumdar'], true)) {
                $g['hukumdar'][] = $a;
            }
        }

        return $g;
    }

    /** "35, 36,x" → [35, 36]; boşsa varsayılan blog kategorileri. */
    public static function kategoriListesi($metin)
    {
        $metin = trim((string) $metin) === '' ? self::BLOG_KATEGORILERI : (string) $metin;

        return array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,;]+/', $metin)))));
    }

    /** HTML gövdenin kelime sayısı (betik / biçem ayıklanır). */
    public static function kelimeSayisi($html)
    {
        $metin = preg_replace('~<(script|style)\b.*?</\1>~is', ' ', (string) $html);
        $metin = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($metin), ENT_QUOTES, 'UTF-8')));

        return $metin === '' ? 0 : count(preg_split('/\s+/u', $metin));
    }

    /** Okuma süresi (dakika, en az 1). */
    public static function okumaSuresi($kelime, $hiz = self::OKUMA_HIZI)
    {
        return max(1, (int) ceil(max(0, (int) $kelime) / max(1, (int) $hiz)));
    }

    /** Joomla images JSON'undaki giriş görseli: "#joomlaImage://…" eki atılır; yerel yol köke bağlanır. */
    public static function introGorsel($imagesJson)
    {
        $j   = json_decode((string) $imagesJson, true);
        $url = trim((string) (is_array($j) ? ($j['image_intro'] ?? '') : ''));
        $url = preg_replace('~#joomlaImage://.*$~', '', $url);

        if ($url === '' || preg_match('/[\s"\'<>]/', $url)) {
            return '';
        }

        return preg_match('~^(https?:)?//|^/~', $url) ? $url : '/' . ltrim($url, '/');
    }

    /** Site araması (mod_numistr_arama sonuç sayfası). */
    public static function aramaUrl($dil, $sorgu)
    {
        return ($dil === 'en-GB' ? '/en/search' : '/tr/arama') . '?q=' . rawurlencode(trim((string) $sorgu));
    }

    /** Uygulama sayfası (plg_system_numistrapp), blogdan geldiği ölçülür. */
    public static function uygulamaUrl($dil, $makaleId)
    {
        return ($dil === 'en-GB' ? '/en/app' : '/tr/app')
            . '?utm_source=numistr&utm_medium=blog&utm_campaign=blog-cta&utm_content=' . (int) $makaleId;
    }

    /** Gizlilik politikası (videonun altındaki not; plg_system_numistrconsent varsayılanlarıyla aynı). */
    public static function gizlilikUrl($dil)
    {
        return $dil === 'en-GB' ? '/en/privacy-policy-gdpr' : '/tr/kvkk-ve-gizlilik-politikasi';
    }

    /** 52 → "0:52", 125 → "2:05"; boş / sıfır → ''. */
    public static function sureYaz($sn)
    {
        $sn = (int) $sn;

        return $sn > 0 ? intdiv($sn, 60) . ':' . str_pad((string) ($sn % 60), 2, '0', STR_PAD_LEFT) : '';
    }

    /**
     * numistr_videolar satırı → şablon öğesi. $kok: site kökü adresi (sonunda / yok), küçük resim tam adresi için.
     * Geçersiz YouTube kimliği → null (şablona hiç gitmez).
     */
    public static function videoOgesi(array $r, $kok)
    {
        $yt = (string) ($r['youtube_id'] ?? '');

        if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $yt)) {
            return null;
        }

        $bicim = ($r['bicim'] ?? '') === 'video' ? 'video' : 'shorts';
        $resim = (string) ($r['kucuk_resim'] ?? '');
        $resim = preg_match('~^images/videolar/[A-Za-z0-9_-]{11}\.jpg$~', $resim) ? rtrim((string) $kok, '/') . '/' . $resim : '';
        $sure  = (int) ($r['sure_sn'] ?? 0);

        return array(
            'yt'       => $yt,
            'bicim'    => $bicim,
            'baslik'   => trim((string) ($r['baslik'] ?? '')),
            'aciklama' => trim((string) ($r['aciklama'] ?? '')),
            'kunye'    => trim((string) ($r['kunye'] ?? '')),
            'resim'    => $resim,
            'sure_sn'  => $sure > 0 ? $sure : 0,
            'sure'     => self::sureYaz($sure),
            'tarih'    => (string) ($r['yayin_tarihi'] ?? ''),
            'izle'     => $bicim === 'shorts' ? 'https://www.youtube.com/shorts/' . $yt : 'https://www.youtube.com/watch?v=' . $yt,
        );
    }

    /** schema.org VideoObject (Google video sonuçları). Yükleme tarihi ya da küçük resim yoksa null: ikisi de zorunlu alan. */
    public static function videoJsonLd(array $o)
    {
        if (empty($o['resim']) || empty($o['tarih']) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $o['tarih'])) {
            return null;
        }

        $j = array(
            '@context'     => 'https://schema.org',
            '@type'        => 'VideoObject',
            'name'         => $o['baslik'] !== '' ? $o['baslik'] : 'NumisTR',
            'description'  => $o['aciklama'] !== '' ? $o['aciklama'] : ($o['baslik'] !== '' ? $o['baslik'] : 'NumisTR'),
            'thumbnailUrl' => array($o['resim']),
            'uploadDate'   => str_replace(' ', 'T', $o['tarih']) . '+00:00',
            'embedUrl'     => 'https://www.youtube.com/embed/' . $o['yt'],
        );

        if (!empty($o['sure_sn'])) {
            $j['duration'] = 'PT' . (int) $o['sure_sn'] . 'S';
        }

        return $j;
    }

    /** Görseli olan sikkeler önde, sıra korunarak en çok $limit tane. */
    public static function gorselliOnde(array $satirlar, $limit)
    {
        $var = array_values(array_filter($satirlar, function ($r) { return !empty($r['gorsel']); }));
        $yok = array_values(array_filter($satirlar, function ($r) { return empty($r['gorsel']); }));

        return array_slice(array_merge($var, $yok), 0, (int) $limit);
    }

    /**
     * Sikkenin start-date / end-date aralığının kesiştiği dönemler ('arkaik', 'klasik' …; etiket alias'ı 'donem-' öneksiz).
     * Başlangıç yoksa ya da sayı değilse boş dizi: süzgeç uygulanmaz. Değerler "-0323" biçiminde gelir.
     */
    public static function sikkeDonemleri($bas, $son)
    {
        $bas = trim((string) $bas);
        $son = trim((string) $son);

        if ($bas === '' || !is_numeric($bas)) {
            return array();
        }

        $b = (int) $bas;
        $e = ($son !== '' && is_numeric($son)) ? (int) $son : $b;

        if ($e < $b) {
            list($b, $e) = array($e, $b);
        }

        $donemler = array();

        foreach (self::DONEM_ARALIKLARI as $donem => $aralik) {
            if ($b < $aralik[1] && $e >= $aralik[0]) {
                $donemler[] = $donem;
            }
        }

        return $donemler;
    }

    /** Yazı sikkenin dönemini tutuyor mu? Dönem etiketi olmayan yazı ya da dönemi bilinmeyen sikke: evet. */
    public static function donemUyar(array $yaziDonemleri, array $sikkeDonemleri)
    {
        return !$yaziDonemleri || !$sikkeDonemleri || (bool) array_intersect($yaziDonemleri, $sikkeDonemleri);
    }

    /** Bölge özet yazısı (AEOLİS, İYONYA …): atlas kategorisinde, dönemsiz, en az 3 yer etiketi, başlık tümüyle büyük harf. */
    public static function bolgeOzeti(array $y)
    {
        $t = trim((string) ($y['baslik'] ?? ''));

        return in_array((int) ($y['catid'] ?? 0), self::ATLAS_KATEGORILERI, true)
            && empty($y['donem'])
            && (int) ($y['yer_sayisi'] ?? 0) >= 3
            && $t !== ''
            && mb_strtoupper($t, 'UTF-8') === $t;
    }

    /**
     * Sikke sayfasının ilgili yazıları: sikkenin dönemini tutmayan yazı elenir, kalanlar sıralanır.
     * Sıra:
     *   1. bölge özeti en sonda;
     *   2. eşleşme: ikisi › hükümdar › darphane;
     *   3. dönemi doğrulanmış yazı dönemsiz yazıdan önce;
     *   4. kategori önceliği;
     *   5. az yer + hükümdar etiketli (odaklı) yazı önce;
     *   6. yeni yazı önce.
     *
     * @param   array  $yazilar  [['id','baslik','catid','publish_up','eslesme' => ikisi|hukumdar|darphane,'donem' => [..],
     *                           'yer_sayisi','etiket_sayisi', …], …]
     *
     * @return  array  aynı satırlar, en çok $limit tane
     */
    public static function sikkeYazilariniSirala(array $yazilar, array $sikkeDonemleri, $limit)
    {
        $sinif = array('ikisi' => 0, 'hukumdar' => 1, 'darphane' => 2);
        $aday  = array();

        foreach ($yazilar as $y) {
            $donem = array_values((array) ($y['donem'] ?? array()));

            if (!self::donemUyar($donem, $sikkeDonemleri)) {
                continue;
            }

            $aday[] = array(array(
                self::bolgeOzeti($y) ? 1 : 0,
                $sinif[$y['eslesme'] ?? ''] ?? 2,
                ($donem && $sikkeDonemleri) ? 0 : 1,
                self::KATEGORI_ONCELIGI[(int) ($y['catid'] ?? 0)] ?? 3,
                (int) ($y['etiket_sayisi'] ?? 0),
                -(int) strtotime((string) ($y['publish_up'] ?? '')),
                (int) ($y['id'] ?? 0),
            ), $y);
        }

        usort($aday, function ($a, $b) { return $a[0] <=> $b[0]; });

        return array_map(function ($a) { return $a[1]; }, array_slice($aday, 0, max(0, (int) $limit)));
    }

    // ------------------------------------------------------------------ veritabanı

    private static function db()
    {
        return Factory::getDbo();
    }

    /** Alan adı → kimlik (istek başına bir kez). */
    public static function alanKimlikleri()
    {
        static $k = null;

        if ($k !== null) {
            return $k;
        }

        $db = self::db();
        $q  = $db->getQuery(true)
            ->select($db->quoteName(array('id', 'name')))
            ->from($db->quoteName('#__fields'))
            ->where($db->quoteName('context') . ' = ' . $db->quote('com_content.article'))
            ->whereIn($db->quoteName('name'), array(self::ALAN_DARPHANE, self::ALAN_HUKUMDAR, self::ALAN_YERLESIM,
                self::ALAN_SIKKE_KIMLIK, self::ALAN_BASLANGIC, self::ALAN_BITIS), ParameterType::STRING);
        $db->setQuery($q);
        $k = array();

        foreach ((array) $db->loadObjectList() as $r) {
            $k[(string) $r->name] = (int) $r->id;
        }

        return $k;
    }

    /** Makalenin ilgili alan değerleri: [alan adı => değer]. */
    public static function alanDegerleri($id)
    {
        $k = self::alanKimlikleri();

        if (!$k) {
            return array();
        }

        $db = self::db();
        $q  = $db->getQuery(true)
            ->select($db->quoteName(array('field_id', 'value')))
            ->from($db->quoteName('#__fields_values'))
            ->where($db->quoteName('item_id') . ' = ' . $db->quote((string) (int) $id))
            ->whereIn($db->quoteName('field_id'), array_values($k));
        $db->setQuery($q);
        $ad = array_flip($k);
        $v  = array();

        foreach ((array) $db->loadObjectList() as $r) {
            $v[$ad[(int) $r->field_id]] = trim((string) $r->value);
        }

        return $v;
    }

    /** Makalenin yayındaki etiketleri ve üst etiketlerinin alias'ı. */
    public static function etiketler($id)
    {
        $db = self::db();
        $q  = $db->getQuery(true)
            ->select(array($db->quoteName('t.id', 'id'), $db->quoteName('t.alias', 'alias'), $db->quoteName('t.title', 'ad'),
                $db->quoteName('t.note', 'not'), $db->quoteName('p.alias', 'ust')))
            ->from($db->quoteName('#__contentitem_tag_map', 'm'))
            ->join('INNER', $db->quoteName('#__tags', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('m.tag_id'))
            ->join('INNER', $db->quoteName('#__tags', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('t.parent_id'))
            ->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_content.article'))
            ->where($db->quoteName('m.content_item_id') . ' = ' . (int) $id)
            ->where($db->quoteName('t.published') . ' = 1');
        $db->setQuery($q);

        return array_map(function ($r) {
            return array('id' => (int) $r->id, 'alias' => (string) $r->alias, 'ad' => (string) $r->ad, 'not' => (string) $r->not,
                'ust' => (string) $r->ust);
        }, (array) $db->loadObjectList());
    }

    /** Association ile makalenin $dil karşılığı; yoksa kendisi. */
    public static function karsilik($id, $dil)
    {
        $db = self::db();
        $q  = $db->getQuery(true)
            ->select($db->quoteName('c.id'))
            ->from($db->quoteName('#__associations', 'a1'))
            ->join('INNER', $db->quoteName('#__associations', 'a2') . ' ON ' . $db->quoteName('a2.key') . ' = '
                . $db->quoteName('a1.key') . ' AND ' . $db->quoteName('a2.context') . ' = ' . $db->quoteName('a1.context'))
            ->join('INNER', $db->quoteName('#__content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a2.id'))
            ->where($db->quoteName('a1.context') . ' = ' . $db->quote('com_content.item'))
            ->where($db->quoteName('a1.id') . ' = ' . (int) $id)
            ->where($db->quoteName('c.language') . ' = ' . $db->quote($dil))
            ->setLimit(1);
        $db->setQuery($q);
        $r = (int) $db->loadResult();

        return $r > 0 ? $r : (int) $id;
    }

    /** Kategorinin istenen dildeki eşi (com_categories.item ilişkisi); yoksa kendisi. Bölge çipi: TR 17 → EN 49. */
    public static function kategoriKarsiligi($id, $dil)
    {
        $db = self::db();
        $q  = $db->getQuery(true)
            ->select($db->quoteName('c.id'))
            ->from($db->quoteName('#__associations', 'a1'))
            ->join('INNER', $db->quoteName('#__associations', 'a2') . ' ON ' . $db->quoteName('a2.key') . ' = '
                . $db->quoteName('a1.key') . ' AND ' . $db->quoteName('a2.context') . ' = ' . $db->quoteName('a1.context'))
            ->join('INNER', $db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a2.id'))
            ->where($db->quoteName('a1.context') . ' = ' . $db->quote('com_categories.item'))
            ->where($db->quoteName('a1.id') . ' = ' . (int) $id)
            ->where($db->quoteName('c.language') . ' = ' . $db->quote($dil))
            ->where($db->quoteName('c.published') . ' = 1')
            ->setLimit(1);
        $db->setQuery($q);
        $r = (int) $db->loadResult();

        return $r > 0 ? $r : (int) $id;
    }

    /**
     * Sayfanın videoları (numistr_videolar; video_esitle.php doldurur). Tablo yoksa ya da okunamazsa boş dizi:
     * sayfa hiçbir zaman düşmez. Önce 16:9 video, sonra en yeni.
     */
    public static function videolar($id, $dil)
    {
        $id   = (int) $id;
        $kisa = $dil === 'en-GB' ? 'en' : 'tr';

        try {
            $db = self::db();
            $q  = $db->getQuery(true)
                ->select($db->quoteName(array('youtube_id', 'bicim', 'baslik', 'aciklama', 'kunye', 'sure_sn', 'kucuk_resim', 'yayin_tarihi')))
                ->from($db->quoteName(self::VIDEO_TABLO))
                ->where($db->quoteName('makale_id') . ' = :id')
                ->where($db->quoteName('dil') . ' = :dil')
                ->bind(':id', $id, ParameterType::INTEGER)
                ->bind(':dil', $kisa, ParameterType::STRING)
                ->order($db->quoteName('bicim') . ' = ' . $db->quote('video') . ' DESC, ' . $db->quoteName('yayin_tarihi') . ' DESC');
            $db->setQuery($q, 0, self::VIDEO_SINIRI);
            $satirlar = (array) $db->loadAssocList();
        } catch (\Throwable $e) {
            return array();
        }

        $kok = rtrim(Uri::root(), '/');

        return array_values(array_filter(array_map(function ($r) use ($kok) { return self::videoOgesi($r, $kok); }, $satirlar)));
    }

    /** Yayındaki makaleler (bağlantı için): [['id','baslik','url'], ...] — kimlik sırası korunur. */
    public static function makaleler(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (!$ids) {
            return array();
        }

        $db = self::db();
        $q  = self::yayinda($db, $db->getQuery(true)
            ->select($db->quoteName(array('c.id', 'c.title', 'c.alias', 'c.catid', 'c.language')))
            ->from($db->quoteName('#__content', 'c'))
            ->whereIn($db->quoteName('c.id'), $ids));
        $db->setQuery($q);
        $s = array();

        foreach ((array) $db->loadObjectList('id') as $r) {
            $s[(int) $r->id] = self::satir($r);
        }

        $out = array();

        foreach ($ids as $id) {
            if (isset($s[$id])) {
                $out[] = $s[$id];
            }
        }

        return $out;
    }

    /**
     * Bir alanı verilen değerlerden birini taşıyan sikkeler (sayfa dilinin kopyaları), görseli olan önde.
     *
     * @return  array  [['id','baslik','url','gorsel'], ...]
     */
    public static function sikkeler($alan, array $degerler, $dil, $haric, $limit)
    {
        $k        = self::alanKimlikleri();
        $degerler = array_values(array_filter(array_map('strval', $degerler), 'strlen'));

        if (!$degerler || empty($k[$alan])) {
            return array();
        }

        $db = self::db();
        $q  = self::yayinda($db, $db->getQuery(true)
            ->select($db->quoteName(array('c.id', 'c.title', 'c.alias', 'c.catid', 'c.language')))
            ->from($db->quoteName('#__fields_values', 'v'))
            ->join('INNER', $db->quoteName('#__content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('v.item_id'))
            ->where($db->quoteName('v.field_id') . ' = ' . (int) $k[$alan])
            ->whereIn($db->quoteName('v.value'), $degerler, ParameterType::STRING)
            ->whereIn($db->quoteName('c.language'), self::sikkeDilleri($dil), ParameterType::STRING)
            ->where($db->quoteName('c.id') . ' <> ' . (int) $haric)
            ->order($db->quoteName('c.hits') . ' DESC')
            ->setLimit(max(1, (int) $limit) * 3));
        $db->setQuery($q);
        $satirlar = array_map(array(__CLASS__, 'satir'), (array) $db->loadObjectList());

        if (!$satirlar) {
            return array();
        }

        $gorsel = self::gorseller(array_column($satirlar, 'id'));

        foreach ($satirlar as &$s) {
            $s['gorsel'] = $gorsel[$s['id']] ?? null;
        }

        unset($s);

        return self::gorselliOnde($satirlar, $limit);
    }

    /** Darphanelerde sikke bastıran hükümdarlar ve sikke sayıları (çoktan aza). */
    public static function hukumdarlar(array $darphaneler, $dil, $limit)
    {
        $k = self::alanKimlikleri();

        if (!$darphaneler || empty($k[self::ALAN_DARPHANE]) || empty($k[self::ALAN_HUKUMDAR])) {
            return array();
        }

        $db = self::db();
        $q  = $db->getQuery(true)
            ->select(array($db->quoteName('a.value', 'ad'), 'COUNT(DISTINCT ' . $db->quoteName('m.item_id') . ') AS ' . $db->quoteName('n')))
            ->from($db->quoteName('#__fields_values', 'm'))
            ->join('INNER', $db->quoteName('#__fields_values', 'a') . ' ON ' . $db->quoteName('a.item_id') . ' = '
                . $db->quoteName('m.item_id') . ' AND ' . $db->quoteName('a.field_id') . ' = ' . (int) $k[self::ALAN_HUKUMDAR])
            ->join('INNER', $db->quoteName('#__content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('m.item_id'))
            ->where($db->quoteName('m.field_id') . ' = ' . (int) $k[self::ALAN_DARPHANE])
            ->whereIn($db->quoteName('m.value'), array_values($darphaneler), ParameterType::STRING)
            ->where('TRIM(' . $db->quoteName('a.value') . ') <> ' . $db->quote(''))
            ->where($db->quoteName('c.state') . ' = 1')
            ->whereIn($db->quoteName('c.language'), self::sikkeDilleri($dil), ParameterType::STRING)
            ->group($db->quoteName('a.value'))
            ->order($db->quoteName('n') . ' DESC')
            ->setLimit(max(1, (int) $limit));
        $db->setQuery($q);

        return array_map(function ($r) { return array('ad' => (string) $r->ad, 'n' => (int) $r->n); },
            (array) $db->loadObjectList());
    }

    /** Yer / hükümdar etiketlerinden en az birini taşıyan yazılar (sayfa dilinde), yeniden eskiye. */
    public static function yazilar(array $yerAliaslari, array $hukumdarAliaslari, $dil, $haric, $limit)
    {
        $kosul = array();
        $db    = self::db();

        if ($yerAliaslari) {
            $kosul[] = '(' . $db->quoteName('p.alias') . ' = ' . $db->quote(self::UST_YER) . ' AND ' . $db->quoteName('t.alias')
                . ' IN (' . implode(',', array_map(array($db, 'quote'), $yerAliaslari)) . '))';
        }

        if ($hukumdarAliaslari) {
            $kosul[] = '(' . $db->quoteName('p.alias') . ' = ' . $db->quote(self::UST_HUKUMDAR) . ' AND ' . $db->quoteName('t.alias')
                . ' IN (' . implode(',', array_map(array($db, 'quote'), $hukumdarAliaslari)) . '))';
        }

        if (!$kosul) {
            return array();
        }

        $q = self::yayinda($db, $db->getQuery(true)
            ->select('DISTINCT ' . implode(',', $db->quoteName(array('c.id', 'c.title', 'c.alias', 'c.catid', 'c.language', 'c.publish_up'))))
            ->from($db->quoteName('#__contentitem_tag_map', 'm'))
            ->join('INNER', $db->quoteName('#__tags', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('m.tag_id'))
            ->join('INNER', $db->quoteName('#__tags', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('t.parent_id'))
            ->join('INNER', $db->quoteName('#__content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('m.content_item_id'))
            ->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_content.article'))
            ->where($db->quoteName('t.published') . ' = 1')
            ->where('(' . implode(' OR ', $kosul) . ')')
            ->whereIn($db->quoteName('c.language'), array($dil, '*'), ParameterType::STRING)
            ->where($db->quoteName('c.id') . ' <> ' . (int) $haric)
            ->order($db->quoteName('c.publish_up') . ' DESC')
            ->setLimit(max(1, (int) $limit)));
        $db->setQuery($q);

        return array_map(array(__CLASS__, 'satir'), (array) $db->loadObjectList());
    }

    /**
     * Sikke sayfasının ilgili yazıları (1.4.0): darphanenin yer etiketi ya da hükümdarın etiketi.
     * Adayları getirir, dönem / etiket sayılarını ekler; süzme ve sıralama sikkeYazilariniSirala'da.
     *
     * @return  array  [['id','baslik','url'], ...]
     */
    public static function sikkeYazilari($darphane, $hukumdar, array $sikkeDonemleri, $dil, $haric, $limit)
    {
        $yer = self::yerEtiketi($darphane);
        $huk = trim((string) $hukumdar) !== '' ? self::sade($hukumdar) : '';
        $db  = self::db();

        $kosul = array('(' . $db->quoteName('p.alias') . ' = ' . $db->quote(self::UST_YER) . ' AND ' . $db->quoteName('t.alias')
            . ' = ' . $db->quote($yer) . ')');

        if ($huk !== '') {
            $kosul[] = '(' . $db->quoteName('p.alias') . ' = ' . $db->quote(self::UST_HUKUMDAR) . ' AND ' . $db->quoteName('t.alias')
                . ' = ' . $db->quote($huk) . ')';
        }

        $q = self::yayinda($db, $db->getQuery(true)
            ->select(array_merge($db->quoteName(array('c.id', 'c.title', 'c.alias', 'c.catid', 'c.language', 'c.publish_up')),
                array($db->quoteName('p.alias', 'ust'))))
            ->from($db->quoteName('#__contentitem_tag_map', 'm'))
            ->join('INNER', $db->quoteName('#__tags', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('m.tag_id'))
            ->join('INNER', $db->quoteName('#__tags', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('t.parent_id'))
            ->join('INNER', $db->quoteName('#__content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('m.content_item_id'))
            ->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_content.article'))
            ->where($db->quoteName('t.published') . ' = 1')
            ->where('(' . implode(' OR ', $kosul) . ')')
            ->whereIn($db->quoteName('c.language'), array($dil, '*'), ParameterType::STRING)
            ->where($db->quoteName('c.id') . ' <> ' . (int) $haric)
            ->setLimit(300));
        $db->setQuery($q);
        $yazilar = array();

        foreach ((array) $db->loadObjectList() as $r) {
            $id = (int) $r->id;

            if (!isset($yazilar[$id])) {
                $yazilar[$id] = array('id' => $id, 'baslik' => (string) $r->title, 'alias' => (string) $r->alias,
                    'catid' => (int) $r->catid, 'language' => (string) $r->language, 'publish_up' => (string) $r->publish_up,
                    'yer' => false, 'huk' => false, 'donem' => array(), 'yer_sayisi' => 0, 'etiket_sayisi' => 0);
            }

            $yazilar[$id][$r->ust === self::UST_YER ? 'yer' : 'huk'] = true;
        }

        if (!$yazilar) {
            return array();
        }

        // Adayların dönem etiketleri ve yer + hükümdar etiketi sayısı (odak ölçüsü)
        $q = $db->getQuery(true)
            ->select(array($db->quoteName('m.content_item_id', 'id'), $db->quoteName('t.alias', 'alias'), $db->quoteName('p.alias', 'ust')))
            ->from($db->quoteName('#__contentitem_tag_map', 'm'))
            ->join('INNER', $db->quoteName('#__tags', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('m.tag_id'))
            ->join('INNER', $db->quoteName('#__tags', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('t.parent_id'))
            ->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_content.article'))
            ->where($db->quoteName('t.published') . ' = 1')
            ->where($db->quoteName('m.content_item_id') . ' IN (' . implode(',', array_keys($yazilar)) . ')')
            ->whereIn($db->quoteName('p.alias'), array(self::UST_YER, self::UST_HUKUMDAR, self::UST_DONEM), ParameterType::STRING);
        $db->setQuery($q);

        foreach ((array) $db->loadObjectList() as $r) {
            $id = (int) $r->id;

            if ($r->ust === self::UST_DONEM) {
                $yazilar[$id]['donem'][] = preg_replace('/^donem-/', '', (string) $r->alias);
            } else {
                $yazilar[$id]['etiket_sayisi']++;
                $yazilar[$id]['yer_sayisi'] += $r->ust === self::UST_YER ? 1 : 0;
            }
        }

        foreach ($yazilar as &$y) {
            $y['eslesme'] = $y['yer'] && $y['huk'] ? 'ikisi' : ($y['huk'] ? 'hukumdar' : 'darphane');
        }
        unset($y);

        return array_map(function ($y) {
            return self::satir((object) array('id' => $y['id'], 'title' => $y['baslik'], 'alias' => $y['alias'],
                'catid' => $y['catid'], 'language' => $y['language']));
        }, self::sikkeYazilariniSirala(array_values($yazilar), $sikkeDonemleri, $limit));
    }

    /** Sade biçimi (etiket alias'ı) verilen hükümdarların authority-name değerleri. */
    public static function hukumdarAdlari(array $aliaslar)
    {
        $k = self::alanKimlikleri();

        if (!$aliaslar || empty($k[self::ALAN_HUKUMDAR])) {
            return array();
        }

        $db = self::db();
        $q  = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('value'))
            ->from($db->quoteName('#__fields_values'))
            ->where($db->quoteName('field_id') . ' = ' . (int) $k[self::ALAN_HUKUMDAR])
            ->where('TRIM(' . $db->quoteName('value') . ') <> ' . $db->quote(''));
        $db->setQuery($q);
        $istenen = array_flip($aliaslar);

        return array_values(array_filter(array_map('strval', (array) $db->loadColumn()),
            function ($ad) use ($istenen) { return isset($istenen[self::sade($ad)]); }));
    }

    /**
     * Sikke kimliği → ilk görselin adresi (lisans kapısından geçen). EN kopyanın görseli coin-id alanıyla TR
     * karşılığından alınır.
     */
    public static function gorseller(array $sikkeIds)
    {
        $sikkeIds = array_values(array_unique(array_filter(array_map('intval', $sikkeIds))));
        $k        = self::alanKimlikleri();

        if (!$sikkeIds) {
            return array();
        }

        $db = self::db();

        // 1. Görsel sahibi adayları: TR ('*') kopyası kendisi; EN kopya coin-id ile TR karşılık(lar)ına — katalogda aynı
        //    coin-id'yi taşıyan mükerrer TR kayıtlar var (ör. EN 28213 → TR 22614 ve 9468), görseli olan ilk aday seçilir.
        $sahip = array();

        foreach ($sikkeIds as $s) {
            $sahip[$s] = array($s);
        }

        if (!empty($k[self::ALAN_SIKKE_KIMLIK])) {
            $f = (int) $k[self::ALAN_SIKKE_KIMLIK];
            $q = $db->getQuery(true)
                ->select(array($db->quoteName('e.item_id', 'kopya'), $db->quoteName('t.item_id', 'asil')))
                ->from($db->quoteName('#__fields_values', 'e'))
                ->join('INNER', $db->quoteName('#__fields_values', 't') . ' ON ' . $db->quoteName('t.field_id') . ' = ' . $f
                    . ' AND ' . $db->quoteName('t.value') . ' = ' . $db->quoteName('e.value'))
                ->join('INNER', $db->quoteName('#__content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('t.item_id'))
                ->join('INNER', $db->quoteName('#__content', 'ce') . ' ON ' . $db->quoteName('ce.id') . ' = ' . $db->quoteName('e.item_id'))
                ->where($db->quoteName('e.field_id') . ' = ' . $f)
                ->whereIn($db->quoteName('e.item_id'), array_map('strval', $sikkeIds), ParameterType::STRING)
                ->where($db->quoteName('ce.language') . ' = ' . $db->quote('en-GB'))
                ->where($db->quoteName('c.language') . ' = ' . $db->quote('*'));
            $db->setQuery($q);

            $tr = array();

            foreach ((array) $db->loadObjectList() as $r) {
                $tr[(int) $r->kopya][] = (int) $r->asil;
            }

            foreach ($tr as $kopya => $asillar) {
                sort($asillar);
                $sahip[$kopya] = $asillar;
            }
        }

        // 2. Sahiplerin ilk görünür görseli (ordering sırasıyla).
        $q = $db->getQuery(true)
            ->select($db->quoteName(array('i.coin_id', 'i.image_id', 'i.ordering')))
            ->from($db->quoteName('coins_images', 'i'))
            ->whereIn($db->quoteName('i.coin_id'), array_values(array_unique(array_merge(...array_values($sahip)))))
            ->where('(' . $db->quoteName('i.license_ok') . ' = 1 OR (' . $db->quoteName('i.license_ok') . ' = 0 AND '
                . $db->quoteName('i.license') . ' IS NOT NULL AND TRIM(' . $db->quoteName('i.license') . ") <> ''))")
            ->where($db->quoteName('i.source_unavailable') . ' = 0') // 1.0.1 (G12): kaynağı erişilemeyen görsel kart olmaz
            ->order($db->quoteName(array('i.coin_id', 'i.ordering', 'i.image_id')));
        $db->setQuery($q);
        $ilk = array();

        foreach ((array) $db->loadObjectList() as $r) {
            if (!isset($ilk[(int) $r->coin_id])) {
                $ilk[(int) $r->coin_id] = (int) $r->image_id;
            }
        }

        $kok = rtrim(Uri::root(), '/');
        $out = array();

        foreach ($sahip as $sikke => $asillar) {
            foreach ($asillar as $asil) {
                if (isset($ilk[$asil])) {
                    $out[$sikke] = $kok . '/index.php?option=com_numistr&view=gorsel&format=raw&id=' . $ilk[$asil] . '&wm=0';
                    break;
                }
            }
        }

        return $out;
    }

    /** Makale özeti: kategori, tarih, kelime sayısı, giriş görseli. Yayında değilse null. */
    public static function makaleOzet($id)
    {
        $db = self::db();
        $q  = self::yayinda($db, $db->getQuery(true)
            ->select($db->quoteName(array('c.id', 'c.title', 'c.alias', 'c.catid', 'c.language', 'c.publish_up', 'c.introtext', 'c.fulltext', 'c.images')))
            ->select($db->quoteName('k.title', 'kat_baslik'))
            ->from($db->quoteName('#__content', 'c'))
            ->join('INNER', $db->quoteName('#__categories', 'k') . ' ON ' . $db->quoteName('k.id') . ' = ' . $db->quoteName('c.catid'))
            ->where($db->quoteName('c.id') . ' = ' . (int) $id));
        $db->setQuery($q);
        $r = $db->loadObject();

        if (!$r) {
            return null;
        }

        return array(
            'id'         => (int) $r->id,
            'catid'      => (int) $r->catid,
            'kat_baslik' => trim((string) $r->kat_baslik),
            'kat_url'    => Route::_(RouteHelper::getCategoryRoute((int) $r->catid, (string) $r->language)),
            'tarih'      => (string) $r->publish_up,
            'kelime'     => self::kelimeSayisi($r->introtext . ' ' . $r->fulltext),
            'gorsel'     => self::introGorsel($r->images),
        );
    }

    /** Aynı kategorinin (serinin) yayındaki yazıları, en yeni önce: [['id','baslik','url','gorsel','tarih'], ...]. */
    public static function seriYazilari($catid, $dil, $haric, $limit)
    {
        $db = self::db();
        $q  = self::yayinda($db, $db->getQuery(true)
            ->select($db->quoteName(array('c.id', 'c.title', 'c.alias', 'c.catid', 'c.language', 'c.images', 'c.publish_up')))
            ->from($db->quoteName('#__content', 'c'))
            ->where($db->quoteName('c.catid') . ' = ' . (int) $catid)
            ->whereIn($db->quoteName('c.language'), array($dil, '*'), ParameterType::STRING)
            ->where($db->quoteName('c.id') . ' <> ' . (int) $haric)
            ->order($db->quoteName('c.publish_up') . ' DESC')
            ->setLimit(max(1, (int) $limit)));
        $db->setQuery($q);

        return array_map(function ($r) {
            return self::satir($r) + array('gorsel' => self::introGorsel($r->images), 'tarih' => (string) $r->publish_up);
        }, (array) $db->loadObjectList());
    }

    /** Üst bilgi çipleri: yer → yerleşim sayfası (yoksa arama), hükümdar → site araması. */
    /** Etiket notu "en=Lydia;kat=17" → ['en' => 'Lydia', 'kat' => '17']. */
    public static function notCoz($not)
    {
        $s = array();

        foreach (explode(';', (string) $not) as $parca) {
            $kv = explode('=', $parca, 2);

            if (count($kv) === 2 && trim($kv[0]) !== '') {
                $s[trim($kv[0])] = trim($kv[1]);
            }
        }

        return $s;
    }

    /** Bölge / dönem etiketinin görünen adı: EN sayfada nottaki İngilizce ad, yoksa etiket başlığı. */
    public static function etiketAdi(array $e, $dil)
    {
        $en = self::notCoz($e['not'] ?? '')['en'] ?? '';

        return $dil === 'en-GB' && $en !== '' ? $en : (string) ($e['ad'] ?? $e['alias'] ?? '');
    }

    public static function etiketCipleri(array $etiketler, $dil)
    {
        $g      = self::etiketGruplari($etiketler);
        $cipler = array();

        foreach (array_slice($g['bolge'], 0, self::CIP_SINIRI) as $e) {
            $kat      = (int) (self::notCoz($e['not'] ?? '')['kat'] ?? 0);
            $kat      = $kat > 0 ? self::kategoriKarsiligi($kat, $dil) : 0;
            $cipler[] = array('tur' => 'bolge', 'ad' => self::etiketAdi($e, $dil),
                'url' => $kat > 0 ? Route::_(RouteHelper::getCategoryRoute($kat, $dil)) : self::aramaUrl($dil, self::etiketAdi($e, $dil)));
        }

        foreach (array_slice($g['yer'], 0, self::CIP_SINIRI) as $alias) {
            $yer      = self::yerlesimler(array(self::darphaneAnahtari($alias)), $dil);
            $cipler[] = $yer
                ? array('tur' => 'yer', 'ad' => $yer[0]['baslik'], 'url' => $yer[0]['url'])
                : array('tur' => 'yer', 'ad' => $alias, 'url' => self::aramaUrl($dil, $alias));
        }

        foreach (self::hukumdarAdlari(array_slice($g['hukumdar'], 0, self::CIP_SINIRI)) as $ad) {
            $cipler[] = array('tur' => 'hukumdar', 'ad' => $ad, 'url' => self::aramaUrl($dil, $ad));
        }

        return $cipler;
    }

    // ------------------------------------------------------------------ bloklar

    /**
     * Modülün tüm verisi (moduleCache ile sayfa başına önbelleklenir).
     *
     * @return  array  ['tur' => .., 'bloklar' => [['tur' => sikke|liste|baglanti|hukumdar, 'baslik' => [anahtar, arg],
     *                  'ogeler' => [...]], ...]]
     */
    public static function veri($id, $dil, $limit, $secenek = array())
    {
        $id        = (int) $id;
        $limit     = max(1, min(24, (int) $limit));
        $bolum     = ($secenek['bolum'] ?? 'alt') === 'ust' ? 'ust' : 'alt';
        $alanlar   = self::alanDegerleri($id);
        $etiketler = self::etiketler($id);
        $tur       = self::sayfaTuru($alanlar, $etiketler);
        $bloklar   = array();
        $h         = self::harita();
        $ozet      = null;
        $blog      = false;

        // Blog kategorisindeki yazı, etiketi olmasa da yazı sayfasıdır (seri, uygulama ve üst bilgi blokları).
        if ($tur === '' || $tur === 'yazi') {
            $ozet = self::makaleOzet($id);
            $blog = $ozet !== null && in_array($ozet['catid'], self::kategoriListesi($secenek['blog'] ?? ''), true);
            $tur  = $blog ? 'yazi' : $tur;
        }

        if ($bolum === 'ust') {
            if (!$blog) {
                return array('tur' => $tur, 'blog' => false, 'bloklar' => array());
            }

            $ust = self::blok('ust', '', '', array(1)) + array(
                'seri'   => array('baslik' => $ozet['kat_baslik'], 'url' => $ozet['kat_url']),
                'tarih'  => $ozet['tarih'],
                'dakika' => self::okumaSuresi($ozet['kelime'], (int) ($secenek['okuma_hizi'] ?? self::OKUMA_HIZI)),
                'cipler' => self::etiketCipleri($etiketler, $dil),
                'donem'  => array_map(function ($e) use ($dil) { return self::etiketAdi($e, $dil); },
                    self::etiketGruplari($etiketler)['donem']),
            );

            return array('tur' => 'yazi', 'blog' => true, 'bloklar' => array($ust));
        }

        // Video (1.3.0): sayfa türünden bağımsız, alt bölümün ilk bloğu (Pazarlama Merkezi ilgili_sayfa eşlemesi).
        $bloklar[] = self::blok('video', 'MOD_NUMISTR_ILGILI_VIDEO', '', self::videolar($id, $dil));

        if ($tur === 'yerlesim') {
            $trId        = $dil === 'tr-TR' ? $id : self::karsilik($id, 'tr-TR');
            $darphaneler = self::tersHarita($h)[$trId] ?? array();

            if ($darphaneler) {
                $bloklar[] = self::blok('sikke', 'MOD_NUMISTR_ILGILI_KENTIN_SIKKELERI', '',
                    self::sikkeler(self::ALAN_DARPHANE, $darphaneler, $dil, 0, $limit));
                $bloklar[] = self::blok('hukumdar', 'MOD_NUMISTR_ILGILI_BASTIRANLAR', '', self::hukumdarlar($darphaneler, $dil, 12));
                $bloklar[] = self::blok('liste', 'MOD_NUMISTR_ILGILI_KENTTEN_SOZ_EDEN', '',
                    self::yazilar(array_map(array(__CLASS__, 'yerEtiketi'), $darphaneler), array(), $dil, $id, 6));
            }
        } elseif ($tur === 'sikke') {
            $darphane = $alanlar[self::ALAN_DARPHANE];
            $hukumdar = $alanlar[self::ALAN_HUKUMDAR] ?? '';
            $yer      = self::yerlesimler(array($darphane), $dil);

            $bloklar[] = self::blok('baglanti', 'MOD_NUMISTR_ILGILI_DARPHANE', '', $yer);
            $bloklar[] = self::blok('sikke', 'MOD_NUMISTR_ILGILI_AYNI_DARPHANE', '',
                self::sikkeler(self::ALAN_DARPHANE, array($darphane), $dil, $id, $limit));

            if ($hukumdar !== '') {
                $bloklar[] = self::blok('sikke', 'MOD_NUMISTR_ILGILI_HUKUMDARIN_SIKKELERI', $hukumdar,
                    self::sikkeler(self::ALAN_HUKUMDAR, array($hukumdar), $dil, $id, $limit));
            }

            // 1.4.0: dönem süzgeci + yeni sıra (önce yalnız yayın tarihine göre ilk 6 idi)
            $bloklar[] = self::blok('liste', 'MOD_NUMISTR_ILGILI_YAZILAR', '',
                self::sikkeYazilari($darphane, $hukumdar,
                    self::sikkeDonemleri($alanlar[self::ALAN_BASLANGIC] ?? '', $alanlar[self::ALAN_BITIS] ?? ''), $dil, $id, 6));
        } elseif ($tur === 'yazi') {
            if ($blog) {
                $seri    = self::seriYazilari($ozet['catid'], $dil, $id, max(1, (int) ($secenek['seri_limit'] ?? 4)));
                $seriIds = array_column($seri, 'id');
                $g       = self::etiketGruplari($etiketler);
                $yaziLim = max(1, (int) ($secenek['yazi_limit'] ?? 6));
                $konu    = array_values(array_filter(
                    self::yazilar($g['yer'], $g['hukumdar'], $dil, $id, $yaziLim + count($seriIds)),
                    function ($y) use ($seriIds) { return !in_array($y['id'], $seriIds, true); }));

                // Yer / hükümdar eşleşmesi azsa: aynı bölge + dönem, sonra aynı bölge, sonra aynı dönem (C18-D)
                if (count($konu) < $yaziLim && ($g['bolge'] || $g['donem'])) {
                    $konu = array_merge($konu, self::benzerYazilar(array_column($g['bolge'], 'id'), array_column($g['donem'], 'id'), $dil,
                        array_merge(array($id), $seriIds, array_column($konu, 'id')), $yaziLim - count($konu)));
                }

                $bloklar[] = self::blok('kart', 'MOD_NUMISTR_ILGILI_SERININ_YAZILARI', $ozet['kat_baslik'], $seri);

                if ((int) ($secenek['cta'] ?? 1) === 1) {
                    $bloklar[] = self::blok('cta', 'MOD_NUMISTR_ILGILI_UYGULAMA_BASLIK', '',
                        array(array('url' => self::uygulamaUrl($dil, $id))));
                }

                $bloklar[] = self::blok('liste', 'MOD_NUMISTR_ILGILI_KONUYLA_ILGILI', '',
                    array_slice($konu, 0, max(1, (int) ($secenek['yazi_limit'] ?? 6))));
            }

            $g = self::etiketGruplari($etiketler);

            foreach (array_slice($g['yer'], 0, self::ETIKET_SINIRI) as $alias) {
                $darphane = self::darphaneAnahtari($alias);
                $yer      = self::yerlesimler(array($darphane), $dil);
                $ad       = $yer ? $yer[0]['baslik'] : $alias;
                $bloklar[] = self::blok('baglanti', 'MOD_NUMISTR_ILGILI_YERLESIM', $ad, $yer);
                $bloklar[] = self::blok('sikke', 'MOD_NUMISTR_ILGILI_YERIN_SIKKELERI', $ad,
                    self::sikkeler(self::ALAN_DARPHANE, array($darphane), $dil, 0, min($limit, 4)));
            }

            $adlar = self::hukumdarAdlari(array_slice($g['hukumdar'], 0, self::ETIKET_SINIRI));

            foreach ($adlar as $ad) {
                $bloklar[] = self::blok('sikke', 'MOD_NUMISTR_ILGILI_HUKUMDARIN_SIKKELERI', $ad,
                    self::sikkeler(self::ALAN_HUKUMDAR, array($ad), $dil, 0, $limit));
            }
        }

        return array('tur' => $tur, 'blog' => $blog,
            'bloklar' => array_values(array_filter($bloklar, function ($b) { return !empty($b['ogeler']); })));
    }

    /** Darphanelerin yerleşim makaleleri sayfa dilinde (eşleme TR kimliği verir; EN için association). */
    public static function yerlesimler(array $darphaneler, $dil)
    {
        $h   = self::harita();
        $ids = array();

        foreach ($darphaneler as $d) {
            foreach ($h[$d] ?? array() as $trId) {
                $ids[] = $dil === 'tr-TR' ? $trId : self::karsilik($trId, $dil);
            }
        }

        return self::makaleler($ids);
    }

    private static function blok($tur, $anahtar, $arg, array $ogeler)
    {
        return array('tur' => $tur, 'baslik' => array($anahtar, (string) $arg), 'ogeler' => array_values($ogeler));
    }

    /**
     * Aynı bölge ve / veya dönem etiketli yazılar: puan = 2 × bölge eşleşmesi + dönem eşleşmesi, sonra en yeni (C18-D).
     *
     * @return  array  [['id','baslik','url'], ...]
     */
    public static function benzerYazilar(array $bolgeIds, array $donemIds, $dil, array $haric, $limit)
    {
        $bolgeIds = array_values(array_filter(array_map('intval', $bolgeIds)));
        $donemIds = array_values(array_filter(array_map('intval', $donemIds)));

        if ((!$bolgeIds && !$donemIds) || $limit < 1) {
            return array();
        }

        $db    = self::db();
        $hepsi = array_merge($bolgeIds, $donemIds);
        $puan  = ($bolgeIds ? '2 * MAX(CASE WHEN ' . $db->quoteName('m.tag_id') . ' IN (' . implode(',', $bolgeIds) . ') THEN 1 ELSE 0 END)' : '0')
            . ' + ' . ($donemIds ? 'MAX(CASE WHEN ' . $db->quoteName('m.tag_id') . ' IN (' . implode(',', $donemIds) . ') THEN 1 ELSE 0 END)' : '0');
        $q     = self::yayinda($db, $db->getQuery(true)
            ->select(array_merge($db->quoteName(array('c.id', 'c.title', 'c.alias', 'c.catid', 'c.language', 'c.publish_up')),
                array($puan . ' AS ' . $db->quoteName('puan'))))
            ->from($db->quoteName('#__contentitem_tag_map', 'm'))
            ->join('INNER', $db->quoteName('#__content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('m.content_item_id'))
            ->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_content.article'))
            ->where($db->quoteName('m.tag_id') . ' IN (' . implode(',', $hepsi) . ')')
            ->whereIn($db->quoteName('c.language'), array($dil, '*'), ParameterType::STRING)
            ->where($db->quoteName('c.id') . ' NOT IN (' . implode(',', array_map('intval', $haric ?: array(0))) . ')')
            ->group($db->quoteName(array('c.id', 'c.title', 'c.alias', 'c.catid', 'c.language', 'c.publish_up')))
            ->order($db->quoteName('puan') . ' DESC, ' . $db->quoteName('c.publish_up') . ' DESC')
            ->setLimit((int) $limit));
        $db->setQuery($q);

        return array_map(array(__CLASS__, 'satir'), (array) $db->loadObjectList());
    }

    private static function satir($r)
    {
        $url = Route::_(RouteHelper::getArticleRoute((int) $r->id . ':' . $r->alias, (int) $r->catid, (string) $r->language));

        return array('id' => (int) $r->id, 'baslik' => (string) $r->title, 'url' => $url);
    }

    /** Yayında + erişilebilir + yayın tarihleri içinde. */
    private static function yayinda($db, $q)
    {
        $simdi  = $db->quote(Factory::getDate()->toSql());
        $erisim = Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();

        return $q->where($db->quoteName('c.state') . ' = 1')
            ->whereIn($db->quoteName('c.access'), array_map('intval', $erisim ?: array(1)))
            ->where('(' . $db->quoteName('c.publish_up') . ' IS NULL OR ' . $db->quoteName('c.publish_up') . ' <= ' . $simdi . ')')
            ->where('(' . $db->quoteName('c.publish_down') . ' IS NULL OR ' . $db->quoteName('c.publish_down') . ' >= ' . $simdi . ')');
    }
}
