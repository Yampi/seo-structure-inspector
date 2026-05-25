<?php
/**
 * SEOSI\Analyzers\Links\ExternalLinksCheck
 * 
 * Checks external links count and links without anchor text.
 */

namespace SEOSI\Analyzers\Links;

if ( ! defined( 'ABSPATH' ) ) exit;

class ExternalLinksCheck {

    /**
     * Build check for external links count.
     *
     * @param int $count Number of external links.
     * @return array Check array.
     */
    public static function build_count_check( int $count ): array {
        if ( $count === 0 ) {
            return [
                'id'             => 'links_external_count',
                'severity'       => 'warning',
                'category'       => 'seo',
                'message'        => 'Sin enlaces externos',
                'recommendation' => 'Agrega enlaces a fuentes externas de autoridad (estudios, documentacion oficial, sitios de referencia). Los links salientes a fuentes relevantes son una senal de calidad para Google y mejoran la credibilidad del contenido para los LLMs.',
            ];
        } else {
            return [
                'id'       => 'links_external_count',
                'severity' => 'pass',
                'category' => 'seo',
                'message'  => "{$count} enlace(s) externo(s) detectado(s)",
                'context'  => [ 'count' => $count ],
            ];
        }
    }

    /**
     * Build check for links without anchor text.
     *
     * @param int $count Number of links without anchor text.
     * @param array $samples Sample links without anchor text.
     * @return array Check array.
     */
    public static function build_no_anchor_check( int $count, array $samples = [] ): array {
        if ( $count > 0 ) {
            return [
                'id'             => 'links_no_anchor_text',
                'severity'       => 'error',
                'category'       => 'seo',
                'message'        => "{$count} enlace(s) sin texto ancla",
                'recommendation' => 'Agrega texto descriptivo a todos los enlaces. El anchor text es una senal de relevancia para Google y es fundamental para accesibilidad. Evita textos genericos como "click aqui" o "ver mas".',
                'context'        => [ 'count' => $count, 'samples' => array_slice( $samples, 0, 3 ) ],
            ];
        } else {
            return [
                'id'       => 'links_no_anchor_text',
                'severity' => 'pass',
                'category' => 'seo',
                'message'  => 'Todos los enlaces tienen texto ancla',
            ];
        }
    }
}
