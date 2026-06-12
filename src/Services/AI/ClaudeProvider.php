<?php
/**
 * BaloaStructureAuditorSEO\Services\AI\ClaudeProvider
 *
 * Anthropic Claude 3.5 Sonnet provider for AI recommendations.
 */

namespace BaloaStructureAuditorSEO\Services\AI;

if ( ! defined( 'ABSPATH' ) ) exit;

class ClaudeProvider implements AIProviderInterface {

    /**
     * Get the name of this AI provider.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Anthropic Claude 3.5 Sonnet';
    }

    /**
     * Check if this provider is configured (e.g. has API keys).
     *
     * @return bool
     */
    public function is_configured(): bool {
        $options = get_option( 'baloa_structure_auditor_seo_options', [] );
        return ! empty( $options['claude_api_key'] );
    }

    /**
     * Generate recommendations based on the analyzed URL and context.
     *
     * @param string $url     The analyzed URL.
     * @param array  $context Additional context from the site analysis.
     * @return array Array of recommendations.
     */
    public function get_recommendations( string $url, array $context = [] ): array {
        if ( ! $this->is_configured() ) {
            return [];
        }

        $options = get_option( 'baloa_structure_auditor_seo_options', [] );
        $api_key = $options['claude_api_key'];

        // 1. Gather audit context
        $audit_summary = $this->get_audit_summary( $url, $context );

        // 2. Build instructions and prompts
        $system_prompt = $this->get_system_instructions();
        $user_prompt = "Audita y genera recomendaciones detalladas para la URL: {$url}\n\nResumen del Diagnóstico Local:\n{$audit_summary}";

        // 3. Make HTTP request
        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
            'timeout' => 25,
            'headers' => [
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'body' => json_encode([
                'model'      => 'claude-3-5-sonnet-20241022',
                'max_tokens' => 4000,
                'system'     => $system_prompt,
                'messages'   => [
                    [ 'role' => 'user', 'content' => $user_prompt ]
                ],
                'temperature'=> 0.2
            ])
        ]);

        if ( is_wp_error( $response ) ) {
            \BaloaStructureAuditorSEO\Core\Logger::error( 'Claude API request failed', [ 'error' => $response->get_error_message() ] );
            throw new \Exception( esc_html( __( 'No se pudo conectar con la API de Anthropic Claude.', 'baloa-structure-auditor-seo' ) ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['error'] ) ) {
            \BaloaStructureAuditorSEO\Core\Logger::error( 'Claude API returned an error', [ 'error' => $data['error'] ] );
            throw new \Exception( esc_html( $data['error']['message'] ?? __( 'Error devuelto por la API de Anthropic Claude.', 'baloa-structure-auditor-seo' ) ) );
        }

        $content = $data['content'][0]['text'] ?? '';
        $result = json_decode( trim( $content ), true );

        if ( ! is_array( $result ) || ! isset( $result['recommendations'] ) ) {
            \BaloaStructureAuditorSEO\Core\Logger::error( 'Claude API returned invalid JSON schema', [ 'raw_content' => $content ] );
            throw new \Exception( esc_html( __( 'La respuesta de la IA no cumple con el formato estructurado esperado.', 'baloa-structure-auditor-seo' ) ) );
        }

        // Add meta information
        $result['meta'] = [
            'provider'      => $this->get_name(),
            'version'       => '1.0.0',
            'analyzed_url'  => $url,
            'timestamp'     => current_time( 'mysql' ),
            'ai_confidence' => 99
        ];

        return $result;
    }

    /**
     * Build standard guidelines and schema validation for the system instructions.
     */
    private function get_system_instructions(): string {
        return "Eres un grupo de 3 consultores expertos que auditan un sitio web:
1. Consultor UI-UX (aplica WCAG 2.2 AA, Material 3, footprints de clic mínimos de 48px).
2. Especialista SEO-GEO-AEO (aplica Schema.org, llms.txt, respuestas estructuradas AEO, optimización RAG).
3. Arquitecto de WordPress (aplica Transients API, escape de salidas con esc_html/esc_url, hooks de extensibilidad).

Analiza el diagnóstico del sitio y genera exactamente 9 recomendaciones (3 por cada experto) devueltas estrictamente en formato JSON bajo el siguiente esquema:
{
  \"recommendations\": [
    {
      \"id\": \"slug_unico_por_recomendacion\",
      \"role\": \"ui_ux | seo_geo_aeo | wp_architect\",
      \"role_label\": \"Consultor UI-UX | Especialista SEO-GEO-AEO | Arquitecto de WordPress\",
      \"severity\": \"critical | warning\",
      \"title\": \"Título corto y directo de la recomendación\",
      \"friction\": \"Explicación detallada de la fricción que causa este problema\",
      \"justification\": \"Qué directriz o estándar respalda la recomendación (ej. WCAG 2.2, HIG, Schema.org)\",
      \"solution_title\": \"Título de la solución propuesta\",
      \"solution_desc\": \"Descripción paso a paso de cómo implementar la solución\",
      \"code_lang\": \"css | php | json | html | markdown\",
      \"code_content\": \"Código exacto o fragmento sugerido para solucionar el problema\",
      \"impact\": \"Métricas de impacto esperadas al aplicar este cambio\"
    }
  ]
}

Responde ÚNICAMENTE con el objeto JSON válido sin texto explicativo adicional.";
    }

    /**
     * Fetch local audit results summary.
     */
    private function get_audit_summary( string $url, array $context = [] ): string {
        $results = $context['analysis_results'] ?? null;

        if ( ! is_array( $results ) ) {
            $user_id = get_current_user_id();
            $transient_key = 'baloa_structure_auditor_seo_rep_' . $user_id . '_' . md5( $url );
            $results = get_transient( $transient_key );
        }

        if ( ! is_array( $results ) ) {
            return "No hay diagnóstico local detallado disponible para esta URL. Proporciona recomendaciones genéricas basadas en las mejores prácticas de los 3 expertos.";
        }

        $summary = "HTML Score: " . ($results['html']['score'] ?? 'N/A') . "/100\n";
        $summary .= "Keyword Score: " . ($results['keyword']['score'] ?? 'N/A') . "/100\n";
        $summary .= "Schema Score: " . ($results['schema']['score'] ?? 'N/A') . "/100\n";
        $summary .= "Readability Score: " . ($results['readability']['score'] ?? 'N/A') . "/100\n";
        $summary .= "Meta Tags Score: " . ($results['metatags']['score'] ?? 'N/A') . "/100\n\n";

        $summary .= "Problemas Críticos Identificados:\n";
        foreach ( ['html', 'keyword', 'schema', 'readability', 'metatags', 'llms', 'aeo', 'links'] as $mod ) {
            if ( isset( $results[$mod]['issues'] ) && is_array( $results[$mod]['issues'] ) ) {
                foreach ( $results[$mod]['issues'] as $issue ) {
                    $summary .= "- [{$mod}] {$issue}\n";
                }
            }
        }

        $summary .= "\nAdvertencias Identificadas:\n";
        foreach ( ['html', 'keyword', 'schema', 'readability', 'metatags', 'llms', 'aeo', 'links'] as $mod ) {
            if ( isset( $results[$mod]['warnings'] ) && is_array( $results[$mod]['warnings'] ) ) {
                foreach ( $results[$mod]['warnings'] as $warn ) {
                    $summary .= "- [{$mod}] {$warn}\n";
                }
            }
        }

        return $summary;
    }
}
