<?php
/**
 * BaloaStructureAuditorSEO\Ajax\BatchHandlers
 * AJAX handlers for batch operations and CSV exporting.
 */

namespace BaloaStructureAuditorSEO\Ajax;

use BaloaStructureAuditorSEO\Services\AnalysisService;
use BaloaStructureAuditorSEO\Services\FetcherService;
use BaloaStructureAuditorSEO\Pro\Services\BatchAnalyzer;
use BaloaStructureAuditorSEO\Core\ResultPresenter;

if ( ! defined( 'ABSPATH' ) ) exit;

class BatchHandlers {
    use AjaxHelper;

    /**
     * AJAX handler to initialize a batch scan.
     *
     * @return void
     */
    public static function batch_create(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'batch_create', 5, 300 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas lotes creados. Espera unos minutos.', 'baloa-structure-auditor-seo' ) ], 429 );
        }

        $raw_post_urls = isset( $_POST['urls'] ) && is_array( $_POST['urls'] ) ? map_deep( wp_unslash( $_POST['urls'] ), 'sanitize_text_field' ) : [];
        $raw_urls      = $raw_post_urls;
        $keyword       = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';

        $urls = array_values( array_filter( array_map( [ __CLASS__, 'sanitize_url' ], $raw_urls ) ) );
        $urls = array_slice( $urls, 0, 500 );

        if ( empty( $urls ) ) {
            wp_send_json_error( [ 'message' => __( 'No se recibieron URLs validas.', 'baloa-structure-auditor-seo' ) ] );
        }

        $job_id = BatchAnalyzer::create_job( $urls, $keyword );
        wp_send_json_success( [ 'job_id' => $job_id, 'total' => count( $urls ) ] );
    }

    /**
     * AJAX handler to analyze a single URL as part of a batch job.
     *
     * @return void
     */
    public static function batch_analyze_url(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'batch_url', 120, 300 ) ) {
            wp_send_json_error( [ 'message' => __( 'Limite de velocidad alcanzado. El batch continuara automaticamente.', 'baloa-structure-auditor-seo' ) ], 429 );
        }

        $job_id  = isset( $_POST['job_id'] ) ? sanitize_key( wp_unslash( $_POST['job_id'] ) ) : '';
        $url     = isset( $_POST['url'] ) ? self::sanitize_url( esc_url_raw( wp_unslash( $_POST['url'] ) ) ) : '';
        $keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';

        if ( ! $job_id || ! $url ) {
            wp_send_json_error( [ 'message' => __( 'Datos insuficientes.', 'baloa-structure-auditor-seo' ) ] );
        }

        if ( ! BatchAnalyzer::get_job( $job_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Job no encontrado.', 'baloa-structure-auditor-seo' ) ], 404 );
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
            $api_key = sanitize_text_field( \BaloaStructureAuditorSEO\Admin\Settings::get_option( 'pagespeed_api_key' ) );
            $result  = AnalysisService::analyze( $fetched['html'], $url, $keyword, $api_key );
        } catch ( \Throwable $e ) {
            \BaloaStructureAuditorSEO\Core\Logger::error( 'batch_analyze_url failed', [ 'message' => $e->getMessage() ] );
            wp_send_json_error( [ 'message' => __( 'Error interno durante el análisis.', 'baloa-structure-auditor-seo' ) ] );
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

    /**
     * AJAX handler to get progress details of a batch job.
     *
     * @return void
     */
    public static function batch_status(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        $job_id = sanitize_text_field( wp_unslash( $_POST['job_id'] ?? '' ) );

        if ( ! $job_id ) {
            wp_send_json_error( [ 'message' => __( 'Job ID requerido.', 'baloa-structure-auditor-seo' ) ] );
        }

        $job = BatchAnalyzer::get_job( $job_id );
        if ( ! $job ) {
            wp_send_json_error( [ 'message' => __( 'Job no encontrado.', 'baloa-structure-auditor-seo' ) ], 404 );
        }

        wp_send_json_success( $job );
    }

    /**
     * AJAX handler to download a batch scan results as CSV.
     *
     * @return void
     */
    public static function export_batch_csv(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'export', 20, 60 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas exportaciones. Espera un minuto.', 'baloa-structure-auditor-seo' ) ], 429 );
        }

        if ( isset( $_POST['job_id'] ) ) {
            $job_id = sanitize_key( wp_unslash( $_POST['job_id'] ) );
        } elseif ( isset( $_GET['job_id'] ) ) {
            $job_id = sanitize_key( wp_unslash( $_GET['job_id'] ) );
        } else {
            $job_id = '';
        }
        if ( ! $job_id ) {
            wp_send_json_error( [ 'message' => __( 'Job ID inválido o ausente.', 'baloa-structure-auditor-seo' ) ] );
        }

        $job = BatchAnalyzer::get_job( $job_id );
        if ( ! $job || empty( $job['results'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Lote no encontrado o sin resultados.', 'baloa-structure-auditor-seo' ) ] );
        }

        $filename = 'seo-batch-report-' . $job_id . '-' . gmdate('Y-m-d') . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );

        $csv_data = chr(0xEF).chr(0xBB).chr(0xBF); // Add UTF-8 BOM for Excel compatibility

        $csv_data .= self::array_to_csv_row( [
            __( 'URL', 'baloa-structure-auditor-seo' ),
            __( 'Score Global', 'baloa-structure-auditor-seo' ),
            __( 'HTML Score', 'baloa-structure-auditor-seo' ),
            __( 'Keyword Score', 'baloa-structure-auditor-seo' ),
            __( 'Schema Score', 'baloa-structure-auditor-seo' ),
            __( 'Readability Score', 'baloa-structure-auditor-seo' ),
            __( 'Meta Tags Score', 'baloa-structure-auditor-seo' ),
            __( 'LLMs Score', 'baloa-structure-auditor-seo' ),
            __( 'AEO Score', 'baloa-structure-auditor-seo' ),
            __( 'CWV Score', 'baloa-structure-auditor-seo' ),
            __( 'Links Score', 'baloa-structure-auditor-seo' ),
            __( 'Problemas Detectados', 'baloa-structure-auditor-seo' )
        ] );

        foreach ( $job['results'] as $url => $summary ) {
            $scores = $summary['scores'] ?? [];
            $issues_str = implode( ' | ', $summary['issues'] ?? [] );

            $csv_data .= self::array_to_csv_row( [
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

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $csv_data;
        exit;
    }

    /**
     * Helper to format an array of fields into a single CSV row in memory.
     *
     * @param array $fields The fields to format.
     * @return string The formatted CSV row.
     */
    private static function array_to_csv_row( array $fields ): string {
        $output = [];
        foreach ( $fields as $field ) {
            $value = str_replace( '"', '""', (string) $field );
            if ( preg_match( '/[",\r\n]/', $value ) ) {
                $value = '"' . $value . '"';
            }
            $output[] = $value;
        }
        return implode( ',', $output ) . "\n";
    }
}
