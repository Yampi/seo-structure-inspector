<?php
/**
 * BaloaStructureAuditorSEO\Ajax\Handlers
 * Central AJAX hooks router for Baloa Structure Auditor for SEO.
 */

namespace BaloaStructureAuditorSEO\Ajax;

if ( ! defined( 'ABSPATH' ) ) exit;

class Handlers {

    /**
     * Registers all AJAX endpoints and hooks them to their specific delegate handler classes.
     *
     * @return void
     */
    public static function register_hooks(): void {
        // Single URL Analysis
        add_action( 'wp_ajax_baloa_structure_auditor_seo_analyze', [ AnalysisHandlers::class, 'analyze_url' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_fetch_sitemap', [ AnalysisHandlers::class, 'fetch_sitemap' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_discover_resources', [ AnalysisHandlers::class, 'discover_resources' ] );

        // Batch scan
        add_action( 'wp_ajax_baloa_structure_auditor_seo_batch_create', [ BatchHandlers::class, 'batch_create' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_batch_analyze_url', [ BatchHandlers::class, 'batch_analyze_url' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_batch_status', [ BatchHandlers::class, 'batch_status' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_export_batch_csv', [ BatchHandlers::class, 'export_batch_csv' ] );

        // Reports
        add_action( 'wp_ajax_baloa_structure_auditor_seo_export_report', [ ReportHandlers::class, 'export_report' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_get_solution', [ ReportHandlers::class, 'get_solution' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_generate_action_plan', [ ReportHandlers::class, 'generate_action_plan' ] );

        // Auto-fixes and Reversion
        add_action( 'wp_ajax_baloa_structure_auditor_seo_autofix_info', [ AutofixHandlers::class, 'autofix_info' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_execute_autofix', [ AutofixHandlers::class, 'execute_autofix' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_get_applied_fixes', [ AutofixHandlers::class, 'get_applied_fixes' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_revert_single_fix', [ AutofixHandlers::class, 'revert_single_fix' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_revert_all_fixes', [ AutofixHandlers::class, 'revert_all_fixes' ] );

        // AI Snaps and configuration
        add_action( 'wp_ajax_baloa_structure_auditor_seo_get_history', [ AIHandlers::class, 'get_history' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_get_ai_recommendations', [ AIHandlers::class, 'get_ai_recommendations' ] );
        add_action( 'wp_ajax_baloa_structure_auditor_seo_save_ai_provider', [ AIHandlers::class, 'save_ai_provider' ] );

        // Competitor Gap
        add_action( 'wp_ajax_baloa_structure_auditor_seo_competitor_gap', [ CompetitorHandlers::class, 'analyze_competitors' ] );
    }
}
