/* global SEOSI, jQuery */
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
  const MODULE_KEYS    = [ 'html', 'keyword', 'schema', 'readability', 'metatags', 'llms', 'aeo', 'cwv', 'links' ];
  const moduleNames = {
    'html': 'SEO Estructural',
    'keyword': 'Keyword Scoring',
    'schema': 'Schema',
    'readability': 'Legibilidad',
    'metatags': 'Metatags',
    'llms': 'GEO / LLMs',
    'aeo': 'AEO / Contenido',
    'cwv': 'Core Web Vitals',
    'links': 'Enlaces'
  };

  let lastDashboardResult = null;
  let globalProblems = [];
  let globalCorrectos = [];

  // ── NUEVO: Estado de Búsqueda y Filtros (Option C) ──
  let activeModule = 'resumen';
  let activeSeverity = 'all';
  let activeSearch = '';

  // ── WORDPRESS CONTENT OPTIMIZER STATE ──
  let wpActiveTab = 'posts';
  let wpPosts = [];
  let wpPages = [];

  // Variable para trackear el alcance seleccionado
  let currentScanScope = 'single';

  function applyProblemsFilters() {
    // ── NUEVO: Bloqueo Premium para Módulos Pro en versión Free ──
    if ( typeof SEOSI !== 'undefined' && ! SEOSI.is_premium && ['llms', 'aeo', 'cwv'].includes(activeModule) ) {
      const name = moduleNames[activeModule] || activeModule;
      const desc = activeModule === 'llms' 
        ? 'Optimiza tu sitio para ser rastreado e indexado por motores de búsqueda e inteligencias artificiales (crawlers de LLMs) mediante reglas personalizadas.'
        : (activeModule === 'aeo'
            ? 'Optimización para motores de respuesta (Answer Engine Optimization) y estructuración de contenido semántico de alto impacto.'
            : 'Mide y optimiza la velocidad, interactividad y estabilidad visual de tu sitio mediante diagnósticos avanzados de Core Web Vitals.');
            
      const settingsUrl = 'admin.php?page=seosi-settings';
      
      const lockHtml = 
        '<div class="seosi-pro-lock-overlay" style="padding: 60px 40px; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(10px); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08); text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; margin-top: 10px; min-height: 350px;">' +
          '<span style="font-size: 64px; margin-bottom: 20px;">🔒</span>' +
          '<h3 style="font-family: \'Syne\', sans-serif; font-size: 24px; font-weight: 800; color: #fff; margin: 0 0 12px 0;">Módulo Premium: ' + name + '</h3>' +
          '<p style="color: #94a3b8; font-size: 14px; max-width: 500px; margin: 0 0 24px 0; line-height: 1.6;">' + desc + '</p>' +
          '<a href="' + settingsUrl + '" class="btn btn-primary" style="background: linear-gradient(135deg, var(--accent-purple), var(--accent-purple-bright)); border: none; padding: 12px 28px; font-weight: 600; text-decoration: none; border-radius: 4px; color: #fff;">Activar Licencia PRO</a>' +
        '</div>';
        
      $('#seosi-problems-list').html(lockHtml);
      $('#seosi-qw-list').html('<div style="color:var(--text-muted); font-size:13px">Contenido bloqueado (Módulo PRO).</div>');
      $('#seosi-ok-list').html('<div style="color:var(--text-muted); font-size:13px">Contenido bloqueado (Módulo PRO).</div>');
      $('#btn-qw-apply').prop('disabled', true);
      $('#btn-autofix-all-problems').hide();
      updateFilterChipCounts();
      return;
    }

    let filtered = globalProblems;

    // 1. Filtrar por Módulo
    if ( activeModule !== 'resumen' ) {
      filtered = filtered.filter(p => p.module === activeModule);
    }

    // 2. Filtrar por Severidad
    if ( activeSeverity !== 'all' ) {
      filtered = filtered.filter(p => p.severity === activeSeverity);
    }

    // 3. Filtrar por Término de Búsqueda
    if ( activeSearch ) {
      filtered = filtered.filter(p => {
        const title = (p.title || '').toLowerCase();
        const desc = (p.recommendation || p.why || '').toLowerCase();
        const modName = (moduleNames[p.module] || p.module || '').toLowerCase();
        return title.includes(activeSearch) || desc.includes(activeSearch) || modName.includes(activeSearch);
      });
    }

    renderProblemsAccordion(filtered);
    updateFilterChipCounts();
  }

  function updateFilterChipCounts() {
    let baseList = globalProblems;
    if ( activeModule !== 'resumen' ) {
      baseList = baseList.filter(p => p.module === activeModule);
    }

    if ( activeSearch ) {
      baseList = baseList.filter(p => {
        const title = (p.title || '').toLowerCase();
        const desc = (p.recommendation || p.why || '').toLowerCase();
        const modName = (moduleNames[p.module] || p.module || '').toLowerCase();
        return title.includes(activeSearch) || desc.includes(activeSearch) || modName.includes(activeSearch);
      });
    }

    const allCount = baseList.length;
    const critCount = baseList.filter(p => p.severity === 'critical').length;
    const warnCount = baseList.filter(p => p.severity === 'warning').length;

    $('#chip-count-all').text(allCount);
    $('#chip-count-crit').text(critCount);
    $('#chip-count-warn').text(warnCount);
  }

  function scoreTier(score) {
    const s = Number(score);
    if ( Number.isNaN(s) ) {
      return { label: '', tier: '', color: '#8892a4' };
    }
    if ( s >= 80 ) return { label: 'Excelente', tier: 'tier-good', color: '#22c55e' };
    if ( s >= 60 ) return { label: 'Bueno', tier: 'tier-mid', color: '#f59e0b' };
    if ( s >= 40 ) return { label: 'Mejorable', tier: 'tier-low', color: '#f59e0b' };
    return { label: 'Crítico', tier: 'tier-bad', color: '#ef4444' };
  }

  function badgeClass(score) {
    const s = Number(score);
    if ( Number.isNaN(s) ) return 'badge-orange';
    if ( s >= 80 ) return 'badge-green';
    if ( s >= 60 ) return 'badge-orange';
    return 'badge-red';
  }

  function moduleScore(mod) {
    if ( ! mod || mod.skipped || mod.error ) return null;
    if ( typeof mod.score === 'number' ) return mod.score;
    return null;
  }

  function updateDonut(score) {
    const tier   = scoreTier(score);
    const offset = DONUT_CIRC - ( Math.max(0, Math.min(100, score)) / 100 ) * DONUT_CIRC;
    $('.donut-fill').css({
      strokeDasharray:  DONUT_CIRC,
      strokeDashoffset: offset,
      stroke:           tier.color,
    });
    $('#seosi-global-status').text(tier.label).attr('class', 'donut-status ' + tier.tier).css('color', tier.color);
  }

  function updateRadar(data) {
    const cx = 100;
    const cy = 100;
    const pts = RADAR_AXES.map(function (axis) {
      const mod   = data[axis.key];
      const score = moduleScore(mod);
      const t     = score === null ? 0 : Math.max(0, Math.min(100, score)) / 100;
      const x     = cx + (axis.x - cx) * t;
      const y     = cy + (axis.y - cy) * t;
      return x.toFixed(1) + ',' + y.toFixed(1);
    });
    const $poly = $('#seosi-radar-data');
    const avg   = scoreTier(data.global_score ?? 0);
    $poly.attr('points', pts.join(' '));
    $poly.css({ stroke: avg.color, fill: hexToRgba(avg.color, 0.15) });
  }

  function hexToRgba(hex, alpha) {
    const h = hex.replace('#', '');
    const full = h.length === 3 ? h.split('').map(function (c) { return c + c; }).join('') : h;
    const n = parseInt(full, 16);
    const r = (n >> 16) & 255;
    const g = (n >> 8) & 255;
    const b = n & 255;
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  function updateSidebarBadges(data) {
    $('[data-score-for]').each(function () {
      const key   = $(this).data('score-for');
      const mod   = data[key];
      const score = moduleScore(mod);
      if ( score === null ) {
        $(this).text('—').attr('class', 'score-badge badge-orange');
        return;
      }
      $(this).text(score).attr('class', 'score-badge ' + badgeClass(score));
    });
  }

  function updateAiCard(data) {
    const aiScore = moduleScore(data.llms);
    const tier    = scoreTier(aiScore ?? 0);
    $('#seosi-ai-score').text(aiScore === null ? '—' : aiScore);
    $('#seosi-ai-label').text(aiScore === null ? '' : tier.label).attr('class', 'ai-score-label ' + tier.tier);
    if ( aiScore !== null && aiScore < 40 ) {
      $('#seosi-ai-desc').text('La visibilidad para IA es baja. Revisa llms.txt, estructura de respuestas y políticas en robots.txt.');
    } else if ( aiScore !== null && aiScore < 60 ) {
      $('#seosi-ai-desc').text('Hay margen de mejora en GEO y señales para crawlers de IA.');
    } else {
      $('#seosi-ai-desc').text('Tu contenido tiene buena visibilidad para sistemas de IA, pero puedes seguir optimizando.');
    }
  }

  function updateRecommendation(problems) {
    if ( ! problems.length ) {
      $('#seosi-rec-title').text('Sin problemas críticos');
      $('#seosi-rec-desc').text('Mantén el monitoreo periódico y revisa advertencias menores.');
      $('#seosi-rec-btn').prop('disabled', true);
      return;
    }
    const top = problems[0];
    $('#seosi-rec-title').text(top.title || top.id || 'Mejora detectada');
    $('#seosi-rec-desc').html(escHtml(top.recommendation || top.why || 'Revisa este punto en el módulo ' + (top.module || '') + '.'));
    $('#seosi-rec-btn').prop('disabled', false);
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // Guardar HTML original de las tarjetas KPI para restaurarlas al finalizar el estado de carga (evitando CLS)
  let kpiCardsOriginalHtml = {};

  function cacheKpiCardsHtml() {
    if (Object.keys(kpiCardsOriginalHtml).length > 0) return;
    
    $('.dash-kpis .kpi-card').each(function(index) {
      const $card = $(this);
      const cardId = $card.attr('id') || 'kpi-card-index-' + index;
      kpiCardsOriginalHtml[cardId] = $card.html();
    });
  }

  function restoreKpiCardsHtml() {
    if (Object.keys(kpiCardsOriginalHtml).length === 0) return;
    
    $('.dash-kpis .kpi-card').each(function(index) {
      const $card = $(this);
      const cardId = $card.attr('id') || 'kpi-card-index-' + index;
      if (kpiCardsOriginalHtml[cardId]) {
        $card.html(kpiCardsOriginalHtml[cardId]);
      }
    });
  }

  function showMainUIAnalysisLoading() {
    // 1. Asegurar que tenemos guardado el HTML original
    cacheKpiCardsHtml();

    // 2. Colocar los skeletons en las tarjetas KPI para evitar Layout Shift (CLS < 0.1)
    $('.dash-kpis .kpi-card').each(function(index) {
      const $card = $(this);
      const cardId = $card.attr('id') || 'kpi-card-index-' + index;

      if (cardId.indexOf('kpi-card-index-0') !== -1) {
        $card.html(
          '<div class="kpi-title">SEO Score</div>' +
          '<div class="seosi-skeleton seosi-skeleton-circle" style="margin-top: 12px;"></div>' +
          '<div class="seosi-skeleton seosi-skeleton-text" style="width: 70px; margin: 16px auto 0; height: 14px;"></div>'
        );
      } else if (cardId.indexOf('kpi-card-index-1') !== -1) {
        $card.html(
          '<div class="kpi-title">Legibilidad</div>' +
          '<div class="seosi-skeleton seosi-skeleton-text" style="width: 50px; height: 32px; margin: 26px auto 26px;"></div>' +
          '<div class="seosi-skeleton seosi-skeleton-text" style="width: 70px; margin: 0 auto; height: 14px;"></div>'
        );
      } else if (cardId.indexOf('kpi-card-index-2') !== -1) {
        $card.html(
          '<div class="kpi-title">IA / Visibilidad</div>' +
          '<div class="seosi-skeleton seosi-skeleton-text" style="width: 50px; height: 32px; margin: 26px auto 26px;"></div>' +
          '<div class="seosi-skeleton seosi-skeleton-text" style="width: 70px; margin: 0 auto; height: 14px;"></div>'
        );
      } else if (cardId.indexOf('kpi-card-index-3') !== -1) {
        $card.html(
          '<div class="kpi-title" style="margin-bottom:0">Problemas encontrados</div>' +
          '<div style="display:flex; flex-direction:column; gap:16px; margin-top:20px; width:100%;">' +
            '<div style="display:flex; align-items:center; gap:8px;">' +
              '<div class="seosi-skeleton" style="width:16px; height:16px; border-radius:50%; flex-shrink: 0;"></div>' +
              '<div class="seosi-skeleton seosi-skeleton-text" style="width:70%; height:12px; margin:0;"></div>' +
            '</div>' +
            '<div style="display:flex; align-items:center; gap:8px;">' +
              '<div class="seosi-skeleton" style="width:16px; height:16px; border-radius:50%; flex-shrink: 0;"></div>' +
              '<div class="seosi-skeleton seosi-skeleton-text" style="width:60%; height:12px; margin:0;"></div>' +
            '</div>' +
            '<div style="display:flex; align-items:center; gap:8px;">' +
              '<div class="seosi-skeleton" style="width:16px; height:16px; border-radius:50%; flex-shrink: 0;"></div>' +
              '<div class="seosi-skeleton seosi-skeleton-text" style="width:50%; height:12px; margin:0;"></div>' +
            '</div>' +
          '</div>'
        );
      } else if (cardId === 'seosi-time-kpi-card' || cardId === 'seosi-wp-kpi-card') {
        $card.html(
          '<div class="seosi-skeleton" style="width: 32px; height: 32px; border-radius: 50%; margin: 8px auto 12px;"></div>' +
          '<div class="seosi-skeleton seosi-skeleton-text" style="width: 80px; height: 28px; margin: 0 auto 12px;"></div>' +
          '<div class="seosi-skeleton seosi-skeleton-text" style="width: 100px; height: 12px; margin: 0 auto 8px;"></div>'
        );
      }
    });

    // 3. Colocar los skeletons en la lista de problemas principal
    let skeletonListHtml = '';
    for (let i = 0; i < 3; i++) {
      skeletonListHtml += 
        '<div class="problem-card" style="opacity: 0.85; border: 1px solid var(--border); padding: 24px; display: flex; align-items: flex-start; gap: 24px; margin-bottom: 16px;">' +
          '<div class="prob-left-col" style="width: 80px; flex-shrink: 0; text-align: center;">' +
            '<div class="seosi-skeleton" style="width: 36px; height: 36px; border-radius: 50%; margin: 0 auto 8px;"></div>' +
            '<div class="seosi-skeleton" style="width: 60px; height: 16px; border-radius: 4px; margin: 0 auto;"></div>' +
          '</div>' +
          '<div class="prob-content" style="flex: 1;">' +
            '<div class="seosi-skeleton seosi-skeleton-title" style="width: 40%; height: 20px; margin-bottom: 12px;"></div>' +
            '<div class="seosi-skeleton seosi-skeleton-text" style="width: 85%; height: 14px; margin-bottom: 8px;"></div>' +
            '<div class="seosi-skeleton seosi-skeleton-text" style="width: 60%; height: 14px; margin-bottom: 16px;"></div>' +
            '<div style="display: flex; gap: 8px;">' +
              '<div class="seosi-skeleton" style="width: 120px; height: 20px; border-radius: 4px;"></div>' +
              '<div class="seosi-skeleton" style="width: 100px; height: 20px; border-radius: 4px;"></div>' +
              '<div class="seosi-skeleton" style="width: 80px; height: 20px; border-radius: 4px;"></div>' +
            '</div>' +
          '</div>' +
          '<div class="prob-divider" style="width: 1px; background: var(--border); align-self: stretch; margin: 0 16px;"></div>' +
          '<div class="prob-right" style="width: 380px; flex-shrink: 0;">' +
            '<div class="seosi-skeleton" style="width: 100%; height: 48px; border-radius: 6px; margin-bottom: 12px;"></div>' +
            '<div style="display: flex; gap: 8px; justify-content: flex-end; width: 100%;">' +
              '<div class="seosi-skeleton" style="flex: 1; height: 32px; border-radius: 6px;"></div>' +
              '<div class="seosi-skeleton" style="flex: 1; height: 32px; border-radius: 6px;"></div>' +
              '<div class="seosi-skeleton" style="flex: 1; height: 32px; border-radius: 6px;"></div>' +
            '</div>' +
          '</div>' +
        '</div>';
    }
    $('#seosi-problems-list').html(skeletonListHtml);
  }

  function showDashboardError(msg) {
    restoreKpiCardsHtml();
    $('#seosi-problems-list').html(
      '<div style="text-align:center;color:#ef4444;padding:20px;">✗ ' + escHtml(msg) + '</div>'
    );
    $('#seosi-last-analyzed').text('Error en el análisis');
  }

  // --- NUEVA HEURISTICA PARA LIGHT THEME ---
  function getHeuristics(check, modKey, severity) {
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
  }

  function renderDashboardResults(d) {
    restoreKpiCardsHtml();
    lastDashboardResult = d;

    // 1. UPDATE KPIs (Top Row)
    const gScore = d.global_score ?? 0;
    $('#seosi-global-score').text(gScore);
    const gTier = scoreTier(gScore);
    $('#seosi-global-status').text(gTier.label).attr('class', 'kpi-footer ' + (gScore >= 80 ? 'good' : (gScore >= 50 ? 'warning' : 'critical')));
    
    // Animate Circle
    const circle = document.getElementById('seosi-global-donut');
    if (circle) {
      const radius = circle.r.baseVal.value;
      const circumference = radius * 2 * Math.PI;
      const offset = circumference - (gScore / 100) * circumference;
      circle.style.strokeDashoffset = offset;
      circle.style.stroke = gTier.color;
    }

    const readScore = moduleScore(d.readability) ?? 0;
    $('#seosi-read-score').text(readScore);
    $('#seosi-read-status').text(scoreTier(readScore).label).attr('class', 'kpi-footer ' + (readScore >= 80 ? 'good' : (readScore >= 50 ? 'warning' : 'critical')));

    const aiScore = moduleScore(d.llms) ?? 0;
    $('#seosi-ai-score').text(aiScore);
    $('#seosi-ai-status').text(scoreTier(aiScore).label).attr('class', 'kpi-footer ' + (aiScore >= 80 ? 'good' : (aiScore >= 50 ? 'warning' : 'critical')));

    // 2. TALLY ALL STATS
    let total = 0, critical = 0, warning = 0, good = 0;
    const modules = {};
    MODULE_KEYS.forEach(k => { if ( d[k] ) modules[k] = d[k]; });

    const problems = [];
    const correctos = [];

    Object.entries(modules).forEach(([modKey, mod]) => {
      if ( ! mod?.checks ) return;
      mod.checks.forEach(c => {
        total++;
        if ( c.passed ) {
          good++;
          correctos.push(Object.assign({}, c, { module: modKey }));
        } else {
          if ( c.severity === 'critical' ) critical++;
          else warning++;
          problems.push(Object.assign({}, c, { module: modKey }));
        }
      });
    });

    $('#seosi-count-crit').text(critical);
    $('#seosi-count-warn').text(warning);
    $('#seosi-count-pass').text(good);

    // 3. COMPILE LISTS AND RENDER
    problems.sort((a, b) => {
      const order = { critical: 0, warning: 1, info: 2 };
      return (order[a.severity] ?? 3) - (order[b.severity] ?? 3);
    });

    globalProblems = problems;
    globalCorrectos = correctos;

    // Time estimate Heuristic
    let totalMin = 0;
    problems.forEach(p => totalMin += getHeuristics(p, p.module, p.severity).tiempo_min);
    $('#seosi-time-est').html(totalMin + ' <span>min</span>');

    activeModule = 'resumen';
    activeSeverity = 'all';
    activeSearch = '';
    $('#seosi-problems-search').val('');
    $('#seosi-search-clear').hide();
    $('.filter-chip').removeClass('active');
    $('.filter-chip[data-severity="all"]').addClass('active');

    $('#seosi-prob-section-title').text('Problemas por Prioridad: Resumen General');
    applyProblemsFilters();

    // RENDER CONDICIONAL DE WORDPRESS
    if (d.wordpress_data && d.wordpress_data.is_wordpress) {
      wpPosts = d.wordpress_data.posts || [];
      wpPages = d.wordpress_data.pages || [];
      wpActiveTab = 'posts';

      $('#seosi-time-kpi-card').hide();
      $('#seosi-wp-kpi-card').show();
      $('#seosi-wp-toggle-group').show();
      $('#seosi-qw-title-text').text('Optimizador de Contenido WP');
      $('#seosi-qw-icon-symbol').text('🌐');
      $('#seosi-wp-status-text').text('Cargando optimizaciones...');

      wpIsLocal = d.wordpress_data.is_local;
      renderWpPostsList(wpIsLocal);
      lazyAnalyzeWpContent(wpIsLocal);
    } else {
      $('#seosi-time-kpi-card').show();
      $('#seosi-wp-kpi-card').hide();
      $('#seosi-wp-toggle-group').hide();
      $('#seosi-qw-title-text').text('Quick wins');
      $('#seosi-qw-icon-symbol').text('⚡');

      renderQuickWinsAndCorrectos(problems, correctos);
    }

    updateSidebarBadges(d);
  }

  function renderProblemsAccordion(problemsList) {
    if ( problemsList.length === 0 ) {
      $('#seosi-problems-list').html(
        '<div style="text-align:center;color:var(--green);padding:40px; background:var(--bg-card); border-radius:12px; border:1px solid var(--border);">🚀 ¡Felicidades! Tu estructura es perfecta y no hay problemas.</div>'
      );
      return;
    }

    const html = problemsList.map(c => {
      const isCrit = c.severity === 'critical';
      const icon = isCrit ? '&lt;/&gt;' : '⚠'; 
      const wrapperCls = isCrit ? 'prob-card-crit' : 'prob-card-warn';
      const label = isCrit ? 'Crítico' : 'Advertencia';
      const title = escHtml(c.title ?? c.id ?? '—');
      const desc = escHtml(c.recommendation || c.why || 'Se detectó un problema en este check.');
      
      const heur = getHeuristics(c, c.module, c.severity);
      const sColor = heur.seo_impact === 'Alto' ? 'red' : 'orange';
      const aColor = heur.ai_impact === 'Alta' ? 'red' : (heur.ai_impact === 'Media' ? 'orange' : 'green');
      
      const analyzedUrl = ($('#seosi-url-input').val() || SEOSI.post_url || '').replace(/\/$/, '');
      const isLocalSite = analyzedUrl === SEOSI.home_url || analyzedUrl.indexOf(SEOSI.home_url + '/') === 0;

      return '<div class="problem-card ' + wrapperCls + '">' +
               '<div class="prob-left-col">' +
                 '<div class="prob-icon-box ' + (isCrit ? 'crit' : 'warn') + '">' + icon + '</div>' +
                 '<div class="prob-label-badge ' + (isCrit ? 'crit' : 'warn') + '">' + label + '</div>' +
               '</div>' +
               '<div class="prob-content">' +
                 '<div class="prob-title">' + title + '</div>' +
                 '<div class="prob-desc">' + desc + '</div>' +
                 (c.context && c.context.allow_directives ? '<div class="prob-directives-box" style="margin-top:10px;"><label style="display:block;font-size:12px;font-weight:700;color:var(--text-secondary);margin-bottom:6px;">📋 Directivas Allow para copiar en robots.txt:</label><textarea readonly style="width:100%;height:140px;padding:10px;font-family:monospace;font-size:12px;background:var(--bg-secondary);color:var(--text-primary);border:1px solid var(--border);border-radius:6px;resize:vertical;" onclick="this.select()">' + escHtml(c.context.allow_directives) + '</textarea><div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Haz clic en el textarea para seleccionar todo el contenido y luego Ctrl+C para copiar.</div></div>' : '') +
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
                   '<div class="why-box-text">' + escHtml(c.why || 'Mejora la experiencia y lectura de bots.') + '</div>' +
                 '</div>' +
                 '<div class="prob-actions">' +
                   '<button type="button" class="btn-sol" data-id="' + c.id + '" data-module="' + c.module + '" data-desc="' + escHtml(c.recommendation || c.why || '') + '">Ver solución</button>' +
                   '<a href="admin.php?page=seosi-glossary#term-' + c.id + '" class="btn-sol btn-glossary-link" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 4px;">Ver en Glosario 📖</a>' +
                   (isLocalSite && c.supports_autofix === true ? '<button type="button" class="btn-autofix" data-id="' + c.id + '" data-module="' + c.module + '" data-url="' + analyzedUrl + '">Auto-fix</button>' : '') +
                 '</div>' +
               '</div>' +
             '</div>';
    });
    $('#seosi-problems-list').html(html.join(''));

    // Count fixable problems that can be autogenerated (excluding llms_txt_present and llms_full_txt_present)
    const fixableCount = problemsList.filter(p => p.supports_autofix === true && p.id !== 'llms_txt_present' && p.id !== 'llms_full_txt_present').length;
    const $btnAll = $('#btn-autofix-all-problems');
    if ($btnAll.length) {
      if (fixableCount > 0) {
        $btnAll.text('✨ Aplicar todos los Auto-fixes (' + fixableCount + ')').show();
      } else {
        $btnAll.hide();
      }
    }
  }

  function renderQuickWinsAndCorrectos(problems, correctos) {
    // Quick Wins: just take the top 3 warnings (or critical)
    const qws = problems.slice(0, 3);
    $('#seosi-qw-count').text(qws.length);
    if(qws.length === 0) {
      $('#seosi-qw-list').html('<div style="color:var(--text-muted); font-size:13px">Excelente, no hay arreglos pendientes.</div>');
      $('#btn-qw-apply').prop('disabled', true);
    } else {
      const qwHtml = qws.map(q => {
        return '<div class="qw-item"><span class="qw-check">◎</span> ' + escHtml(q.title || 'Check pendiente') + ' <span class="qw-time">' + getHeuristics(q, q.module, q.severity).tiempo_min + ' min</span></div>';
      });
      $('#seosi-qw-list').html(qwHtml.join(''));
      $('#btn-qw-apply').prop('disabled', false);
    }

    // Correctos: Limit to 10 maybe? Let's limit to 14
    $('#seosi-ok-count').text(correctos.length);
    if(correctos.length === 0) {
      $('#seosi-ok-list').html('<div style="color:var(--text-muted); font-size:13px">Aún no hay checks correctos.</div>');
    } else {
      const okHtml = correctos.slice(0, 5).map(ok => {
        return '<div class="ok-item"><div class="ok-item-left"><span class="ok-icon">◎</span> ' + escHtml(ok.title || 'Check correcto') + '</div> <span style="transform:rotate(-90deg); color:var(--border)">⌄</span></div>';
      });
      let xtra = '';
      if (correctos.length > 5) xtra = '<div style="font-size:12px; color:var(--text-muted); margin-top:8px;">... y ' + (correctos.length - 5) + ' más</div>' +
                                       '<button type="button" class="btn-action-light" style="margin-top:12px; font-weight:500;">Ver todos los correctos</button>';
      $('#seosi-ok-list').html(okHtml.join('') + xtra);
    }
  }

  // ── WORDPRESS CONTENT OPTIMIZER HELPERS ──
  let wpIsLocal = false;

  function renderWpPostsList(isLocal) {
    const items = wpActiveTab === 'posts' ? wpPosts : wpPages;
    $('#seosi-qw-count').text(items.length);

    if (items.length === 0) {
      $('#seosi-qw-list').html('<div style="color:var(--text-muted); font-size:13px">Sin contenido reciente encontrado.</div>');
      return;
    }

    const html = items.map((item, index) => {
      const score = item.score;
      let scoreHtml = '';
      let scoreClass = 'score-none';
      
      if (score === undefined || score === null) {
        scoreHtml = '<span class="wp-post-score score-none">⏳</span>';
      } else {
        if (score >= 80) scoreClass = 'score-good';
        else if (score >= 40) scoreClass = 'score-mid';
        else scoreClass = 'score-bad';
        scoreHtml = '<span class="wp-post-score ' + scoreClass + '">' + score + '</span>';
      }

      // Check if there are fixable issues
      let fixableCount = 0;
      if (item.problems) {
        item.problems.forEach(p => {
          if (p.supports_autofix === true) fixableCount++;
        });
      }

      let actionBtn = '';
      if (score === undefined || score === null) {
        actionBtn = '<button type="button" class="wp-post-btn-autofix" disabled style="opacity: 0.5;">Analizando...</button>';
      } else {
        if (isLocal) {
          if (fixableCount > 0) {
            actionBtn = '<button type="button" class="wp-post-btn-autofix btn-trigger-wp-autofix" data-index="' + index + '" data-type="' + wpActiveTab + '">Auto-fix (' + fixableCount + ')</button>';
          } else {
            actionBtn = '<button type="button" class="wp-post-btn-autofix" disabled style="background: rgba(34, 197, 94, 0.15); color: var(--green); border: 1px solid rgba(34, 197, 94, 0.25);">✓ Optimizado</button>';
          }
        } else {
          actionBtn = '<span style="font-size:11px; color:var(--text-muted);">Externo (Solo lectura)</span>';
        }
      }

      return '<div class="wp-post-item">' +
               '<div class="wp-post-title" title="' + escHtml(item.title) + '">' + escHtml(item.title) + '</div>' +
               '<div class="wp-post-meta">' +
                 scoreHtml +
                 actionBtn +
               '</div>' +
             '</div>';
    });

    $('#seosi-qw-list').html(html.join(''));
  }

  function lazyAnalyzeWpContent(isLocal) {
    const queue = [];
    wpPosts.forEach((item, index) => {
      queue.push({ type: 'posts', index: index, item: item });
    });
    wpPages.forEach((item, index) => {
      queue.push({ type: 'pages', index: index, item: item });
    });

    const pending = [];
    const oneHour = 60 * 60 * 1000;

    queue.forEach(q => {
      const cacheKey = 'seosi_wp_cache_' + encodeURIComponent(q.item.url);
      try {
        const cacheStr = localStorage.getItem(cacheKey);
        if (cacheStr) {
          const cache = JSON.parse(cacheStr);
          if (cache && (Date.now() - cache.timestamp < oneHour)) {
            q.item.score = cache.score;
            q.item.problems = cache.problems;
            q.item.results = cache.results;
            return;
          }
        }
      } catch (e) {
        console.error('Error reading wp cache:', e);
      }
      pending.push(q);
    });

    renderWpPostsList(isLocal);
    updateWpSummaryKpi();

    if (pending.length === 0) return;

    let currentIdx = 0;
    function processNextPending() {
      if (currentIdx >= pending.length) {
        updateWpSummaryKpi();
        return;
      }

      const q = pending[currentIdx];
      
      $.ajax({
        url: SEOSI.ajax_url,
        method: 'POST',
        data: {
          action: 'seosi_analyze',
          nonce: SEOSI.nonce,
          url: q.item.url,
          keyword: ''
        },
        success: function(res) {
          if (res.success && res.data) {
            const problems = [];
            MODULE_KEYS.forEach(k => {
              if (res.data[k] && res.data[k].checks) {
                res.data[k].checks.forEach(c => {
                  if (!c.passed) {
                    problems.push(Object.assign({}, c, { module: k }));
                  }
                });
              }
            });

            q.item.score = res.data.global_score ?? 0;
            q.item.problems = problems;
            q.item.results = res.data;

            const cacheKey = 'seosi_wp_cache_' + encodeURIComponent(q.item.url);
            try {
              localStorage.setItem(cacheKey, JSON.stringify({
                timestamp: Date.now(),
                score: q.item.score,
                problems: problems,
                results: res.data
              }));
            } catch(e) {}

            renderWpPostsList(isLocal);
            updateWpSummaryKpi();
          }
        },
        error: function() {
          q.item.score = 0;
          q.item.problems = [];
          renderWpPostsList(isLocal);
        },
        complete: function() {
          currentIdx++;
          setTimeout(processNextPending, 800);
        }
      });
    }

    processNextPending();
  }

  function updateWpSummaryKpi() {
    let sum = 0;
    let count = 0;
    wpPosts.concat(wpPages).forEach(item => {
      if (item.score !== undefined && item.score !== null) {
        sum += Number(item.score);
        count++;
      }
    });

    if (count === 0) {
      $('#seosi-wp-score-avg').text('—');
      $('#seosi-wp-status-text').text('Analizando contenido...').css('color', 'var(--text-muted)');
      return;
    }

    const avg = Math.round(sum / count);
    $('#seosi-wp-score-avg').text(avg);
    
    const tier = scoreTier(avg);
    $('#seosi-wp-score-avg').css('color', tier.color);
    $('#seosi-wp-status-text').text(tier.label).css('color', tier.color);
  }

  function refreshWpItem(url) {
    const item = wpPosts.concat(wpPages).find(p => p.url === url);
    if (!item) return;

    item.score = null;
    item.problems = null;
    item.results = null;
    renderWpPostsList(wpIsLocal);
    updateWpSummaryKpi();

    $.ajax({
      url: SEOSI.ajax_url,
      method: 'POST',
      data: {
        action: 'seosi_analyze',
        nonce: SEOSI.nonce,
        url: url,
        keyword: ''
      },
      success: function(res) {
        if (res.success && res.data) {
          const problems = [];
          MODULE_KEYS.forEach(k => {
            if (res.data[k] && res.data[k].checks) {
              res.data[k].checks.forEach(c => {
                if (!c.passed) {
                  problems.push(Object.assign({}, c, { module: k }));
                }
              });
            }
          });

          item.score = res.data.global_score ?? 0;
          item.problems = problems;
          item.results = res.data;

          const cacheKey = 'seosi_wp_cache_' + encodeURIComponent(url);
          try {
            localStorage.setItem(cacheKey, JSON.stringify({
              timestamp: Date.now(),
              score: item.score,
              problems: problems,
              results: res.data
            }));
          } catch(e) {}

          renderWpPostsList(wpIsLocal);
          updateWpSummaryKpi();
        }
      }
    });
  }

  function executeSequentialAutofixes(fixes, progressCallback, completionCallback) {
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
        url: SEOSI.ajax_url,
        method: 'POST',
        data: {
          action: 'seosi_execute_autofix',
          nonce: SEOSI.nonce,
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
          console.error('[SEOSI] Sequential autofix failed for', fix.id, err);
        },
        complete: function() {
          currentIndex++;
          setTimeout(processNext, 200);
        }
      });
    }

    processNext();
  }

  function openWpManualFixesModal(item, manualProblems, afterAutomatic) {
    $('#seosi-modal-title').text('Acción requerida: ' + item.title);
    $('#seosi-modal-action-btn').hide();

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
                    '<strong style="font-size:13px; color:var(--text-primary);">' + escHtml(p.title || p.id) + '</strong>' +
                  '</div>' +
                  '<div style="font-size:12px; color:var(--text-secondary);">' + escHtml(p.recommendation || p.why || '') + '</div>' +
                '</div>' +
                '<div>' +
                  '<button type="button" class="btn-autofix wp-post-btn-autofix" data-id="' + p.id + '" data-module="' + p.module + '" data-url="' + escHtml(item.url) + '">Auto-fix</button>' +
                '</div>' +
              '</div>';
    });
    html += '</div>';

    $('#seosi-modal-body').html(html);
    $('#seosi-modal-overlay').fadeIn('fast');
  }


  // ── Dashboard ───────────────────────────────────────────────────────────────
  const $analyzeBtn = $('#seosi-analyze-btn-dash');
  const $urlInput   = $('#seosi-url-input');

  $urlInput.on('keypress', function (e) {
    if (e.which === 13) {
      e.preventDefault();
      $analyzeBtn.click();
    }
  });

  // moduleNames moved to the top of the file

  // Module view removed in favor of filtering problems

  // Vincular eventos de los chips de filtrado y búsqueda
  $(document).on('click', '.filter-chip', function(e) {
    e.preventDefault();
    $('.filter-chip').removeClass('active');
    $(this).addClass('active');
    activeSeverity = $(this).data('severity');
    applyProblemsFilters();
  });

  $(document).on('input keyup', '#seosi-problems-search', function() {
    activeSearch = $(this).val().toLowerCase().trim();
    if ( activeSearch ) {
      $('#seosi-search-clear').show();
    } else {
      $('#seosi-search-clear').hide();
    }
    applyProblemsFilters();
  });

  $(document).on('click', '#seosi-search-clear', function(e) {
    e.preventDefault();
    $('#seosi-problems-search').val('');
    $(this).hide();
    activeSearch = '';
    applyProblemsFilters();
  });

  $(document).on('click', '#btn-autofix-all-problems', function(e) {
    e.preventDefault();
    const $btn = $(this);
    
    // Get all fixable automatic problems from globalProblems
    const fixableProblems = globalProblems.filter(p => p.supports_autofix === true && p.id !== 'llms_txt_present' && p.id !== 'llms_full_txt_present');
    if (fixableProblems.length === 0) return;

    $btn.prop('disabled', true).css('opacity', '0.7');

    const analyzedUrl = ($('#seosi-url-input').val() || SEOSI.post_url || '').replace(/\/$/, '');
    
    const fixesData = fixableProblems.map(p => ({
      id: p.id,
      module: p.module,
      url: analyzedUrl
    }));

    executeSequentialAutofixes(
      fixesData,
      function(current, total) {
        $btn.text('⏳ Aplicando (' + current + '/' + total + ')...');
      },
      function(result) {
        $btn.text('✨ Aplicar todos los Auto-fixes').css('opacity', '1').prop('disabled', false).hide();
        
        // Simular clic para volver a analizar y actualizar el dashboard
        $('#seosi-analyze-btn-dash').click();
      }
    );
  });

  $(document).on('click', '#btn-qw-apply', function(e) {
    e.preventDefault();
    const $btn = $(this);
    
    const isWp = lastDashboardResult && lastDashboardResult.wordpress_data && lastDashboardResult.wordpress_data.is_wordpress;
    
    if (isWp) {
      const items = wpActiveTab === 'posts' ? wpPosts : wpPages;
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

      executeSequentialAutofixes(
        allFixes,
        function(current, total, currentFix) {
          $btn.text('⏳ Aplicando (' + current + '/' + total + ') - ' + (currentFix.postTitle || ''));
        },
        function(result) {
          $btn.text('🌟 Aplicar todas las sugerencias').css('opacity', '1').prop('disabled', false);
          
          // Invalidate cache for all affected URLs
          items.forEach(item => {
            const cacheKey = 'seosi_wp_cache_' + encodeURIComponent(item.url);
            localStorage.removeItem(cacheKey);
            item.score = null;
            item.problems = null;
            item.results = null;
          });

          renderWpPostsList(wpIsLocal);
          updateWpSummaryKpi();
          lazyAnalyzeWpContent(wpIsLocal);
        }
      );
    } else {
      // Standard Mode (Single URL)
      if (!globalProblems || globalProblems.length === 0) return;
      
      const qws = globalProblems.slice(0, 3);
      const fixableQws = qws.filter(q => q.supports_autofix === true && q.id !== 'llms_txt_present' && q.id !== 'llms_full_txt_present');
      
      if (fixableQws.length === 0) {
        alert('Las sugerencias sugeridas actualmente requieren acción manual (puedes hacer click en "Auto-fix" en cada problema para ver los detalles).');
        return;
      }

      $btn.prop('disabled', true).css('opacity', '0.7');

      const analyzedUrl = ($('#seosi-url-input').val() || SEOSI.post_url || '').replace(/\/$/, '');
      
      const fixesData = fixableQws.map(q => ({
        id: q.id,
        module: q.module,
        url: analyzedUrl
      }));

      executeSequentialAutofixes(
        fixesData,
        function(current, total) {
          $btn.text('⏳ Aplicando (' + current + '/' + total + ')...');
        },
        function(result) {
          $btn.text('🌟 Aplicar todas las sugerencias').css('opacity', '1').prop('disabled', false);
          
          // Simular click para re-analizar la URL
          $('#seosi-analyze-btn-dash').click();
        }
      );
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
        activeModule = mod;
        $('#seosi-prob-section-title').text('Problemas por Prioridad: Resumen General');
        applyProblemsFilters();
        return;
      }

      if ( mod === 'recommendations' ) {
        $('.nav-item').removeClass('active');
        $(this).addClass('active');
        $('#view-sitemap').hide();
        $('#view-resumen').hide();
        $('#view-recommendations').fadeIn('fast');
        activeModule = mod;
        loadAIRecommendations();
        return;
      }

      if ( ! lastDashboardResult ) return;

      $('.nav-item').removeClass('active');
      $(this).addClass('active');
      $('#view-sitemap').hide();
      $('#view-recommendations').hide();
      $('#view-resumen').show();

      activeModule = mod;

      $('#seosi-prob-section-title').text('Problemas por Prioridad: ' + (moduleNames[mod] || mod));

      applyProblemsFilters();
    });

    $analyzeBtn.on('click', function (e) {
      e.preventDefault();

      const url = $urlInput.val().trim();
      if ( ! url && currentScanScope !== 'posts' && currentScanScope !== 'pages' ) return;

      // ESCENARIO 1: Solo esta página (Flujo original intacto)
      if (currentScanScope === 'single') {
        $analyzeBtn.prop('disabled', true).text('⏳');
        $('#seosi-last-analyzed').text('Analizando...');
        showMainUIAnalysisLoading();

        $.ajax({
          url:    SEOSI.ajax_url,
          method: 'POST',
          data: {
            action:  'seosi_analyze',
            nonce:   SEOSI.nonce,
            url:     url,
            keyword: '',
          },
          success: function (res) {
            if ( ! res.success ) {
              showDashboardError(res.data?.message ?? 'Error desconocido');
              return;
            }
            renderDashboardResults(res.data);

            // Guardar análisis en LocalStorage para evitar pérdida al cambiar de pestaña
            try {
              localStorage.setItem('seosi_last_result', JSON.stringify(res.data));
              localStorage.setItem('seosi_last_url', url);
              localStorage.setItem('seosi_last_kwd', '');
              
              // Invalidar caché de recomendaciones de IA para esta URL
              const cacheKey = 'seosi_ai_cache_' + encodeURIComponent(url);
              localStorage.removeItem(cacheKey);
            } catch(e) {
              console.error('[SEOSI] Error al guardar en localStorage:', e);
            }
          },
          error: function () {
            showDashboardError('Error de conexión.');
          },
          complete: function () {
            $analyzeBtn.prop('disabled', false).text('🔍');
            $('#seosi-export-btn').prop('disabled', false);
          },
        });
        return;
      }

      // ESCENARIOS DE LOTE: Sitemap, Posts, Páginas
      // 1. Deshabilitar UI
      $analyzeBtn.prop('disabled', true).text('⏳');
      $('#seosi-last-analyzed').text('Descubriendo recursos...');

      // 2. Transición instantánea y suave al Explorador de Sitio / Sitemap
      $('.nav-item').removeClass('active');
      $('.nav-item[data-module="sitemap"]').addClass('active');
      $('#view-resumen').hide();
      $('#view-sitemap').fadeIn('fast');

      // 3. Establecer la URL del sitemap en el Explorador si aplica
      if (currentScanScope === 'sitemap') {
        $('#seosi-sitemap-url-input').val(url);
      }

      $('#seosi-sitemap-scan-status').text('⏳ Descubriendo páginas de la opción seleccionada...').fadeIn('fast');

      // 4. Llamada AJAX centralizada de descubrimiento
      $.ajax({
        url: SEOSI.ajax_url,
        method: 'POST',
        data: {
          action: 'seosi_discover_resources',
          nonce: SEOSI.nonce,
          scope: currentScanScope,
          url: url
        },
        success: function(res) {
          if (res.success && res.data && Array.isArray(res.data.urls)) {
            // Cargar en el estado del sitemap
            sitemapState.sitemap_url = (currentScanScope === 'sitemap') ? url : SEOSI.home_url;
            sitemapState.urls = res.data.urls.map(item => ({
              url: item.url,
              lastmod: item.lastmod || '—',
              score: null,
              issues: null
            }));
            
            saveSitemapState();
            renderSitemapTable();

            $('#seosi-sitemap-scan-status').text('✓ Páginas descubiertas con éxito.').fadeOut(3000);

            // 5. UX PREMIUM: Iniciar análisis masivo automáticamente en 1.5s
            setTimeout(function() {
              const selectedIndices = sitemapState.urls.map((_, i) => i);
              if (selectedIndices.length > 0) {
                analyzeUrlsInBatch(selectedIndices, '');
              }
            }, 1500);
          } else {
            $('#seosi-sitemap-scan-status').html('<span style="color:var(--red)">✗ ' + escHtml(res.data?.message || 'No se pudieron descubrir recursos.') + '</span>');
          }
        },
        error: function() {
          $('#seosi-sitemap-scan-status').html('<span style="color:var(--red)">✗ Error de conexión al descubrir recursos.</span>');
        },
        complete: function() {
          $analyzeBtn.prop('disabled', false).text('🔍');
          // Resetear el scope selector a 'single' para siguientes búsquedas individuales
          $('.scope-item[data-scope="single"]').click();
        }
      });
    });

    // Dropdown toggle click
    $('#seosi-export-btn').on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if ($(this).prop('disabled')) return;
      $('#seosi-export-menu').fadeToggle('fast');
    });

    // Close dropdown on click outside
    $(document).on('click', function(e) {
      if (!$(e.target).closest('.seosi-export-dropdown-container').length) {
        $('#seosi-export-menu').fadeOut('fast');
      }
    });

    // Handle format selection click
    $(document).on('click', '.seosi-dropdown-item', function (e) {
      e.preventDefault();
      const format = $(this).data('format');
      const url = $urlInput.val().trim();
      if (!url) return;

      const $menu = $('#seosi-export-menu');
      $menu.fadeOut('fast');

      // Visual feedback on button
      const $btn = $('#seosi-export-btn');
      const originalText = $btn.find('span').first().text();
      $btn.prop('disabled', true).find('span').first().text('⏳...');

      $.ajax({
        url:    SEOSI.ajax_url,
        method: 'POST',
        data: {
          action:  'seosi_export_report',
          nonce:   SEOSI.nonce,
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

    $(document).on('click', '#seosi-action-plan-btn', function(e) {
      e.preventDefault();
      const $btn = $(this);

      if (!globalProblems || globalProblems.length === 0) {
        alert('Primero debes analizar una URL para ver los problemas.');
        return;
      }

      $btn.prop('disabled', true).text('Generando...');
      
      const filteredProblems = globalProblems.filter(function(p) { return p.severity === 'critical' || p.severity === 'warning'; });
      const analyzedUrl = $('#seosi-url-input').val().trim() || SEOSI.post_url || '';

      $.ajax({
        url: SEOSI.ajax_url,
        method: 'POST',
        data: {
          action: 'seosi_generate_action_plan',
          nonce: SEOSI.nonce,
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

  // ── Meta Box ────────────────────────────────────────────────────────────────
  if ( $('#seosi-analyze-btn').length ) {
    $('#seosi-analyze-btn').on('click', function (e) {
      e.preventDefault();

      const $btn    = $(this);
      const keyword = $('#seosi-keyword-input').val().trim();
      const url     = SEOSI.post_url || '';

      if ( ! url ) return;

      $btn.prop('disabled', true).text('Analizando...');

      $.ajax({
        url:    SEOSI.ajax_url,
        method: 'POST',
        data: {
          action:   'seosi_analyze',
          nonce:    SEOSI.nonce,
          url:      url,
          keyword:  keyword,
          post_id:  SEOSI.post_id || 0,
        },
        success: function (res) {
          if ( res.success ) {
            const score = res.data.global_score ?? '—';
            $('#seosi-metabox-result')
              .html('<p style="color:#22c55e">✓ Análisis completado. Score: ' + escHtml(String(score)) + '</p>')
              .show();
          } else {
            $('#seosi-metabox-result')
              .html('<p style="color:#ef4444">✗ ' + escHtml(res.data?.message ?? 'Error') + '</p>')
              .show();
          }
        },
        error: function () {
          $('#seosi-metabox-result')
            .html('<p style="color:#ef4444">✗ Error de conexión.</p>')
            .show();
        },
        complete: function () {
          $btn.prop('disabled', false).text('Analizar');
        },
      });
    });
  }

  // ── Modals para Soluciones y Auto-Fix ─────────────────────────────────────────
  if ($('body').find('#seosi-modal-overlay').length === 0) {
    $('body').append(
      '<div id="seosi-modal-overlay" class="seosi-modal-overlay" style="display:none;">' +
        '<div class="seosi-modal">' +
          '<div class="seosi-modal-header">' +
            '<h3 id="seosi-modal-title">Título</h3>' +
            '<button type="button" id="seosi-modal-close">&times;</button>' +
          '</div>' +
          '<div id="seosi-modal-body" class="seosi-modal-body"></div>' +
          '<div class="seosi-modal-footer">' +
            '<button type="button" id="seosi-modal-action-btn" class="btn-autofix" style="display:none;">Aplicar</button>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  $('#seosi-modal-close').on('click', function() {
    $('#seosi-modal-overlay').fadeOut('fast');
  });

  $(document).on('click', '.btn-sol', function() {
    const id = $(this).data('id');
    const module = $(this).data('module');
    const desc = $(this).data('desc');
    
    $('#seosi-modal-title').text('Solución Sugerida');
    $('#seosi-modal-body').html('<div style="text-align:center;">Cargando solución...</div>');
    $('#seosi-modal-action-btn').hide();
    $('#seosi-modal-overlay').fadeIn('fast');

    $.ajax({
      url: SEOSI.ajax_url,
      method: 'POST',
      data: {
        action: 'seosi_get_solution',
        nonce: SEOSI.nonce,
        check_id: id,
        module: module,
        desc: desc
      },
      success: function(res) {
        if (res.success) {
          $('#seosi-modal-body').html(res.data.solution_html);
        } else {
          $('#seosi-modal-body').html('<p style="color:red;">Error: ' + res.data.message + '</p>');
        }
      }
    });
  });

  $(document).on('click', '.btn-autofix', function() {
    if ($(this).attr('id') === 'seosi-modal-action-btn') return; 
    
    const id = $(this).data('id');
    const module = $(this).data('module');
    const url = $(this).data('url');
    
    $('#seosi-modal-title').text('Auto-Fix');
    $('#seosi-modal-body').html('<div style="text-align:center;">Analizando requerimientos para fix...</div>');
    $('#seosi-modal-action-btn').hide().data('id', id).data('module', module).data('url', url);
    $('#seosi-modal-overlay').fadeIn('fast');

    $.ajax({
      url: SEOSI.ajax_url,
      method: 'POST',
      data: {
        action: 'seosi_autofix_info',
        nonce: SEOSI.nonce,
        check_id: id,
        module: module,
        url: url
      },
      success: function(res) {
        if (res.success) {
          let html = '<p>' + escHtml(res.data.description) + '</p>';
          if (res.data.requires_input) {
            res.data.fields.forEach(function(f) {
              html += '<div style="margin-top:10px;">';
              html += '<label style="display:block;margin-bottom:5px;font-weight:bold;">' + escHtml(f.label) + '</label>';
              const val = escHtml(f.value || '');
              if (f.type === 'textarea') {
                html += '<textarea id="af-input-' + escHtml(f.name) + '" class="seosi-af-input" style="width:100%;height:150px;padding:8px;border:1px solid var(--border);border-radius:4px;font-family:monospace;resize:vertical;" placeholder="' + escHtml(f.placeholder || '') + '">' + val + '</textarea>';
              } else {
                html += '<input type="text" id="af-input-' + escHtml(f.name) + '" class="seosi-af-input" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:4px;" placeholder="' + escHtml(f.placeholder || '') + '" value="' + val + '">';
              }
              html += '</div>';
            });
          }
          $('#seosi-modal-body').html(html);
          $('#seosi-modal-action-btn').show().text(res.data.requires_input ? 'Aplicar con Datos' : 'Ejecutar Auto-Fix');
          
          if (res.data.supports_autogen) {
            if ($('#seosi-modal-autogen-btn').length === 0) {
              $('#seosi-modal-action-btn').before('<button type="button" id="seosi-modal-autogen-btn" class="btn-autofix" style="margin-right:10px; background-color: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">Auto-Generar (Recomendado)</button>');
            }
            $('#seosi-modal-autogen-btn').show().data('id', id).data('module', module).data('url', url);
          } else {
            $('#seosi-modal-autogen-btn').hide();
          }
        } else {
          $('#seosi-modal-body').html('<p style="color:red;">' + (res.data.message || 'Error desconocido') + '</p>');
        }
      }
    });
  });

  $('#seosi-modal-action-btn').on('click', function() {
    const $btn = $(this);
    const id = $btn.data('id');
    const module = $btn.data('module');
    const url = $btn.data('url');
    
    let inputData = {};
    $('.seosi-af-input').each(function() {
      const name = $(this).attr('id').replace('af-input-', '');
      inputData[name] = $(this).val();
    });

    $btn.prop('disabled', true).text('Ejecutando...');

    $.ajax({
      url: SEOSI.ajax_url,
      method: 'POST',
      data: {
        action: 'seosi_execute_autofix',
        nonce: SEOSI.nonce,
        check_id: id,
        module: module,
        url: url,
        input_data: JSON.stringify(inputData)
      },
      success: function(res) {
        if (res.success) {
          $('#seosi-modal-body').html('<p style="color:var(--green);font-weight:bold;">✓ ' + escHtml(res.data.message) + '</p>');
          $btn.hide();
          handleSuccessfulAutoFix(id);
        } else {
          $('#seosi-modal-body').append('<p style="color:var(--red);margin-top:10px;">Error: ' + escHtml(res.data.message) + '</p>');
        }
      },
      complete: function() {
        $btn.prop('disabled', false).text('Aplicar con Datos');
      }
    });
  });

  $(document).on('click', '#seosi-modal-autogen-btn', function() {
    const $btn = $(this);
    const id = $btn.data('id');
    const module = $btn.data('module');
    const url = $btn.data('url');
    
    $btn.prop('disabled', true).text('Generando...');
    $('#seosi-modal-action-btn').prop('disabled', true);

    $.ajax({
      url: SEOSI.ajax_url,
      method: 'POST',
      data: {
        action: 'seosi_execute_autofix',
        nonce: SEOSI.nonce,
        check_id: id,
        module: module,
        url: url,
        input_data: JSON.stringify({ autogenerate: 'true' })
      },
      success: function(res) {
        if (res.success) {
          $('#seosi-modal-body').html('<p style="color:var(--green);font-weight:bold;">✓ ' + escHtml(res.data.message) + '</p>');
          $btn.hide();
          $('#seosi-modal-action-btn').hide();
          handleSuccessfulAutoFix(id);
        } else {
          $('#seosi-modal-body').append('<p style="color:var(--red);margin-top:10px;">Error: ' + escHtml(res.data.message) + '</p>');
        }
      },
      complete: function() {
        $btn.prop('disabled', false).text('Auto-Generar (Recomendado)');
        $('#seosi-modal-action-btn').prop('disabled', false);
      }
    });
  });

  function handleSuccessfulAutoFix(id) {
    const currentUrl = $('#seosi-modal-action-btn').data('url');
    if (currentUrl) {
      const isWpItem = wpPosts.concat(wpPages).some(p => p.url === currentUrl);
      if (isWpItem) {
        refreshWpItem(currentUrl);
      }
    }

    $('#seosi-modal-body button[data-id="' + id + '"]').closest('div').css('opacity', '0.5').find('button').prop('disabled', true).text('✓ Resuelto');

    const $card = $('.problem-card').has('button[data-id="' + id + '"]');
    if ($card.length === 0) return;

    const isCrit = $card.hasClass('prob-card-crit');

    $card.fadeOut(400, function() {
      $(this).remove();
      if ($('.problem-card').length === 0) {
        $('#seosi-problems-list').html(
          '<div style="text-align:center;color:var(--green);padding:40px; background:var(--bg-card); border-radius:12px; border:1px solid var(--border);">🚀 ¡Felicidades! Tu estructura es perfecta y no hay problemas.</div>'
        );
      }
    });

    const fixedCheck = globalProblems.find(function(p) { return p.id === id; });
    globalProblems = globalProblems.filter(function(p) { return p.id !== id; });
    if (fixedCheck) {
      fixedCheck.passed = true;
      globalCorrectos.unshift(fixedCheck);
    }

    if (isCrit) {
      const $critCount = $('#seosi-count-crit');
      let val = parseInt($critCount.text()) || 0;
      $critCount.text(Math.max(0, val - 1));
    } else {
      const $warnCount = $('#seosi-count-warn');
      let val = parseInt($warnCount.text()) || 0;
      $warnCount.text(Math.max(0, val - 1));
    }
    const $passCount = $('#seosi-count-pass');
    let passVal = parseInt($passCount.text()) || 0;
    $passCount.text(passVal + 1);

    const minsToSubtract = isCrit ? 10 : 5;
    const $timeVal = $('#seosi-time-est');
    let currentMins = parseInt($timeVal.text()) || 0;
    let newMins = Math.max(0, currentMins - minsToSubtract);
    $timeVal.html(newMins + ' <span>min</span>');

    renderQuickWinsAndCorrectos(globalProblems, globalCorrectos);
    updateFilterChipCounts();
  }

  // ── SISTEMA DE TEMAS GLOBAL (Modo Día / Modo Noche / Automático) ──
  function initGlobalThemeSystem() {
    const $root = $('.seoi-dashboard-root');
    if (!$root.length) return;

    const $themeSelect = $('select[name="seosi_options[ui_theme]"]');

    // Sincronizar localStorage con el tema actual
    const currentTheme = $root.attr('data-theme') || 'dark';
    localStorage.setItem('seosi_theme', currentTheme);

    // Escuchar el cambio en el selector de la página de Ajustes
    if ($themeSelect.length) {
      $themeSelect.on('change', function () {
        const selectedTheme = $(this).val();
        $root.attr('data-theme', selectedTheme);
        localStorage.setItem('seosi_theme', selectedTheme);
      });
    }
  }

  initGlobalThemeSystem();

  // ── SITEMAP EXPLORER & PERSISTENCE INITIALIZATION ──

  let sitemapState = {
    sitemap_url: '',
    urls: []
  };

  function saveSitemapState() {
    try {
      localStorage.setItem('seosi_sitemap_state', JSON.stringify(sitemapState));
    } catch (e) {
      console.error('[SEOSI] Error al guardar sitemap en localStorage:', e);
    }
  }

  function restoreSitemapState() {
    try {
      const stateStr = localStorage.getItem('seosi_sitemap_state');
      if (stateStr) {
        const state = JSON.parse(stateStr);
        if (state && Array.isArray(state.urls)) {
          sitemapState = state;
          $('#seosi-sitemap-url-input').val(sitemapState.sitemap_url || SEOSI.home_url || '');
          renderSitemapTable();
          updateSelectAllCheckbox();
          return true;
        }
      }
    } catch (e) {
      console.error('[SEOSI] Error al restaurar sitemap de localStorage:', e);
    }
    return false;
  }

  function updateSelectAllCheckbox() {
    if (sitemapState.urls.length === 0) return;
    const allChecked = sitemapState.urls.every(item => item.selected !== false);
    $('#seosi-sitemap-select-all').prop('checked', allChecked);
  }

  function renderSitemapTable() {
    const $tbody = $('#seosi-sitemap-table-body');
    $tbody.empty();

    if (!sitemapState.urls || sitemapState.urls.length === 0) {
      $('#seosi-sitemap-controls-panel').hide();
      $('#seosi-sitemap-table-container').hide();
      return;
    }

    $('#seosi-sitemap-count-badge').text(sitemapState.urls.length);
    $('#seosi-sitemap-controls-panel').show();
    $('#seosi-sitemap-table-container').show();

    sitemapState.urls.forEach((item, index) => {
      const isSelected = item.selected !== false;
      const score = item.score;
      const issues = item.issues || [];

      // Score Pill
      let scoreHtml = '<span class="sitemap-score-pill score-none">—</span>';
      if (score !== null && score !== undefined) {
        let scoreClass = 'score-bad';
        if (score >= 80) scoreClass = 'score-good';
        else if (score >= 40) scoreClass = 'score-mid';
        scoreHtml = '<span class="sitemap-score-pill ' + scoreClass + '">' + score + '</span>';
      }

      // Diagnostics / Issues
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
            diagHtml += '<span class="sitemap-issue-indicator ' + badgeClass + '">' + escHtml(cleanIss) + '</span>';
          });
          diagHtml += '</div>';
        }
      }

      // Actions
      let actionHtml = '';
      if (score !== null && score !== undefined) {
        actionHtml = '<button type="button" class="btn-sitemap-detail" data-url="' + escHtml(item.url) + '">Detalle & Corregir 🛠️</button>';
      } else {
        actionHtml = '<button type="button" class="btn-sitemap-quick" data-url="' + escHtml(item.url) + '" data-index="' + index + '">Analizar</button>';
      }

      const rowHtml = '<tr data-index="' + index + '">' +
        '<td style="text-align:center; padding: 12px;"><input type="checkbox" class="sitemap-row-checkbox" ' + (isSelected ? 'checked' : '') + ' style="cursor:pointer;" /></td>' +
        '<td style="padding: 12px;"><a href="' + escHtml(item.url) + '" target="_blank" class="sitemap-url-link">' + escHtml(item.url) + '</a></td>' +
        '<td style="padding: 12px; font-size:12px; color:var(--text-muted);">' + escHtml(item.lastmod) + '</td>' +
        '<td class="sitemap-score-badge-cell" style="padding: 12px;">' + scoreHtml + '</td>' +
        '<td class="sitemap-diagnostics-cell" style="padding: 12px;">' + diagHtml + '</td>' +
        '<td style="text-align:right; padding: 12px;">' + actionHtml + '</td>' +
      '</tr>';

      $tbody.append(rowHtml);
    });

    // Show sitemap export button reactively if at least one URL has been analyzed
    const hasAnalyzed = sitemapState.urls.some(item => item.score !== null && item.score !== undefined);
    if (hasAnalyzed) {
      $('#seosi-sitemap-export-btn').fadeIn('fast');
    } else {
      $('#seosi-sitemap-export-btn').hide();
    }
  }

  let isBatchRunning = false;

  function analyzeUrlsInBatch(indices, keyword) {
    if (isBatchRunning || indices.length === 0) return;
    isBatchRunning = true;

    // UI Updates
    $('#seosi-sitemap-discover-btn').prop('disabled', true);
    $('#seosi-sitemap-batch-btn').prop('disabled', true).text('⏳ Procesando...');
    $('#seosi-sitemap-clear-btn').prop('disabled', true);
    $('.sitemap-row-checkbox, #seosi-sitemap-select-all').prop('disabled', true);
    $('.btn-sitemap-quick, .btn-sitemap-detail').prop('disabled', true);
    
    // Progress UI
    const total = indices.length;
    let completed = 0;
    $('#seosi-sitemap-progress-count').text('0/' + total);
    $('#seosi-sitemap-progress-percent').text('0%');
    $('#seosi-sitemap-progress-bar-fill').css('width', '0%');
    $('#seosi-sitemap-progress-wrap').slideDown('fast');

    const urlsToAnalyze = indices.map(idx => sitemapState.urls[idx].url);

    // Create Batch Job
    $.ajax({
      url: SEOSI.ajax_url,
      method: 'POST',
      data: {
        action: 'seosi_batch_create',
        nonce: SEOSI.nonce,
        urls: urlsToAnalyze,
        keyword: keyword
      },
      success: function(res) {
        if (!res.success) {
          alert('Error al iniciar el lote: ' + (res.data?.message || 'Desconocido'));
          resetBatchUI();
          return;
        }

        const jobId = res.data.job_id;
        sitemapState.job_id = jobId;
        saveSitemapState();
        let currentIndex = 0;

        function processNext() {
          if (currentIndex >= total) {
            resetBatchUI();
            return;
          }

          const targetIndex = indices[currentIndex];
          const item = sitemapState.urls[targetIndex];
          const $row = $('.sitemap-table tbody tr[data-index="' + targetIndex + '"]');
          
          $row.addClass('sitemap-loading-row');
          $row.find('.sitemap-score-badge-cell').html('<span class="sitemap-score-pill score-none">⏳</span>');
          $row.find('.sitemap-diagnostics-cell').html('<span style="color:var(--accent-blue)">Analizando...</span>');

          $.ajax({
            url: SEOSI.ajax_url,
            method: 'POST',
            data: {
              action: 'seosi_batch_analyze_url',
              nonce: SEOSI.nonce,
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

              saveSitemapState();
              updateRowUI(targetIndex);
            },
            error: function() {
              $row.removeClass('sitemap-loading-row');
              item.score = 0;
              item.issues = ['Error de red al conectar con el servidor.'];
              saveSitemapState();
              updateRowUI(targetIndex);
            },
            complete: function() {
              completed++;
              currentIndex++;
              
              const percent = Math.round((completed / total) * 100);
              $('#seosi-sitemap-progress-count').text(completed + '/' + total);
              $('#seosi-sitemap-progress-percent').text(percent + '%');
              $('#seosi-sitemap-progress-bar-fill').css('width', percent + '%');

              setTimeout(processNext, 500);
            }
          });
        }

        processNext();
      },
      error: function() {
        alert('Error al conectar con el servidor para crear el lote.');
        resetBatchUI();
      }
    });
  }

  function updateRowUI(idx) {
    const item = sitemapState.urls[idx];
    const $row = $('.sitemap-table tbody tr[data-index="' + idx + '"]');
    if ($row.length === 0) return;

    const score = item.score;
    const issues = item.issues || [];

    // Score Pill
    let scoreClass = 'score-bad';
    if (score >= 80) scoreClass = 'score-good';
    else if (score >= 40) scoreClass = 'score-mid';
    const scoreHtml = '<span class="sitemap-score-pill ' + scoreClass + '">' + score + '</span>';
    $row.find('.sitemap-score-badge-cell').html(scoreHtml);

    // Diagnostics
    let diagHtml = '';
    if (issues.length === 0) {
      diagHtml = '<span style="color:var(--green); font-weight: 600;">✓ Sin problemas</span>';
    } else {
      diagHtml = '<div style="display:flex; flex-direction:column; gap:4px; font-size:11px;">';
      issues.forEach(iss => {
        const cleanIss = iss.replace(/^\[.*?\]\s*/, '');
        const isCritical = iss.toLowerCase().includes('crítico') || iss.toLowerCase().includes('critical') || iss.toLowerCase().includes('h1') || iss.toLowerCase().includes('meta title') || iss.toLowerCase().includes('robots');
        const badgeClass = isCritical ? 'crit' : 'warn';
        diagHtml += '<span class="sitemap-issue-indicator ' + badgeClass + '">' + escHtml(cleanIss) + '</span>';
      });
      diagHtml += '</div>';
    }
    $row.find('.sitemap-diagnostics-cell').html(diagHtml);

    // Actions button
    const actionHtml = '<button type="button" class="btn-sitemap-detail" data-url="' + escHtml(item.url) + '">Detalle & Corregir 🛠️</button>';
    $row.find('td:last-child').html(actionHtml);
  }

  function resetBatchUI() {
    isBatchRunning = false;
    $('#seosi-sitemap-discover-btn').prop('disabled', false);
    $('#seosi-sitemap-batch-btn').prop('disabled', false).text('⚡ Iniciar Análisis Masivo');
    $('#seosi-sitemap-clear-btn').prop('disabled', false);
    $('.sitemap-row-checkbox, #seosi-sitemap-select-all').prop('disabled', false);
    $('.btn-sitemap-quick, .btn-sitemap-detail').prop('disabled', false);
    setTimeout(function() {
      $('#seosi-sitemap-progress-wrap').slideUp('fast');
    }, 3000);
  }

  function initSitemapExplorer() {
    // 1. Discover sitemap
    $('#seosi-sitemap-discover-btn').on('click', function(e) {
      e.preventDefault();
      const sitemapUrl = $('#seosi-sitemap-url-input').val().trim();
      if (!sitemapUrl) {
        alert('Por favor, ingresa una URL válida.');
        return;
      }

      $('#seosi-sitemap-discover-btn').prop('disabled', true);
      $('#seosi-sitemap-scan-status').text('⏳ Descubriendo páginas del sitio...').fadeIn('fast');

      $.ajax({
        url: SEOSI.ajax_url,
        method: 'POST',
        data: {
          action: 'seosi_fetch_sitemap',
          nonce: SEOSI.nonce,
          url: sitemapUrl
        },
        success: function(res) {
          if (res.success && res.data && Array.isArray(res.data.urls)) {
            sitemapState.sitemap_url = sitemapUrl;
            sitemapState.urls = res.data.urls.map(item => ({
              url: item.url,
              lastmod: item.lastmod || '—',
              score: null,
              issues: null
            }));
            saveSitemapState();
            renderSitemapTable();
            $('#seosi-sitemap-scan-status').text('✓ Páginas descubiertas con éxito.').fadeOut(3000);
          } else {
            $('#seosi-sitemap-scan-status').html('<span style="color:var(--red)">✗ ' + escHtml(res.data?.message || 'No se pudo leer el sitemap.') + '</span>');
          }
        },
        error: function() {
          $('#seosi-sitemap-scan-status').html('<span style="color:var(--red)">✗ Error de conexión al escanear sitemap.</span>');
        },
        complete: function() {
          $('#seosi-sitemap-discover-btn').prop('disabled', false);
        }
      });
    });

    // 2. Select All Checkbox
    $(document).on('change', '#seosi-sitemap-select-all', function() {
      const isChecked = $(this).is(':checked');
      $('.sitemap-row-checkbox').prop('checked', isChecked);
      sitemapState.urls.forEach(item => {
        item.selected = isChecked;
      });
      saveSitemapState();
    });

    // 3. Row Checkbox
    $(document).on('change', '.sitemap-row-checkbox', function() {
      const index = $(this).closest('tr').data('index');
      sitemapState.urls[index].selected = $(this).is(':checked');
      saveSitemapState();
      updateSelectAllCheckbox();
    });

    // 4. Batch run click
    $('#seosi-sitemap-batch-btn').on('click', function(e) {
      e.preventDefault();
      const keyword = $('#seosi-sitemap-batch-keyword').val().trim();
      
      const selectedIndices = [];
      sitemapState.urls.forEach((item, index) => {
        if (item.selected !== false) {
          selectedIndices.push(index);
        }
      });

      if (selectedIndices.length === 0) {
        alert('Por favor, selecciona al menos una página para analizar.');
        return;
      }

      analyzeUrlsInBatch(selectedIndices, keyword);
    });

    // 5. Individual Quick Analyze Row Click
    $(document).on('click', '.btn-sitemap-quick', function(e) {
      e.preventDefault();
      const index = $(this).data('index');
      analyzeUrlsInBatch([index], '');
    });

    // 6. Clear Sitemap list
    $('#seosi-sitemap-clear-btn').on('click', function(e) {
      e.preventDefault();
      if (confirm('¿Estás seguro de que deseas limpiar la lista de páginas descubiertas?')) {
        sitemapState = { sitemap_url: '', urls: [] };
        saveSitemapState();
        renderSitemapTable();
      }
    });

    // 7. Detalle & Corregir Click (Hot Restore)
    $(document).on('click', '.btn-sitemap-detail', function(e) {
      e.preventDefault();
      const url = $(this).data('url');
      if (!url) return;

      // Set URL input
      $('#seosi-url-input').val(url);

      // Switch to Resumen
      $('.nav-item').removeClass('active');
      $('.nav-item[data-module="resumen"]').addClass('active');
      $('#view-sitemap').hide();
      $('#view-resumen').show();

      // Trigger detailed analysis
      $('#seosi-analyze-btn-dash').click();
    });

    // 8. Export Batch CSV click
    $('#seosi-sitemap-export-btn').on('click', function(e) {
      e.preventDefault();
      const jobId = sitemapState.job_id;
      if (!jobId) {
        alert('No hay un identificador de lote disponible para exportar.');
        return;
      }
      const downloadUrl = SEOSI.ajax_url + '?action=seosi_export_batch_csv&nonce=' + SEOSI.nonce + '&job_id=' + jobId;
      window.location.href = downloadUrl;
    });

    // Restore or auto-discover
    const hasState = restoreSitemapState();
    if (!hasState && SEOSI.home_url) {
      const defaultSitemap = SEOSI.home_url.replace(/\/$/, '') + '/wp-sitemap.xml';
      $('#seosi-sitemap-url-input').val(defaultSitemap);
      // Auto trigger discover for incredible first-load UX
      setTimeout(function() {
        $('#seosi-sitemap-discover-btn').click();
      }, 500);
    }
  }

  function restoreLastAnalysis() {
    try {
      const lastUrl = localStorage.getItem('seosi_last_url');
      const lastResultStr = localStorage.getItem('seosi_last_result');

      if (lastUrl) {
        $('#seosi-url-input').val(lastUrl);
      }

      if (lastResultStr) {
        const lastResult = JSON.parse(lastResultStr);
        if (lastResult) {
          renderDashboardResults(lastResult);
          $('#seosi-last-analyzed').text('Cargado desde el último análisis');
          $('#seosi-export-btn').prop('disabled', false);
        }
      }
    } catch (e) {
      console.error('[SEOSI] Error al restaurar el último análisis:', e);
    }
  }

  function initScanScopeDropdown() {
    const $trigger = $('#seosi-scope-btn');
    const $menu    = $('#seosi-scope-menu');
    const $banner  = $('#seosi-batch-warning-banner');

    if ( ! $trigger.length ) return;

    // Toggle Menú
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

    // Cerrar menú al hacer clic fuera
    $(document).on('click', function () {
      $menu.hide();
      $trigger.attr('aria-expanded', 'false');
    });

    // Cambio de opción
    $menu.on('click', '.scope-item', function (e) {
      e.preventDefault();
      const scope = $(this).data('scope');
      const icon  = $(this).data('icon');
      const label = $(this).find('strong').text();

      $('.scope-item').removeClass('active');
      $(this).addClass('active');

      // Cambiar texto de Trigger
      $trigger.find('.scope-icon').text(icon);
      $trigger.find('.scope-text').text(label);

      currentScanScope = scope;

      // Mostrar banner de aviso si es lote
      if (scope !== 'single') {
        $banner.slideDown('fast');
        
        // Auto-completar URL por defecto si está vacío
        const currentUrl = $('#seosi-url-input').val().trim();
        if (!currentUrl) {
          if (scope === 'sitemap') {
            $('#seosi-url-input').val(SEOSI.home_url ? SEOSI.home_url.replace(/\/$/, '') + '/wp-sitemap.xml' : '');
          } else if (scope === 'posts' || scope === 'pages') {
            $('#seosi-url-input').val(SEOSI.home_url || '');
          }
        }
      } else {
        $banner.slideUp('fast');
      }

      $menu.hide();
      $trigger.attr('aria-expanded', 'false');
    });
  }

  function initWpContentEvents() {
    // 1. Alternar entre Entradas y Páginas en el Optimizador de Contenido WP
    $(document).on('click', '.wp-toggle-btn', function(e) {
      e.preventDefault();
      $('.wp-toggle-btn').removeClass('active');
      $(this).addClass('active');
      wpActiveTab = $(this).data('type');
      renderWpPostsList(wpIsLocal);
    });

    // 2. Click en Auto-fix de un Post/Página en el listado de WP
    $(document).on('click', '.btn-trigger-wp-autofix', function(e) {
      e.preventDefault();
      const $btn = $(this);
      const idx = $btn.data('index');
      const type = $btn.data('type');
      const item = type === 'posts' ? wpPosts[idx] : wpPages[idx];
      if (!item) return;

      const problems = item.problems || [];
      const autoFixes = [];
      const manualFixes = [];

      problems.forEach(p => {
        if (p.supports_autofix === true) {
          if (p.id === 'llms_txt_present' || p.id === 'llms_full_txt_present') {
            manualFixes.push(p);
          } else {
            autoFixes.push(p);
          }
        }
      });

      if (autoFixes.length > 0) {
        $btn.prop('disabled', true).css('opacity', '0.7');
        
        const fixesData = autoFixes.map(f => ({
          id: f.id,
          module: f.module,
          url: item.url
        }));

        executeSequentialAutofixes(
          fixesData,
          function(current, total) {
            $btn.text('⏳ Reparando (' + current + '/' + total + ')...');
          },
          function(result) {
            // Invalidate cache
            const cacheKey = 'seosi_wp_cache_' + encodeURIComponent(item.url);
            localStorage.removeItem(cacheKey);

            refreshWpItem(item.url);

            if (manualFixes.length > 0) {
              openWpManualFixesModal(item, manualFixes, true);
            } else {
              $btn.text('✓ Optimizado').css({
                'background': 'rgba(34, 197, 94, 0.15)',
                'color': 'var(--green)',
                'border': '1px solid rgba(34, 197, 94, 0.25)'
              });
            }
          }
        );
      } else if (manualFixes.length > 0) {
        openWpManualFixesModal(item, manualFixes, false);
      }
    });
  }

  // ── AI Recommendations JavaScript Implementation ───────────────────────────
  let lastAIRecommendations = null;

  function initAIRecommendations() {
    // 1. Botón "TIP IA" redirecciona a Recomendaciones
    $(document).on('click', '#btn-tip-ia', function(e) {
      e.preventDefault();
      $('.nav-item[data-module="recommendations"]').click();
    });

    // 2. Filtro por Rol / Experto
    $(document).on('click', '#seosi-ai-filter-chips .filter-chip', function(e) {
      e.preventDefault();
      $('#seosi-ai-filter-chips .filter-chip').removeClass('active');
      $(this).addClass('active');
      
      const role = $(this).data('role');
      renderAIRecommendations(role);
    });

    // 3. Click en tarjeta de recomendación para colapsar/expandir
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

    // 4. Copiar código al portapapeles
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

    // 5. Simular corrección de IA
    $(document).on('click', '.btn-ai-simulate-fix', function(e) {
      e.preventDefault();
      const $btn = $(this);
      
      $btn.prop('disabled', true).text('⏳ Procesando...');
      
      setTimeout(function() {
        $btn.text('✓ ¡Aplicado con éxito!').css({
          'background': '#10b981',
          'box-shadow': 'none'
        });
        
        // Invalidar caché del navegador local
        const analyzedUrl = ($('#seosi-url-input').val() || SEOSI.post_url || '').replace(/\/$/, '');
        if (analyzedUrl) {
          const cacheKey = 'seosi_ai_cache_' + encodeURIComponent(analyzedUrl);
          localStorage.removeItem(cacheKey);
        }
      }, 1200);
    });
  }

  function loadAIRecommendations() {
    const analyzedUrl = ($('#seosi-url-input').val() || SEOSI.post_url || '').replace(/\/$/, '');
    const $list = $('#seosi-ai-rec-list');

    if (!analyzedUrl) {
      $list.html(
        '<div style="text-align:center; color:var(--text-muted); padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">' +
          '<span style="font-size:32px; display:block; margin-bottom:12px;">🔍</span>' +
          'Ingresa una URL en el panel superior y presiona analizar para que el grupo de expertos genere sugerencias detalladas.' +
        '</div>'
      );
      $('#seosi-ai-crit-count').text('—');
      $('#seosi-ai-count-all').text('0');
      $('#seosi-ai-count-uiux').text('0');
      $('#seosi-ai-count-seogeoaeo').text('0');
      $('#seosi-ai-count-wparch').text('0');
      return;
    }

    const cacheKey = 'seosi_ai_cache_' + encodeURIComponent(analyzedUrl);
    const cachedData = localStorage.getItem(cacheKey);
    if (cachedData) {
      try {
        const data = JSON.parse(cachedData);
        if (data && (Date.now() - data.timestamp) < 600000) { // 10 minutes cache
          lastAIRecommendations = data.results;
          processAIRecommendationsResults(lastAIRecommendations);
          return;
        }
      } catch(e) {}
    }

    // Loader premium
    $list.html(
      '<div style="text-align:center; padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">' +
        '<div class="ai-skeleton-loader" style="margin: 0 auto 16px auto; width: 48px; height: 48px; border: 4px solid var(--border); border-top-color: var(--accent-purple); border-radius: 50%; animation: spin 1s linear infinite;"></div>' +
        '<p style="color:var(--text-secondary); font-size:14px; font-weight:700; margin:0 0 4px 0;">Reuniendo al Grupo de Trabajo...</p>' +
        '<p style="color:var(--text-muted); font-size:12px; margin:0;">El Consultor UI-UX, Especialista SEO-GEO-AEO y el Arquitecto de WordPress están evaluando ' + escHtml(analyzedUrl) + '...</p>' +
      '</div>'
    );

    $.ajax({
      url: SEOSI.ajax_url,
      method: 'POST',
      data: {
        action: 'seosi_get_ai_recommendations',
        nonce: SEOSI.nonce,
        url: analyzedUrl
      },
      success: function(res) {
        if (res.success && res.data) {
          lastAIRecommendations = res.data;
          
          try {
            localStorage.setItem(cacheKey, JSON.stringify({
              timestamp: Date.now(),
              results: res.data
            }));
          } catch(e) {}
          
          processAIRecommendationsResults(lastAIRecommendations);
        } else {
          $list.html(
            '<div style="text-align:center; color:var(--red); padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">' +
              '✗ Error al obtener recomendaciones: ' + escHtml(res.data?.message || 'Error desconocido') +
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
  }

  function processAIRecommendationsResults(data) {
    const total = data.recommendations.length;
    const uiux = data.recommendations.filter(r => r.role === 'ui_ux').length;
    const seo = data.recommendations.filter(r => r.role === 'seo_geo_aeo').length;
    const wparch = data.recommendations.filter(r => r.role === 'wp_architect').length;

    $('#seosi-ai-confidence-val').text(data.meta.ai_confidence || '96');
    $('#seosi-ai-crit-count').text(total);

    $('#seosi-ai-count-all').text(total);
    $('#seosi-ai-count-uiux').text(uiux);
    $('#seosi-ai-count-seogeoaeo').text(seo);
    $('#seosi-ai-count-wparch').text(wparch);

    const activeRole = $('#seosi-ai-filter-chips .filter-chip.active').data('role') || 'all';
    renderAIRecommendations(activeRole);
  }

  function renderAIRecommendations(roleFilter) {
    const $list = $('#seosi-ai-rec-list');
    $list.empty();

    if (!lastAIRecommendations || !lastAIRecommendations.recommendations || lastAIRecommendations.recommendations.length === 0) {
      $list.html('<div style="text-align:center; color:var(--text-muted); padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">Sin recomendaciones de IA.</div>');
      return;
    }

    let listHtml = '';
    const filtered = roleFilter === 'all' 
      ? lastAIRecommendations.recommendations 
      : lastAIRecommendations.recommendations.filter(r => r.role === roleFilter);

    if (filtered.length === 0) {
      $list.html('<div style="text-align:center; color:var(--text-muted); padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">No hay recomendaciones para este experto.</div>');
      return;
    }

    filtered.forEach(item => {
      const isCrit = item.severity === 'critical';
      const sevLabel = isCrit ? 'Crítico' : 'Advertencia';
      const sevClass = isCrit ? 'crit' : 'warn';
      
      let roleBorderColor = '#3b82f6'; // UIUX
      if (item.role === 'seo_geo_aeo') roleBorderColor = '#10b981'; // SEO
      if (item.role === 'wp_architect') roleBorderColor = '#8b5cf6'; // WP

      listHtml += `
        <div class="ai-rec-card" data-role="${item.role}" style="background:var(--bg-card); border:1px solid var(--border); border-left: 5px solid ${roleBorderColor}; border-radius:var(--radius); padding:20px; transition:all 0.3s ease; box-shadow:var(--shadow-sm); cursor:pointer; margin-bottom:16px;">
          <div class="ai-rec-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px;">
            <div>
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span class="sitemap-issue-indicator ${sevClass}">${sevLabel}</span>
                <span style="font-size:11px; font-weight:700; color:var(--text-muted); background:var(--bg-secondary); padding:2px 8px; border-radius:4px; border:1px solid var(--border); text-transform:uppercase;">
                  ${escHtml(item.role_label)}
                </span>
              </div>
              <h3 style="font-size:16px; font-weight:800; color:var(--text-primary); margin:0 0 6px 0;">${escHtml(item.title)}</h3>
            </div>
            <span class="ai-rec-toggle-arrow" style="font-size:14px; color:var(--text-muted); transition:transform 0.2s ease; display:inline-block;">▼</span>
          </div>
          
          <p style="color:var(--text-secondary); font-size:13px; line-height:1.5; margin:8px 0 0 0;">
            ${escHtml(item.friction)}
          </p>

          <div class="ai-rec-details-drawer" style="display:none; margin-top:16px; border-top:1px dashed var(--border); padding-top:16px;">
            <div style="margin-bottom:12px;">
              <strong style="font-size:12px; color:var(--text-primary); display:block; margin-bottom:4px;">💡 Solución Recomendada:</strong>
              <p style="font-size:13px; color:var(--text-secondary); margin:0 0 4px 0; font-weight:700;">${escHtml(item.solution_title)}</p>
              <p style="font-size:13px; color:var(--text-secondary); margin:0;">${escHtml(item.solution_desc)}</p>
            </div>

            <div style="margin-bottom:12px;">
              <strong style="font-size:12px; color:var(--text-primary); display:block; margin-bottom:4px;">📊 Justificación y Estándar:</strong>
              <p style="font-size:12px; color:var(--text-muted); margin:0; line-height:1.4;">${escHtml(item.justification)}</p>
            </div>

            <div class="ai-rec-code-box-wrap" style="position:relative; margin-top:12px; border-radius:var(--radius-sm); overflow:hidden; background:#1e293b; border:1px solid #334155;">
              <div style="background:#0f172a; color:#94a3b8; font-size:11px; font-weight:700; padding:6px 12px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #334155;">
                <span>CÓDIGO SUGERIDO (${item.code_lang.toUpperCase()})</span>
                <button type="button" class="btn-copy-code" data-code="${escAttr(item.code_content)}" style="background:transparent; border:none; color:#3b82f6; cursor:pointer; font-weight:bold; font-size:11px; padding:2px 6px; border-radius:3px; transition:all 0.2s;">Copiar</button>
              </div>
              <pre style="margin:0; padding:12px; overflow-x:auto; font-family:Consolas, Monaco, 'Courier New', monospace; font-size:12px; color:#f8fafc; line-height:1.5;"><code>${escHtml(item.code_content)}</code></pre>
            </div>

            <div style="margin-top:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
              <span style="font-size:12px; color:var(--green); font-weight:700; display:flex; align-items:center; gap:4px;">
                <span>📈</span> Impacto Técnico: ${escHtml(item.impact)}
              </span>
              <button type="button" class="btn-ai-simulate-fix" data-id="${item.id}" style="background:linear-gradient(135deg, var(--accent-purple), #9b72e8); color:#fff; border:none; padding:6px 14px; border-radius:4px; font-size:12px; font-weight:700; cursor:pointer; box-shadow:0 2px 4px rgba(155, 114, 232, 0.25); transition:transform 0.2s;">
                ✨ Aplicar Sugerencia
              </button>
            </div>
          </div>
        </div>
      `;
    });

    $list.html(listHtml);
  }

  // Restore detailed dashboard analysis and initialize sitemap explorer
  restoreLastAnalysis();
  initSitemapExplorer();
  initScanScopeDropdown();
  initWpContentEvents();
  initAIRecommendations();

});

