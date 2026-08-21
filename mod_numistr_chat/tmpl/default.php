<?php
/**
 * @package     NumisTR Chat Module
 * @subpackage  mod_numistr_chat
 * @version     2.0.1
 * @copyright   Copyright (C) 2025-2026 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/** @var array $assistantConfig */

$wa      = Factory::getApplication()->getDocument()->getWebAssetManager();
$version = '2.0.0';

// config first (inline), then the widget (deferred)
// inline config runs at parse time; the widget script is deferred, so it always sees it
$wa->addInlineScript(
    'window.NumisTRAssistantConfig = ' . json_encode($assistantConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';',
    ['name' => 'mod_numistr_chat.config']
);

$wa->registerAndUseScript(
    'mod_numistr_chat.widget',
    Uri::root(true) . '/media/mod_numistr_chat/js/numistr-assistant.js?v=' . $version,
    [],
    ['defer' => true]
);
