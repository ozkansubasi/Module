<?php
/**
 * @package     NumisTR Ticker Module
 * @subpackage  mod_numistr_ticker
 * @copyright   Copyright (C) 2025 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Helper class for NumisTR Ticker Module
 */
class ModNumistrTickerHelper
{
    /**
     * Get ticker items from database
     *
     * @param   \Joomla\Registry\Registry  $params  Module parameters
     *
     * @return  array  Array of ticker items
     */
    public function getTickerItems($params)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        // Get parameters
        $categoryId = (int) $params->get('ticker_category');
        $regionFilter = $params->get('region_filter', 'all');
        $autoDetect = (bool) $params->get('auto_detect_region', true);
        $itemCount = (int) $params->get('item_count', 20);
        $randomize = (bool) $params->get('randomize', true);
        $cacheDuration = (int) $params->get('cache_duration', 3600);

        // Auto-detect region from current page
        // Works when: auto_detect_region=true AND (region_filter='auto' OR region_filter='all')
        if ($autoDetect && ($regionFilter === 'auto' || $regionFilter === 'all')) {
            $detectedRegion = $this->detectCurrentRegion();
            if (!empty($detectedRegion)) {
                $regionFilter = $detectedRegion;
            }
        }

        // Build cache key
        $cacheKey = 'mod_numistr_ticker_' . md5(
            $categoryId . '_' . $regionFilter . '_' . $itemCount . '_' . ($randomize ? 'rand' : 'ordered')
        );

        // Try to get from cache
        if ($cacheDuration > 0) {
            $cache = Factory::getCache('mod_numistr_ticker', 'output');
            $cache->setLifeTime($cacheDuration);

            $cachedData = $cache->get($cacheKey);
            if ($cachedData !== false) {
                return $cachedData;
            }
        }

        // Get current language
        $app = Factory::getApplication();
        $currentLang = $app->getLanguage()->getTag();

        // Build query
        $query->select([
            'a.id',
            'a.title',
            'a.introtext',
            'a.alias',
            'a.catid',
            'a.language',
            'c.title AS category_title',
            'c.alias AS category_alias'
        ])
        ->from($db->quoteName('#__content', 'a'))
        ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON a.catid = c.id')
        ->where('a.state = 1') // Published only
        ->where('a.catid = ' . $categoryId)
        ->where('(' . $db->quoteName('a.language') . ' = ' . $db->quote($currentLang) .
                ' OR ' . $db->quoteName('a.language') . ' = ' . $db->quote('*') . ')');

        // Filter by region if specified
        if ($regionFilter !== 'all' && $regionFilter !== 'auto' && $regionFilter !== '') {
            // Check if item has region_code or region-code custom field
            // Joomla may store field names with hyphens or underscores
            $query->leftJoin(
                $db->quoteName('#__fields_values', 'fv') . ' ON fv.item_id = a.id'
            )
            ->leftJoin(
                $db->quoteName('#__fields', 'f') . ' ON f.id = fv.field_id'
            )
            ->where('(f.name = ' . $db->quote('region_code') . ' OR f.name = ' . $db->quote('region-code') . ')')
            ->where('fv.value = ' . $db->quote($regionFilter));
        }

        // Ordering
        if ($randomize) {
            $query->order('RAND()');
        } else {
            $query->order('a.ordering ASC, a.title ASC');
        }

        // Set limit
        $query->setLimit($itemCount);

        $db->setQuery($query);

        try {
            $items = $db->loadObjectList();

            // Process items
            foreach ($items as &$item) {
                // Clean introtext (remove HTML tags)
                $item->introtext = strip_tags($item->introtext);

                // Get custom fields if available
                $item->custom_fields = $this->getCustomFields($item->id);

                // Extract ancient and modern names
                $this->extractNames($item);
            }

            // Cache the result
            if ($cacheDuration > 0) {
                $cache->store($items, $cacheKey);
            }

            return $items;

        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('MOD_NUMISTR_TICKER_ERROR', $e->getMessage()),
                'error'
            );
            return [];
        }
    }

    /**
     * Get custom fields for an article
     *
     * @param   int  $articleId  Article ID
     *
     * @return  array  Custom fields
     */
    protected function getCustomFields($articleId)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select([
            'f.name',
            'f.title',
            'fv.value'
        ])
        ->from($db->quoteName('#__fields_values', 'fv'))
        ->join('INNER', $db->quoteName('#__fields', 'f') . ' ON f.id = fv.field_id')
        ->where('fv.item_id = ' . (int) $articleId);

        $db->setQuery($query);

        try {
            $fields = $db->loadObjectList('name');
            return $fields ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Extract fact title and description from item
     * Supports both old format (ancient_name/modern_name) and new format (fact_title/fact_description)
     *
     * @param   object  &$item  Item object (passed by reference)
     *
     * @return  void
     */
    protected function extractNames(&$item)
    {
        // NEW FORMAT: fact_title and fact_description (check both hyphenated and underscored)
        if (isset($item->custom_fields['fact-title'])) {
            $item->fact_title = $item->custom_fields['fact-title']->value;
        }
        elseif (isset($item->custom_fields['fact_title'])) {
            $item->fact_title = $item->custom_fields['fact_title']->value;
        }
        // OLD FORMAT FALLBACK: ancient_name
        elseif (isset($item->custom_fields['ancient_name'])) {
            $item->fact_title = $item->custom_fields['ancient_name']->value;
        }
        // FALLBACK: use article title
        else {
            $item->fact_title = $item->title;
        }

        // NEW FORMAT: fact_description (check both hyphenated and underscored)
        if (isset($item->custom_fields['fact-description'])) {
            $item->fact_description = $item->custom_fields['fact-description']->value;
        }
        elseif (isset($item->custom_fields['fact_description'])) {
            $item->fact_description = $item->custom_fields['fact_description']->value;
        }
        // OLD FORMAT FALLBACK: modern_name
        elseif (isset($item->custom_fields['modern_name'])) {
            $item->fact_description = $item->custom_fields['modern_name']->value;
        }
        // FALLBACK: use introtext
        else {
            $item->fact_description = $item->introtext;
        }

        // Get region code if available (for filtering, not display)
        if (isset($item->custom_fields['region_code'])) {
            $item->region_code = $item->custom_fields['region_code']->value;
        } else {
            $item->region_code = null;
        }

        // BACKWARD COMPATIBILITY: Set old field names too
        $item->ancient_name = $item->fact_title;
        $item->modern_name = $item->fact_description;
    }

    /**
     * Detect current region from page context
     * Returns category alias which matches region_code custom field values
     * (e.g., 'lydia-coins', 'cappadocia-coins', 'pisidia-coins')
     *
     * @return  string  Region code (category alias) for filtering or empty string
     */
    public function detectCurrentRegion()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // Try to get from category context
        $option = $input->get('option', '', 'CMD');
        $view = $input->get('view', '', 'CMD');

        if ($option === 'com_content' && ($view === 'category' || $view === 'article')) {
            // For category view, get from 'id' parameter
            // For article view, get from article's category
            $catid = 0;

            if ($view === 'category') {
                $catid = $input->get('id', 0, 'INT');
            } else {
                // Article view - get article's category
                $articleId = $input->get('id', 0, 'INT');
                if ($articleId > 0) {
                    $db = Factory::getDbo();
                    $query = $db->getQuery(true)
                        ->select('catid')
                        ->from('#__content')
                        ->where('id = ' . $articleId);
                    $db->setQuery($query);
                    $catid = (int) $db->loadResult();
                }
            }

            // Get category alias - this directly matches region_code custom field values
            if ($catid > 0) {
                $db = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select('alias')
                    ->from('#__categories')
                    ->where('id = ' . $catid);
                $db->setQuery($query);
                $alias = $db->loadResult();

                if ($alias) {
                    // Category alias is already in the correct format (e.g., 'cappadocia-coins')
                    // This matches the region_code custom field values
                    return $alias;
                }
            }
        }

        return '';
    }

    /**
     * Get display-friendly region name from category
     * Used for displaying region name in the ticker header
     *
     * @return  string  Region display name or empty string
     */
    public function detectCurrentRegionTitle()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        $option = $input->get('option', '', 'CMD');
        $view = $input->get('view', '', 'CMD');

        if ($option === 'com_content' && ($view === 'category' || $view === 'article')) {
            $catid = 0;

            if ($view === 'category') {
                $catid = $input->get('id', 0, 'INT');
            } else {
                $articleId = $input->get('id', 0, 'INT');
                if ($articleId > 0) {
                    $db = Factory::getDbo();
                    $query = $db->getQuery(true)
                        ->select('catid')
                        ->from('#__content')
                        ->where('id = ' . $articleId);
                    $db->setQuery($query);
                    $catid = (int) $db->loadResult();
                }
            }

            if ($catid > 0) {
                $db = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select('title')
                    ->from('#__categories')
                    ->where('id = ' . $catid);
                $db->setQuery($query);
                return $db->loadResult() ?: '';
            }
        }

        return '';
    }

    /**
     * Load module assets (CSS & JS)
     *
     * @param   \Joomla\Registry\Registry  $params  Module parameters
     *
     * @return  void
     */
    public function loadAssets($params)
    {
        $doc = Factory::getDocument();
        $wa = $doc->getWebAssetManager();

        // Module path
        $modulePath = 'modules/mod_numistr_ticker';

        // Register and use CSS
        $wa->registerAndUseStyle(
            'mod_numistr_ticker',
            $modulePath . '/assets/css/ticker.css',
            [],
            ['version' => '1.0.0']
        );

        // Register and use JS
        $wa->registerAndUseScript(
            'mod_numistr_ticker',
            $modulePath . '/assets/js/ticker.js',
            [],
            ['version' => '1.0.0', 'defer' => true],
            []
        );

        // Pass parameters to JavaScript
        $animationSpeed = (int) $params->get('animation_speed', 60);

        $doc->addScriptOptions('mod_numistr_ticker', [
            'animationSpeed' => $animationSpeed,
        ]);
    }

    /**
     * Get API-friendly format for ticker items
     * Used by REST API endpoint
     *
     * @param   array   $items         Ticker items
     * @param   string  $regionFilter  Region filter
     *
     * @return  array  API response format
     */
    public static function formatForApi($items, $regionFilter = 'all')
    {
        $formatted = [];

        foreach ($items as $item) {
            $formatted[] = [
                'id' => (int) $item->id,
                'ancient_name' => $item->ancient_name,
                'modern_name' => $item->modern_name,
                'region_code' => $item->region_code,
                'full_text' => $item->ancient_name . ' → ' . $item->modern_name,
            ];
        }

        return [
            'items' => $formatted,
            'count' => count($formatted),
            'region_filter' => $regionFilter,
        ];
    }

    /**
     * Get region name from page context (public method)
     * Now receives category title directly from detectCurrentRegion()
     *
     * @param   string  $categoryTitle  Category title from page context (e.g., 'Pisidya Sikkeleri', 'Lydia Sikkeleri')
     *
     * @return  string  Region name (cleaned, e.g., 'Pisidya', 'Lydia')
     */
    public function getRegionNameFromCode($categoryTitle)
    {
        if (empty($categoryTitle)) {
            return '';
        }

        // Remove common suffixes from category titles
        // Turkish: "Sikkeleri", "Sikke", "Coins"
        // English: "Coins", "Coinage"
        $categoryTitle = preg_replace('/\s+(Sikkeleri|Sikke|Coins|Coinage)\s*$/ui', '', $categoryTitle);

        return trim($categoryTitle);
    }

    /**
     * Get region name from region code (internal mapping)
     *
     * @param   string|null  $regionCode  Region code from custom field
     *
     * @return  string  Region name or empty string
     */
    protected function getRegionName($regionCode)
    {
        if (empty($regionCode)) {
            return '';
        }

        // Region code to name mapping (Turkish/English based on current language)
        $app = Factory::getApplication();
        $currentLang = $app->getLanguage()->getTag();
        $isTurkish = ($currentLang === 'tr-TR');

        $regionMap = [
            'AIOLIS' => $isTurkish ? 'Aiolis' : 'Aeolis',
            'KARIA' => $isTurkish ? 'Karia' : 'Caria',
            'IONIA' => $isTurkish ? 'İonia' : 'Ionia',
            'LYDIA' => $isTurkish ? 'Lydia' : 'Lydia',
            'PHRYGIA' => $isTurkish ? 'Phrygia' : 'Phrygia',
            'MYSIA' => $isTurkish ? 'Mysia' : 'Mysia',
            'TROAS' => $isTurkish ? 'Troas' : 'Troas',
            'BITHYNIA' => $isTurkish ? 'Bithynia' : 'Bithynia',
            'PAPHLAGONIA' => $isTurkish ? 'Paphlagonia' : 'Paphlagonia',
            'PONTUS' => $isTurkish ? 'Pontus' : 'Pontus',
            'GALATIA' => $isTurkish ? 'Galatia' : 'Galatia',
            'CAPPADOCIA' => $isTurkish ? 'Kappadokia' : 'Cappadocia',
            'LYCAONIA' => $isTurkish ? 'Lykaonia' : 'Lycaonia',
            'CILICIA' => $isTurkish ? 'Kilikya' : 'Cilicia',
            'PAMPHYLIA' => $isTurkish ? 'Pamphylia' : 'Pamphylia',
            'PISIDIA' => $isTurkish ? 'Pisidia' : 'Pisidia',
            'LYCIA' => $isTurkish ? 'Likya' : 'Lycia',
        ];

        // Return mapped name or original code
        return $regionMap[strtoupper($regionCode)] ?? $regionCode;
    }
}
