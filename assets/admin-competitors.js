/* global BALOA, jQuery */
jQuery(function ($) {
  if (typeof window.BALOA_Admin === 'undefined') return;

  const admin = window.BALOA_Admin;

  // Wrap renderDashboardResults to enable the compare button once results are loaded
  const originalRender = admin.renderDashboardResults;
  admin.renderDashboardResults = function (data) {
    if (typeof originalRender === 'function') {
      originalRender(data);
    }
    $('#baloa-compare-btn').prop('disabled', false);
  };

  // Click handler for competitor comparison button
  $('#baloa-compare-btn').on('click', function (e) {
    e.preventDefault();
    if (!admin.state.lastDashboardResult) return;

    $('#baloa-modal-title').text('Comparativa de Estructura con Competidores');
    $('#baloa-modal-action-btn').hide();

    let html = '<div class="competitors-setup-form" style="display:flex; flex-direction:column; gap:12px;">' +
                 '<p style="font-size:13px; color:var(--text-secondary); margin-bottom:8px;">Ingresa la URL de hasta 3 competidores para auditar brechas estructurales y de contenido:</p>' +
                 '<div>' +
                   '<label style="display:block; font-size:12px; font-weight:700; color:var(--text-primary); margin-bottom:4px;">Competidor 1</label>' +
                   '<input type="url" id="baloa-comp-url-1" class="baloa-comp-input" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; background:var(--bg-secondary); color:var(--text-primary);" placeholder="https://competidor1.com/pagina-slug/" />' +
                 '</div>' +
                 '<div>' +
                   '<label style="display:block; font-size:12px; font-weight:700; color:var(--text-primary); margin-bottom:4px;">Competidor 2</label>' +
                   '<input type="url" id="baloa-comp-url-2" class="baloa-comp-input" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; background:var(--bg-secondary); color:var(--text-primary);" placeholder="https://competidor2.com/pagina-slug/" />' +
                 '</div>' +
                 '<div>' +
                   '<label style="display:block; font-size:12px; font-weight:700; color:var(--text-primary); margin-bottom:4px;">Competidor 3</label>' +
                   '<input type="url" id="baloa-comp-url-3" class="baloa-comp-input" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; background:var(--bg-secondary); color:var(--text-primary);" placeholder="https://competidor3.com/pagina-slug/" />' +
                 '</div>' +
                 '<button type="button" id="baloa-run-comparison" class="btn btn-primary" style="margin-top:12px; width:100%; padding:10px; font-weight:bold; font-size:13px;">Iniciar Análisis Comparativo</button>' +
               '</div>';

    $('#baloa-modal-body').html(html);
    $('#baloa-modal-overlay').fadeIn('fast');
  });

  // Handle run comparison click
  $(document).on('click', '#baloa-run-comparison', function () {
    const $btn = $(this);
    const originalUrl = ($('#baloa-url-input').val() || BALOA.post_url || '').trim();
    
    const comps = [];
    $('.baloa-comp-input').each(function () {
      const val = $(this).val().trim();
      if (val) comps.push(val);
    });

    if (comps.length === 0) {
      alert('Ingresa al menos un competidor para comparar.');
      return;
    }

    $btn.prop('disabled', true).text('⏳ Descargando y analizando competidores...');

    $.ajax({
      url: BALOA.ajax_url,
      method: 'POST',
      data: {
        action: 'baloa_structure_auditor_seo_competitor_gap',
        nonce: BALOA.nonce,
        url: originalUrl,
        competitors: comps
      },
      success: function (res) {
        if (res.success) {
          admin.renderCompetitorMatrix(res.data);
        } else {
          $('#baloa-modal-body').html('<p style="color:var(--red); font-weight:bold;">Error: ' + admin.escHtml(res.data?.message || 'Error desconocido') + '</p>');
        }
      },
      error: function () {
        $('#baloa-modal-body').html('<p style="color:var(--red); font-weight:bold;">Error en el servidor al realizar el análisis.</p>');
      }
    });
  });

  admin.renderCompetitorMatrix = function (data) {
    const orig = data.original;
    const comps = data.competitors || [];

    let ths = '<th style="padding:10px; text-align:left; color:var(--text-secondary);">Métrica / Canal</th>' +
              '<th style="padding:10px; text-align:center; color:var(--primary); font-weight:bold;">Tú (Original)</th>';
    
    comps.forEach((c, idx) => {
      const name = 'Comp ' + (idx + 1);
      ths += '<th style="padding:10px; text-align:center; color:var(--text-primary);" title="' + admin.escHtml(c.url) + '">' + name + '</th>';
    });

    let trs = '';
    const metrics = [
      { key: 'globalScore', name: 'SEO Score Global' },
      { key: 'html', name: 'SEO Estructural' },
      { key: 'schema', name: 'Schema Markup' },
      { key: 'readability', name: 'Legibilidad' },
      { key: 'metatags', name: 'Metatags / Directivas' },
      { key: 'images', name: 'Imágenes / Rendimiento' },
      { key: 'links', name: 'Enlaces e Infraestructura' }
    ];

    metrics.forEach(m => {
      trs += '<tr style="border-bottom:1px solid var(--border);">';
      trs += '<td style="padding:10px; font-weight:bold; color:var(--text-secondary);">' + m.name + '</td>';
      trs += '<td style="padding:10px; text-align:center; font-weight:bold; color:var(--primary);">' + (orig[m.key] || 0) + '/100</td>';
      
      comps.forEach(c => {
        if (c.error) {
          trs += '<td style="padding:10px; text-align:center; color:var(--red); font-size:11px;">⚠️ Error</td>';
        } else {
          const val = c[m.key] || 0;
          const diff = val - (orig[m.key] || 0);
          let diffHtml = '';
          if (diff > 0) {
            diffHtml = ' <span style="color:var(--green); font-size:10px;">(+' + diff + ')</span>';
          } else if (diff < 0) {
            diffHtml = ' <span style="color:var(--red); font-size:10px;">(' + diff + ')</span>';
          }
          trs += '<td style="padding:10px; text-align:center; color:var(--text-primary);">' + val + '/100' + diffHtml + '</td>';
        }
      });
      trs += '</tr>';
    });

    let html = '<div class="competitor-results-wrapper" style="overflow-x:auto;">' +
                 '<p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">Matriz comparativa de optimización técnica y brechas detectadas:</p>' +
                 '<table class="sitemap-table" style="width:100%; border-collapse:collapse; font-size:13px;">' +
                   '<thead>' +
                     '<tr style="border-bottom:2px solid var(--border);">' + ths + '</tr>' +
                   '</thead>' +
                   '<tbody>' + trs + '</tbody>' +
                 '</table>' +
               '</div>';

    $('#baloa-modal-body').html(html);
  };
});
