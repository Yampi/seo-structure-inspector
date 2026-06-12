<?php
/**
 * BaloaStructureAuditorSEO\Services\AI\AnalysisContextExtractor
 *
 * Extracts failed checks (error/warning) and formats context from analysis results.
 */

namespace BaloaStructureAuditorSEO\Services\AI;

if ( ! defined( 'ABSPATH' ) ) exit;

class AnalysisContextExtractor {

    /**
     * Extract failed checks (error/warning) from the localized analysis results array.
     *
     * @param array $analysis_results Localized analysis results.
     * @return array Failed checks with module metadata.
     */
    public static function extract_failed_checks( array $analysis_results ): array {
        $modules = [ 'html', 'keyword', 'schema', 'readability', 'metatags', 'llms', 'aeo', 'geo', 'cwv', 'links' ];
        $failed_checks = [];

        foreach ( $modules as $mod ) {
            if ( ! isset( $analysis_results[ $mod ] ) || ! is_array( $analysis_results[ $mod ] ) ) {
                continue;
            }
            $checks = $analysis_results[ $mod ]['checks'] ?? [];
            if ( ! is_array( $checks ) ) {
                continue;
            }
            foreach ( $checks as $check ) {
                if ( ! is_array( $check ) || ! isset( $check['id'], $check['severity'] ) ) {
                    continue;
                }
                $severity = $check['severity'];
                if ( in_array( $severity, [ 'error', 'warning' ], true ) ) {
                    $check['module_key'] = $mod;
                    $failed_checks[] = $check;
                }
            }
        }

        return $failed_checks;
    }

    /**
     * Group failed checks by expert category.
     *
     * @param array $failed_checks Array of failed checks.
     * @return array Grouped checks.
     */
    public static function group_by_category( array $failed_checks ): array {
        $grouped = [
            'ui_ux'        => [],
            'seo_geo_aeo'  => [],
            'wp_architect' => [],
        ];

        foreach ( $failed_checks as $check ) {
            $category = self::get_check_category( $check );
            if ( isset( $grouped[ $category ] ) ) {
                $grouped[ $category ][] = $check;
            }
        }

        return $grouped;
    }

    /**
     * Helper to get check category.
     *
     * @param array $check Check array.
     * @return string Category identifier.
     */
    public static function get_check_category( array $check ): string {
        $module = $check['module_key'] ?? '';
        
        switch ( $module ) {
            case 'html':
            case 'readability':
                return 'ui_ux';
            case 'keyword':
            case 'schema':
            case 'metatags':
            case 'llms':
            case 'aeo':
            case 'geo':
                return 'seo_geo_aeo';
            case 'cwv':
            case 'links':
            default:
                return 'wp_architect';
        }
    }
}
