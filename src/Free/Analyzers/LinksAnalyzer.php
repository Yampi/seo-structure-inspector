<?php
/**
 * BaloaStructureAuditorSEO\Free\Analyzers\LinksAnalyzer
 *
 * Orchestrator for links analysis.
 * Delegates to specialized sub-checks for internal/external links, images, and canonical.
 *
 * Migrated to BaloaStructureAuditorSEO\Core\ScoringEngine in v0.3.0.
 *
 * @package SEO_Structure_Inspector
 * @since   0.1.0
 */

namespace BaloaStructureAuditorSEO\Free\Analyzers;

use BaloaStructureAuditorSEO\Core\ScoringEngine;
use BaloaStructureAuditorSEO\Core\BaseAnalyzer;
use BaloaStructureAuditorSEO\Analyzers\Links\InternalLinksCheck;
use BaloaStructureAuditorSEO\Analyzers\Links\ExternalLinksCheck;
use BaloaStructureAuditorSEO\Analyzers\Links\CanonicalCheck;
use BaloaStructureAuditorSEO\Analyzers\Links\BrokenLinksCheck;

if ( ! defined( 'ABSPATH' ) ) exit;

class LinksAnalyzer extends BaseAnalyzer {

    /**
     * Main entry point.
     *
     * @param string $html Raw HTML string.
     * @param string $url  Optional page URL.
     * @param array  $context Optional context.
     * @return array Standard module result via ScoringEngine::build_result().
     */
    public static function analyze( string $html, string $url = '', array $context = [] ): array|\BaloaStructureAuditorSEO\Core\DTO\ModuleResult {
        $dom   = self::load_dom( $html );
        $xpath = new \DOMXPath( $dom );
        $base  = self::get_base_url( $url );

        $checks  = [];
        $details = [];

        // â”€â”€ Links classification â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $links    = self::classify_links( $dom, $base );
        $internal = $links['internal'];
        $external = $links['external'];
        $notext   = $links['notext'];
        $nofollow = $links['nofollow_internal'];

        $details['links'] = [
            'internal_count'  => count( $internal ),
            'external_count'  => count( $external ),
            'notext_count'    => count( $notext ),
            'nofollow_count'  => count( $nofollow ),
            'internal_sample' => array_slice( $internal, 0, 5 ),
            'external_sample' => array_slice( $external, 0, 5 ),
            'notext_sample'   => array_slice( $notext,   0, 3 ),
        ];

        // â”€â”€ Internal links checks â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $checks[] = InternalLinksCheck::build_count_check( count( $internal ) );
        self::append_check( $checks, InternalLinksCheck::build_nofollow_check( count( $nofollow ) ) );

        // ——————————————————————————————————————————————————————————————————————————————
        $checks[] = ExternalLinksCheck::build_count_check( count( $external ) );
        $checks[] = ExternalLinksCheck::build_no_anchor_check( count( $notext ), $notext );

        // ——————————————————————————————————————————————————————————————————————————————

        // ——————————————————————————————————————————————————————————————————————————————
        $details['canonical'] = trim( $xpath->query( '//link[@rel="canonical"]/@href' )->item(0)?->nodeValue ?? '' );
        $checks[] = CanonicalCheck::build_check( $xpath, $url );

        // ——————————————————————————————————————————————————————————————————————————————
        $enable_broken_links_check = \BaloaStructureAuditorSEO\Admin\Settings::get_option( 'enable_broken_links_check' );
        if ( $enable_broken_links_check ) {
            $total_links = count( $internal ) + count( $external );
            
            if ( $total_links > 0 ) {
                // If total links <= 20, check all
                // If total links > 20, sample first 10 internal and first 10 external
                $urls_to_check = [];
                
                if ( $total_links <= 20 ) {
                    $urls_to_check = array_merge(
                        array_column( $internal, 'href' ),
                        array_column( $external, 'href' )
                    );
                } else {
                    // Sample first 10 internal and first 10 external
                    $internal_sample = array_slice( $internal, 0, 10 );
                    $external_sample = array_slice( $external, 0, 10 );
                    $urls_to_check = array_merge(
                        array_column( $internal_sample, 'href' ),
                        array_column( $external_sample, 'href' )
                    );
                }
                
                // Check links with timeout of 5 seconds
                $broken_results = BrokenLinksCheck::check_links( $urls_to_check, 5 );
                $broken_checks = BrokenLinksCheck::generate_checks( $broken_results );
                
                // Add broken links checks to the checks array
                $checks = array_merge( $checks, $broken_checks );
                
                // Add broken links details
                $details['broken_links'] = [
                    'checked'  => count( $urls_to_check ),
                    'results'  => $broken_results,
                ];
            }
        }

        return ScoringEngine::build_result( $checks, $details );
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     */
    private static function append_check( array &$checks, ?array $check ): void {
        if ( is_array( $check ) && $check !== [] ) {
            $checks[] = $check;
        }
    }

    // â”€â”€ Link classifier â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function classify_links( \DOMDocument $dom, string $base ): array {
        $internal          = [];
        $external          = [];
        $notext            = [];
        $nofollow_internal = [];
        $host              = wp_parse_url( $base, PHP_URL_HOST ) ?? '';

        foreach ( $dom->getElementsByTagName( 'a' ) as $a ) {
            $href = trim( $a->getAttribute( 'href' ) );
            $text = trim( $a->textContent );
            $rel  = $a->getAttribute( 'rel' );

            if ( ! $href || str_starts_with( $href, '#' ) || str_starts_with( $href, 'mailto:' ) || str_starts_with( $href, 'tel:' ) ) {
                continue;
            }

            // Resolve relative URLs
            if ( ! preg_match( '/^https?:\/\//i', $href ) ) {
                $href = rtrim( $base, '/' ) . '/' . ltrim( $href, '/' );
            }

            $is_internal = $host && str_contains( $href, $host );
            $type        = $is_internal ? 'internal' : 'external';
            $link        = [ 'href' => $href, 'text' => $text, 'type' => $type ];

            if ( empty( $text ) ) {
                $notext[] = $link;
            }

            if ( $is_internal ) {
                $internal[] = $link;
                if ( str_contains( $rel, 'nofollow' ) ) {
                    $nofollow_internal[] = $link;
                }
            } else {
                $external[] = $link;
            }
        }

        return compact( 'internal', 'external', 'notext', 'nofollow_internal' );
    }
}
