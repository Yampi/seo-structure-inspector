<?php
/**
 * BaloaStructureAuditorSEO\Core\Contracts\LicenseProviderInterface
 * 
 * Agnostic license contract to prevent provider lock-in.
 */

namespace BaloaStructureAuditorSEO\Core\Contracts;

if ( ! defined( 'ABSPATH' ) ) exit;

interface LicenseProviderInterface {

    /**
     * Check if the premium/PRO features are unlocked.
     *
     * @return bool True if active premium license, false otherwise.
     */
    public function is_premium(): bool;

    /**
     * Check if a specific feature is enabled under the current plan.
     *
     * @param string $feature_id Name of the feature (e.g., 'aeo', 'batch', 'pdf').
     * @return bool True if allowed, false otherwise.
     */
    public function has_feature( string $feature_id ): bool;

    /**
     * Retrieve the detailed status of the license.
     *
     * @return array Standardized license status array.
     */
    public function get_license_status(): array;
}
