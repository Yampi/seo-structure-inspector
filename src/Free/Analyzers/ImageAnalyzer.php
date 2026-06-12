<?php
declare(strict_types=1);

/**
 * BaloaStructureAuditorSEO\Free\Analyzers\ImageAnalyzer
 *
 * Detailed SEO image analyzer.
 * Checks for missing alt text, empty alt text, generic filenames, lazy loading, and WebP format.
 *
 * @package SEO_Structure_Inspector
 * @since   1.0.0
 */

namespace BaloaStructureAuditorSEO\Free\Analyzers;

use BaloaStructureAuditorSEO\Core\ScoringEngine;
use BaloaStructureAuditorSEO\Core\BaseAnalyzer;
use BaloaStructureAuditorSEO\Core\DTO\ModuleResult;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ImageAnalyzer extends BaseAnalyzer {

    /**
     * Main entry point.
     *
     * @param string $html Raw HTML content.
     * @param string $url  Optional URL.
     * @param array  $context Optional context.
     * @return array|ModuleResult
     */
    public static function analyze( string $html, string $url = '', array $context = [] ): array|ModuleResult {
        $dom   = self::load_dom( $html );
        $all           = [];
        $no_alt        = [];
        $empty_alt     = [];
        $generic_names = [];
        $no_lazy       = [];
        $non_webp      = [];

        $imgs = $dom->getElementsByTagName( 'img' );
        $total = $imgs->length;

        foreach ( $imgs as $img ) {
            $src = trim( $img->getAttribute( 'src' ) );
            if ( ! $src ) {
                continue;
            }

            $all[] = $src;

            // 1. Alt attribute checks
            if ( ! $img->hasAttribute( 'alt' ) ) {
                $no_alt[] = $src;
            } elseif ( trim( $img->getAttribute( 'alt' ) ) === '' ) {
                $empty_alt[] = $src;
            }

            // 2. Generic filename check
            $filename = basename( $src );
            if ( self::is_generic_filename( $filename ) ) {
                $generic_names[] = $src;
            }

            // 3. Lazy loading check
            if ( ! $img->hasAttribute( 'loading' ) || strtolower( trim( $img->getAttribute( 'loading' ) ) ) !== 'lazy' ) {
                $no_lazy[] = $src;
            }

            // 4. WebP / modern formats check
            $ext = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
            if ( in_array( $ext, [ 'jpg', 'jpeg', 'png', 'gif' ], true ) ) {
                $non_webp[] = $src;
            }
        }

        $checks = [];

        // Check 1: Images presence
        if ( $total === 0 ) {
            $checks[] = [
                'id'             => 'images_present',
                'severity'       => 'warning',
                'category'       => 'images',
                'message'        => __( 'No se detectaron imágenes en la página', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Asegúrate de agregar imágenes descriptivas para mejorar la experiencia de usuario y capturar tráfico orgánico.', 'baloa-structure-auditor-seo' ),
            ];
        } else {
            // Check 2: Missing Alt
            $missing_alt_count = count( $no_alt );
            if ( $missing_alt_count > 0 ) {
                $checks[] = [
                    'id'             => 'images_missing_alt',
                    'severity'       => 'error',
                    'category'       => 'images',
                    /* translators: %d: number of images without alt attribute */
                    'message'        => sprintf( _n( '%d imagen sin atributo alt', '%d imágenes sin atributo alt', $missing_alt_count, 'baloa-structure-auditor-seo' ), $missing_alt_count ),
                    'recommendation' => __( 'Agrega texto alternativo (alt) descriptivo a todas las imágenes importantes para accesibilidad y SEO.', 'baloa-structure-auditor-seo' ),
                    'context'        => [ 'count' => $missing_alt_count, 'samples' => array_slice( $no_alt, 0, 3 ) ],
                ];
            }

            // Check 3: Empty Alt
            $empty_alt_count = count( $empty_alt );
            if ( $empty_alt_count > 0 ) {
                $checks[] = [
                    'id'             => 'images_empty_alt',
                    'severity'       => 'warning',
                    'category'       => 'images',
                    /* translators: %d: number of images with empty alt attribute */
                    'message'        => sprintf( _n( '%d imagen con alt vacío', '%d imágenes con alt vacío', $empty_alt_count, 'baloa-structure-auditor-seo' ), $empty_alt_count ),
                    'recommendation' => __( 'El alt vacío (alt="") es correcto solo si la imagen es decorativa. Si aporta contexto, añade texto descriptivo.', 'baloa-structure-auditor-seo' ),
                    'context'        => [ 'count' => $empty_alt_count, 'samples' => array_slice( $empty_alt, 0, 3 ) ],
                ];
            }

            // Check 4: Generic filename
            $generic_count = count( $generic_names );
            if ( $generic_count > 0 ) {
                $checks[] = [
                    'id'             => 'images_generic_filename',
                    'severity'       => 'warning',
                    'category'       => 'images',
                    /* translators: %d: number of images with generic filename */
                    'message'        => sprintf( _n( '%d imagen con nombre de archivo genérico', '%d imágenes con nombre de archivo genérico', $generic_count, 'baloa-structure-auditor-seo' ), $generic_count ),
                    'recommendation' => __( 'Usa nombres de archivo descriptivos con palabras clave antes de subir las imágenes.', 'baloa-structure-auditor-seo' ),
                    'context'        => [ 'count' => $generic_count, 'samples' => array_slice( $generic_names, 0, 3 ) ],
                ];
            }

            // Check 5: Missing lazy loading
            $no_lazy_count = count( $no_lazy );
            if ( $no_lazy_count > 0 ) {
                $checks[] = [
                    'id'             => 'images_missing_lazy_loading',
                    'severity'       => 'warning',
                    'category'       => 'images',
                    /* translators: %d: number of images missing lazy loading */
                    'message'        => sprintf( _n( '%d imagen sin carga diferida (lazy loading)', '%d imágenes sin carga diferida (lazy loading)', $no_lazy_count, 'baloa-structure-auditor-seo' ), $no_lazy_count ),
                    'recommendation' => __( 'Agrega el atributo loading="lazy" a las imágenes debajo del pliegue inicial para mejorar la velocidad.', 'baloa-structure-auditor-seo' ),
                    'context'        => [ 'count' => $no_lazy_count, 'samples' => array_slice( $no_lazy, 0, 3 ) ],
                ];
            }

            // Check 6: Non-WebP format
            $non_webp_count = count( $non_webp );
            if ( $non_webp_count > 0 ) {
                $checks[] = [
                    'id'             => 'images_webp_format',
                    'severity'       => 'warning',
                    'category'       => 'images',
                    /* translators: %d: number of images not in WebP/AVIF format */
                    'message'        => sprintf( _n( '%d imagen en formato tradicional (no WebP/AVIF)', '%d imágenes en formato tradicional (no WebP/AVIF)', $non_webp_count, 'baloa-structure-auditor-seo' ), $non_webp_count ),
                    'recommendation' => __( 'Convierte tus imágenes a WebP o AVIF para reducir el peso de carga sin perder calidad visual.', 'baloa-structure-auditor-seo' ),
                    'context'        => [ 'count' => $non_webp_count, 'samples' => array_slice( $non_webp, 0, 3 ) ],
                ];
            }

            // Check 7: OK check
            $ok_count = $total - $missing_alt_count - $empty_alt_count;
            if ( $ok_count > 0 ) {
                $checks[] = [
                    'id'       => 'images_with_alt',
                    'severity' => 'pass',
                    'category' => 'images',
                    /* translators: 1: number of images with alt, 2: total images count */
                    'message'  => sprintf( __( '%1$d/%2$d imágenes con alt correcto', 'baloa-structure-auditor-seo' ), $ok_count, $total ),
                    'context'  => [ 'ok' => $ok_count, 'total' => $total ],
                ];
            }
        }

        $details = [
            'total'         => $total,
            'noalt_count'   => count( $no_alt ),
            'emptyalt_count'=> count( $empty_alt ),
            'generic_count' => count( $generic_names ),
            'nolazy_count'  => count( $no_lazy ),
            'nonwebp_count' => count( $non_webp ),
            'samples'       => array_slice( $all, 0, 5 )
        ];

        return ScoringEngine::build_result( $checks, $details );
    }

    /**
     * Check if a filename is generic.
     *
     * @param string $filename The basename of the file.
     * @return bool
     */
    private static function is_generic_filename( string $filename ): bool {
        $name = pathinfo( $filename, PATHINFO_FILENAME );
        return (bool) preg_match( '/^(image|img|pic|photo|dsc|screenshot|capture|pantalla)[-_]?\d+$/i', $name );
    }
}
