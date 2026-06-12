<?php
/**
 * BaloaStructureAuditorSEO\Analyzers\Links\InternalLinksCheck
 * 
 * Checks internal links count and nofollow attributes.
 */

namespace BaloaStructureAuditorSEO\Analyzers\Links;

if ( ! defined( 'ABSPATH' ) ) exit;

class InternalLinksCheck {

    /**
     * Build check for internal links count.
     *
     * @param int $count Number of internal links.
     * @return array Check array.
     */
    public static function build_count_check( int $count ): array {
        if ( $count === 0 ) {
            return [
                'id'             => 'links_internal_count',
                'severity'       => 'error',
                'category'       => 'seo',
                'message'        => 'Sin enlaces internos',
                'recommendation' => 'Agrega enlaces internos a otras paginas relevantes de tu sitio. Son esenciales para distribuir PageRank, ayudar a los crawlers a descubrir contenido y mejorar la navegacion del usuario.',
            ];
        } elseif ( $count < 3 ) {
            return [
                'id'             => 'links_internal_count',
                'severity'       => 'warning',
                'category'       => 'seo',
                'message'        => "Solo {$count} enlace(s) interno(s) â€” recomendado minimo 3",
                'recommendation' => 'Agrega mas enlaces internos. Un minimo de 3-5 por pagina ayuda a distribuir autoridad y mejora la indexacion por parte de los crawlers.',
                'context'        => [ 'count' => $count ],
            ];
        } else {
            return [
                'id'       => 'links_internal_count',
                'severity' => 'pass',
                'category' => 'seo',
                'message'  => "{$count} enlaces internos detectados",
                'context'  => [ 'count' => $count ],
            ];
        }
    }

    /**
     * Build check for internal links with nofollow.
     *
     * @param int $count Number of internal links with nofollow.
     * @return array Check array.
     */
    public static function build_nofollow_check( int $count ): ?array {
        if ( $count <= 0 ) {
            return null;
        }

        return [
            'id'             => 'links_internal_nofollow',
            'severity'       => 'warning',
            'category'       => 'seo',
            'message'        => "{$count} enlace(s) interno(s) con rel=\"nofollow\"",
            'recommendation' => 'Los enlaces internos con nofollow bloquean la transferencia de PageRank entre tus propias paginas. Elimina nofollow de los enlaces internos salvo que tengas una razon tecnica especifica.',
            'context'        => [ 'count' => $count ],
        ];
    }
}
