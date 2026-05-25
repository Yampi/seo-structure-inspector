<?php
/**
 * SEOSI\Services\FetcherService
 * Handles HTML fetching with multiple strategies.
 */

namespace SEOSI\Services;

use SEOSI\Core\Http;

if ( ! defined( 'ABSPATH' ) ) exit;

class FetcherService {

    /**
     * @param string $url Validated URL.
     * @return array{ html: string|null, strategy: string|null, error: string|null }
     */
    public static function fetch_html( string $url ): array {
        if ( ! self::is_safe_remote_url( $url ) ) {
            return [ 'html' => null, 'strategy' => null, 'error' => 'URL no permitida por seguridad (SSRF protection).' ];
        }

        if ( ! self::is_dns_safe( $url ) ) {
            return [ 'html' => null, 'strategy' => null, 'error' => 'URL no permitida por seguridad (DNS rebinding detected).' ];
        }

        $response = self::fetch_with_redirect_validation( $url, Http::args( [
            'timeout'     => 20,
            'redirection' => 0,
            'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'headers'     => [
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
                'Cache-Control'   => 'no-cache',
            ],
        ] ) );

        if ( is_wp_error( $response ) ) {
            error_log( '[SEOSI] Direct fetch failed: ' . $response->get_error_message() );
        }

        $code = ! is_wp_error( $response ) ? (int) wp_remote_retrieve_response_code( $response ) : 0;

        if ( ! is_wp_error( $response ) && $code === 200 ) {
            $html = wp_remote_retrieve_body( $response );
            if ( ! empty( trim( $html ) ) ) {
                return [ 'html' => $html, 'strategy' => 'direct', 'error' => null ];
            }
        }

        $cache_url      = 'https://webcache.googleusercontent.com/search?q=cache:' . rawurlencode( $url ) . '&hl=es';
        $cache_response = wp_remote_get( $cache_url, Http::args( [
            'timeout'    => 15,
            'user-agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)',
        ] ) );

        if ( is_wp_error( $cache_response ) ) {
            error_log( '[SEOSI] Google Cache fetch failed: ' . $cache_response->get_error_message() );
        }

        $cache_code = ! is_wp_error( $cache_response ) ? (int) wp_remote_retrieve_response_code( $cache_response ) : 0;

        if ( ! is_wp_error( $cache_response ) && $cache_code === 200 ) {
            $html = wp_remote_retrieve_body( $cache_response );
            if ( ! empty( trim( $html ) ) ) {
                return [ 'html' => $html, 'strategy' => 'google_cache', 'error' => null ];
            }
        }

        if ( $cache_code === 403 ) {
            error_log( '[SEOSI] Google Cache returned HTTP 403 for ' . $url );
        }

        $error_msg = self::build_fetch_error_message( $response, $code, $cache_response, $cache_code );

        return [ 'html' => null, 'strategy' => null, 'error' => $error_msg ];
    }

    public static function sanitize_manual_html( string $html ): string {
        $html = preg_replace( '/<\?php.*?\?>/si', '', $html );
        $html = preg_replace( '/<\?=.*?\?>/si', '', $html );
        $html = preg_replace( '/\s+on\w+\s*=\s*(["\']).*?\1/si', '', $html );
        $html = preg_replace( '/\s+on\w+\s*=\s*[^\s>]+/si', '', $html );
        $html = preg_replace( '/\b(href|src|action)\s*=\s*(["\'])javascript:.*?\2/si', '', $html );

        $html = preg_replace_callback(
            '/<script([^>]*)>(.*?)<\/script>/si',
            function ( $matches ) {
                $attrs = $matches[1];
                $body  = $matches[2];
                if ( stripos( $attrs, 'application/ld+json' ) !== false ) {
                    return '<script' . $attrs . '>' . $body . '</script>';
                }
                return '';
            },
            $html
        );

        return $html;
    }

    private static function build_fetch_error_message( $response, int $code, $cache_response, int $cache_code ): string {
        if ( is_wp_error( $response ) ) {
            return self::humanize_wp_error( $response->get_error_message() );
        }

        if ( $code === 403 ) {
            return 'HTTP 403 — acceso denegado (WAF/Cloudflare). Intenta pegar el HTML manualmente.';
        }

        if ( $code === 0 ) {
            return 'No se pudo conectar con el servidor. Verifica DNS, SSL o intenta pegar el HTML manualmente.';
        }

        if ( $code >= 500 ) {
            return "HTTP {$code} — error del servidor remoto. Intenta pegar el HTML manualmente.";
        }

        if ( is_wp_error( $cache_response ) ) {
            return self::humanize_wp_error( $cache_response->get_error_message() );
        }

        if ( $cache_code === 403 ) {
            return 'HTTP 403 — el servidor y Google Cache bloquearon la solicitud. Intenta pegar el HTML manualmente.';
        }

        return "HTTP {$code} — el servidor bloqueó la solicitud (WAF/Cloudflare). Intenta pegar el HTML manualmente.";
    }

    private static function humanize_wp_error( string $message ): string {
        $lower = strtolower( $message );

        if ( str_contains( $lower, 'timed out' ) || str_contains( $lower, 'timeout' ) ) {
            return 'Tiempo de espera agotado al obtener la URL. Intenta pegar el HTML manualmente.';
        }

        if ( str_contains( $lower, 'ssl' ) || str_contains( $lower, 'certificate' ) ) {
            return 'Error SSL al conectar. Si usas hosting compartido, contacta al administrador o pega el HTML manualmente.';
        }

        if ( str_contains( $lower, 'could not resolve' ) || str_contains( $lower, 'dns' ) ) {
            return 'Fallo de resolución DNS. Verifica la URL o pega el HTML manualmente.';
        }

        return $message . ' Intenta pegar el HTML manualmente.';
    }

    private static function is_safe_remote_url( string $url ): bool {
        $parsed = parse_url( $url );
        if ( ! $parsed || ! isset( $parsed['host'] ) ) {
            return false;
        }

        $host = strtolower( $parsed['host'] );

        $localhost_patterns = [ 'localhost', '127.0.0.1', '::1', '0.0.0.0' ];
        if ( in_array( $host, $localhost_patterns, true ) ) {
            return false;
        }

        if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
            if ( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
                return false;
            }
        }

        $blocked_hosts = [ '169.254.169.254', 'metadata.google.internal' ];
        if ( in_array( $host, $blocked_hosts, true ) ) {
            return false;
        }

        if ( str_ends_with( $host, '.local' ) || str_ends_with( $host, '.internal' ) || str_ends_with( $host, '.corp' ) ) {
            return false;
        }

        return true;
    }

    private static function is_dns_safe( string $url ): bool {
        $parsed = parse_url( $url );
        if ( ! $parsed || ! isset( $parsed['host'] ) ) {
            return false;
        }

        $ips = gethostbynamel( $parsed['host'] );
        if ( $ips === false || empty( $ips ) ) {
            return false;
        }

        foreach ( $ips as $ip ) {
            if ( $ip === '127.0.0.1' || $ip === '::1' || $ip === '0.0.0.0' ) {
                return false;
            }
            if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
                return false;
            }
            if ( $ip === '169.254.169.254' ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|\WP_Error
     */
    private static function fetch_with_redirect_validation( string $url, array $args ) {
        $max_redirects   = 5;
        $current_url     = $url;
        $redirect_count  = 0;

        while ( $redirect_count < $max_redirects ) {
            $response = wp_remote_get( $current_url, $args );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $code    = (int) wp_remote_retrieve_response_code( $response );
            $headers = wp_remote_retrieve_headers( $response );

            if ( in_array( $code, [ 301, 302, 303, 307, 308 ], true ) ) {
                $location = $headers['location'] ?? $headers['Location'] ?? null;
                if ( ! $location ) {
                    return $response;
                }

                if ( ! str_starts_with( $location, 'http://' ) && ! str_starts_with( $location, 'https://' ) ) {
                    $parsed   = parse_url( $current_url );
                    $location = ( $parsed['scheme'] ?? 'https' ) . '://' . ( $parsed['host'] ?? '' ) . '/' . ltrim( $location, '/' );
                }

                if ( ! self::is_safe_remote_url( $location ) ) {
                    return new \WP_Error( 'seosi_ssrf', 'Redirección bloqueada por seguridad (SSRF protection).' );
                }

                if ( ! self::is_dns_safe( $location ) ) {
                    return new \WP_Error( 'seosi_dns', 'Redirección bloqueada por seguridad (DNS rebinding detected).' );
                }

                $current_url = $location;
                $redirect_count++;
                continue;
            }

            return $response;
        }

        return new \WP_Error( 'seosi_redirects', 'Demasiadas redirecciones.' );
    }
}
