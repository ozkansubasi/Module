<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

if (!function_exists('numistr_render_gallery')) {
function numistr_render_gallery() {
    $app     = Factory::getApplication();
    $coin_id = $app->input->getInt('id', 0);
    if ($coin_id <= 0) return;

    try {
        // --- DEBUG LOG (isteğe bağlı) ---
        $logFile = JPATH_SITE . '/logs/numistr_gallery_debug.log';
        $log = function(string $msg) use ($logFile) {
            @error_log(date('c') . ' ' . $msg . PHP_EOL, 3, $logFile);
        };
        $log("BEGIN coin_id={$coin_id}");

        $db = Factory::getDbo();

        // Görselleri çek
        $q  = $db->getQuery(true)
            ->select($db->quoteName(['image_id','image_type','weight','diameter','ordering','filename','remote_url']))
            ->from($db->quoteName('coins_images'))
            ->where($db->quoteName('coin_id') . ' = ' . (int)$coin_id)
            ->order($db->quoteName('ordering') . ' ASC');
        $db->setQuery($q);
        $images = (array)$db->loadObjectList();

        if (!$images) { $log('END (no images)'); return; }

        // Custom field: sikke-gorsel-url → http/https ve com_numistr içermiyorsa kapakta dış URL
        $coverOverride = null;
        try {
            $q2 = $db->getQuery(true)
                ->select($db->quoteName('v.value'))
                ->from($db->quoteName('#__fields', 'f'))
                ->join('INNER', $db->quoteName('#__fields_values', 'v') . ' ON ' . $db->quoteName('v.field_id') . ' = ' . $db->quoteName('f.id'))
                ->where($db->quoteName('f.name') . ' = ' . $db->quote('sikke-gorsel-url'))
                ->where($db->quoteName('v.item_id') . ' = ' . (int)$coin_id)
                ->setLimit(1);
            $db->setQuery($q2);
            $cv = trim((string)$db->loadResult());
            if ($cv !== '' && preg_match('~^https?://~i', $cv) && stripos($cv, 'option=com_numistr') === false) {
                $coverOverride = $cv;
            }
            if ($coverOverride) { $log('cover_override=' . $coverOverride); }
        } catch (\Throwable $e) {
            $log('cover_override_err=' . $e->getMessage());
        }

        // ---------- GLightbox CSS & JS (tek sefer yükle) ----------
        echo '<link rel="preload" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" as="style">';
        echo '<script>
(function(){
  if(!document.getElementById("glb-css")){
    var l=document.createElement("link");
    l.id="glb-css"; l.rel="stylesheet";
    l.href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css";
    document.head.appendChild(l);
  }
  if(!document.getElementById("glb-js")){
    var s=document.createElement("script");
    s.id="glb-js"; s.src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"; s.defer=true;
    document.head.appendChild(s);
  }
})();
</script>';

        // ---------- Galeri HTML ----------
        $uid = 'numistr-gallery-' . (int)$coin_id . '-' . substr(md5((string)mt_rand()),0,6);
        echo '<div id="'.$uid.'" class="coin-gallery-wrapper" oncontextmenu="return false;"><div class="coin-gallery">';

        /**
         * Satır mantığı:
         * - Normal görseller: 2 sütun → satırın 1. görselinde caption görünür, 2.'de gizlenir.
         * - "Çiftli/Birleşik" görsel: image_type === "detay" (case-insensitive) → tek sütunluk satır ve caption görünür.
         */
        $col = 0; // aktif satır sütun sayacı (0 → ilk, 1 → ikinci)

        // Base URL - Joomla language filter ve SEF'i tamamen bypass etmek için
        $baseUrl = rtrim(\Joomla\CMS\Uri\Uri::root(), '/');

        foreach ($images as $idx => $image) {
            // ABSOLUTE URL with format=raw - /tr/ prefix'ini atla
            // HER GÖRSEL KENDİ image_id'sini kullanır
            $thumbUrl = $baseUrl . '/index.php?option=com_numistr&view=gorsel&format=raw&id='.(int)$image->image_id.'&wm=0';

            // NOT: coverOverride (sikke-gorsel-url) SADECE intro image içindir
            // Gallery'de her görsel kendi coins_images kaydını kullanır

            // ABSOLUTE URL with format=raw - Joomla language filter ve SEF'i tamamen bypass et
            $popupUrl = $baseUrl . '/index.php?option=com_numistr&view=gorsel&format=raw&id='.(int)$image->image_id.'&wm=1';

            // --- ÇİFTLİ GÖRSEL TESPİTİ: image_type = "detay" ise tek satır ---
            $t = strtolower(trim((string)($image->image_type ?? '')));
            $isPair = ($t === 'detay'); // <-- sizin istediğiniz kriter

            // Caption görünürlüğü
            $showCaption = $isPair ? true : ($col === 0);

            // item sınıfı
            $itemClass = 'gallery-item' . ($isPair ? ' pair' : '');

            echo '<div class="'.$itemClass.'">';

            // thumb + lightbox
            echo '  <a href="'.$popupUrl.'" class="numistr-lb" data-gallery="'.$uid.'" data-type="image" rel="noopener nofollow">';
            echo '    <img src="'.$thumbUrl.'" alt="Sikke görseli" loading="lazy" onerror="(function(i){try{if(i.dataset.fallback!==\'1\'){var u=new URL(i.src,window.location.href);u.searchParams.set(\'wm\',\'0\');i.dataset.fallback=\'1\';i.src=u.toString();}}catch(e){}})(this);">';
            echo '  </a>';

            // Caption (ağırlık/çap)
            echo '  <div class="gallery-caption"'.($showCaption ? '' : ' style="display:none"').'>';
            $w = isset($image->weight)   ? trim((string)$image->weight)   : '';
            $d = isset($image->diameter) ? trim((string)$image->diameter) : '';
            if ($w !== '') echo '<p class="metric"><strong>Ağırlık:</strong> ' . htmlspecialchars($w, ENT_QUOTES, 'UTF-8') . ' g</p>';
            if ($d !== '') echo '<p class="metric"><strong>Çap:</strong> '      . htmlspecialchars($d, ENT_QUOTES, 'UTF-8') . ' mm</p>';
            echo '  </div>';

            echo '</div>';

            // sütun sayacını güncelle
            if ($isPair) {
                // tek satır kapladı → yeni satıra geç
                $col = 0;
            } else {
                // iki sütunlu düzende ilerle
                $col = ($col === 0) ? 1 : 0;
            }
        }

        echo '</div></div>';

        // ---------- GLightbox init + sertleştirme ----------
        echo '<script>
(function(){
  var gid="'.$uid.'";
  
  function preventAll(e){
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    return false;
  }
  
  function harden(){
    var c=document.querySelector(".glightbox-container");
    if(!c) return;
    
    // Container seviyesinde tüm korumalar
    c.oncontextmenu=preventAll;
    c.addEventListener("contextmenu",preventAll,{capture:true,passive:false});
    c.addEventListener("selectstart",preventAll,{capture:true,passive:false});
    c.addEventListener("dragstart",preventAll,{capture:true,passive:false});
    
    // Overlay koruması
    var overlay=c.querySelector(".goverlay");
    if(overlay){
      overlay.oncontextmenu=preventAll;
      overlay.addEventListener("contextmenu",preventAll,{capture:true,passive:false});
    }
    
    // Görsel slide koruması
    var slides=c.querySelectorAll(".gslide");
    slides.forEach(function(slide){
      slide.oncontextmenu=preventAll;
      slide.addEventListener("contextmenu",preventAll,{capture:true,passive:false});
      slide.addEventListener("selectstart",preventAll,{capture:true,passive:false});
      slide.addEventListener("dragstart",preventAll,{capture:true,passive:false});
    });
    
    // Görsel koruması
    var imgs=c.querySelectorAll("img");
    imgs.forEach(function(img){
      img.setAttribute("draggable","false");
      img.oncontextmenu=preventAll;
      img.ondragstart=preventAll;
      img.onselectstart=preventAll;
      img.addEventListener("contextmenu",preventAll,{capture:true,passive:false});
      img.addEventListener("dragstart",preventAll,{capture:true,passive:false});
      img.addEventListener("selectstart",preventAll,{capture:true,passive:false});
      img.style.pointerEvents="auto";
      img.style.userSelect="none";
      img.style.webkitUserSelect="none";
      img.style.mozUserSelect="none";
      img.style.msUserSelect="none";
      img.style.webkitUserDrag="none";
      img.style.webkitTouchCallout="none";
      img.style.cursor="default";
    });
    
    // Slide media wrapper koruması
    var gslideMedia=c.querySelectorAll(".gslide-media, .gslide-image");
    gslideMedia.forEach(function(el){
      el.oncontextmenu=preventAll;
      el.addEventListener("contextmenu",preventAll,{capture:true,passive:false});
      el.addEventListener("selectstart",preventAll,{capture:true,passive:false});
      el.addEventListener("dragstart",preventAll,{capture:true,passive:false});
    });
  }

  function initWhenReady(){
    if(!window.GLightbox){ return setTimeout(initWhenReady,50); }
    var inst=GLightbox({
      selector:"#"+gid+" a.numistr-lb",
      touchNavigation:true,
      keyboardNavigation:true,
      loop:true,
      closeOnOutsideClick:true
    });
    
    // Her açılışta ve slide değişiminde korumayı uygula
    inst.on("open",function(){ 
      setTimeout(harden,0);
      setTimeout(harden,100);
      setTimeout(harden,300);
    });
    inst.on("slide_changed",function(){ 
      setTimeout(harden,0);
      setTimeout(harden,100);
    });
  }

  if(document.readyState==="complete"||document.readyState==="interactive"){
    setTimeout(initWhenReady,0);
  }else{
    document.addEventListener("DOMContentLoaded",initWhenReady);
  }
})();
</script>';

        // ---------- Stil ----------
        echo '<style>
.coin-gallery-wrapper{margin-top:25px;padding-top:15px;border-top:1px solid #eee;}
.coin-gallery{display:flex;flex-wrap:wrap;gap:20px;}
/* Normal öğe: iki sütun */
.gallery-item{text-align:center;flex-basis:calc(50% - 10px);flex-grow:1;}
.gallery-item img{max-width:100%;height:auto;border:1px solid #ddd;border-radius:4px;padding:5px;background:#fff;transition:box-shadow .2s}
.gallery-item img:hover{box-shadow:0 4px 10px rgba(0,0,0,.15)}
.gallery-caption{margin-top:8px;font-size:13px;color:#555;}
.gallery-caption p{margin:4px 0;line-height:1.4}

/* Çiftli (image_type=detay) öğe: tam satır, görsel ortalı ve normal boyutta */
.gallery-item.pair{flex-basis:100%;text-align:center;}
.gallery-item.pair img{max-width:480px;display:inline-block;}

/* GLightbox overlay failsafe */
.glightbox-container{position:fixed!important;inset:0!important;z-index:999999!important}
.goverlay{position:fixed!important;inset:0!important}

/* Küçük ekran iyileştirmesi */
@media (max-width: 680px){
  .gallery-item{flex-basis:100%;}
  .gallery-item.pair img{max-width:100%;}
}
</style>';

        $log('END (rendered)');
    } catch (\Throwable $e) {
        try {
            $logFile = JPATH_SITE . '/logs/numistr_gallery_debug.log';
            @error_log(date('c') . ' EXCEPTION coin_id=' . $coin_id . ' msg=' . $e->getMessage() . PHP_EOL, 3, $logFile);
        } catch (\Throwable $e2) {}
    }
}}