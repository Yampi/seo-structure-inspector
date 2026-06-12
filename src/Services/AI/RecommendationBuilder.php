<?php
/**
 * BaloaStructureAuditorSEO\Services\AI\RecommendationBuilder
 *
 * Builds structured recommendation items from checks and Bookman data.
 */

namespace BaloaStructureAuditorSEO\Services\AI;

if ( ! defined( 'ABSPATH' ) ) exit;

class RecommendationBuilder {

    /**
     * Build a recommendation array from a failed check and a Bookman term.
     *
     * @param array      $check        Failed check details.
     * @param array|null $bookman_term Bookman term dictionary if matches.
     * @param string     $url          Target URL being analyzed.
     * @return array|null The structured recommendation item or null on invalid check.
     */
    public static function from_check( array $check, ?array $bookman_term, string $url ): ?array {
        $check_id = $check['id'] ?? '';
        if ( ! $check_id ) {
            return null;
        }

        // Determine role and labels
        $role_info = self::map_category_to_role( $check['module_key'] ?? '' );
        
        // Determine severity
        $check_severity = $check['severity'] ?? 'warning';
        $severity = 'warning';
        if ( $check_severity === 'error' ) {
            $severity = 'critical';
        } elseif ( $check_severity === 'info' || $check_severity === 'pass' ) {
            $severity = 'info';
        }

        // Base text fallback
        $title = $check['title'] ?? $check_id;
        $friction = $check['problem'] ?? ( $check['message'] ?? '' );
        $justification = '';
        $solution_title = __( 'Solución recomendada', 'baloa-structure-auditor-seo' );
        $solution_desc = '';
        $impact = '';
        $code_lang = 'html';
        $code_content = '';

        if ( $bookman_term ) {
            $title = $bookman_term['name'] ?? $title;
            $friction = $bookman_term['why_it_matters'] ?? $friction;
            $justification = $bookman_term['full_definition'] ?? '';
            if ( isset( $bookman_term['source_name'] ) ) {
                $justification .= ' (Referencia: ' . $bookman_term['source_name'];
                if ( ! empty( $bookman_term['source_url'] ) ) {
                    $justification .= ' - ' . $bookman_term['source_url'];
                }
                $justification .= ')';
            }
            /* translators: %s: Title of the optimization */
            $solution_title = sprintf( __( 'Optimización de %s', 'baloa-structure-auditor-seo' ), $title );
            $solution_desc = $bookman_term['recommendation'] ?? '';
            
            // Determine code language and content
            $example_good = $bookman_term['example_good'] ?? '';
            if ( $example_good ) {
                $code_content = $example_good;
                if ( stripos( $example_good, '{' ) !== false && stripos( $example_good, '}' ) !== false && stripos( $example_good, '@context' ) !== false ) {
                    $code_lang = 'json';
                } elseif ( stripos( $example_good, '<?php' ) !== false ) {
                    $code_lang = 'php';
                } elseif ( stripos( $example_good, '<script' ) !== false || stripos( $example_good, '<div' ) !== false || stripos( $example_good, '<html' ) !== false ) {
                    $code_lang = 'html';
                } else {
                    $code_lang = ( $role_info['role'] === 'ui_ux' ) ? 'css' : 'html';
                }
            }

            // Impact estimation
            $impact = self::get_impact_for_category( $role_info['role'], $title );
        } else {
            // Context-based fallback for dynamic checks
            if ( str_contains( $check_id, 'schema_' ) ) {
                $code_lang = 'json';
                $code_content = "{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"WebPage\"\n}";
            }
            $impact = __( 'Mejora la legibilidad e indexación del sitio.', 'baloa-structure-auditor-seo' );
        }

        $ai_prompt = sprintf(
            "Actúa como un experto en SEO Técnico y desarrollo WordPress. Ayúdame a solucionar el siguiente problema detectado en mi sitio web (%s):\n\n" .
            "Problema: %s - %s\n" .
            "Recomendación: %s\n" .
            "Detalles adicionales: %s\n\n" .
            "Por favor, proporcióname el código exacto optimizado y las instrucciones detalladas paso a paso para implementarlo en mi tema de WordPress o mediante un plugin personalizado.",
            $url,
            $title,
            $friction,
            $solution_desc,
            $justification
        );

        if ( ! empty( $code_content ) ) {
            $ai_prompt .= "\n\nCódigo de referencia sugerido:\n" . $code_content;
        }

        return [
            'id'             => 'rec_' . $check_id,
            'role'           => $role_info['role'],
            'role_label'     => $role_info['role_label'],
            'severity'       => $severity,
            'title'          => $title,
            'friction'       => $friction,
            'justification'  => $justification,
            'solution_title' => $solution_title,
            'solution_desc'  => $solution_desc,
            'code_lang'      => $code_lang,
            'code_content'   => $code_content,
            'impact'         => $impact,
            'ai_prompt'      => $ai_prompt,
        ];
    }

    /**
     * Map category / module key to role.
     *
     * @param string $category Module key or category.
     * @return array Role and role label.
     */
    public static function map_category_to_role( string $category ): array {
        switch ( $category ) {
            case 'html':
            case 'readability':
                return [
                    'role'       => 'ui_ux',
                    'role_label' => __( 'Consultor UI-UX', 'baloa-structure-auditor-seo' ),
                ];
            case 'keyword':
            case 'schema':
            case 'metatags':
            case 'llms':
            case 'aeo':
            case 'geo':
                return [
                    'role'       => 'seo_geo_aeo',
                    'role_label' => __( 'Especialista SEO-GEO-AEO', 'baloa-structure-auditor-seo' ),
                ];
            case 'cwv':
            case 'links':
            default:
                return [
                    'role'       => 'wp_architect',
                    'role_label' => __( 'Arquitecto WordPress', 'baloa-structure-auditor-seo' ),
                ];
        }
    }

    /**
     * Get dynamic impact descriptions.
     *
     * @param string $role  Expert role identifier.
     * @param string $title Audit check title.
     * @return string Impact text.
     */
    private static function get_impact_for_category( string $role, string $title ): string {
        switch ( $role ) {
            case 'ui_ux':
                /* translators: %s: Title of the recommendation. */
                return sprintf( __( 'Incrementa la satisfacción de lectura y reduce la frustración del usuario en dispositivos móviles al optimizar %s.', 'baloa-structure-auditor-seo' ), $title );
            case 'seo_geo_aeo':
                /* translators: %s: Title of the recommendation. */
                return sprintf( __( 'Mejora las posibilidades de que tu sitio sea citado en respuestas generativas de IA (GEO/AEO) y aumenta la visibilidad orgánica al corregir %s.', 'baloa-structure-auditor-seo' ), $title );
            case 'wp_architect':
            default:
                /* translators: %s: Title of the recommendation. */
                return sprintf( __( 'Optimiza el rendimiento técnico del servidor, acelera la velocidad de carga percibida por el usuario y elimina lagunas estructurales de %s.', 'baloa-structure-auditor-seo' ), $title );
        }
    }

    /**
     * Estimate confidence score based on the Bookman glossary match rate.
     *
     * @param int $total_recs     Total number of failed checks.
     * @param int $bookman_covered Number of checks with Bookman matches.
     * @return int Confidence score percentage.
     */
    public static function calculate_confidence( int $total_recs, int $bookman_covered ): int {
        if ( $total_recs === 0 ) {
            return 100;
        }
        $ratio = $bookman_covered / $total_recs;
        return (int) round( 80 + ( $ratio * 20 ) ); // Range [80 - 100]%
    }
}
