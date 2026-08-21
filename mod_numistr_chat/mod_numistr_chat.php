<?php
/**
 * @package     NumisTR Chat Module
 * @subpackage  mod_numistr_chat
 * @version     2.0.0
 * @copyright   Copyright (C) 2025-2026 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * v2.0.0: WhatsApp + n8n RAG kutusu kaldirildi; NumisTR AI Asistan (ADR-003 Faz 1)
 *         same-origin /v1/assistant/chat ucuna baglanir. UI tek JS dosyasindadir:
 *         media/mod_numistr_chat/js/numistr-assistant.js (kaynak: Module/numistr-chatbot-widget).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

$apiBase        = trim((string) $params->get('api_base', '/api/index.php/v1/assistant'));
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
    $apiBase = '/api/index.php/v1/assistant';
}

$assistantConfig = [
    'apiBase'        => rtrim($apiBase, '/'),
    'position'       => $widgetPosition,
    'primaryColor'   => $primaryColor,
    'secondaryColor' => $secondaryColor,
    'restoreHistory' => $restoreHistory,
];

require ModuleHelper::getLayoutPath('mod_numistr_chat', $params->get('layout', 'default'));
