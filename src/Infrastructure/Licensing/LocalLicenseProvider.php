<?php
/**
 * SEOSI\Infrastructure\Licensing\LocalLicenseProvider
 * 
 * Concrete implementation for local license key validation.
 */

namespace SEOSI\Infrastructure\Licensing;

use SEOSI\Core\Contracts\LicenseProviderInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

class LocalLicenseProvider implements LicenseProviderInterface {

    /**
     * Allowed test keys.
     */
    private const VALID_KEYS = [
        'SI-PRO-LOCAL-TEST',
        'PRO-DEV'
    ];

    /**
     * Cache local status to avoid repeating lookups.
     */
    private ?bool $cached_premium = null;

    /**
     * Check if the premium/PRO features are unlocked.
     *
     * @return bool True if active premium license, false otherwise.
     */
    public function is_premium(): bool {
        if ( null !== $this->cached_premium ) {
            return $this->cached_premium;
        }

        // Allow bypassing via local constant for dev environments
        if ( defined( 'SEOSI_PRO_ENABLED' ) && \SEOSI_PRO_ENABLED ) {
            $this->cached_premium = true;
            return true;
        }

        $key = (string) get_option( 'seosi_license_key', '' );
        $key = strtoupper( trim( $key ) );

        $is_valid = in_array( $key, self::VALID_KEYS, true );
        $this->cached_premium = $is_valid;

        return $is_valid;
    }

    /**
     * Check if a specific feature is enabled under the current plan.
     *
     * @param string $feature_id Name of the feature (e.g., 'aeo', 'batch', 'pdf').
     * @return bool True if allowed, false otherwise.
     */
    public function has_feature( string $feature_id ): bool {
        // In local mock mode, a valid key unlocks all features.
        return $this->is_premium();
    }

    /**
     * Retrieve the detailed status of the license.
     *
     * @return array Standardized license status array.
     */
    public function get_license_status(): array {
        $key = (string) get_option( 'seosi_license_key', '' );
        $is_premium = $this->is_premium();

        return [
            'active'      => $is_premium,
            'license_key' => $key,
            'type'        => $is_premium ? 'PRO (Local Dev)' : 'Free',
            'expires'     => 'Lifetime',
            'provider'    => 'Local',
        ];
    }
}
