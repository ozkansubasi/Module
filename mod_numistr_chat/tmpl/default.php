<?php
/**
 * @package     NumisTR Chat Module
 * @subpackage  mod_numistr_chat
 * @version     2.0.1
 * @copyright   Copyright (C) 2025-2026 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

/** @var array $assistantConfig */

$version = '2.0.1';
$src     = Uri::root(true) . '/media/mod_numistr_chat/js/numistr-assistant.js?v=' . $version;
$json    = json_encode($assistantConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$sfx     = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');

// Output real markup: some template/page-builder module renderers drop modules whose body is empty.
?>
<div id="numistr-assistant-root" class="mod-numistr-chat<?php echo $sfx; ?>" data-version="<?php echo $version; ?>"></div>
<script>window.NumisTRAssistantConfig = <?php echo $json; ?>;</script>
<script src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
