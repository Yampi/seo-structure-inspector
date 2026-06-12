<?php
/**
 * BaloaStructureAuditorSEO\Free\Analyzers\HTMLInspector
 *
 * Analyzes the structural hierarchy of HTML tags:
 * body, header, footer, main, section, article, h1-h3, p
 *
 * Migrated to BaloaStructureAuditorSEO\Core\ScoringEngine in v0.3.0.
 *
 * @package SEO_Structure_Inspector
 * @since   0.1.0
 */

namespace BaloaStructureAuditorSEO\Free\Analyzers;

use BaloaStructureAuditorSEO\Core\ScoringEngine;
use BaloaStructureAuditorSEO\Core\CheckPresenter;
use BaloaStructureAuditorSEO\Core\BaseAnalyzer;

if ( ! defined( 'ABSPATH' ) ) exit;

class HTMLInspector extends BaseAnalyzer {

    /**
     * Main entry point.
     *
     * @param string $html Raw HTML string.
     * @param string $url  Optional URL.
     * @param array  $context Optional context.
     * @return array Standard module result via ScoringEngine::build_result().
     */
    public static function analyze( string $html, string $url = '', array $context = [] ): array|\BaloaStructureAuditorSEO\Core\DTO\ModuleResult {
        $dom     = self::load_dom( $html );
        $xpath   = new \DOMXPath( $dom );
        $details = self::build_details( $dom, $xpath );
        $checks  = CheckPresenter::apply_en( self::run_checks( $details ) );

        return ScoringEngine::build_result( $checks, $details );
    }

    // â”€â”€ Tag counters â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function count_tag( \DOMDocument $dom, string $tag ): int {
        return $dom->getElementsByTagName( $tag )->length;
    }

    private static function get_texts( \DOMDocument $dom, string $tag ): array {
        $nodes = $dom->getElementsByTagName( $tag );
        $texts = [];
        foreach ( $nodes as $node ) {
            $texts[] = trim( $node->textContent );
        }
        return array_filter( $texts );
    }

    // â”€â”€ Build raw details â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function build_details( \DOMDocument $dom, \DOMXPath $xpath ): array {
        $tags    = [ 'body', 'header', 'main', 'footer', 'section', 'article', 'h1', 'h2', 'h3', 'p' ];
        $details = [];

        foreach ( $tags as $tag ) {
            $count = self::count_tag( $dom, $tag );
            $details[ $tag ] = [
                'count' => $count,
                'texts' => in_array( $tag, [ 'h1', 'h2', 'h3' ], true )
                    ? array_values( self::get_texts( $dom, $tag ) )
                    : [],
            ];
        }

        $details['heading_order'] = self::get_heading_order( $dom );

        return $details;
    }

    private static function get_heading_order( \DOMDocument $dom ): array {
        $xpath    = new \DOMXPath( $dom );
        $nodes    = $xpath->query( '//*[self::h1 or self::h2 or self::h3]' );
        $headings = [];
        foreach ( $nodes as $node ) {
            $headings[] = [
                'tag'  => $node->nodeName,
                'text' => trim( $node->textContent ),
            ];
        }
        return $headings;
    }

    // â”€â”€ Checks â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Returns array of standard checks.
     * All checks follow ScoringEngine format.
     *
     * @param array $d Details array from build_details().
     * @return array[]
     */
    private static function run_checks( array $d ): array {
        $checks = [];

        // â”€â”€ 1. Single <body> â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( $d['body']['count'] === 1 ) {
            $checks[] = [
                'id'       => 'single_body',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'count' => 1 ],
            ];
        } else {
            $checks[] = [
                'id'             => 'single_body',
                'severity'       => 'error',
                'category'       => 'seo',
                'context'        => [ 'count' => $d['body']['count'] ],
            ];
        }

        // â”€â”€ 2. Single <h1> â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $h1 = $d['h1']['count'];
        if ( $h1 === 1 ) {
            $checks[] = [
                'id'       => 'single_h1',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'count' => 1, 'value' => ( $d['h1']['texts'][0] ?? '' ) ],
            ];
        } elseif ( $h1 === 0 ) {
            $checks[] = [
                'id'             => 'single_h1',
                'severity'       => 'error',
                'category'       => 'seo',
                'context'        => [ 'count' => 0 ],
            ];
        } else {
            $checks[] = [
                'id'             => 'single_h1',
                'severity'       => 'error',
                'category'       => 'seo',
                'context'        => [ 'count' => $h1, 'texts' => $d['h1']['texts'] ],
            ];
        }

        // â”€â”€ 3. h2 exists if h3 exists â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( $d['h3']['count'] > 0 && $d['h2']['count'] === 0 ) {
            $checks[] = [
                'id'             => 'heading_hierarchy',
                'severity'       => 'error',
                'category'       => 'seo',
                'context'        => [ 'h2_count' => 0, 'h3_count' => $d['h3']['count'] ],
            ];
        } elseif ( $d['h2']['count'] > 0 ) {
            $checks[] = [
                'id'       => 'heading_hierarchy',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'h2_count' => $d['h2']['count'] ],
            ];
        }

        // â”€â”€ 4. Heading order (no skipping levels) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( count( $d['heading_order'] ) === 0 ) {
            $checks[] = [
                'id'       => 'heading_order',
                'severity' => 'info',
                'category' => 'seo',
                'message'  => 'Informativo/No aplica: Esta página no utiliza encabezados',
                'supports_autofix' => false,
            ];
        } else {
            $order_issues = self::check_heading_order( $d['heading_order'] );
            if ( empty( $order_issues ) ) {
                $checks[] = [
                    'id'       => 'heading_order',
                    'severity' => 'pass',
                    'category' => 'seo',
                    'message'  => 'Heading hierarchy has no skipped levels (h1 -> h2 -> h3)',
                ];
            } else {
                foreach ( $order_issues as $i => $oi ) {
                    $checks[] = [
                        'id'             => 'heading_order_' . $i,
                        'severity'       => 'error',
                        'category'       => 'seo',
                        'message'        => $oi,
                        'recommendation' => 'Fix heading order so levels are not skipped. Structure: h1 -> h2 -> h3.',
                    ];
                }
            }
        }

        // â”€â”€ 5. <footer> present â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( $d['footer']['count'] >= 1 ) {
            $checks[] = [
                'id'       => 'has_footer',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'count' => $d['footer']['count'] ],
            ];
        } else {
            $checks[] = [
                'id'             => 'has_footer',
                'severity'       => 'warning',
                'category'       => 'seo',
            ];
        }

        // â”€â”€ 6. <main> present â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( $d['main']['count'] >= 1 ) {
            $checks[] = [
                'id'       => 'has_main',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'count' => $d['main']['count'] ],
            ];
        } else {
            $checks[] = [
                'id'             => 'has_main',
                'severity'       => 'warning',
                'category'       => 'geo',
            ];
        }

        // â”€â”€ 7. <section> or <article> used â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $has_semantic = $d['section']['count'] > 0 || $d['article']['count'] > 0;
        if ( $has_semantic ) {
            $checks[] = [
                'id'       => 'semantic_tags',
                'severity' => 'pass',
                'category' => 'geo',
                'context'  => [ 'section_count' => $d['section']['count'], 'article_count' => $d['article']['count'] ],
            ];
        } else {
            $checks[] = [
                'id'             => 'semantic_tags',
                'severity'       => 'warning',
                'category'       => 'geo',
            ];
        }

        // â”€â”€ 8. <p> tags present â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( $d['p']['count'] > 0 ) {
            $checks[] = [
                'id'       => 'has_paragraphs',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'count' => $d['p']['count'] ],
            ];
        } else {
            $checks[] = [
                'id'             => 'has_paragraphs',
                'severity'       => 'error',
                'category'       => 'seo',
                'context'        => [ 'count' => 0 ],
            ];
        }

        // â”€â”€ 9. <article> inside <section> â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( $d['section']['count'] > 0 && $d['article']['count'] > 0 ) {
            $checks[] = [
                'id'       => 'article_in_section',
                'severity' => 'pass',
                'category' => 'geo',
                'context'  => [ 'section_count' => $d['section']['count'], 'article_count' => $d['article']['count'] ],
            ];
        }

        return $checks;
    }

    /**
     * Checks that headings don't skip levels (e.g. h1 -> h3 without h2).
     *
     * @param array $headings Flat list from get_heading_order().
     * @return string[] Error messages.
     */
    private static function check_heading_order( array $headings ): array {
        $level_map = [ 'h1' => 1, 'h2' => 2, 'h3' => 3 ];
        $errors    = [];
        $prev      = 0;

        foreach ( $headings as $h ) {
            $level = $level_map[ $h['tag'] ] ?? null;
            if ( $level === null ) continue;

            if ( $prev > 0 && $level > $prev + 1 ) {
                $errors[] = sprintf(
                    'Hierarchy skip: <%s> after <h%d> — "%s"',
                    $h['tag'], $prev, mb_substr( $h['text'], 0, 60 )
                );
            }
            $prev = $level;
        }

        return $errors;
    }
}
