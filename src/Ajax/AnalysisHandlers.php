<?php
/**
 * BaloaStructureAuditorSEO\Ajax\AnalysisHandlers
 * AJAX handlers for single URL analysis and resource discovery.
 */

namespace BaloaStructureAuditorSEO\Ajax;

use BaloaStructureAuditorSEO\Services\AnalysisService;
use BaloaStructureAuditorSEO\Services\FetcherService;
use BaloaStructureAuditorSEO\Pro\Services\SitemapReader;
use BaloaStructureAuditorSEO\Pro\Services\HistoryService;
use BaloaStructureAuditorSEO\Core\ResultPresenter;

if ( ! defined( 'ABSPATH' ) ) exit;

class AnalysisHandlers {
    use AjaxHelper;

    const MAX_HTML_SIZE = 1048576; // 1MB max for manual HTML input

    /**
     * AJAX handler to analyze a single URL or manual HTML.
     *
     * @return void
     */
    public static function analyze_url(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_request();

        if ( ! self::check_rate_limit( 'analyze', 30, 300 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas solicitudes. Espera unos minutos.', 'baloa-structure-auditor-seo' ) ], 429 );
        }

        $url         = isset( $_POST['url'] ) ? self::sanitize_url( esc_url_raw( wp_unslash( $_POST['url'] ) ) ) : '';
        $keyword     = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
        $raw_html    = isset( $_POST['manual_html'] ) ? wp_unslash( $_POST['manual_html'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $manual_html = trim( $raw_html );

        if ( ! $url ) {
            wp_send_json_error( [ 'message' => __( 'URL invalida o ausente.', 'baloa-structure-auditor-seo' ) ] );
        }

        if ( ! empty( $manual_html ) && strlen( $manual_html ) > self::MAX_HTML_SIZE ) {
            wp_send_json_error( [ 'message' => __( 'El HTML manual excede el tamaño máximo permitido (1MB).', 'baloa-structure-auditor-seo' ) ] );
        }

        try {
            if ( ! empty( $manual_html ) ) {
                $html     = FetcherService::sanitize_manual_html( $manual_html );
                $strategy = 'manual';
            } else {
                $fetched = FetcherService::fetch_html( $url );
                if ( $fetched['error'] ) {
                    wp_send_json_error( [
                        'message'      => $fetched['error'],
                        'allow_manual' => true,
                    ] );
                }
                $html     = $fetched['html'];
                $strategy = $fetched['strategy'];
            }

            if ( empty( trim( (string) $html ) ) ) {
                wp_send_json_error( [ 'message' => __( 'HTML vacio.', 'baloa-structure-auditor-seo' ), 'allow_manual' => true ] );
            }

            $api_key = sanitize_text_field( \BaloaStructureAuditorSEO\Admin\Settings::get_option( 'pagespeed_api_key' ) );

            $results = AnalysisService::analyze( $html, $url, $keyword, $api_key );
        } catch ( \Throwable $e ) {
            \BaloaStructureAuditorSEO\Core\Logger::error( 'analyze_url failed', [ 'message' => $e->getMessage() ] );
            wp_send_json_error( [ 'message' => __( 'Error interno durante el análisis.', 'baloa-structure-auditor-seo' ) ] );
        }
        $result_array = $results->toArray();
        $result_array['strategy'] = $strategy;
        $result_array = ResultPresenter::localize_analysis_results( $result_array );

        $result_array['wordpress_data'] = self::detect_wordpress_and_get_posts( $url, $html );

        $user_id = get_current_user_id();
        set_transient( 'baloa_structure_auditor_seo_rep_' . $user_id . '_' . md5( $url ), $result_array, 2 * HOUR_IN_SECONDS );

        $post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
        if ( $post_id > 0 && \BaloaStructureAuditorSEO\Core\Plugin::get_instance()->get_license()->is_premium() && class_exists( '\BaloaStructureAuditorSEO\Pro\Services\HistoryService' ) ) {
            HistoryService::save_snapshot( $post_id, $results, $keyword );
        }

        wp_send_json_success( $result_array );
    }

    /**
     * AJAX handler to fetch URLs from a sitemap (PRO).
     *
     * @return void
     */
    public static function fetch_sitemap(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        self::verify_premium_request();

        if ( ! self::check_rate_limit( 'sitemap', 10, 60 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas solicitudes. Espera un minuto.', 'baloa-structure-auditor-seo' ) ], 429 );
        }

        $url     = isset( $_POST['url'] ) ? self::sanitize_url( esc_url_raw( wp_unslash( $_POST['url'] ) ) ) : '';
        if ( ! $url ) {
            wp_send_json_error( [ 'message' => __( 'URL invalida o ausente.', 'baloa-structure-auditor-seo' ) ] );
        }

        $result = SitemapReader::discover( $url );

        if ( $result['error'] ) {
            wp_send_json_error( [ 'message' => $result['error'] ] );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX handler to discover local post/page resources or external sitemaps.
     *
     * @return void
     */
    public static function discover_resources(): void {
        check_ajax_referer( 'baloa_structure_auditor_seo_nonce', 'nonce' );
        $scope = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'single';
        if ( $scope === 'sitemap' ) {
            self::verify_premium_request();
        } else {
            self::verify_request();
        }

        if ( ! self::check_rate_limit( 'discover_resources', 20, 60 ) ) {
            wp_send_json_error( [ 'message' => __( 'Demasiadas solicitudes. Espera un momento.', 'baloa-structure-auditor-seo' ) ], 429 );
        }

        $url   = isset( $_POST['url'] ) ? self::sanitize_url( esc_url_raw( wp_unslash( $_POST['url'] ) ) ) : '';

        if ( $scope === 'sitemap' ) {
            if ( ! $url ) {
                wp_send_json_error( [ 'message' => __( 'URL inválida o ausente.', 'baloa-structure-auditor-seo' ) ] );
            }
            $result = SitemapReader::discover( $url );
            if ( $result['error'] ) {
                wp_send_json_error( [ 'message' => $result['error'] ] );
            }
            wp_send_json_success( $result );
            return;
        }

        if ( in_array( $scope, [ 'posts', 'pages' ], true ) ) {
            $post_type = $scope === 'posts' ? 'post' : 'page';
            
            $cache_key = 'baloa_structure_auditor_seo_disc_' . $post_type;
            $cached    = get_transient( $cache_key );
            if ( is_array( $cached ) ) {
                wp_send_json_success( [ 'urls' => $cached, 'count' => count( $cached ), 'error' => null ] );
                return;
            }

            $args = [
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];

            $posts = get_posts( $args );
            $urls  = [];

            foreach ( $posts as $p ) {
                $permalink = get_permalink( $p->ID );
                if ( $permalink ) {
                    $urls[] = [
                        'url'     => esc_url_raw( $permalink ),
                        'lastmod' => get_the_modified_date( 'Y-m-d H:i:s', $p->ID ),
                        'title'   => get_the_title( $p->ID ),
                    ];
                }
            }

            set_transient( $cache_key, $urls, 900 );
            wp_send_json_success( [ 'urls' => $urls, 'count' => count( $urls ), 'error' => null ] );
            return;
        }

        wp_send_json_error( [ 'message' => __( 'Alcance de escaneo inválido.', 'baloa-structure-auditor-seo' ) ] );
    }

    /**
     * Private helper to detect if target is WordPress and fetch posts/pages.
     */
    private static function detect_wordpress_and_get_posts( string $url, string $html ): array {
        $data = [
            'is_wordpress' => false,
            'is_local'     => false,
            'posts'        => [],
            'pages'        => []
        ];

        $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $target_host = wp_parse_url( $url, PHP_URL_HOST );

        if ( $home_host === $target_host ) {
            $data['is_wordpress'] = true;
            $data['is_local']     = true;

            $posts = get_posts([
                'numberposts' => 3,
                'post_type'   => 'post',
                'post_status' => 'publish',
            ]);
            if ( is_array( $posts ) ) {
                foreach ( $posts as $p ) {
                    $data['posts'][] = [
                        'id'    => $p->ID,
                        'title' => get_the_title( $p ),
                        'url'   => get_permalink( $p )
                    ];
                }
            }

            $pages = get_posts([
                'numberposts' => 3,
                'post_type'   => 'page',
                'post_status' => 'publish',
            ]);
            if ( is_array( $pages ) ) {
                foreach ( $pages as $pg ) {
                    $data['pages'][] = [
                        'id'    => $pg->ID,
                        'title' => get_the_title( $pg ),
                        'url'   => get_permalink( $pg )
                    ];
                }
            }

            return $data;
        }

        $has_wp_generator = (strpos($html, '<meta name="generator" content="WordPress') !== false);
        $has_wp_paths = (strpos($html, '/wp-content/') !== false || strpos($html, '/wp-includes/') !== false);

        if ( $has_wp_generator || $has_wp_paths ) {
            $data['is_wordpress'] = true;
        } else {
            $api_url = rtrim( $url, '/' ) . '/wp-json/wp/v2/posts';
            $response = wp_remote_head( $api_url, [ 'timeout' => 1.5 ] );
            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $data['is_wordpress'] = true;
            }
        }

        if ( $data['is_wordpress'] && $target_host ) {
            $transient_key = 'baloa_structure_auditor_seo_wp_ext_' . md5( $target_host );
            $cached = get_transient( $transient_key );

            if ( is_array( $cached ) ) {
                $data['posts'] = $cached['posts'] ?? [];
                $data['pages'] = $cached['pages'] ?? [];
                return $data;
            }

            $posts_url = rtrim( $url, '/' ) . '/wp-json/wp/v2/posts?per_page=3&_fields=id,title,link';
            $posts_response = wp_remote_get( $posts_url, [ 'timeout' => 2.0 ] );
            if ( ! is_wp_error( $posts_response ) && wp_remote_retrieve_response_code( $posts_response ) === 200 ) {
                $posts_body = json_decode( wp_remote_retrieve_body( $posts_response ), true );
                if ( is_array( $posts_body ) ) {
                    foreach ( $posts_body as $p ) {
                        $title = is_array( $p['title'] ) ? ($p['title']['rendered'] ?? '') : ($p['title'] ?? '');
                        $data['posts'][] = [
                            'id'    => $p['id'] ?? 0,
                            'title' => html_entity_decode( $title ),
                            'url'   => $p['link'] ?? ''
                        ];
                    }
                }
            }

            $pages_url = rtrim( $url, '/' ) . '/wp-json/wp/v2/pages?per_page=3&_fields=id,title,link';
            $pages_response = wp_remote_get( $pages_url, [ 'timeout' => 2.0 ] );
            if ( ! is_wp_error( $pages_response ) && wp_remote_retrieve_response_code( $pages_response ) === 200 ) {
                $pages_body = json_decode( wp_remote_retrieve_body( $pages_response ), true );
                if ( is_array( $pages_body ) ) {
                    foreach ( $pages_body as $pg ) {
                        $title = is_array( $pg['title'] ) ? ($pg['title']['rendered'] ?? '') : ($pg['title'] ?? '');
                        $data['pages'][] = [
                            'id'    => $pg['id'] ?? 0,
                            'title' => html_entity_decode( $title ),
                            'url'   => $pg['link'] ?? ''
                        ];
                    }
                }
            }

            set_transient( $transient_key, [
                'posts' => $data['posts'],
                'pages' => $data['pages']
            ], HOUR_IN_SECONDS );
        }

        return $data;
    }
}
