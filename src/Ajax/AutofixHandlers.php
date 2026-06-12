<?php
/**
 * BaloaStructureAuditorSEO\Ajax\AutofixHandlers
 * AJAX handlers for premium auto-fixes and revert actions.
 */

namespace BaloaStructureAuditorSEO\Ajax;

use BaloaStructureAuditorSEO\Core\Capabilities;
use BaloaStructureAuditorSEO\Pro\Services\AutoFixService;
use BaloaStructureAuditorSEO\Pro\Services\ReversionService;

if ( ! defined( 'ABSPATH' ) ) exit;

class AutofixHandlers {
    use AjaxHelper;

    /**
     * AJAX handler to get information about an automatic fix.
     *
     * @return void
     */
    public static function autofix_info(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        $check_id = isset( $_POST['check_id'] ) ? sanitize_text_field( wp_unslash( $_POST['check_id'] ) ) : '';
        $module   = isset( $_POST['module'] ) ? sanitize_text_field( wp_unslash( $_POST['module'] ) ) : '';
        $url      = isset( $_POST['url'] ) ? self::sanitize_url( esc_url_raw( wp_unslash( $_POST['url'] ) ) ) : '';

        if ( ! class_exists( AutoFixService::class ) ) {
            require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Pro/Services/AutoFixService.php';
        }

        $info = AutoFixService::get_autofix_info( $check_id, $module, $url );

        if ( ! $info['available'] ) {
            wp_send_json_error( [ 'message' => __( 'No hay una solución automática disponible para este problema.', 'baloa-structure-auditor-seo' ) ] );
        }

        wp_send_json_success( $info );
    }

    /**
     * AJAX handler to execute an automatic fix.
     *
     * @return void
     */
    public static function execute_autofix(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        $check_id   = isset( $_POST['check_id'] ) ? sanitize_text_field( wp_unslash( $_POST['check_id'] ) ) : '';
        $module     = isset( $_POST['module'] ) ? sanitize_text_field( wp_unslash( $_POST['module'] ) ) : '';
        $url        = isset( $_POST['url'] ) ? self::sanitize_url( esc_url_raw( wp_unslash( $_POST['url'] ) ) ) : '';
        $raw_input  = isset( $_POST['input_data'] ) ? wp_unslash( $_POST['input_data'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $decoded    = $raw_input !== '' ? json_decode( $raw_input, true ) : [];
        $input_data = is_array( $decoded ) ? self::sanitize_array_recursive( $decoded ) : [];

        if ( ! class_exists( AutoFixService::class ) ) {
            require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Pro/Services/AutoFixService.php';
        }

        $result = AutoFixService::execute_autofix( $check_id, $module, $url, $input_data );

        if ( ! $result['success'] ) {
            wp_send_json_error( [ 'message' => $result['message'] ] );
        }

        wp_send_json_success( [ 'message' => $result['message'] ] );
    }

    /**
     * AJAX handler to fetch all applied fixes.
     *
     * @return void
     */
    public static function get_applied_fixes(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request( Capabilities::manage_settings() );

        if ( ! class_exists( ReversionService::class ) ) {
            require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Pro/Services/ReversionService.php';
        }

        $summary = ReversionService::get_summary();
        $details = ReversionService::get_applied_fixes();

        wp_send_json_success( [
            'summary' => $summary,
            'details' => $details
        ] );
    }

    /**
     * AJAX handler to revert a single applied fix.
     *
     * @return void
     */
    public static function revert_single_fix(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request( Capabilities::manage_settings() );

        $type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
        $target  = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';
        $post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

        if ( empty( $type ) || empty( $target ) ) {
            wp_send_json_error( [ 'message' => __( 'Parámetros insuficientes.', 'baloa-structure-auditor-seo' ) ] );
        }

        if ( ! class_exists( ReversionService::class ) ) {
            require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Pro/Services/ReversionService.php';
        }

        $success = ReversionService::revert_single_fix( $type, $target, $post_id );

        if ( $success ) {
            wp_send_json_success( [ 'message' => __( 'Corrección revertida correctamente.', 'baloa-structure-auditor-seo' ) ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'No se pudo revertir la corrección seleccionada.', 'baloa-structure-auditor-seo' ) ] );
        }
    }

    /**
     * AJAX handler to revert all applied fixes.
     *
     * @return void
     */
    public static function revert_all_fixes(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request( Capabilities::manage_settings() );

        $confirm_code  = isset( $_POST['confirm_code'] ) ? sanitize_text_field( wp_unslash( $_POST['confirm_code'] ) ) : '';
        $raw_purge     = isset( $_POST['purge_history'] ) ? sanitize_text_field( wp_unslash( $_POST['purge_history'] ) ) : '';
        $purge_history = $raw_purge === 'true';

        if ( strtolower( trim( $confirm_code ) ) !== 'purgar' ) {
            wp_send_json_error( [ 'message' => __( 'Código de confirmación incorrecto. Escribe "PURGAR" para proceder.', 'baloa-structure-auditor-seo' ) ] );
        }

        if ( ! class_exists( ReversionService::class ) ) {
            require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Pro/Services/ReversionService.php';
        }

        ReversionService::purge_all_fixes( $purge_history );

        wp_send_json_success( [ 'message' => __( 'Limpieza completa y purga realizada con éxito. Todas las mejoras aplicadas por el plugin han sido eliminadas.', 'baloa-structure-auditor-seo' ) ] );
    }
}
