<?php
/**
 * SEOSI\Analyzers\Links\CanonicalCheck
 * 
 * Checks canonical URL presence and correctness.
 */

namespace SEOSI\Analyzers\Links;

if ( ! defined( 'ABSPATH' ) ) exit;

class CanonicalCheck {

    /**
     * Build check array for canonical URL.
     *
     * @param \DOMXPath $xpath DOMXPath instance.
     * @param string $page_url Current page URL.
     * @return array Check array.
     */
    public static function build_check( \DOMXPath $xpath, string $page_url ): array {
        $canonical = trim( $xpath->query( '//link[@rel="canonical"]/@href' )->item(0)?->nodeValue ?? '' );

        if ( ! $canonical ) {
            return [
                'id'             => 'links_canonical',
                'severity'       => 'warning',
                'category'       => 'seo',
                'message'        => 'URL canonica ausente',
                'recommendation' => 'Agrega un canonical para evitar problemas de contenido duplicado. Sin el, Google puede indexar versiones incorrectas de la URL (con/sin trailing slash, con parametros, etc.).',
                'context'        => [ 'value' => $canonical ],
            ];
        } else {
            $canon_base = rtrim( $canonical, '/' );
            $page_base  = rtrim( $page_url, '/' );
            $self_ref   = $canon_base === $page_base;

            if ( $self_ref ) {
                return [
                    'id'       => 'links_canonical',
                    'severity' => 'pass',
                    'category' => 'seo',
                    'message'  => "Canonical correcto y auto-referencial: {$canonical}",
                    'context'  => [ 'value' => $canonical ],
                ];
            } else {
                return [
                    'id'             => 'links_canonical',
                    'severity'       => 'warning',
                    'category'       => 'seo',
                    'message'        => "Canonical apunta a URL diferente: {$canonical}",
                    'recommendation' => 'El canonical apunta a una URL diferente a la pagina analizada. Verifica si es intencional. Si no lo es, corrígelo para apuntar a la URL actual.',
                    'context'        => [ 'canonical' => $canonical, 'page' => $page_url ],
                ];
            }
        }
    }
}
