<?php
/**
 * BaloaStructureAuditorSEO\Analyzers\Links\BrokenLinksCheck
 * 
 * Checks for broken links using HEAD requests.
 * Classifies links as ok, broken, redirect, or timeout.
 */

namespace BaloaStructureAuditorSEO\Analyzers\Links;

use BaloaStructureAuditorSEO\Core\Http;

if ( ! defined( 'ABSPATH' ) ) exit;

class BrokenLinksCheck {

    /**
     * Check multiple URLs for broken links.
     *
     * @param array $urls URLs to check (max 20 recommended).
     * @param int $timeout Timeout in seconds (default 5).
     * @return array Array of results with url, status_code, type for each URL.
     */
    public static function check_links( array $urls, int $timeout = 5 ): array {
        $results = [];
        
        foreach ( $urls as $url ) {
            $result = self::check_single_link( $url, $timeout );
            $results[] = $result;
        }
        
        return $results;
    }

    /**
     * Check a single URL using HEAD request.
     *
     * @param string $url URL to check.
     * @param int $timeout Timeout in seconds.
     * @return array Result with url, status_code, type.
     */
    private static function check_single_link( string $url, int $timeout ): array {
        $response = wp_remote_head( $url, Http::args( [
            'timeout' => $timeout,
        ] ) );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            if ( strpos( $error_message, 'timed out' ) !== false ) {
                return [
                    'url'         => $url,
                    'status_code' => null,
                    'type'        => 'timeout',
                    'error'       => $error_message,
                ];
            }
            return [
                'url'         => $url,
                'status_code' => null,
                'type'        => 'broken',
                'error'       => $error_message,
            ];
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        // Classify by status code
        if ( $status_code >= 200 && $status_code < 300 ) {
            $type = 'ok';
        } elseif ( $status_code >= 300 && $status_code < 400 ) {
            $type = 'redirect';
        } elseif ( $status_code >= 400 ) {
            $type = 'broken';
        } else {
            $type = 'broken';
        }

        return [
            'url'         => $url,
            'status_code' => $status_code,
            'type'        => $type,
        ];
    }

    /**
     * Generate checks from broken links results.
     *
     * @param array $results Results from check_links().
     * @return array Checks array for ScoringEngine.
     */
    public static function generate_checks( array $results ): array {
        $checks = [];
        
        $broken_count = 0;
        $redirect_count = 0;
        $timeout_count = 0;
        $ok_count = 0;

        foreach ( $results as $result ) {
            switch ( $result['type'] ) {
                case 'broken':
                    $broken_count++;
                    break;
                case 'redirect':
                    $redirect_count++;
                    break;
                case 'timeout':
                    $timeout_count++;
                    break;
                case 'ok':
                    $ok_count++;
                    break;
            }
        }

        $total = count( $results );
        if ( $total === 0 ) {
            return $checks;
        }

        // Check for broken links (error severity)
        if ( $broken_count > 0 ) {
            $checks[] = [
                'id'             => 'links_broken_check',
                'severity'       => 'error',
                'category'       => 'links',
                'message'        => "Se encontraron {$broken_count} enlaces rotos ({$broken_count}/{$total})",
                'recommendation' => 'Los enlaces rotos afectan la experiencia de usuario y el SEO. Revisa y corrige o elimina los enlaces que devuelven errores 404, 410, 500.',
                'context'        => [
                    'broken_count' => $broken_count,
                    'total'        => $total,
                ],
            ];
        }

        // Check for redirects (warning severity)
        if ( $redirect_count > 0 ) {
            $checks[] = [
                'id'             => 'links_redirect_check',
                'severity'       => 'warning',
                'category'       => 'links',
                'message'        => "Se encontraron {$redirect_count} redirecciones ({$redirect_count}/{$total})",
                'recommendation' => 'Las redirecciones innecesarias ralentizan la carga. Revisa si las redirecciones son necesarias y actualiza los enlaces directamente cuando sea posible.',
                'context'        => [
                    'redirect_count' => $redirect_count,
                    'total'          => $total,
                ],
            ];
        }

        // Check for timeouts (warning severity)
        if ( $timeout_count > 0 ) {
            $checks[] = [
                'id'             => 'links_timeout_check',
                'severity'       => 'warning',
                'category'       => 'links',
                'message'        => "Se encontraron {$timeout_count} enlaces con timeout ({$timeout_count}/{$total})",
                'recommendation' => 'Los enlaces que no responden pueden indicar servidores lentos o caídos. Revisa estos enlaces periódicamente.',
                'context'        => [
                    'timeout_count' => $timeout_count,
                    'total'         => $total,
                ],
            ];
        }

        // Pass if all links are ok
        if ( $broken_count === 0 && $redirect_count === 0 && $timeout_count === 0 && $ok_count > 0 ) {
            $checks[] = [
                'id'       => 'links_broken_check',
                'severity' => 'pass',
                'category' => 'links',
                'message'  => "Todos los enlaces funcionan correctamente ({$ok_count}/{$total})",
                'context'  => [
                    'ok_count' => $ok_count,
                    'total'    => $total,
                ],
            ];
        }

        return $checks;
    }
}
