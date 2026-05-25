<?php
/**
 * SEOSI\Ajax\Handlers
 * AJAX handlers for SEO Structure Inspector.
 * Separated from main file for better organization.
 */

namespace SEOSI\Ajax;

use SEOSI\Core\Capabilities;
use SEOSI\Services\AnalysisService;
use SEOSI\Services\FetcherService;
use SEOSI\Pro\Services\BatchAnalyzer;
use SEOSI\Pro\Services\PDFReport;
use SEOSI\Pro\Services\SitemapReader;
use SEOSI\Pro\Services\HistoryService;
use SEOSI\Core\ResultPresenter;

if ( ! defined( 'ABSPATH' ) ) exit;

class Handlers {

    const MAX_HTML_SIZE = 1048576; // 1MB max for manual HTML input

    public static function register_hooks(): void {
        add_action( 'wp_ajax_seosi_analyze', [ __CLASS__, 'analyze_url' ] );
        add_action( 'wp_ajax_seosi_fetch_sitemap', [ __CLASS__, 'fetch_sitemap' ] );
        add_action( 'wp_ajax_seosi_discover_resources', [ __CLASS__, 'discover_resources' ] );
        add_action( 'wp_ajax_seosi_batch_create', [ __CLASS__, 'batch_create' ] );
        add_action( 'wp_ajax_seosi_batch_analyze_url', [ __CLASS__, 'batch_analyze_url' ] );
        add_action( 'wp_ajax_seosi_batch_status', [ __CLASS__, 'batch_status' ] );
        add_action( 'wp_ajax_seosi_export_report', [ __CLASS__, 'export_report' ] );
        add_action( 'wp_ajax_seosi_export_batch_csv', [ __CLASS__, 'export_batch_csv' ] );
        add_action( 'wp_ajax_seosi_get_history', [ __CLASS__, 'get_history' ] );
        add_action( 'wp_ajax_seosi_get_solution', [ __CLASS__, 'get_solution' ] );
        add_action( 'wp_ajax_seosi_autofix_info', [ __CLASS__, 'autofix_info' ] );
        add_action( 'wp_ajax_seosi_execute_autofix', [ __CLASS__, 'execute_autofix' ] );
        add_action( 'wp_ajax_seosi_generate_action_plan', [ __CLASS__, 'generate_action_plan' ] );
        add_action( 'wp_ajax_seosi_get_applied_fixes', [ __CLASS__, 'get_applied_fixes' ] );
        add_action( 'wp_ajax_seosi_revert_single_fix', [ __CLASS__, 'revert_single_fix' ] );
        add_action( 'wp_ajax_seosi_revert_all_fixes', [ __CLASS__, 'revert_all_fixes' ] );
        add_action( 'wp_ajax_seosi_get_ai_recommendations', [ __CLASS__, 'get_ai_recommendations' ] );
    }

    // ── Security helpers ──────────────────────────────────────────────────────────

    private static function verify_request( ?string $capability = null ): void {
        $capability = $capability ?? Capabilities::analyze();
        check_ajax_referer( 'seosi_nonce', 'nonce' );

        if ( ! current_user_can( $capability ) ) {
            wp_send_json_error( [ 'message' => __( 'Permisos insuficientes.', 'seo-si' ) ], 403 );
        }
    }

    /**
     * Secures premium AJAX routes by verifying both general permissions and Pro license status.
     *
     * @param string|null $capability Required user capability.
     * @return void
     */
    private static function verify_premium_request( ?string $capability = null ): void {
        self::verify_request( $capability );

        $is_premium = \SEOSI\Core\Plugin::get_instance()->get_license()->is_premium();
        if ( ! $is_premium ) {
            wp_send_json_error( [
                'message' => __( 'Esta característica requiere la versión PRO de SEO Structure Inspector.', 'seo-si' ),
                'is_pro_required' => true
            ], 403 );
        }
    }

    private static function check_rate_limit( string $action, int $max_calls = 10, int $window_seconds = 60 ): bool {
        $user_id = get_current_user_id();
        $key     = 'seosi_rl_' . $action . '_' . $user_id;
        $current = (int) get_transient( $key );

        if ( $current >= $max_calls ) {
            return false;
        }

        set_transient( $key, $current + 1, $window_seconds );
        return true;
    }

    private static function sanitize_url( string $raw ): string {
        $url = esc_url_raw( trim( $raw ) );
        return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
    }

    // ── AJAX: analyze single URL ──────────────────────────────────────────────────

    public static function analyze_url(): void {
        self::verify_request();

        if ( ! self::check_rate_limit( 'analyze', 30, 300 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas solicitudes. Espera unos minutos.', 'seo-si' ) ], 429 );
        }

        $url         = self::sanitize_url( $_POST['url'] ?? '' );
        $keyword     = sanitize_text_field( $_POST['keyword'] ?? '' );
        $manual_html = isset( $_POST['manual_html'] ) ? trim( $_POST['manual_html'] ) : '';

        if ( ! $url ) {
            wp_send_json_error( [ 'message' => __( 'URL invalida o ausente.', 'seo-si' ) ] );
        }

        // Validate manual HTML size to prevent DoS
        if ( ! empty( $manual_html ) && strlen( $manual_html ) > self::MAX_HTML_SIZE ) {
            wp_send_json_error( [ 'message' => __( 'El HTML manual excede el tamaño máximo permitido (1MB).', 'seo-si' ) ] );
        }

        try {
            if ( ! empty( $manual_html ) ) {
                $html     = FetcherService::sanitize_manual_html( $manual_html );
                $strategy = 'manual';
            } else {
                $fetched = FetcherService::fetch_html( $url );
                if ( $fetched['error'] ) {
                    wp_send_json_error( [
                        'message'      => $fetched['error'],
                        'allow_manual' => true,
                    ] );
                }
                $html     = $fetched['html'];
                $strategy = $fetched['strategy'];
            }

            if ( empty( trim( (string) $html ) ) ) {
                wp_send_json_error( [ 'message' => __( 'HTML vacio.', 'seo-si' ), 'allow_manual' => true ] );
            }

            $api_key = sanitize_text_field( \SEOSI\Admin\Settings::get_option( 'pagespeed_api_key' ) );

            $results = AnalysisService::analyze( $html, $url, $keyword, $api_key );
        } catch ( \Throwable $e ) {
            error_log( '[SEOSI] analyze_url: ' . $e->getMessage() );
            wp_send_json_error( [ 'message' => __( 'Error interno durante el análisis.', 'seo-si' ) ] );
        }
        $result_array = $results->toArray();
        $result_array['strategy'] = $strategy;
        $result_array = ResultPresenter::localize_analysis_results( $result_array );

        // Detect WordPress and get recent posts/pages
        $result_array['wordpress_data'] = self::detect_wordpress_and_get_posts( $url, $html );

        // Secure transient cache for report generation (2 hours)
        $user_id = get_current_user_id();
        set_transient( 'seosi_rep_' . $user_id . '_' . md5( $url ), $result_array, 2 * HOUR_IN_SECONDS );

        // Save to history if post_id is provided
        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        if ( $post_id > 0 ) {
            HistoryService::save_snapshot( $post_id, $results, $keyword );
        }

        wp_send_json_success( $result_array );
    }

    // ── AJAX: fetch sitemap URLs ───────────────────────────────────────────────────

    public static function fetch_sitemap(): void {
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'sitemap', 10, 60 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas solicitudes. Espera un minuto.', 'seo-si' ) ], 429 );
        }

        $url = self::sanitize_url( $_POST['url'] ?? '' );
        if ( ! $url ) {
            wp_send_json_error( [ 'message' => __( 'URL invalida o ausente.', 'seo-si' ) ] );
        }

        $result = SitemapReader::discover( $url );

        if ( $result['error'] ) {
            wp_send_json_error( [ 'message' => $result['error'] ] );
        }

        wp_send_json_success( $result );
    }

    // ── AJAX: discover resources (sitemap, posts, pages) ──────────────────────────

    public static function discover_resources(): void {
        $scope = sanitize_key( $_POST['scope'] ?? 'single' );
        if ( $scope === 'sitemap' ) {
            self::verify_premium_request();
        } else {
            self::verify_request();
        }

        if ( ! self::check_rate_limit( 'discover_resources', 20, 60 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas solicitudes. Espera un momento.', 'seo-si' ) ], 429 );
        }

        $scope = sanitize_key( $_POST['scope'] ?? 'single' );
        $url   = self::sanitize_url( $_POST['url'] ?? '' );

        if ( $scope === 'sitemap' ) {
            if ( ! $url ) {
                wp_send_json_error( [ 'message' => __( 'URL inválida o ausente.', 'seo-si' ) ] );
            }
            $result = SitemapReader::discover( $url );
            if ( $result['error'] ) {
                wp_send_json_error( [ 'message' => $result['error'] ] );
            }
            wp_send_json_success( $result );
            return;
        }

        if ( in_array( $scope, [ 'posts', 'pages' ], true ) ) {
            $post_type = $scope === 'posts' ? 'post' : 'page';
            
            $cache_key = 'seosi_disc_' . $post_type;
            $cached    = get_transient( $cache_key );
            if ( is_array( $cached ) ) {
                wp_send_json_success( [ 'urls' => $cached, 'count' => count( $cached ), 'error' => null ] );
                return;
            }

            $args = [
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];

            $posts = get_posts( $args );
            $urls  = [];

            foreach ( $posts as $p ) {
                $permalink = get_permalink( $p->ID );
                if ( $permalink ) {
                    $urls[] = [
                        'url'     => esc_url_raw( $permalink ),
                        'lastmod' => get_the_modified_date( 'Y-m-d H:i:s', $p->ID ),
                        'title'   => get_the_title( $p->ID ),
                    ];
                }
            }

            set_transient( $cache_key, $urls, 900 );
            wp_send_json_success( [ 'urls' => $urls, 'count' => count( $urls ), 'error' => null ] );
            return;
        }

        wp_send_json_error( [ 'message' => __( 'Alcance de escaneo inválido.', 'seo-si' ) ] );
    }

    // ── AJAX: create batch job ────────────────────────────────────────────────────

    public static function batch_create(): void {
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'batch_create', 5, 300 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas lotes creados. Espera unos minutos.', 'seo-si' ) ], 429 );
        }

        $raw_urls = (array) ( $_POST['urls'] ?? [] );
        $keyword  = sanitize_text_field( $_POST['keyword'] ?? '' );

        $urls = array_values( array_filter( array_map( [ __CLASS__, 'sanitize_url' ], $raw_urls ) ) );
        $urls = array_slice( $urls, 0, 500 );

        if ( empty( $urls ) ) {
            wp_send_json_error( [ 'message' => __( 'No se recibieron URLs validas.', 'seo-si' ) ] );
        }

        $job_id = BatchAnalyzer::create_job( $urls, $keyword );
        wp_send_json_success( [ 'job_id' => $job_id, 'total' => count( $urls ) ] );
    }

    // ── AJAX: analyze single URL in batch ────────────────────────────────────────

    public static function batch_analyze_url(): void {
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'batch_url', 120, 300 ) ) {
            wp_send_json_error( [ 'message' => __( 'Limite de velocidad alcanzado. El batch continuara automaticamente.', 'seo-si' ) ], 429 );
        }

        $job_id  = sanitize_text_field( $_POST['job_id']  ?? '' );
        $url     = self::sanitize_url( $_POST['url'] ?? '' );
        $keyword = sanitize_text_field( $_POST['keyword'] ?? '' );

        if ( ! $job_id || ! $url ) {
            wp_send_json_error( [ 'message' => __( 'Datos insuficientes.', 'seo-si' ) ] );
        }

        if ( ! BatchAnalyzer::get_job( $job_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Job no encontrado.', 'seo-si' ) ], 404 );
        }

        $fetched = FetcherService::fetch_html( $url );
        if ( $fetched['error'] ) {
            BatchAnalyzer::store_result( $job_id, $url, [
                'url'      => $url,
                'strategy' => 'error',
                'error'    => $fetched['error'],
            ] );
            wp_send_json_success( [ 'status' => 'error', 'url' => $url, 'message' => $fetched['error'] ] );
            return;
        }

        try {
            $api_key = sanitize_text_field( \SEOSI\Admin\Settings::get_option( 'pagespeed_api_key' ) );
            $result  = AnalysisService::analyze( $fetched['html'], $url, $keyword, $api_key );
        } catch ( \Throwable $e ) {
            error_log( '[SEOSI] batch_analyze_url: ' . $e->getMessage() );
            wp_send_json_error( [ 'message' => __( 'Error interno durante el análisis.', 'seo-si' ) ] );
        }

        $result_array = $result->toArray();
        $result_array['strategy'] = $fetched['strategy'];

        BatchAnalyzer::store_result( $job_id, $url, $result_array );
        $summary = BatchAnalyzer::summarize( ResultPresenter::localize_analysis_results( $result_array ) );

        wp_send_json_success( [
            'status'  => 'ok',
            'url'     => $url,
            'summary' => $summary,
        ] );
    }

    // ── AJAX: get batch job status ────────────────────────────────────────────────

    public static function batch_status(): void {
        self::verify_premium_request();

        $job_id = sanitize_text_field( $_POST['job_id'] ?? '' );

        if ( ! $job_id ) {
            wp_send_json_error( [ 'message' => __( 'Job ID requerido.', 'seo-si' ) ] );
        }

        $job = BatchAnalyzer::get_job( $job_id );
        if ( ! $job ) {
            wp_send_json_error( [ 'message' => __( 'Job no encontrado.', 'seo-si' ) ], 404 );
        }

        wp_send_json_success( $job );
    }

    // ── AJAX: export PDF report ───────────────────────────────────────────────────

    public static function export_report(): void {
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'export', 20, 60 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas exportaciones. Espera un minuto.', 'seo-si' ) ], 429 );
        }

        $url = self::sanitize_url( $_POST['url'] ?? '' );
        if ( ! $url ) {
            wp_send_json_error( [ 'message' => __( 'URL inválida o ausente.', 'seo-si' ) ] );
        }

        $user_id = get_current_user_id();
        $transient_key = 'seosi_rep_' . $user_id . '_' . md5( $url );
        $results = get_transient( $transient_key );

        if ( ! $results || ! is_array( $results ) ) {
            // Attempt to re-run the analysis on the fly to avoid failing if transient expired!
            try {
                $fetched = FetcherService::fetch_html( $url );
                if ( ! $fetched['error'] ) {
                    $api_key = sanitize_text_field( \SEOSI\Admin\Settings::get_option( 'pagespeed_api_key' ) );
                    $analysis_obj = AnalysisService::analyze( $fetched['html'], $url, '', $api_key );
                    $results = $analysis_obj->toArray();
                    $results['strategy'] = $fetched['strategy'];
                    $results = ResultPresenter::localize_analysis_results( $results );
                    
                    // Cache it now
                    set_transient( $transient_key, $results, 2 * HOUR_IN_SECONDS );
                }
            } catch ( \Throwable $e ) {
                error_log( '[SEOSI] export_report fallback failed: ' . $e->getMessage() );
            }
        }

        if ( ! $results || ! is_array( $results ) ) {
            wp_send_json_error( [ 'message' => __( 'No se encontraron resultados de análisis recientes para esta URL y no se pudo re-analizar. Por favor, analízala de nuevo.', 'seo-si' ) ] );
        }

        $format = sanitize_key( $_POST['format'] ?? 'html' );

        if ( $format === 'action_plan' ) {
            // Return action plan HTML
            $problems = [];
            $modules = [ 'html', 'keyword', 'schema', 'readability', 'metatags', 'llms', 'aeo', 'links' ];
            
            // Reconstruct problems from the cached results
            foreach ( $modules as $mod ) {
                if ( isset( $results[$mod] ) ) {
                    $mod_data = $results[$mod];
                    
                    foreach ( $mod_data['issues'] ?? [] as $issue ) {
                        $problems[] = [
                            'id' => $mod . '_issue',
                            'module' => $mod,
                            'severity' => 'critical',
                            'title' => $issue,
                            'recommendation' => $issue,
                        ];
                    }
                    foreach ( $mod_data['warnings'] ?? [] as $warn ) {
                        $problems[] = [
                            'id' => $mod . '_warn',
                            'module' => $mod,
                            'severity' => 'warning',
                            'title' => $warn,
                            'recommendation' => $warn,
                        ];
                    }
                }
            }
            
            // Sort by severity (critical first)
            usort( $problems, function ( $a, $b ) {
                $weights = [ 'critical' => 0, 'warning' => 1, 'info' => 2 ];
                $wa = $weights[ $a['severity'] ?? 'info' ] ?? 3;
                $wb = $weights[ $b['severity'] ?? 'info' ] ?? 3;
                return $wa <=> $wb;
            } );

            if ( ! class_exists( '\SEOSI\Pro\Services\SolutionService' ) ) {
                require_once SEOSI_DIR . 'src/Pro/Services/SolutionService.php';
            }
            
            ob_start();
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Plan de Acción SEO - <?php echo esc_html( $url ); ?></title>
                <style>
                    body { font-family: system-ui, -apple-system, sans-serif; color: #334155; line-height: 1.6; padding: 40px; max-width: 900px; margin: 0 auto; background: #f8fafc; }
                    h1 { border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; color: #0f172a; }
                    .report-header { margin-bottom: 40px; }
                    .problem-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); page-break-inside: avoid; }
                    .problem-card.critical { border-left: 5px solid #ef4444; }
                    .problem-card.warning { border-left: 5px solid #f59e0b; }
                    .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 16px; }
                    .badge.critical { background: #fee2e2; color: #b91c1c; }
                    .badge.warning { background: #fef3c7; color: #b45309; }
                    .prob-title { font-size: 18px; font-weight: 600; color: #1e293b; margin: 0 0 8px 0; }
                    .prob-desc { color: #475569; margin-bottom: 16px; font-size: 15px; }
                    .prob-solution { background: #f1f5f9; padding: 16px; border-radius: 6px; }
                    .prob-solution h4 { margin-top: 0; color: #0f172a; }
                    .prob-solution pre { background: #1e293b; color: #f8fafc; padding: 12px; border-radius: 4px; overflow-x: auto; font-size: 13px; }
                    @media print {
                        @page { margin: 1.5cm; }
                        body { background: #fff; padding: 0; max-width: 100%; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                        .problem-card { box-shadow: none; border-color: #cbd5e1; }
                        .prob-solution pre { background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; white-space: pre-wrap; word-wrap: break-word; }
                    }
                </style>
            </head>
            <body>
                <div class="report-header">
                    <h1>Plan de Acción SEO Orientado a Impacto</h1>
                    <p><strong>URL Analizada:</strong> <?php echo esc_html( $url ); ?></p>
                    <p><strong>Fecha:</strong> <?php echo esc_html( wp_date( 'Y-m-d H:i:s' ) ); ?></p>
                    <p>A continuación se detallan los problemas encontrados ordenados por prioridad, junto con la solución técnica paso a paso para resolver cada uno.</p>
                    <button onclick="window.print()" style="padding:10px 20px; background:#4f8ef7; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;" id="print-btn">Imprimir / PDF</button>
                    <script>
                        window.addEventListener('beforeprint', function() { document.getElementById('print-btn').style.display = 'none'; });
                        window.addEventListener('afterprint', function() { document.getElementById('print-btn').style.display = 'inline-block'; });
                    </script>
                </div>
                
                <?php foreach ( $problems as $p ) : 
                    $severity_class = $p['severity'] === 'critical' ? 'critical' : 'warning';
                    $severity_label = $p['severity'] === 'critical' ? 'Crítico' : 'Advertencia';
                    $title = $p['title'] ?? $p['id'] ?? 'Problema';
                    $desc = $p['recommendation'] ?? $p['why'] ?? 'Se requiere atención.';
                    $solution_html = \SEOSI\Pro\Services\SolutionService::get_solution_html( $p['id'], $p['module'] ?? '', $desc );
                ?>
                <div class="problem-card <?php echo esc_attr( $severity_class ); ?>">
                    <span class="badge <?php echo esc_attr( $severity_class ); ?>"><?php echo esc_html( $severity_label ); ?></span>
                    <h3 class="prob-title"><?php echo esc_html( $title ); ?></h3>
                    <p class="prob-desc"><?php echo esc_html( $desc ); ?></p>
                    <div class="prob-solution">
                        <?php echo wp_kses_post( $solution_html ); ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <p style="text-align:center; color:#64748b; font-size:13px; margin-top:40px;">Generado por SEO Structure Inspector</p>
            </body>
            </html>
            <?php
            $html = ob_get_clean();
            wp_send_json_success( [ 'html' => $html ] );
            return;
        }

        $html = PDFReport::render( $results, $url );

        if ( $format === 'print' ) {
            $html = str_replace( '</body>', '<script>window.addEventListener("load", function() { setTimeout(function() { window.print(); }, 500); });</script></body>', $html );
        }

        wp_send_json_success( [ 'html' => $html ] );
    }

    public static function export_batch_csv(): void {
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'export', 20, 60 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas exportaciones. Espera un minuto.', 'seo-si' ) ], 429 );
        }

        $job_id = sanitize_key( $_REQUEST['job_id'] ?? '' );
        if ( ! $job_id ) {
            wp_send_json_error( [ 'message' => __( 'Job ID inválido o ausente.', 'seo-si' ) ] );
        }

        $job = BatchAnalyzer::get_job( $job_id );
        if ( ! $job || empty( $job['results'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Lote no encontrado o sin resultados.', 'seo-si' ) ] );
        }

        $filename = 'seo-batch-report-' . $job_id . '-' . date('Y-m-d') . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );

        $output = fopen( 'php://output', 'w' );
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

        // Header row
        fputcsv( $output, [
            __( 'URL', 'seo-si' ),
            __( 'Score Global', 'seo-si' ),
            __( 'HTML Score', 'seo-si' ),
            __( 'Keyword Score', 'seo-si' ),
            __( 'Schema Score', 'seo-si' ),
            __( 'Readability Score', 'seo-si' ),
            __( 'Meta Tags Score', 'seo-si' ),
            __( 'LLMs Score', 'seo-si' ),
            __( 'AEO Score', 'seo-si' ),
            __( 'CWV Score', 'seo-si' ),
            __( 'Links Score', 'seo-si' ),
            __( 'Problemas Detectados', 'seo-si' )
        ] );

        foreach ( $job['results'] as $url => $summary ) {
            $scores = $summary['scores'] ?? [];
            $issues_str = implode( ' | ', $summary['issues'] ?? [] );

            fputcsv( $output, [
                $url,
                $summary['global'] ?? '0',
                $scores['html'] ?? '—',
                $scores['keyword'] ?? '—',
                $scores['schema'] ?? '—',
                $scores['readability'] ?? '—',
                $scores['metatags'] ?? '—',
                $scores['llms'] ?? '—',
                $scores['aeo'] ?? '—',
                $scores['cwv'] ?? '—',
                $scores['links'] ?? '—',
                $issues_str
            ] );
        }

        fclose( $output );
        exit;
    }

    // ── AJAX: get history ─────────────────────────────────────────────────────────

    public static function get_history(): void {
        self::verify_premium_request();

        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        if ( $post_id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Post ID inválido.', 'seo-si' ) ] );
        }

        $history = HistoryService::get_history( $post_id );
        wp_send_json_success( [ 'history' => $history ] );
    }

    // ── AJAX: get solution ─────────────────────────────────────────────────────────

    public static function get_solution(): void {
        self::verify_premium_request();

        $check_id = sanitize_text_field( $_POST['check_id'] ?? '' );
        $module   = sanitize_text_field( $_POST['module'] ?? '' );
        $desc     = sanitize_text_field( $_POST['desc'] ?? '' );

        if ( ! class_exists( '\SEOSI\Pro\Services\SolutionService' ) ) {
            require_once SEOSI_DIR . 'src/Pro/Services/SolutionService.php';
        }

        $solution_html = \SEOSI\Pro\Services\SolutionService::get_solution_html( $check_id, $module, $desc );

        wp_send_json_success( [ 'solution_html' => $solution_html ] );
    }

    // ── AJAX: autofix info ─────────────────────────────────────────────────────────

    public static function autofix_info(): void {
        self::verify_premium_request();

        $check_id = sanitize_text_field( $_POST['check_id'] ?? '' );
        $module   = sanitize_text_field( $_POST['module'] ?? '' );
        $url      = self::sanitize_url( $_POST['url'] ?? '' );

        if ( ! class_exists( '\SEOSI\Pro\Services\AutoFixService' ) ) {
            require_once SEOSI_DIR . 'src/Pro/Services/AutoFixService.php';
        }

        $info = \SEOSI\Pro\Services\AutoFixService::get_autofix_info( $check_id, $module, $url );

        if ( ! $info['available'] ) {
            wp_send_json_error( [ 'message' => __( 'No hay una solución automática disponible para este problema.', 'seo-si' ) ] );
        }

        wp_send_json_success( $info );
    }

    // ── AJAX: execute autofix ──────────────────────────────────────────────────────

    public static function execute_autofix(): void {
        self::verify_premium_request();

        $check_id   = sanitize_text_field( $_POST['check_id'] ?? '' );
        $module     = sanitize_text_field( $_POST['module'] ?? '' );
        $url        = self::sanitize_url( $_POST['url'] ?? '' );
        $input_data = isset( $_POST['input_data'] ) ? json_decode( wp_unslash( $_POST['input_data'] ), true ) : [];

        if ( ! is_array( $input_data ) ) {
            $input_data = [];
        }

        if ( ! class_exists( '\SEOSI\Pro\Services\AutoFixService' ) ) {
            require_once SEOSI_DIR . 'src/Pro/Services/AutoFixService.php';
        }

        $result = \SEOSI\Pro\Services\AutoFixService::execute_autofix( $check_id, $module, $url, $input_data );

        if ( ! $result['success'] ) {
            wp_send_json_error( [ 'message' => $result['message'] ] );
        }

        wp_send_json_success( [ 'message' => $result['message'] ] );
    }

    /**
     * Sanitize array recursively with context-aware sanitization
     *
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    private static function sanitize_array_recursive( $value ) {
        if ( is_array( $value ) ) {
            return array_map( [ __CLASS__, 'sanitize_array_recursive' ], $value );
        } elseif ( is_string( $value ) ) {
            // For URLs, use esc_url_raw instead of sanitize_text_field
            if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
                return esc_url_raw( $value );
            }
            // For other strings, use sanitize_text_field
            return sanitize_text_field( $value );
        } elseif ( is_int( $value ) || is_float( $value ) ) {
            return $value;
        } elseif ( is_bool( $value ) ) {
            return $value;
        }
        return '';
    }

    // ── AJAX: generate action plan ──────────────────────────────────────────────────

    public static function generate_action_plan(): void {
        self::verify_premium_request();

        $problems_json = isset( $_POST['problems'] ) ? wp_unslash( $_POST['problems'] ) : '[]';
        $problems = json_decode( $problems_json, true );

        if ( ! is_array( $problems ) || empty( $problems ) ) {
            wp_send_json_error( [ 'message' => __( 'No hay problemas para procesar.', 'seo-si' ) ] );
        }

        if ( ! class_exists( '\SEOSI\Pro\Services\SolutionService' ) ) {
            require_once SEOSI_DIR . 'src/Pro/Services/SolutionService.php';
        }

        // Sort by severity (critical first)
        usort( $problems, function ( $a, $b ) {
            $weights = [ 'critical' => 0, 'warning' => 1, 'info' => 2 ];
            $wa = $weights[ $a['severity'] ?? 'info' ] ?? 3;
            $wb = $weights[ $b['severity'] ?? 'info' ] ?? 3;
            return $wa <=> $wb;
        } );

        $url = esc_url_raw( $_POST['url'] ?? '' );
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Plan de Acción SEO - <?php echo esc_html( $url ); ?></title>
            <style>
                body { font-family: system-ui, -apple-system, sans-serif; color: #334155; line-height: 1.6; padding: 40px; max-width: 900px; margin: 0 auto; background: #f8fafc; }
                h1 { border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; color: #0f172a; }
                .report-header { margin-bottom: 40px; }
                .problem-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); page-break-inside: avoid; }
                .problem-card.critical { border-left: 5px solid #ef4444; }
                .problem-card.warning { border-left: 5px solid #f59e0b; }
                .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 16px; }
                .badge.critical { background: #fee2e2; color: #b91c1c; }
                .badge.warning { background: #fef3c7; color: #b45309; }
                .prob-title { font-size: 18px; font-weight: 600; color: #1e293b; margin: 0 0 8px 0; }
                .prob-desc { color: #475569; margin-bottom: 16px; font-size: 15px; }
                .prob-solution { background: #f1f5f9; padding: 16px; border-radius: 6px; }
                .prob-solution h4 { margin-top: 0; color: #0f172a; }
                .prob-solution pre { background: #1e293b; color: #f8fafc; padding: 12px; border-radius: 4px; overflow-x: auto; font-size: 13px; }
                @media print {
                    @page { margin: 1.5cm; }
                    body { background: #fff; padding: 0; max-width: 100%; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .problem-card { box-shadow: none; border-color: #cbd5e1; }
                    .prob-solution pre { background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; white-space: pre-wrap; word-wrap: break-word; }
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <h1>Plan de Acción SEO Orientado a Impacto</h1>
                <p><strong>URL Analizada:</strong> <?php echo esc_html( $url ); ?></p>
                <p><strong>Fecha:</strong> <?php echo esc_html( wp_date( 'Y-m-d H:i:s' ) ); ?></p>
                <p>A continuación se detallan los problemas encontrados ordenados por prioridad, junto con la solución técnica paso a paso para resolver cada uno.</p>
                <button onclick="window.print()" style="padding:10px 20px; background:#4f8ef7; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;" id="print-btn">Imprimir / PDF</button>
                <script>
                    window.addEventListener('beforeprint', function() { document.getElementById('print-btn').style.display = 'none'; });
                    window.addEventListener('afterprint', function() { document.getElementById('print-btn').style.display = 'inline-block'; });
                </script>
            </div>
            
            <?php foreach ( $problems as $p ) : 
                $severity_class = $p['severity'] === 'critical' ? 'critical' : 'warning';
                $severity_label = $p['severity'] === 'critical' ? 'Crítico' : 'Advertencia';
                $title = $p['title'] ?? $p['id'] ?? 'Problema';
                $desc = $p['recommendation'] ?? $p['why'] ?? 'Se requiere atención.';
                $solution_html = \SEOSI\Pro\Services\SolutionService::get_solution_html( $p['id'], $p['module'] ?? '', $desc );
            ?>
            <div class="problem-card <?php echo esc_attr( $severity_class ); ?>">
                <span class="badge <?php echo esc_attr( $severity_class ); ?>"><?php echo esc_html( $severity_label ); ?></span>
                <h3 class="prob-title"><?php echo esc_html( $title ); ?></h3>
                <p class="prob-desc"><?php echo esc_html( $desc ); ?></p>
                <div class="prob-solution">
                    <?php echo wp_kses_post( $solution_html ); ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <p style="text-align:center; color:#64748b; font-size:13px; margin-top:40px;">Generado por SEO Structure Inspector</p>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        wp_send_json_success( [ 'html' => $html ] );
    }

    /**
     * Detects if the target site is WordPress and retrieves recent posts/pages.
     *
     * @param string $url The target URL.
     * @param string $html The HTML content of the main page.
     * @return array The WordPress detection data.
     */
    private static function detect_wordpress_and_get_posts( string $url, string $html ): array {
        $data = [
            'is_wordpress' => false,
            'is_local'     => false,
            'posts'        => [],
            'pages'        => []
        ];

        // 1. Check if it's the local WordPress site
        $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $target_host = wp_parse_url( $url, PHP_URL_HOST );

        if ( $home_host === $target_host ) {
            $data['is_wordpress'] = true;
            $data['is_local']     = true;

            // Fetch 3 most recent posts
            $posts = get_posts([
                'numberposts' => 3,
                'post_type'   => 'post',
                'post_status' => 'publish',
            ]);
            if ( is_array( $posts ) ) {
                foreach ( $posts as $p ) {
                    $data['posts'][] = [
                        'id'    => $p->ID,
                        'title' => get_the_title( $p ),
                        'url'   => get_permalink( $p )
                    ];
                }
            }

            // Fetch 3 most recent pages
            $pages = get_posts([
                'numberposts' => 3,
                'post_type'   => 'page',
                'post_status' => 'publish',
            ]);
            if ( is_array( $pages ) ) {
                foreach ( $pages as $pg ) {
                    $data['pages'][] = [
                        'id'    => $pg->ID,
                        'title' => get_the_title( $pg ),
                        'url'   => get_permalink( $pg )
                    ];
                }
            }

            return $data;
        }

        // 2. Check if it's an external WordPress site by parsing HTML or testing REST API
        $has_wp_generator = (strpos($html, '<meta name="generator" content="WordPress') !== false);
        $has_wp_paths = (strpos($html, '/wp-content/') !== false || strpos($html, '/wp-includes/') !== false);

        if ( $has_wp_generator || $has_wp_paths ) {
            $data['is_wordpress'] = true;
        } else {
            // Check if REST API is responsive by testing the link tag or standard REST route
            $api_url = rtrim( $url, '/' ) . '/wp-json/wp/v2/posts';
            $response = wp_remote_head( $api_url, [ 'timeout' => 1.5 ] );
            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $data['is_wordpress'] = true;
            }
        }

        // If external WordPress, attempt to fetch recent posts & pages via WP REST API using transients
        if ( $data['is_wordpress'] && $target_host ) {
            $transient_key = 'seosi_wp_ext_' . md5( $target_host );
            $cached = get_transient( $transient_key );

            if ( is_array( $cached ) ) {
                $data['posts'] = $cached['posts'] ?? [];
                $data['pages'] = $cached['pages'] ?? [];
                return $data;
            }

            // Fetch posts via REST API
            $posts_url = rtrim( $url, '/' ) . '/wp-json/wp/v2/posts?per_page=3&_fields=id,title,link';
            $posts_response = wp_remote_get( $posts_url, [ 'timeout' => 2.0 ] );
            if ( ! is_wp_error( $posts_response ) && wp_remote_retrieve_response_code( $posts_response ) === 200 ) {
                $posts_body = json_decode( wp_remote_retrieve_body( $posts_response ), true );
                if ( is_array( $posts_body ) ) {
                    foreach ( $posts_body as $p ) {
                        $title = is_array( $p['title'] ) ? ($p['title']['rendered'] ?? '') : ($p['title'] ?? '');
                        $data['posts'][] = [
                            'id'    => $p['id'] ?? 0,
                            'title' => html_entity_decode( $title ),
                            'url'   => $p['link'] ?? ''
                        ];
                    }
                }
            }

            // Fetch pages via REST API
            $pages_url = rtrim( $url, '/' ) . '/wp-json/wp/v2/pages?per_page=3&_fields=id,title,link';
            $pages_response = wp_remote_get( $pages_url, [ 'timeout' => 2.0 ] );
            if ( ! is_wp_error( $pages_response ) && wp_remote_retrieve_response_code( $pages_response ) === 200 ) {
                $pages_body = json_decode( wp_remote_retrieve_body( $pages_response ), true );
                if ( is_array( $pages_body ) ) {
                    foreach ( $pages_body as $pg ) {
                        $title = is_array( $pg['title'] ) ? ($pg['title']['rendered'] ?? '') : ($pg['title'] ?? '');
                        $data['pages'][] = [
                            'id'    => $pg['id'] ?? 0,
                            'title' => html_entity_decode( $title ),
                            'url'   => $pg['link'] ?? ''
                        ];
                    }
                }
            }

            // Cache transient for 1 hour to prevent slowing down subsequent scans
            set_transient( $transient_key, [
                'posts' => $data['posts'],
                'pages' => $data['pages']
            ], HOUR_IN_SECONDS );
        }

        return $data;
    }

    // ── AJAX: reversion handlers ──────────────────────────────────────────────────

    public static function get_applied_fixes(): void {
        self::verify_premium_request( \SEOSI\Core\Capabilities::manage_settings() );

        if ( ! class_exists( '\SEOSI\Pro\Services\ReversionService' ) ) {
            require_once SEOSI_DIR . 'src/Pro/Services/ReversionService.php';
        }

        $summary = \SEOSI\Pro\Services\ReversionService::get_summary();
        $details = \SEOSI\Pro\Services\ReversionService::get_applied_fixes();

        wp_send_json_success( [
            'summary' => $summary,
            'details' => $details
        ] );
    }

    public static function revert_single_fix(): void {
        self::verify_premium_request( \SEOSI\Core\Capabilities::manage_settings() );

        $type    = sanitize_text_field( $_POST['type'] ?? '' );
        $target  = sanitize_text_field( $_POST['target'] ?? '' );
        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

        if ( empty( $type ) || empty( $target ) ) {
            wp_send_json_error( [ 'message' => __( 'Parámetros insuficientes.', 'seo-si' ) ] );
        }

        if ( ! class_exists( '\SEOSI\Pro\Services\ReversionService' ) ) {
            require_once SEOSI_DIR . 'src/Pro/Services/ReversionService.php';
        }

        $success = \SEOSI\Pro\Services\ReversionService::revert_single_fix( $type, $target, $post_id );

        if ( $success ) {
            wp_send_json_success( [ 'message' => __( 'Corrección revertida correctamente.', 'seo-si' ) ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'No se pudo revertir la corrección seleccionada.', 'seo-si' ) ] );
        }
    }

    public static function revert_all_fixes(): void {
        self::verify_premium_request( \SEOSI\Core\Capabilities::manage_settings() );

        $confirm_code  = sanitize_text_field( $_POST['confirm_code'] ?? '' );
        $purge_history = isset( $_POST['purge_history'] ) && $_POST['purge_history'] === 'true';

        if ( strtolower( trim( $confirm_code ) ) !== 'purgar' ) {
            wp_send_json_error( [ 'message' => __( 'Código de confirmación incorrecto. Escribe "PURGAR" para proceder.', 'seo-si' ) ] );
        }

        if ( ! class_exists( '\SEOSI\Pro\Services\ReversionService' ) ) {
            require_once SEOSI_DIR . 'src/Pro/Services/ReversionService.php';
        }

        \SEOSI\Pro\Services\ReversionService::purge_all_fixes( $purge_history );

        wp_send_json_success( [ 'message' => __( 'Limpieza completa y purga realizada con éxito. Todas las mejoras aplicadas por el plugin han sido eliminadas.', 'seo-si' ) ] );
    }

    // ── AJAX: get AI recommendations ─────────────────────────────────────────────

    public static function get_ai_recommendations(): void {
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'ai_recommendations', 30, 60 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas solicitudes. Espera un momento.', 'seo-si' ) ], 429 );
        }

        $url = self::sanitize_url( $_POST['url'] ?? '' );
        if ( ! $url ) {
            wp_send_json_error( [ 'message' => __( 'URL inválida o ausente.', 'seo-si' ) ] );
        }

        if ( ! class_exists( '\SEOSI\Services\AI\AIManager' ) ) {
            require_once SEOSI_DIR . 'src/Services/AI/AIProviderInterface.php';
            require_once SEOSI_DIR . 'src/Services/AI/AIManager.php';
            require_once SEOSI_DIR . 'src/Services/AI/DefaultAIProvider.php';
        }

        try {
            $recommendations = \SEOSI\Services\AI\AIManager::get_recommendations( $url );
            wp_send_json_success( $recommendations );
        } catch ( \Throwable $e ) {
            error_log( '[SEOSI] get_ai_recommendations: ' . $e->getMessage() );
            wp_send_json_error( [ 'message' => __( 'Error al obtener recomendaciones de IA.', 'seo-si' ) ] );
        }
    }
}
