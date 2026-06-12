/* global BALOA, jQuery */
jQuery(function ($) {
  if (typeof window.BALOA_Admin === 'undefined') return;

  const admin = window.BALOA_Admin;

  admin.executeSequentialAutofixes = function (fixes, progressCallback, completionCallback) {
    if (!fixes || fixes.length === 0) {
      completionCallback({ successCount: 0, totalCount: 0 });
      return;
    }

    let currentIndex = 0;
    let successCount = 0;
    const totalCount = fixes.length;

    function processNext() {
      if (currentIndex >= totalCount) {
        completionCallback({ successCount: successCount, totalCount: totalCount });
        return;
      }

      const fix = fixes[currentIndex];
      progressCallback(currentIndex, totalCount, fix);

      $.ajax({
        url: BALOA.ajax_url,
        method: 'POST',
        data: {
          action: 'baloa_structure_auditor_seo_execute_autofix',
          nonce: BALOA.nonce,
          check_id: fix.id,
          module: fix.module,
          url: fix.url,
          input_data: JSON.stringify({ autogenerate: 'true' })
        },
        success: function(res) {
          if (res && res.success) {
            successCount++;
          }
        },
        error: function(xhr, status, err) {
          console.error('[BALOA] Sequential autofix failed for', fix.id, err);
        },
        complete: function() {
          currentIndex++;
          setTimeout(processNext, 200);
        }
      });
    }

    processNext();
  };

  admin.openWpManualFixesModal = function (item, manualProblems, afterAutomatic) {
    $('#baloa-modal-title').text('Acción requerida: ' + item.title);
    $('#baloa-modal-action-btn').hide();

    let html = '<div class="wp-modal-autofix-list" style="display:flex; flex-direction:column; gap:12px;">';
    if (afterAutomatic) {
      html += '<p style="font-size:13px; color:var(--text-secondary); margin-bottom:8px;">Se completaron los auto-fixes automáticos. Los siguientes checks requieren que ingreses información personalizada:</p>';
    } else {
      html += '<p style="font-size:13px; color:var(--text-secondary); margin-bottom:8px;">Los siguientes checks requieren que ingreses información personalizada para aplicarse:</p>';
    }
    
    manualProblems.forEach(p => {
      const isCrit = p.severity === 'critical';
      const label = isCrit ? 'Crítico' : 'Advertencia';
      const badgeClass = isCrit ? 'crit' : 'warn';
      
      html += '<div class="wp-modal-autofix-item" style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-secondary); border:1px solid var(--border); border-radius:6px; padding:12px 16px; gap:16px;">' +
                '<div style="flex:1;">' +
                  '<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">' +
                    '<span class="sitemap-issue-indicator ' + badgeClass + '">' + label + '</span>' +
                    '<strong style="font-size:13px; color:var(--text-primary);">' + admin.escHtml(p.title || p.id) + '</strong>' +
                  '</div>' +
                  '<div style="font-size:12px; color:var(--text-secondary);">' + admin.escHtml(p.recommendation || p.why || '') + '</div>' +
                '</div>' +
                '<div>' +
                  '<button type="button" class="btn-autofix wp-post-btn-autofix" data-id="' + p.id + '" data-module="' + p.module + '" data-url="' + admin.escHtml(item.url) + '">Auto-fix</button>' +
                '</div>' +
              '</div>';
    });
    html += '</div>';

    $('#baloa-modal-body').html(html);
    $('#baloa-modal-overlay').fadeIn('fast');
  };

  admin.handleSuccessfulAutoFix = function (id) {
    const currentUrl = $('#baloa-modal-action-btn').data('url');
    if (currentUrl) {
      const isWpItem = admin.state.wpPosts.concat(admin.state.wpPages).some(p => p.url === currentUrl);
      if (isWpItem && typeof admin.refreshWpItem === 'function') {
        admin.refreshWpItem(currentUrl);
      }
    }

    $('#baloa-modal-body button[data-id="' + id + '"]').closest('div').css('opacity', '0.5').find('button').prop('disabled', true).text('✓ Resuelto');

    const $card = $('.problem-card').has('button[data-id="' + id + '"]');
    if ($card.length === 0) return;

    const isCrit = $card.hasClass('prob-card-crit');

    $card.fadeOut(400, function() {
      $(this).remove();
      if ($('.problem-card').length === 0) {
        $('#baloa-problems-list').html(
          '<div style="text-align:center;color:var(--green);padding:40px; background:var(--bg-card); border-radius:12px; border:1px solid var(--border);">🚀 ¡Felicidades! Tu estructura es perfecta y no hay problemas.</div>'
        );
      }
    });

    const fixedCheck = admin.state.globalProblems.find(function(p) { return p.id === id; });
    admin.state.globalProblems = admin.state.globalProblems.filter(function(p) { return p.id !== id; });
    if (fixedCheck) {
      fixedCheck.passed = true;
      fixedCheck.severity = 'pass';
      admin.state.globalCorrectos.unshift(fixedCheck);
    }

    if (isCrit) {
      const $critCount = $('#baloa-count-crit');
      let val = parseInt($critCount.text()) || 0;
      $critCount.text(Math.max(0, val - 1));
    } else {
      const $warnCount = $('#baloa-count-warn');
      let val = parseInt($warnCount.text()) || 0;
      $warnCount.text(Math.max(0, val - 1));
    }
    const $passCount = $('#baloa-count-pass');
    let passVal = parseInt($passCount.text()) || 0;
    $passCount.text(passVal + 1);

    const minsToSubtract = isCrit ? 10 : 5;
    const $timeVal = $('#baloa-time-est');
    let currentMins = parseInt($timeVal.text()) || 0;
    let newMins = Math.max(0, currentMins - minsToSubtract);
    $timeVal.html(newMins + ' <span>min</span>');

    if (typeof admin.renderQuickWinsAndCorrectos === 'function') {
      admin.renderQuickWinsAndCorrectos(admin.state.globalProblems, admin.state.globalCorrectos);
    }
    if (typeof admin.updateFilterChipCounts === 'function') {
      admin.updateFilterChipCounts();
    }
  };

  // Initialize Modal structure if not present
  if ($('body').find('#baloa-modal-overlay').length === 0) {
    $('body').append(
      '<div id="baloa-modal-overlay" class="baloa-modal-overlay" style="display:none;">' +
        '<div class="baloa-modal">' +
          '<div class="baloa-modal-header">' +
            '<h3 id="baloa-modal-title">Título</h3>' +
            '<button type="button" id="baloa-modal-close">&times;</button>' +
          '</div>' +
          '<div id="baloa-modal-body" class="baloa-modal-body"></div>' +
          '<div class="baloa-modal-footer">' +
            '<button type="button" id="baloa-modal-action-btn" class="btn-autofix" style="display:none;">Aplicar</button>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  // Bind modal events
  $('#baloa-modal-close').on('click', function() {
    $('#baloa-modal-overlay').fadeOut('fast');
  });

  $(document).on('click', '.btn-sol', function() {
    const id = $(this).data('id');
    const module = $(this).data('module');
    const desc = $(this).data('desc');
    
    $('#baloa-modal-title').text('Solución Sugerida');
    $('#baloa-modal-body').html('<div style="text-align:center;">Cargando solución...</div>');
    $('#baloa-modal-action-btn').hide();
    $('#baloa-modal-overlay').fadeIn('fast');

    $.ajax({
      url: BALOA.ajax_url,
      method: 'POST',
      data: {
        action: 'baloa_structure_auditor_seo_get_solution',
        nonce: BALOA.nonce,
        check_id: id,
        module: module,
        desc: desc
      },
      success: function(res) {
        if (res.success) {
          $('#baloa-modal-body').html(res.data.solution_html);
        } else {
          $('#baloa-modal-body').html('<p style="color:red;">Error: ' + res.data.message + '</p>');
        }
      }
    });
  });

  $(document).on('click', '.btn-autofix', function() {
    if ($(this).attr('id') === 'baloa-modal-action-btn') return; 
    
    const id = $(this).data('id');
    const module = $(this).data('module');
    const url = $(this).data('url');
    
    $('#baloa-modal-title').text('Auto-Fix');
    $('#baloa-modal-body').html('<div style="text-align:center;">Analizando requerimientos para fix...</div>');
    $('#baloa-modal-action-btn').hide().data('id', id).data('module', module).data('url', url);
    $('#baloa-modal-overlay').fadeIn('fast');

    $.ajax({
      url: BALOA.ajax_url,
      method: 'POST',
      data: {
        action: 'baloa_structure_auditor_seo_autofix_info',
        nonce: BALOA.nonce,
        check_id: id,
        module: module,
        url: url
      },
      success: function(res) {
        if (res.success) {
          let html = '<p>' + admin.escHtml(res.data.description) + '</p>';
          if (res.data.requires_input) {
            res.data.fields.forEach(function(f) {
              html += '<div style="margin-top:10px;">';
              html += '<label style="display:block;margin-bottom:5px;font-weight:bold;">' + admin.escHtml(f.label) + '</label>';
              const val = admin.escHtml(f.value || '');
              if (f.type === 'textarea') {
                html += '<textarea id="af-input-' + admin.escHtml(f.name) + '" class="baloa-af-input" style="width:100%;height:150px;padding:8px;border:1px solid var(--border);border-radius:4px;font-family:monospace;resize:vertical;" placeholder="' + admin.escHtml(f.placeholder || '') + '">' + val + '</textarea>';
              } else {
                html += '<input type="text" id="af-input-' + admin.escHtml(f.name) + '" class="baloa-af-input" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:4px;" placeholder="' + admin.escHtml(f.placeholder || '') + '" value="' + val + '">';
              }
              html += '</div>';
            });
          }
          $('#baloa-modal-body').html(html);
          $('#baloa-modal-action-btn').show().text(res.data.requires_input ? 'Aplicar con Datos' : 'Ejecutar Auto-Fix');
          
          if (res.data.supports_autogen) {
            if ($('#baloa-modal-autogen-btn').length === 0) {
              $('#baloa-modal-action-btn').before('<button type="button" id="baloa-modal-autogen-btn" class="btn-autofix" style="margin-right:10px; background-color: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">Auto-Generar (Recomendado)</button>');
            }
            $('#baloa-modal-autogen-btn').show().data('id', id).data('module', module).data('url', url);
          } else {
            $('#baloa-modal-autogen-btn').hide();
          }
        } else {
          $('#baloa-modal-body').html('<p style="color:red;">' + (res.data.message || 'Error desconocido') + '</p>');
        }
      }
    });
  });

  $('#baloa-modal-action-btn').on('click', function() {
    const $btn = $(this);
    const id = $btn.data('id');
    const module = $btn.data('module');
    const url = $btn.data('url');
    
    let inputData = {};
    $('.baloa-af-input').each(function() {
      const name = $(this).attr('id').replace('af-input-', '');
      inputData[name] = $(this).val();
    });

    $btn.prop('disabled', true).text('Ejecutando...');

    $.ajax({
      url: BALOA.ajax_url,
      method: 'POST',
      data: {
        action: 'baloa_structure_auditor_seo_execute_autofix',
        nonce: BALOA.nonce,
        check_id: id,
        module: module,
        url: url,
        input_data: JSON.stringify(inputData)
      },
      success: function(res) {
        if (res.success) {
          $('#baloa-modal-body').html('<p style="color:var(--green);font-weight:bold;">✓ ' + admin.escHtml(res.data.message) + '</p>');
          $btn.hide();
          if ($('#baloa-modal-autogen-btn').length) {
            $('#baloa-modal-autogen-btn').hide();
          }
          admin.handleSuccessfulAutoFix(id);
        } else {
          $('#baloa-modal-body').append('<p style="color:var(--red);margin-top:10px;">Error: ' + admin.escHtml(res.data.message) + '</p>');
        }
      },
      complete: function() {
        $btn.prop('disabled', false).text('Aplicar con Datos');
      }
    });
  });

  $(document).on('click', '#baloa-modal-autogen-btn', function() {
    const $btn = $(this);
    const id = $btn.data('id');
    const module = $btn.data('module');
    const url = $btn.data('url');
    
    $btn.prop('disabled', true).text('Generando...');
    $('#baloa-modal-action-btn').prop('disabled', true);

    $.ajax({
      url: BALOA.ajax_url,
      method: 'POST',
      data: {
        action: 'baloa_structure_auditor_seo_execute_autofix',
        nonce: BALOA.nonce,
        check_id: id,
        module: module,
        url: url,
        input_data: JSON.stringify({ autogenerate: 'true' })
      },
      success: function(res) {
        if (res.success) {
          $('#baloa-modal-body').html('<p style="color:var(--green);font-weight:bold;">✓ ' + admin.escHtml(res.data.message) + '</p>');
          $btn.hide();
          $('#baloa-modal-action-btn').hide();
          admin.handleSuccessfulAutoFix(id);
        } else {
          $('#baloa-modal-body').append('<p style="color:var(--red);margin-top:10px;">Error: ' + admin.escHtml(res.data.message) + '</p>');
        }
      },
      complete: function() {
        $btn.prop('disabled', false).text('Auto-Generar (Recomendado)');
        $('#baloa-modal-action-btn').prop('disabled', false);
      }
    });
  });
});
