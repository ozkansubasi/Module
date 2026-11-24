<?php
/**
 * @package     NumisTR
 * @subpackage  mod_numistr_harita
 *
 * Antik harita modülü: Sourcerer + Custom modül #125 / #130 zincirinin yerine geçer (CVE-2026-74253).
 * Harita kodu tamamen istemci tarafında (Google Maps + /v1/locations); modül yalnız işaretlemeyi basar.
 *   harita = anadolu → Anadolu'nun antik haritası (bölgeler, yerleşimler, darphaneler)
 *   harita = sikke   → sikke detay sayfasında darphane haritası (koordinatı sayfadaki alandan okur)
 * Google Maps anahtarı modül ayarında (tarayıcı anahtarı; kodda ve git'te tutulmaz). Anahtar yoksa hiçbir şey basmaz.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Log\Log;

$harita  = (string) $params->get('harita', 'sikke');
$anahtar = trim((string) $params->get('api_key', ''));

if (!in_array($harita, array('anadolu', 'sikke'), true) || $anahtar === '') {
    Log::add('mod_numistr_harita: harita türü ya da Google Maps anahtarı eksik (modül ' . (int) $module->id . ')', Log::WARNING, 'mod_numistr_harita');

    return;
}

// 1.2.0: sikke düzenindeki "Google Haritalar'da göster" PRO oturumda koordinatı açar (galeri modülüyle aynı kural:
// misafir değil + com_numistr pro_group_id oturumun grupları arasında). Joomla'sız sınamada Factory yoksa false.
$proMu = false;

if ($harita === 'sikke') {
    try {
        $kim     = \Joomla\CMS\Factory::getApplication()->getIdentity();
        $proGrup = (int) \Joomla\CMS\Component\ComponentHelper::getParams('com_numistr')->get('pro_group_id', 10);
        $proMu   = $kim && !$kim->guest && $proGrup > 0
            && in_array($proGrup, array_map('intval', (array) $kim->getAuthorisedGroups()), true);
    } catch (\Throwable $e) {
        $proMu = false;
    }
}

require ModuleHelper::getLayoutPath('mod_numistr_harita', $harita);
