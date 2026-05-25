<?php
/**
 * SEOSI\Services\AI\AIManager
 *
 * Central manager for registering and calling AI recommendation providers.
 */

namespace SEOSI\Services\AI;

if ( ! defined( 'ABSPATH' ) ) exit;

class AIManager {

    /**
     * Cache group name for transients.
     */
    const TRANSIENT_PREFIX = 'seosi_ai_rec_';

    /**
     * Registered AI providers.
     *
     * @var AIProviderInterface[]
     */
    private static array $providers = [];

    /**
     * Register a new AI provider.
     *
     * @param string              $id       Unique identifier.
     * @param AIProviderInterface $provider The provider instance.
     */
    public static function register_provider( string $id, AIProviderInterface $provider ): void {
        self::$providers[$id] = $provider;
    }

    /**
     * Get all registered providers.
     *
     * @return AIProviderInterface[]
     */
    public static function get_providers(): array {
        self::ensure_defaults();
        return self::$providers;
    }

    /**
     * Get the active AI provider based on settings.
     *
     * @return AIProviderInterface
     */
    public static function get_active_provider(): AIProviderInterface {
        self::ensure_defaults();

        $options = get_option( 'seosi_options', [] );
        $active_id = $options['ai_provider'] ?? 'default';

        if ( isset( self::$providers[$active_id] ) ) {
            return self::$providers[$active_id];
        }

        return self::$providers['default'];
    }

    /**
     * Get AI recommendations for a specific URL, checking cache first.
     *
     * @param string $url     The analyzed URL.
     * @param array  $context Optional analysis details context.
     * @return array Array of recommendations.
     */
    public static function get_recommendations( string $url, array $context = [] ): array {
        if ( empty( $url ) ) {
            return [];
        }

        $provider = self::get_active_provider();
        $cache_key = self::TRANSIENT_PREFIX . md5( $url . '_' . $provider->get_name() );

        // Try to get cached recommendations
        $cached = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        // Get fresh recommendations
        $recommendations = $provider->get_recommendations( $url, $context );

        // Cache for 2 hours (7200 seconds) to avoid unnecessary recalculations
        set_transient( $cache_key, $recommendations, 2 * HOUR_IN_SECONDS );

        return $recommendations;
    }

    /**
     * Clear cached recommendations for a specific URL.
     *
     * @param string $url The analyzed URL.
     */
    public static function clear_cache( string $url ): void {
        $providers = self::get_providers();
        foreach ( $providers as $provider ) {
            $cache_key = self::TRANSIENT_PREFIX . md5( $url . '_' . $provider->get_name() );
            delete_transient( $cache_key );
        }
    }

    /**
     * Ensure the default local provider is registered.
     */
    private static function ensure_defaults(): void {
        if ( ! isset( self::$providers['default'] ) ) {
            self::register_provider( 'default', new DefaultAIProvider() );
        }
    }
}
