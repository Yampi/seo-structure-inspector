<?php
/**
 * BaloaStructureAuditorSEO\Core\BaseAnalyzer
 * 
 * Abstract base class for all analyzers.
 * Provides common methods for DOM loading and URL parsing.
 */

namespace BaloaStructureAuditorSEO\Core;

use BaloaStructureAuditorSEO\Core\Contracts\AnalyzerInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class BaseAnalyzer implements AnalyzerInterface {

    /**
     * Load HTML into DOMDocument with error handling.
     *
     * @param string $html Raw HTML string.
     * @return \DOMDocument Loaded DOM document.
     */
    protected static function load_dom( string $html ): \DOMDocument {
        $dom = new \DOMDocument();
        libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR );
        libxml_clear_errors();
        return $dom;
    }

    /**
     * Extract base URL (scheme + host) from a full URL.
     *
     * @param string $url Full URL.
     * @return string Base URL (scheme://host).
     */
    protected static function get_base_url( string $url ): string {
        $p = wp_parse_url( $url );
        return ( $p['scheme'] ?? 'https' ) . '://' . ( $p['host'] ?? '' );
    }
}
