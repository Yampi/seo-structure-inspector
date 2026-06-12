<?php
/**
 * BaloaStructureAuditorSEO\Analyzers\Keyword\PositionCheck
 * 
 * Checks keyword presence in strategic positions (title, meta, h1, h2, first paragraph, URL).
 */

namespace BaloaStructureAuditorSEO\Analyzers\Keyword;

if ( ! defined( 'ABSPATH' ) ) exit;

class PositionCheck {

    /**
     * Check keyword in <title>.
     *
     * @param \DOMXPath $xpath DOMXPath instance.
     * @param string $keyword Target keyword (lowercase).
     * @return array Check array.
     */
    public static function check_title( \DOMXPath $xpath, string $keyword ): array {
        $title_node = $xpath->query( '//title' )->item(0);
        $title_text = $title_node ? mb_strtolower( trim( $title_node->textContent ) ) : '';
        $title_val  = $title_node ? trim( $title_node->textContent ) : '(no encontrado)';

        return self::kw_check(
            'kw_title',
            str_contains( $title_text, $keyword ),
            'Keyword en <title>',
            'Incluye la keyword en el <title>. Es la señal de relevancia más fuerte para Google y para los LLMs que indexan la página.',
            [ 'value' => $title_val ]
        );
    }

    /**
     * Check keyword in meta description.
     *
     * @param \DOMXPath $xpath DOMXPath instance.
     * @param string $keyword Target keyword (lowercase).
     * @return array Check array.
     */
    public static function check_meta_description( \DOMXPath $xpath, string $keyword ): array {
        $meta      = $xpath->query( '//meta[@name="description"]/@content' )->item(0);
        $meta_text = $meta ? mb_strtolower( $meta->nodeValue ) : '';
        $meta_val  = $meta ? $meta->nodeValue : '(no encontrada)';

        return self::kw_check(
            'kw_meta_description',
            $meta && str_contains( $meta_text, $keyword ),
            'Keyword en meta description',
            'Incluye la keyword en la meta description. Mejora el CTR en SERPs y refuerza la relevancia temática.',
            [ 'value' => $meta_val ]
        );
    }

    /**
     * Check keyword in first <h1>.
     *
     * @param \DOMDocument $dom DOMDocument instance.
     * @param string $keyword Target keyword (lowercase).
     * @return array Check array.
     */
    public static function check_h1( \DOMDocument $dom, string $keyword ): array {
        $h1      = $dom->getElementsByTagName( 'h1' )->item(0);
        $h1_text = $h1 ? mb_strtolower( trim( $h1->textContent ) ) : '';
        $h1_val  = $h1 ? trim( $h1->textContent ) : '(no encontrado)';

        return self::kw_check(
            'kw_h1',
            $h1 && str_contains( $h1_text, $keyword ),
            'Keyword en primer <h1>',
            'El <h1> debe contener la keyword principal. Es el elemento semántico más importante después del <title>.',
            [ 'value' => $h1_val ]
        );
    }

    /**
     * Check keyword in first <p>.
     *
     * @param \DOMDocument $dom DOMDocument instance.
     * @param string $keyword Target keyword (lowercase).
     * @return array Check array.
     */
    public static function check_first_paragraph( \DOMDocument $dom, string $keyword ): array {
        $first_p      = $dom->getElementsByTagName( 'p' )->item(0);
        $first_p_text = $first_p ? mb_strtolower( trim( $first_p->textContent ) ) : '';
        $first_p_val  = $first_p
            ? mb_substr( trim( $first_p->textContent ), 0, 120 ) . '...'
            : '(no encontrado)';

        return self::kw_check(
            'kw_first_paragraph',
            $first_p && str_contains( $first_p_text, $keyword ),
            'Keyword en primer <p>',
            'Menciona la keyword en el primer párrafo. Google y los LLMs dan más peso al contenido que aparece al inicio del cuerpo.',
            [ 'value' => $first_p_val ]
        );
    }

    /**
     * Check keyword in any <h2>.
     *
     * @param \DOMDocument $dom DOMDocument instance.
     * @param string $keyword Target keyword (lowercase).
     * @return array Check array.
     */
    public static function check_h2( \DOMDocument $dom, string $keyword ): array {
        $h2_found = false;
        $h2_val   = 'No encontrada en ningún <h2>';
        foreach ( $dom->getElementsByTagName( 'h2' ) as $h2 ) {
            if ( str_contains( mb_strtolower( $h2->textContent ), $keyword ) ) {
                $h2_found = true;
                $h2_val   = trim( $h2->textContent );
                break;
            }
        }

        return self::kw_check(
            'kw_h2',
            $h2_found,
            'Keyword en algún <h2>',
            'Incluye la keyword en al menos un <h2>. Refuerza la relevancia temática a lo largo del contenido.',
            [ 'value' => $h2_val ]
        );
    }

    /**
     * Check keyword in URL (canonical).
     *
     * @param \DOMXPath $xpath DOMXPath instance.
     * @param string $keyword Target keyword (lowercase).
     * @return array Check array.
     */
    public static function check_url( \DOMXPath $xpath, string $keyword ): array {
        $canonical = $xpath->query( '//link[@rel="canonical"]/@href' )->item(0);
        $url_text  = $canonical ? mb_strtolower( $canonical->nodeValue ) : '';
        $kw_slug   = str_replace( ' ', '-', $keyword );
        $url_match = $canonical && (
            str_contains( $url_text, $kw_slug ) ||
            str_contains( $url_text, rawurlencode( $keyword ) )
        );

        return self::kw_check(
            'kw_url',
            $url_match,
            'Keyword en URL (canonical)',
            'La URL debe contener la keyword en formato slug (guiones). Ejemplo: /keyword-principal. Mejora CTR y relevancia.',
            [ 'value' => $canonical ? $canonical->nodeValue : '(no encontrada)' ]
        );
    }

    /**
     * Shorthand for building a pass/error keyword check.
     */
    private static function kw_check(
        string $id,
        bool   $passed,
        string $label,
        string $recommendation,
        array  $context = []
    ): array {
        if ( $passed ) {
            return [
                'id'       => $id,
                'severity' => 'pass',
                'category' => 'seo',
                'message'  => $label,
                'context'  => $context,
            ];
        }
        return [
            'id'             => $id,
            'severity'       => 'error',
            'category'       => 'seo',
            'message'        => $label . ' — no encontrada',
            'recommendation' => $recommendation,
            'context'        => $context,
        ];
    }
}
