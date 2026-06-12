<?php

namespace BaloaStructureAuditorSEO\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class ResultPresenter {
    public static function localize_analysis_results( array $results ): array {
        // Merge 'geo' checks and details into 'llms' to unify GEO / LLMs under a single frontend module
        if ( isset( $results['geo'] ) && is_array( $results['geo'] ) ) {
            if ( ! isset( $results['llms'] ) || ! is_array( $results['llms'] ) ) {
                $results['llms'] = [
                    'score'    => 0,
                    'checks'   => [],
                    'issues'   => [],
                    'warnings' => [],
                    'passed'   => [],
                    'details'  => [],
                    'skipped'  => false,
                    'error'    => null,
                ];
            }
            $geo_checks = $results['geo']['checks'] ?? [];
            $llms_checks = $results['llms']['checks'] ?? [];
            
            // Combine checks
            $combined_checks = array_merge( $llms_checks, $geo_checks );
            $results['llms']['checks'] = ScoringEngine::normalize_checks( $combined_checks );
            
            // Combine details
            $results['llms']['details'] = array_merge( $results['llms']['details'] ?? [], $results['geo']['details'] ?? [] );
            
            // Recalculate combined score
            $results['llms']['score'] = ScoringEngine::calculate_score( $results['llms']['checks'] );
        }

        $modules = [ 'html', 'keyword', 'schema', 'readability', 'metatags', 'llms', 'aeo', 'cwv', 'links' ];

        foreach ( $modules as $mod ) {
            if ( ! isset( $results[ $mod ] ) || ! is_array( $results[ $mod ] ) ) continue;
            $results[ $mod ] = self::localize_module_result( $results[ $mod ], $mod, $results['url'] ?? '' );
        }

        return $results;
    }

    private static function localize_module_result( array $module, string $mod_key, string $url ): array {
        if ( empty( $module['checks'] ) || ! is_array( $module['checks'] ) ) return $module;

        $t      = static fn( string $s ): string => $s;
        $checks = $module['checks'];

        foreach ( $checks as &$check ) {
            if ( ! is_array( $check ) ) continue;

            $check_id = $check['id'] ?? '';
            if ( ! empty( $check_id ) && Plugin::get_instance()->get_license()->is_premium() && class_exists( '\BaloaStructureAuditorSEO\Pro\Services\AutoFixService' ) ) {
                $autofix_info = \BaloaStructureAuditorSEO\Pro\Services\AutoFixService::get_autofix_info( $check_id, $mod_key, $url );
                $check['supports_autofix'] = ! empty( $autofix_info['available'] );
            } else {
                $check['supports_autofix'] = false;
            }

            if ( CheckCatalog::present( $check ) === null ) continue;

            foreach ( [ 'title', 'problem', 'why', 'how', 'message', 'recommendation' ] as $k ) {
                if ( ! array_key_exists( $k, $check ) ) continue;
                $en_key = $k . '_en';
                if ( array_key_exists( $en_key, $check ) ) continue;
                $check[ $en_key ] = $check[ $k ];
            }
        }
        unset( $check );

        $checks = CheckPresenter::apply_with( $checks, $t );

        $split = ScoringEngine::split_checks( $checks );
        $module['issues']   = $split['issues'];
        $module['warnings'] = $split['warnings'];
        $module['passed']   = $split['passed'];
        $module['checks']   = $checks;

        return $module;
    }
}
