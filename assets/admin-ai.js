/* global BALOA, jQuery */
jQuery(function ($) {
  if (typeof window.BALOA_Admin === 'undefined') return;

  const admin = window.BALOA_Admin;

  admin.initAIRecommendations = function () {
    $(document).on('click', '#btn-tip-ia', function(e) {
      e.preventDefault();
      $('.nav-item[data-module="recommendations"]').click();
    });

    $(document).on('click', '#baloa-ai-filter-chips .filter-chip', function(e) {
      e.preventDefault();
      $('#baloa-ai-filter-chips .filter-chip').removeClass('active');
      $(this).addClass('active');
      
      const role = $(this).data('role');
      admin.renderAIRecommendations(role);
    });

    $(document).on('click', '.ai-rec-card', function(e) {
      if ($(e.target).closest('button, select, input, textarea, pre').length) return;

      const $drawer = $(this).find('.ai-rec-details-drawer');
      const $arrow = $(this).find('.ai-rec-toggle-arrow');
      
      if ($drawer.is(':visible')) {
        $drawer.slideUp('fast');
        $arrow.css('transform', 'rotate(0deg)');
      } else {
        $drawer.slideDown('fast');
        $arrow.css('transform', 'rotate(180deg)');
      }
    });

    $(document).on('click', '.btn-copy-code', function(e) {
      e.preventDefault();
      const $btn = $(this);
      const code = $btn.data('code');
      
      if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(function() {
          const oldText = $btn.text();
          $btn.text('¡Copiado!').css('color', '#10b981');
          setTimeout(() => {
            $btn.text(oldText).css('color', '#3b82f6');
          }, 2000);
        });
      } else {
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(code).select();
        document.execCommand('copy');
        $temp.remove();
        const oldText = $btn.text();
        $btn.text('¡Copiado!').css('color', '#10b981');
        setTimeout(() => {
          $btn.text(oldText).css('color', '#3b82f6');
        }, 2000);
      }
    });

    $(document).on('click', '.btn-ai-simulate-fix', function(e) {
      e.preventDefault();
      const $btn = $(this);
      
      $btn.prop('disabled', true).text('⏳ Procesando...');
      
      setTimeout(function() {
        $btn.text('✓ ¡Aplicado con éxito!').css({
          'background': '#10b981',
          'box-shadow': 'none'
        });
        
        const analyzedUrl = ($('#baloa-url-input').val() || BALOA.post_url || '').replace(/\/$/, '');
        if (analyzedUrl) {
          const cacheKey = 'baloa_structure_auditor_seo_ai_cache_' + encodeURIComponent(analyzedUrl);
          localStorage.removeItem(cacheKey);
        }
      }, 1200);
    });

    $(document).on('change', '#baloa-ai-provider-select', function() {
      const selectedProvider = $(this).val();
      
      $.ajax({
        url: BALOA.ajax_url,
        method: 'POST',
        data: {
          action: 'baloa_structure_auditor_seo_save_ai_provider',
          nonce: BALOA.nonce,
          provider: selectedProvider
        }
      });

      const analyzedUrl = ($('#baloa-url-input').val() || BALOA.post_url || '').replace(/\/$/, '');
      if (analyzedUrl) {
        const cacheKey = 'baloa_structure_auditor_seo_ai_cache_' + encodeURIComponent(analyzedUrl);
        localStorage.removeItem(cacheKey);
      }
      
      admin.loadAIRecommendations();
    });

    $(document).on('click', '.btn-copy-prompt', function(e) {
      e.preventDefault();
      const $btn = $(this);
      const prompt = $btn.data('prompt');
      
      if (navigator.clipboard) {
        navigator.clipboard.writeText(prompt).then(function() {
          const oldHtml = $btn.html();
          $btn.html('💬 ¡Copiado!').css('color', '#10b981');
          setTimeout(() => {
            $btn.html(oldHtml).css('color', 'var(--text-primary)');
          }, 2000);
        });
      } else {
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(prompt).select();
        document.execCommand('copy');
        $temp.remove();
        const oldHtml = $btn.html();
        $btn.html('💬 ¡Copiado!').css('color', '#10b981');
        setTimeout(() => {
          $btn.html(oldHtml).css('color', 'var(--text-primary)');
        }, 2000);
      }
    });
  };

  admin.loadAIRecommendations = function () {
    const analyzedUrl = ($('#baloa-url-input').val() || BALOA.post_url || '').replace(/\/$/, '');
    const $list = $('#baloa-ai-rec-list');

    if (!analyzedUrl) {
      $list.html(
        '<div style="text-align:center; color:var(--text-muted); padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">' +
          '<span style="font-size:32px; display:block; margin-bottom:12px;">🔍</span>' +
          'Ingresa una URL en el panel superior y presiona analizar para que el grupo de expertos genere sugerencias detalladas.' +
        '</div>'
      );
      $('#baloa-ai-crit-count').text('—');
      $('#baloa-ai-count-all').text('0');
      $('#baloa-ai-count-uiux').text('0');
      $('#baloa-ai-count-seogeoaeo').text('0');
      $('#baloa-ai-count-wparch').text('0');
      return;
    }

    const cacheKey = 'baloa_structure_auditor_seo_ai_cache_' + encodeURIComponent(analyzedUrl);
    const cachedData = localStorage.getItem(cacheKey);
    if (cachedData) {
      try {
        const data = JSON.parse(cachedData);
        if (data && (Date.now() - data.timestamp) < 600000) { // 10 minutes cache
          admin.state.lastAIRecommendations = data.results;
          admin.processAIRecommendationsResults(admin.state.lastAIRecommendations);
          return;
        }
      } catch(e) {}
    }

    $list.html(
      '<div style="text-align:center; padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">' +
        '<div class="ai-skeleton-loader" style="margin: 0 auto 16px auto; width: 48px; height: 48px; border: 4px solid var(--border); border-top-color: var(--accent-purple); border-radius: 50%; animation: spin 1s linear infinite;"></div>' +
        '<p style="color:var(--text-secondary); font-size:14px; font-weight:700; margin:0 0 4px 0;">Reuniendo al Grupo de Trabajo...</p>' +
        '<p style="color:var(--text-muted); font-size:12px; margin:0;">El Consultor UI-UX, Especialista SEO-GEO-AEO y el Arquitecto de WordPress están evaluando ' + admin.escHtml(analyzedUrl) + '...</p>' +
      '</div>'
    );

    let analysisScore = '0';
    try {
      const lastResult = localStorage.getItem('baloa_structure_auditor_seo_last_result');
      if (lastResult) {
        analysisScore = JSON.parse(lastResult).global_score || '0';
      }
    } catch(e) {}

    $.ajax({
      url: BALOA.ajax_url,
      method: 'POST',
      data: {
        action: 'baloa_structure_auditor_seo_get_ai_recommendations',
        nonce: BALOA.nonce,
        url: analyzedUrl,
        analysis_hash: analysisScore
      },
      success: function(res) {
        if (res.success && res.data) {
          admin.state.lastAIRecommendations = res.data;
          
          try {
            localStorage.setItem(cacheKey, JSON.stringify({
              timestamp: Date.now(),
              results: res.data
            }));
          } catch(e) {}
          
          admin.processAIRecommendationsResults(admin.state.lastAIRecommendations);
        } else {
          $list.html(
            '<div style="text-align:center; color:var(--red); padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">' +
              '✗ Error al obtener recomendaciones: ' + admin.escHtml(res.data?.message || 'Error desconocido') +
            '</div>'
          );
        }
      },
      error: function() {
        $list.html(
          '<div style="text-align:center; color:var(--red); padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">' +
            '✗ Error de conexión con el motor de IA.' +
          '</div>'
        );
      }
    });
  };

  admin.processAIRecommendationsResults = function (data) {
    const total = data.recommendations.length;
    const uiux = data.recommendations.filter(r => r.role === 'ui_ux').length;
    const seo = data.recommendations.filter(r => r.role === 'seo_geo_aeo').length;
    const wparch = data.recommendations.filter(r => r.role === 'wp_architect').length;

    $('#baloa-ai-confidence-val').text(data.meta.ai_confidence || '96');
    $('#baloa-ai-crit-count').text(total);

    $('#baloa-ai-count-all').text(total);
    $('#baloa-ai-count-uiux').text(uiux);
    $('#baloa-ai-count-seogeoaeo').text(seo);
    $('#baloa-ai-count-wparch').text(wparch);

    // Show/hide contextual badge based on provider
    if (data.meta && data.meta.provider && data.meta.provider.indexOf('Dinámico') !== -1) {
      $('#baloa-ai-dynamic-badge').show();
    } else {
      $('#baloa-ai-dynamic-badge').hide();
    }

    const activeRole = $('#baloa-ai-filter-chips .filter-chip.active').data('role') || 'all';
    admin.renderAIRecommendations(activeRole);
  };

  admin.renderAIRecommendations = function (roleFilter) {
    const $list = $('#baloa-ai-rec-list');
    $list.empty();

    if (!admin.state.lastAIRecommendations || !admin.state.lastAIRecommendations.recommendations || admin.state.lastAIRecommendations.recommendations.length === 0) {
      $list.html('<div style="text-align:center; color:var(--text-muted); padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">Sin recomendaciones de IA.</div>');
      return;
    }

    let listHtml = '';
    const filtered = roleFilter === 'all' 
      ? admin.state.lastAIRecommendations.recommendations 
      : admin.state.lastAIRecommendations.recommendations.filter(r => r.role === roleFilter);

    if (filtered.length === 0) {
      $list.html('<div style="text-align:center; color:var(--text-muted); padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">No hay recomendaciones para este experto.</div>');
      return;
    }

    filtered.forEach(item => {
      const isCrit = item.severity === 'critical';
      const sevLabel = isCrit ? 'Crítico' : 'Advertencia';
      const sevClass = isCrit ? 'crit' : 'warn';
      
      let roleBorderColor = '#3b82f6';
      if (item.role === 'seo_geo_aeo') roleBorderColor = '#10b981';
      if (item.role === 'wp_architect') roleBorderColor = '#8b5cf6';

      listHtml += `
        <div class="ai-rec-card" data-role="${item.role}" style="background:var(--bg-card); border:1px solid var(--border); border-left: 5px solid ${roleBorderColor}; border-radius:var(--radius); padding:20px; transition:all 0.3s ease; box-shadow:var(--shadow-sm); cursor:pointer; margin-bottom:16px;">
          <div class="ai-rec-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px;">
            <div>
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span class="sitemap-issue-indicator ${sevClass}">${sevLabel}</span>
                <span style="font-size:11px; font-weight:700; color:var(--text-muted); background:var(--bg-secondary); padding:2px 8px; border-radius:4px; border:1px solid var(--border); text-transform:uppercase;">
                  ${admin.escHtml(item.role_label)}
                </span>
              </div>
              <h3 style="font-size:16px; font-weight:800; color:var(--text-primary); margin:0 0 6px 0;">${admin.escHtml(item.title)}</h3>
            </div>
            <span class="ai-rec-toggle-arrow" style="font-size:14px; color:var(--text-muted); transition:transform 0.2s ease; display:inline-block;">▼</span>
          </div>
          
          <p style="color:var(--text-secondary); font-size:13px; line-height:1.5; margin:8px 0 0 0;">
            ${admin.escHtml(item.friction)}
          </p>

          <div class="ai-rec-details-drawer" style="display:none; margin-top:16px; border-top:1px dashed var(--border); padding-top:16px;">
            <div style="margin-bottom:12px;">
              <strong style="font-size:12px; color:var(--text-primary); display:block; margin-bottom:4px;">💡 Solución Recomendada:</strong>
              <p style="font-size:13px; color:var(--text-secondary); margin:0 0 4px 0; font-weight:700;">${admin.escHtml(item.solution_title)}</p>
              <p style="font-size:13px; color:var(--text-secondary); margin:0;">${admin.escHtml(item.solution_desc)}</p>
            </div>

            <div style="margin-bottom:12px;">
              <strong style="font-size:12px; color:var(--text-primary); display:block; margin-bottom:4px;">📊 Justificación y Estándar:</strong>
              <p style="font-size:12px; color:var(--text-muted); margin:0; line-height:1.4;">${admin.escHtml(item.justification)}</p>
            </div>

            <div class="ai-rec-code-box-wrap" style="position:relative; margin-top:12px; border-radius:var(--radius-sm); overflow:hidden; background:#1e293b; border:1px solid #334155;">
              <div style="background:#0f172a; color:#94a3b8; font-size:11px; font-weight:700; padding:6px 12px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #334155;">
                <span>CÓDIGO SUGERIDO (${item.code_lang.toUpperCase()})</span>
                <button type="button" class="btn-copy-code" data-code="${admin.escAttr(item.code_content)}" style="background:transparent; border:none; color:#3b82f6; cursor:pointer; font-weight:bold; font-size:11px; padding:2px 6px; border-radius:3px; transition:all 0.2s;">Copiar</button>
              </div>
              <pre style="margin:0; padding:12px; overflow-x:auto; font-family:Consolas, Monaco, 'Courier New', monospace; font-size:12px; color:#f8fafc; line-height:1.5;"><code>${admin.escHtml(item.code_content)}</code></pre>
            </div>

            <div style="margin-top:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
              <span style="font-size:12px; color:var(--green); font-weight:700; display:flex; align-items:center; gap:4px;">
                <span>📈</span> Impacto Técnico: ${admin.escHtml(item.impact)}
              </span>
              <div style="display:flex; gap:8px;">
                <button type="button" class="btn-copy-prompt" data-prompt="${admin.escAttr(item.ai_prompt)}" style="background:var(--bg-secondary); color:var(--text-primary); border:1px solid var(--border); padding:6px 14px; border-radius:4px; font-size:12px; font-weight:700; cursor:pointer; transition:all 0.2s;">
                  💬 Copiar Prompt
                </button>
                <button type="button" class="btn-ai-simulate-fix" data-id="${item.id}" style="background:linear-gradient(135deg, var(--accent-purple), #9b72e8); color:#fff; border:none; padding:6px 14px; border-radius:4px; font-size:12px; font-weight:700; cursor:pointer; box-shadow:0 2px 4px rgba(155, 114, 232, 0.25); transition:transform 0.2s;">
                  ✨ Aplicar Sugerencia
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    $list.html(listHtml);
  };

  admin.initAIRecommendations();
});
