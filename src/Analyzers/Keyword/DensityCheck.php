<?php
/**
 * BaloaStructureAuditorSEO\Analyzers\Keyword\DensityCheck
 * 
 * Checks keyword density in the body text.
 */

namespace BaloaStructureAuditorSEO\Analyzers\Keyword;

if ( ! defined( 'ABSPATH' ) ) exit;

class DensityCheck {

    /**
     * Calculate keyword density as a percentage.
     *
     * @param string $text Body text.
     * @param string $keyword Target keyword.
     * @return float Density percentage.
     */
    public static function calculate( string $text, string $keyword ): float {
        $word_count  = str_word_count( $text );
        if ( $word_count === 0 ) return 0.0;
        $kw_words    = str_word_count( $keyword );
        $occurrences = substr_count( $text, $keyword );
        return ( $occurrences * $kw_words / $word_count ) * 100;
    }

    /**
     * Build check array for keyword density.
     *
     * @param float $density Density percentage.
     * @return array Check array.
     */
    public static function build_check( float $density ): array {
        $density_context = number_format( $density, 2 ) . '%';

        if ( $density > 3.0 ) {
            return [
                'id'             => 'kw_density',
                'severity'       => 'warning',
                'category'       => 'seo',
                'message'        => "Densidad de keyword alta: {$density_context} (máx. recomendado: 3%)",
                'recommendation' => 'Reduce la frecuencia de la keyword. La over-optimización puede ser penalizada por Google (keyword stuffing). Usa variaciones semánticas.',
                'context'        => [ 'density' => $density ],
            ];
        } elseif ( $density < 0.5 ) {
            return [
                'id'             => 'kw_density',
                'severity'       => 'warning',
                'category'       => 'seo',
                'message'        => "Densidad de keyword baja: {$density_context} (mín. recomendado: 0.5%)",
                'recommendation' => 'Menciona la keyword con más frecuencia en el cuerpo del texto. La densidad baja puede indicar que el contenido no está suficientemente enfocado.',
                'context'        => [ 'density' => $density ],
            ];
        } else {
            return [
                'id'       => 'kw_density',
                'severity' => 'pass',
                'category' => 'seo',
                'message'  => "Densidad de keyword correcta: {$density_context} (rango óptimo 0.5%-3%)",
                'context'  => [ 'density' => $density ],
            ];
        }
    }
}
