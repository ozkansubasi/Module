<?php
/**
 * @package     NumisTR
 * @subpackage  mod_numistr_gallery
 *
 * Sikke görsel galerisi. Yalnız makale sayfasında (com_content, view=article) çalışır; görsel yoksa hiçbir şey basmaz.
 * Önbellek yok: büyük görselin filigranı oturuma göre değişir (Pro → wm=2); ziyaretçi için sitenin sayfa önbelleği yeter.
 * Hata sayfayı asla düşürmez (günlüğe yazılır).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;

require_once __DIR__ . '/helper.php';

$app = Factory::getApplication();

if ($app->input->getCmd('option') !== 'com_content' || $app->input->getCmd('view') !== 'article') {
    return;
}

$makaleId = $app->input->getInt('id', 0);

if ($makaleId <= 0) {
    return;
}

try {
    $gorseller = ModNumistrGalleryHelper::gorseller($makaleId);

    if (!$gorseller) {
        return;
    }

    $ogeler = ModNumistrGalleryHelper::ogeler($gorseller, ModNumistrGalleryHelper::popupWm($app), rtrim(Uri::root(), '/'));
    ModNumistrGalleryHelper::varliklar();
} catch (\Throwable $e) {
    Log::add('mod_numistr_gallery: ' . $e->getMessage(), Log::WARNING, 'mod_numistr_gallery');

    return;
}

$uid = 'numistr-gallery-' . $makaleId . '-' . substr(md5((string) mt_rand()), 0, 6);

require ModuleHelper::getLayoutPath('mod_numistr_gallery', $params->get('layout', 'default'));
