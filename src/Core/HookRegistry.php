<?php
/**
 * SEOSI\Core\HookRegistry
 *
 * Centralized hook registration for the entire plugin.
 */

namespace SEOSI\Core;

use SEOSI\Admin\Settings;
use SEOSI\Admin\MetaBox;
use SEOSI\Admin\AdminPage;
use SEOSI\Ajax\Handlers;
use SEOSI\Api\RestController;
use SEOSI\Pro\Services\SchedulerService;
use SEOSI\Pro\Services\AutoFixService;
use SEOSI\Pro\Services\StructuralFixerService;
use SEOSI\Pro\Services\FAQSchemaService;

if ( ! defined( 'ABSPATH' ) ) exit;

class HookRegistry {

    public static function boot(): void {
        if ( ! class_exists( Plugin::class ) ) {
            add_action( 'admin_notices', [ __CLASS__, 'render_missing_plugin_notice' ] );
            return;
        }

        // Initialize licensing provider and register dynamic analyzers
        self::initialize_licensing_and_analyzers();

        self::register_plugin_bootstrap();
        self::register_admin_ui();
        self::register_ajax_handlers();
        self::register_rest_api();
        
        // Conditionally register premium features
        $is_premium = Plugin::get_instance()->get_license()->is_premium();
        if ( $is_premium ) {
            self::register_cron();
            self::register_autofix();
            self::register_structural_fixer();
            self::register_faq_schema();
        }

        self::register_assets();
        self::register_i18n();
    }

    public static function render_missing_plugin_notice(): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__( 'SEO Structure Inspector: clase Plugin no disponible.', 'seo-si' );
        echo '</p></div>';
    }

    /**
     * Initializes the license provider and registers dynamic core/premium analyzers.
     */
    private static function initialize_licensing_and_analyzers(): void {
        // Instantiate and set the local license provider
        if ( class_exists( \SEOSI\Infrastructure\Licensing\LocalLicenseProvider::class ) ) {
            $license_provider = new \SEOSI\Infrastructure\Licensing\LocalLicenseProvider();
            Plugin::get_instance()->set_license_provider( $license_provider );
        }

        // Register Free Analyzers
        if ( class_exists( \SEOSI\Core\AnalyzerRegistry::class ) ) {
            \SEOSI\Core\AnalyzerRegistry::register( 'html', \SEOSI\Free\Analyzers\HTMLInspector::class );
            \SEOSI\Core\AnalyzerRegistry::register( 'metatags', \SEOSI\Free\Analyzers\MetaTagsAnalyzer::class );
            \SEOSI\Core\AnalyzerRegistry::register( 'schema', \SEOSI\Free\Analyzers\SchemaChecker::class );
            \SEOSI\Core\AnalyzerRegistry::register( 'readability', \SEOSI\Free\Analyzers\ReadabilityAnalyzer::class );
            \SEOSI\Core\AnalyzerRegistry::register( 'links', \SEOSI\Free\Analyzers\LinksAnalyzer::class );

            // If Premium, register Pro Analyzers
            $is_premium = Plugin::get_instance()->get_license()->is_premium();
            if ( $is_premium ) {
                \SEOSI\Core\AnalyzerRegistry::register( 'aeo', \SEOSI\Pro\Analyzers\AEOAnalyzer::class );
                \SEOSI\Core\AnalyzerRegistry::register( 'llms', \SEOSI\Pro\Analyzers\LLMsChecker::class );
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
            if ( ! class_exists( RestController::class ) ) {
                return;
            }
            RestController::register_routes();
        } );
    }

    private static function register_cron(): void {
        add_action( \SEOSI_CRON_HOOK, function () {
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
}
