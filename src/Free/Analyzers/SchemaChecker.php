<?php
/**
 * BaloaStructureAuditorSEO\Free\Analyzers\SchemaChecker
 *
 * Detects and validates Schema.org markup (JSON-LD and microdata).
 *
 * Migrated to BaloaStructureAuditorSEO\Core\ScoringEngine in v0.3.0.
 * Fix: @type can be array or string â€” normalized before validation.
 *
 * @package SEO_Structure_Inspector
 * @since   0.1.0
 */

namespace BaloaStructureAuditorSEO\Free\Analyzers;

use BaloaStructureAuditorSEO\Core\ScoringEngine;
use BaloaStructureAuditorSEO\Core\CheckPresenter;
use BaloaStructureAuditorSEO\Core\BaseAnalyzer;

if ( ! defined( 'ABSPATH' ) ) exit;

class SchemaChecker extends BaseAnalyzer {

    /**
     * Main entry point.
     *
     * @param string $html Raw page HTML.
     * @param string $url  Optional URL.
     * @param array  $context Optional context.
     * @return array Standard module result via ScoringEngine::build_result().
     */
    public static function analyze( string $html, string $url = '', array $context = [] ): array|\BaloaStructureAuditorSEO\Core\DTO\ModuleResult {
        $dom = self::load_dom( $html );

        $json_ld   = self::extract_json_ld( $dom );
        $microdata = self::detect_microdata( $dom );

        $has_schema = ! empty( $json_ld ) || $microdata['found'];

        $checks  = [];
        $details = [
            'has_schema'    => $has_schema,
            'json_ld_count' => count( $json_ld ),
            'microdata'     => $microdata,
            'schemas'       => [],
        ];

        // â”€â”€ Presence check â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( ! $has_schema ) {
            $checks[] = [
                'id'             => 'schema_present',
                'severity'       => 'error',
                'category'       => 'seo',
                'context'        => [ 'has_schema' => false ],
            ];
        } else {
            $checks[] = [
                'id'       => 'schema_present',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'has_schema' => true ],
            ];
        }

        // â”€â”€ JSON-LD analysis â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        foreach ( $json_ld as $raw ) {
            $data = json_decode( $raw, true );

            if ( ! $data ) {
                $checks[] = [
                    'id'             => 'schema_json_ld_valid',
                    'severity'       => 'error',
                    'category'       => 'seo',
                ];
                continue;
            }

            // Handle @graph wrapper
            $entries = isset( $data['@graph'] ) ? $data['@graph'] : [ $data ];

            foreach ( $entries as $entry ) {
                // @type can be string or array â€” normalize
                $raw_type            = $entry['@type'] ?? 'Desconocido';
                $type_display        = is_array( $raw_type ) ? implode( ', ', $raw_type ) : (string) $raw_type;
                $type_for_validation = is_array( $raw_type ) ? ( $raw_type[0] ?? 'Desconocido' ) : $raw_type;

                $validation = self::validate_schema( $entry, $type_for_validation );
                $details['schemas'][] = $validation;

                if ( ! empty( $validation['missing'] ) ) {
                    foreach ( $validation['missing'] as $field ) {
                        $checks[] = [
                            'id'             => 'schema_field_' . strtolower( $type_for_validation ) . '_' . $field,
                            'severity'       => 'warning',
                            'category'       => 'seo',
                            'context'        => [ 'type' => $type_display, 'field' => $field ],
                        ];
                    }
                } else {
                    $checks[] = [
                        'id'       => 'schema_complete_' . strtolower( $type_for_validation ),
                        'severity' => 'pass',
                        'category' => 'seo',
                        'context'  => [ 'type' => $type_display ],
                    ];
                }
            }
        }

        // â”€â”€ Microdata â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ( $microdata['found'] ) {
            $checks[] = [
                'id'       => 'schema_microdata',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'types' => $microdata['types'] ],
            ];
        }

        $checks = CheckPresenter::apply_en( $checks );
        return ScoringEngine::build_result( $checks, $details );
    }

    // â”€â”€ Extractors â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function extract_json_ld( \DOMDocument $dom ): array {
        $xpath   = new \DOMXPath( $dom );
        $scripts = $xpath->query( '//script[@type="application/ld+json"]' );
        $blocks  = [];
        foreach ( $scripts as $script ) {
            $content = trim( $script->textContent );
            if ( $content ) $blocks[] = $content;
        }
        return $blocks;
    }

    private static function detect_microdata( \DOMDocument $dom ): array {
        $xpath = new \DOMXPath( $dom );
        $nodes = $xpath->query( '//*[@itemtype]' );
        $types = [];
        foreach ( $nodes as $node ) {
            $t = $node->getAttribute( 'itemtype' );
            if ( $t ) $types[] = $t;
        }
        return [
            'found' => count( $types ) > 0,
            'types' => array_unique( $types ),
        ];
    }

    // â”€â”€ Schema validators â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function validate_schema( array $data, string $type ): array {
        $recommended = self::recommended_fields( $type );
        $missing     = [];

        foreach ( $recommended as $field ) {
            if ( empty( $data[ $field ] ) ) {
                $missing[] = $field;
            }
        }

        return [
            'type'    => $type,
            'fields'  => array_keys( $data ),
            'missing' => $missing,
        ];
    }

    private static function recommended_fields( string $type ): array {
        $map = [
            'Article'        => [ 'headline', 'author', 'datePublished', 'image' ],
            'BlogPosting'    => [ 'headline', 'author', 'datePublished', 'image' ],
            'NewsArticle'    => [ 'headline', 'author', 'datePublished', 'image' ],
            'WebPage'        => [ 'name', 'url', 'description' ],
            'WebSite'        => [ 'name', 'url' ],
            'Organization'   => [ 'name', 'url', 'logo' ],
            'Person'         => [ 'name', 'url' ],
            'Product'        => [ 'name', 'image', 'description', 'offers' ],
            'BreadcrumbList' => [ 'itemListElement' ],
            'FAQPage'        => [ 'mainEntity' ],
            'QAPage'         => [ 'mainEntity' ],
            'HowTo'          => [ 'name', 'step' ],
            'LocalBusiness'  => [ 'name', 'address', 'telephone' ],
            'Event'          => [ 'name', 'startDate', 'location' ],
            'Recipe'         => [ 'name', 'recipeIngredient', 'recipeInstructions' ],
        ];

        return $map[ $type ] ?? [ 'name' ];
    }
}
