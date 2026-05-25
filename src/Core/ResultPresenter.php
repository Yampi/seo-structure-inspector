<?php

namespace SEOSI\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class ResultPresenter {
    public static function localize_analysis_results( array $results ): array {
        $modules = [ 'html', 'keyword', 'schema', 'readability', 'metatags', 'llms', 'aeo', 'cwv', 'links' ];

        foreach ( $modules as $mod ) {
            if ( ! isset( $results[ $mod ] ) || ! is_array( $results[ $mod ] ) ) continue;
            $results[ $mod ] = self::localize_module_result( $results[ $mod ], $mod, $results['url'] ?? '' );
        }

        return $results;
    }

    private static function localize_module_result( array $module, string $mod_key, string $url ): array {
        if ( empty( $module['checks'] ) || ! is_array( $module['checks'] ) ) return $module;

        $checks = $module['checks'];
        $t      = static fn( string $s ): string => __( $s, 'seo-si' );

        foreach ( $checks as &$check ) {
            if ( ! is_array( $check ) ) continue;

            $check_id = $check['id'] ?? '';
            if ( ! empty( $check_id ) ) {
                if ( ! class_exists( '\SEOSI\Services\AutoFixService' ) ) {
                    $dir = defined( 'SEOSI_DIR' ) ? SEOSI_DIR : dirname( __DIR__, 2 ) . '/';
                    require_once $dir . 'src/Services/AutoFixService.php';
                }
                $autofix_info = \SEOSI\Services\AutoFixService::get_autofix_info( $check_id, $mod_key, $url );
                $check['supports_autofix'] = ! empty( $autofix_info['available'] );
            }

            if ( CheckCatalog::present( $check, static fn( string $s ): string => $s ) === null ) continue;

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
