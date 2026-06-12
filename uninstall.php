<?php
/**
 * Uninstall routine for Baloa Structure Auditor for SEO.
 * This file is automatically called by WordPress when the plugin is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// â”€â”€ Delete plugin options â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// ── Delete plugin options ────────────────────────────────────────────────────────
delete_option( 'baloa_structure_auditor_seo_options' );
delete_option( 'baloa_structure_auditor_seo_url_overrides' );
delete_option( 'baloa_structure_auditor_seo_resolved_checks' );
delete_option( 'baloa_structure_auditor_seo_llms_txt_content' );
delete_option( 'baloa_structure_auditor_seo_llms_full_txt_content' );

// ── Delete post meta ────────────────────────────────────────────────────────
global $wpdb;
$baloa_structure_auditor_seo_meta_keys = [
    '_baloa_meta_desc',
    '_baloa_schema',
    '_baloa_meta_title',
    '_baloa_canonical',
    '_baloa_robots',
    '_baloa_og_title',
    '_baloa_og_desc',
    '_baloa_og_img',
    '_baloa_tw_card',
    '_baloa_tw_title',
    '_baloa_tw_desc',
    '_baloa_tw_img',
    '_baloa_cwv_preload',
    '_baloa_aeo_tldr',
    '_baloa_autofill_alt',
    '_baloa_history',
    '_baloa_schedule',
    '_baloa_keyword',
    '_baloa_json_ld_schema'
];

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",
        $baloa_structure_auditor_seo_meta_keys[0],
        $baloa_structure_auditor_seo_meta_keys[1],
        $baloa_structure_auditor_seo_meta_keys[2],
        $baloa_structure_auditor_seo_meta_keys[3],
        $baloa_structure_auditor_seo_meta_keys[4],
        $baloa_structure_auditor_seo_meta_keys[5],
        $baloa_structure_auditor_seo_meta_keys[6],
        $baloa_structure_auditor_seo_meta_keys[7],
        $baloa_structure_auditor_seo_meta_keys[8],
        $baloa_structure_auditor_seo_meta_keys[9],
        $baloa_structure_auditor_seo_meta_keys[10],
        $baloa_structure_auditor_seo_meta_keys[11],
        $baloa_structure_auditor_seo_meta_keys[12],
        $baloa_structure_auditor_seo_meta_keys[13],
        $baloa_structure_auditor_seo_meta_keys[14],
        $baloa_structure_auditor_seo_meta_keys[15],
        $baloa_structure_auditor_seo_meta_keys[16],
        $baloa_structure_auditor_seo_meta_keys[17],
        $baloa_structure_auditor_seo_meta_keys[18]
    )
);

// ── Delete transients ────────────────────────────────────────────────────────
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_baloa_%'
     OR option_name LIKE '_transient_timeout_baloa_%'"
);

// â”€â”€ Clear scheduled cron events â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
wp_clear_scheduled_hook( 'baloa_structure_auditor_seo_run_scheduled' );
