<?php
/**
 * BaloaStructureAuditorSEO\Ajax\ReportHandlers
 * AJAX handlers for report exports and action plans.
 */

namespace BaloaStructureAuditorSEO\Ajax;

use BaloaStructureAuditorSEO\Services\AnalysisService;
use BaloaStructureAuditorSEO\Services\FetcherService;
use BaloaStructureAuditorSEO\Pro\Services\PDFReport;
use BaloaStructureAuditorSEO\Pro\Services\SolutionService;
use BaloaStructureAuditorSEO\Core\ResultPresenter;

if ( ! defined( 'ABSPATH' ) ) exit;

class ReportHandlers {
    use AjaxHelper;

    /**
     * AJAX handler to export an analysis report as PDF or Action Plan HTML.
     *
     * @return void
     */
    public static function export_report(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'export', 20, 60 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas exportaciones. Espera un minuto.', 'baloa-structure-auditor-seo' ) ], 429 );
        }

        $url = isset( $_POST['url'] ) ? self::sanitize_url( esc_url_raw( wp_unslash( $_POST['url'] ) ) ) : '';
        if ( ! $url ) {
            wp_send_json_error( [ 'message' => __( 'URL inválida o ausente.', 'baloa-structure-auditor-seo' ) ] );
        }

        $user_id = get_current_user_id();
        $transient_key = 'baloa_structure_auditor_seo_rep_' . $user_id . '_' . md5( $url );
        $results = get_transient( $transient_key );

        if ( ! $results || ! is_array( $results ) ) {
            try {
                $fetched = FetcherService::fetch_html( $url );
                if ( ! $fetched['error'] ) {
                    $api_key = sanitize_text_field( \BaloaStructureAuditorSEO\Admin\Settings::get_option( 'pagespeed_api_key' ) );
                    $analysis_obj = AnalysisService::analyze( $fetched['html'], $url, '', $api_key );
                    $results = $analysis_obj->toArray();
                    $results['strategy'] = $fetched['strategy'];
                    $results = ResultPresenter::localize_analysis_results( $results );
                    
                    set_transient( $transient_key, $results, 2 * HOUR_IN_SECONDS );
                }
            } catch ( \Throwable $e ) {
                \BaloaStructureAuditorSEO\Core\Logger::error( 'export_report fallback failed', [ 'message' => $e->getMessage() ] );
            }
        }

        if ( ! $results || ! is_array( $results ) ) {
            wp_send_json_error( [ 'message' => __( 'No se encontraron resultados de análisis recientes para esta URL y no se pudo re-analizar. Por favor, analízala de nuevo.', 'baloa-structure-auditor-seo' ) ] );
        }

        $format = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : 'html';

        if ( $format === 'action_plan' ) {
            $problems = [];
            $modules = [ 'html', 'keyword', 'schema', 'readability', 'metatags', 'llms', 'aeo', 'links' ];
            
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
            
            usort( $problems, function ( $a, $b ) {
                $weights = [ 'critical' => 0, 'warning' => 1, 'info' => 2 ];
                $wa = $weights[ $a['severity'] ?? 'info' ] ?? 3;
                $wb = $weights[ $b['severity'] ?? 'info' ] ?? 3;
                return $wa <=> $wb;
            } );

            if ( ! class_exists( SolutionService::class ) ) {
                require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Pro/Services/SolutionService.php';
            }
            
            ob_start();
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Plan de Acción SEO - <?php echo esc_html( $url ); ?></title>
                <?php
                wp_register_style( 'baloa-action-plan', BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/action-plan.css', [], BALOA_STRUCTURE_AUDITOR_SEO_VERSION );
                wp_print_styles( 'baloa-action-plan' );
                ?>
            </head>
            <body>
                <div class="report-header">
                    <h1>Plan de Acción SEO Orientado a Impacto</h1>
                    <p><strong>URL Analizada:</strong> <?php echo esc_html( $url ); ?></p>
                    <p><strong>Fecha:</strong> <?php echo esc_html( wp_date( 'Y-m-d H:i:s' ) ); ?></p>
                    <p>A continuación se detallan los problemas encontrados ordenados por prioridad, junto con la solución técnica paso a paso para resolver cada uno.</p>
                    <button onclick="window.print()" style="padding:10px 20px; background:#4f8ef7; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;" id="print-btn">Imprimir / PDF</button>
                    <?php
                    wp_register_script( 'baloa-action-plan-js', BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/action-plan.js', [], BALOA_STRUCTURE_AUDITOR_SEO_VERSION, true );
                    wp_print_scripts( 'baloa-action-plan-js' );
                    ?>
                </div>
                
                <?php foreach ( $problems as $p ) : 
                    $severity_class = $p['severity'] === 'critical' ? 'critical' : 'warning';
                    $severity_label = $p['severity'] === 'critical' ? 'Crítico' : 'Advertencia';
                    $title = $p['title'] ?? $p['id'] ?? 'Problema';
                    $desc = $p['recommendation'] ?? $p['why'] ?? 'Se requiere atención.';
                    $solution_html = SolutionService::get_solution_html( $p['id'], $p['module'] ?? '', $desc );
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
                
                <p style="text-align:center; color:#64748b; font-size:13px; margin-top:40px;">Generado por Baloa Structure Auditor for SEO</p>
            </body>
            </html>
            <?php
            $html = ob_get_clean();
            wp_send_json_success( [ 'html' => $html ] );
            return;
        }

        $is_print = ( $format === 'print' );
        $html = PDFReport::render( $results, $url, $is_print );

        wp_send_json_success( [ 'html' => $html ] );
    }

    /**
     * AJAX handler to get a detailed step-by-step solution for a specific check.
     *
     * @return void
     */
    public static function get_solution(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        $check_id = isset( $_POST['check_id'] ) ? sanitize_text_field( wp_unslash( $_POST['check_id'] ) ) : '';
        $module   = isset( $_POST['module'] ) ? sanitize_text_field( wp_unslash( $_POST['module'] ) ) : '';
        $desc     = isset( $_POST['desc'] ) ? sanitize_text_field( wp_unslash( $_POST['desc'] ) ) : '';

        if ( ! class_exists( SolutionService::class ) ) {
            require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Pro/Services/SolutionService.php';
        }

        $solution_html = SolutionService::get_solution_html( $check_id, $module, $desc );

        wp_send_json_success( [ 'solution_html' => $solution_html ] );
    }

    /**
     * AJAX handler to dynamically generate an action plan based on user selection.
     *
     * @return void
     */
    public static function generate_action_plan(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        $raw_problems     = isset( $_POST['problems'] ) ? wp_unslash( $_POST['problems'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $decoded_problems = json_decode( $raw_problems, true );
        $problems = is_array( $decoded_problems ) ? self::sanitize_array_recursive( $decoded_problems ) : [];

        if ( ! is_array( $problems ) || empty( $problems ) ) {
            wp_send_json_error( [ 'message' => __( 'No hay problemas para procesar.', 'baloa-structure-auditor-seo' ) ] );
        }

        if ( ! class_exists( SolutionService::class ) ) {
            require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Pro/Services/SolutionService.php';
        }

        usort( $problems, function ( $a, $b ) {
            $weights = [ 'critical' => 0, 'warning' => 1, 'info' => 2 ];
            $wa = $weights[ $a['severity'] ?? 'info' ] ?? 3;
            $wb = $weights[ $b['severity'] ?? 'info' ] ?? 3;
            return $wa <=> $wb;
        } );

        $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Plan de Acción SEO - <?php echo esc_html( $url ); ?></title>
            <?php
            wp_register_style( 'baloa-action-plan', BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/action-plan.css', [], BALOA_STRUCTURE_AUDITOR_SEO_VERSION );
            wp_print_styles( 'baloa-action-plan' );
            ?>
        </head>
        <body>
            <div class="report-header">
                <h1>Plan de Acción SEO Orientado a Impacto</h1>
                <p><strong>URL Analizada:</strong> <?php echo esc_html( $url ); ?></p>
                <p><strong>Fecha:</strong> <?php echo esc_html( wp_date( 'Y-m-d H:i:s' ) ); ?></p>
                <p>A continuación se detallan los problemas encontrados ordenados por prioridad, junto con la solución técnica paso a paso para resolver cada uno.</p>
                <button onclick="window.print()" style="padding:10px 20px; background:#4f8ef7; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;" id="print-btn">Imprimir / PDF</button>
                <?php
                wp_register_script( 'baloa-action-plan-js', BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/action-plan.js', [], BALOA_STRUCTURE_AUDITOR_SEO_VERSION, true );
                wp_print_scripts( 'baloa-action-plan-js' );
                ?>
            </div>
            
            <?php foreach ( $problems as $p ) : 
                $severity_class = $p['severity'] === 'critical' ? 'critical' : 'warning';
                $severity_label = $p['severity'] === 'critical' ? 'Crítico' : 'Advertencia';
                $title = $p['title'] ?? $p['id'] ?? 'Problema';
                $desc = $p['recommendation'] ?? $p['why'] ?? 'Se requiere atención.';
                $solution_html = SolutionService::get_solution_html( $p['id'], $p['module'] ?? '', $desc );
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
            
            <p style="text-align:center; color:#64748b; font-size:13px; margin-top:40px;">Generado por Baloa Structure Auditor for SEO</p>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        wp_send_json_success( [ 'html' => $html ] );
    }
}
