<?php
/**
 * @package     NumisTR
 * @subpackage  mod_numistr_ilgili
 *
 * Yer / hükümdar bağlantılı "ilgili içerik" blokları (ADR-010). Yalnız makale sayfasında (com_content, view=article)
 * çalışır; sayfa ilgisizse ya da veri yoksa hiçbir şey basmaz. Hata sayfayı asla düşürmez (günlüğe yazılır).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;

require_once __DIR__ . '/helper.php';

$app = Factory::getApplication();

if ($app->input->getCmd('option') !== 'com_content' || $app->input->getCmd('view') !== 'article') {
    return;
}

$makaleId = $app->input->getInt('id', 0);

if ($makaleId <= 0) {
    return;
}

$dil = $app->getLanguage()->getTag();

$cacheparams               = new \stdClass();
$cacheparams->cachemode    = 'safeuri';
$cacheparams->class        = 'ModNumistrIlgiliHelper';
$cacheparams->method       = 'veri';
$secenek = array(
    'bolum'      => (string) $params->get('bolum', 'alt'),
    'blog'       => (string) $params->get('blog_kategorileri', ModNumistrIlgiliHelper::BLOG_KATEGORILERI),
    'seri_limit' => (int) $params->get('seri_limit', 4),
    'yazi_limit' => (int) $params->get('yazi_limit', 6),
    'cta'        => (int) $params->get('cta', 1),
    'okuma_hizi' => (int) $params->get('okuma_hizi', ModNumistrIlgiliHelper::OKUMA_HIZI),
);

$cacheparams->methodparams = array($makaleId, $dil, (int) $params->get('limit', 8), $secenek);
$cacheparams->modeparams   = array('option' => 'WORD', 'view' => 'WORD', 'id' => 'INT', 'lang' => 'CMD');

try {
    $veri = ModuleHelper::moduleCache($module, $params, $cacheparams);
} catch (\Throwable $e) {
    Log::add('mod_numistr_ilgili: ' . $e->getMessage(), Log::WARNING, 'mod_numistr_ilgili');

    return;
}

if (empty($veri['bloklar'])) {
    return;
}

// Okunabilirlik + blok biçemi yalnız blog yazısında (makale gövdesini de etkilediği için sikke / yerleşim sayfasında yüklenmez).
if (!empty($veri['blog'])) {
    HTMLHelper::_('stylesheet', 'mod_numistr_ilgili/ilgili.css', array('version' => 'auto', 'relative' => true));
}

require ModuleHelper::getLayoutPath('mod_numistr_ilgili', $params->get('layout', 'default'));
