<?php
/**
 * @package     NumisTR Ticker Module
 * @subpackage  mod_numistr_ticker
 * @copyright   Copyright (C) 2025 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

// Get parameters
$displayFormat = $params->get('display_format', 'single_line');
$fadeInterval = (int) $params->get('fade_interval', 15);
$showIcon = (bool) $params->get('show_icon', false);
$showNavigation = (bool) $params->get('show_navigation', false);
$showDots = (bool) $params->get('show_dots', false);
$pauseOnHover = (bool) $params->get('pause_on_hover', false);

$isSingleLine = ($displayFormat === 'single_line');

?>

<div class="numistr-ticker-fade<?php echo $moduleclass_sfx; ?><?php echo $isSingleLine ? ' single-line' : ' multi-line'; ?>" 
     data-interval="<?php echo $fadeInterval * 1000; ?>"
     data-pause-on-hover="<?php echo $pauseOnHover ? '1' : '0'; ?>">
    
    <?php if ($showIcon) : ?>
        <div class="ticker-icon">
            <span class="icon-info-circle" aria-hidden="true"></span>
        </div>
    <?php endif; ?>

    <div class="ticker-content-fade">
        <?php foreach ($items as $index => $item) : ?>
            <div class="ticker-item-fade<?php echo $index === 0 ? ' active' : ''; ?>" data-index="<?php echo $index; ?>">
                <?php if ($isSingleLine) : ?>
                    <?php if (!empty($regionName)) : ?>
                        <span class="ticker-region"><?php echo htmlspecialchars($regionName, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="ticker-separator">/</span>
                    <?php endif; ?>
                    <span class="ticker-title"><?php echo htmlspecialchars($item->fact_title, ENT_QUOTES, 'UTF-8'); ?></span>:
                    <span class="ticker-description"><?php echo htmlspecialchars($item->fact_description, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else : ?>
                    <?php if (!empty($regionName)) : ?>
                        <div class="ticker-region"><?php echo htmlspecialchars($regionName, ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars($item->fact_title, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php else : ?>
                        <div class="ticker-title"><?php echo htmlspecialchars($item->fact_title, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <div class="ticker-description"><?php echo htmlspecialchars($item->fact_description, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($showNavigation && count($items) > 1) : ?>
        <div class="ticker-navigation">
            <button class="ticker-prev" aria-label="<?php echo Text::_('MOD_NUMISTR_TICKER_PREVIOUS'); ?>">
                <span class="icon-chevron-left" aria-hidden="true"></span>
            </button>
            
            <?php if ($showDots) : ?>
                <div class="ticker-dots">
                    <?php foreach ($items as $index => $item) : ?>
                        <button class="ticker-dot<?php echo $index === 0 ? ' active' : ''; ?>"
                                data-index="<?php echo $index; ?>"
                                aria-label="<?php echo Text::sprintf('MOD_NUMISTR_TICKER_GO_TO', $index + 1); ?>">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <button class="ticker-next" aria-label="<?php echo Text::_('MOD_NUMISTR_TICKER_NEXT'); ?>">
                <span class="icon-chevron-right" aria-hidden="true"></span>
            </button>
        </div>
    <?php endif; ?>
</div>
