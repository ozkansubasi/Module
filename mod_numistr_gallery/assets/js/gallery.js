/**
 * mod_numistr_gallery — sayfadaki her galeriye GLightbox bağlar; lightbox açıkken sağ tık / sürükleme / seçme kapalı.
 * Eski betiğin örnek başına satır içi betiğiyle aynı davranış; GLightbox bu dosyadan önce (defer, bağımlılık) yüklenir.
 */
(function () {
  'use strict';

  function preventAll(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    return false;
  }

  function block(el, events) {
    el.oncontextmenu = preventAll;
    events.forEach(function (ev) {
      el.addEventListener(ev, preventAll, { capture: true, passive: false });
    });
  }

  function harden() {
    var c = document.querySelector('.glightbox-container');
    if (!c) return;

    block(c, ['contextmenu', 'selectstart', 'dragstart']);

    var overlay = c.querySelector('.goverlay');
    if (overlay) block(overlay, ['contextmenu']);

    c.querySelectorAll('.gslide, .gslide-media, .gslide-image').forEach(function (el) {
      block(el, ['contextmenu', 'selectstart', 'dragstart']);
    });

    c.querySelectorAll('img').forEach(function (img) {
      img.setAttribute('draggable', 'false');
      img.ondragstart = preventAll;
      img.onselectstart = preventAll;
      block(img, ['contextmenu', 'dragstart', 'selectstart']);
      img.style.pointerEvents = 'auto';
      img.style.userSelect = 'none';
      img.style.webkitUserSelect = 'none';
      img.style.webkitUserDrag = 'none';
      img.style.webkitTouchCallout = 'none';
      img.style.cursor = 'default';
    });
  }

  var tries = 0;

  function init() {
    if (!window.GLightbox) {
      // CDN gecikirse 10 sn bekle; gelmezse küçük görseller bağlantı olarak çalışmaya devam eder.
      if (tries++ < 200) setTimeout(init, 50);
      return;
    }

    document.querySelectorAll('.coin-gallery-wrapper[id^="numistr-gallery-"]').forEach(function (w) {
      if (w.dataset.glightbox === '1') return;
      w.dataset.glightbox = '1';

      var inst = GLightbox({
        selector: '#' + w.id + ' a.numistr-lb',
        touchNavigation: true,
        keyboardNavigation: true,
        loop: true,
        closeOnOutsideClick: true
      });

      inst.on('open', function () {
        setTimeout(harden, 0);
        setTimeout(harden, 100);
        setTimeout(harden, 300);
      });
      inst.on('slide_changed', function () {
        setTimeout(harden, 0);
        setTimeout(harden, 100);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
