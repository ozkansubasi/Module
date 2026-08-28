<?php
/**
 * @package     NumisTR Chat Module
 * @subpackage  mod_numistr_chat
 * @version     2.1.1
 * @copyright   Copyright (C) 2025-2026 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * v2.0.0: WhatsApp + n8n RAG kutusu kaldirildi; NumisTR AI Asistan (ADR-003 Faz 1)
 *         same-origin /v1/assistant/chat ucuna baglanir. UI tek JS dosyasindadir:
 *         media/mod_numistr_chat/js/numistr-assistant.js (kaynak: Module/numistr-chatbot-widget).
 * v2.1.0: ADR-003 Faz 2b/7 + 2b/9 — varsayilan uc SITE uygulamasindaki com_ajax koprusu
 *         (group=webservices). Joomla API uygulamasi site oturumunu kimlik saymadigi
 *         icin giris yapmis kullanici asistanda anonim gorunuyordu; kopru bunu cozer.
 *         Yeni: kimlik rozeti (Uye/PRO), giris + ucretsiz uyelik CTA'lari,
 *         gecmis paneli (konusma listesi / acma / arsivleme) ve Pro'da gunluk
 *         mesaj sayacinin gizlenmesi.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Session\Session;

$apiBase        = trim((string) $params->get('api_base', '/index.php?option=com_ajax&group=webservices&plugin=numistr&format=json'));
$widgetPosition = (string) $params->get('widget_position', 'bottom-right');
$primaryColor   = (string) $params->get('primary_color', '#8B4513');
$secondaryColor = (string) $params->get('secondary_color', '#D4AF37');
$restoreHistory = (int) $params->get('restore_history', 1) === 1;

// defensive normalisation (values end up inside a <script> block)
$hex = static function (string $v, string $fallback): string {
    return preg_match('/^#[0-9a-fA-F]{3,8}$/', $v) ? $v : $fallback;
};
$primaryColor   = $hex($primaryColor, '#8B4513');
$secondaryColor = $hex($secondaryColor, '#D4AF37');
$widgetPosition = $widgetPosition === 'bottom-left' ? 'bottom-left' : 'bottom-right';

if ($apiBase === '' || !preg_match('#^(/|https://)#', $apiBase)) {
    $apiBase = '/index.php?option=com_ajax&group=webservices&plugin=numistr&format=json';
}

// 2.1.0 gecisi: 2.0.x kurulumlarinda bu alanda ESKI VARSAYILAN duruyor ve Joomla
// modul guncellemesi kayitli ayari korudugu icin kendiliginden degismiyor. Eski
// varsayilan birebir duruyorsa kopruye yukselt — aksi halde istek Joomla API
// uygulamasina gider, orada site oturumu gorunmez ve GIRIS YAPMIS KULLANICI
// ASISTANDA ANONIM SAYILIR (Faz 2b'nin tum amaci bu). Elle girilmis FARKLI bir
// deger (ozel alan adi, farkli yol) oldugu gibi birakilir.
if ($apiBase === '/api/index.php/v1/assistant') {
    $apiBase = '/index.php?option=com_ajax&group=webservices&plugin=numistr&format=json';
}

// Uc bicimi: com_ajax koprusu query-string, eski REST ucu yol tabanlidir.
// (Yukaridaki gecis disinda) elle girilmis her deger oldugu gibi kullanilir.
$apiMode = strpos($apiBase, 'option=com_ajax') !== false ? 'bridge' : 'rest';

$assistantConfig = [
    // koprude sondaki '/' anlamli degil; yalnizca REST ucunda kirpilir
    'apiBase'        => $apiMode === 'rest' ? rtrim($apiBase, '/') : $apiBase,
    'apiMode'        => $apiMode,
    // Tanima ucu (Faz 2b/8) kimlik gerektirir ve tarama kotasi tuketir; cross-site
    // form POST'una karsi Joomla oturum jetonu istenir. Sayfa onbellekten gelirse
    // jeton bayat olabilir -> uc 403 doner, widget "sayfayi yenileyin" der.
    'csrfToken'      => Session::getFormToken(),
    'recognizeEnabled' => (int) $params->get('recognize_enabled', 1) === 1,
    'position'       => $widgetPosition,
    'primaryColor'   => $primaryColor,
    'secondaryColor' => $secondaryColor,
    'restoreHistory' => $restoreHistory,
];

require ModuleHelper::getLayoutPath('mod_numistr_chat', $params->get('layout', 'default'));
