/* global BALOA, jQuery */
jQuery(function ($) {
  if (typeof window.BALOA_Admin === 'undefined') return;

  const admin = window.BALOA_Admin;

  admin.applyProblemsFilters = function () {
    if ( typeof BALOA !== 'undefined' && ! BALOA.is_premium && ['llms', 'aeo', 'cwv'].includes(admin.state.activeModule) ) {
      $('#baloa-problems-list').html('<div style="color:var(--text-muted); padding:40px; text-align:center;">Módulo no disponible.</div>');
      $('#baloa-qw-list').html('');
      $('#baloa-ok-list').html('');
      $('#btn-qw-apply').prop('disabled', true);
      $('#btn-autofix-all-problems').hide();
      admin.updateFilterChipCounts();
      return;
    }

    let filtered = admin.state.globalProblems;

    if ( admin.state.activeModule !== 'resumen' ) {
      if ( admin.state.activeModule === 'llms' ) {
        filtered = filtered.filter(p => p.module === 'llms' || p.module === 'geo');
      } else {
        filtered = filtered.filter(p => p.module === admin.state.activeModule);
      }
    }

    if ( admin.state.activeSeverity !== 'all' ) {
      filtered = filtered.filter(p => p.severity === admin.state.activeSeverity);
    }

    if ( admin.state.activeSearch ) {
      filtered = filtered.filter(p => {
        const title = (p.title || '').toLowerCase();
        const desc = (p.recommendation || p.why || '').toLowerCase();
        const modName = (admin.moduleNames[p.module] || p.module || '').toLowerCase();
        return title.includes(admin.state.activeSearch) || desc.includes(admin.state.activeSearch) || modName.includes(admin.state.activeSearch);
      });
    }

    admin.renderProblemsAccordion(filtered);
    admin.updateFilterChipCounts();
    if ( typeof admin.updateSocialPreview === 'function' ) {
      admin.updateSocialPreview();
    }
  };

  admin.updateFilterChipCounts = function () {
    let baseList = admin.state.globalProblems;
    if ( admin.state.activeModule !== 'resumen' ) {
      if ( admin.state.activeModule === 'llms' ) {
        baseList = baseList.filter(p => p.module === 'llms' || p.module === 'geo');
      } else {
        baseList = baseList.filter(p => p.module === admin.state.activeModule);
      }
    }

    if ( admin.state.activeSearch ) {
      baseList = baseList.filter(p => {
        const title = (p.title || '').toLowerCase();
        const desc = (p.recommendation || p.why || '').toLowerCase();
        const modName = (admin.moduleNames[p.module] || p.module || '').toLowerCase();
        return title.includes(admin.state.activeSearch) || desc.includes(admin.state.activeSearch) || modName.includes(admin.state.activeSearch);
      });
    }

    const allCount = baseList.length;
    const critCount = baseList.filter(p => p.severity === 'critical').length;
    const warnCount = baseList.filter(p => p.severity === 'warning').length;

    $('#chip-count-all').text(allCount);
    $('#chip-count-crit').text(critCount);
    $('#chip-count-warn').text(warnCount);
  };

  admin.renderProblemsAccordion = function (problemsList) {
    if ( problemsList.length === 0 ) {
      $('#baloa-problems-list').html(
        '<div style="text-align:center;color:var(--green);padding:40px; background:var(--bg-card); border-radius:12px; border:1px solid var(--border);">🚀 ¡Felicidades! Tu estructura es perfecta y no hay problemas.</div>'
      );
      return;
    }

    const html = problemsList.map(c => {
      const isCrit = c.severity === 'critical';
      const icon = isCrit ? '&lt;/&gt;' : '⚠'; 
      const wrapperCls = isCrit ? 'prob-card-crit' : 'prob-card-warn';
      const label = isCrit ? 'Crítico' : 'Advertencia';
      const title = admin.escHtml(c.title ?? c.id ?? '—');
      const desc = admin.escHtml(c.recommendation || c.why || 'Se detectó un problema en este check.');
      
      const heur = admin.getHeuristics(c, c.module, c.severity);
      const sColor = heur.seo_impact === 'Alto' ? 'red' : 'orange';
      const aColor = heur.ai_impact === 'Alta' ? 'red' : (heur.ai_impact === 'Media' ? 'orange' : 'green');
      
      const analyzedUrl = ($('#baloa-url-input').val() || BALOA.post_url || '').replace(/\/$/, '');
      const isLocalSite = analyzedUrl === BALOA.home_url || analyzedUrl.indexOf(BALOA.home_url + '/') === 0;

      return '<div class="problem-card ' + wrapperCls + '">' +
               '<div class="prob-left-col">' +
                 '<div class="prob-icon-box ' + (isCrit ? 'crit' : 'warn') + '">' + icon + '</div>' +
                 '<div class="prob-label-badge ' + (isCrit ? 'crit' : 'warn') + '">' + label + '</div>' +
               '</div>' +
               '<div class="prob-content">' +
                 '<div class="prob-title">' + title + '</div>' +
                 '<div class="prob-desc">' + desc + '</div>' +
                 (c.context && c.context.allow_directives ? '<div class="prob-directives-box" style="margin-top:10px;"><label style="display:block;font-size:12px;font-weight:700;color:var(--text-secondary);margin-bottom:6px;">📋 Directivas Allow para copiar en robots.txt:</label><textarea readonly style="width:100%;height:140px;padding:10px;font-family:monospace;font-size:12px;background:var(--bg-secondary);color:var(--text-primary);border:1px solid var(--border);border-radius:6px;resize:vertical;" onclick="this.select()">' + admin.escHtml(c.context.allow_directives) + '</textarea><div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Haz clic en el textarea para seleccionar todo el contenido y luego Ctrl+C para copiar.</div></div>' : '') +
                 '<div class="prob-badges">' +
                   '<span class="prob-badge">SEO Impacto: <strong class="' + sColor + '">' + heur.seo_impact + '</strong></span>' +
                   '<span class="prob-badge">IA Visibilidad: <strong class="' + aColor + '">' + heur.ai_impact + '</strong></span>' +
                   '<span class="prob-badge" style="color:var(--text-muted)">⏱ Tiempo: ' + heur.tiempo_min + ' min</span>' +
                 '</div>' +
               '</div>' +
               '<div class="prob-divider"></div>' +
               '<div class="prob-right">' +
                 '<div class="why-box">' +
                   '<div class="why-box-title">¿Por qué es importante?</div>' +
                   '<div class="why-box-text">' + admin.escHtml(c.why || 'Mejora la experiencia y lectura de bots.') + '</div>' +
                   '<button type="button" class="btn-sol" data-id="' + c.id + '" data-module="' + c.module + '" data-desc="' + admin.escHtml(c.recommendation || c.why || '') + '">Ver solución</button>' +
                   '<a href="admin.php?page=baloa-glossary#term-' + c.id + '" class="btn-sol btn-glossary-link" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 4px;">Ver en Glosario 📖</a>' +
                   (isLocalSite && c.supports_autofix === true && typeof BALOA !== 'undefined' && BALOA.is_premium ? '<button type="button" class="btn-autofix" data-id="' + c.id + '" data-module="' + c.module + '" data-url="' + analyzedUrl + '">Auto-fix</button>' : '') +
                 '</div>' +
               '</div>' +
             '</div>';
    });
    $('#baloa-problems-list').html(html.join(''));

    const fixableCount = typeof BALOA !== 'undefined' && BALOA.is_premium ? problemsList.filter(p => p.supports_autofix === true && p.id !== 'llms_txt_present' && p.id !== 'llms_full_txt_present').length : 0;
    const $btnAll = $('#btn-autofix-all-problems');
    if ($btnAll.length) {
      if (fixableCount > 0) {
        $btnAll.text('✨ Aplicar todos los Auto-fixes (' + fixableCount + ')').show();
      } else {
        $btnAll.hide();
      }
    }
  };

  admin.renderQuickWinsAndCorrectos = function (problems, correctos) {
    const qws = problems.slice(0, 3);
    $('#baloa-qw-count').text(qws.length);
    if(qws.length === 0) {
      $('#baloa-qw-list').html('<div style="color:var(--text-muted); font-size:13px">Excelente, no hay arreglos pendientes.</div>');
      $('#btn-qw-apply').prop('disabled', true);
    } else {
      const qwHtml = qws.map(q => {
        return '<div class="qw-item"><span class="qw-check">◎</span> ' + admin.escHtml(q.title || 'Check pendiente') + ' <span class="qw-time">' + admin.getHeuristics(q, q.module, q.severity).tiempo_min + ' min</span></div>';
      });
      $('#baloa-qw-list').html(qwHtml.join(''));
      $('#btn-qw-apply').prop('disabled', false);
    }

    $('#baloa-ok-count').text(correctos.length);
    if(correctos.length === 0) {
      $('#baloa-ok-list').html('<div style="color:var(--text-muted); font-size:13px">Aún no hay checks correctos.</div>');
    } else {
      const okHtml = correctos.slice(0, 5).map(ok => {
        return '<div class="ok-item"><div class="ok-item-left"><span class="ok-icon">◎</span> ' + admin.escHtml(ok.title || 'Check correcto') + '</div> <span style="transform:rotate(-90deg); color:var(--border)">⌄</span></div>';
      });
      let xtra = '';
      if (correctos.length > 5) xtra = '<div style="font-size:12px; color:var(--text-muted); margin-top:8px;">... y ' + (correctos.length - 5) + ' más</div>' +
                                       '<button type="button" class="btn-action-light" style="margin-top:12px; font-weight:500;">Ver todos los correctos</button>';
      $('#baloa-ok-list').html(okHtml.join('') + xtra);
    }
  };

  // Bind UI Filter Events
  $(document).on('click', '.filter-chip', function(e) {
    e.preventDefault();
    $('.filter-chip').removeClass('active');
    $(this).addClass('active');
    admin.state.activeSeverity = $(this).data('severity');
    admin.applyProblemsFilters();
  });

  $(document).on('input keyup', '#baloa-problems-search', function() {
    admin.state.activeSearch = $(this).val().toLowerCase().trim();
    if ( admin.state.activeSearch ) {
      $('#baloa-search-clear').show();
    } else {
      $('#baloa-search-clear').hide();
    }
    admin.applyProblemsFilters();
  });

  $(document).on('click', '#baloa-search-clear', function(e) {
    e.preventDefault();
    $('#baloa-problems-search').val('');
    $(this).hide();
    admin.state.activeSearch = '';
    admin.applyProblemsFilters();
  });

  $(document).on('click', '#btn-autofix-all-problems', function(e) {
    e.preventDefault();
    const $btn = $(this);
    
    const fixableProblems = admin.state.globalProblems.filter(p => p.supports_autofix === true && p.id !== 'llms_txt_present' && p.id !== 'llms_full_txt_present');
    if (fixableProblems.length === 0) return;

    $btn.prop('disabled', true).css('opacity', '0.7');

    const analyzedUrl = ($('#baloa-url-input').val() || BALOA.post_url || '').replace(/\/$/, '');
    
    const fixesData = fixableProblems.map(p => ({
      id: p.id,
      module: p.module,
      url: analyzedUrl
    }));

    if (typeof admin.executeSequentialAutofixes === 'function') {
      admin.executeSequentialAutofixes(
        fixesData,
        function(current, total) {
          $btn.text('⏳ Aplicando (' + current + '/' + total + ')...');
        },
        function(result) {
          $btn.text('✨ Aplicar todos los Auto-fixes').css('opacity', '1').prop('disabled', false).hide();
          $('#baloa-analyze-btn-dash').click();
        }
      );
    }
  });

  $(document).on('click', '#btn-qw-apply', function(e) {
    e.preventDefault();
    const $btn = $(this);
    
    const isWp = admin.state.lastDashboardResult && admin.state.lastDashboardResult.wordpress_data && admin.state.lastDashboardResult.wordpress_data.is_wordpress;
    
    if (isWp) {
      const items = admin.state.wpActiveTab === 'posts' ? admin.state.wpPosts : admin.state.wpPages;
      const allFixes = [];

      items.forEach(item => {
        const problems = item.problems || [];
        problems.forEach(p => {
          if (p.supports_autofix === true && p.id !== 'llms_txt_present' && p.id !== 'llms_full_txt_present') {
            allFixes.push({
              id: p.id,
              module: p.module,
              url: item.url,
              postTitle: item.title
            });
          }
        });
      });

      if (allFixes.length === 0) {
        alert('No hay sugerencias automáticas para aplicar en este lote.');
        return;
      }

      $btn.prop('disabled', true).css('opacity', '0.7');

      if (typeof admin.executeSequentialAutofixes === 'function') {
        admin.executeSequentialAutofixes(
          allFixes,
          function(current, total, currentFix) {
            $btn.text('⏳ Aplicando (' + current + '/' + total + ') - ' + (currentFix.postTitle || ''));
          },
          function(result) {
            $btn.text('🌟 Aplicar todas las sugerencias').css('opacity', '1').prop('disabled', false);
            
            items.forEach(item => {
              const cacheKey = 'baloa_structure_auditor_seo_wp_cache_' + encodeURIComponent(item.url);
              localStorage.removeItem(cacheKey);
              item.score = null;
              item.problems = null;
              item.results = null;
            });

            if (typeof admin.renderWpPostsList === 'function') admin.renderWpPostsList(admin.state.wpIsLocal);
            if (typeof admin.updateWpSummaryKpi === 'function') admin.updateWpSummaryKpi();
            if (typeof admin.lazyAnalyzeWpContent === 'function') admin.lazyAnalyzeWpContent(admin.state.wpIsLocal);
          }
        );
      }
    } else {
      if (!admin.state.globalProblems || admin.state.globalProblems.length === 0) return;
      
      const qws = admin.state.globalProblems.slice(0, 3);
      const fixableQws = qws.filter(q => q.supports_autofix === true && q.id !== 'llms_txt_present' && q.id !== 'llms_full_txt_present');
      
      if (fixableQws.length === 0) {
        alert('Las sugerencias sugeridas actualmente requieren acción manual (puedes hacer click en "Auto-fix" en cada problema para ver los detalles).');
        return;
      }

      $btn.prop('disabled', true).css('opacity', '0.7');

      const analyzedUrl = ($('#baloa-url-input').val() || BALOA.post_url || '').replace(/\/$/, '');
      
      const fixesData = fixableQws.map(q => ({
        id: q.id,
        module: q.module,
        url: analyzedUrl
      }));

      if (typeof admin.executeSequentialAutofixes === 'function') {
        admin.executeSequentialAutofixes(
          fixesData,
          function(current, total) {
            $btn.text('⏳ Aplicando (' + current + '/' + total + ')...');
          },
          function(result) {
            $btn.text('🌟 Aplicar todas las sugerencias').css('opacity', '1').prop('disabled', false);
            $('#baloa-analyze-btn-dash').click();
          }
        );
      }
    }
  });
});
