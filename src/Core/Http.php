<?php
/**
 * SEOSI\Core\Http
 * Shared HTTP defaults for wp_remote_* calls.
 */

namespace SEOSI\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Http {

    public static function sslverify(): bool {
        return (bool) apply_filters( 'seosi_sslverify', true );
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public static function args( array $args = [] ): array {
        $args['sslverify'] = self::sslverify();
        return $args;
    }
}
