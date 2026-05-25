<?php
/**
 * Plugin Name:       SEO Structure Inspector
 * Plugin URI:        https://tecnicoelho.com/seo-structure-inspector
 * Description:       Advanced SEO analysis with HTML structure, schema validation,
 *                    AEO/GEO optimization, Core Web Vitals, and AI visibility checks.
 * Version:           1.6.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Brian Yamanue Baloa Gota
 * Author URI:        https://tecnicoelho.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       seo-si
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Constants ─────────────────────────────────────────────────────────────────
define( 'SEOSI_VERSION', '1.6.0' );
define( 'SEOSI_DIR',     plugin_dir_path( __FILE__ ) );
define( 'SEOSI_URL',     plugin_dir_url( __FILE__ ) );
define( 'SEOSI_MIN_WP',  '6.0' );
define( 'SEOSI_MIN_PHP', '8.1' );
define( 'SEOSI_CRON_HOOK', 'seosi_run_scheduled' );

// ── Composer Autoload ──────────────────────────────────────────────────────────
$seosi_autoload = SEOSI_DIR . 'vendor/autoload.php';

if ( file_exists( $seosi_autoload ) ) {
    require_once $seosi_autoload;
} else {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__( 'SEO Structure Inspector: falta vendor/autoload.php. Ejecuta composer install.', 'seo-si' );
        echo '</p></div>';
    } );
    return;
}

// ── Activation/Deactivation Hooks for Scheduled Analysis ─────────────────────
register_activation_hook( __FILE__, 'seosi_activate_plugin' );
register_deactivation_hook( __FILE__, 'seosi_deactivate_plugin' );

function seosi_activate_plugin() {
    if ( version_compare( PHP_VERSION, SEOSI_MIN_PHP, '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( sprintf( 'SEO Structure Inspector requiere PHP %s o superior. Tu versión actual es %s.', SEOSI_MIN_PHP, PHP_VERSION ) );
    }

    if ( version_compare( get_bloginfo( 'version' ), SEOSI_MIN_WP, '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( sprintf( 'SEO Structure Inspector requiere WordPress %s o superior. Tu versión actual es %s.', SEOSI_MIN_WP, get_bloginfo( 'version' ) ) );
    }

    if ( ! wp_next_scheduled( SEOSI_CRON_HOOK ) ) {
        wp_schedule_event( time(), 'hourly', SEOSI_CRON_HOOK );
    }
}

function seosi_deactivate_plugin() {
    wp_clear_scheduled_hook( SEOSI_CRON_HOOK );
}

// ── Bootstrap Plugin via Centralized Hook Registry ─────────────────────────────
if ( class_exists( 'SEOSI\Core\HookRegistry' ) ) {
    SEOSI\Core\HookRegistry::boot();
} else {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__( 'SEO Structure Inspector: no se pudieron cargar las clases del plugin. Revisa vendor/autoload.php.', 'seo-si' );
        echo '</p></div>';
    } );
}
