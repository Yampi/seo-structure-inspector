<?php
/**
 * Plugin Name:       Baloa Structure Auditor for SEO
 * Plugin URI:        https://www.tecnicoelho.com
 * Description:       Advanced SEO analysis with HTML structure, schema validation,
 *                    AEO/GEO optimization, Core Web Vitals, and AI visibility checks.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Brian Baloa (TecniCoelho)
 * Author URI:        https://tecnicoelho.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       baloa-structure-auditor-seo
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// â”€â”€ Constants â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
define( 'BALOA_STRUCTURE_AUDITOR_SEO_VERSION',   '2.0.0' );
define( 'BALOA_STRUCTURE_AUDITOR_SEO_DIR',       plugin_dir_path( __FILE__ ) );
define( 'BALOA_STRUCTURE_AUDITOR_SEO_URL',       plugin_dir_url( __FILE__ ) );
define( 'BALOA_STRUCTURE_AUDITOR_SEO_MIN_WP',    '6.0' );
define( 'BALOA_STRUCTURE_AUDITOR_SEO_MIN_PHP',   '8.1' );
define( 'BALOA_STRUCTURE_AUDITOR_SEO_CRON_HOOK', 'baloa_structure_auditor_seo_run_scheduled' );

// ── Autoload ──────────────────────────────────────────────────────────
spl_autoload_register( function ( $class ) {
    if ( str_starts_with( $class, 'BaloaStructureAuditorSEO\\' ) ) {
        $relative_class = substr( $class, strlen( 'BaloaStructureAuditorSEO\\' ) );
        $file = BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/' . str_replace( '\\', '/', $relative_class ) . '.php';
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
} );

// Optional Composer autoload in development environments
// if ( file_exists( BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'vendor/autoload.php' ) ) {
//     require_once BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'vendor/autoload.php';
// }

// ── Activation/Deactivation Hooks for Scheduled Analysis ─────────────────────
register_activation_hook( __FILE__, [ \BaloaStructureAuditorSEO\Core\Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \BaloaStructureAuditorSEO\Core\Plugin::class, 'deactivate' ] );

// ── Bootstrap Plugin via Centralized Hook Registry ─────────────────────────────
if ( class_exists( '\BaloaStructureAuditorSEO\Core\HookRegistry' ) ) {
    \BaloaStructureAuditorSEO\Core\HookRegistry::boot();
} else {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__( 'Baloa Structure Auditor for SEO: no se pudieron cargar las clases del plugin. Revisa vendor/autoload.php.', 'baloa-structure-auditor-seo' );
        echo '</p></div>';
    } );
}
