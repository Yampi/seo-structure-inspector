<?php
/**
 * SEOSI\Analyzers\Links\ImageAltCheck
 * 
 * Checks image alt attributes.
 */

namespace SEOSI\Analyzers\Links;

if ( ! defined( 'ABSPATH' ) ) exit;

class ImageAltCheck {

    /**
     * Analyze images and return alt check results.
     *
     * @param \DOMDocument $dom DOMDocument instance.
     * @return array Array with 'all', 'noalt', 'emptyalt' arrays.
     */
    public static function analyze( \DOMDocument $dom ): array {
        $all      = [];
        $noalt    = [];
        $emptyalt = [];

        foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
            $src = $img->getAttribute( 'src' );
            if ( ! $src ) continue;

            $all[] = $src;

            if ( ! $img->hasAttribute( 'alt' ) ) {
                $noalt[] = $src;
            } elseif ( trim( $img->getAttribute( 'alt' ) ) === '' ) {
                $emptyalt[] = $src;
            }
        }

        return compact( 'all', 'noalt', 'emptyalt' );
    }

    /**
     * Build check array for images presence.
     *
     * @param int $total Total number of images.
     * @return array Check array.
     */
    public static function build_presence_check( int $total ): ?array {
        if ( $total !== 0 ) {
            return null;
        }

        return [
            'id'             => 'images_present',
            'severity'       => 'warning',
            'category'       => 'seo',
            'message'        => 'Sin imagenes detectadas en la pagina',
            'recommendation' => 'Agrega imagenes relevantes con texto alt descriptivo. Las imagenes mejoran el engagement, el tiempo de permanencia y son oportunidades adicionales de trafico via Google Imagenes.',
        ];
    }

    /**
     * Build check array for missing alt attributes.
     *
     * @param int $count Number of images without alt.
     * @param array $samples Sample images without alt.
     * @return array Check array.
     */
    public static function build_missing_alt_check( int $count, array $samples = [] ): ?array {
        if ( $count <= 0 ) {
            return null;
        }

        return [
            'id'             => 'images_missing_alt',
            'severity'       => 'error',
            'category'       => 'seo',
            'message'        => "{$count} imagen(es) sin atributo alt",
            'recommendation' => 'Agrega alt descriptivo a todas las imagenes. El alt es fundamental para: accesibilidad (lectores de pantalla), SEO de imagenes (Google Imagenes) y para que los LLMs entiendan el contenido visual.',
            'context'        => [ 'count' => $count, 'samples' => array_slice( $samples, 0, 3 ) ],
        ];
    }

    /**
     * Build check array for empty alt attributes.
     *
     * @param int $count Number of images with empty alt.
     * @return array Check array.
     */
    public static function build_empty_alt_check( int $count ): ?array {
        if ( $count <= 0 ) {
            return null;
        }

        return [
            'id'             => 'images_empty_alt',
            'severity'       => 'warning',
            'category'       => 'seo',
            'message'        => "{$count} imagen(es) con alt vacio",
            'recommendation' => 'El alt vacio (alt="") es correcto solo para imagenes puramente decorativas. Si la imagen aporta informacion o contexto, agrega un alt descriptivo.',
            'context'        => [ 'count' => $count ],
        ];
    }

    /**
     * Build check array for images with correct alt.
     *
     * @param int $ok Number of images with correct alt.
     * @param int $total Total number of images.
     * @return array Check array.
     */
    public static function build_with_alt_check( int $ok, int $total ): ?array {
        if ( $ok <= 0 || $total <= 0 ) {
            return null;
        }

        return [
            'id'       => 'images_with_alt',
            'severity' => 'pass',
            'category' => 'seo',
            'message'  => "{$ok}/{$total} imagenes con alt correcto",
            'context'  => [ 'ok' => $ok, 'total' => $total ],
        ];
    }
}
