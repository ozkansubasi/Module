/*!
 * mod_numistr_ilgili 1.3.0 — video oynatıcıyı tıklayınca yükler.
 * Tıklamadan önce YouTube'a / Google'a hiç istek gitmez (küçük resim sitenin kendi sunucusunda).
 * Tıklayınca youtube-nocookie.com oynatıcısı aynı yerde açılır ve oynamaya başlar.
 */
(function (w, d) {
  'use strict';

  var KIMLIK = /^[A-Za-z0-9_-]{11}$/;

  d.addEventListener('click', function (olay) {
    var dugme = olay.target.closest ? olay.target.closest('.nmi-video-oynat') : null;
    var yt = dugme ? dugme.getAttribute('data-nmi-yt') : '';

    if (!dugme || !KIMLIK.test(yt || '')) {
      return;
    }

    olay.preventDefault();

    var f = d.createElement('iframe');
    f.className = 'nmi-video-iframe';
    var dil = (d.documentElement.getAttribute('lang') || '').slice(0, 2).toLowerCase();
    f.src = 'https://www.youtube-nocookie.com/embed/' + yt + '?autoplay=1&rel=0&playsinline=1'
      + (dil === 'tr' || dil === 'en' ? '&hl=' + dil + '&cc_lang_pref=' + dil : '');
    f.title = dugme.getAttribute('data-nmi-baslik') || 'YouTube';
    f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
    f.setAttribute('allowfullscreen', '');
    // YouTube gömülü oynatıcı yönlendiren adresi ister (yoksa "Hata 153"); sayfa politikası aynı değerde, burada da açıkça.
    f.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
    dugme.parentNode.replaceChild(f, dugme);
    f.focus();

    // Ölçüm: GTM yalnız onay verildiyse yüklü; değilse dizi yalnız sayfada kalır.
    if (w.dataLayer && w.dataLayer.push) {
      w.dataLayer.push({ event: 'video_oynat', video_id: yt });
    }
  });
})(window, document);
