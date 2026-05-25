<?php
/**
 * SEOSI\Admin\Settings
 * Plugin settings management using WordPress Settings API.
 */

namespace SEOSI\Admin;

use SEOSI\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {

    const OPTION_GROUP = 'seosi_settings';
    const OPTION_NAME  = 'seosi_options';

    public static function register_hooks(): void {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
    }

    public static function register_settings(): void {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [
                'sanitize_callback' => [ __CLASS__, 'sanitize_options' ],
                'default'           => self::get_defaults(),
            ]
        );

        register_setting(
            self::OPTION_GROUP,
            'seosi_license_key',
            [
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ]
        );

        // License Key
        add_settings_section(
            'seosi_section_license',
            'Licencia de Producto',
            [ __CLASS__, 'render_section_license' ],
            'seosi-settings'
        );

        add_settings_field(
            'seosi_license_key_field',
            'Clave de Licencia PRO',
            [ __CLASS__, 'render_field_license_key' ],
            'seosi-settings',
            'seosi_section_license'
        );

        // PageSpeed API Key
        add_settings_section(
            'seosi_section_api',
            'Configuración de API',
            [ __CLASS__, 'render_section_api' ],
            'seosi-settings'
        );

        add_settings_field(
            'seosi_pagespeed_api_key',
            'Google PageSpeed API Key',
            [ __CLASS__, 'render_field_pagespeed_key' ],
            'seosi-settings',
            'seosi_section_api'
        );

        // Analysis Options
        add_settings_section(
            'seosi_section_analysis',
            'Opciones de Análisis',
            [ __CLASS__, 'render_section_analysis' ],
            'seosi-settings'
        );

        add_settings_field(
            'seosi_enable_cwv',
            'Habilitar Core Web Vitals',
            [ __CLASS__, 'render_field_enable_cwv' ],
            'seosi-settings',
            'seosi_section_analysis'
        );

        add_settings_field(
            'seosi_timeout',
            'Timeout de solicitud (segundos)',
            [ __CLASS__, 'render_field_timeout' ],
            'seosi-settings',
            'seosi_section_analysis'
        );

        add_settings_field(
            'seosi_enable_structural_fixes',
            'Habilitar Correcciones Estructurales',
            [ __CLASS__, 'render_field_enable_structural_fixes' ],
            'seosi-settings',
            'seosi_section_analysis'
        );

        add_settings_field(
            'seosi_structural_fixes_strategy',
            'Estrategia de Corrección',
            [ __CLASS__, 'render_field_structural_fixes_strategy' ],
            'seosi-settings',
            'seosi_section_analysis'
        );

        add_settings_field(
            'seosi_structural_header_selectors',
            'Selectores de Cabecera (Header)',
            [ __CLASS__, 'render_field_structural_header_selectors' ],
            'seosi-settings',
            'seosi_section_analysis'
        );

        add_settings_field(
            'seosi_structural_main_selectors',
            'Selectores de Contenido Principal (Main)',
            [ __CLASS__, 'render_field_structural_main_selectors' ],
            'seosi-settings',
            'seosi_section_analysis'
        );

        add_settings_field(
            'seosi_structural_footer_selectors',
            'Selectores de Pie de Página (Footer)',
            [ __CLASS__, 'render_field_structural_footer_selectors' ],
            'seosi-settings',
            'seosi_section_analysis'
        );

        add_settings_field(
            'seosi_faq_page',
            'Página de FAQ (FAQPage Schema)',
            [ __CLASS__, 'render_field_faq_page' ],
            'seosi-settings',
            'seosi_section_analysis'
        );

        // UI & Theme Options
        add_settings_section(
            'seosi_section_ui',
            'Aspecto e Interfaz',
            [ __CLASS__, 'render_section_ui' ],
            'seosi-settings'
        );

        add_settings_field(
            'seosi_ui_theme',
            'Tema de la Interfaz',
            [ __CLASS__, 'render_field_ui_theme' ],
            'seosi-settings',
            'seosi_section_ui'
        );
    }

    public static function add_settings_page(): void {
        add_submenu_page(
            'seo-structure-inspector',
            'Configuración',
            'Configuración',
            Capabilities::manage_settings(),
            'seosi-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function render_settings_page(): void {
        if ( ! Capabilities::user_can_manage_settings() ) {
            return;
        }

        $options = get_option( self::OPTION_NAME, self::get_defaults() );
        $theme   = $options['ui_theme'] ?? 'dark';
        ?>
        <div class="seoi-dashboard-root" data-theme="<?php echo esc_attr( $theme ); ?>">
            <!-- SIDEBAR -->
            <aside class="sidebar">
                <div class="logo">
                    <div class="logo-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L20 7V17L12 22L4 17V7L12 2Z" stroke="white" stroke-width="1.5" fill="url(#seosi-logo-grad)"/>
                            <defs>
                                <linearGradient id="seosi-logo-grad" x1="4" y1="2" x2="20" y2="22" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#4f8ef7"/>
                                    <stop offset="1" stop-color="#9b72e8"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <strong>SEO</strong>
                        <span>Structure Inspector</span>
                    </div>
                </div>

                <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-structure-inspector' ) ); ?>">
                    <span class="nav-left"><span class="nav-icon">📊</span> Panel de Control</span>
                </a>
                <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=seosi-glossary' ) ); ?>">
                    <span class="nav-left"><span class="nav-icon">📖</span> Glosario</span>
                </a>
                
                <div class="nav-divider"></div>

                <a class="nav-item active" href="<?php echo esc_url( admin_url( 'admin.php?page=seosi-settings' ) ); ?>">
                    <span class="nav-left"><span class="nav-icon">⚙️</span> Configuración</span>
                </a>
            </aside>

            <!-- MAIN CONTENT -->
            <div class="main">
                <header class="topbar">
                    <h2 style="font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; margin: 0; color: var(--text-primary);">Configuración General</h2>
                </header>

                <div class="content" style="padding: 24px; overflow-y: auto; max-height: calc(100vh - 80px);">
                    
                    <!-- Pestañas de Navegación Modular (Chunking - Directrices UX) -->
                    <div class="seosi-settings-tabs">
                        <button type="button" class="seosi-tab-btn active" data-tab="license">🎫 Licencia</button>
                        <button type="button" class="seosi-tab-btn" data-tab="api">🔑 APIs y Claves</button>
                        <button type="button" class="seosi-tab-btn" data-tab="analysis">🔧 Opciones de Análisis</button>
                        <button type="button" class="seosi-tab-btn" data-tab="ui">🎨 Aspecto e Interfaz</button>
                    </div>

                    <div class="seosi-settings-form-card">
                        <form action="options.php" method="post" class="seosi-settings-form">
                            <?php
                            settings_fields( self::OPTION_GROUP );
                            do_settings_sections( 'seosi-settings' );
                            submit_button( 'Guardar configuración', 'btn btn-primary', 'submit', true, [
                                'style' => 'background: linear-gradient(135deg, var(--accent-purple), var(--accent-purple-bright)); color: #fff; padding: 12px 28px; border: none; font-family: var(--font-main); font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); transition: all 0.2s;'
                            ] );
                            ?>
                        </form>
                    </div>
                </div>

                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    var sections = ['license', 'api', 'analysis', 'ui'];
                    var form = $('.seosi-settings-form');
                    
                    // Obtener los elementos hijos del formulario (excepto inputs ocultos de settings_fields y el submit)
                    var children = form.children().not('input[type="hidden"], p.submit');
                    
                    // Agrupar dinámicamente los H2 y sus tablas/párrafos asociados en divs de contenido de pestaña
                    var currentGroup = null;
                    var groupIndex = 0;
                    
                    children.each(function() {
                        var el = $(this);
                        if (el.is('h2')) {
                            var sectionKey = sections[groupIndex] || 'section-' + groupIndex;
                            currentGroup = $('<div class="seosi-tab-content" id="tab-seosi-settings-' + sectionKey + '"></div>');
                            // Insertar el contenedor antes del H2 actual
                            el.before(currentGroup);
                            groupIndex++;
                        }
                        if (currentGroup) {
                            currentGroup.append(el);
                        }
                    });
                    
                    // Inicializar el estado de visibilidad
                    $('.seosi-tab-content').hide();
                    $('#tab-seosi-settings-license').show();
                    
                    // Manejador del click de pestañas
                    $('.seosi-tab-btn').on('click', function(e) {
                        e.preventDefault();
                        var tabKey = $(this).data('tab');
                        
                        // Alternar clases activas
                        $('.seosi-tab-btn').removeClass('active');
                        $(this).addClass('active');
                        
                        // Animación y visualización del bloque correspondiente
                        $('.seosi-tab-content').hide();
                        $('#tab-seosi-settings-' + tabKey).fadeIn(150);
                    });
                });
                </script>
            </div>
        </div>
        <?php
    }

    public static function render_section_api( array $args ): void {
        echo '<p>Configura las claves de API para servicios externos.</p>';
    }

    public static function render_section_analysis( array $args ): void {
        echo '<p>Ajusta las opciones de comportamiento del análisis.</p>';
    }

    public static function render_field_pagespeed_key( array $args ): void {
        $options  = get_option( self::OPTION_NAME, self::get_defaults() );
        $api_key  = $options['pagespeed_api_key'] ?? '';
        ?>
        <input
            type="text"
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>[pagespeed_api_key]"
            value="<?php echo esc_attr( $api_key ); ?>"
            class="regular-text"
            placeholder="AIzaSy..."
        />
        <p class="description">
            Opcional. Sin clave, PageSpeed API tiene límites de rate. 
            Obtén una clave en <a href="https://developers.google.com/speed/docs/insights/v5/get-started" target="_blank">Google Cloud Console</a>.
        </p>
        <?php
    }

    public static function render_field_enable_cwv( array $args ): void {
        $options = get_option( self::OPTION_NAME, self::get_defaults() );
        $enabled = $options['enable_cwv'] ?? true;
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enable_cwv]"
                value="1"
                <?php checked( $enabled, true ); ?>
            />
            Habilitar análisis de Core Web Vitals
        </label>
        <p class="description">
            Deshabilita si no quieres usar PageSpeed API (ahorra tiempo de análisis).
        </p>
        <?php
    }

    public static function render_field_timeout( array $args ): void {
        $options = get_option( self::OPTION_NAME, self::get_defaults() );
        $timeout = $options['timeout'] ?? 20;
        ?>
        <input
            type="number"
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>[timeout]"
            value="<?php echo esc_attr( $timeout ); ?>"
            min="5"
            max="60"
            class="small-text"
        />
        <p class="description">
            Tiempo máximo de espera para solicitudes HTTP (5-60 segundos).
        </p>
        <?php
    }

    public static function render_field_enable_structural_fixes( array $args ): void {
        $options = get_option( self::OPTION_NAME, self::get_defaults() );
        $enabled = $options['enable_structural_fixes'] ?? false;
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enable_structural_fixes]"
                value="1"
                <?php checked( $enabled, true ); ?>
            />
            Habilitar auto-corrección estructural dinámica en el frontend.
        </label>
        <p class="description">
            Si se activa, el plugin reescribirá dinámicamente la salida HTML (Output Buffering) para solucionar problemas de falta de etiquetas semánticas (header, main, footer), múltiples H1 y viewport meta ausente.
        </p>
        <?php
    }

    public static function render_field_structural_fixes_strategy( array $args ): void {
        $options  = get_option( self::OPTION_NAME, self::get_defaults() );
        $strategy = $options['structural_fixes_strategy'] ?? 'wrap';
        ?>
        <select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[structural_fixes_strategy]">
            <option value="wrap" <?php selected( $strategy, 'wrap' ); ?>>Envolver contenedor (Wrap)</option>
            <option value="replace" <?php selected( $strategy, 'replace' ); ?>>Reemplazar etiqueta (Replace)</option>
        </select>
        <p class="description">
            <strong>Envolver:</strong> Envuelve el contenedor encontrado con la etiqueta semántica correspondiente (ej. &lt;header class="seosi-semantic-header"&gt;&lt;div id="masthead"&gt;...&lt;/div&gt;&lt;/header&gt;).<br>
            <strong>Reemplazar:</strong> Reemplaza la etiqueta del contenedor encontrado (ej. convierte &lt;div id="masthead"&gt; en &lt;header id="masthead" class="seosi-semantic-header"&gt;).
        </p>
        <?php
    }

    public static function render_field_structural_header_selectors( array $args ): void {
        $options   = get_option( self::OPTION_NAME, self::get_defaults() );
        $selectors = $options['structural_header_selectors'] ?? 'masthead, site-header, header-wrap';
        ?>
        <input
            type="text"
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>[structural_header_selectors]"
            value="<?php echo esc_attr( $selectors ); ?>"
            class="regular-text"
        />
        <p class="description">
            Lista de selectores (IDs o clases de CSS sin prefijo # o .) separados por comas que identifican el encabezado de la página. Ej. <code>masthead, site-header, header-wrap</code>.
        </p>
        <?php
    }

    public static function render_field_structural_main_selectors( array $args ): void {
        $options   = get_option( self::OPTION_NAME, self::get_defaults() );
        $selectors = $options['structural_main_selectors'] ?? 'content, primary, content-area';
        ?>
        <input
            type="text"
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>[structural_main_selectors]"
            value="<?php echo esc_attr( $selectors ); ?>"
            class="regular-text"
        />
        <p class="description">
            Lista de selectores (IDs o clases) separados por comas que identifican el contenido principal. Ej. <code>content, primary, content-area</code>.
        </p>
        <?php
    }

    public static function render_field_structural_footer_selectors( array $args ): void {
        $options   = get_option( self::OPTION_NAME, self::get_defaults() );
        $selectors = $options['structural_footer_selectors'] ?? 'colophon, site-footer, footer-wrap';
        ?>
        <input
            type="text"
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>[structural_footer_selectors]"
            value="<?php echo esc_attr( $selectors ); ?>"
            class="regular-text"
        />
        <p class="description">
            Lista de selectores (IDs o clases) separados por comas que identifican el pie de página. Ej. <code>colophon, site-footer, footer-wrap</code>.
        </p>
        <?php
    }

    public static function render_field_faq_page( array $args ): void {
        $options     = get_option( self::OPTION_NAME, self::get_defaults() );
        $faq_page_id = absint( $options['faq_page_id'] ?? 0 );
        ?>
        <?php
        wp_dropdown_pages( [
            'name'              => esc_attr( self::OPTION_NAME ) . '[faq_page_id]',
            'selected'          => $faq_page_id,
            'show_option_none'  => '— Ninguna (deshabilitado) —',
            'option_none_value' => 0,
        ] );
        ?>
        <p class="description">
            Selecciona la página de Preguntas Frecuentes. El plugin extraerá las preguntas y respuestas del contenido y generará automáticamente un esquema JSON-LD FAQPage.
        </p>
        <?php
    }

    public static function sanitize_options( array $input ): array {
        $sanitized = [];
        $defaults  = self::get_defaults();

        $sanitized['pagespeed_api_key']       = sanitize_text_field( $input['pagespeed_api_key'] ?? '' );
        $sanitized['enable_cwv']              = isset( $input['enable_cwv'] ) ? (bool) $input['enable_cwv'] : $defaults['enable_cwv'];
        $sanitized['enable_broken_links_check'] = isset( $input['enable_broken_links_check'] ) ? (bool) $input['enable_broken_links_check'] : $defaults['enable_broken_links_check'];
        $sanitized['enable_scheduled_analysis'] = isset( $input['enable_scheduled_analysis'] ) ? (bool) $input['enable_scheduled_analysis'] : $defaults['enable_scheduled_analysis'];
        $sanitized['schedule_alert_email']    = sanitize_email( $input['schedule_alert_email'] ?? $defaults['schedule_alert_email'] );
        $sanitized['schedule_alert_threshold'] = absint( $input['schedule_alert_threshold'] ?? $defaults['schedule_alert_threshold'] );
        $sanitized['schedule_alert_threshold'] = max( 1, min( 50, $sanitized['schedule_alert_threshold'] ) );
        $sanitized['timeout']                 = absint( $input['timeout'] ?? $defaults['timeout'] );
        $sanitized['timeout']                 = max( 5, min( 60, $sanitized['timeout'] ) );

        $sanitized['enable_structural_fixes'] = isset( $input['enable_structural_fixes'] ) ? (bool) $input['enable_structural_fixes'] : false;
        $sanitized['structural_fixes_strategy'] = sanitize_text_field( $input['structural_fixes_strategy'] ?? $defaults['structural_fixes_strategy'] );
        if ( ! in_array( $sanitized['structural_fixes_strategy'], [ 'wrap', 'replace' ], true ) ) {
            $sanitized['structural_fixes_strategy'] = 'wrap';
        }
        $sanitized['structural_header_selectors'] = sanitize_text_field( $input['structural_header_selectors'] ?? $defaults['structural_header_selectors'] );
        $sanitized['structural_main_selectors']   = sanitize_text_field( $input['structural_main_selectors'] ?? $defaults['structural_main_selectors'] );
        $sanitized['structural_footer_selectors'] = sanitize_text_field( $input['structural_footer_selectors'] ?? $defaults['structural_footer_selectors'] );
        $sanitized['faq_page_id'] = absint( $input['faq_page_id'] ?? 0 );

        $sanitized['ui_theme'] = sanitize_text_field( $input['ui_theme'] ?? $defaults['ui_theme'] );
        if ( ! in_array( $sanitized['ui_theme'], [ 'dark', 'light', 'auto' ], true ) ) {
            $sanitized['ui_theme'] = 'dark';
        }

        return $sanitized;
    }

    public static function get_defaults(): array {
        return [
            'pagespeed_api_key'       => '',
            'enable_cwv'              => true,
            'enable_broken_links_check' => true,
            'enable_scheduled_analysis' => false,
            'schedule_alert_email'    => get_option( 'admin_email' ),
            'schedule_alert_threshold' => 10,
            'timeout'                 => 20,
            'enable_structural_fixes' => false,
            'structural_fixes_strategy' => 'wrap',
            'structural_header_selectors' => 'masthead, site-header, header-wrap',
            'structural_main_selectors' => 'content, primary, content-area',
            'structural_footer_selectors' => 'colophon, site-footer, footer-wrap',
            'faq_page_id'             => 0,
            'ui_theme'                => 'dark',
            'module_weights'          => [
                'html'        => 1.0,
                'keyword'     => 1.5,
                'schema'      => 1.2,
                'readability' => 0.8,
                'metatags'    => 1.2,
                'llms'        => 1.0,
                'aeo'         => 1.0,
                'cwv'         => 1.5,
                'links'       => 1.0,
            ],
        ];
    }

    public static function render_section_ui( array $args ): void {
        echo '<p>Personaliza la visualización y apariencia de la interfaz de administración del plugin.</p>';
    }

    public static function render_field_ui_theme( array $args ): void {
        $options = get_option( self::OPTION_NAME, self::get_defaults() );
        $theme   = $options['ui_theme'] ?? 'dark';
        ?>
        <select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ui_theme]">
            <option value="dark" <?php selected( $theme, 'dark' ); ?>>Modo Noche (Oscuro)</option>
            <option value="light" <?php selected( $theme, 'light' ); ?>>Modo Día (Claro)</option>
            <option value="auto" <?php selected( $theme, 'auto' ); ?>>Automático (Preferencia del Sistema)</option>
        </select>
        <p class="description">
            Define la apariencia visual de la interfaz de administración del plugin (Dashboard, Glosario y Ajustes).
        </p>
        <?php
    }

    public static function render_section_license( array $args ): void {
        echo '<p>Administra la clave de activación de tu licencia para desbloquear los módulos avanzados.</p>';
    }

    public static function render_field_license_key( array $args ): void {
        $license_key = get_option( 'seosi_license_key', '' );
        $provider = \SEOSI\Core\Plugin::get_instance()->get_license();
        $is_premium = $provider->is_premium();
        $status = $provider->get_license_status();
        ?>
        <input
            type="text"
            name="seosi_license_key"
            value="<?php echo esc_attr( $license_key ); ?>"
            class="regular-text"
            placeholder="Clave de Licencia (ej. SI-PRO-LOCAL-TEST)"
            style="font-family: monospace; letter-spacing: 0.5px;"
        />
        <div style="margin-top: 12px; display: flex; align-items: center; gap: 8px;">
            <?php if ( $is_premium ) : ?>
                <span style="background: #e2fcdb; color: #1e5a14; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; border: 1px solid #c2f5b5;">
                    ✓ PRO ACTIVO (<?php echo esc_html( $status['type'] ); ?>)
                </span>
            <?php else : ?>
                <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; border: 1px solid #fecaca;">
                    ✗ LICENCIA FREE / INACTIVA
                </span>
            <?php endif; ?>
        </div>
        <p class="description">
            Introduce tu clave de licencia para activar características como Auto-Fix, análisis automatizados por CRON, exportación de PDF, diagnóstico AEO/LLMs y batch-analysis. Para desarrollo y pruebas locales, puedes usar la clave <code>SI-PRO-LOCAL-TEST</code>.
        </p>
        <?php
    }

    public static function get_option( string $key, $default = null ) {
        $options = get_option( self::OPTION_NAME, self::get_defaults() );
        if ( $default === null ) {
            $default = self::get_defaults()[ $key ] ?? null;
        }
        return $options[ $key ] ?? $default;
    }
}
