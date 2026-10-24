<?php
/**
 * @package     NumisTR
 * @subpackage  mod_numistr_gallery
 *
 * $ogeler: ModNumistrGalleryHelper::ogeler() · $uid: örnek kimliği (GLightbox seçicisi assets/js/gallery.js'te).
 * İşaretleme eski betikle (modules/custom/numistr_gallery.php) aynıdır; sınıf adlarını değiştirme — CSS ve JS onlara bağlı.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$h = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

// Küçük görsel yüklenemezse bir kez wm=0 ile yeniden dener.
$yedek = "(function(i){try{if(i.dataset.fallback!=='1'){var u=new URL(i.src,window.location.href);"
    . "u.searchParams.set('wm','0');i.dataset.fallback='1';i.src=u.toString();}}catch(e){}})(this);";
?>
<div id="<?php echo $h($uid); ?>" class="coin-gallery-wrapper" oncontextmenu="return false;">
    <div class="coin-gallery">
        <?php foreach ($ogeler as $o) : ?>
            <div class="<?php echo $h($o['sinif']); ?>">
                <a href="<?php echo $h($o['buyuk']); ?>" class="numistr-lb" data-gallery="<?php echo $h($uid); ?>" data-type="image" rel="noopener nofollow">
                    <img src="<?php echo $h($o['kucuk']); ?>" alt="<?php echo $h(Text::_('MOD_NUMISTR_GALLERY_GORSEL_ALT')); ?>" loading="lazy" onerror="<?php echo $h($yedek); ?>">
                </a>
                <div class="gallery-caption"<?php echo $o['yazi'] ? '' : ' style="display:none"'; ?>>
                    <?php if ($o['agirlik'] !== '') : ?>
                        <p class="metric"><strong><?php echo $h(Text::_('MOD_NUMISTR_GALLERY_AGIRLIK')); ?></strong> <?php echo $h($o['agirlik']); ?> g</p>
                    <?php endif; ?>
                    <?php if ($o['cap'] !== '') : ?>
                        <p class="metric"><strong><?php echo $h(Text::_('MOD_NUMISTR_GALLERY_CAP')); ?></strong> <?php echo $h($o['cap']); ?> mm</p>
                    <?php endif; ?>
                    <?php if ($o['kaynak'] !== '' || $o['lisans'] !== '') : ?>
                        <p class="img-credit"><?php echo $h($o['kaynak']); ?><?php if ($o['lisans'] !== '') : ?><?php echo $o['kaynak'] !== '' ? ' · ' : ''; ?><span class="img-lic"><?php echo $h($o['lisans']); ?></span><?php endif; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
