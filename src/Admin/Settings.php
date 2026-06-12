<?php
/**
 * BaloaStructureAuditorSEO\Admin\Settings
 * Plugin settings management using WordPress Settings API.
 */

namespace BaloaStructureAuditorSEO\Admin;

use BaloaStructureAuditorSEO\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	const OPTION_GROUP = 'baloa_structure_auditor_seo_settings';
	const OPTION_NAME  = 'baloa_structure_auditor_seo_options';

	/**
	 * Registers actions and filters for settings management.
	 */
	public static function register_hooks(): void {
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
	}

	/**
	 * Registers the options, sections, and fields for the plugin settings.
	 */
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
			'baloa_structure_auditor_seo_license_key',
			[
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			]
		);

		register_setting(
			self::OPTION_GROUP,
			'baloa_structure_auditor_seo_ai_crawler_options',
			[
				'sanitize_callback' => [ __CLASS__, 'sanitize_ai_crawler_options' ],
				'default'           => [],
			]
		);

		// Sections
		add_settings_section(
			'baloa_structure_auditor_seo_section_license',
			__( 'Licencia de Producto', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_section_license' ],
			'baloa-settings'
		);

		add_settings_section(
			'baloa_structure_auditor_seo_section_api',
			__( 'Configuración de API', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_section_api' ],
			'baloa-settings'
		);

		add_settings_section(
			'baloa_structure_auditor_seo_section_analysis',
			__( 'Opciones de Análisis', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_section_analysis' ],
			'baloa-settings'
		);

		add_settings_section(
			'baloa_structure_auditor_seo_section_ui',
			__( 'Aspecto e Interfaz', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_section_ui' ],
			'baloa-settings'
		);

		// Fields
		add_settings_field(
			'baloa_structure_auditor_seo_license_key_field',
			__( 'Clave de Licencia PRO', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_license_key' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_license'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_pagespeed_api_key',
			__( 'Google PageSpeed API Key', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_pagespeed_key' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_api'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_openai_api_key',
			__( 'OpenAI API Key (GPT-4o)', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_openai_key' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_api'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_gemini_api_key',
			__( 'Google Gemini API Key (1.5 Pro)', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_gemini_key' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_api'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_claude_api_key',
			__( 'Anthropic Claude API Key (3.5 Sonnet)', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_claude_key' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_api'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_ai_provider',
			__( 'Motor de IA Predeterminado', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_ai_provider' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_api'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_enable_cwv',
			__( 'Habilitar Core Web Vitals', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_enable_cwv' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_analysis'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_timeout',
			__( 'Timeout de solicitud (segundos)', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_timeout' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_analysis'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_enable_structural_fixes',
			__( 'Habilitar Correcciones Estructurales', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_enable_structural_fixes' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_analysis'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_structural_fixes_strategy',
			__( 'Estrategia de Corrección', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_structural_fixes_strategy' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_analysis'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_structural_header_selectors',
			__( 'Selectores de Cabecera (Header)', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_structural_header_selectors' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_analysis'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_structural_main_selectors',
			__( 'Selectores de Contenido Principal (Main)', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_structural_main_selectors' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_analysis'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_structural_footer_selectors',
			__( 'Selectores de Pie de Página (Footer)', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_structural_footer_selectors' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_analysis'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_faq_page',
			__( 'Página de FAQ (FAQPage Schema)', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_faq_page' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_analysis'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_enable_telemetry',
			__( 'Habilitar Telemetría RUM', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_enable_telemetry' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_analysis'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_ai_crawler_control',
			__( 'Control de Rastreadores de IA', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_ai_crawler_control' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_analysis'
		);

		add_settings_field(
			'baloa_structure_auditor_seo_ui_theme',
			__( 'Tema de la Interfaz', 'baloa-structure-auditor-seo' ),
			[ SettingsPresenter::class, 'render_field_ui_theme' ],
			'baloa-settings',
			'baloa_structure_auditor_seo_section_ui'
		);
	}

	/**
	 * Registers the options page.
	 */
	public static function add_settings_page(): void {
		add_submenu_page(
			'baloa-structure-auditor-seo',
			__( 'Configuración', 'baloa-structure-auditor-seo' ),
			__( 'Configuración', 'baloa-structure-auditor-seo' ),
			Capabilities::manage_settings(),
			'baloa-settings',
			[ SettingsPresenter::class, 'render_settings_page' ]
		);
	}

	/**
	 * Sanitizes plugin options array.
	 */
	public static function sanitize_options( array $input ): array {
		$sanitized = [];
		$defaults  = self::get_defaults();

		$sanitized['pagespeed_api_key']         = sanitize_text_field( $input['pagespeed_api_key'] ?? '' );
		$sanitized['enable_cwv']                = isset( $input['enable_cwv'] ) ? (bool) $input['enable_cwv'] : $defaults['enable_cwv'];
		$sanitized['enable_broken_links_check'] = isset( $input['enable_broken_links_check'] ) ? (bool) $input['enable_broken_links_check'] : $defaults['enable_broken_links_check'];
		$sanitized['enable_scheduled_analysis'] = isset( $input['enable_scheduled_analysis'] ) ? (bool) $input['enable_scheduled_analysis'] : $defaults['enable_scheduled_analysis'];
		$sanitized['schedule_alert_email']      = sanitize_email( $input['schedule_alert_email'] ?? $defaults['schedule_alert_email'] );
		$sanitized['schedule_alert_threshold']  = absint( $input['schedule_alert_threshold'] ?? $defaults['schedule_alert_threshold'] );
		$sanitized['schedule_alert_threshold']  = max( 1, min( 50, $sanitized['schedule_alert_threshold'] ) );
		$sanitized['timeout']                   = absint( $input['timeout'] ?? $defaults['timeout'] );
		$sanitized['timeout']                   = max( 5, min( 60, $sanitized['timeout'] ) );

		$sanitized['enable_telemetry']          = isset( $input['enable_telemetry'] ) ? (bool) $input['enable_telemetry'] : false;
		$sanitized['enable_structural_fixes']   = isset( $input['enable_structural_fixes'] ) ? (bool) $input['enable_structural_fixes'] : false;
		$sanitized['structural_fixes_strategy'] = sanitize_text_field( $input['structural_fixes_strategy'] ?? $defaults['structural_fixes_strategy'] );
		if ( ! in_array( $sanitized['structural_fixes_strategy'], [ 'wrap', 'replace' ], true ) ) {
			$sanitized['structural_fixes_strategy'] = 'wrap';
		}
		$sanitized['structural_header_selectors'] = sanitize_text_field( $input['structural_header_selectors'] ?? $defaults['structural_header_selectors'] );
		$sanitized['structural_main_selectors']   = sanitize_text_field( $input['structural_main_selectors'] ?? $defaults['structural_main_selectors'] );
		$sanitized['structural_footer_selectors'] = sanitize_text_field( $input['structural_footer_selectors'] ?? $defaults['structural_footer_selectors'] );
		$sanitized['faq_page_id']                 = absint( $input['faq_page_id'] ?? 0 );

		$sanitized['ui_theme'] = sanitize_text_field( $input['ui_theme'] ?? $defaults['ui_theme'] );
		if ( ! in_array( $sanitized['ui_theme'], [ 'dark', 'light', 'auto' ], true ) ) {
			$sanitized['ui_theme'] = 'dark';
		}

		$sanitized['openai_api_key'] = sanitize_text_field( $input['openai_api_key'] ?? '' );
		$sanitized['gemini_api_key'] = sanitize_text_field( $input['gemini_api_key'] ?? '' );
		$sanitized['claude_api_key'] = sanitize_text_field( $input['claude_api_key'] ?? '' );

		$sanitized['ai_provider'] = sanitize_text_field( $input['ai_provider'] ?? 'default' );
		if ( ! in_array( $sanitized['ai_provider'], [ 'default', 'openai', 'gemini', 'claude' ], true ) ) {
			$sanitized['ai_provider'] = 'default';
		}

		return $sanitized;
	}

	/**
	 * Sanitizes dynamic AI bots block options.
	 */
	public static function sanitize_ai_crawler_options( $input ): array {
		if ( ! is_array( $input ) ) {
			return [];
		}
		$sanitized = [];
		foreach ( $input as $name => $data ) {
			$clean_name = trim( preg_replace( '/[^a-zA-Z0-9\-\*_]/', '', $name ) );
			if ( empty( $clean_name ) ) {
				continue;
			}
			$directive = isset( $data['directive'] ) && 'allow' === strtolower( trim( $data['directive'] ) ) ? 'Allow' : 'Disallow';
			$desc      = sanitize_text_field( $data['desc'] ?? '' );
			$sanitized[ $clean_name ] = [
				'directive' => $directive,
				'desc'      => $desc,
			];
		}
		return $sanitized;
	}

	/**
	 * Returns defaults options for plugin.
	 */
	public static function get_defaults(): array {
		return [
			'pagespeed_api_key'           => '',
			'enable_cwv'                  => true,
			'enable_broken_links_check'   => true,
			'enable_scheduled_analysis'   => false,
			// phpcs:ignore
			'schedule_alert_email'        => get_option( 'admin_email' ),
			'schedule_alert_threshold'    => 10,
			'timeout'                     => 20,
			'enable_telemetry'            => false,
			'enable_structural_fixes'     => false,
			'structural_fixes_strategy'   => 'wrap',
			'structural_header_selectors' => 'masthead, site-header, header-wrap',
			'structural_main_selectors'   => 'content, primary, content-area',
			'structural_footer_selectors' => 'colophon, site-footer, footer-wrap',
			'faq_page_id'                 => 0,
			'ui_theme'                    => 'dark',
			'openai_api_key'              => '',
			'gemini_api_key'              => '',
			'claude_api_key'              => '',
			'ai_provider'                 => 'default',
			'module_weights'              => [
				'html'        => 1.0,
				'keyword'     => 1.0,
				'schema'      => 1.2,
				'readability' => 0.8,
				'metatags'    => 1.0,
				'llms'        => 2.0, // GEO — alta prioridad en era IA
				'aeo'         => 2.0, // AEO — alta prioridad en era IA
				'cwv'         => 1.5,
				'links'       => 1.0,
				'geo'         => 1.5, // SEO Local
			],
		];
	}

	/**
	 * Helper function to retrieve a specific plugin option.
	 */
	public static function get_option( string $key, $default = null ) {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		if ( null === $default ) {
			$default = self::get_defaults()[ $key ] ?? null;
		}
		return $options[ $key ] ?? $default;
	}
}
