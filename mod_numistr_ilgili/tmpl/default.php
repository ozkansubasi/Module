<?php
/**
 * @package     NumisTR
 * @subpackage  mod_numistr_ilgili
 *
 * $veri['bloklar']: tur =
 *   sikke    (görselli sikke kartı ızgarası) | liste (yazı bağlantıları) | baglanti (yerleşim sayfası) |
 *   hukumdar (hükümdar + sikke sayısı) |
 *   kart     (aynı serinin yazıları: giriş görselli kart; görsel yoksa seri adıyla yer tutucu) |
 *   cta      (uygulama kartı: fotoğrafla tanıma) |
 *   ust      (bolum=ust: seri rozeti, tarih, okuma süresi, yer / hükümdar çipleri; başlığın üstünde).
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$h = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$tarihYaz = function ($t) {
    return $t ? HTMLHelper::_('date', $t, Text::_('DATE_FORMAT_LC3')) : '';
};

if (($veri['bloklar'][0]['tur'] ?? '') === 'ust') :
    $u = $veri['bloklar'][0];
    ?>
<div class="nmi-ust">
    <?php if (!empty($u['seri']['baslik'])) : ?>
        <a class="nmi-ust-seri" href="<?php echo $h($u['seri']['url']); ?>"><?php echo $h($u['seri']['baslik']); ?></a>
    <?php endif; ?>
    <p class="nmi-ust-meta">
        <?php if ($u['tarih']) : ?><time datetime="<?php echo $h(substr((string) $u['tarih'], 0, 10)); ?>"><?php echo $h($tarihYaz($u['tarih'])); ?></time><?php endif; ?>
        <span aria-hidden="true">·</span>
        <span><?php echo $h(Text::sprintf('MOD_NUMISTR_ILGILI_OKUMA_SURESI', (int) $u['dakika'])); ?></span>
    </p>
    <?php if (!empty($u['cipler'])) : ?>
        <ul class="nmi-ust-cipler" aria-label="<?php echo $h(Text::_('MOD_NUMISTR_ILGILI_ETIKETLER')); ?>">
            <?php foreach ($u['cipler'] as $c) : ?>
                <li><a class="nmi-cip nmi-cip-<?php echo $h($c['tur']); ?>" href="<?php echo $h($c['url']); ?>"><?php echo $h($c['ad']); ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php
    return;
endif;
?>
<div class="numistr-ilgili">
<?php foreach ($veri['bloklar'] as $blok) :
    list($anahtar, $arg) = $blok['baslik'];
    $baslik = $arg !== '' ? Text::sprintf($anahtar, $arg) : Text::_($anahtar);
    ?>
    <section class="numistr-ilgili-blok numistr-ilgili-<?php echo $h($blok['tur']); ?> uk-margin-medium-top">
        <?php if ($blok['tur'] !== 'cta') : ?>
            <h3 class="uk-h4 uk-margin-small-bottom"><?php echo $h($baslik); ?></h3>
        <?php endif; ?>

        <?php if ($blok['tur'] === 'kart') : ?>
            <div class="uk-grid-small uk-child-width-1-2 uk-child-width-1-4@m" uk-grid>
                <?php foreach ($blok['ogeler'] as $o) : ?>
                    <div>
                        <a class="nmi-kart uk-card uk-card-default uk-card-small uk-display-block uk-link-reset" href="<?php echo $h($o['url']); ?>">
                            <div class="nmi-kart-gorsel uk-background-muted">
                                <?php if (!empty($o['gorsel'])) : ?>
                                    <img src="<?php echo $h($o['gorsel']); ?>" alt="" loading="lazy">
                                <?php else : ?>
                                    <span class="nmi-kart-yer"><?php echo $h($arg); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="uk-card-body uk-padding-small">
                                <span class="nmi-kart-baslik"><?php echo $h($o['baslik']); ?></span>
                                <?php if (!empty($o['tarih'])) : ?>
                                    <span class="nmi-kart-tarih uk-text-meta"><?php echo $h($tarihYaz($o['tarih'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($blok['tur'] === 'cta') : ?>
            <div class="nmi-cta">
                <div class="nmi-cta-metin">
                    <h3 class="uk-h4 uk-margin-remove"><?php echo $h($baslik); ?></h3>
                    <p class="uk-margin-small-top uk-margin-remove-bottom"><?php echo $h(Text::_('MOD_NUMISTR_ILGILI_UYGULAMA_METIN')); ?></p>
                </div>
                <a class="nmi-cta-dugme" href="<?php echo $h($blok['ogeler'][0]['url']); ?>"><?php echo $h(Text::_('MOD_NUMISTR_ILGILI_UYGULAMA_DUGME')); ?></a>
            </div>

        <?php elseif ($blok['tur'] === 'sikke') : ?>
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
