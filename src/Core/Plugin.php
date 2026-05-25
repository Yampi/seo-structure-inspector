<?php
/**
 * SEOSI\Core\Plugin
 * Main bootstrap class for SEO Structure Inspector.
 */

namespace SEOSI\Core;

use SEOSI\Core\Contracts\LicenseProviderInterface;

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
            if ( class_exists( '\\SEOSI\\Infrastructure\\Licensing\\LocalLicenseProvider' ) ) {
                $this->license_provider = new \SEOSI\Infrastructure\Licensing\LocalLicenseProvider();
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

        $is_dashboard = ( 'toplevel_page_seo-structure-inspector' === $hook );
        $is_glossary  = ( 'seo-inspector_page_seosi-glossary' === $hook );
        $is_settings  = ( 'seo-inspector_page_seosi-settings' === $hook );
        $is_reversion = ( 'seo-inspector_page_seosi-reversion' === $hook );
        $is_editor    = in_array( $hook, [ 'post.php', 'post-new.php' ], true );

        if ( ! $is_dashboard && ! $is_editor && ! $is_glossary && ! $is_settings && ! $is_reversion ) {
            return;
        }

        if ( $is_editor ) {
            wp_enqueue_style(
                'seo-si-admin',
                SEOSI_URL . 'assets/admin.css',
                [],
                SEOSI_VERSION
            );
        }

        if ( $is_dashboard || $is_glossary || $is_settings || $is_reversion ) {
            wp_enqueue_style(
                'seo-si-dashboard',
                SEOSI_URL . 'assets/admin-dashboard.css',
                [],
                filemtime( SEOSI_DIR . 'assets/admin-dashboard.css' )
            );
        }

        wp_enqueue_script(
            'seo-si-admin',
            SEOSI_URL . 'assets/admin.js',
            [ 'jquery' ],
            SEOSI_VERSION,
            true
        );

        $post_id = get_the_ID();

        wp_localize_script( 'seo-si-admin', 'SEOSI', [
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'seosi_nonce' ),
            'post_url'   => $post_id ? get_permalink( $post_id ) : '',
            'post_id'    => $post_id ?: 0,
            'home_url'   => untrailingslashit( home_url() ),
            'is_premium' => self::get_instance()->get_license()->is_premium(),
        ] );
    }

    public function load_textdomain(): void {
        $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
        $po     = SEOSI_DIR . 'languages/seo-si-' . $locale . '.po';

        if ( file_exists( $po ) && class_exists( PoMoCompiler::class ) ) {
            try {
                PoMoCompiler::ensure_compiled( 'seo-si', $po, $locale );
            } catch ( \Throwable $e ) {
                error_log( '[SEOSI] ' . $e->getMessage() );
            }
        }

        load_plugin_textdomain( 'seo-si', false, dirname( plugin_basename( dirname( __DIR__ ) ) ) . '/languages' );
    }

    public function get_version(): string {
        return SEOSI_VERSION;
    }
}
