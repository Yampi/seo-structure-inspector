<?php
/**
 * SEOSI\Free\Analyzers\MetaTagsAnalyzer
 *
 * Checks presence and quality of meta tags:
 * standard SEO metas, Open Graph, Twitter Card, canonical, robots, viewport.
 *
 * Migrated to SEOSI\Core\ScoringEngine in v0.3.0.
 *
 * @package SEO_Structure_Inspector
 * @since   0.1.0
 */

namespace SEOSI\Free\Analyzers;

use SEOSI\Core\ScoringEngine;
use SEOSI\Core\CheckPresenter;
use SEOSI\Core\BaseAnalyzer;

if ( ! defined( 'ABSPATH' ) ) exit;

class MetaTagsAnalyzer extends BaseAnalyzer {

    /**
     * Main entry point.
     *
     * @param string $html Raw HTML string.
     * @param string $url  Optional URL.
     * @param array  $context Optional context.
     * @return array Standard module result via ScoringEngine::build_result().
     */
    public static function analyze( string $html, string $url = '', array $context = [] ): array|\SEOSI\Core\DTO\ModuleResult {
        $dom   = self::load_dom( $html );
        $xpath = new \DOMXPath( $dom );

        $checks  = [];
        $details = self::extract_all( $xpath );

        // ── Standard SEO ──────────────────────────────────────────────────────

        // Title
        $title = trim( $xpath->query( '//title' )->item(0)?->textContent ?? '' );
        $title_len = mb_strlen( $title );

        if ( ! $title ) {
            $checks[] = [
                'id'             => 'meta_title',
                'severity'       => 'error',
                'category'       => 'seo',
                'context'        => [ 'length' => 0, 'value' => '' ],
            ];
        } elseif ( $title_len < 30 ) {
            $checks[] = [
                'id'             => 'meta_title',
                'severity'       => 'warning',
                'category'       => 'seo',
                'context'        => [ 'length' => $title_len, 'value' => $title ],
            ];
        } elseif ( $title_len > 60 ) {
            $checks[] = [
                'id'             => 'meta_title',
                'severity'       => 'warning',
                'category'       => 'seo',
                'context'        => [ 'length' => $title_len, 'value' => $title ],
            ];
        } else {
            $checks[] = [
                'id'       => 'meta_title',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'length' => $title_len, 'value' => $title ],
            ];
        }

        // Meta description
        $desc     = self::meta( $xpath, 'name', 'description' );
        $desc_len = mb_strlen( $desc );

        if ( ! $desc ) {
            $checks[] = [
                'id'             => 'meta_description',
                'severity'       => 'error',
                'category'       => 'seo',
                'context'        => [ 'length' => 0, 'value' => '' ],
            ];
        } elseif ( $desc_len < 70 ) {
            $checks[] = [
                'id'             => 'meta_description',
                'severity'       => 'warning',
                'category'       => 'seo',
                'context'        => [ 'length' => $desc_len, 'value' => $desc ],
            ];
        } elseif ( $desc_len > 155 ) {
            $checks[] = [
                'id'             => 'meta_description',
                'severity'       => 'warning',
                'category'       => 'seo',
                'context'        => [ 'length' => $desc_len, 'value' => $desc ],
            ];
        } else {
            $checks[] = [
                'id'       => 'meta_description',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'length' => $desc_len, 'value' => $desc ],
            ];
        }

        // Canonical
        $canonical = $xpath->query( '//link[@rel="canonical"]/@href' )->item(0)?->nodeValue ?? '';

        if ( ! $canonical ) {
            $checks[] = [
                'id'             => 'meta_canonical',
                'severity'       => 'warning',
                'category'       => 'seo',
                'context'        => [ 'value' => '' ],
            ];
        } else {
            $checks[] = [
                'id'       => 'meta_canonical',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'value' => $canonical ],
            ];
        }

        // Robots
        $robots = self::meta( $xpath, 'name', 'robots' );

        if ( ! $robots ) {
            $checks[] = [
                'id'             => 'meta_robots',
                'severity'       => 'warning',
                'category'       => 'seo',
                'context'        => [ 'value' => '' ],
            ];
        } elseif ( str_contains( mb_strtolower( $robots ), 'noindex' ) ) {
            $checks[] = [
                'id'             => 'meta_robots',
                'severity'       => 'error',
                'category'       => 'seo',
                'context'        => [ 'value' => $robots ],
            ];
        } else {
            $checks[] = [
                'id'       => 'meta_robots',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'value' => $robots ],
            ];
        }

        // Viewport
        $viewport = self::meta( $xpath, 'name', 'viewport' );

        if ( ! $viewport ) {
            $checks[] = [
                'id'             => 'meta_viewport',
                'severity'       => 'error',
                'category'       => 'seo',
                'context'        => [ 'value' => '' ],
            ];
        } else {
            $checks[] = [
                'id'       => 'meta_viewport',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'value' => $viewport ],
            ];
        }

        // ── Open Graph ────────────────────────────────────────────────────────

        $og_fields  = [
            'og:title'       => 'OG Title',
            'og:description' => 'OG Description',
            'og:image'       => 'OG Image',
            'og:type'        => 'OG Type',
            'og:url'         => 'OG URL',
        ];
        $og_missing = [];

        foreach ( $og_fields as $prop => $label ) {
            $val = self::meta( $xpath, 'property', $prop );
            if ( $val ) {
                $checks[] = [
                    'id'       => 'og_' . str_replace( ':', '_', $prop ),
                    'severity' => 'pass',
                    'category' => 'seo',
                    'message'  => "{$label}: " . mb_substr( $val, 0, 80 ),
                    'context'  => [ 'value' => $val ],
                ];
            } else {
                $og_missing[] = $prop;
            }
        }

        if ( ! empty( $og_missing ) ) {
            $checks[] = [
                'id'             => 'og_incomplete',
                'severity'       => 'warning',
                'category'       => 'seo',
                'context'        => [ 'missing' => $og_missing ],
            ];
        }

        // ── Twitter Card ──────────────────────────────────────────────────────

        $tw_card = self::meta( $xpath, 'name', 'twitter:card' );

        if ( ! $tw_card ) {
            $checks[] = [
                'id'             => 'twitter_card',
                'severity'       => 'warning',
                'category'       => 'seo',
                'context'        => [ 'value' => '' ],
            ];
        } else {
            $checks[] = [
                'id'       => 'twitter_card',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'value' => $tw_card ],
            ];
        }

        $tw_fields  = [ 'twitter:title', 'twitter:description', 'twitter:image' ];
        $tw_missing = [];

        foreach ( $tw_fields as $name ) {
            $val = self::meta( $xpath, 'name', $name );
            if ( $val ) {
                $checks[] = [
                    'id'       => 'twitter_' . str_replace( 'twitter:', '', $name ),
                    'severity' => 'pass',
                    'category' => 'seo',
                    'message'  => "{$name}: " . mb_substr( $val, 0, 80 ),
                    'context'  => [ 'value' => $val ],
                ];
            } else {
                $tw_missing[] = $name;
            }
        }

        if ( ! empty( $tw_missing ) ) {
            $checks[] = [
                'id'             => 'twitter_incomplete',
                'severity'       => 'warning',
                'category'       => 'seo',
                'context'        => [ 'missing' => $tw_missing ],
            ];
        }

        // Preserve legacy fields for frontend compatibility
        $details['title']     = $title;
        $details['desc']      = $desc;
        $details['canonical'] = $canonical;

        $checks = CheckPresenter::apply_en( $checks );
        return ScoringEngine::build_result( $checks, $details );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function meta( \DOMXPath $xpath, string $attr, string $value ): string {
        $node = $xpath->query( "//meta[@{$attr}='{$value}']/@content" )->item(0);
        return trim( $node?->nodeValue ?? '' );
    }

    private static function extract_all( \DOMXPath $xpath ): array {
        $nodes  = $xpath->query( '//meta[@name or @property]' );
        $result = [];
        foreach ( $nodes as $node ) {
            $key = $node->getAttribute( 'name' ) ?: $node->getAttribute( 'property' );
            $val = $node->getAttribute( 'content' );
            if ( $key ) $result[ $key ] = $val;
        }
        return $result;
    }
}
