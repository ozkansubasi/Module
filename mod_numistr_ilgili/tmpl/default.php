<?php
/**
 * @package     NumisTR
 * @subpackage  mod_numistr_ilgili
 *
 * $veri['bloklar']: tur = sikke (görselli kart ızgarası) | liste (yazı bağlantıları) | baglanti (yerleşim sayfası) |
 * hukumdar (hükümdar + sikke sayısı). Görsel yoksa yer tutucu.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$h = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
?>
<div class="numistr-ilgili">
<?php foreach ($veri['bloklar'] as $blok) :
    list($anahtar, $arg) = $blok['baslik'];
    $baslik = $arg !== '' ? Text::sprintf($anahtar, $arg) : Text::_($anahtar);
    ?>
    <section class="numistr-ilgili-blok uk-margin-medium-top">
        <h3 class="uk-h4 uk-margin-small-bottom"><?php echo $h($baslik); ?></h3>

        <?php if ($blok['tur'] === 'sikke') : ?>
            <div class="uk-grid-small uk-child-width-1-2 uk-child-width-1-4@m" uk-grid>
                <?php foreach ($blok['ogeler'] as $o) : ?>
                    <div>
                        <a class="uk-card uk-card-default uk-card-small uk-display-block uk-link-reset" href="<?php echo $h($o['url']); ?>">
                            <div class="uk-card-media-top uk-background-muted uk-flex uk-flex-center uk-flex-middle" style="aspect-ratio:1/1;overflow:hidden">
                                <?php if (!empty($o['gorsel'])) : ?>
                                    <img src="<?php echo $h($o['gorsel']); ?>" alt="<?php echo $h($o['baslik']); ?>" loading="lazy"
                                         style="width:100%;height:100%;object-fit:contain">
                                <?php else : ?>
                                    <span class="uk-text-muted" uk-icon="icon: image; ratio: 2"></span>
                                <?php endif; ?>
                            </div>
                            <div class="uk-card-body uk-padding-small uk-text-small"><?php echo $h($o['baslik']); ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($blok['tur'] === 'hukumdar') : ?>
            <ul class="uk-subnav uk-subnav-divider uk-margin-remove-bottom">
                <?php foreach ($blok['ogeler'] as $o) : ?>
                    <li><span><?php echo $h($o['ad']); ?> <span class="uk-text-muted">(<?php echo (int) $o['n']; ?>)</span></span></li>
                <?php endforeach; ?>
            </ul>

        <?php elseif ($blok['tur'] === 'baglanti') : ?>
            <ul class="uk-list">
                <?php foreach ($blok['ogeler'] as $o) : ?>
                    <li><a class="uk-button uk-button-default uk-button-small" href="<?php echo $h($o['url']); ?>"><?php echo $h($o['baslik']); ?> →</a></li>
                <?php endforeach; ?>
            </ul>

        <?php else : ?>
            <ul class="uk-list uk-list-bullet">
                <?php foreach ($blok['ogeler'] as $o) : ?>
                    <li><a href="<?php echo $h($o['url']); ?>"><?php echo $h($o['baslik']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
</div>
