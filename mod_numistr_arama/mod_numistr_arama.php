<?php
/**
 * @package     NumisTR Search Module
 * @subpackage  mod_numistr_arama
 * @version     1.0.2
 * @copyright   Copyright (C) 2026 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * 1.0.0 (16 Kas 2026): site araması. İki düzen:
 *   default — arama kutusu (ana sayfa açılışı / herhangi bir konum); JS'siz düz GET formu.
 *   sonuc   — sonuç sayfası (/tr/arama, /en/search); GET /api/index.php/v1/search (plg_webservices_numistr 1.26.0)
 *             ile JS'te çizer. Sayfa noindex. Toplam sonuç sayısı gösterilmez (konumlandırma kuralı).
 * Maket: claude.ai artifact "NumisTR ana sayfa ve arama maketi" (kullanıcı onayı 16 Kas).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

require_once __DIR__ . '/helper.php';

$app = Factory::getApplication();
$lang = ModNumistrAramaHelper::lang((string) $app->getLanguage()->getTag());
$strings = ModNumistrAramaHelper::strings($lang);
$resultsPath = ModNumistrAramaHelper::resultsPath(
    $lang,
    (string) $params->get('results_path_tr', '/tr/arama'),
    (string) $params->get('results_path_en', '/en/search')
);
$examples = ModNumistrAramaHelper::examples((string) $params->get(
    $lang === 'en' ? 'examples_en' : 'examples_tr',
    $lang === 'en' ? 'Lampsacus, winged horse, Ephesus' : 'Lampsakos, kanatlı at, Efes'
));
$surface = (string) $params->get('surface', 'dark') === 'light' ? 'light' : 'dark';
$query = ModNumistrAramaHelper::cleanQuery($app->input->getString('q', ''));
$apiBase = ModNumistrAramaHelper::apiBase((string) $params->get('api_base', '/api/index.php/v1/search'));
$sfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$version = ModNumistrAramaHelper::VERSION;

$layout = (string) $params->get('layout', 'default');

// Biçim dosyası <head>'e (media/mod_numistr_arama/css/arama.css)
try {
    $app->getDocument()->getWebAssetManager()->registerAndUseStyle(
        'mod_numistr_arama',
        'mod_numistr_arama/arama.css',
        ['version' => $version]
    );
} catch (\Throwable $e) {
    // varlık yöneticisi yoksa (eski şablon) biçimsiz ama çalışır
}

if (strpos($layout, 'sonuc') !== false) {
    $doc = $app->getDocument();
    $doc->setMetaData('robots', 'noindex, follow');
    if ($query !== '') {
        $doc->setTitle($strings['title_prefix'] . ': ' . $query);
    }
}

require ModuleHelper::getLayoutPath('mod_numistr_arama', $layout);
