<?php
/**
 * BSAS\Services\ReportService
 * Generates an ultra-compact, print-to-PDF ready HTML report page
 * optimized for standard A4 and Letter physical page dimensions.
 */

namespace BaloaStructureAuditorSEO\Services;

if ( ! defined( 'ABSPATH' ) ) exit;

class ReportService {

    /**
     * Renders the free compact HTML report.
     *
     * @param array  $results Full analysis results array.
     * @param string $url     Analyzed URL.
     * @return string Renders the full HTML document.
     */
    public static function render( array $results, string $url ): string {
        $locale = function_exists( 'determine_locale' ) ? determine_locale() : ( function_exists( 'get_locale' ) ? get_locale() : 'es_ES' );
        $lang   = strtolower( substr( (string) $locale, 0, 2 ) );
        $date     = function_exists( 'wp_date' ) ? wp_date( 'd/m/Y H:i' ) : gmdate( 'd/m/Y H:i' );
        $domain   = wp_parse_url( $url, PHP_URL_HOST ) ?? $url;
        $keyword  = $results['keyword']['keyword'] ?? '—';

        $scores = [
            __( 'Estructura HTML', 'baloa-structure-auditor-seo' ) => $results['html']['score']        ?? null,
            __( 'Keyword Scoring', 'baloa-structure-auditor-seo' ) => $results['keyword']['score']     ?? null,
            __( 'Schema.org', 'baloa-structure-auditor-seo' )      => $results['schema']['score']      ?? null,
            __( 'Legibilidad', 'baloa-structure-auditor-seo' )     => $results['readability']['score'] ?? null,
            __( 'Metatags', 'baloa-structure-auditor-seo' )        => $results['metatags']['score']    ?? null,
            __( 'Enlaces e Imágenes', 'baloa-structure-auditor-seo' ) => $results['links']['score']     ?? null,
        ];

        $valid_scores = array_filter( $scores, fn($s) => $s !== null );
        $global = count( $valid_scores ) > 0
            ? (int) round( array_sum( $valid_scores ) / count( $valid_scores ) )
            : 0;

        $sections_data = [
            [ 'title' => __( 'Estructura HTML', 'baloa-structure-auditor-seo' ),  'data' => $results['html']        ?? null ],
            [ 'title' => __( 'Keyword Scoring', 'baloa-structure-auditor-seo' ), 'data' => $results['keyword']      ?? null ],
            [ 'title' => __( 'Schema.org', 'baloa-structure-auditor-seo' ),      'data' => $results['schema']       ?? null ],
            [ 'title' => __( 'Legibilidad', 'baloa-structure-auditor-seo' ),     'data' => $results['readability']  ?? null ],
            [ 'title' => __( 'Metatags', 'baloa-structure-auditor-seo' ),       'data' => $results['metatags']     ?? null ],
            [ 'title' => __( 'Enlaces e Imágenes', 'baloa-structure-auditor-seo' ),  'data' => $results['links']    ?? null ],
        ];

        $all_issues   = [];
        $all_warnings = [];
        $all_passed   = [];
        foreach ( $sections_data as $sec ) {
            if ( ! $sec['data'] || isset( $sec['data']['error'] ) ) continue;
            foreach ( $sec['data']['issues']   ?? [] as $i ) { $all_issues[]   = [ 'section' => $sec['title'], 'text' => $i ]; }
            foreach ( $sec['data']['warnings'] ?? [] as $w ) { $all_warnings[] = [ 'section' => $sec['title'], 'text' => $w ]; }
            foreach ( $sec['data']['passed']   ?? [] as $p ) { $all_passed[]   = [ 'section' => $sec['title'], 'text' => $p ]; }
        }

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $lang ?: 'es' ); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( sprintf( /* translators: %s: domain name */ __( 'Reporte SEO — %s', 'baloa-structure-auditor-seo' ), $domain ) ); ?></title>
<?php
wp_register_style( 'baloa-pdf-report', BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/pdf-report.css', [], BALOA_STRUCTURE_AUDITOR_SEO_VERSION );
wp_print_styles( 'baloa-pdf-report' );
?>
</head>
<body>

<button class="theme-btn" id="themeToggle" aria-label="Cambiar tema">
  <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
  <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
</button>

<div class="report-container">

  <!-- 📄 PÁGINA 1: Diagnóstico General y Cuadrícula Slim -->
  <section class="pdf-page">
    <div class="page-body">
      <!-- Slim Header -->
      <div class="slim-header">
        <div class="header-left">
          <span class="badge-audit"><?php esc_html_e( 'REPORTE DE AUDITORÍA SEO', 'baloa-structure-auditor-seo' ); ?></span>
          <h1 class="domain-title"><?php echo esc_html( $domain ); ?></h1>
          <p class="url-desc"><?php echo esc_html( $url ); ?></p>
          <?php if ( $keyword !== '—' ): ?>
            <span class="kwd-badge">🗝️ <?php echo esc_html( $keyword ); ?></span>
          <?php endif; ?>
        </div>
        
        <div class="header-right">
          <div class="global-score-badge" style="border: 2px solid <?php echo esc_attr( self::score_hex($global) ); ?>; background: <?php echo esc_attr( self::score_hex($global) ); ?>0a;">
            <span class="score-num" style="color: <?php echo esc_attr( self::score_hex($global) ); ?>;"><?php echo esc_html( $global ); ?></span>
            <span class="score-label"><?php esc_html_e( 'PUNTUACIÓN GLOBAL', 'baloa-structure-auditor-seo' ); ?></span>
          </div>
        </div>
      </div>

      <!-- KPI Grid -->
      <h2 class="section-title"><span class="accent-bar"></span><?php esc_html_e( 'Resumen por Categorías', 'baloa-structure-auditor-seo' ); ?></h2>
      <div class="kpi-grid">
        <?php foreach ( $scores as $label => $score ):
          if ( $score === null ) continue;
          $color = self::score_hex( $score );
        ?>
        <div class="kpi-card-compact">
          <div class="kpi-card-header">
            <span class="kpi-label"><?php echo esc_html( $label ); ?></span>
            <strong class="kpi-score" style="color: <?php echo esc_attr( $color ); ?>;"><?php echo esc_html( $score ); ?>%</strong>
          </div>
          <div class="kpi-progress-bar-bg">
            <div class="kpi-progress-bar-fill" style="width: <?php echo esc_attr( $score ); ?>%; background: <?php echo esc_attr( $color ); ?>;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Executive Diagnosis Panel -->
      <div class="diagnosis-panel" style="border-left: 4px solid <?php echo esc_attr( self::score_hex($global) ); ?>;">
        <h3 class="panel-title"><?php esc_html_e( 'Diagnóstico Ejecutivo', 'baloa-structure-auditor-seo' ); ?></h3>
        <p class="panel-text">
          <?php echo esc_html( sprintf( /* translators: %d: global score */ __( 'Este sitio web presenta una puntuación general de salud estructural de SEO de %d/100.', 'baloa-structure-auditor-seo' ), $global ) ); ?>
          <?php if ( $global >= 80 ): ?>
            <?php esc_html_e( 'La estructura de la página cumple sólidamente con las directrices de indexabilidad estructural y SEO técnico de WordPress. Solo se sugieren pequeños ajustes para optimizar al máximo su visibilidad.', 'baloa-structure-auditor-seo' ); ?>
          <?php elseif ( $global >= 50 ): ?>
            <?php esc_html_e( 'Se detectaron varios problemas moderados que restringen directamente la visibilidad en buscadores orgánicos. Recomendamos aplicar las mejoras sugeridas, en especial en metatags y jerarquía de encabezados, para evitar pérdidas de tráfico.', 'baloa-structure-auditor-seo' ); ?>
          <?php else: ?>
            <?php esc_html_e( 'La página presenta fallos estructurales graves que dificultan su correcta indexación en los motores de búsqueda. Se requiere una acción técnica correctiva inmediata aplicando las correcciones automáticas.', 'baloa-structure-auditor-seo' ); ?>
          <?php endif; ?>
        </p>
        
        <div class="diagnosis-stats">
          <div class="stat-pill"><span class="dot red"></span><strong><?php echo count( $all_issues ); ?></strong> <?php esc_html_e( 'Críticos', 'baloa-structure-auditor-seo' ); ?></div>
          <div class="stat-pill"><span class="dot orange"></span><strong><?php echo count( $all_warnings ); ?></strong> <?php esc_html_e( 'Advertencias', 'baloa-structure-auditor-seo' ); ?></div>
          <div class="stat-pill"><span class="dot green"></span><strong><?php echo count( $all_passed ); ?></strong> <?php esc_html_e( 'Correctos', 'baloa-structure-auditor-seo' ); ?></div>
        </div>
      </div>
    </div>
    
    <div class="page-footer">
      <span><?php esc_html_e( 'Auditoría generada el:', 'baloa-structure-auditor-seo' ); ?> <?php echo esc_html( $date ); ?> · Baloa Structure Auditor v2.0.0</span>
      <span><?php esc_html_e( 'Página 1 de 3', 'baloa-structure-auditor-seo' ); ?></span>
    </div>
  </section>

  <!-- 📄 PÁGINA 2: Plan de Acción Técnico -->
  <section class="pdf-page">
    <div class="page-body">
      <h2 class="section-title"><span class="accent-bar bg-red"></span><?php esc_html_e( 'Plan de Acción y Prioridades Técnicas', 'baloa-structure-auditor-seo' ); ?></h2>
      <p class="section-subtitle"><?php esc_html_e( 'Prioridades consolidadas que requieren atención correctiva para asegurar la visibilidad del sitio.', 'baloa-structure-auditor-seo' ); ?></p>
      
      <div class="action-grid">
        <!-- Columna Izquierda: Críticos -->
        <div class="action-col">
          <h3 class="col-title color-red">🚨 <?php esc_html_e( 'Problemas Críticos', 'baloa-structure-auditor-seo' ); ?> (<?php echo count( $all_issues ); ?>)</h3>
          <div class="issues-list">
            <?php if ( empty( $all_issues ) ): ?>
              <div class="empty-notice green">✓ <?php esc_html_e( 'No se encontraron problemas críticos.', 'baloa-structure-auditor-seo' ); ?></div>
            <?php else: ?>
              <?php foreach ( array_slice( $all_issues, 0, 8 ) as $item ): ?>
                <div class="issue-card critical">
                  <span class="badge-role red"><?php echo esc_html( $item['section'] ); ?></span>
                  <p class="issue-text"><?php echo esc_html( $item['text'] ); ?></p>
                </div>
              <?php endforeach; ?>
              <?php if ( count( $all_issues ) > 8 ): ?>
                <div style="font-size:10px; color:var(--text-muted); text-align:center; margin-top:2px;">+<?php echo count( $all_issues ) - 8; ?> <?php esc_html_e( 'problemas adicionales', 'baloa-structure-auditor-seo' ); ?></div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Columna Derecha: Advertencias -->
        <div class="action-col">
          <h3 class="col-title color-orange">⚠️ <?php esc_html_e( 'Advertencias', 'baloa-structure-auditor-seo' ); ?> (<?php echo count( $all_warnings ); ?>)</h3>
          <div class="issues-list">
            <?php if ( empty( $all_warnings ) ): ?>
              <div class="empty-notice green">✓ <?php esc_html_e( 'No se encontraron advertencias.', 'baloa-structure-auditor-seo' ); ?></div>
            <?php else: ?>
              <?php foreach ( array_slice( $all_warnings, 0, 8 ) as $item ): ?>
                <div class="issue-card warning">
                  <span class="badge-role orange"><?php echo esc_html( $item['section'] ); ?></span>
                  <p class="issue-text"><?php echo esc_html( $item['text'] ); ?></p>
                </div>
              <?php endforeach; ?>
              <?php if ( count( $all_warnings ) > 8 ): ?>
                <div style="font-size:10px; color:var(--text-muted); text-align:center; margin-top:2px;">+<?php echo count( $all_warnings ) - 8; ?> <?php esc_html_e( 'advertencias adicionales', 'baloa-structure-auditor-seo' ); ?></div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="page-footer">
      <span><?php esc_html_e( 'Auditoría generada el:', 'baloa-structure-auditor-seo' ); ?> <?php echo esc_html( $date ); ?> · Baloa Structure Auditor v2.0.0</span>
      <span><?php esc_html_e( 'Página 2 de 3', 'baloa-structure-auditor-seo' ); ?></span>
    </div>
  </section>

  <!-- 📄 PÁGINA 3: Elementos Correctos y Conclusiones -->
  <section class="pdf-page">
    <div class="page-body">
      <h2 class="section-title"><span class="accent-bar bg-green"></span><?php esc_html_e( 'Auditorías Aprobadas y Aspectos Destacados', 'baloa-structure-auditor-seo' ); ?></h2>
      <p class="section-subtitle"><?php esc_html_e( 'Elementos estructurales verificados con éxito que demuestran un buen cumplimiento técnico.', 'baloa-structure-auditor-seo' ); ?></p>
      
      <div class="passed-container-grid">
        <?php if ( empty( $all_passed ) ): ?>
          <p style="color:var(--text-muted)"><?php esc_html_e( 'No se registraron auditorías aprobadas en este análisis.', 'baloa-structure-auditor-seo' ); ?></p>
        <?php else: ?>
          <div class="passed-list-columns">
            <?php foreach ( array_slice( $all_passed, 0, 16 ) as $item ): ?>
              <div class="passed-card-compact">
                <span class="check-ico-green">✓</span>
                <div class="passed-card-desc">
                  <strong><?php echo esc_html( $item['section'] ); ?></strong>
                  <span><?php echo esc_html( $item['text'] ); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if ( count( $all_passed ) > 16 ): ?>
            <div style="font-size:10px; color:var(--text-muted); text-align:center; margin-top:8px;">+<?php echo count( $all_passed ) - 16; ?> <?php esc_html_e( 'comprobaciones correctas adicionales', 'baloa-structure-auditor-seo' ); ?></div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      
      <!-- Conclusion Panel -->
      <div class="diagnosis-panel" style="margin-top: 24px; border-left: 4px solid var(--green);">
        <h4 style="font-size:12.5px; font-weight:700; color:var(--text); margin-bottom:4px;"><?php esc_html_e( 'Conclusión de la Auditoría', 'baloa-structure-auditor-seo' ); ?></h4>
        <p style="font-size:11.5px; color:var(--text-secondary); line-height:1.45; margin:0;">
          <?php esc_html_e( 'Este reporte técnico detallado ha sido generado por el motor de análisis estructural del plugin base gratuito. Las comprobaciones evalúan la correcta jerarquía y semántica HTML, validación de schemas de datos estructurados, optimización básica de metatags y estado de enlaces. Se sugiere ejecutar este análisis de forma periódica.', 'baloa-structure-auditor-seo' ); ?>
        </p>
      </div>
    </div>

    <div class="page-footer">
      <div style="display:flex; justify-content:space-between; width:100%;">
        <span><?php esc_html_e( 'Auditor Técnico:', 'baloa-structure-auditor-seo' ); ?> Brian Baloa (bbaloa)</span>
        <span><?php esc_html_e( 'Página 3 de 3', 'baloa-structure-auditor-seo' ); ?></span>
      </div>
    </div>
  </section>

</div>

<?php
wp_register_script( 'baloa-pdf-report-js', BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/pdf-report.js', [], BALOA_STRUCTURE_AUDITOR_SEO_VERSION, true );
wp_print_scripts( 'baloa-pdf-report-js' );
?>

</body>
</html>
        <?php
        return ob_get_clean();
    }

    private static function score_hex( int $score ): string {
        if ( $score >= 80 ) return '#10b981';
        if ( $score >= 50 ) return '#f59e0b';
        return '#f43f5e';
    }
}
