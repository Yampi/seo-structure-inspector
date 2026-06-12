<?php
/**
 * BaloaStructureAuditorSEO\Core\Plugin
 * Main bootstrap class for Baloa Structure Auditor for SEO.
 */

namespace BaloaStructureAuditorSEO\Core;

use BaloaStructureAuditorSEO\Core\Contracts\LicenseProviderInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

class Plugin {

    private static $instance = null;
    private ?LicenseProviderInterface $license_provider = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set the license provider implementation.
     *
     * @param LicenseProviderInterface $provider The licensing provider.
     */
    public function set_license_provider( LicenseProviderInterface $provider ): void {
        $this->license_provider = $provider;
    }

    /**
     * Get the active license provider.
     *
     * @return LicenseProviderInterface The licensing provider instance.
     */
    public function get_license(): LicenseProviderInterface {
        if ( null === $this->license_provider ) {
            if ( class_exists( '\\BaloaStructureAuditorSEO\\Infrastructure\\Licensing\\LocalLicenseProvider' ) ) {
                $this->license_provider = new \BaloaStructureAuditorSEO\Infrastructure\Licensing\LocalLicenseProvider();
            }
        }
        return $this->license_provider;
    }

    public function init(): void {
        // Reserved for future lightweight init if needed.
    }

    public function enqueue_admin_assets( string $hook ): void {
        if ( ! Capabilities::user_can_analyze() ) {
            return;
        }

        $is_dashboard = ( 'toplevel_page_baloa-structure-auditor-seo' === $hook );
        $is_glossary  = ( str_contains( $hook, 'baloa-glossary' ) );
        $is_settings  = ( str_contains( $hook, 'baloa-settings' ) );
        $is_reversion = ( str_contains( $hook, 'baloa-reversion' ) );
        $is_editor    = in_array( $hook, [ 'post.php', 'post-new.php' ], true );

        if ( ! $is_dashboard && ! $is_editor && ! $is_glossary && ! $is_settings && ! $is_reversion ) {
            return;
        }

        if ( $is_editor ) {
            wp_enqueue_style(
                'baloa-structure-auditor-seo-admin',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin.css',
                [],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION
            );
        }

        if ( $is_dashboard || $is_glossary || $is_settings || $is_reversion ) {
            wp_enqueue_style(
                'baloa-structure-auditor-seo-dashboard',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-dashboard.css',
                [],
                filemtime( BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'assets/admin-dashboard.css' )
            );
        }

        wp_enqueue_script(
            'baloa-structure-auditor-seo-admin',
            BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin.js',
            [ 'jquery' ],
            BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
            true
        );

        if ( $is_dashboard || $is_editor ) {
            wp_enqueue_script(
                'baloa-admin-autofix',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-autofix.js',
                [ 'jquery', 'baloa-structure-auditor-seo-admin' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );
        }

        if ( $is_dashboard ) {
            wp_enqueue_script(
                'baloa-admin-dashboard',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-dashboard.js',
                [ 'jquery', 'baloa-structure-auditor-seo-admin' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );

            wp_enqueue_script(
                'baloa-admin-problems',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-problems.js',
                [ 'jquery', 'baloa-structure-auditor-seo-admin' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );

            wp_enqueue_script(
                'baloa-admin-optimizer',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-optimizer.js',
                [ 'jquery', 'baloa-structure-auditor-seo-admin' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );

            wp_enqueue_script(
                'baloa-admin-sitemap',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-sitemap.js',
                [ 'jquery', 'baloa-structure-auditor-seo-admin' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );

            wp_enqueue_script(
                'baloa-admin-ai',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-ai.js',
                [ 'jquery', 'baloa-structure-auditor-seo-admin' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );

            wp_enqueue_script(
                'baloa-admin-social',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-social.js',
                [ 'jquery', 'baloa-structure-auditor-seo-admin' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );

            wp_enqueue_script(
                'baloa-admin-entities',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-entities.js',
                [ 'jquery', 'baloa-structure-auditor-seo-admin' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );

            wp_enqueue_script(
                'baloa-admin-competitors',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-competitors.js',
                [ 'jquery', 'baloa-structure-auditor-seo-admin' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );
        }



        if ( $is_settings ) {
            wp_enqueue_script(
                'baloa-admin-settings',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-settings.js',
                [ 'jquery' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );
        }

        if ( $is_glossary ) {
            wp_enqueue_script(
                'baloa-admin-glossary',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-glossary.js',
                [ 'jquery' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );
        }

        if ( $is_reversion ) {
            wp_enqueue_script(
                'baloa-admin-reversion',
                BALOA_STRUCTURE_AUDITOR_SEO_URL . 'assets/admin-reversion.js',
                [ 'jquery' ],
                BALOA_STRUCTURE_AUDITOR_SEO_VERSION,
                true
            );
        }

        $post_id = get_the_ID();
        $reversion_strings = [];

        if ( $is_reversion ) {
            $reversion_strings = [
                'scanning_fixes'        => __( 'Escaneando cambios activos...', 'baloa-structure-auditor-seo' ),
                'structural_active'     => __( 'Activo (Frontend)', 'baloa-structure-auditor-seo' ),
                'structural_inactive'   => __( 'Inactivo', 'baloa-structure-auditor-seo' ),
                'no_fixes'              => __( 'No hay correcciones o anulaciones activas en la base de datos.', 'baloa-structure-auditor-seo' ),
                'url_override'          => __( 'URL Override', 'baloa-structure-auditor-seo' ),
                'post_meta'             => __( 'Post Meta', 'baloa-structure-auditor-seo' ),
                'undo'                  => __( 'Deshacer', 'baloa-structure-auditor-seo' ),
                'communication_error'   => __( 'Error de comunicación con el servidor.', 'baloa-structure-auditor-seo' ),
                'confirm_single_revert' => __( '¿Estás seguro de que deseas eliminar permanentemente todas las mejoras de SEO inyectadas en "', 'baloa-structure-auditor-seo' ),
                'deleting'              => __( 'Eliminando...', 'baloa-structure-auditor-seo' ),
                'connection_error'      => __( 'Error de conexión.', 'baloa-structure-auditor-seo' ),
                'confirm_bulk_purge'    => __( '¡ADVERTENCIA CRÍTICA!\n\nEstás a punto de eliminar de forma masiva y definitiva ABSOLUTAMENTE TODAS las correcciones de SEO aplicadas en este sitio.\n\nEsta acción no se puede deshacer y devolverá la base de datos a su estado original predeterminado. ¿Deseas continuar?', 'baloa-structure-auditor-seo' ),
                'purging_db'            => __( 'Purgando base de datos...', 'baloa-structure-auditor-seo' ),
                'purge_all_btn'         => __( 'Purgar Todas las Correcciones', 'baloa-structure-auditor-seo' )
            ];
        }

        wp_localize_script( 'baloa-structure-auditor-seo-admin', 'BALOA', [
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'baloa_structure_auditor_seo_nonce' ),
            'post_url'   => $post_id ? get_permalink( $post_id ) : '',
            'post_id'    => $post_id ?: 0,
            'home_url'   => untrailingslashit( home_url() ),
            'is_premium' => self::get_instance()->get_license()->is_premium(),
            'reversion'  => $reversion_strings,
        ] );
    }

    /**
     * Load the plugin textdomain.
     * Discouraged since WP 4.6, as WordPress.org loads translations automatically.
     * Retained as a stub for compatibility.
     */
    public function load_textdomain(): void {
        // WordPress.org automatically loads translations for this plugin.
    }

    public static function activate(): void {
        if ( version_compare( PHP_VERSION, BALOA_STRUCTURE_AUDITOR_SEO_MIN_PHP, '<' ) ) {
            deactivate_plugins( plugin_basename( BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'baloa-structure-auditor-seo.php' ) );
            wp_die( esc_html( sprintf( 'Baloa Structure Auditor for SEO requiere PHP %s o superior. Tu versión actual es %s.', BALOA_STRUCTURE_AUDITOR_SEO_MIN_PHP, PHP_VERSION ) ) );
        }

        if ( version_compare( get_bloginfo( 'version' ), BALOA_STRUCTURE_AUDITOR_SEO_MIN_WP, '<' ) ) {
            deactivate_plugins( plugin_basename( BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'baloa-structure-auditor-seo.php' ) );
            wp_die( esc_html( sprintf( 'Baloa Structure Auditor for SEO requiere WordPress %s o superior. Tu versión actual es %s.', BALOA_STRUCTURE_AUDITOR_SEO_MIN_WP, get_bloginfo( 'version' ) ) ) );
        }

        if ( ! wp_next_scheduled( BALOA_STRUCTURE_AUDITOR_SEO_CRON_HOOK ) ) {
            wp_schedule_event( time(), 'hourly', BALOA_STRUCTURE_AUDITOR_SEO_CRON_HOOK );
        }
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook( BALOA_STRUCTURE_AUDITOR_SEO_CRON_HOOK );
    }

    public function get_version(): string {
        return BALOA_STRUCTURE_AUDITOR_SEO_VERSION;
    }
}
