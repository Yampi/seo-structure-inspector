<?php
/**
 * BaloaStructureAuditorSEO\Core\Http
 * Shared HTTP defaults for wp_remote_* calls.
 */

namespace BaloaStructureAuditorSEO\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Http {

    public static function sslverify(): bool {
        return (bool) apply_filters( 'baloa_structure_auditor_seo_sslverify', true );
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
