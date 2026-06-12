<?php
/**
 * BaloaStructureAuditorSEO\Ajax\CompetitorHandlers
 *
 * AJAX Handlers for Competitor Gap Analysis.
 *
 * @package BaloaStructureAuditorSEO
 */

declare(strict_types=1);

namespace BaloaStructureAuditorSEO\Ajax;

use BaloaStructureAuditorSEO\Pro\Services\CompetitorGapService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CompetitorHandlers {
    use AjaxHelper;

    /**
     * AJAX handler to run side-by-side competitor benchmarking.
     */
    public static function analyze_competitors(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();
        
        $original_url = isset( $_POST['url'] ) ? self::sanitize_url( esc_url_raw( wp_unslash( $_POST['url'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $comps = isset( $_POST['competitors'] ) ? self::sanitize_array_recursive( wp_unslash( $_POST['competitors'] ) ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        
        if ( ! $original_url || empty( $comps ) ) {
            wp_send_json_error( [ 'message' => __( 'Parámetros inválidos o competidores ausentes.', 'baloa-structure-auditor-seo' ) ] );
        }
        
        $original_data = self::get_original_url_data( $original_url );
        if ( ! $original_data ) {
            wp_send_json_error( [ 'message' => __( 'Debes analizar primero la URL principal.', 'baloa-structure-auditor-seo' ) ] );
        }
        
        $comparison = CompetitorGapService::compare( $original_data, $comps );
        wp_send_json_success( $comparison );
    }

    private static function get_original_url_data( string $url ): ?array {
        $user_id = get_current_user_id();
        $cache_key = 'baloa_structure_auditor_seo_rep_' . $user_id . '_' . md5( $url );
        $data = get_transient( $cache_key );
        return is_array( $data ) ? $data : null;
    }
}
