<?php
/**
 * @package     NumisTR Search Module
 * @subpackage  mod_numistr_arama
 * @version     1.0.1
 * @license     GNU General Public License version 2 or later
 *
 * Düzen "default": arama kutusu. JS gerektirmez (düz GET formu → sonuç sayfası).
 */

defined('_JEXEC') or die;

/** @var string $resultsPath @var array $strings @var array $examples @var string $surface @var string $query @var string $sfx @var object $module */

$e = static function ($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$inputId = 'nma-q-' . (int) $module->id;
?>
<div class="nma nma-kutu nma--<?php echo $surface; ?><?php echo $sfx; ?>">
<form class="nma-form" action="<?php echo $e($resultsPath); ?>" method="get" role="search">
<label class="nma-sr" for="<?php echo $inputId; ?>"><?php echo $e($strings['label']); ?></label>
<input id="<?php echo $inputId; ?>" class="nma-input" type="search" name="q" value="<?php echo $e($query); ?>"
 placeholder="<?php echo $e($strings['placeholder']); ?>" minlength="2" maxlength="60" autocomplete="off" required>
<button class="nma-btn" type="submit" aria-label="<?php echo $e($strings['button']); ?>">
<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path></svg>
<span class="nma-btn-text"><?php echo $e($strings['button']); ?></span>
</button>
</form>
<?php if ($examples) : ?>
<div class="nma-ornek">
<span class="nma-ornek-etiket"><?php echo $e($strings['examples']); ?></span>
<?php foreach ($examples as $ex) : ?>
<a class="nma-chip" href="<?php echo $e(ModNumistrAramaHelper::exampleUrl($resultsPath, $ex)); ?>"><?php echo $e($ex); ?></a>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
