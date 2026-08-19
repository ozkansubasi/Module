<?php
/**
 * @package     NumisTR Chat Module
 * @subpackage  mod_numistr_chat
 * @version     1.0.0
 * @copyright   Copyright (C) 2025 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

// Get module parameters
$whatsappNumber = $params->get('whatsapp_number', '+905551234567');
$ragEndpoint = $params->get('rag_endpoint', 'https://n8n.aetelekom.com/webhook/chat');
$widgetPosition = $params->get('widget_position', 'bottom-right');
$primaryColor = $params->get('primary_color', '#25d366');
$language = $params->get('language', 'tr');

// Include the module layout
require ModuleHelper::getLayoutPath('mod_numistr_chat', $params->get('layout', 'default'));
