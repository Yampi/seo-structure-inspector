<?php
/**
 * BaloaStructureAuditorSEO\Infrastructure\Storage\TransientStorage
 * 
 * WordPress Transient API adapter implementing StorageInterface.
 */

namespace BaloaStructureAuditorSEO\Infrastructure\Storage;

use BaloaStructureAuditorSEO\Services\Storage\StorageInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

class TransientStorage implements StorageInterface {

    private string $prefix;

    /**
     * Constructor.
     *
     * @param string $prefix Prefix for the transient keys.
     */
    public function __construct( string $prefix = 'baloa_' ) {
        $this->prefix = $prefix;
    }

    /**
     * Store data with a key.
     *
     * @param string $key Storage key.
     * @param array $data Data to store.
     * @param int $expiry Expiration time in seconds.
     * @return bool True on success, false on failure.
     */
    public function save( string $key, array $data, int $expiry ): bool {
        return set_transient( $this->prefix . $key, $data, $expiry );
    }

    /**
     * Retrieve data by key.
     *
     * @param string $key Storage key.
     * @return array|null Data array or null if not found/expired.
     */
    public function get( string $key ): ?array {
        $data = get_transient( $this->prefix . $key );
        return is_array( $data ) ? $data : null;
    }

    /**
     * Delete data by key.
     *
     * @param string $key Storage key.
     * @return bool True on success, false on failure.
     */
    public function delete( string $key ): bool {
        return delete_transient( $this->prefix . $key );
    }

    /**
     * Get approximate size in bytes of stored data.
     *
     * @param string $key Storage key.
     * @return int Size in bytes.
     */
    public function get_size( string $key ): int {
        $data = $this->get( $key );
        if ( ! $data ) {
            return 0;
        }
        return strlen( serialize( $data ) );
    }
}
