<?php
/**
 * SEOSI\Core\Capabilities
 * Central capability checks for admin UI, AJAX, and REST.
 */

namespace SEOSI\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Capabilities {

    /**
     * Dashboard, meta box analysis UI, and AJAX analyze actions.
     * Default: edit_posts (Administrator, Editor). Filter to manage_options for stricter sites.
     */
    public static function analyze(): string {
        return apply_filters( 'seosi_cap_analyze', 'edit_posts' );
    }

    /**
     * Plugin settings and destructive options.
     */
    public static function manage_settings(): string {
        return apply_filters( 'seosi_cap_settings', 'manage_options' );
    }

    /**
     * REST API (Application Passwords / integrations).
     */
    public static function rest_api(): string {
        return apply_filters( 'seosi_cap_rest', self::manage_settings() );
    }

    public static function user_can_analyze(): bool {
        return current_user_can( self::analyze() );
    }

    public static function user_can_manage_settings(): bool {
        return current_user_can( self::manage_settings() );
    }
}
