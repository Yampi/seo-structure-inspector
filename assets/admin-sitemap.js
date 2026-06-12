/* global BALOA, jQuery */
jQuery(function ($) {
  if (typeof window.BALOA_Admin === 'undefined') return;

  const admin = window.BALOA_Admin;
  let isBatchRunning = false;

  admin.saveSitemapState = function () {
    try {
      localStorage.setItem('baloa_structure_auditor_seo_sitemap_state', JSON.stringify(admin.state.sitemapState));
    } catch (e) {
      console.error('[BALOA] Error al guardar sitemap en localStorage:', e);
    }
  };

  admin.restoreSitemapState = function () {
    try {
      const stateStr = localStorage.getItem('baloa_structure_auditor_seo_sitemap_state');
      if (stateStr) {
        const state = JSON.parse(stateStr);
        if (state && Array.isArray(state.urls)) {
          admin.state.sitemapState = state;
          $('#baloa-sitemap-url-input').val(admin.state.sitemapState.sitemap_url || BALOA.home_url || '');
          admin.renderSitemapTable();
          admin.updateSelectAllCheckbox();
          return true;
        }
      }
    } catch (e) {
      console.error('[BALOA] Error al restaurar sitemap de localStorage:', e);
    }
    return false;
  };

  admin.updateSelectAllCheckbox = function () {
    if (admin.state.sitemapState.urls.length === 0) return;
    const allChecked = admin.state.sitemapState.urls.every(item => item.selected !== false);
    $('#baloa-sitemap-select-all').prop('checked', allChecked);
  };

  admin.renderSitemapTable = function () {
    const $tbody = $('#baloa-sitemap-table-body');
    $tbody.empty();

    if (!admin.state.sitemapState.urls || admin.state.sitemapState.urls.length === 0) {
      $('#baloa-sitemap-controls-panel').hide();
      $('#baloa-sitemap-table-container').hide();
      return;
    }

    $('#baloa-sitemap-count-badge').text(admin.state.sitemapState.urls.length);
    $('#baloa-sitemap-controls-panel').show();
    $('#baloa-sitemap-table-container').show();

    admin.state.sitemapState.urls.forEach((item, index) => {
      const isSelected = item.selected !== false;
      const score = item.score;
      const issues = item.issues || [];

      let scoreHtml = '<span class="sitemap-score-pill score-none">—</span>';
      if (score !== null && score !== undefined) {
        let scoreClass = 'score-bad';
        if (score >= 80) scoreClass = 'score-good';
        else if (score >= 40) scoreClass = 'score-mid';
        scoreHtml = '<span class="sitemap-score-pill ' + scoreClass + '">' + score + '</span>';
      }

      let diagHtml = '<span style="color:var(--text-muted)">Pendiente</span>';
      if (score !== null && score !== undefined) {
        if (issues.length === 0) {
          diagHtml = '<span style="color:var(--green); font-weight: 600;">✓ Sin problemas</span>';
        } else {
          diagHtml = '<div style="display:flex; flex-direction:column; gap:4px; font-size:11px;">';
          issues.forEach(iss => {
            const cleanIss = iss.replace(/^\[.*?\]\s*/, '');
            const isCritical = iss.toLowerCase().includes('crítico') || iss.toLowerCase().includes('critical') || iss.toLowerCase().includes('h1') || iss.toLowerCase().includes('meta title') || iss.toLowerCase().includes('robots');
            const badgeClass = isCritical ? 'crit' : 'warn';
            diagHtml += '<span class="sitemap-issue-indicator ' + badgeClass + '">' + admin.escHtml(cleanIss) + '</span>';
          });
          diagHtml += '</div>';
        }
      }

      let actionHtml = '';
      if (score !== null && score !== undefined) {
        actionHtml = '<button type="button" class="btn-sitemap-detail" data-url="' + admin.escHtml(item.url) + '">Detalle & Corregir 🛠️</button>';
      } else {
        actionHtml = '<button type="button" class="btn-sitemap-quick" data-url="' + admin.escHtml(item.url) + '" data-index="' + index + '">Analizar</button>';
      }

      const rowHtml = '<tr data-index="' + index + '">' +
        '<td style="text-align:center; padding: 12px;"><input type="checkbox" class="sitemap-row-checkbox" ' + (isSelected ? 'checked' : '') + ' style="cursor:pointer;" /></td>' +
        '<td style="padding: 12px;"><a href="' + admin.escHtml(item.url) + '" target="_blank" class="sitemap-url-link">' + admin.escHtml(item.url) + '</a></td>' +
        '<td style="padding: 12px; font-size:12px; color:var(--text-muted);">' + admin.escHtml(item.lastmod) + '</td>' +
        '<td class="sitemap-score-badge-cell" style="padding: 12px;">' + scoreHtml + '</td>' +
        '<td class="sitemap-diagnostics-cell" style="padding: 12px;">' + diagHtml + '</td>' +
        '<td style="text-align:right; padding: 12px;">' + actionHtml + '</td>' +
      '</tr>';

      $tbody.append(rowHtml);
    });

    const hasAnalyzed = admin.state.sitemapState.urls.some(item => item.score !== null && item.score !== undefined);
    if (hasAnalyzed) {
      $('#baloa-sitemap-export-btn').fadeIn('fast');
    } else {
      $('#baloa-sitemap-export-btn').hide();
    }
  };

  admin.analyzeUrlsInBatch = function (indices, keyword) {
    if (isBatchRunning || indices.length === 0) return;
    isBatchRunning = true;

    $('#baloa-sitemap-discover-btn').prop('disabled', true);
    $('#baloa-sitemap-batch-btn').prop('disabled', true).text('⏳ Procesando...');
    $('#baloa-sitemap-clear-btn').prop('disabled', true);
    $('.sitemap-row-checkbox, #baloa-sitemap-select-all').prop('disabled', true);
    $('.btn-sitemap-quick, .btn-sitemap-detail').prop('disabled', true);
    
    const total = indices.length;
    let completed = 0;
    $('#baloa-sitemap-progress-count').text('0/' + total);
    $('#baloa-sitemap-progress-percent').text('0%');
    $('#baloa-sitemap-progress-bar-fill').css('width', '0%');
    $('#baloa-sitemap-progress-wrap').slideDown('fast');

    const urlsToAnalyze = indices.map(idx => admin.state.sitemapState.urls[idx].url);

    $.ajax({
      url: BALOA.ajax_url,
      method: 'POST',
      data: {
        action: 'baloa_structure_auditor_seo_batch_create',
        nonce: BALOA.nonce,
        urls: urlsToAnalyze,
        keyword: keyword
      },
      success: function(res) {
        if (!res.success) {
          alert('Error al iniciar el lote: ' + (res.data?.message || 'Desconocido'));
          admin.resetBatchUI();
          return;
        }

        const jobId = res.data.job_id;
        admin.state.sitemapState.job_id = jobId;
        admin.saveSitemapState();
        let currentIndex = 0;

        function processNext() {
          if (currentIndex >= total) {
            admin.resetBatchUI();
            return;
          }

          const targetIndex = indices[currentIndex];
          const item = admin.state.sitemapState.urls[targetIndex];
          const $row = $('.sitemap-table tbody tr[data-index="' + targetIndex + '"]');
          
          $row.addClass('sitemap-loading-row');
          $row.find('.sitemap-score-badge-cell').html('<span class="sitemap-score-pill score-none">⏳</span>');
          $row.find('.sitemap-diagnostics-cell').html('<span style="color:var(--accent-blue)">Analizando...</span>');

          $.ajax({
            url: BALOA.ajax_url,
            method: 'POST',
            data: {
              action: 'baloa_structure_auditor_seo_batch_analyze_url',
              nonce: BALOA.nonce,
              job_id: jobId,
              url: item.url,
              keyword: keyword
            },
            success: function(analysisRes) {
              $row.removeClass('sitemap-loading-row');
              
              if (analysisRes.success && analysisRes.data.status === 'ok') {
                const summary = analysisRes.data.summary;
                item.score = summary.global;
                item.issues = summary.issues || [];
              } else {
                item.score = 0;
                item.issues = [analysisRes.data?.message || analysisRes.data?.error || 'Error al analizar la página.'];
              }

              admin.saveSitemapState();
              admin.updateRowUI(targetIndex);
            },
            error: function() {
              $row.removeClass('sitemap-loading-row');
              item.score = 0;
              item.issues = ['Error de red al conectar con el servidor.'];
              admin.saveSitemapState();
              admin.updateRowUI(targetIndex);
            },
            complete: function() {
              completed++;
              currentIndex++;
              
              const percent = Math.round((completed / total) * 100);
              $('#baloa-sitemap-progress-count').text(completed + '/' + total);
              $('#baloa-sitemap-progress-percent').text(percent + '%');
              $('#baloa-sitemap-progress-bar-fill').css('width', percent + '%');

              setTimeout(processNext, 500);
            }
          });
        }

        processNext();
      },
      error: function() {
        alert('Error al conectar con el servidor para crear el lote.');
        admin.resetBatchUI();
      }
    });
  };

  admin.updateRowUI = function (idx) {
    const item = admin.state.sitemapState.urls[idx];
    const $row = $('.sitemap-table tbody tr[data-index="' + idx + '"]');
    if ($row.length === 0) return;

    const score = item.score;
    const issues = item.issues || [];

    let scoreClass = 'score-bad';
    if (score >= 80) scoreClass = 'score-good';
    else if (score >= 40) scoreClass = 'score-mid';
    const scoreHtml = '<span class="sitemap-score-pill ' + scoreClass + '">' + score + '</span>';
    $row.find('.sitemap-score-badge-cell').html(scoreHtml);

    let diagHtml = '';
    if (issues.length === 0) {
      diagHtml = '<span style="color:var(--green); font-weight: 600;">✓ Sin problemas</span>';
    } else {
      diagHtml = '<div style="display:flex; flex-direction:column; gap:4px; font-size:11px;">';
      issues.forEach(iss => {
        const cleanIss = iss.replace(/^\[.*?\]\s*/, '');
        const isCritical = iss.toLowerCase().includes('crítico') || iss.toLowerCase().includes('critical') || iss.toLowerCase().includes('h1') || iss.toLowerCase().includes('meta title') || iss.toLowerCase().includes('robots');
        const badgeClass = isCritical ? 'crit' : 'warn';
        diagHtml += '<span class="sitemap-issue-indicator ' + badgeClass + '">' + admin.escHtml(cleanIss) + '</span>';
      });
      diagHtml += '</div>';
    }
    $row.find('.sitemap-diagnostics-cell').html(diagHtml);

    const actionHtml = '<button type="button" class="btn-sitemap-detail" data-url="' + admin.escHtml(item.url) + '">Detalle & Corregir 🛠️</button>';
    $row.find('td:last-child').html(actionHtml);
  };

  admin.resetBatchUI = function () {
    isBatchRunning = false;
    $('#baloa-sitemap-discover-btn').prop('disabled', false);
    $('#baloa-sitemap-batch-btn').prop('disabled', false).text('⚡ Iniciar Análisis Masivo');
    $('#baloa-sitemap-clear-btn').prop('disabled', false);
    $('.sitemap-row-checkbox, #baloa-sitemap-select-all').prop('disabled', false);
    $('.btn-sitemap-quick, .btn-sitemap-detail').prop('disabled', false);
    setTimeout(function() {
      $('#baloa-sitemap-progress-wrap').slideUp('fast');
    }, 3000);
  };

  admin.initSitemapExplorer = function () {
    $('#baloa-sitemap-discover-btn').on('click', function(e) {
      e.preventDefault();
      const sitemapUrl = $('#baloa-sitemap-url-input').val().trim();
      if (!sitemapUrl) {
        alert('Por favor, ingresa una URL válida.');
        return;
      }

      $('#baloa-sitemap-discover-btn').prop('disabled', true);
      $('#baloa-sitemap-scan-status').text('⏳ Descubriendo páginas del sitio...').fadeIn('fast');

      $.ajax({
        url: BALOA.ajax_url,
        method: 'POST',
        data: {
          action: 'baloa_structure_auditor_seo_fetch_sitemap',
          nonce: BALOA.nonce,
          url: sitemapUrl
        },
        success: function(res) {
          if (res.success && res.data && Array.isArray(res.data.urls)) {
            admin.state.sitemapState.sitemap_url = sitemapUrl;
            admin.state.sitemapState.urls = res.data.urls.map(item => ({
              url: item.url,
              lastmod: item.lastmod || '—',
              score: null,
              issues: null
            }));
            admin.saveSitemapState();
            admin.renderSitemapTable();
            $('#baloa-sitemap-scan-status').text('✓ Páginas descubiertas con éxito.').fadeOut(3000);
          } else {
            $('#baloa-sitemap-scan-status').html('<span style="color:var(--red)">✗ ' + admin.escHtml(res.data?.message || 'No se pudo leer el sitemap.') + '</span>');
          }
        },
        error: function() {
          $('#baloa-sitemap-scan-status').html('<span style="color:var(--red)">✗ Error de conexión al escanear sitemap.</span>');
        },
        complete: function() {
          $('#baloa-sitemap-discover-btn').prop('disabled', false);
        }
      });
    });

    $(document).on('change', '#baloa-sitemap-select-all', function() {
      const isChecked = $(this).is(':checked');
      $('.sitemap-row-checkbox').prop('checked', isChecked);
      admin.state.sitemapState.urls.forEach(item => {
        item.selected = isChecked;
      });
      admin.saveSitemapState();
    });

    $(document).on('change', '.sitemap-row-checkbox', function() {
      const index = $(this).closest('tr').data('index');
      admin.state.sitemapState.urls[index].selected = $(this).is(':checked');
      admin.saveSitemapState();
      admin.updateSelectAllCheckbox();
    });

    $('#baloa-sitemap-batch-btn').on('click', function(e) {
      e.preventDefault();
      const keyword = $('#baloa-sitemap-batch-keyword').val().trim();
      
      const selectedIndices = [];
      admin.state.sitemapState.urls.forEach((item, index) => {
        if (item.selected !== false) {
          selectedIndices.push(index);
        }
      });

      if (selectedIndices.length === 0) {
        alert('Por favor, selecciona al menos una página para analizar.');
        return;
      }

      admin.analyzeUrlsInBatch(selectedIndices, keyword);
    });

    $(document).on('click', '.btn-sitemap-quick', function(e) {
      e.preventDefault();
      const index = $(this).data('index');
      admin.analyzeUrlsInBatch([index], '');
    });

    $('#baloa-sitemap-clear-btn').on('click', function(e) {
      e.preventDefault();
      if (confirm('¿Estás seguro de que deseas limpiar la lista de páginas descubiertas?')) {
        admin.state.sitemapState = { sitemap_url: '', urls: [] };
        admin.saveSitemapState();
        admin.renderSitemapTable();
      }
    });

    $(document).on('click', '.btn-sitemap-detail', function(e) {
      e.preventDefault();
      const url = $(this).data('url');
      if (!url) return;

      $('#baloa-url-input').val(url);

      $('.nav-item').removeClass('active');
      $('.nav-item[data-module="resumen"]').addClass('active');
      $('#view-sitemap').hide();
      $('#view-resumen').show();

      $('#baloa-analyze-btn-dash').click();
    });

    $('#baloa-sitemap-export-btn').on('click', function(e) {
      e.preventDefault();
      const jobId = admin.state.sitemapState.job_id;
      if (!jobId) {
        alert('No hay un identificador de lote disponible para exportar.');
        return;
      }
      const downloadUrl = BALOA.ajax_url + '?action=bsa_export_batch_csv&nonce=' + BALOA.nonce + '&job_id=' + jobId;
      window.location.href = downloadUrl;
    });

    const hasState = admin.restoreSitemapState();
    if (!hasState && BALOA.home_url) {
      const defaultSitemap = BALOA.home_url.replace(/\/$/, '') + '/wp-sitemap.xml';
      $('#baloa-sitemap-url-input').val(defaultSitemap);
      setTimeout(function() {
        $('#baloa-sitemap-discover-btn').click();
      }, 500);
    }
  };

  admin.initSitemapExplorer();
});
