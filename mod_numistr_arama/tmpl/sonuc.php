<?php
/**
 * @package     NumisTR Search Module
 * @subpackage  mod_numistr_arama
 * @version     1.0.3
 * @license     GNU General Public License version 2 or later
 *
 * Düzen "sonuc": sonuç sayfası. Form sunucuda basılır; sonuçları media/mod_numistr_arama/js/arama.js çizer.
 * JS çalışmazsa form yine aramayı yeniden gönderir.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

/** @var string $resultsPath @var array $strings @var string $query @var string $lang @var string $apiBase @var string $sfx @var string $version @var object $module */

$e = static function ($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$rootId = 'nma-sonuc-' . (int) $module->id;
$inputId = 'nma-s-' . (int) $module->id;
$cfg = ModNumistrAramaHelper::configJson([
    'root' => $rootId,
    'api' => $apiBase,
    'lang' => $lang,
    'q' => $query,
    'path' => $resultsPath,
    's' => $strings,
]);
$js = Uri::root(true) . '/media/mod_numistr_arama/js/arama.js?v=' . $version;
?>
<div id="<?php echo $rootId; ?>" class="nma nma-sonuc nma--light<?php echo $sfx; ?>">
<form class="nma-form" action="<?php echo $e($resultsPath); ?>" method="get" role="search">
<label class="nma-sr" for="<?php echo $inputId; ?>"><?php echo $e($strings['label']); ?></label>
<input id="<?php echo $inputId; ?>" class="nma-input" type="search" name="q" value="<?php echo $e($query); ?>"
 placeholder="<?php echo $e($strings['placeholder']); ?>" minlength="2" maxlength="60" autocomplete="off" required>
<button class="nma-btn" type="submit" aria-label="<?php echo $e($strings['button']); ?>">
<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path></svg>
<span class="nma-btn-text"><?php echo $e($strings['button']); ?></span>
</button>
</form>
<div class="nma-tabs" role="group" aria-label="<?php echo $e($strings['label']); ?>"></div>
<div class="nma-sonuclar" aria-live="polite"><?php if ($query === '') : ?><p class="nma-not"><?php echo $e($strings['short']); ?></p><?php endif; ?></div>
</div>
<script>(window.NumisTRAramaConfig = window.NumisTRAramaConfig || []).push(<?php echo $cfg; ?>);</script>
<script src="<?php echo $e($js); ?>" defer></script>
