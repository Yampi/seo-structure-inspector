<?php
/**
 * SEOSI\Core\Hooks
 * Centralized hooks and filters for plugin extensibility.
 */

namespace SEOSI\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Hooks {

    // ── Analysis Hooks ─────────────────────────────────────────────────────────

    /**
     * Filter: Modify HTML before analysis.
     *
     * @param string $html Raw HTML.
     * @param string $url  Page URL.
     * @return string Modified HTML.
     */
    public static function filter_html( string $html, string $url ): string {
        return apply_filters( 'seosi_filter_html', $html, $url );
    }

    /**
     * Filter: Modify analysis results.
     *
     * @param array  $results Analysis results.
     * @param string $url    Page URL.
     * @return array Modified results.
     */
    public static function filter_results( array $results, string $url ): array {
        return apply_filters( 'seosi_filter_results', $results, $url );
    }

    /**
     * Action: Fired after analysis is complete.
     *
     * @param array  $results Analysis results.
     * @param string $url    Page URL.
     */
    public static function action_after_analysis( array $results, string $url ): void {
        do_action( 'seosi_after_analysis', $results, $url );
    }

    /**
     * Filter: Modify global score calculation.
     *
     * @param int    $score  Calculated global score.
     * @param array  $results Full analysis results.
     * @return int Modified score.
     */
    public static function filter_global_score( int $score, array $results ): int {
        return apply_filters( 'seosi_filter_global_score', $score, $results );
    }

    // ── Module Hooks ───────────────────────────────────────────────────────────

    /**
     * Filter: Add custom analyzer modules.
     *
     * @param array $modules List of analyzer classes.
     * @return array Modified modules list.
     */
    public static function filter_analyzers( array $modules ): array {
        return apply_filters( 'seosi_filter_analyzers', $modules );
    }

    /**
     * Filter: Modify specific module results.
     *
     * @param array  $module_results Results from a specific module.
     * @param string $module_name   Name of the module (e.g., 'html', 'keyword').
     * @param string $url           Page URL.
     * @return array Modified module results.
     */
    public static function filter_module_result( array $module_results, string $module_name, string $url ): array {
        return apply_filters( 'seosi_filter_module_result', $module_results, $module_name, $url );
    }

    // ── Fetching Hooks ─────────────────────────────────────────────────────────

    /**
     * Filter: Modify HTTP request args before fetching HTML.
     *
     * @param array  $args HTTP request args.
     * @param string $url  Target URL.
     * @return array Modified args.
     */
    public static function filter_fetch_args( array $args, string $url ): array {
        return apply_filters( 'seosi_filter_fetch_args', $args, $url );
    }

    /**
     * Filter: Modify fetched HTML.
     *
     * @param string $html Fetched HTML.
     * @param string $url  Source URL.
     * @return string Modified HTML.
     */
    public static function filter_fetched_html( string $html, string $url ): string {
        return apply_filters( 'seosi_filter_fetched_html', $html, $url );
    }

    // ── Batch Hooks ────────────────────────────────────────────────────────────

    /**
     * Action: Fired when a batch job is created.
     *
     * @param string $job_id  Job ID.
     * @param array  $urls    List of URLs.
     * @param string $keyword Target keyword.
     */
    public static function action_batch_created( string $job_id, array $urls, string $keyword ): void {
        do_action( 'seosi_batch_created', $job_id, $urls, $keyword );
    }

    /**
     * Action: Fired when a batch job is completed.
     *
     * @param string $job_id Job ID.
     * @param array  $job    Complete job data.
     */
    public static function action_batch_completed( string $job_id, array $job ): void {
        do_action( 'seosi_batch_completed', $job_id, $job );
    }

    /**
     * Action: Fired after each URL in a batch is analyzed.
     *
     * @param string $job_id Job ID.
     * @param string $url    Analyzed URL.
     * @param array  $result Analysis result.
     */
    public static function action_batch_url_analyzed( string $job_id, string $url, array $result ): void {
        do_action( 'seosi_batch_url_analyzed', $job_id, $url, $result );
    }

    // ── Report Hooks ────────────────────────────────────────────────────────────

    /**
     * Filter: Modify report HTML before export.
     *
     * @param string $html    Report HTML.
     * @param array  $results Analysis results.
     * @param string $url     Page URL.
     * @return string Modified HTML.
     */
    public static function filter_report_html( string $html, array $results, string $url ): string {
        return apply_filters( 'seosi_filter_report_html', $html, $results, $url );
    }

    /**
     * Filter: Modify report filename.
     *
     * @param string $filename Generated filename.
     * @param array  $results  Analysis results.
     * @param string $url      Page URL.
     * @return string Modified filename.
     */
    public static function filter_report_filename( string $filename, array $results, string $url ): string {
        return apply_filters( 'seosi_filter_report_filename', $filename, $results, $url );
    }
}
