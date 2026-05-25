<?php
/**
 * SEOSI\Api\RestController
 * 
 * REST API endpoints for SEO Structure Inspector.
 * Allows external integrations via WordPress REST API with Application Passwords.
 */

namespace SEOSI\Api;

use SEOSI\Core\Capabilities;
use SEOSI\Services\AnalysisService;
use SEOSI\Pro\Services\HistoryService;
use SEOSI\Services\FetcherService;
use SEOSI\Core\ResultPresenter;

if ( ! defined( 'ABSPATH' ) ) exit;

class RestController {

    const RATE_LIMIT_KEY = 'seosi_rest_rate_limit';
    const RATE_LIMIT_MAX = 60; // 60 requests per hour

    /**
     * Register REST API routes.
     */
    public static function register_routes(): void {
        register_rest_route( 'seosi/v1', '/analyze', [
            'methods'  => [ 'GET', 'POST' ],
            'callback' => [ __CLASS__, 'analyze' ],
            'permission_callback' => [ __CLASS__, 'check_permission' ],
            'args'     => [
                'url' => [
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return filter_var( $param, FILTER_VALIDATE_URL ) !== false;
                    },
                    'sanitize_callback' => 'esc_url_raw',
                ],
                'keyword' => [
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'modules' => [
                    'required'          => false,
                    'sanitize_callback' => function( $param ) {
                        if ( is_array( $param ) ) {
                            return array_map( 'sanitize_text_field', $param );
                        }
                        return [];
                    },
                ],
            ],
        ] );

        register_rest_route( 'seosi/v1', '/history/(?P<post_id>\d+)', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_history' ],
            'permission_callback' => [ __CLASS__, 'check_permission' ],
            'args'     => [
                'post_id' => [
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param > 0;
                    },
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );
    }

    /**
     * Check permission for REST API access.
     * Requires manage_options capability (authenticated via Application Passwords).
     */
    public static function check_permission(): bool {
        return Capabilities::user_can_manage_settings();
    }

    /**
     * Check rate limit for REST API.
     * Maximum 60 requests per hour per user.
     */
    private static function check_rate_limit(): bool {
        $user_id = get_current_user_id();
        if ( $user_id === 0 ) return false;

        $key = self::RATE_LIMIT_KEY . '_' . $user_id;
        $data = get_transient( $key );

        if ( $data === false ) {
            // First request in this hour
            set_transient( $key, [ 'count' => 1, 'start' => time() ], HOUR_IN_SECONDS );
            return true;
        }

        if ( time() - $data['start'] >= HOUR_IN_SECONDS ) {
            // Reset for new hour
            set_transient( $key, [ 'count' => 1, 'start' => time() ], HOUR_IN_SECONDS );
            return true;
        }

        if ( $data['count'] >= self::RATE_LIMIT_MAX ) {
            return false;
        }

        // Increment count
        $data['count']++;
        set_transient( $key, $data, HOUR_IN_SECONDS );
        return true;
    }

    /**
     * REST API endpoint for analyzing a URL.
     */
    public static function analyze( \WP_REST_Request $request ): \WP_REST_Response {
        if ( ! self::check_rate_limit() ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Rate limit exceeded. Maximum 60 requests per hour.', 'seo-si' ),
            ], 429 );
        }

        $url = $request->get_param( 'url' );
        $keyword = $request->get_param( 'keyword' ) ?? '';
        $modules = $request->get_param( 'modules' );

        try {
            $fetched = FetcherService::fetch_html( $url );
            if ( $fetched['error'] ) {
                return new \WP_REST_Response( [
                    'success' => false,
                    'message' => $fetched['error'],
                ], 400 );
            }

            $html     = $fetched['html'];
            $strategy = $fetched['strategy'];
            $api_key  = sanitize_text_field( \SEOSI\Admin\Settings::get_option( 'pagespeed_api_key' ) );
            $results  = AnalysisService::analyze( $html, $url, $keyword, $api_key );
        } catch ( \Throwable $e ) {
            error_log( '[SEOSI] REST analyze: ' . $e->getMessage() );
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Error interno durante el análisis.', 'seo-si' ),
            ], 500 );
        }
        $result_array = $results->toArray();
        $result_array['strategy'] = $strategy;
        $result_array = ResultPresenter::localize_analysis_results( $result_array );

        // Filter modules if specified
        if ( is_array( $modules ) && ! empty( $modules ) ) {
            $result_array = self::filter_modules( $result_array, $modules );
        }

        return new \WP_REST_Response( [
            'success' => true,
            'data'    => $result_array,
            'meta'    => [
                'analyzed_at'    => time(),
                'plugin_version' => SEOSI_VERSION,
                'strategy'       => $strategy,
            ],
        ], 200 );
    }

    /**
     * REST API endpoint for getting post history.
     */
    public static function get_history( \WP_REST_Request $request ): \WP_REST_Response {
        $is_premium = \SEOSI\Core\Plugin::get_instance()->get_license()->is_premium();
        if ( ! $is_premium ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Esta característica requiere la versión PRO de SEO Structure Inspector.', 'seo-si' ),
            ], 403 );
        }

        $post_id = $request->get_param( 'post_id' );

        $history = HistoryService::get_history( $post_id );

        return new \WP_REST_Response( [
            'success'  => true,
            'data'     => [
                'post_id' => $post_id,
                'history' => $history,
            ],
        ], 200 );
    }

    /**
     * Filter results to include only specified modules.
     *
     * @param array $results Full analysis results.
     * @param array $modules Modules to include.
     * @return array Filtered results.
     */
    private static function filter_modules( array $results, array $modules ): array {
        $filtered = $results;

        // Always include url, global_score, strategy, analyzed_at
        $preserve = [ 'url', 'global_score', 'strategy', 'analyzed_at' ];

        foreach ( $results as $key => $value ) {
            if ( ! in_array( $key, $preserve, true ) && ! in_array( $key, $modules, true ) ) {
                unset( $filtered[ $key ] );
            }
        }

        return $filtered;
    }
}
