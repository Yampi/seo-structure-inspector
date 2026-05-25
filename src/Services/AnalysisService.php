<?php
/**
 * SEOSI\Services\AnalysisService
 *
 * Central service layer for running SEO analysis.
 * Coordinates fetching, normalization, and analysis layers.
 * Supports dynamic agnóstico registry for OCP compliance.
 */

namespace SEOSI\Services;

use SEOSI\Core\AnalyzerRegistry;
use SEOSI\Core\Plugin;
use SEOSI\Core\Hooks;
use SEOSI\Core\DTO\AnalysisResult;
use SEOSI\Free\Analyzers\KeywordAnalyzer;

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

        // ── Core Web Vitals (Premium check) ───────────────────────────────────
        $enable_cwv = \SEOSI\Admin\Settings::get_option( 'enable_cwv' ) && $is_premium;
        
        if ( $enable_cwv && class_exists( '\SEOSI\Pro\Analyzers\CoreWebVitals' ) ) {
            $cwv_both = \SEOSI\Pro\Analyzers\CoreWebVitals::analyze_both( $url, $api_key );
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

        // ── Keyword analysis (Free / Basic) ──────────────────────────────────
        if ( $keyword ) {
            $raw_results['keyword'] = KeywordAnalyzer::analyze( $html, $keyword );
        } else {
            $raw_results['keyword'] = null;
        }

        // ── Dynamic page-level analyzers (OCP) ────────────────────────────────
        foreach ( AnalyzerRegistry::get_all() as $key => $class_name ) {
            try {
                $raw_results[ $key ] = $class_name::analyze( $html, $url );
            } catch ( \Throwable $e ) {
                error_log( sprintf( '[SEOSI] Failed running analyzer %s: %s', $key, $e->getMessage() ) );
                $raw_results[ $key ] = [ 'error' => $e->getMessage(), 'skipped' => true ];
            }
        }

        // Convert raw arrays to ModuleResult DTOs and apply auto-fixes
        $module_results = [];
        foreach ( [ 'html', 'schema', 'readability', 'metatags', 'links', 'aeo', 'llms', 'keyword', 'cwv', 'cwv_mobile', 'cwv_desktop' ] as $mod_key ) {
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
            cwv:         $module_results['cwv'],
            cwvMobile:   $module_results['cwv_mobile'],
            cwvDesktop:  $module_results['cwv_desktop'],
            links:       $module_results['links'],
            strategy:    'direct',
            analyzedAt:  time(),
        );

        // Calculate global score from module results using weighted system
        $global_score = \SEOSI\Core\ScoringEngine::calculate_global_score( $result );

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
            cwv:         $module_results['cwv'],
            cwvMobile:   $module_results['cwv_mobile'],
            cwvDesktop:  $module_results['cwv_desktop'],
            links:       $module_results['links'],
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
     * @param array|\SEOSI\Core\DTO\ModuleResult $array Result array from analyzer.
     * @return \SEOSI\Core\DTO\ModuleResult
     */
    private static function array_to_module_result( array|\SEOSI\Core\DTO\ModuleResult $array ): \SEOSI\Core\DTO\ModuleResult {
        if ( $array instanceof \SEOSI\Core\DTO\ModuleResult ) { return $array; }
        return new \SEOSI\Core\DTO\ModuleResult(
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
     * @param \SEOSI\Core\DTO\ModuleResult $module_result Original module result.
     * @param string                       $url           The analyzed URL.
     * @return \SEOSI\Core\DTO\ModuleResult Modified module result.
     */
    private static function apply_resolved_checks( \SEOSI\Core\DTO\ModuleResult $module_result, string $url ): \SEOSI\Core\DTO\ModuleResult {
        if ( $module_result->skipped || $module_result->error !== null ) {
            return $module_result;
        }

        $checks = $module_result->checks;
        $modified = false;

        if ( class_exists( '\SEOSI\Pro\Services\AutoFixService' ) ) {
            foreach ( $checks as &$check ) {
                $check_id = $check['id'] ?? '';
                if ( ! empty( $check_id ) && \SEOSI\Pro\Services\AutoFixService::is_check_resolved( $url, $check_id ) ) {
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
            return \SEOSI\Core\ScoringEngine::build_result( $checks, $module_result->details );
        }

        return $module_result;
    }
}
