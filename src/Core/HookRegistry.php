<?php
/**
 * BaloaStructureAuditorSEO\Core\HookRegistry
 *
 * Centralized hook registration for the entire plugin.
 */

namespace BaloaStructureAuditorSEO\Core;

use BaloaStructureAuditorSEO\Admin\Settings;
use BaloaStructureAuditorSEO\Admin\MetaBox;
use BaloaStructureAuditorSEO\Admin\AdminPage;
use BaloaStructureAuditorSEO\Ajax\Handlers;
use BaloaStructureAuditorSEO\Api\RestController;
use BaloaStructureAuditorSEO\Pro\Services\SchedulerService;
use BaloaStructureAuditorSEO\Pro\Services\AutoFixService;
use BaloaStructureAuditorSEO\Pro\Services\StructuralFixerService;
use BaloaStructureAuditorSEO\Pro\Services\FAQSchemaService;

if ( ! defined( 'ABSPATH' ) ) exit;

class HookRegistry {

    public static function boot(): void {
        if ( ! class_exists( Plugin::class ) ) {
            add_action( 'admin_notices', [ __CLASS__, 'render_missing_plugin_notice' ] );
            return;
        }

        // Initialize licensing provider, register dynamic analyzers and Pro hooks on plugins_loaded
        add_action( 'plugins_loaded', [ __CLASS__, 'init_premium_and_analyzers' ], 15 );

        self::register_plugin_bootstrap();
        self::register_admin_ui();
        self::register_ajax_handlers();
        self::register_rest_api();

        self::register_assets();
        self::register_i18n();
    }

    /**
     * Delayed initialization of license, analyzers, and premium hooks
     * to avoid race conditions with the Pro add-on load priority.
     */
    public static function init_premium_and_analyzers(): void {
        self::initialize_licensing_and_analyzers();

        // Conditionally register premium features
        $is_premium = Plugin::get_instance()->get_license()->is_premium();
        if ( $is_premium ) {
            self::register_cron();
            self::register_autofix();
            self::register_structural_fixer();
            self::register_faq_schema();
            
            // Core Web Vitals RUM, AI crawler, and semantic schema hooks
            self::register_pro_services();
        }
    }

    public static function render_missing_plugin_notice(): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__( 'Baloa Structure Auditor for SEO: clase Plugin no disponible.', 'baloa-structure-auditor-seo' );
        echo '</p></div>';
    }

    /**
     * Initializes the license provider and registers dynamic core/premium analyzers.
     */
    private static function initialize_licensing_and_analyzers(): void {
        // Instantiate and set the local license provider
        if ( class_exists( \BaloaStructureAuditorSEO\Infrastructure\Licensing\LocalLicenseProvider::class ) ) {
            $license_provider = new \BaloaStructureAuditorSEO\Infrastructure\Licensing\LocalLicenseProvider();
            Plugin::get_instance()->set_license_provider( $license_provider );
        }

        // Register Free Analyzers
        if ( class_exists( \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::class ) ) {
            \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'html', \BaloaStructureAuditorSEO\Free\Analyzers\HTMLInspector::class );
            \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'metatags', \BaloaStructureAuditorSEO\Free\Analyzers\MetaTagsAnalyzer::class );
            \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'schema', \BaloaStructureAuditorSEO\Free\Analyzers\SchemaChecker::class );
            \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'readability', \BaloaStructureAuditorSEO\Free\Analyzers\ReadabilityAnalyzer::class );
            \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'links', \BaloaStructureAuditorSEO\Free\Analyzers\LinksAnalyzer::class );
            \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'images', \BaloaStructureAuditorSEO\Free\Analyzers\ImageAnalyzer::class );

            // If Premium, register Pro Analyzers
            $is_premium = Plugin::get_instance()->get_license()->is_premium();
            if ( $is_premium ) {
                \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'aeo', \BaloaStructureAuditorSEO\Pro\Analyzers\AEOAnalyzer::class );
                \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'llms', \BaloaStructureAuditorSEO\Pro\Analyzers\LLMsChecker::class );
                \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'geo', \BaloaStructureAuditorSEO\Pro\Analyzers\GEOAnalyzer::class );
                \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'entities', \BaloaStructureAuditorSEO\Pro\Analyzers\EntityAnalyzer::class );
                \BaloaStructureAuditorSEO\Core\AnalyzerRegistry::register( 'naturalness', \BaloaStructureAuditorSEO\Pro\Analyzers\NaturalnessAnalyzer::class );
            }
        }
    }

    private static function register_plugin_bootstrap(): void {
        add_action( 'plugins_loaded', function () {
            if ( ! class_exists( Plugin::class ) ) {
                return;
            }
            Plugin::get_instance()->init();
        } );
    }

    private static function register_admin_ui(): void {
        // Must register admin_menu BEFORE it fires. admin_menu runs earlier than admin_init.
        $register = static function (): void {
            if ( ! class_exists( Settings::class ) || ! class_exists( MetaBox::class ) || ! class_exists( AdminPage::class ) ) {
                return;
            }
            AdminPage::register_hooks();
            Settings::register_hooks();
            MetaBox::register_hooks();
        };

        if ( did_action( 'plugins_loaded' ) ) {
            $register();
        } else {
            add_action( 'plugins_loaded', $register, 20 );
        }
    }

    private static function register_ajax_handlers(): void {
        add_action( 'plugins_loaded', function () {
            if ( ! class_exists( Handlers::class ) ) {
                return;
            }
            Handlers::register_hooks();
        }, 20 );
    }

    private static function register_rest_api(): void {
        add_action( 'rest_api_init', function () {
            if ( class_exists( RestController::class ) ) {
                RestController::register_routes();
            }
            if ( class_exists( \BaloaStructureAuditorSEO\Api\TelemetryController::class ) && Plugin::get_instance()->get_license()->is_premium() ) {
                \BaloaStructureAuditorSEO\Api\TelemetryController::register_routes();
            }
        } );
    }

    private static function register_cron(): void {
        add_action( \BALOA_STRUCTURE_AUDITOR_SEO_CRON_HOOK, function () {
            if ( ! class_exists( SchedulerService::class ) ) {
                return;
            }
            SchedulerService::process_scheduled_posts();
        } );
    }

    private static function register_assets(): void {
        add_action( 'admin_enqueue_scripts', function ( string $hook ) {
            if ( ! class_exists( Plugin::class ) ) {
                return;
            }
            Plugin::get_instance()->enqueue_admin_assets( $hook );
        } );
    }

    private static function register_i18n(): void {
        add_action( 'init', function () {
            if ( ! class_exists( Plugin::class ) ) {
                return;
            }
            Plugin::get_instance()->load_textdomain();
        } );
    }

    private static function register_autofix(): void {
        add_action( 'plugins_loaded', function () {
            if ( ! class_exists( AutoFixService::class ) ) {
                return;
            }
            AutoFixService::register_hooks();
        }, 20 );
    }

    private static function register_structural_fixer(): void {
        add_action( 'plugins_loaded', function () {
            if ( ! class_exists( StructuralFixerService::class ) ) {
                return;
            }
            StructuralFixerService::register_hooks();
        }, 20 );
    }

    private static function register_faq_schema(): void {
        add_action( 'plugins_loaded', function () {
            if ( ! class_exists( FAQSchemaService::class ) ) {
                return;
            }
            FAQSchemaService::register_hooks();
        }, 20 );
    }

    private static function register_pro_services(): void {
        add_action( 'plugins_loaded', function () {
            if ( class_exists( \BaloaStructureAuditorSEO\Pro\Services\AIControlService::class ) ) {
                \BaloaStructureAuditorSEO\Pro\Services\AIControlService::register_hooks();
            }
            if ( class_exists( \BaloaStructureAuditorSEO\Pro\Services\TelemetryService::class ) ) {
                \BaloaStructureAuditorSEO\Pro\Services\TelemetryService::register_hooks();
            }
            if ( class_exists( \BaloaStructureAuditorSEO\Pro\Services\SchemaGeneratorService::class ) ) {
                \BaloaStructureAuditorSEO\Pro\Services\SchemaGeneratorService::register_hooks();
            }
        }, 20 );
    }
}
