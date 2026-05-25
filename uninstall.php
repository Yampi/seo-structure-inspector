<?php
/**
 * Uninstall routine for SEO Structure Inspector.
 * This file is automatically called by WordPress when the plugin is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// ── Delete plugin options ─────────────────────────────────────────────────────
delete_option( 'seosi_options' );
delete_option( 'seosi_url_overrides' );
delete_option( 'seosi_resolved_checks' );
delete_option( 'seosi_llms_txt_content' );
delete_option( 'seosi_llms_full_txt_content' );

// ── Delete post meta ──────────────────────────────────────────────────────────
global $wpdb;
$meta_keys = [
    '_seosi_meta_desc',
    '_seosi_schema',
    '_seosi_meta_title',
    '_seosi_canonical',
    '_seosi_robots',
    '_seosi_og_title',
    '_seosi_og_desc',
    '_seosi_og_img',
    '_seosi_tw_card',
    '_seosi_tw_title',
    '_seosi_tw_desc',
    '_seosi_tw_img',
    '_seosi_cwv_preload',
    '_seosi_aeo_tldr',
    '_seosi_autofill_alt',
    '_seosi_history',
    '_seosi_schedule'
];

$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ($placeholders)",
        ...$meta_keys
    )
);

// ── Delete transients ─────────────────────────────────────────────────────────
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_seosi_%'
     OR option_name LIKE '_transient_timeout_seosi_%'"
);

// ── Clear scheduled cron events ─────────────────────────────────────────────────
wp_clear_scheduled_hook( 'seosi_run_scheduled' );
