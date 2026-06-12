<?php
/**
 * BaloaStructureAuditorSEO\Ajax\AjaxHelper
 * Trait for shared AJAX utilities, security checks, and rate-limiting.
 */

namespace BaloaStructureAuditorSEO\Ajax;

use BaloaStructureAuditorSEO\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) exit;

trait AjaxHelper {

    /**
     * Verifies the general security nonce and user capability for AJAX requests.
     *
     * @param string|null $capability The capability to check. Defaults to analyze capability.
     * @return void
     */
    protected static function verify_request( ?string $capability = null ): void {
        $capability = $capability ?? Capabilities::analyze();
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );

        if ( ! current_user_can( $capability ) ) {
            wp_send_json_error( [ 'message' => __( 'Permisos insuficientes.', 'baloa-structure-auditor-seo' ) ], 403 );
        }
    }

    /**
     * Secures premium AJAX routes by verifying both general permissions and Pro license status.
     *
     * @param string|null $capability Required user capability.
     * @return void
     */
    protected static function verify_premium_request( ?string $capability = null ): void {
        self::verify_request( $capability );

        $is_premium = \BaloaStructureAuditorSEO\Core\Plugin::get_instance()->get_license()->is_premium();
        if ( ! $is_premium ) {
            wp_send_json_error( [
                'message' => __( 'Esta característica requiere la versión PRO de Baloa Structure Auditor for SEO.', 'baloa-structure-auditor-seo' ),
                'is_pro_required' => true
            ], 403 );
        }
    }

    /**
     * Simple transient-based rate-limiting helper.
     *
     * @param string $action The action identifier.
     * @param int $max_calls Max allowed calls in the window.
     * @param int $window_seconds Time window in seconds.
     * @return bool True if within limits, false otherwise.
     */
    protected static function check_rate_limit( string $action, int $max_calls = 10, int $window_seconds = 60 ): bool {
        $user_id = get_current_user_id();
        $key     = 'baloa_structure_auditor_seo_rl_' . $action . '_' . $user_id;
        $current = (int) get_transient( $key );

        if ( $current >= $max_calls ) {
            return false;
        }

        set_transient( $key, $current + 1, $window_seconds );
        return true;
    }

    /**
     * Cleans and validates a URL.
     *
     * @param string $raw The raw URL string.
     * @return string The sanitized URL or empty string.
     */
    protected static function sanitize_url( string $raw ): string {
        $url = esc_url_raw( trim( $raw ) );
        return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
    }

    /**
     * Sanitize array recursively with context-aware sanitization
     *
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    protected static function sanitize_array_recursive( $value ) {
        if ( is_array( $value ) ) {
            return array_map( [ __CLASS__, 'sanitize_array_recursive' ], $value );
        } elseif ( is_string( $value ) ) {
            if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
                return esc_url_raw( $value );
            }
            return sanitize_text_field( $value );
        } elseif ( is_int( $value ) || is_float( $value ) ) {
            return $value;
        } elseif ( is_bool( $value ) ) {
            return $value;
        }
        return '';
    }
}
