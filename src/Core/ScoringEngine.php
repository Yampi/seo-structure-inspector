<?php
/**
 * SEOSI\Core\ScoringEngine
 *
 * Central scoring engine for SEO Structure Inspector.
 * All modules must return checks in the standard format and
 * delegate score calculation to this class.
 *
 * Standard check format:
 * [
 *   'id'             => 'unique_check_id',       // snake_case, unique per module
 *   'severity'       => 'error|warning|pass',    // error = problem, warning = advisory, pass = ok
 *   'category'       => 'seo|geo|aeo|performance|accessibility',
 *   'message'        => 'Human-readable result',
 *   'recommendation' => 'What to do about it',   // only for error/warning
 *   'context'        => [],                       // optional extra data (counts, found values, etc.)
 * ]
 *
 * @package SEO_Structure_Inspector
 * @since   0.3.0
 */

namespace SEOSI\Core;

use SEOSI\Core\DTO\ModuleResult;
use SEOSI\Core\DTO\AnalysisResult;

if ( ! defined( 'ABSPATH' ) ) exit;

class ScoringEngine {

    // ── Severity weights ──────────────────────────────────────────────────────
    // errors cost more; passes earn points; warnings are advisory
    const WEIGHT_ERROR   = 2;
    const WEIGHT_WARNING = 1;
    const WEIGHT_PASS    = 1;

    // ── Module weights for global score calculation ─────────────────────────
    // Higher weight = more impact on global score
    const MODULE_WEIGHTS = [
        'html'        => 1.0, // SEO Estructural
        'keyword'     => 1.0, // EEAT
        'schema'      => 1.2,
        'readability' => 0.8,
        'metatags'    => 1.0,
        'llms'        => 2.5, // GEO / LLMs - Peso predominante
        'aeo'         => 2.5, // AEO - Peso predominante
        'cwv'         => 1.0, // Core Web Vitals
        'links'       => 1.0,
    ];

    /**
     * Calculate a 0–100 score from a set of checks.
     *
     * Formula: pass_points / total_points * 100
     * - Each pass  counts as WEIGHT_PASS   point(s) earned
     * - Each error counts as WEIGHT_ERROR  point(s) in the denominator (not earned)
     * - Each warning counts as WEIGHT_WARNING point(s) in denominator (not earned)
     *
     * Custom weights can override per-check defaults via $weight_overrides:
     * [ 'check_id' => 2.5 ]
     *
     * @param array $checks          Array of standard check arrays.
     * @param array $weight_overrides Optional map of check_id => weight multiplier.
     * @return int Score 0–100.
     */
    public static function calculate_score( array $checks, array $weight_overrides = [] ): int {
        if ( empty( $checks ) ) return 0;

        $earned = 0.0;
        $total  = 0.0;

        foreach ( $checks as $check ) {
            $severity = $check['severity'] ?? 'warning';
            $id       = $check['id']       ?? '';

            // 'info' severity is purely informational — does not affect score.
            if ( $severity === 'info' ) {
                continue;
            }

            // Base weight for this check
            $base_weight = match ( $severity ) {
                'pass'    => self::WEIGHT_PASS,
                'warning' => self::WEIGHT_WARNING,
                'error'   => self::WEIGHT_ERROR,
                default   => self::WEIGHT_WARNING,
            };

            // Apply custom override if present
            $weight = isset( $weight_overrides[ $id ] )
                ? (float) $weight_overrides[ $id ]
                : (float) $base_weight;

            $total += $weight;

            if ( $severity === 'pass' ) {
                $earned += $weight;
            }
        }

        if ( $total === 0.0 ) return 0;

        return (int) round( ( $earned / $total ) * 100 );
    }

    /**
     * Split checks into the legacy issues/warnings/passed arrays
     * for backward compatibility with the existing JS frontend.
     *
     * @param array $checks Standard check arrays.
     * @return array { issues: string[], warnings: string[], passed: string[] }
     */
    public static function split_checks( array $checks ): array {
        $issues   = [];
        $warnings = [];
        $passed   = [];

        foreach ( $checks as $check ) {
            $msg = $check['message'] ?? '';
            switch ( $check['severity'] ?? '' ) {
                case 'error':
                    $issues[] = $msg;
                    break;
                case 'warning':
                    $warnings[] = $msg;
                    break;
                case 'pass':
                case 'info':
                    $passed[] = $msg;
                    break;
            }
        }

        return compact( 'issues', 'warnings', 'passed' );
    }

    /**
     * Extract only error/warning checks with their recommendations.
     * Used to build the "suggestions" panel in the frontend.
     *
     * @param array $checks Standard check arrays.
     * @return array[] Only checks with severity error or warning.
     */
    public static function get_actionable( array $checks ): array {
        return array_values( array_filter( $checks, function ( $c ) {
            return in_array( $c['severity'] ?? '', [ 'error', 'warning' ], true );
        } ) );
    }

    /**
     * Build the full module result as a typed DTO.
     * Convenience wrapper used by every module's analyze() method.
     *
     * @param array  $checks   Standard check arrays.
     * @param array  $details  Module-specific raw data.
     * @param array  $weight_overrides Optional per-check weight overrides.
     * @return ModuleResult
     */
    /**
     * Remove null/empty/invalid entries from a checks list.
     *
     * @param array<int, array<string, mixed>|null> $checks
     * @return array<int, array<string, mixed>>
     */
    public static function normalize_checks( array $checks ): array {
        return array_values( array_filter( $checks, function ( $check ) {
            return is_array( $check ) && $check !== [] && isset( $check['id'], $check['severity'], $check['message'] );
        } ) );
    }

    public static function build_result( array $checks, array $details = [], array $weight_overrides = [] ): ModuleResult {
        $checks = self::normalize_checks( $checks );
        $score  = self::calculate_score( $checks, $weight_overrides );
        $split  = self::split_checks( $checks );

        return new ModuleResult(
            score:    $score,
            checks:   $checks,
            issues:   $split['issues'],
            warnings: $split['warnings'],
            passed:   $split['passed'],
            details:  $details,
            skipped:  false,
            error:    null,
        );
    }

    /**
     * Validate that a check array has the required fields.
     * Use during development/debug — not in production hot paths.
     *
     * @param array $check
     * @return bool
     */
    public static function validate_check( array $check ): bool {
        $required = [ 'id', 'severity', 'category', 'message' ];
        foreach ( $required as $field ) {
            if ( empty( $check[ $field ] ) ) return false;
        }
        return in_array( $check['severity'], [ 'error', 'warning', 'pass', 'info' ], true );
    }

    /**
     * Calculate global score from AnalysisResult using module weights.
     * Excludes skipped modules and modules with errors.
     *
     * @param AnalysisResult $result Complete analysis result.
     * @return int Global score 0-100.
     */
    public static function calculate_global_score( AnalysisResult $result ): int {
        // Get weights from Settings (allows Pro versions to customize)
        $settings_weights = \SEOSI\Admin\Settings::get_option( 'module_weights', self::MODULE_WEIGHTS );
        
        // Allow filtering of module weights via hook
        $weights = apply_filters( 'seosi_module_weights', $settings_weights );

        $weighted_sum = 0.0;
        $total_weight = 0.0;

        $modules = [
            'html'        => $result->html,
            'keyword'     => $result->keyword,
            'schema'      => $result->schema,
            'readability' => $result->readability,
            'metatags'    => $result->metatags,
            'llms'        => $result->llms,
            'aeo'         => $result->aeo,
            'cwv'         => $result->cwv,
            'cwvMobile'   => $result->cwvMobile,
            'cwvDesktop'  => $result->cwvDesktop,
            'links'       => $result->links,
        ];

        foreach ( $modules as $name => $module ) {
            // Skip null modules (not analyzed)
            if ( $module === null ) {
                continue;
            }

            // Skip modules with errors or skipped status
            if ( $module->skipped || $module->error !== null ) {
                continue;
            }

            // Special handling for CWV: use average of mobile and desktop if both available
            if ( $name === 'cwvMobile' || $name === 'cwvDesktop' ) {
                // Skip individual CWV results when both are available
                // We'll calculate the average separately
                continue;
            }

            $weight = $weights[ $name ] ?? 1.0;
            $weighted_sum += $module->score * $weight;
            $total_weight += $weight;
        }

        // Calculate average of mobile and desktop CWV if both are available
        if ( $result->cwvMobile !== null && ! $result->cwvMobile->skipped && $result->cwvMobile->error === null &&
             $result->cwvDesktop !== null && ! $result->cwvDesktop->skipped && $result->cwvDesktop->error === null ) {
            $cwv_avg = ( $result->cwvMobile->score + $result->cwvDesktop->score ) / 2;
            $weight = $weights['cwv'] ?? 1.0;
            $weighted_sum += $cwv_avg * $weight;
            $total_weight += $weight;
        } elseif ( $result->cwv !== null && ! $result->cwv->skipped && $result->cwv->error === null ) {
            // Fallback to single CWV result if mobile/desktop not available
            $weight = $weights['cwv'] ?? 1.0;
            $weighted_sum += $result->cwv->score * $weight;
            $total_weight += $weight;
        }

        if ( $total_weight === 0.0 ) return 0;
        return (int) round( $weighted_sum / $total_weight );
    }
}
