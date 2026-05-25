<?php
/**
 * SEOSI\Services\Storage\StorageInterface
 * 
 * Interface for storage backends.
 * Allows BatchAnalyzer to switch between transients, custom tables, or Redis
 * without changing its logic.
 */

namespace SEOSI\Services\Storage;

if ( ! defined( 'ABSPATH' ) ) exit;

interface StorageInterface {

    /**
     * Store data with a key.
     *
     * @param string $key Storage key.
     * @param array $data Data to store.
     * @param int $expiry Expiration time in seconds.
     * @return bool True on success, false on failure.
     */
    public function save( string $key, array $data, int $expiry ): bool;

    /**
     * Retrieve data by key.
     *
     * @param string $key Storage key.
     * @return array|null Data array or null if not found/expired.
     */
    public function get( string $key ): ?array;

    /**
     * Delete data by key.
     *
     * @param string $key Storage key.
     * @return bool True on success, false on failure.
     */
    public function delete( string $key ): bool;

    /**
     * Get approximate size in bytes of stored data.
     *
     * @param string $key Storage key.
     * @return int Size in bytes.
     */
    public function get_size( string $key ): int;
}
