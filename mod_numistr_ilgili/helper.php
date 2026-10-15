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

    const UST_YER      = 'yerler';
    const UST_HUKUMDAR = 'hukumdarlar';

    /** Yazı sayfasında en çok kaç yer / hükümdar etiketi için blok üretilir. */
    const ETIKET_SINIRI = 3;

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
        $g = array('yer' => array(), 'hukumdar' => array());

        foreach ($etiketler as $e) {
            $a = (string) ($e['alias'] ?? '');

            if ($a === '') {
                continue;
            }

            if (($e['ust'] ?? '') === self::UST_YER && !in_array($a, $g['yer'], true)) {
                $g['yer'][] = $a;
            } elseif (($e['ust'] ?? '') === self::UST_HUKUMDAR && !in_array($a, $g['hukumdar'], true)) {
                $g['hukumdar'][] = $a;
            }
        }

        return $g;
    }

    /** Görseli olan sikkeler önde, sıra korunarak en çok $limit tane. */
    public static function gorselliOnde(array $satirlar, $limit)
    {
        $var = array_values(array_filter($satirlar, function ($r) { return !empty($r['gorsel']); }));
        $yok = array_values(array_filter($satirlar, function ($r) { return empty($r['gorsel']); }));

        return array_slice(array_merge($var, $yok), 0, (int) $limit);
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
                self::ALAN_SIKKE_KIMLIK), ParameterType::STRING);
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
            ->select(array($db->quoteName('t.alias', 'alias'), $db->quoteName('p.alias', 'ust')))
            ->from($db->quoteName('#__contentitem_tag_map', 'm'))
            ->join('INNER', $db->quoteName('#__tags', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('m.tag_id'))
            ->join('INNER', $db->quoteName('#__tags', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('t.parent_id'))
            ->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_content.article'))
            ->where($db->quoteName('m.content_item_id') . ' = ' . (int) $id)
            ->where($db->quoteName('t.published') . ' = 1');
        $db->setQuery($q);

        return array_map(function ($r) { return array('alias' => (string) $r->alias, 'ust' => (string) $r->ust); },
            (array) $db->loadObjectList());
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

    // ------------------------------------------------------------------ bloklar

    /**
     * Modülün tüm verisi (moduleCache ile sayfa başına önbelleklenir).
     *
     * @return  array  ['tur' => .., 'bloklar' => [['tur' => sikke|liste|baglanti|hukumdar, 'baslik' => [anahtar, arg],
     *                  'ogeler' => [...]], ...]]
     */
    public static function veri($id, $dil, $limit)
    {
        $id       = (int) $id;
        $limit    = max(1, min(24, (int) $limit));
        $alanlar  = self::alanDegerleri($id);
        $etiketler = self::etiketler($id);
        $tur      = self::sayfaTuru($alanlar, $etiketler);
        $bloklar  = array();
        $h        = self::harita();

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

            $bloklar[] = self::blok('liste', 'MOD_NUMISTR_ILGILI_YAZILAR', '',
                self::yazilar(array(self::yerEtiketi($darphane)), $hukumdar !== '' ? array(self::sade($hukumdar)) : array(), $dil, $id, 6));
        } elseif ($tur === 'yazi') {
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

        return array('tur' => $tur, 'bloklar' => array_values(array_filter($bloklar, function ($b) { return !empty($b['ogeler']); })));
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
