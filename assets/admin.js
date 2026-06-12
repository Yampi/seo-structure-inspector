/* global BALOA, jQuery */
jQuery(function ($) {

  const DONUT_R        = 45;
  const DONUT_CIRC     = 2 * Math.PI * DONUT_R;
  const RADAR_AXES     = [
    { key: 'html',       x: 100, y: 20 },
    { key: 'llms',       x: 164, y: 57 },
    { key: 'aeo',        x: 164, y: 143 },
    { key: 'schema',     x: 100, y: 180 },
    { key: 'cwv',        x: 36,  y: 143 },
    { key: 'keyword',    x: 36,  y: 57 },
  ];
  const MODULE_KEYS    = [ 'html', 'keyword', 'schema', 'readability', 'metatags', 'llms', 'aeo', 'cwv', 'links', 'images', 'geo' ];
  const moduleNames = {
    'html': 'SEO Estructural',
    'keyword': 'Keyword Scoring',
    'schema': 'Schema',
    'readability': 'Legibilidad',
    'metatags': 'Metatags',
    'llms': 'GEO / LLMs',
    'geo': 'GEO / LLMs',
    'aeo': 'AEO / Contenido',
    'cwv': 'Core Web Vitals',
    'links': 'Enlaces',
    'images': 'Imágenes'
  };

  // Define global namespace
  window.BALOA_Admin = window.BALOA_Admin || {};
  const admin = window.BALOA_Admin;

  // Initialize shared state
  admin.DONUT_CIRC = DONUT_CIRC;
  admin.RADAR_AXES = RADAR_AXES;
  admin.MODULE_KEYS = MODULE_KEYS;
  admin.moduleNames = moduleNames;

  admin.state = {
    lastDashboardResult: null,
    globalProblems: [],
    globalCorrectos: [],
    activeModule: 'resumen',
    activeSeverity: 'all',
    activeSearch: '',
    wpActiveTab: 'posts',
    wpPosts: [],
    wpPages: [],
    currentScanScope: 'single',
    sitemapState: {
      sitemap_url: '',
      urls: []
    },
    lastAIRecommendations: null,
    wpIsLocal: false
  };

  // Base Helper Utilities
  admin.escHtml = function (str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  };

  admin.escAttr = function (str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  admin.hexToRgba = function (hex, alpha) {
    const h = hex.replace('#', '');
    const full = h.length === 3 ? h.split('').map(function (c) { return c + c; }).join('') : h;
    const n = parseInt(full, 16);
    const r = (n >> 16) & 255;
    const g = (n >> 8) & 255;
    const b = n & 255;
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  };

  admin.scoreTier = function (score) {
    const s = Number(score);
    if ( Number.isNaN(s) ) {
      return { label: '', tier: '', color: '#8892a4' };
    }
    if ( s >= 80 ) return { label: 'Excelente', tier: 'tier-good', color: '#22c55e' };
    if ( s >= 60 ) return { label: 'Bueno', tier: 'tier-mid', color: '#f59e0b' };
    if ( s >= 40 ) return { label: 'Mejorable', tier: 'tier-low', color: '#f59e0b' };
    return { label: 'Crítico', tier: 'tier-bad', color: '#ef4444' };
  };

  admin.badgeClass = function (score) {
    const s = Number(score);
    if ( Number.isNaN(s) ) return 'badge-orange';
    if ( s >= 80 ) return 'badge-green';
    if ( s >= 60 ) return 'badge-orange';
    return 'badge-red';
  };

  admin.moduleScore = function (mod) {
    if ( ! mod || mod.skipped || mod.error ) return null;
    if ( typeof mod.score === 'number' ) return mod.score;
    return null;
  };

  admin.getHeuristics = function (check, modKey, severity) {
    let t = severity === 'critical' ? 10 : (severity === 'warning' ? 5 : 2);
    let seoImpact = 'Medio';
    let aiImpact = 'Media';
    
    if (modKey === 'html' || modKey === 'cwv') {
      seoImpact = severity === 'critical' ? 'Alto' : 'Medio';
      aiImpact = 'Baja';
    } else if (modKey === 'llms' || modKey === 'aeo' || modKey === 'schema') {
      seoImpact = 'Medio';
      aiImpact = severity === 'critical' ? 'Alta' : 'Media';
    } else {
      seoImpact = 'Bajo';
      aiImpact = 'Baja';
    }
    
    return { tiempo_min: t, seo_impact: seoImpact, ai_impact: aiImpact };
  };

  // Theme selector controller
  function initGlobalThemeSystem() {
    const $root = $('.seoi-dashboard-root');
    if (!$root.length) return;

    const $themeSelect = $('select[name="baloa_structure_auditor_seo_options[ui_theme]"]');

    const currentTheme = $root.attr('data-theme') || 'dark';
    localStorage.setItem('baloa_structure_auditor_seo_theme', currentTheme);

    if ($themeSelect.length) {
      $themeSelect.on('change', function () {
        const selectedTheme = $(this).val();
        $root.attr('data-theme', selectedTheme);
        localStorage.setItem('baloa_structure_auditor_seo_theme', selectedTheme);
      });
    }
  }

  function restoreLastAnalysis() {
    try {
      const lastUrl = localStorage.getItem('baloa_structure_auditor_seo_last_url');
      const lastResultStr = localStorage.getItem('baloa_structure_auditor_seo_last_result');

      if (lastUrl) {
        $('#baloa-url-input').val(lastUrl);
      }

      if (lastResultStr && typeof admin.renderDashboardResults === 'function') {
        const lastResult = JSON.parse(lastResultStr);
        if (lastResult) {
          admin.renderDashboardResults(lastResult);
          $('#baloa-last-analyzed').text('Cargado desde el último análisis');
          $('#baloa-export-btn').prop('disabled', false);
        }
      }
    } catch (e) {
      console.error('[BALOA] Error al restaurar el último análisis:', e);
    }
  }

  function initScanScopeDropdown() {
    const $trigger = $('#baloa-scope-btn');
    const $menu    = $('#baloa-scope-menu');
    const $banner  = $('#baloa-batch-warning-banner');

    if ( ! $trigger.length ) return;

    $trigger.on('click', function (e) {
      e.stopPropagation();
      const isOpen = $menu.is(':visible');
      if (isOpen) {
        $menu.hide();
        $trigger.attr('aria-expanded', 'false');
      } else {
        $menu.fadeIn(100);
        $trigger.attr('aria-expanded', 'true');
      }
    });

    $(document).on('click', function () {
      $menu.hide();
      $trigger.attr('aria-expanded', 'false');
    });

    $menu.on('click', '.scope-item', function (e) {
      e.preventDefault();
      const scope = $(this).data('scope');
      const icon  = $(this).data('icon');
      const label = $(this).find('strong').text();

      $('.scope-item').removeClass('active');
      $(this).addClass('active');

      $trigger.find('.scope-icon').text(icon);
      $trigger.find('.scope-text').text(label);

      admin.state.currentScanScope = scope;

      if (scope !== 'single') {
        $banner.slideDown('fast');
        
        const currentUrl = $('#baloa-url-input').val().trim();
        if (!currentUrl) {
          if (scope === 'sitemap') {
            $('#baloa-url-input').val(BALOA.home_url ? BALOA.home_url.replace(/\/$/, '') + '/wp-sitemap.xml' : '');
          } else if (scope === 'posts' || scope === 'pages') {
            $('#baloa-url-input').val(BALOA.home_url || '');
          }
        }
      } else {
        $banner.slideUp('fast');
      }

      $menu.hide();
      $trigger.attr('aria-expanded', 'false');
    });
  }

  // Dashboard Page Core Handlers
  const $analyzeBtn = $('#baloa-analyze-btn-dash');
  const $urlInput   = $('#baloa-url-input');

  $urlInput.on('keypress', function (e) {
    if (e.which === 13) {
      e.preventDefault();
      $analyzeBtn.click();
    }
  });

  if ( $analyzeBtn.length ) {
    $('.nav-item[data-module]').on('click', function (e) {
      e.preventDefault();
      const mod = $(this).data('module');

      if ( mod === 'sitemap' ) {
        $('.nav-item').removeClass('active');
        $(this).addClass('active');
        $('#view-resumen').hide();
        $('#view-recommendations').hide();
        $('#view-sitemap').fadeIn('fast');
        return;
      }

      if ( mod === 'resumen' ) {
        $('.nav-item').removeClass('active');
        $(this).addClass('active');
        $('#view-sitemap').hide();
        $('#view-recommendations').hide();
        $('#view-resumen').show();
        admin.state.activeModule = mod;
        $('#baloa-prob-section-title').text('Problemas por Prioridad: Resumen General');
        if (typeof admin.applyProblemsFilters === 'function') admin.applyProblemsFilters();
        return;
      }

      if ( mod === 'recommendations' ) {
        $('.nav-item').removeClass('active');
        $(this).addClass('active');
        $('#view-sitemap').hide();
        $('#view-resumen').hide();
        $('#view-recommendations').fadeIn('fast');
        admin.state.activeModule = mod;
        if (typeof admin.loadAIRecommendations === 'function') admin.loadAIRecommendations();
        return;
      }

      if ( ! admin.state.lastDashboardResult ) return;

      $('.nav-item').removeClass('active');
      $(this).addClass('active');
      $('#view-sitemap').hide();
      $('#view-recommendations').hide();
      $('#view-resumen').show();

      admin.state.activeModule = mod;
      $('#baloa-prob-section-title').text('Problemas por Prioridad: ' + (moduleNames[mod] || mod));
      if (typeof admin.applyProblemsFilters === 'function') admin.applyProblemsFilters();
    });

    $analyzeBtn.on('click', function (e) {
      e.preventDefault();
      const url = $urlInput.val().trim();
      if ( ! url && admin.state.currentScanScope !== 'posts' && admin.state.currentScanScope !== 'pages' ) return;

      if (admin.state.currentScanScope === 'single') {
        $analyzeBtn.prop('disabled', true).text('⏳');
        $('#baloa-last-analyzed').text('Analizando...');
        if (typeof admin.showMainUIAnalysisLoading === 'function') admin.showMainUIAnalysisLoading();

        $.ajax({
          url:    BALOA.ajax_url,
          method: 'POST',
          data: {
            action:  'baloa_structure_auditor_seo_analyze',
            nonce:   BALOA.nonce,
            url:     url,
            keyword: '',
          },
          success: function (res) {
            if ( ! res.success ) {
              if (typeof admin.showDashboardError === 'function') admin.showDashboardError(res.data?.message ?? 'Error desconocido');
              return;
            }
            if (typeof admin.renderDashboardResults === 'function') admin.renderDashboardResults(res.data);

            try {
              localStorage.setItem('baloa_structure_auditor_seo_last_result', JSON.stringify(res.data));
              localStorage.setItem('baloa_structure_auditor_seo_last_url', url);
              localStorage.setItem('baloa_structure_auditor_seo_last_kwd', '');
              
              const cacheKey = 'baloa_structure_auditor_seo_ai_cache_' + encodeURIComponent(url);
              localStorage.removeItem(cacheKey);
            } catch(e) {
              console.error('[BALOA] Error al guardar en localStorage:', e);
            }
          },
          error: function () {
            if (typeof admin.showDashboardError === 'function') admin.showDashboardError('Error de conexión.');
          },
          complete: function () {
            $analyzeBtn.prop('disabled', false).text('🔍');
            $('#baloa-export-btn').prop('disabled', false);
          },
        });
        return;
      }

      // Batch scopes
      $analyzeBtn.prop('disabled', true).text('⏳');
      $('#baloa-last-analyzed').text('Descubriendo recursos...');

      $('.nav-item').removeClass('active');
      $('.nav-item[data-module="sitemap"]').addClass('active');
      $('#view-resumen').hide();
      $('#view-sitemap').fadeIn('fast');

      if (admin.state.currentScanScope === 'sitemap') {
        $('#baloa-sitemap-url-input').val(url);
      }

      $('#baloa-sitemap-scan-status').text('⏳ Descubriendo páginas de la opción seleccionada...').fadeIn('fast');

      $.ajax({
        url: BALOA.ajax_url,
        method: 'POST',
        data: {
          action: 'baloa_structure_auditor_seo_discover_resources',
          nonce: BALOA.nonce,
          scope: admin.state.currentScanScope,
          url: url
        },
        success: function(res) {
          if (res.success && res.data && Array.isArray(res.data.urls)) {
            admin.state.sitemapState.sitemap_url = (admin.state.currentScanScope === 'sitemap') ? url : BALOA.home_url;
            admin.state.sitemapState.urls = res.data.urls.map(item => ({
              url: item.url,
              lastmod: item.lastmod || '—',
              score: null,
              issues: null
            }));
            
            if (typeof admin.saveSitemapState === 'function') admin.saveSitemapState();
            if (typeof admin.renderSitemapTable === 'function') admin.renderSitemapTable();

            $('#baloa-sitemap-scan-status').text('✓ Páginas descubiertas con éxito.').fadeOut(3000);

            setTimeout(function() {
              const selectedIndices = admin.state.sitemapState.urls.map((_, i) => i);
              if (selectedIndices.length > 0 && typeof admin.analyzeUrlsInBatch === 'function') {
                admin.analyzeUrlsInBatch(selectedIndices, '');
              }
            }, 1500);
          } else {
            $('#baloa-sitemap-scan-status').html('<span style="color:var(--red)">✗ ' + admin.escHtml(res.data?.message || 'No se pudieron descubrir recursos.') + '</span>');
          }
        },
        error: function() {
          $('#baloa-sitemap-scan-status').html('<span style="color:var(--red)">✗ Error de conexión al descubrir recursos.</span>');
        },
        complete: function() {
          $analyzeBtn.prop('disabled', false).text('🔍');
          $('.scope-item[data-scope="single"]').click();
        }
      });
    });

    $('#baloa-export-btn').on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if ($(this).prop('disabled')) return;
      $('#baloa-export-menu').fadeToggle('fast');
    });

    $(document).on('click', function(e) {
      if (!$(e.target).closest('.baloa-export-dropdown-container').length) {
        $('#baloa-export-menu').fadeOut('fast');
      }
    });

    $(document).on('click', '.baloa-dropdown-item', function (e) {
      e.preventDefault();
      const format = $(this).data('format');
      const url = $urlInput.val().trim();
      if (!url) return;

      const $menu = $('#baloa-export-menu');
      $menu.fadeOut('fast');

      const $btn = $('#baloa-export-btn');
      const originalText = $btn.find('span').first().text();
      $btn.prop('disabled', true).find('span').first().text('⏳...');

      $.ajax({
        url:    BALOA.ajax_url,
        method: 'POST',
        data: {
          action:  'baloa_structure_auditor_seo_export_report',
          nonce:   BALOA.nonce,
          url:     url,
          format:  format,
        },
        success: function (res) {
          if ( res.success && res.data?.html ) {
            const w = window.open('', '_blank');
            if ( w ) {
              w.document.write(res.data.html);
              w.document.close();
            } else {
              alert('Por favor, permite las ventanas emergentes (popups) para poder ver e imprimir tu reporte.');
            }
          } else {
            alert('Error al exportar reporte: ' + (res.data?.message || 'Desconocido'));
          }
        },
        error: function () {
          alert('Error de conexión al exportar el reporte.');
        },
        complete: function() {
          $btn.prop('disabled', false).find('span').first().text(originalText);
        }
      });
    });

    $(document).on('click', '#baloa-action-plan-btn', function(e) {
      e.preventDefault();
      const $btn = $(this);

      if (!admin.state.globalProblems || admin.state.globalProblems.length === 0) {
        alert('Primero debes analizar una URL para ver los problemas.');
        return;
      }

      $btn.prop('disabled', true).text('Generando...');
      
      const filteredProblems = admin.state.globalProblems.filter(function(p) { return p.severity === 'critical' || p.severity === 'warning'; });
      const analyzedUrl = $('#baloa-url-input').val().trim() || BALOA.post_url || '';

      $.ajax({
        url: BALOA.ajax_url,
        method: 'POST',
        data: {
          action: 'baloa_structure_auditor_seo_generate_action_plan',
          nonce: BALOA.nonce,
          url: analyzedUrl,
          problems: JSON.stringify(filteredProblems)
        },
        success: function(res) {
          if (res.success && res.data?.html) {
            const w = window.open('', '_blank');
            if (w) {
              w.document.write(res.data.html);
              w.document.close();
            } else {
              alert('Por favor, permite las ventanas emergentes (popups) para ver el informe.');
            }
          } else {
            alert('Error al generar el plan: ' + (res.data?.message || 'Desconocido'));
          }
        },
        error: function() {
          alert('Error de conexión al generar el plan de acción.');
        },
        complete: function() {
          $btn.prop('disabled', false).text('Ver plan de acción');
        }
      });
    });
  }

  // Meta Box page analyzer button
  if ( $('#baloa-analyze-btn').length ) {
    $('#baloa-analyze-btn').on('click', function (e) {
      e.preventDefault();

      const $btn    = $(this);
      const keyword = $('#baloa-keyword-input').val().trim();
      const url     = BALOA.post_url || '';

      if ( ! url ) return;

      $btn.prop('disabled', true).text('Analizando...');

      $.ajax({
        url:    BALOA.ajax_url,
        method: 'POST',
        data: {
          action:   'baloa_structure_auditor_seo_analyze',
          nonce:    BALOA.nonce,
          url:      url,
          keyword:  keyword,
          post_id:  BALOA.post_id || 0,
        },
        success: function (res) {
          if ( res.success ) {
            const score = res.data.global_score ?? '—';
            $('#baloa-metabox-result')
              .html('<p style="color:#22c55e">✓ Análisis completado. Score: ' + admin.escHtml(String(score)) + '</p>')
              .show();
          } else {
            $('#baloa-metabox-result')
              .html('<p style="color:#ef4444">✗ ' + admin.escHtml(res.data?.message ?? 'Error') + '</p>')
              .show();
          }
        },
        error: function () {
          $('#baloa-metabox-result')
            .html('<p style="color:#ef4444">✗ Error de conexión.</p>')
            .show();
        },
        complete: function () {
          $btn.prop('disabled', false).text('Analizar');
        },
      });
    });
  }

  restoreLastAnalysis();
  initGlobalThemeSystem();
  initScanScopeDropdown();
});
