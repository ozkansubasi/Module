<?php
/**
 * @package     NumisTR
 * @subpackage  mod_numistr_gallery
 *
 * Sikke detay sayfasının görsel galerisi. Sourcerer + Custom modül #131 + modules/custom/numistr_gallery.php
 * zincirinin yerine geçer (CVE-2026-74253; devir notu claudedocs/2026-10-22-sourcerer-cve-2026-74253-devir-notu.md).
 * HTML çıktısı eski betikle aynıdır (tests/gallery_test.php eşdeğerliği denetler); farklar: CSS / JS dosyadan ve
 * <head>'den yüklenir, GLightbox sürümü sabit + SRI, sayfa başına günlük yazımı yok, kullanılmayan kapak sorgusu yok.
 *
 * Görseller: coins_images (coin_id = makale kimliği), G12 işaretli (source_unavailable) görseller hariç, ordering sırasıyla.
 * Filigran: küçük görsel wm=0; büyük görsel wm=1, Pro oturumunda yalnız serbest lisanslı (license_ok=1) görselde wm=2
 * (ADR-006 Faz 2 + ADR-008 rev. 2026-09-25). Yetkiyi bileşen doğrular; burada yalnız adres seçilir.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

class ModNumistrGalleryHelper
{
    const GLIGHTBOX_CSS     = 'https://cdn.jsdelivr.net/npm/glightbox@3.3.1/dist/css/glightbox.min.css';
    const GLIGHTBOX_CSS_SRI = 'sha384-GPAzSuZc0kFvdIev6wm9zg8gnafE8tLso7rsAYQfc9hAdWCpOcpcNI5W9lWkYcsd';
    const GLIGHTBOX_JS      = 'https://cdn.jsdelivr.net/npm/glightbox@3.3.1/dist/js/glightbox.min.js';
    const GLIGHTBOX_JS_SRI  = 'sha384-MZZbZ6RXJudK43v1qY1zOWKOU2yfeBPatuFoKyHAaAgHTUZhwblRTc9CphTt4IGQ';

    const SURUM = '1.0.0';

    // ------------------------------------------------------------------ saf mantık (DB'siz test edilir)

    /**
     * Görsel satırları → şablon öğeleri. Satır düzeni: normal görseller iki sütun (satırın ilk görselinde ölçü yazısı
     * görünür, ikincisinde gizlenir); image_type = "detay" tam satır kaplar ve yazısı görünür. Atıf her görselde
     * gösterilir (lisans şartı görsel başınadır).
     *
     * @param   object[]  $gorseller  coins_images satırları
     * @param   int       $popupWm    oturumun büyük görsel filigranı (1 ya da Pro için 2)
     * @param   string    $kok        site kökü, sonda / olmadan
     *
     * @return  array[]
     */
    public static function ogeler(array $gorseller, $popupWm, $kok)
    {
        $ogeler = array();
        $sutun  = 0;

        foreach ($gorseller as $g) {
            $id     = (int) $g->image_id;
            $tur    = strtolower(trim((string) ($g->image_type ?? '')));
            $cift   = ($tur === 'detay');
            $imgWm  = ((int) ($g->license_ok ?? 0) === 1) ? (int) $popupWm : 1;
            $adres  = $kok . '/index.php?option=com_numistr&view=gorsel&format=raw&id=' . $id;

            $ogeler[] = array(
                'sinif'    => 'gallery-item' . ($cift ? ' pair' : ''),
                'kucuk'    => $adres . '&wm=0',
                'buyuk'    => $adres . '&wm=' . $imgWm,
                'yazi'     => $cift ? true : ($sutun === 0),
                'agirlik'  => isset($g->weight) ? trim((string) $g->weight) : '',
                'cap'      => isset($g->diameter) ? trim((string) $g->diameter) : '',
                'kaynak'   => isset($g->source_credit) ? trim((string) $g->source_credit) : '',
                'lisans'   => isset($g->license) ? trim((string) $g->license) : '',
            );

            $sutun = $cift ? 0 : ($sutun === 0 ? 1 : 0);
        }

        return $ogeler;
    }

    /** Pro grubu kimliği oturumun yetkili grupları arasındaysa 2, değilse 1. */
    public static function wmSec($misafir, array $gruplar, $proGrup)
    {
        return (!$misafir && $proGrup > 0 && in_array((int) $proGrup, array_map('intval', $gruplar), true)) ? 2 : 1;
    }

    // ------------------------------------------------------------------ Joomla'ya bağlı

    /** @return object[] */
    public static function gorseller($makaleId)
    {
        $db = Factory::getDbo();
        $q  = $db->getQuery(true)
            ->select($db->quoteName(array('image_id', 'image_type', 'weight', 'diameter', 'ordering', 'source_credit', 'license', 'license_ok')))
            ->from($db->quoteName('coins_images'))
            ->where($db->quoteName('coin_id') . ' = ' . (int) $makaleId)
            ->where($db->quoteName('source_unavailable') . ' = 0')
            ->order($db->quoteName('ordering') . ' ASC');
        $db->setQuery($q);

        return (array) $db->loadObjectList();
    }

    public static function popupWm($app)
    {
        try {
            $kim = $app->getIdentity();

            if (!$kim) {
                return 1;
            }

            $proGrup = (int) ComponentHelper::getParams('com_numistr')->get('pro_group_id', 10);

            return self::wmSec((bool) $kim->guest, (array) $kim->getAuthorisedGroups(), $proGrup);
        } catch (\Throwable $e) {
            return 1;
        }
    }

    /** GLightbox (sabit sürüm + SRI) ve modülün CSS / JS'i <head>'e; birden çok örnekte bir kez. */
    public static function varliklar()
    {
        $wa  = Factory::getApplication()->getDocument()->getWebAssetManager();
        $yol = 'modules/mod_numistr_gallery/assets';

        $wa->registerAndUseStyle('mod_numistr_gallery.glightbox', self::GLIGHTBOX_CSS, array(),
            array('integrity' => self::GLIGHTBOX_CSS_SRI, 'crossorigin' => 'anonymous'));
        $wa->registerAndUseStyle('mod_numistr_gallery', $yol . '/css/gallery.css', array('version' => self::SURUM),
            array(), array('mod_numistr_gallery.glightbox'));
        $wa->registerAndUseScript('mod_numistr_gallery.glightbox', self::GLIGHTBOX_JS, array(),
            array('integrity' => self::GLIGHTBOX_JS_SRI, 'crossorigin' => 'anonymous', 'defer' => true));
        $wa->registerAndUseScript('mod_numistr_gallery', $yol . '/js/gallery.js', array('version' => self::SURUM),
            array('defer' => true), array('mod_numistr_gallery.glightbox'));
    }
}
