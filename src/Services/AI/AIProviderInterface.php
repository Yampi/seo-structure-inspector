<?php
/**
 * SEOSI\Services\AI\AIProviderInterface
 *
 * Contract for all AI recommendation engines/providers.
 */

namespace SEOSI\Services\AI;

if ( ! defined( 'ABSPATH' ) ) exit;

interface AIProviderInterface {

    /**
     * Get the name of this AI provider.
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Check if this provider is configured (e.g. has API keys).
     *
     * @return bool
     */
    public function is_configured(): bool;

    /**
     * Generate recommendations based on the analyzed URL and context.
     *
     * @param string $url     The analyzed URL.
     * @param array  $context Additional context from the site analysis.
     * @return array Array of recommendations.
     */
    public function get_recommendations( string $url, array $context = [] ): array;
}
