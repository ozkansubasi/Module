<?php
/**
 * @package     NumisTR Ticker Module
 * @subpackage  mod_numistr_ticker
 * @copyright   Copyright (C) 2025 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;

// Include the helper
require_once __DIR__ . '/helper.php';

// Get module parameters - ensure it's a Registry object
$params = $module->params;
if (is_string($params)) {
    $params = new Registry($params);
}

// Get ticker items
$helper = new ModNumistrTickerHelper();
$items = $helper->getTickerItems($params);

// Check if we have items
if (empty($items)) {
    return;
}

// Load module assets (CSS & JS)
$helper->loadAssets($params);

// Get current region code (for filtering) - returns alias like 'lydia-coins'
$currentRegion = $helper->detectCurrentRegion();

// Get region title for display (returns title like 'Lidya Sikkeleri')
$currentRegionTitle = $helper->detectCurrentRegionTitle();

// Get region name for display (convert title to readable name like 'Lidya')
$regionName = $helper->getRegionNameFromCode($currentRegionTitle);

// Module class suffix
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8');

// Display the module
require ModuleHelper::getLayoutPath('mod_numistr_ticker', $params->get('layout', 'default'));
