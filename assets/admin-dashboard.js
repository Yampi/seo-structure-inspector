/* global BALOA, jQuery */
jQuery(function ($) {
  if (typeof window.BALOA_Admin === 'undefined') return;

  const admin = window.BALOA_Admin;
  let kpiCardsOriginalHtml = {};

  admin.updateDonut = function (score) {
    const tier   = admin.scoreTier(score);
    const offset = admin.DONUT_CIRC - ( Math.max(0, Math.min(100, score)) / 100 ) * admin.DONUT_CIRC;
    $('.donut-fill').css({
      strokeDasharray:  admin.DONUT_CIRC,
      strokeDashoffset: offset,
      stroke:           tier.color,
    });
    $('#baloa-global-status').text(tier.label).attr('class', 'donut-status ' + tier.tier).css('color', tier.color);
  };

  admin.updateRadar = function (data) {
    const cx = 100;
    const cy = 100;
    const pts = admin.RADAR_AXES.map(function (axis) {
      const mod   = data[axis.key];
      const score = admin.moduleScore(mod);
      const t     = score === null ? 0 : Math.max(0, Math.min(100, score)) / 100;
      const x     = cx + (axis.x - cx) * t;
      const y     = cy + (axis.y - cy) * t;
      return x.toFixed(1) + ',' + y.toFixed(1);
    });
    const $poly = $('#baloa-radar-data');
    const avg   = admin.scoreTier(data.global_score ?? 0);
    $poly.attr('points', pts.join(' '));
    $poly.css({ stroke: avg.color, fill: admin.hexToRgba(avg.color, 0.15) });
  };

  admin.calculateCombinedAiScore = function (data) {
    if (!data) return null;
    const llmsScore = admin.moduleScore(data.llms);
    const geoScore = admin.moduleScore(data.geo);
    const aeoScore = admin.moduleScore(data.aeo);

    let sum = 0;
    let weight = 0;

    if ( llmsScore !== null ) {
      sum += llmsScore * 0.25;
      weight += 0.25;
    }
    if ( geoScore !== null ) {
      sum += geoScore * 0.25;
      weight += 0.25;
    }
    if ( aeoScore !== null ) {
      sum += aeoScore * 0.50;
      weight += 0.50;
    }

    if ( weight > 0 ) {
      return Math.round( sum / weight );
    }
    return null;
  };

  admin.updateSidebarBadges = function (data) {
    $('[data-score-for]').each(function () {
      const key   = $(this).data('score-for');
      let score = null;
      if ( key === 'llms' ) {
        score = admin.calculateCombinedAiScore(data);
      } else {
        const mod = data[key];
        score = admin.moduleScore(mod);
      }
      
      if ( score === null ) {
        if ( key === 'keyword' ) {
          $(this).text('N/A')
                 .attr('class', 'score-badge badge-orange')
                 .attr('title', 'Ingresa una palabra clave para analizar el scoring de keyword');
        } else {
          $(this).text('—').attr('class', 'score-badge badge-orange').removeAttr('title');
        }
        return;
      }
      $(this).text(score).attr('class', 'score-badge ' + admin.badgeClass(score)).removeAttr('title');
    });
  };
 
  admin.updateAiCard = function (data) {
    const aiScore = admin.calculateCombinedAiScore(data);
    const tier    = admin.scoreTier(aiScore ?? 0);
    $('#baloa-ai-score').text(aiScore === null ? '—' : aiScore);
    $('#baloa-ai-label').text(aiScore === null ? '' : tier.label).attr('class', 'ai-score-label ' + tier.tier);
    if ( aiScore !== null && aiScore < 40 ) {
      $('#baloa-ai-desc').text('La visibilidad para IA es baja. Revisa llms.txt, estructura de respuestas y políticas en robots.txt.');
    } else if ( aiScore !== null && aiScore < 60 ) {
      $('#baloa-ai-desc').text('Hay margen de mejora en GEO y señales para crawlers de IA.');
    } else {
      $('#baloa-ai-desc').text('Tu contenido tiene buena visibilidad para sistemas de IA, pero puedes seguir optimizando.');
    }
  };

  admin.updateRecommendation = function (problems) {
    if ( ! problems.length ) {
      $('#baloa-rec-title').text('Sin problemas críticos');
      $('#baloa-rec-desc').text('Mantén el monitoreo periódico y revisa advertencias menores.');
      $('#baloa-rec-btn').prop('disabled', true);
      return;
    }
    const top = problems[0];
    $('#baloa-rec-title').text(top.title || top.id || 'Mejora detectada');
    $('#baloa-rec-desc').html(admin.escHtml(top.recommendation || top.why || 'Revisa este punto en el módulo ' + (top.module || '') + '.'));
    $('#baloa-rec-btn').prop('disabled', false);
  };

  admin.cacheKpiCardsHtml = function () {
    if (Object.keys(kpiCardsOriginalHtml).length > 0) return;
    
    $('.dash-kpis .kpi-card').each(function(index) {
      const $card = $(this);
      const cardId = $card.attr('id') || 'kpi-card-index-' + index;
      kpiCardsOriginalHtml[cardId] = $card.html();
    });
  };

  admin.restoreKpiCardsHtml = function () {
    if (Object.keys(kpiCardsOriginalHtml).length === 0) return;
    
    $('.dash-kpis .kpi-card').each(function(index) {
      const $card = $(this);
      const cardId = $card.attr('id') || 'kpi-card-index-' + index;
      if (kpiCardsOriginalHtml[cardId]) {
        $card.html(kpiCardsOriginalHtml[cardId]);
      }
    });
  };

  admin.showMainUIAnalysisLoading = function () {
    admin.cacheKpiCardsHtml();

    $('.dash-kpis .kpi-card').each(function(index) {
      const $card = $(this);
      const cardId = $card.attr('id') || 'kpi-card-index-' + index;

      if (cardId.indexOf('kpi-card-index-0') !== -1) {
        $card.html(
          '<div class="kpi-title">SEO Score</div>' +
          '<div class="baloa-skeleton baloa-skeleton-circle" style="margin-top: 12px;"></div>' +
          '<div class="baloa-skeleton baloa-skeleton-text" style="width: 70px; margin: 16px auto 0; height: 14px;"></div>'
        );
      } else if (cardId.indexOf('kpi-card-index-1') !== -1) {
        $card.html(
          '<div class="kpi-title">Legibilidad</div>' +
          '<div class="baloa-skeleton baloa-skeleton-text" style="width: 50px; height: 32px; margin: 26px auto 26px;"></div>' +
          '<div class="baloa-skeleton baloa-skeleton-text" style="width: 70px; margin: 0 auto; height: 14px;"></div>'
        );
      } else if (cardId.indexOf('kpi-card-index-2') !== -1) {
        $card.html(
          '<div class="kpi-title">IA / Visibilidad</div>' +
          '<div class="baloa-skeleton baloa-skeleton-text" style="width: 50px; height: 32px; margin: 26px auto 26px;"></div>' +
          '<div class="baloa-skeleton baloa-skeleton-text" style="width: 70px; margin: 0 auto; height: 14px;"></div>'
        );
      } else if (cardId.indexOf('kpi-card-index-3') !== -1) {
        $card.html(
          '<div class="kpi-title" style="margin-bottom:0">Problemas encontrados</div>' +
          '<div style="display:flex; flex-direction:column; gap:16px; margin-top:20px; width:100%;">' +
            '<div style="display:flex; align-items:center; gap:8px;">' +
              '<div class="baloa-skeleton" style="width:16px; height:16px; border-radius:50%; flex-shrink: 0;"></div>' +
              '<div class="baloa-skeleton baloa-skeleton-text" style="width:70%; height:12px; margin:0;"></div>' +
            '</div>' +
            '<div style="display:flex; align-items:center; gap:8px;">' +
              '<div class="baloa-skeleton" style="width:16px; height:16px; border-radius:50%; flex-shrink: 0;"></div>' +
              '<div class="baloa-skeleton baloa-skeleton-text" style="width:60%; height:12px; margin:0;"></div>' +
            '</div>' +
            '<div style="display:flex; align-items:center; gap:8px;">' +
              '<div class="baloa-skeleton" style="width:16px; height:16px; border-radius:50%; flex-shrink: 0;"></div>' +
              '<div class="baloa-skeleton baloa-skeleton-text" style="width:50%; height:12px; margin:0;"></div>' +
            '</div>' +
          '</div>'
        );
      } else if (cardId === 'baloa-time-kpi-card' || cardId === 'baloa-wp-kpi-card') {
        $card.html(
          '<div class="baloa-skeleton" style="width: 32px; height: 32px; border-radius: 50%; margin: 8px auto 12px;"></div>' +
          '<div class="baloa-skeleton baloa-skeleton-text" style="width: 80px; height: 28px; margin: 0 auto 12px;"></div>' +
          '<div class="baloa-skeleton baloa-skeleton-text" style="width: 100px; height: 12px; margin: 0 auto 8px;"></div>'
        );
      }
    });

    let skeletonListHtml = '';
    for (let i = 0; i < 3; i++) {
      skeletonListHtml += 
        '<div class="problem-card" style="opacity: 0.85; border: 1px solid var(--border); padding: 24px; display: flex; align-items: flex-start; gap: 24px; margin-bottom: 16px;">' +
          '<div class="prob-left-col" style="width: 80px; flex-shrink: 0; text-align: center;">' +
            '<div class="baloa-skeleton" style="width: 36px; height: 36px; border-radius: 50%; margin: 0 auto 8px;"></div>' +
            '<div class="baloa-skeleton" style="width: 60px; height: 16px; border-radius: 4px; margin: 0 auto;"></div>' +
          '</div>' +
          '<div class="prob-content" style="flex: 1;">' +
            '<div class="baloa-skeleton baloa-skeleton-title" style="width: 40%; height: 20px; margin-bottom: 12px;"></div>' +
            '<div class="baloa-skeleton baloa-skeleton-text" style="width: 85%; height: 14px; margin-bottom: 8px;"></div>' +
            '<div class="baloa-skeleton baloa-skeleton-text" style="width: 60%; height: 14px; margin-bottom: 16px;"></div>' +
            '<div style="display: flex; gap: 8px;">' +
              '<div class="baloa-skeleton" style="width: 120px; height: 20px; border-radius: 4px;"></div>' +
              '<div class="baloa-skeleton" style="width: 100px; height: 20px; border-radius: 4px;"></div>' +
              '<div class="baloa-skeleton" style="width: 80px; height: 20px; border-radius: 4px;"></div>' +
            '</div>' +
          '</div>' +
          '<div class="prob-divider" style="width: 1px; background: var(--border); align-self: stretch; margin: 0 16px;"></div>' +
          '<div class="prob-right" style="width: 380px; flex-shrink: 0;">' +
            '<div class="baloa-skeleton" style="width: 100%; height: 48px; border-radius: 6px; margin-bottom: 12px;"></div>' +
            '<div style="display: flex; gap: 8px; justify-content: flex-end; width: 100%;">' +
              '<div class="baloa-skeleton" style="flex: 1; height: 32px; border-radius: 6px;"></div>' +
              '<div class="baloa-skeleton" style="flex: 1; height: 32px; border-radius: 6px;"></div>' +
              '<div class="baloa-skeleton" style="flex: 1; height: 32px; border-radius: 6px;"></div>' +
            '</div>' +
          '</div>' +
        '</div>';
    }
    $('#baloa-problems-list').html(skeletonListHtml);
  };

  admin.showDashboardError = function (msg) {
    admin.restoreKpiCardsHtml();
    $('#baloa-problems-list').html(
      '<div style="text-align:center;color:#ef4444;padding:20px;">✗ ' + admin.escHtml(msg) + '</div>'
    );
    $('#baloa-last-analyzed').text('Error en el análisis');
  };

  admin.adaptApiResponse = function (d) {
    if ( ! d ) return d;
    admin.MODULE_KEYS.forEach(k => {
      if ( d[k] && Array.isArray(d[k].checks) ) {
        d[k].checks.forEach(c => {
          if ( c.severity === 'error' ) {
            c.severity = 'critical';
          }
          if ( c.severity === 'pass' ) {
            c.passed = true;
          }
        });
      }
    });
    return d;
  };

  admin.renderDashboardResults = function (d) {
    d = admin.adaptApiResponse(d);
    admin.restoreKpiCardsHtml();
    admin.state.lastDashboardResult = d;

    // 1. UPDATE KPIs (Top Row)
    const gScore = d.global_score ?? 0;
    $('#baloa-global-score').text(gScore);
    const gTier = admin.scoreTier(gScore);
    $('#baloa-global-status').text(gTier.label).attr('class', 'kpi-footer ' + (gScore >= 80 ? 'good' : (gScore >= 50 ? 'warning' : 'critical')));
    
    // Animate Circle
    const circle = document.getElementById('baloa-global-donut');
    if (circle) {
      const radius = circle.r.baseVal.value;
      const circumference = radius * 2 * Math.PI;
      const offset = circumference - (gScore / 100) * circumference;
      circle.style.strokeDashoffset = offset;
      circle.style.stroke = gTier.color;
    }

    const readScore = admin.moduleScore(d.readability) ?? 0;
    $('#baloa-read-score').text(readScore);
    $('#baloa-read-status').text(admin.scoreTier(readScore).label).attr('class', 'kpi-footer ' + (readScore >= 80 ? 'good' : (readScore >= 50 ? 'warning' : 'critical')));

    const combinedAiScore = admin.calculateCombinedAiScore(d);
    const finalAiScore = combinedAiScore ?? 0;
    $('#baloa-ai-score').text(finalAiScore);
    $('#baloa-ai-status').text(admin.scoreTier(finalAiScore).label).attr('class', 'kpi-footer ' + (finalAiScore >= 80 ? 'good' : (finalAiScore >= 50 ? 'warning' : 'critical')));

    // 2. TALLY ALL STATS
    let total = 0, critical = 0, warning = 0, good = 0;
    const modules = {};
    admin.MODULE_KEYS.forEach(k => { if ( d[k] ) modules[k] = d[k]; });

    const problems = [];
    const correctos = [];

    Object.entries(modules).forEach(([modKey, mod]) => {
      if ( ! mod?.checks ) return;
      mod.checks.forEach(c => {
        total++;
        if ( c.severity === 'pass' ) {
          good++;
          correctos.push(Object.assign({}, c, { module: modKey }));
        } else {
          if ( c.severity === 'critical' ) critical++;
          else warning++;
          problems.push(Object.assign({}, c, { module: modKey }));
        }
      });
    });

    $('#baloa-count-crit').text(critical);
    $('#baloa-count-warn').text(warning);
    $('#baloa-count-pass').text(good);

    // 3. COMPILE LISTS AND RENDER
    problems.sort((a, b) => {
      const order = { critical: 0, warning: 1, info: 2 };
      return (order[a.severity] ?? 3) - (order[b.severity] ?? 3);
    });

    admin.state.globalProblems = problems;
    admin.state.globalCorrectos = correctos;

    // Time estimate Heuristic
    let totalMin = 0;
    problems.forEach(p => totalMin += admin.getHeuristics(p, p.module, p.severity).tiempo_min);
    $('#baloa-time-est').html(totalMin + ' <span>min</span>');

    admin.state.activeModule = 'resumen';
    admin.state.activeSeverity = 'all';
    admin.state.activeSearch = '';
    $('#baloa-problems-search').val('');
    $('#baloa-search-clear').hide();
    $('.filter-chip').removeClass('active');
    $('.filter-chip[data-severity="all"]').addClass('active');

    $('#baloa-prob-section-title').text('Problemas por Prioridad: Resumen General');
    
    if (typeof admin.applyProblemsFilters === 'function') {
      admin.applyProblemsFilters();
    }

    // RENDER CONDICIONAL DE WORDPRESS
    if (d.wordpress_data && d.wordpress_data.is_wordpress) {
      admin.state.wpPosts = d.wordpress_data.posts || [];
      admin.state.wpPages = d.wordpress_data.pages || [];
      admin.state.wpActiveTab = 'posts';

      $('#baloa-time-kpi-card').hide();
      $('#baloa-wp-kpi-card').show();
      $('#baloa-wp-toggle-group').show();
      $('#baloa-qw-title-text').text('Optimizador de Contenido WP');
      $('#baloa-qw-icon-symbol').text('🌐');
      $('#baloa-wp-status-text').text('Cargando optimizaciones...');

      admin.state.wpIsLocal = d.wordpress_data.is_local;
      if (typeof admin.renderWpPostsList === 'function') {
        admin.renderWpPostsList(admin.state.wpIsLocal);
      }
      if (typeof admin.lazyAnalyzeWpContent === 'function') {
        admin.lazyAnalyzeWpContent(admin.state.wpIsLocal);
      }
    } else {
      $('#baloa-time-kpi-card').show();
      $('#baloa-wp-kpi-card').hide();
      $('#baloa-wp-toggle-group').hide();
      $('#baloa-qw-title-text').text('Quick wins');
      $('#baloa-qw-icon-symbol').text('⚡');

      if (typeof admin.renderQuickWinsAndCorrectos === 'function') {
        admin.renderQuickWinsAndCorrectos(problems, correctos);
      }
    }

    admin.updateSidebarBadges(d);
  };
});
