<?php
/**
 * BaloaStructureAuditorSEO\Admin\SettingsPresenter
 * Presenter class handling the HTML rendering for plugin settings fields and panels.
 */

namespace BaloaStructureAuditorSEO\Admin;

use BaloaStructureAuditorSEO\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsPresenter {

	/**
	 * Renders a standard text or password input field.
	 */
	private static function render_text_input( string $key, string $type = 'text', string $placeholder = '', string $desc = '' ): void {
		$options = get_option( Settings::OPTION_NAME, Settings::get_defaults() );
		$val     = $options[ $key ] ?? '';
		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( $val ); ?>"
			class="regular-text"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
		/>
		<?php if ( ! empty( $desc ) ) : ?>
			<p class="description"><?php echo esc_html( $desc ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders a checkbox input field.
	 */
	private static function render_checkbox( string $key, string $label, string $desc = '' ): void {
		$options = get_option( Settings::OPTION_NAME, Settings::get_defaults() );
		$enabled = $options[ $key ] ?? false;
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]"
				value="1"
				<?php checked( $enabled, true ); ?>
			/>
			<?php echo esc_html( $label ); ?>
		</label>
		<?php if ( ! empty( $desc ) ) : ?>
			<p class="description"><?php echo esc_html( $desc ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders a select dropdown input field.
	 */
	private static function render_select( string $key, array $options_list, string $desc = '' ): void {
		$options  = get_option( Settings::OPTION_NAME, Settings::get_defaults() );
		$selected = $options[ $key ] ?? '';
		?>
		<select name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]">
			<?php foreach ( $options_list as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $selected, $val ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( ! empty( $desc ) ) : ?>
			<p class="description"><?php echo wp_kses_post( $desc ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the settings main wrapper dashboard page.
	 */
	public static function render_settings_page(): void {
		if ( ! Capabilities::user_can_manage_settings() ) {
			return;
		}

		$options = get_option( Settings::OPTION_NAME, Settings::get_defaults() );
		$theme   = $options['ui_theme'] ?? 'dark';
		?>
		<div class="seoi-dashboard-root" data-theme="<?php echo esc_attr( $theme ); ?>">
			<!-- SIDEBAR -->
			<aside class="sidebar">
				<div class="logo">
					<div class="logo-icon" aria-hidden="true">
						<svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<!-- Borde Hexagonal Blanco -->
							<path d="M12 2.5L21.5 8V16L12 21.5L2.5 16V8L12 2.5Z" fill="#111422" stroke="#ffffff" stroke-width="1.5" stroke-linejoin="round" />
							
							<!-- Detalle en el vértice inferior del hexágono -->
							<circle cx="12" cy="21.5" r="0.6" fill="#ffffff" />
							
							<!-- Orejas del Búho -->
							<path d="M7.5 8.5L4.5 5L9 6.5" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8" stroke-linejoin="round" />
							<path d="M16.5 8.5L19.5 5L15 6.5" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8" stroke-linejoin="round" />
							
							<!-- Cuerpo/Cabeza Circular del Búho -->
							<circle cx="12" cy="13.5" r="6.2" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8" />
							
							<!-- Ojos Grandes -->
							<circle cx="9.3" cy="12.5" r="2.2" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8" />
							<circle cx="14.7" cy="12.5" r="2.2" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8" />
							
							<!-- Pupilas Celestes Circulares -->
							<circle cx="9.3" cy="12.5" r="1.1" fill="#06b6d4" />
							<circle cx="14.7" cy="12.5" r="1.1" fill="#06b6d4" />
							
							<!-- Pico (Naranja) -->
							<path d="M11.2 13.2H12.8L12 14.6Z" fill="#f59e0b" />
							
							<defs>
								<linearGradient id="baloa-logo-grad" x1="4" y1="2" x2="20" y2="22" gradientUnits="userSpaceOnUse">
									<stop stop-color="#4f8ef7"/>
									<stop offset="1" stop-color="#9b72e8"/>
								</linearGradient>
							</defs>
						</svg>
					</div>
					<div class="logo-text">
						<strong>BALOA</strong>
						<span>Structure Auditor</span>
					</div>
				</div>

				<a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=baloa-structure-auditor-seo' ) ); ?>">
					<span class="nav-left"><span class="nav-icon">📊</span> <?php esc_html_e( 'Panel de Control', 'baloa-structure-auditor-seo' ); ?></span>
				</a>
				<a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=baloa-glossary' ) ); ?>">
					<span class="nav-left"><span class="nav-icon">📖</span> <?php esc_html_e( 'Glosario', 'baloa-structure-auditor-seo' ); ?></span>
				</a>
				
				<div class="nav-divider"></div>

				<a class="nav-item active" href="<?php echo esc_url( admin_url( 'admin.php?page=baloa-settings' ) ); ?>">
					<span class="nav-left"><span class="nav-icon">⚙️</span> <?php esc_html_e( 'Configuración', 'baloa-structure-auditor-seo' ); ?></span>
				</a>
			</aside>

			<!-- MAIN CONTENT -->
			<div class="main">
				<header class="topbar">
					<h2 style="font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; margin: 0; color: var(--text-primary);"><?php esc_html_e( 'Configuración General', 'baloa-structure-auditor-seo' ); ?></h2>
				</header>

				<div class="content" style="padding: 24px; overflow-y: auto; max-height: calc(100vh - 80px);">
					
					<!-- Pestañas de Navegación Modular -->
					<div class="baloa-settings-tabs">
						<button type="button" class="baloa-tab-btn active" data-tab="license"><?php esc_html_e( '🎫 Licencia', 'baloa-structure-auditor-seo' ); ?></button>
						<button type="button" class="baloa-tab-btn" data-tab="api"><?php esc_html_e( '🔑 APIs y Claves', 'baloa-structure-auditor-seo' ); ?></button>
						<button type="button" class="baloa-tab-btn" data-tab="analysis"><?php esc_html_e( '🔧 Opciones de Análisis', 'baloa-structure-auditor-seo' ); ?></button>
						<button type="button" class="baloa-tab-btn" data-tab="ui"><?php esc_html_e( '🎨 Aspecto e Interfaz', 'baloa-structure-auditor-seo' ); ?></button>
					</div>

					<div class="baloa-settings-form-card">
						<form action="options.php" method="post" class="baloa-settings-form">
							<?php
							settings_fields( Settings::OPTION_GROUP );
							do_settings_sections( 'baloa-settings' );
							submit_button(
								__( 'Guardar configuración', 'baloa-structure-auditor-seo' ),
								'btn btn-primary',
								'submit',
								true,
								[
									'style' => 'background: linear-gradient(135deg, var(--accent-purple), var(--accent-purple-bright)); color: #fff; padding: 12px 28px; border: none; font-family: var(--font-main); font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); transition: all 0.2s;',
								]
							);
							?>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function render_section_license( array $args ): void {
		echo '<p>' . esc_html__( 'Administra la clave de activación de tu licencia para desbloquear los módulos avanzados.', 'baloa-structure-auditor-seo' ) . '</p>';
	}

	public static function render_section_api( array $args ): void {
		echo '<p>' . esc_html__( 'Configura las claves de API para servicios externos.', 'baloa-structure-auditor-seo' ) . '</p>';
	}

	public static function render_section_analysis( array $args ): void {
		echo '<p>' . esc_html__( 'Ajusta las opciones de comportamiento del análisis.', 'baloa-structure-auditor-seo' ) . '</p>';
	}

	public static function render_section_ui( array $args ): void {
		echo '<p>' . esc_html__( 'Personaliza la visualización y apariencia de la interfaz de administración del plugin.', 'baloa-structure-auditor-seo' ) . '</p>';
	}

	public static function render_field_license_key( array $args ): void {
		$license_key = get_option( 'baloa_structure_auditor_seo_license_key', '' );
		$provider    = \BaloaStructureAuditorSEO\Core\Plugin::get_instance()->get_license();
		$is_premium  = $provider->is_premium();
		$status      = $provider->get_license_status();
		?>
		<input
			type="text"
			name="baloa_structure_auditor_seo_license_key"
			value="<?php echo esc_attr( $license_key ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Clave de Licencia (ej. SI-PRO-LOCAL-TEST)', 'baloa-structure-auditor-seo' ); ?>"
			style="font-family: monospace; letter-spacing: 0.5px;"
		/>
		<div style="margin-top: 12px; display: flex; align-items: center; gap: 8px;">
			<?php if ( $is_premium ) : ?>
				<span style="background: #e2fcdb; color: #1e5a14; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; border: 1px solid #c2f5b5;">
					✓ <?php
					/* translators: %s: Type of PRO license status. */
					echo esc_html( sprintf( __( 'PRO ACTIVO (%s)', 'baloa-structure-auditor-seo' ), $status['type'] ) );
					?>
				</span>
			<?php else : ?>
				<span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; border: 1px solid #fecaca;">
					✗ <?php esc_html_e( 'LICENCIA FREE / INACTIVA', 'baloa-structure-auditor-seo' ); ?>
				</span>
			<?php endif; ?>
		</div>
		<p class="description">
			<?php esc_html_e( 'Introduce tu clave de licencia para activar características como Auto-Fix, análisis automatizados por CRON, exportación de PDF, diagnóstico AEO/LLMs y batch-analysis. Para desarrollo y pruebas locales, puedes usar la clave SI-PRO-LOCAL-TEST.', 'baloa-structure-auditor-seo' ); ?>
		</p>
		<?php
	}

	public static function render_field_pagespeed_key( array $args ): void {
		self::render_text_input(
			'pagespeed_api_key',
			'text',
			'AIzaSy...',
			__( 'Opcional. Sin clave, PageSpeed API tiene límites de rate. Obtén una clave en Google Cloud Console.', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_openai_key( array $args ): void {
		self::render_text_input(
			'openai_api_key',
			'password',
			'sk-proj-...',
			__( 'Necesario si deseas usar OpenAI GPT-4o para el diagnóstico semántico.', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_gemini_key( array $args ): void {
		self::render_text_input(
			'gemini_api_key',
			'password',
			'AIzaSy...',
			__( 'Necesario si deseas usar Google Gemini 1.5 Pro para el diagnóstico semántico.', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_claude_key( array $args ): void {
		self::render_text_input(
			'claude_api_key',
			'password',
			'sk-ant-...',
			__( 'Necesario si deseas usar Anthropic Claude 3.5 Sonnet para el diagnóstico semántico.', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_ai_provider( array $args ): void {
		self::render_select(
			'ai_provider',
			[
				'default' => __( '🤖 BSA Expert Engine (Local)', 'baloa-structure-auditor-seo' ),
				'openai'  => __( '🔮 OpenAI GPT-4o', 'baloa-structure-auditor-seo' ),
				'gemini'  => __( '♊ Google Gemini 1.5 Pro', 'baloa-structure-auditor-seo' ),
				'claude'  => __( '🦉 Anthropic Claude 3.5 Sonnet', 'baloa-structure-auditor-seo' ),
			],
			__( 'Elige el motor de IA predeterminado para el análisis del grupo de expertos.', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_enable_cwv( array $args ): void {
		self::render_checkbox(
			'enable_cwv',
			__( 'Habilitar análisis de Core Web Vitals', 'baloa-structure-auditor-seo' ),
			__( 'Deshabilita si no quieres usar PageSpeed API (ahorra tiempo de análisis).', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_timeout( array $args ): void {
		$options = get_option( Settings::OPTION_NAME, Settings::get_defaults() );
		$timeout = $options['timeout'] ?? 20;
		?>
		<input
			type="number"
			name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[timeout]"
			value="<?php echo esc_attr( $timeout ); ?>"
			min="5"
			max="60"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Tiempo máximo de espera para solicitudes HTTP (5-60 segundos).', 'baloa-structure-auditor-seo' ); ?>
		</p>
		<?php
	}

	public static function render_field_enable_structural_fixes( array $args ): void {
		self::render_checkbox(
			'enable_structural_fixes',
			__( 'Habilitar auto-corrección estructural dinámica en el frontend.', 'baloa-structure-auditor-seo' ),
			__( 'Si se activa, el plugin reescribirá dinámicamente la salida HTML (Output Buffering) para solucionar problemas de falta de etiquetas semánticas (header, main, footer), múltiples H1 y viewport meta ausente.', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_structural_fixes_strategy( array $args ): void {
		self::render_select(
			'structural_fixes_strategy',
			[
				'wrap'    => __( 'Envolver contenedor (Wrap)', 'baloa-structure-auditor-seo' ),
				'replace' => __( 'Reemplazar etiqueta (Replace)', 'baloa-structure-auditor-seo' ),
			],
			__( '<strong>Envolver:</strong> Envuelve el contenedor encontrado con la etiqueta semántica correspondiente (ej. &lt;header class="baloa-semantic-header"&gt;&lt;div id="masthead"&gt;...&lt;/div&gt;&lt;/header&gt;).<br><strong>Reemplazar:</strong> Reemplaza la etiqueta del contenedor encontrado (ej. convierte &lt;div id="masthead"&gt; en &lt;header id="masthead" class="baloa-semantic-header"&gt;).', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_structural_header_selectors( array $args ): void {
		self::render_text_input(
			'structural_header_selectors',
			'text',
			'masthead, site-header, header-wrap',
			__( 'Lista de selectores (IDs o clases de CSS sin prefijo # o .) separados por comas que identifican el encabezado de la página. Ej. masthead, site-header, header-wrap.', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_structural_main_selectors( array $args ): void {
		self::render_text_input(
			'structural_main_selectors',
			'text',
			'content, primary, content-area',
			__( 'Lista de selectores (IDs o clases) separados por comas que identifican el contenido principal. Ej. content, primary, content-area.', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_structural_footer_selectors( array $args ): void {
		self::render_text_input(
			'structural_footer_selectors',
			'text',
			'colophon, site-footer, footer-wrap',
			__( 'Lista de selectores (IDs o clases) separados por comas que identifican el pie de página. Ej. colophon, site-footer, footer-wrap.', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_faq_page( array $args ): void {
		$options     = get_option( Settings::OPTION_NAME, Settings::get_defaults() );
		$faq_page_id = absint( $options['faq_page_id'] ?? 0 );

		$dropdown = wp_dropdown_pages( [
			'name'              => esc_attr( Settings::OPTION_NAME ) . '[faq_page_id]',
			'selected'          => esc_attr( $faq_page_id ),
			'show_option_none'  => esc_html__( '— Ninguna (deshabilitado) —', 'baloa-structure-auditor-seo' ),
			'option_none_value' => 0,
			'echo'              => 0,
		] );
		echo wp_kses_post( $dropdown );
		?>
		<p class="description">
			<?php esc_html_e( 'Selecciona la página de Preguntas Frecuentes. El plugin extraerá las preguntas y respuestas del contenido y generará automáticamente un esquema JSON-LD FAQPage.', 'baloa-structure-auditor-seo' ); ?>
		</p>
		<?php
	}

	public static function render_field_ui_theme( array $args ): void {
		self::render_select(
			'ui_theme',
			[
				'dark'  => __( 'Modo Noche (Oscuro)', 'baloa-structure-auditor-seo' ),
				'light' => __( 'Modo Día (Claro)', 'baloa-structure-auditor-seo' ),
				'auto'  => __( 'Automático (Preferencia del Sistema)', 'baloa-structure-auditor-seo' ),
			],
			__( 'Define la apariencia visual de la interfaz de administración del plugin (Dashboard, Glosario y Ajustes).', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_enable_telemetry( array $args ): void {
		self::render_checkbox(
			'enable_telemetry',
			__( 'Habilitar telemetría RUM en el frontend para Core Web Vitals', 'baloa-structure-auditor-seo' ),
			__( 'Recolecta métricas de carga y estabilidad reales (LCP, FID, CLS, INP) de los usuarios que navegan por tu sitio para agregarlas en el panel de control.', 'baloa-structure-auditor-seo' )
		);
	}

	public static function render_field_ai_crawler_control( array $args ): void {
		$agents = \BaloaStructureAuditorSEO\Pro\Services\AIControlService::get_configured_agents();
		?>
		<table class="wp-list-table widefat fixed striped" style="max-width: 600px; border-radius: 6px; border: 1px solid var(--border-color); overflow: hidden; margin-top: 8px;">
			<thead>
				<tr>
					<th style="padding: 10px; font-weight: 600;"><?php esc_html_e( 'Agente de IA', 'baloa-structure-auditor-seo' ); ?></th>
					<th style="padding: 10px; font-weight: 600;"><?php esc_html_e( 'Directiva (Acceso)', 'baloa-structure-auditor-seo' ); ?></th>
					<th style="padding: 10px; font-weight: 600;"><?php esc_html_e( 'Descripción', 'baloa-structure-auditor-seo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $agents as $agent ) :
					$name = $agent->get_name();
					$dir  = $agent->get_directive();
					?>
					<tr>
						<td style="padding: 10px; font-family: monospace; font-weight: bold;"><?php echo esc_html( $name ); ?></td>
						<td style="padding: 10px;">
							<select name="baloa_structure_auditor_seo_ai_crawler_options[<?php echo esc_attr( $name ); ?>][directive]">
								<option value="Disallow" <?php selected( $dir, 'Disallow' ); ?>><?php esc_html_e( 'Bloquear (Disallow)', 'baloa-structure-auditor-seo' ); ?></option>
								<option value="Allow" <?php selected( $dir, 'Allow' ); ?>><?php esc_html_e( 'Permitir (Allow)', 'baloa-structure-auditor-seo' ); ?></option>
							</select>
						</td>
						<td style="padding: 10px; font-size: 12px; color: var(--text-muted);"><?php echo esc_html( $agent->get_description() ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description" style="margin-top: 8px;">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: 1: llms.txt link, 2: llms-full.txt link */
					__( 'Ajusta las políticas para cada bot de inteligencia artificial en tu archivo <code>robots.txt</code>. También puedes ver los archivos dinámicos generados: %1$s y %2$s.', 'baloa-structure-auditor-seo' ),
					'<a href="' . esc_url( home_url( '/llms.txt' ) ) . '" target="_blank">/llms.txt</a>',
					'<a href="' . esc_url( home_url( '/llms-full.txt' ) ) . '" target="_blank">/llms-full.txt</a>'
				)
			);
			?>
		</p>
		<?php
	}
}
