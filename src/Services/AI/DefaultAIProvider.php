<?php
/**
 * BaloaStructureAuditorSEO\Services\AI\DefaultAIProvider
 *
 * Dynamic AI recommendation provider that uses Bookman catalog and actual
 * analysis results to generate context-aware suggestions.
 */

namespace BaloaStructureAuditorSEO\Services\AI;

use BaloaStructureAuditorSEO\Core\Bookman;

if ( ! defined( 'ABSPATH' ) ) exit;

class DefaultAIProvider implements AIProviderInterface {

    /**
     * Get the name of this AI provider.
     *
     * @return string
     */
    public function get_name(): string {
        return 'BSA Expert Engine (Dinámico)';
    }

    /**
     * Check if this provider is configured.
     *
     * @return bool
     */
    public function is_configured(): bool {
        return true; // Always ready to serve local expert rules
    }

    /**
     * Generate recommendations based on the analyzed URL and context.
     *
     * @param string $url     The analyzed URL.
     * @param array  $context Additional context (e.g. analysis_results).
     * @return array Array of structured recommendations.
     */
    public function get_recommendations( string $url, array $context = [] ): array {
        $analysis_results = $context['analysis_results'] ?? null;

        // If no results in context, attempt to pull from recent transients
        if ( ! $analysis_results ) {
            $user_id = get_current_user_id();
            $analysis_cache_key = 'baloa_structure_auditor_seo_rep_' . $user_id . '_' . md5( $url );
            $analysis_results = get_transient( $analysis_cache_key );
        }

        if ( ! $analysis_results || ! is_array( $analysis_results ) ) {
            return $this->get_fallback_recommendations( $url );
        }

        return $this->build_dynamic_recommendations( $url, $analysis_results );
    }

    /**
     * Build recommendations dynamically from failed checks.
     *
     * @param string $url              The analyzed URL.
     * @param array  $analysis_results The localized analysis results.
     * @return array Recommendations payload.
     */
    private function build_dynamic_recommendations( string $url, array $analysis_results ): array {
        if ( ! class_exists( AnalysisContextExtractor::class ) ) {
            require_once __DIR__ . '/AnalysisContextExtractor.php';
        }
        if ( ! class_exists( RecommendationBuilder::class ) ) {
            require_once __DIR__ . '/RecommendationBuilder.php';
        }

        $failed_checks = AnalysisContextExtractor::extract_failed_checks( $analysis_results );
        $bookman_terms = Bookman::get_terms();

        $recommendations = [];
        $critical_count = 0;
        $warning_count = 0;
        $info_count = 0;
        $bookman_covered = 0;

        foreach ( $failed_checks as $check ) {
            $check_id = $check['id'] ?? '';
            $term = $bookman_terms[ $check_id ] ?? null;
            if ( $term ) {
                $bookman_covered++;
            }

            $built = RecommendationBuilder::from_check( $check, $term, $url );
            if ( $built ) {
                $recommendations[] = $built;

                if ( $built['severity'] === 'critical' ) {
                    $critical_count++;
                } elseif ( $built['severity'] === 'warning' ) {
                    $warning_count++;
                } else {
                    $info_count++;
                }
            }
        }

        // Sort by severity (critical/error first, then warning, then info)
        usort( $recommendations, function ( $a, $b ) {
            $order = [ 'critical' => 1, 'warning' => 2, 'info' => 3 ];
            $a_val = $order[ $a['severity'] ] ?? 99;
            $b_val = $order[ $b['severity'] ] ?? 99;
            return $a_val <=> $b_val;
        } );

        $ai_confidence = RecommendationBuilder::calculate_confidence( count( $failed_checks ), $bookman_covered );

        return [
            'meta' => [
                'provider'      => $this->get_name(),
                'version'       => '2.0.0',
                'analyzed_url'  => $url,
                'timestamp'     => current_time( 'mysql' ),
                'ai_confidence' => $ai_confidence,
                'stats'         => [
                    'critical' => $critical_count,
                    'warning'  => $warning_count,
                    'info'     => $info_count
                ]
            ],
            'recommendations' => $recommendations
        ];
    }

    /**
     * Fallback static recommendations when no analysis exists yet.
     *
     * @param string $url Analyzed URL.
     * @return array Fallback recommendations.
     */
    private function get_fallback_recommendations( string $url ): array {
        $parsed_url = wp_parse_url( $url );
        $host = $parsed_url['host'] ?? 'tudominio.com';
        $protocol = $parsed_url['scheme'] ?? 'https';
        $domain = $protocol . '://' . $host;

        $recs = [
            [
                'id' => 'uiux_color_contrast',
                'role' => 'ui_ux',
                'role_label' => __( 'Consultor UI-UX', 'baloa-structure-auditor-seo' ),
                'severity' => 'critical',
                'title' => __( 'Contraste de Color Inadecuado en Elementos Interactivos', 'baloa-structure-auditor-seo' ),
                'friction' => __( 'Los placeholders gris claro y las etiquetas deshabilitadas no cumplen con la relación mínima de contraste sobre fondos claros, impidiendo que usuarios con discapacidad visual naveguen correctamente.', 'baloa-structure-auditor-seo' ),
                'justification' => __( 'WCAG 2.2 (Criterio de Éxito 1.4.3). Nielsen Norman Group (NN/g) desaconseja el uso de placeholders en lugar de etiquetas porque causan sobrecarga cognitiva.', 'baloa-structure-auditor-seo' ),
                'solution_title' => __( 'Reemplazo por Etiquetas Flotantes Autocontrastables', 'baloa-structure-auditor-seo' ),
                'solution_desc' => __( 'Sustituir los campos con placeholders débiles por un sistema de etiquetas flotantes (Floating Labels) que utilicen colores con al menos 4.5:1 de contraste.', 'baloa-structure-auditor-seo' ),
                'code_lang' => 'css',
                'code_content' => ":root {\n  --color-label: #475569;\n  --color-input-bg: #ffffff;\n}\n.floating-label-group {\n  position: relative;\n  margin-bottom: 20px;\n}",
                'impact' => __( 'Garantiza la legibilidad universal del formulario, reduciendo la tasa de rebote del usuario.', 'baloa-structure-auditor-seo' )
            ],
            [
                'id' => 'seogeoaeo_json_ld',
                'role' => 'seo_geo_aeo',
                'role_label' => __( 'Especialista SEO-GEO-AEO', 'baloa-structure-auditor-seo' ),
                'severity' => 'critical',
                'title' => __( 'Ausencia de Datos Estructurados de Geolocalización (LocalBusiness)', 'baloa-structure-auditor-seo' ),
                'friction' => __( 'El sitio web no cuenta con marcado de datos estructurados de negocio local. Esto debilita severamente el posicionamiento orgánico en búsquedas locales de Google Maps y en motores de respuesta como Perplexity y ChatGPT.', 'baloa-structure-auditor-seo' ),
                'justification' => __( 'Schema.org Vocabulary (LocalBusiness / GeoCoordinates). Una inyección estructurada NAP consistente (Name, Address, Phone) permite a las APIs de IA mapear e indexar el negocio con absoluta precisión.', 'baloa-structure-auditor-seo' ),
                'solution_title' => __( 'Inyección Automatizada de Esquema Semántico LocalBusiness', 'baloa-structure-auditor-seo' ),
                'solution_desc' => __( 'Generar e inyectar un bloque estructurado en formato JSON-LD directamente en el pie de página de la URL analizada.', 'baloa-structure-auditor-seo' ),
                'code_lang' => 'json',
                'code_content' => "<script type=\"application/ld+json\">\n{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"LocalBusiness\",\n  \"name\": \"" . esc_attr( get_bloginfo('name') ) . "\",\n  \"url\": \"" . esc_url( $domain ) . "\"\n}\n</script>",
                'impact' => __( 'Asegura la indexación del negocio local en el Google Local Pack y permite que las IAs recomendadoras de geolocalización citen la dirección sin ambigüedad.', 'baloa-structure-auditor-seo' )
            ],
            [
                'id' => 'seogeoaeo_llms_txt',
                'role' => 'seo_geo_aeo',
                'role_label' => __( 'Especialista SEO-GEO-AEO', 'baloa-structure-auditor-seo' ),
                'severity' => 'warning',
                'title' => __( 'Falta de Documento de Indexación Conversacional (llms.txt)', 'baloa-structure-auditor-seo' ),
                'friction' => __( 'Los rastreadores y agentes de IA (como PerplexityBot y ClaudeBot) consumen excesivos recursos (tokens) procesando estructuras HTML completas, lo que incrementa el riesgo de que el contenido del sitio sea ignorado.', 'baloa-structure-auditor-seo' ),
                'justification' => __( 'Directrices emergentes de optimización AEO para RAG (Retrieval-Augmented Generation). El estándar del ecosistema propone la publicación de un archivo /llms.txt plano y estructurado.', 'baloa-structure-auditor-seo' ),
                'solution_title' => __( 'Creación y Exposición Dinámica de /llms.txt', 'baloa-structure-auditor-seo' ),
                'solution_desc' => __( 'Configurar una regla de reescritura en WordPress o un endpoint que renderice un archivo de texto Markdown en la raíz, resumiendo la arquitectura de contenidos.', 'baloa-structure-auditor-seo' ),
                'code_lang' => 'markdown',
                'code_content' => "# " . esc_attr( get_bloginfo('name') ) . "\n\n> " . esc_attr( get_bloginfo('description') ) . "\n\n## Información Clave del Sitio\n- **URL Base:** " . esc_url( $domain ),
                'impact' => __( 'Facilita un resumen limpio de menos de 1000 tokens para que los Answer Engines de Inteligencia Artificial entiendan el núcleo del negocio.', 'baloa-structure-auditor-seo' )
            ]
        ];

        return [
            'meta' => [
                'provider'      => $this->get_name(),
                'version'       => '2.0.0',
                'analyzed_url'  => $url,
                'timestamp'     => current_time( 'mysql' ),
                'ai_confidence' => 96,
                'stats'         => [
                    'critical' => 2,
                    'warning'  => 1,
                    'info'     => 0
                ]
            ],
            'recommendations' => $recs
        ];
    }
}
