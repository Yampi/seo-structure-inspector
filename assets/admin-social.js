/* global BALOA, jQuery */
jQuery(function ($) {
  if (typeof window.BALOA_Admin === 'undefined') return;

  const admin = window.BALOA_Admin;

  admin.updateSocialPreview = function () {
    const $panel = $('#baloa-social-preview-panel');
    if (!$panel.length) return;

    if (admin.state.activeModule !== 'metatags' || !admin.state.lastDashboardResult) {
      $panel.hide();
      return;
    }

    $panel.fadeIn('fast');

    const d = admin.state.lastDashboardResult;
    const meta = d.metatags || {};
    const details = meta.details || {};

    // Get values
    const ogTitle = details['og:title'] || details['title'] || '';
    const ogDesc = details['og:description'] || details['desc'] || '';
    const ogImg = details['og:image'] || '';

    const twTitle = details['twitter:title'] || ogTitle || '';
    const twDesc = details['twitter:description'] || ogDesc || '';
    const twImg = details['twitter:image'] || ogImg || '';

    const domain = d.url ? admin.getDomainFromUrl(d.url) : 'TUDOMINIO.COM';

    // 1. Update Facebook Preview
    $('.fb-post-text').text(ogDesc ? 'Lee nuestro último artículo: ' + ogTitle : 'Échale un vistazo a nuestro nuevo artículo...');
    $('.fb-preview-domain').text(domain);
    $('.fb-preview-title').text(ogTitle || 'Falta og:title');
    $('.fb-preview-desc').text(ogDesc || 'Falta og:description');

    if (ogImg) {
      $('.fb-preview-img').attr('src', ogImg).show();
      $('.fb-preview-placeholder').hide();
    } else {
      $('.fb-preview-img').hide().attr('src', '');
      $('.fb-preview-placeholder').show();
    }

    // 2. Update Twitter/X Preview
    $('.tw-post-text').text(twDesc ? 'Te recomendamos leer: ' + twTitle : 'Aquí un adelanto de nuestro contenido...');
    $('.tw-preview-domain').text(domain);
    $('.tw-preview-title').text(twTitle || 'Falta twitter:title');
    $('.tw-preview-desc').text(twDesc || 'Falta twitter:description');

    if (twImg) {
      $('.tw-preview-img').attr('src', twImg).show();
      $('.tw-preview-placeholder').hide();
    } else {
      $('.tw-preview-img').hide().attr('src', '');
      $('.tw-preview-placeholder').show();
    }
  };

  admin.getDomainFromUrl = function (url) {
    try {
      const parsed = new URL(url);
      return parsed.hostname.toUpperCase();
    } catch (e) {
      return 'TUDOMINIO.COM';
    }
  };
});
