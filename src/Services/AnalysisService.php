<?php
/**
 * BaloaStructureAuditorSEO\Services\AnalysisService
 *
 * Central service layer for running SEO analysis.
 * Coordinates fetching, normalization, and analysis layers.
 * Supports dynamic agnostic registry for OCP compliance.
 */

namespace BaloaStructureAuditorSEO\Services;

use BaloaStructureAuditorSEO\Core\AnalyzerRegistry;
use BaloaStructureAuditorSEO\Core\Plugin;
use BaloaStructureAuditorSEO\Core\Hooks;
use BaloaStructureAuditorSEO\Core\DTO\AnalysisResult;
use BaloaStructureAuditorSEO\Free\Analyzers\KeywordAnalyzer;

if ( ! defined( 'ABSPATH' ) ) exit;

class AnalysisService {

    /**
     * Run complete analysis on HTML content.
     *
     * @param string $html     HTML content to analyze.
     * @param string $url      Page URL.
     * @param string $keyword  Optional target keyword.
     * @param string $api_key  Optional PageSpeed API key.
     * @return AnalysisResult Complete analysis results.
     */
    public static function analyze( string $html, string $url, string $keyword = '', string $api_key = '' ): AnalysisResult {
        
        $is_premium = Plugin::get_instance()->get_license()->is_premium();

        // â”€â”€ Core Web Vitals (Premium check) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $enable_cwv = \BaloaStructureAuditorSEO\Admin\Settings::get_option( 'enable_cwv' ) && $is_premium;
        
        if ( $enable_cwv && class_exists( '\BaloaStructureAuditorSEO\Pro\Analyzers\CoreWebVitals' ) ) {
            $cwv_both = \BaloaStructureAuditorSEO\Pro\Analyzers\CoreWebVitals::analyze_both( $url, $api_key );
        } else {
            $cwv_both = [ 
                'mobile'  => [ 'score' => null, 'checks' => [], 'skipped' => true ],
                'desktop' => [ 'score' => null, 'checks' => [], 'skipped' => true ]
            ];
        }

        // Initialize raw results array
        $raw_results = [
            'cwv'         => $cwv_both['mobile'],
            'cwv_mobile'  => $cwv_both['mobile'],
            'cwv_desktop' => $cwv_both['desktop'],
        ];

        // â”€â”€ Keyword analysis (Free / Basic) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( $keyword ) {
            $raw_results['keyword'] = KeywordAnalyzer::analyze( $html, $keyword );
        } else {
            $raw_results['keyword'] = null;
        }

        // â”€â”€ Dynamic page-level analyzers (OCP) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        foreach ( AnalyzerRegistry::get_all() as $key => $class_name ) {
            try {
                $raw_results[ $key ] = $class_name::analyze( $html, $url );
            } catch ( \Throwable $e ) {
                \BaloaStructureAuditorSEO\Core\Logger::error( sprintf( 'Failed running analyzer %s: %s', $key, $e->getMessage() ) );
                $raw_results[ $key ] = [ 'error' => $e->getMessage(), 'skipped' => true ];
            }
        }

        // Convert raw arrays to ModuleResult DTOs and apply auto-fixes
        self::merge_pro_analyzers( $raw_results );
        $module_results = [];
        foreach ( [ 'html', 'schema', 'readability', 'metatags', 'links', 'images', 'aeo', 'llms', 'keyword', 'cwv', 'cwv_mobile', 'cwv_desktop', 'geo' ] as $mod_key ) {
            $val = $raw_results[ $mod_key ] ?? null;
            if ( $val === null ) {
                $module_results[ $mod_key ] = null;
            } else {
                $module_results[ $mod_key ] = self::apply_resolved_checks( self::array_to_module_result( $val ), $url );
            }
        }

        // Create AnalysisResult with temporary global score (will be recalculated)
        $result = new AnalysisResult(
            url:         esc_url( $url ),
            globalScore: 0, // temporary
            html:        $module_results['html'],
            keyword:     $module_results['keyword'],
            schema:      $module_results['schema'],
            readability: $module_results['readability'],
            metatags:    $module_results['metatags'],
            llms:        $module_results['llms'],
            aeo:         $module_results['aeo'],
            geo:         $module_results['geo'],
            cwv:         $module_results['cwv'],
            cwvMobile:   $module_results['cwv_mobile'],
            cwvDesktop:  $module_results['cwv_desktop'],
            links:       $module_results['links'],
            images:      $module_results['images'],
            strategy:    'direct',
            analyzedAt:  time(),
        );

        // Calculate global score from module results using weighted system
        $global_score = \BaloaStructureAuditorSEO\Core\ScoringEngine::calculate_global_score( $result );

        // Recreate AnalysisResult with correct global score
        $result = new AnalysisResult(
            url:         esc_url( $url ),
            globalScore: $global_score,
            html:        $module_results['html'],
            keyword:     $module_results['keyword'],
            schema:      $module_results['schema'],
            readability: $module_results['readability'],
            metatags:    $module_results['metatags'],
            llms:        $module_results['llms'],
            aeo:         $module_results['aeo'],
            geo:         $module_results['geo'],
            cwv:         $module_results['cwv'],
            cwvMobile:   $module_results['cwv_mobile'],
            cwvDesktop:  $module_results['cwv_desktop'],
            links:       $module_results['links'],
            images:      $module_results['images'],
            strategy:    'direct',
            analyzedAt:  time(),
        );

        // Apply hooks (convert to array for hooks, then back to DTO)
        $result_array = $result->toArray();
        $result_array = Hooks::filter_results( $result_array, $url );
        Hooks::action_after_analysis( $result_array, $url );
        $result = AnalysisResult::fromArray( $result_array );

        return $result;
    }

    /**
     * Convert array result from analyzer to ModuleResult DTO.
     *
     * @param array|\BaloaStructureAuditorSEO\Core\DTO\ModuleResult $array Result array from analyzer.
     * @return \BaloaStructureAuditorSEO\Core\DTO\ModuleResult
     */
    private static function array_to_module_result( array|\BaloaStructureAuditorSEO\Core\DTO\ModuleResult $array ): \BaloaStructureAuditorSEO\Core\DTO\ModuleResult {
        if ( $array instanceof \BaloaStructureAuditorSEO\Core\DTO\ModuleResult ) { return $array; }
        return new \BaloaStructureAuditorSEO\Core\DTO\ModuleResult(
            score:    $array['score'] ?? 0,
            checks:   $array['checks'] ?? [],
            issues:   $array['issues'] ?? [],
            warnings: $array['warnings'] ?? [],
            passed:   $array['passed'] ?? [],
            details:  $array['details'] ?? [],
            skipped:  $array['skipped'] ?? false,
            error:    $array['error'] ?? null,
        );
    }

    /**
     * Apply resolved check overrides dynamically.
     *
     * @param \BaloaStructureAuditorSEO\Core\DTO\ModuleResult $module_result Original module result.
     * @param string                       $url           The analyzed URL.
     * @return \BaloaStructureAuditorSEO\Core\DTO\ModuleResult Modified module result.
     */
    private static function apply_resolved_checks( \BaloaStructureAuditorSEO\Core\DTO\ModuleResult $module_result, string $url ): \BaloaStructureAuditorSEO\Core\DTO\ModuleResult {
        if ( $module_result->skipped || $module_result->error !== null ) {
            return $module_result;
        }

        $checks = $module_result->checks;
        $modified = false;

        if ( class_exists( '\BaloaStructureAuditorSEO\Pro\Services\AutoFixService' ) ) {
            foreach ( $checks as &$check ) {
                $check_id = $check['id'] ?? '';
                if ( ! empty( $check_id ) && \BaloaStructureAuditorSEO\Pro\Services\AutoFixService::is_check_resolved( $url, $check_id ) ) {
                    if ( isset( $check['severity'] ) && $check['severity'] !== 'pass' ) {
                        $check['severity'] = 'pass';
                        if ( isset( $check['message'] ) ) {
                            $check['message'] .= ' (Solucionado vía Auto-Fix)';
                        }
                        $modified = true;
                    }
                }
            }
            unset( $check );
        }

        if ( $modified ) {
            return \BaloaStructureAuditorSEO\Core\ScoringEngine::build_result( $checks, $module_result->details );
        }

        return $module_result;
    }

    private static function merge_pro_analyzers( array &$raw_results ): void {
        self::merge_entities( $raw_results );
        self::merge_naturalness( $raw_results );
    }

    private static function merge_entities( array &$raw_results ): void {
        if ( ! isset( $raw_results['entities'] ) ) {
            return;
        }
        $ent = self::array_to_module_result( $raw_results['entities'] );
        $kw = self::array_to_module_result( $raw_results['keyword'] ?? [] );
        $raw_results['keyword'] = new \BaloaStructureAuditorSEO\Core\DTO\ModuleResult(
            score: $kw->score > 0 ? (int) round( ( $kw->score + $ent->score ) / 2 ) : $ent->score,
            checks: array_merge( $kw->checks, $ent->checks ),
            issues: array_merge( $kw->issues, $ent->issues ),
            warnings: array_merge( $kw->warnings, $ent->warnings ),
            passed: array_merge( $kw->passed, $ent->passed ),
            details: array_merge( $kw->details, [ 'entities' => $ent->details ] )
        );
    }

    private static function merge_naturalness( array &$raw_results ): void {
        if ( ! isset( $raw_results['naturalness'] ) || ! isset( $raw_results['readability'] ) ) {
            return;
        }
        $read = self::array_to_module_result( $raw_results['readability'] );
        $nat = self::array_to_module_result( $raw_results['naturalness'] );
        $raw_results['readability'] = new \BaloaStructureAuditorSEO\Core\DTO\ModuleResult(
            score: (int) round( ( $read->score + $nat->score ) / 2 ),
            checks: array_merge( $read->checks, $nat->checks ),
            issues: array_merge( $read->issues, $nat->issues ),
            warnings: array_merge( $read->warnings, $nat->warnings ),
            passed: array_merge( $read->passed, $nat->passed ),
            details: array_merge( $read->details, [ 'naturalness' => $nat->details ] )
        );
    }
}
