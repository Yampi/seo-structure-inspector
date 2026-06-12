<?php
/**
 * BaloaStructureAuditorSEO\Admin\AdminPage
 * Admin page — analyze any URL or discover URLs from sitemap.
 */

namespace BaloaStructureAuditorSEO\Admin;

use BaloaStructureAuditorSEO\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminPage {

    public static function register_hooks(): void {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_menu', [ __CLASS__, 'add_glossary_page' ] );
        add_action( 'admin_menu', [ __CLASS__, 'add_reversion_page' ] );
        add_action( 'admin_menu', [ __CLASS__, 'add_readiness_page' ], 999 ); // Add hidden page
        add_action( 'after_plugin_row_baloa-structure-auditor-seo/baloa-structure-auditor-seo.php', [ __CLASS__, 'render_uninstall_warning' ], 10, 3 );
    }

    public static function add_menu(): void {
        add_menu_page(
            'Baloa Structure Auditor for SEO',
            'SEO Auditor',
            Capabilities::analyze(),
            'baloa-structure-auditor-seo',
            [ __CLASS__, 'render' ],
            'dashicons-search',
            30
        );
    }

    public static function add_glossary_page(): void {
        add_submenu_page(
            'baloa-structure-auditor-seo',
            'Glosario SEO',
            'Glosario',
            Capabilities::analyze(),
            'baloa-glossary',
            [ __CLASS__, 'render_glossary' ]
        );
    }

    public static function render_glossary(): void {
        if ( ! Capabilities::user_can_analyze() ) {
            wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'baloa-structure-auditor-seo' ) );
        }

        \BaloaStructureAuditorSEO\Core\ViewRenderer::render_echo( 'glossary-page' );
    }

    /**
     * Add hidden readiness checker page
     */
    public static function add_readiness_page(): void {
        add_submenu_page(
            null, // Hidden from menu
            'Baloa Structure Auditor for SEO Readiness',
            'Readiness',
            Capabilities::manage_settings(),
            'baloa-readiness',
            [ __CLASS__, 'render_readiness' ]
        );
    }

    public static function render(): void {
        if ( ! Capabilities::user_can_analyze() ) {
            wp_die(
                esc_html__( 'No tienes permisos para usar Baloa Structure Auditor for SEO. Se requiere poder editar entradas o administrar el sitio.', 'baloa-structure-auditor-seo' ),
                esc_html__( 'Permisos insuficientes', 'baloa-structure-auditor-seo' ),
                [ 'response' => 403 ]
            );
        }

        \BaloaStructureAuditorSEO\Core\ViewRenderer::render_echo( 'admin-page' );
    }

    /**
     * Render readiness checker page
     */
    public static function render_readiness(): void {
        if ( ! Capabilities::user_can_manage_settings() ) {
            wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'baloa-structure-auditor-seo' ) );
        }

        $results = \BaloaStructureAuditorSEO\Core\ReadinessChecker::run();
        ?>
        <div class="wrap">
            <h1>Baloa Structure Auditor for SEO - Readiness Checker</h1>
            <p>System readiness check for plugin deployment.</p>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Check Results</h2>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Check</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $results as $check => $result ) : ?>
                        <tr>
                            <td><?php echo esc_html( ucwords( str_replace( '_', ' ', $check ) ) ); ?></td>
                            <td>
                                <?php
                                $icon = 'pass' === $result['status'] ? '✅' : ( 'warn' === $result['status'] ? '⚠️' : '❌' );
                                echo esc_html( $icon . ' ' . strtoupper( $result['status'] ) );
                                ?>
                            </td>
                            <td><?php echo esc_html( $result['message'] ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p style="margin-top: 20px;">
                <em>Legend: ✅ Pass | ⚠️ Warning | ❌ Fail</em>
            </p>
        </div>
        <?php
    }

    public static function add_reversion_page(): void {
        add_submenu_page(
            'baloa-structure-auditor-seo',
            __( 'Control de Cambios', 'baloa-structure-auditor-seo' ),
            __( 'Control de Cambios', 'baloa-structure-auditor-seo' ),
            Capabilities::manage_settings(),
            'baloa-reversion',
            [ __CLASS__, 'render_reversion_page' ]
        );
    }

    public static function render_reversion_page(): void {
        if ( ! Capabilities::user_can_manage_settings() ) {
            wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'baloa-structure-auditor-seo' ) );
        }

        \BaloaStructureAuditorSEO\Core\ViewRenderer::render_echo( 'reversion-page' );
    }

    /**
     * Renders a warning directly in the Plugins page list warning about data deletion upon uninstallation.
     */
    public static function render_uninstall_warning( string $plugin_file, array $plugin_data, string $status ): void {
        if ( ! Capabilities::user_can_manage_settings() ) {
            return;
        }
        ?>
        <tr class="active plugin-update-tr baloa-plugin-warning-tr" id="baloa-warning-row" data-slug="baloa-structure-auditor-seo">
            <td colspan="4" class="plugin-update colspanchange">
                <div class="notice inline notice-warning notice-alt" style="margin: 5px 20px 15px 20px; display: block; border-left-color: #f59e0b;">
                    <p style="font-size: 13px; font-weight: 500; margin: 0.5em 0;">
                        <span class="dashicons dashicons-warning" style="color: #f59e0b; vertical-align: text-bottom; margin-right: 5px; font-size: 18px; width: 18px; height: 18px;"></span>
                        <strong><?php esc_html_e( 'Atención:', 'baloa-structure-auditor-seo' ); ?></strong> <?php esc_html_e( 'Si desinstalas y eliminas este plugin, todas las optimizaciones aplicadas (como meta descripciones, títulos alternativos, marcado JSON-LD y archivos llms.txt) se purgarán de forma permanente e irreversible de la base de datos.', 'baloa-structure-auditor-seo' ); ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=baloa-reversion' ) ); ?>"><?php esc_html_e( 'Gestionar correcciones y copias de seguridad', 'baloa-structure-auditor-seo' ); ?></a>
                    </p>
                </div>
            </td>
        </tr>
        <?php
    }
}
