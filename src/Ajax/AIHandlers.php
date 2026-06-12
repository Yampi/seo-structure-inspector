<?php
/**
 * BaloaStructureAuditorSEO\Ajax\AIHandlers
 * AJAX handlers for AI recommendations, history snapshot, and settings.
 */

namespace BaloaStructureAuditorSEO\Ajax;

use BaloaStructureAuditorSEO\Pro\Services\HistoryService;
use BaloaStructureAuditorSEO\Services\AI\AIManager;

if ( ! defined( 'ABSPATH' ) ) exit;

class AIHandlers {
    use AjaxHelper;

    /**
     * AJAX handler to get post historical analysis snapshots.
     *
     * @return void
     */
    public static function get_history(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        $post_id     = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
        if ( $post_id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Post ID inválido.', 'baloa-structure-auditor-seo' ) ] );
        }

        $history = HistoryService::get_history( $post_id );
        wp_send_json_success( [ 'history' => $history ] );
    }

    /**
     * AJAX handler to get AI recommendations for a specific page.
     *
     * @return void
     */
    public static function get_ai_recommendations(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'ai_recommendations', 30, 60 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas solicitudes. Espera un momento.', 'baloa-structure-auditor-seo' ) ], 429 );
        }

        $url = isset( $_POST['url'] ) ? self::sanitize_url( esc_url_raw( wp_unslash( $_POST['url'] ) ) ) : '';
        if ( ! $url ) {
            wp_send_json_error( [ 'message' => __( 'URL inválida o ausente.', 'baloa-structure-auditor-seo' ) ] );
        }

        if ( ! class_exists( AIManager::class ) ) {
            require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Services/AI/AIProviderInterface.php';
            require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Services/AI/AIManager.php';
            require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Services/AI/DefaultAIProvider.php';
        }

        try {
            $user_id = get_current_user_id();
            $analysis_cache_key = 'baloa_structure_auditor_seo_rep_' . $user_id . '_' . md5( $url );
            $cached_analysis = get_transient( $analysis_cache_key );
            $context = [];
            if ( is_array( $cached_analysis ) ) {
                $context['analysis_results'] = $cached_analysis;
            }

            $recommendations = AIManager::get_recommendations( $url, $context );
            wp_send_json_success( $recommendations );
        } catch ( \Throwable $e ) {
            \BaloaStructureAuditorSEO\Core\Logger::error( 'get_ai_recommendations failed', [ 'message' => $e->getMessage() ] );
            wp_send_json_error( [ 'message' => __( 'Error al obtener recomendaciones de IA.', 'baloa-structure-auditor-seo' ) ] );
        }
    }

    /**
     * AJAX handler to save the preferred AI provider.
     *
     * @return void
     */
    public static function save_ai_provider(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        $provider = sanitize_text_field( wp_unslash( $_POST['provider'] ?? 'default' ) );
        if ( in_array( $provider, [ 'default', 'openai', 'gemini', 'claude' ], true ) ) {
            $options = get_option( 'baloa_structure_auditor_seo_options', [] );
            $options['ai_provider'] = $provider;
            update_option( 'baloa_structure_auditor_seo_options', $options );
            wp_send_json_success();
            return;
        }
        wp_send_json_error();
    }
}
