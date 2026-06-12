<?php
/**
 * BaloaStructureAuditorSEO\Api\TelemetryController
 *
 * REST API handler for receiving RUM metric samples.
 */

declare(strict_types=1);

namespace BaloaStructureAuditorSEO\Api;

use BaloaStructureAuditorSEO\Domain\Performance\MetricSample;
use BaloaStructureAuditorSEO\Pro\Services\TelemetryService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TelemetryController {

    /**
     * Register telemetry rest routes.
     */
    public static function register_routes(): void {
        register_rest_route( 'baloa-structure-auditor-seo/v1', '/telemetry', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'receive_telemetry' ],
            'permission_callback' => '__return_true', // Public endpoint for client browsers
        ] );
    }

    /**
     * Callback to receive telemetry.
     */
    public static function receive_telemetry( \WP_REST_Request $request ): \WP_REST_Response {
        // Rate limit by IP (max 10 requests per minute)
        $ip            = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $ip_hash       = md5( $ip );
        $transient_key = 'baloa_structure_auditor_seo_telemetry_limit_' . $ip_hash;
        $count         = (int) get_transient( $transient_key );
        
        if ( $count >= 10 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'Rate limit exceeded.',
            ], 429 );
        }
        set_transient( $transient_key, $count + 1, MINUTE_IN_SECONDS );

        $params = $request->get_json_params();
        if ( ! is_array( $params ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'Invalid JSON.',
            ], 400 );
        }

        $name  = sanitize_text_field( $params['name'] ?? '' );
        $value = floatval( $params['value'] ?? 0.0 );
        $url   = esc_url_raw( $params['url'] ?? '' );

        try {
            // Instantiate Value Object to validate data
            $sample = new MetricSample( $name, $value, $url );
            TelemetryService::store_sample( $sample );
        } catch ( \InvalidArgumentException $e ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $e->getMessage(),
            ], 400 );
        }

        return new \WP_REST_Response( [
            'success' => true,
        ], 200 );
    }
}
