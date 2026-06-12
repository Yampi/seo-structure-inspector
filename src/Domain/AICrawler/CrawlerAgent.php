<?php
/**
 * BaloaStructureAuditorSEO\Domain\AICrawler\CrawlerAgent
 *
 * Value Object representing an AI Crawler Agent rule.
 */

declare(strict_types=1);

namespace BaloaStructureAuditorSEO\Domain\AICrawler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CrawlerAgent {

    private string $name;
    private string $directive; // 'Allow' or 'Disallow'
    private string $description;

    /**
     * Constructor.
     *
     * @param string $name        Name of the AI crawler agent (e.g., GPTBot).
     * @param string $directive   Directive rule: 'Allow' or 'Disallow'.
     * @param string $description Optional description of the crawler.
     */
    public function __construct( string $name, string $directive = 'Disallow', string $description = '' ) {
        $this->name        = $this->sanitize_name( $name );
        $this->directive   = $this->sanitize_directive( $directive );
        $this->description = $description;
    }

    /**
     * Get the sanitized name.
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get the directive ('Allow' or 'Disallow').
     */
    public function get_directive(): string {
        return $this->directive;
    }

    /**
     * Get the description.
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Helper to sanitize crawler name.
     */
    private function sanitize_name( string $name ): string {
        return trim( preg_replace( '/[^a-zA-Z0-9\-\*_]/', '', $name ) );
    }

    /**
     * Helper to sanitize directive.
     */
    private function sanitize_directive( string $directive ): string {
        $dir = ucfirst( strtolower( trim( $directive ) ) );
        return in_array( $dir, [ 'Allow', 'Disallow' ], true ) ? $dir : 'Disallow';
    }
}
