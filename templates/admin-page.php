<?php
/**
 * Template: Admin Page - Dark Theme Dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'seosi_options', [] );
$theme   = $options['ui_theme'] ?? 'dark';
$is_premium = \SEOSI\Core\Plugin::get_instance()->get_license()->is_premium();
?>
<div class="seoi-dashboard-root" data-theme="<?php echo esc_attr( $theme ); ?>">
<aside class="sidebar">
  <div class="logo">
    <div class="logo-icon" aria-hidden="true">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2L20 7V17L12 22L4 17V7L12 2Z" stroke="white" stroke-width="1.5" fill="url(#seosi-logo-grad)"/>
        <defs>
          <linearGradient id="seosi-logo-grad" x1="4" y1="2" x2="20" y2="22" gradientUnits="userSpaceOnUse">
            <stop stop-color="#4f8ef7"/>
            <stop offset="1" stop-color="#9b72e8"/>
          </linearGradient>
        </defs>
      </svg>
    </div>
    <div class="logo-text">
      <strong>SEO</strong>
      <span>Structure Inspector</span>
    </div>
  </div>

  <a class="nav-item active" href="#" data-module="resumen">
    <span class="nav-left"><span class="nav-icon">📊</span> Resumen</span>
  </a>
  <a class="nav-item" href="#" data-module="sitemap">
    <span class="nav-left"><span class="nav-icon">🗺️</span> Explorar Sitio <?php if ( ! $is_premium ) : ?><span style="font-size: 8px; margin-left: 6px; background: #f59e0b; color: #fff; padding: 1px 4px; border-radius: 3px; font-weight: 800;">PRO</span><?php endif; ?></span>
  </a>
  <a class="nav-item" href="#" data-module="html">
    <span class="nav-left"><span class="nav-icon">🔧</span> SEO Estructural</span>
    <span class="score-badge" data-score-for="html">—</span>
  </a>
  <a class="nav-item" href="#" data-module="llms">
    <span class="nav-left"><span class="nav-icon">🌐</span> GEO / LLMs <?php if ( ! $is_premium ) : ?><span style="font-size: 8px; margin-left: 6px; background: #f59e0b; color: #fff; padding: 1px 4px; border-radius: 3px; font-weight: 800;">PRO</span><?php endif; ?></span>
    <span class="score-badge" data-score-for="llms">—</span>
  </a>
  <a class="nav-item" href="#" data-module="aeo">
    <span class="nav-left"><span class="nav-icon">📝</span> AEO / Contenido <?php if ( ! $is_premium ) : ?><span style="font-size: 8px; margin-left: 6px; background: #f59e0b; color: #fff; padding: 1px 4px; border-radius: 3px; font-weight: 800;">PRO</span><?php endif; ?></span>
    <span class="score-badge" data-score-for="aeo">—</span>
  </a>
  <a class="nav-item" href="#" data-module="cwv">
    <span class="nav-left"><span class="nav-icon">⚡</span> Core Web Vitals <?php if ( ! $is_premium ) : ?><span style="font-size: 8px; margin-left: 6px; background: #f59e0b; color: #fff; padding: 1px 4px; border-radius: 3px; font-weight: 800;">PRO</span><?php endif; ?></span>
    <span class="score-badge" data-score-for="cwv">—</span>
  </a>
  <a class="nav-item" href="#" data-module="schema">
    <span class="nav-left"><span class="nav-icon">🔗</span> Schema</span>
    <span class="score-badge" data-score-for="schema">—</span>
  </a>
  <a class="nav-item" href="#" data-module="metatags">
    <span class="nav-left"><span class="nav-icon">🏷️</span> Metatags</span>
    <span class="score-badge" data-score-for="metatags">—</span>
  </a>
  <a class="nav-item" href="#" data-module="links">
    <span class="nav-left"><span class="nav-icon">🔗</span> Enlaces</span>
    <span class="score-badge" data-score-for="links">—</span>
  </a>
  <a class="nav-item" href="#" data-module="readability">
    <span class="nav-left"><span class="nav-icon">📖</span> Legibilidad</span>
    <span class="score-badge" data-score-for="readability">—</span>
  </a>
  <a class="nav-item" href="#" data-module="keyword">
    <span class="nav-left"><span class="nav-icon">⭐</span> EEAT</span>
    <span class="score-badge" data-score-for="keyword">—</span>
  </a>
  <a class="nav-item" href="#" data-module="recommendations" style="border-left: 2px dashed var(--accent-purple);">
    <span class="nav-left"><span class="nav-icon">🧠</span> Recomendaciones IA <?php if ( ! $is_premium ) : ?><span style="font-size: 8px; margin-left: 6px; background: var(--accent-purple); color: #fff; padding: 1px 4px; border-radius: 3px; font-weight: 800;">PRO</span><?php endif; ?></span>
    <span class="score-badge" style="background:var(--accent-purple); color:#fff" data-score-for="recommendations">✨</span>
  </a>

  <div class="nav-divider"></div>

  <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=seosi-settings' ) ); ?>">
    <span class="nav-left"><span class="nav-icon">⚙️</span> Configuración</span>
  </a>
</aside>

<div class="main">
  <header class="topbar">
    <div class="url-input-wrap">
      <div class="seosi-scope-dropdown">
        <button type="button" id="seosi-scope-btn" class="scope-dropdown-trigger" aria-haspopup="true" aria-expanded="false">
          <span class="scope-icon">📄</span> <span class="scope-text">Esta página</span> <span class="scope-arrow">▼</span>
        </button>
        <div id="seosi-scope-menu" class="scope-dropdown-menu">
          <div class="scope-item active" data-scope="single" data-icon="📄">
            <span class="item-icon">📄</span>
            <div class="item-details">
              <strong>Esta página</strong>
              <span>Analiza solo la URL especificada</span>
            </div>
          </div>
          <div class="scope-item <?php echo $is_premium ? '' : 'scope-item--pro-locked'; ?>" data-scope="sitemap" data-icon="🌐">
            <span class="item-icon">🌐</span>
            <div class="item-details">
              <strong>Sitio completo <?php if ( ! $is_premium ) : ?><span style="font-size: 8px; margin-left: 4px; background: #f59e0b; color: #fff; padding: 1px 4px; border-radius: 3px; font-weight: 800;">PRO</span><?php endif; ?></strong>
              <span>Escanea todas las páginas del sitemap.xml ⏳</span>
            </div>
          </div>
          <div class="scope-item" data-scope="posts" data-icon="📝">
            <span class="item-icon">📝</span>
            <div class="item-details">
              <strong>Últimos posts</strong>
              <span>Analiza las últimas 20 entradas locales</span>
            </div>
          </div>
          <div class="scope-item" data-scope="pages" data-icon="📑">
            <span class="item-icon">📑</span>
            <div class="item-details">
              <strong>Últimas páginas</strong>
              <span>Analiza las últimas 20 páginas estáticas</span>
            </div>
          </div>
        </div>
      </div>
      <input id="seosi-url-input" class="url-input" type="url" value="" placeholder="https://tudominio.com/slug/" />
    </div>
    <button id="seosi-analyze-btn-dash" class="btn btn-primary" type="button">Analizar</button>
    <span id="seosi-last-analyzed" class="topbar-meta"></span>
    
    <!-- Premium Export Dropdown -->
    <div class="seosi-export-dropdown-container" style="position: relative; display: inline-block;">
      <button type="button" class="btn btn-secondary" id="seosi-export-btn" disabled style="display: inline-flex; align-items: center; gap: 6px;">
        <span>⬆ Exportar</span>
        <span class="dropdown-caret" style="font-size: 9px; opacity: 0.7;">▼</span>
      </button>
      <div class="seosi-dropdown-menu" id="seosi-export-menu" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 6px; z-index: 1000; min-width: 220px;">
        <div class="seosi-dropdown-item <?php echo $is_premium ? '' : 'seosi-dropdown-item--pro-locked'; ?>" data-format="print">
          <span class="item-icon">📄</span>
          <div class="item-desc">
            <strong>Imprimir / Guardar PDF <?php if ( ! $is_premium ) : ?><span style="font-size: 8px; margin-left: 4px; background: #f59e0b; color: #fff; padding: 1px 4px; border-radius: 3px; font-weight: 800;">PRO</span><?php endif; ?></strong>
            <span>Genera vista y abre impresión</span>
          </div>
        </div>
        <div class="seosi-dropdown-item <?php echo $is_premium ? '' : 'seosi-dropdown-item--pro-locked'; ?>" data-format="html">
          <span class="item-icon">🌐</span>
          <div class="item-desc">
            <strong>HTML Interactivo <?php if ( ! $is_premium ) : ?><span style="font-size: 8px; margin-left: 4px; background: #f59e0b; color: #fff; padding: 1px 4px; border-radius: 3px; font-weight: 800;">PRO</span><?php endif; ?></strong>
            <span>Descarga el reporte completo</span>
          </div>
        </div>
        <div class="seosi-dropdown-item <?php echo $is_premium ? '' : 'seosi-dropdown-item--pro-locked'; ?>" data-format="action_plan">
          <span class="item-icon">⚡</span>
          <div class="item-desc">
            <strong>Plan de Acción (PDF) <?php if ( ! $is_premium ) : ?><span style="font-size: 8px; margin-left: 4px; background: #f59e0b; color: #fff; padding: 1px 4px; border-radius: 3px; font-weight: 800;">PRO</span><?php endif; ?></strong>
            <span>Soluciones paso a paso</span>
          </div>
        </div>
      </div>
    </div>
    
    <button type="button" class="btn btn-primary" id="seosi-compare-btn" disabled>⬆ Comparar</button>
  </header>

  <!-- Banner de Advertencia de Lote -->
  <div id="seosi-batch-warning-banner" class="seosi-warning-banner" style="display:none; margin: 10px 40px 0 40px;">
    <span class="warning-icon">⏳</span>
    <span class="warning-text">El escaneo masivo de sitio o recursos descubrirá y procesará múltiples páginas en lote. Esta acción requerirá un poco más de tiempo.</span>
  </div>

  <div class="content">
    <!-- VISTA: RESUMEN -->
    <div id="view-resumen" class="seosi-view">
      <div class="dash-kpis">
        <!-- 1. SEO Score -->
        <div class="kpi-card">
          <div class="kpi-title">SEO Score</div>
          <div class="score-circle-wrapper">
            <svg class="score-circle-svg" viewBox="0 0 90 90">
              <circle class="score-circle-bg" cx="45" cy="45" r="40"></circle>
              <circle class="score-circle-value" id="seosi-global-donut" cx="45" cy="45" r="40"></circle>
            </svg>
            <div class="score-circle-text">
              <div class="score-circle-big" id="seosi-global-score">—</div>
              <div class="score-circle-small">/100</div>
            </div>
          </div>
          <div class="kpi-footer good" id="seosi-global-status">Sin datos</div>
        </div>

        <!-- 2. Legibilidad -->
        <div class="kpi-card">
          <div class="kpi-title">Legibilidad</div>
          <div class="score-circle-text" style="position:relative; transform:none; top:0; left:0; margin-bottom:12px;">
            <div style="display:flex; align-items:baseline; justify-content:center; gap:4px">
              <span class="score-circle-big" id="seosi-read-score">—</span>
              <span class="score-circle-small">/100</span>
            </div>
          </div>
          <div class="kpi-footer good" id="seosi-read-status">Sin datos</div>
          <div class="sparkline" style="background:#f1f5f9; border-radius:4px; opacity:0.6"></div>
        </div>

        <!-- 3. IA / Visibilidad -->
        <div class="kpi-card">
          <div class="kpi-title">IA / Visibilidad</div>
          <div class="score-circle-text" style="position:relative; transform:none; top:0; left:0; margin-bottom:12px;">
            <div style="display:flex; align-items:baseline; justify-content:center; gap:4px">
              <span class="score-circle-big" id="seosi-ai-score">—</span>
              <span class="score-circle-small">/100</span>
            </div>
          </div>
          <div class="kpi-footer warning" id="seosi-ai-status">Sin datos</div>
          <div class="sparkline" style="background:#f1f5f9; border-radius:4px; opacity:0.6"></div>
        </div>

        <!-- 4. Problemas encontrados -->
        <div class="kpi-card" style="justify-content:flex-start">
          <div class="kpi-title" style="margin-bottom:0">Problemas encontrados</div>
          <div class="prob-stats-list">
            <div class="prob-stat-item">
              <div class="prob-stat-icon crit">!</div>
              <div class="prob-stat-val" id="seosi-count-crit">0</div>
              <span>Críticos</span>
            </div>
            <div class="prob-stat-item">
              <div class="prob-stat-icon warn">!</div>
              <div class="prob-stat-val" id="seosi-count-warn">0</div>
              <span>Advertencias</span>
            </div>
            <div class="prob-stat-item">
              <div class="prob-stat-icon pass">✓</div>
              <div class="prob-stat-val" id="seosi-count-pass">0</div>
              <span>Correctos</span>
            </div>
          </div>
        </div>

        <!-- 5. Tiempo estimado -->
        <div class="kpi-card" id="seosi-time-kpi-card" style="background:#f8fafc; align-items:center; justify-content:center;">
          <div class="time-kpi-icon">🕒</div>
          <div class="time-kpi-val" id="seosi-time-est">— <span>min</span></div>
          <div class="time-kpi-sub" id="seosi-time-sub">para resolver todo</div>
          <button type="button" id="seosi-action-plan-btn" class="btn-action-light">Ver plan de acción</button>
        </div>

        <!-- 5 (ALT). Estado de WordPress -->
        <div class="kpi-card" id="seosi-wp-kpi-card" style="display:none; align-items:center; justify-content:center; background:rgba(79, 142, 247, 0.05); border-color:rgba(79, 142, 247, 0.15);">
          <div class="wp-kpi-icon" style="font-size:24px; margin-bottom:8px;">🌐</div>
          <div class="wp-kpi-title" style="font-size:13px; font-weight:700; color:var(--text-primary); margin-bottom:4px;">Sitio WordPress</div>
          <div class="wp-kpi-val" id="seosi-wp-score-avg" style="font-size:28px; font-weight:800; text-align:center;">—</div>
          <div class="wp-kpi-sub" id="seosi-wp-status-text" style="text-align:center; font-size:11px; color:var(--text-muted);">Cargando contenido...</div>
        </div>
      </div>

      <div class="section-header">
        <div class="section-title" id="seosi-prob-section-title">Problemas por Prioridad: Resumen General</div>
      </div>

      <!-- NUEVO: Barra de Búsqueda y Filtros de Problemas -->
      <div class="seosi-problems-filter-bar">
        <div class="filter-chips">
          <button type="button" class="filter-chip active" data-severity="all">
            Todos <span class="chip-count" id="chip-count-all">0</span>
          </button>
          <button type="button" class="filter-chip chip-crit" data-severity="critical">
            Críticos <span class="chip-count" id="chip-count-crit">0</span>
          </button>
          <button type="button" class="filter-chip chip-warn" data-severity="warning">
            Advertencias <span class="chip-count" id="chip-count-warn">0</span>
          </button>
          <button type="button" id="btn-autofix-all-problems" class="filter-chip" style="background: rgba(16, 185, 129, 0.1); color: var(--green); border-color: rgba(16, 185, 129, 0.2); display: none; margin-left: 8px;">
            ✨ Aplicar todos los Auto-fixes
          </button>
        </div>
        <div class="problems-search-wrap">
          <span class="search-icon" aria-hidden="true">🔍</span>
          <input type="text" id="seosi-problems-search" placeholder="Buscar problema..." />
          <button type="button" id="seosi-search-clear" class="search-clear-btn" style="display: none;" title="Limpiar búsqueda">&times;</button>
        </div>
      </div>

      <!-- Contenedor con Scroll para la Lista de Problemas -->
      <div class="seosi-problems-scroll-container">
        <div id="seosi-problems-list">
          <div style="text-align:center; color:var(--text-muted); padding:40px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">
            Ingresa una URL y presiona analizar.
          </div>
        </div>
      </div>

      <div class="split-bottom">
        <div class="qw-card">
          <div class="qw-header" style="justify-content: space-between; display: flex; width: 100%; align-items: center;">
            <div style="display:flex; align-items:center; gap:8px;">
              <span class="qw-icon" id="seosi-qw-icon-symbol">⚡</span> 
              <span id="seosi-qw-title-text">Quick wins</span> (<span id="seosi-qw-count">0</span>)
            </div>
            <div id="seosi-wp-toggle-group" class="wp-toggle-group" style="display:none; gap:6px;">
              <button type="button" class="wp-toggle-btn active" data-type="posts">Entradas</button>
              <button type="button" class="wp-toggle-btn" data-type="pages">Páginas</button>
            </div>
          </div>
          <div class="qw-list" id="seosi-qw-list">
            <div style="color:var(--text-muted); font-size:13px">Sin quick wins sugeridos.</div>
          </div>
          <button type="button" class="btn-qw" id="btn-qw-apply" disabled>🌟 Aplicar todas las sugerencias</button>
        </div>

        <div class="ok-card">
          <div class="ok-header"><span class="ok-icon">✓</span> Correctos (<span id="seosi-ok-count">0</span>)</div>
          <div class="qw-list" id="seosi-ok-list">
             <div style="color:var(--text-muted); font-size:13px">Sin checks correctos.</div>
          </div>
        </div>
      </div>

      <div class="tip-card">
        <div class="tip-icon">✨</div>
        <div class="tip-content">
          <div class="tip-label">Tip IA</div>
          <div class="tip-text" id="seosi-tip-text">Completa los campos recomendados y mejorarás significativamente tu visibilidad.</div>
        </div>
        <button type="button" class="btn-tip" id="btn-tip-ia">Ver recomendaciones IA →</button>
      </div>

    </div> <!-- /view-resumen -->

    <!-- VISTA: EXPLORADOR DE SITIO / SITEMAP -->
    <div id="view-sitemap" class="seosi-view" style="display:none; position:relative;">
      <?php if ( ! $is_premium ) : ?>
      <div class="seosi-pro-lock-overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; min-height:450px; background:rgba(15,23,42,0.85); backdrop-filter:blur(6px); display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:10; border-radius:var(--radius); padding:40px; text-align:center;">
        <span style="font-size:64px; margin-bottom:16px;">🗺️</span>
        <h2 style="font-family:'Syne', sans-serif; font-size:24px; font-weight:800; color:#fff; margin:0 0 12px 0;">Explorador de Sitio Completo en Lote</h2>
        <p style="color:#94a3b8; font-size:14px; max-width:500px; margin:0 0 24px 0; line-height:1.6;">
            Escanea tu sitio completo en segundo plano y analiza el sitemap.xml en lote de forma automatizada. Requiere la versión PRO de SEO Structure Inspector.
        </p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seosi-settings' ) ); ?>" class="btn btn-primary" style="background:linear-gradient(135deg, var(--accent-purple), var(--accent-purple-bright)); border:none; padding:12px 28px; font-weight:600; text-decoration:none; border-radius:4px; color:#fff;">
            Activar Licencia PRO
        </a>
      </div>
      <?php endif; ?>
      <div class="sitemap-explorer-header" style="margin-bottom: 20px;">
        <h2 class="sitemap-explorer-title" style="font-size: 22px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px;">🗺️ Explorador de Sitio & Sitemap</h2>
        <p class="sitemap-explorer-desc" style="color: var(--text-secondary); font-size: 13px; line-height: 1.5;">Descubre todas las páginas de tu sitio escaneando el sitemap.xml. Analiza las URLs en lote para obtener un diagnóstico general o selecciona una para optimizarla a fondo.</p>
      </div>

      <!-- Tarjeta de Escaneo -->
      <div class="sitemap-scanner-card" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 20px;">
        <div class="sitemap-scanner-inputs" style="display: flex; gap: 12px; align-items: flex-end;">
          <div class="sitemap-input-wrap" style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
            <label for="seosi-sitemap-url-input" class="sitemap-input-label" style="font-size: 12px; font-weight: 700; color: var(--text-secondary);">URL del Sitemap o del Sitio</label>
            <input type="url" id="seosi-sitemap-url-input" class="url-input" placeholder="https://tudominio.com/wp-sitemap.xml" />
          </div>
          <button type="button" id="seosi-sitemap-discover-btn" class="btn btn-primary" style="height: 38px;">🔍 Descubrir Páginas</button>
        </div>
        <div id="seosi-sitemap-scan-status" class="sitemap-scan-status-msg" style="display:none; margin-top:12px; font-size:13px; font-weight:600; color:var(--text-secondary);"></div>
      </div>

      <!-- Panel de Control y Progreso Lote (Oculto inicialmente) -->
      <div id="seosi-sitemap-controls-panel" class="sitemap-controls-panel" style="display:none; background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 20px;">
        <div class="sitemap-summary-info" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          <span id="seosi-sitemap-summary-text" style="font-size: 14px; color: var(--text-primary);">Se encontraron <strong id="seosi-sitemap-count-badge" style="font-size: 16px; color: var(--accent-blue);">0</strong> páginas en el sitemap.</span>
          <div class="sitemap-batch-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <div class="sitemap-keyword-batch-wrap" style="display:flex; align-items:center; gap:8px;">
              <input type="text" id="seosi-sitemap-batch-keyword" class="url-input" style="width:180px; padding:8px 12px; font-size:12px;" placeholder="Palabra clave opcional" />
            </div>
            <button type="button" id="seosi-sitemap-batch-btn" class="btn btn-primary" style="background:var(--green)">⚡ Iniciar Análisis Masivo</button>
            <button type="button" id="seosi-sitemap-clear-btn" class="btn btn-secondary">Limpiar lista</button>
            <button type="button" id="seosi-sitemap-export-btn" class="btn btn-secondary" style="display: none; background: rgba(16, 185, 129, 0.1); color: var(--green); border-color: rgba(16, 185, 129, 0.2);">⬆ Exportar Lote (CSV)</button>
          </div>
        </div>

        <!-- Barra de Progreso Lote -->
        <div id="seosi-sitemap-progress-wrap" class="sitemap-progress-wrap" style="display:none; margin-top: 16px; border-top: 1px solid var(--border); padding-top: 16px;">
          <div class="sitemap-progress-header" style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px;">
            <span>Procesando páginas: <strong id="seosi-sitemap-progress-count" style="color: var(--text-primary);">0/0</strong></span>
            <span id="seosi-sitemap-progress-percent" style="color: var(--accent-blue);">0%</span>
          </div>
          <div class="sitemap-progress-bar-bg" style="width: 100%; height: 8px; background: var(--bg-secondary); border-radius: 4px; overflow: hidden;">
            <div id="seosi-sitemap-progress-bar-fill" class="sitemap-progress-bar-fill" style="width:0%; height: 100%; background: linear-gradient(135deg, var(--green), var(--green-dim)); transition: width 0.3s ease; border-radius: 4px;"></div>
          </div>
        </div>
      </div>

      <!-- Tabla de Páginas Descubiertas -->
      <div id="seosi-sitemap-table-container" class="sitemap-table-container" style="display:none; margin-top:16px;">
        <div class="sitemap-table-wrap" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm);">
          <table class="sitemap-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
            <thead>
              <tr style="background: var(--bg-secondary); border-bottom: 1px solid var(--border); font-weight: 700; color: var(--text-primary);">
                <th style="width:45px; text-align:center; padding: 12px;"><input type="checkbox" id="seosi-sitemap-select-all" checked style="cursor:pointer;" /></th>
                <th style="padding: 12px; font-weight: 700;">Página / URL</th>
                <th style="width:160px; padding: 12px; font-weight: 700;">Última Modificación</th>
                <th style="width:110px; text-align:center; padding: 12px; font-weight: 700;">Score SEO</th>
                <th style="padding: 12px; font-weight: 700;">Diagnóstico Rápido</th>
                <th style="width:250px; text-align:right; padding: 12px; font-weight: 700;">Acciones</th>
              </tr>
            </thead>
            <tbody id="seosi-sitemap-table-body">
              <!-- Renderizado dinámico desde JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div> <!-- /view-sitemap -->

    <!-- VISTA: RECOMENDACIONES IA -->
    <div id="view-recommendations" class="seosi-view" style="display:none; position:relative;">
      <?php if ( ! $is_premium ) : ?>
      <div class="seosi-pro-lock-overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; min-height:450px; background:rgba(15,23,42,0.85); backdrop-filter:blur(6px); display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:10; border-radius:var(--radius); padding:40px; text-align:center;">
        <span style="font-size:64px; margin-bottom:16px;">🧠</span>
        <h2 style="font-family:'Syne', sans-serif; font-size:24px; font-weight:800; color:#fff; margin:0 0 12px 0;">Recomendaciones del Grupo de Expertos con IA</h2>
        <p style="color:#94a3b8; font-size:14px; max-width:500px; margin:0 0 24px 0; line-height:1.6;">
            Recibe diagnósticos detallados, sugerencias y soluciones técnicas automáticas personalizadas en accesibilidad, WP Core y SEO mediante nuestro grupo de agentes expertos con IA.
        </p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seosi-settings' ) ); ?>" class="btn btn-primary" style="background:linear-gradient(135deg, var(--accent-purple), var(--accent-purple-bright)); border:none; padding:12px 28px; font-weight:600; text-decoration:none; border-radius:4px; color:#fff;">
            Activar Licencia PRO
        </a>
      </div>
      <?php endif; ?>
      <div class="recommendations-header" style="margin-bottom: 24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
          <h2 style="font-size: 22px; font-weight: 800; color: var(--text-primary); margin: 0 0 6px 0; display:flex; align-items:center; gap:8px;">
            <span>🧠</span> Recomendaciones del Grupo de Expertos
          </h2>
          <p style="color: var(--text-secondary); font-size: 13px; margin: 0; line-height: 1.5;">
            Análisis semántico consolidado de nuestro equipo experto impulsado por IA para auditar accesibilidad, visibilidad e infraestructura.
          </p>
        </div>
        
        <!-- Mock de Motor de IA -->
        <div class="ai-provider-selector-wrap" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 12px; display:flex; align-items:center; gap:8px;">
          <span style="font-size: 12px; font-weight:700; color:var(--text-secondary);">Motor Activo:</span>
          <select id="seosi-ai-provider-select" style="background:var(--bg-secondary); color:var(--text-primary); border:1px solid var(--border); border-radius:4px; padding:4px 8px; font-size:12px; cursor:pointer; font-weight:600;">
            <option value="default" selected>🤖 SEO-SI Expert Engine (Local)</option>
            <option value="openai" disabled>🔮 OpenAI GPT-4o (API Key Requerido)</option>
            <option value="gemini" disabled>♊ Google Gemini 1.5 Pro (API Key Requerido)</option>
            <option value="claude" disabled>🦉 Anthropic Claude 3.5 Sonnet (API Key Requerido)</option>
          </select>
        </div>
      </div>

      <!-- KPI Summary Cards for IA -->
      <div class="dash-kpis" style="margin-bottom: 24px;">
        <div class="kpi-card" style="padding: 16px; min-height: auto;">
          <div class="kpi-title" style="margin-bottom:4px;">Índice de Confianza IA</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--accent-purple); display:flex; align-items:baseline; gap:4px;">
            <span id="seosi-ai-confidence-val">96</span><span style="font-size:12px; color:var(--text-muted);">%</span>
          </div>
          <div style="font-size:11px; color:var(--green); margin-top:2px;">✓ Precisión Óptima</div>
        </div>
        <div class="kpi-card" style="padding: 16px; min-height: auto;">
          <div class="kpi-title" style="margin-bottom:4px;">Expertos Colaboradores</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--text-primary);">
            3 <span style="font-size:13px; font-weight:500; color:var(--text-secondary);">Activos</span>
          </div>
          <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">UI-UX | SEO | WP Architect</div>
        </div>
        <div class="kpi-card" style="padding: 16px; min-height: auto;">
          <div class="kpi-title" style="margin-bottom:4px;">Problemas Sugeridos</div>
          <div style="font-size: 24px; font-weight: 800; color: var(--red); display:flex; align-items:baseline; gap:4px;" id="seosi-ai-crit-container">
            <span id="seosi-ai-crit-count">—</span>
          </div>
          <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Por resolver e implementar</div>
        </div>
      </div>

      <!-- Barra de Filtros por Perfil Experto -->
      <div class="seosi-problems-filter-bar" style="margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div class="filter-chips" style="display:flex; gap:8px;" id="seosi-ai-filter-chips">
          <button type="button" class="filter-chip active" data-role="all">
            👥 Todos los Expertos (<span id="seosi-ai-count-all">0</span>)
          </button>
          <button type="button" class="filter-chip" data-role="ui_ux" style="border-left: 3px solid #3b82f6;">
            🎨 Consultor UI-UX (<span id="seosi-ai-count-uiux">0</span>)
          </button>
          <button type="button" class="filter-chip" data-role="seo_geo_aeo" style="border-left: 3px solid #10b981;">
            📈 SEO-GEO-AEO (<span id="seosi-ai-count-seogeoaeo">0</span>)
          </button>
          <button type="button" class="filter-chip" data-role="wp_architect" style="border-left: 3px solid #8b5cf6;">
            ⚙️ WP Architect (<span id="seosi-ai-count-wparch">0</span>)
          </button>
        </div>
        
        <div style="font-size: 11px; color: var(--text-muted); font-style:italic;">
          * Haz clic en una tarjeta para ver la solución técnica paso a paso.
        </div>
      </div>

      <!-- Contenedor de la Lista de Recomendaciones de IA -->
      <div id="seosi-ai-rec-container">
        <div id="seosi-ai-rec-list" style="display:flex; flex-direction:column; gap:16px;">
          <!-- Se renderiza dinámicamente desde JS -->
          <div style="text-align:center; color:var(--text-muted); padding:48px; background:var(--bg-card); border-radius:var(--radius); border:1px solid var(--border);">
            <span style="font-size:32px; display:block; margin-bottom:12px;">🔍</span>
            Ingresa una URL en el panel superior y presiona analizar para que el grupo de expertos genere sugerencias detalladas.
          </div>
        </div>
      </div>
    </div> <!-- /view-recommendations -->
  </div>
</div>
</div>
