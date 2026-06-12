<?php
/**
 * BaloaStructureAuditorSEO\Free\Analyzers\ReadabilityAnalyzer
 *
 * Analyzes content readability using Flesch-Kincaid adapted for Spanish
 * (Fernandez-Huerta), passive voice, long sentences, long paragraphs,
 * and transition word ratio.
 *
 * Migrated to BaloaStructureAuditorSEO\Core\ScoringEngine in v0.3.0.
 *
 * @package SEO_Structure_Inspector
 * @since   0.1.0
 */

namespace BaloaStructureAuditorSEO\Free\Analyzers;

use BaloaStructureAuditorSEO\Core\ScoringEngine;
use BaloaStructureAuditorSEO\Core\BaseAnalyzer;

if ( ! defined( 'ABSPATH' ) ) exit;

class ReadabilityAnalyzer extends BaseAnalyzer {

    /**
     * Main entry point.
     *
     * @param string $html Raw HTML string.
     * @param string $url  Optional URL.
     * @param array  $context Optional context.
     * @return array Standard module result via ScoringEngine::build_result().
     */
    public static function analyze( string $html, string $url = '', array $context = [] ): array|\BaloaStructureAuditorSEO\Core\DTO\ModuleResult {
        $dom  = self::load_dom( $html );
        $text = self::extract_body_text( $dom );

        if ( empty( trim( $text ) ) ) {
            return [ 'error' => 'No se encontró contenido de texto para analizar.' ];
        }

        $sentences  = self::split_sentences( $text );
        $words      = self::split_words( $text );
        $paragraphs = self::extract_paragraphs( $dom );

        $flesch          = self::flesch_score( $text, $sentences, $words );
        $long_sentences  = self::long_sentences( $sentences );
        $passive         = self::passive_voice( $sentences );
        $long_paragraphs = self::long_paragraphs( $paragraphs );
        $transitions     = self::transition_word_ratio( $sentences );

        $checks  = [];
        $details = [
            'flesch'          => $flesch,
            'flesch_label'    => self::flesch_label( $flesch ),
            'word_count'      => count( $words ),
            'sentence_count'  => count( $sentences ),
            'paragraph_count' => count( $paragraphs ),
            'samples'         => [
                'long_sentences' => array_slice( $long_sentences, 0, 3 ),
                'passive'        => array_slice( $passive, 0, 3 ),
            ],
        ];

        // â”€â”€ 1. Flesch score â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $flesch_label = self::flesch_label( $flesch );

        if ( $flesch >= 60 ) {
            $checks[] = [
                'id'       => 'readability_flesch',
                'severity' => 'pass',
                'category' => 'seo',
                'message'  => "Legibilidad Flesch: {$flesch}/100 — {$flesch_label}",
                'context'  => [ 'score' => $flesch ],
            ];
        } elseif ( $flesch >= 40 ) {
            $checks[] = [
                'id'             => 'readability_flesch',
                'severity'       => 'warning',
                'category'       => 'seo',
                'message'        => "Flesch {$flesch}/100 — texto moderadamente difícil ({$flesch_label})",
                'recommendation' => 'Simplifica el texto: usa oraciones más cortas, evita jerga innecesaria y prefiere vocabulario común. Un texto más legible mejora el tiempo de permanencia y es más fácil de procesar por LLMs.',
                'context'        => [ 'score' => $flesch ],
            ];
        } else {
            $checks[] = [
                'id'             => 'readability_flesch',
                'severity'       => 'error',
                'category'       => 'seo',
                'message'        => "Flesch {$flesch}/100 — texto muy difícil de leer ({$flesch_label})",
                'recommendation' => 'El contenido es demasiado complejo. Divide oraciones largas, usa listas para información densa, y reduce el uso de palabras técnicas sin explicación. Los LLMs priorizan contenido claro y bien estructurado para sus respuestas.',
                'context'        => [ 'score' => $flesch ],
            ];
        }

        // â”€â”€ 2. Long sentences â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $long_pct = count( $sentences ) > 0
            ? round( count( $long_sentences ) / count( $sentences ) * 100 )
            : 0;

        if ( $long_pct <= 25 ) {
            $checks[] = [
                'id'       => 'readability_long_sentences',
                'severity' => 'pass',
                'category' => 'seo',
                'message'  => "Oraciones largas: {$long_pct}% (dentro del limite recomendado 25%)",
                'context'  => [ 'percent' => $long_pct ],
            ];
        } else {
            $checks[] = [
                'id'             => 'readability_long_sentences',
                'severity'       => 'warning',
                'category'       => 'seo',
                'message'        => "Oraciones largas (>20 palabras): {$long_pct}% — supera el 25% recomendado",
                'recommendation' => 'Divide las oraciones largas en dos. Las oraciones cortas mejoran la legibilidad, el SEO y la probabilidad de ser usadas en featured snippets y respuestas de IA.',
                'context'        => [ 'percent' => $long_pct, 'samples' => array_slice( $long_sentences, 0, 2 ) ],
            ];
        }

        // â”€â”€ 3. Passive voice â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $passive_pct = count( $sentences ) > 0
            ? round( count( $passive ) / count( $sentences ) * 100 )
            : 0;

        if ( $passive_pct <= 10 ) {
            $checks[] = [
                'id'       => 'readability_passive_voice',
                'severity' => 'pass',
                'category' => 'seo',
                'message'  => "Voz pasiva: {$passive_pct}% de oraciones (aceptable 10%)",
                'context'  => [ 'percent' => $passive_pct ],
            ];
        } else {
            $checks[] = [
                'id'             => 'readability_passive_voice',
                'severity'       => 'warning',
                'category'       => 'seo',
                'message'        => "Voz pasiva: {$passive_pct}% de oraciones — supera el 10% recomendado",
                'recommendation' => 'Reescribe las oraciones en voz activa. Ejemplo: en lugar de "El artículo fue escrito por..." usa "escribió el artículo". La voz activa es más directa y fácil de procesar para lectores y LLMs.',
                'context'        => [ 'percent' => $passive_pct, 'samples' => array_slice( $passive, 0, 2 ) ],
            ];
        }

        // â”€â”€ 4. Long paragraphs â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $long_p_count = count( $long_paragraphs );

        if ( $long_p_count === 0 ) {
            $checks[] = [
                'id'       => 'readability_long_paragraphs',
                'severity' => 'pass',
                'category' => 'seo',
                'message'  => 'Sin parrafos excesivamente largos (>150 palabras)',
            ];
        } else {
            $checks[] = [
                'id'             => 'readability_long_paragraphs',
                'severity'       => 'warning',
                'category'       => 'geo',
                'message'        => "{$long_p_count} párrafo(s) con más de 150 palabras",
                'recommendation' => 'Divide los párrafos largos en bloques más pequeños. Los LLMs extraen mejor la información de párrafos de 40-80 palabras. Los párrafos cortos también mejoran la chunkability del contenido para AI Overviews.',
                'context'        => [ 'count' => $long_p_count ],
            ];
        }

        // â”€â”€ 5. Transition words â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $trans_pct = $transitions['ratio'];

        if ( $trans_pct >= 30 ) {
            $checks[] = [
                'id'       => 'readability_transitions',
                'severity' => 'pass',
                'category' => 'seo',
                'message'  => "Palabras de transicion: {$trans_pct}% de oraciones (buen flujo de lectura)",
                'context'  => [ 'percent' => $trans_pct ],
            ];
        } else {
            $checks[] = [
                'id'             => 'readability_transitions',
                'severity'       => 'warning',
                'category'       => 'seo',
                'message'        => "Palabras de transición: {$trans_pct}% de oraciones — por debajo del 30% recomendado",
                'recommendation' => 'Agrega conectores: "sin embargo", "además", "por lo tanto", "en consecuencia", "por ejemplo". Mejoran la cohesión del texto y facilitan la lectura secuencial por parte de los LLMs.',
                'context'        => [ 'percent' => $trans_pct ],
            ];
        }

        return ScoringEngine::build_result( $checks, $details );
    }

    // â”€â”€ Text extraction â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function extract_body_text( \DOMDocument $dom ): string {
        $remove_tags = [ 'script', 'style', 'nav', 'header', 'footer', 'aside' ];
        foreach ( $remove_tags as $tag ) {
            $nodes     = $dom->getElementsByTagName( $tag );
            $to_remove = [];
            foreach ( $nodes as $node ) $to_remove[] = $node;
            foreach ( $to_remove as $node ) $node->parentNode?->removeChild( $node );
        }
        $body = $dom->getElementsByTagName( 'body' )->item(0);
        $text = $body ? $body->textContent : $dom->textContent;
        return preg_replace( '/\s+/', ' ', trim( $text ) );
    }

    private static function extract_paragraphs( \DOMDocument $dom ): array {
        $nodes      = $dom->getElementsByTagName( 'p' );
        $paragraphs = [];
        foreach ( $nodes as $node ) {
            $t = trim( $node->textContent );
            if ( strlen( $t ) > 20 ) $paragraphs[] = $t;
        }
        return $paragraphs;
    }

    // â”€â”€ Text helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function split_sentences( string $text ): array {
        $sentences = preg_split( '/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );
        return array_values( array_filter( $sentences, fn( $s ) => str_word_count( $s ) > 2 ) );
    }

    private static function split_words( string $text ): array {
        preg_match_all( '/\b\w+\b/u', mb_strtolower( $text ), $matches );
        return $matches[0];
    }

    private static function count_syllables( string $word ): int {
        $word   = mb_strtolower( $word );
        $vowels = preg_match_all( '/[aeiouáéíóú]/u', $word );
        return max( 1, (int) $vowels );
    }

    // â”€â”€ Flesch (Fernandez-Huerta for Spanish) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function flesch_score( string $text, array $sentences, array $words ): int {
        $word_count     = count( $words );
        $sentence_count = count( $sentences );

        if ( $word_count === 0 || $sentence_count === 0 ) return 0;

        $syllable_count = 0;
        foreach ( $words as $word ) {
            $syllable_count += self::count_syllables( $word );
        }

        $asl   = $word_count / $sentence_count;
        $asw   = $syllable_count / $word_count;
        $score = 206.84 - ( 60.1 * $asw ) - ( 1.02 * $asl );

        return (int) max( 0, min( 100, round( $score ) ) );
    }

    private static function flesch_label( int $score ): string {
        return match( true ) {
            $score >= 90 => 'Muy fácil',
            $score >= 80 => 'Fácil',
            $score >= 70 => 'Bastante fácil',
            $score >= 60 => 'Normal',
            $score >= 50 => 'Bastante difícil',
            $score >= 30 => 'Difícil',
            default      => 'Muy difícil',
        };
    }

    // â”€â”€ Analyzers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function long_sentences( array $sentences ): array {
        return array_values( array_filter( $sentences, fn( $s ) => str_word_count( $s ) > 20 ) );
    }

    private static function passive_voice( array $sentences ): array {
        $pattern = '/\b(fue|fueron|es|son|era|eran|sera|seran|ha sido|han sido|was|were|is|are|been|being|be)\s+\w+(ado|ido|ada|ida|ed|en)\b/iu';
        return array_values( array_filter( $sentences, fn( $s ) => preg_match( $pattern, $s ) ) );
    }

    private static function long_paragraphs( array $paragraphs ): array {
        return array_values( array_filter( $paragraphs, fn( $p ) => str_word_count( $p ) > 150 ) );
    }

    private static function transition_word_ratio( array $sentences ): array {
        $transitions = [
            'sin embargo','ademas','por lo tanto','no obstante','en consecuencia',
            'asimismo','por otro lado','en cambio','de hecho','es decir',
            'por ejemplo','en primer lugar','en segundo lugar','finalmente',
            'en conclusion','a pesar de','aunque','debido a','por eso',
            'asi que','entonces','tambien','incluso','mientras que',
            'however','furthermore','therefore','nevertheless','consequently',
            'moreover','on the other hand','in contrast','in fact','that is',
            'for example','first','second','finally','in conclusion',
            'despite','although','because','so','thus','also','even','whereas',
        ];

        $count = 0;
        foreach ( $sentences as $sentence ) {
            $lower = mb_strtolower( $sentence );
            foreach ( $transitions as $t ) {
                if ( str_contains( $lower, $t ) ) { $count++; break; }
            }
        }

        $total = count( $sentences );
        $ratio = $total > 0 ? (int) round( $count / $total * 100 ) : 0;

        return [ 'count' => $count, 'ratio' => $ratio ];
    }
}
