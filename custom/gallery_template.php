<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

/**
 * Beklenen değişkenler: $images, $uid, $coin_id
 * Davranış:
 *  - Thumb (img src): remote_url http/https ise DOĞRUDAN remote; değilse local endpoint (wm=0)
 *  - Popup (href): DAİMA local endpoint (wm=1) — hotlink riskine takılmamak için
 *  - image_type='detay' olan kart tam satır kaplar
 */

function is_abs($u){ return is_string($u) && preg_match('~^https?://~i',$u); }

?>
<div id="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>" class="coin-gallery-wrapper" oncontextmenu="return false;">
  <div class="coin-gallery">
    <?php foreach ($images as $idx => $image): ?>
      <?php
        $imageId  = (int)($image->image_id ?? 0);
        $imgType  = (string)($image->image_type ?? '');
        $weight   = isset($image->weight)   ? trim((string)$image->weight)   : '';
        $diameter = isset($image->diameter) ? trim((string)$image->diameter) : '';
        $remote   = isset($image->remote_url) ? trim((string)$image->remote_url) : '';

        // Popup: local endpoint - DÜZELTİLDİ: &format=raw kaldırıldı
        $popupUrl = Route::_('index.php?option=com_numistr&view=gorsel&id='.$imageId.'&wm=1', false);

        // Thumb: remote varsa doğrudan remote; yoksa local endpoint - DÜZELTİLDİ: &format=raw kaldırıldı
        if ($remote !== '' && is_abs($remote)) {
            $thumbUrl = $remote;
        } else {
            $thumbUrl = Route::_('index.php?option=com_numistr&view=gorsel&id='.$imageId.'&wm=0', false);
        }

        $itemClass = 'gallery-item' . (strcasecmp($imgType,'detay')===0 ? ' is-detay' : '');
      ?>
      <div class="<?php echo $itemClass; ?>">
        <a href="<?php echo htmlspecialchars($popupUrl, ENT_QUOTES, 'UTF-8'); ?>"
           class="numistr-lb-<?php echo (int)$coin_id; ?>"
           data-gallery="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>"
           rel="noopener nofollow noreferrer">
          <img src="<?php echo htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8'); ?>"
               alt="Sikke görseli"
               loading="lazy"
               decoding="async"
               draggable="false"
               referrerpolicy="no-referrer"
               oncontextmenu="return false;">
        </a>
        <div class="gallery-caption">
          <?php if (($idx % 2) === 0): ?>
            <?php if ($weight !== ''): ?>
              <p class="metric"><strong>Ağırlık:</strong> <?php echo htmlspecialchars($weight, ENT_QUOTES, 'UTF-8'); ?> g</p>
            <?php endif; ?>
            <?php if ($diameter !== ''): ?>
              <p class="metric"><strong>Çap:</strong> <?php echo htmlspecialchars($diameter, ENT_QUOTES, 'UTF-8'); ?> mm</p>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>