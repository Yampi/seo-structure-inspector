<?php
/**
 * BaloaStructureAuditorSEO\Domain\Semantic\JsonLdGraph
 *
 * Domain Entity representing a collection of connected Schema.org JSON-LD nodes.
 */

declare(strict_types=1);

namespace BaloaStructureAuditorSEO\Domain\Semantic;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class JsonLdGraph {

    private array $nodes = [];

    /**
     * Constructor.
     *
     * @param array $nodes Initial set of Schema nodes.
     */
    public function __construct( array $nodes = [] ) {
        foreach ( $nodes as $node ) {
            $this->add_node( $node );
        }
    }

    /**
     * Add a node to the graph with validation.
     */
    public function add_node( array $node ): void {
        $node = $this->validate_and_sanitize_node( $node );
        $this->nodes[] = $node;
    }

    /**
     * Get all nodes in the graph.
     */
    public function get_nodes(): array {
        return $this->nodes;
    }

    /**
     * Compile the graph to a JSON-LD string.
     */
    public function compile(): string {
        if ( empty( $this->nodes ) ) {
            return '';
        }

        $graph = [
            '@context' => 'https://schema.org',
            '@graph'   => $this->nodes,
        ];

        return (string) json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
    }

    /**
     * Internal node validator.
     *
     * @param array $node Node data to validate.
     * @return array Sanitized node.
     * @throws \InvalidArgumentException if schema invariants are violated.
     */
    private function validate_and_sanitize_node( array $node ): array {
        if ( ! isset( $node['@type'] ) ) {
            throw new \InvalidArgumentException( "Cada nodo del grafo debe contener una propiedad '@type'." );
        }

        $type = $node['@type'];
        
        // AEO & Local Search constraints
        if ( $type === 'FAQPage' ) {
            if ( empty( $node['mainEntity'] ) || ! is_array( $node['mainEntity'] ) ) {
                throw new \InvalidArgumentException( "FAQPage debe contener una lista de entidades en 'mainEntity'." );
            }
        } elseif ( $type === 'LocalBusiness' || str_contains( (string) $type, 'LocalBusiness' ) ) {
            if ( empty( $node['name'] ) ) {
                throw new \InvalidArgumentException( "LocalBusiness requiere una propiedad 'name'." );
            }
        }

        return $node;
    }
}
