<?php
/**
 * PHPUnit Bootstrap for SEO Structure Inspector tests.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

// Mock basic WordPress core functions
if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( string $text ): string {
        return htmlspecialchars( $text, ENT_NOQUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( string $url ): string {
        return $url;
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( string $tag, $value, ...$args ) {
        return $value;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( string $tag, callable $function_to_add, int $priority = 10, int $accepted_args = 1 ): bool {
        return true;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( string $tag, callable $function_to_add, int $priority = 10, int $accepted_args = 1 ): bool {
        return true;
    }
}

// Global variables to control mocks dynamically in tests
global $wp_mock_is_singular, $wp_mock_is_admin, $wp_mock_is_preview, $wp_mock_is_feed, $wp_mock_is_embed, $wp_mock_is_robots, $wp_mock_is_trackback;
$wp_mock_is_singular = false;
$wp_mock_is_admin = false;
$wp_mock_is_preview = false;
$wp_mock_is_feed = false;
$wp_mock_is_embed = false;
$wp_mock_is_robots = false;
$wp_mock_is_trackback = false;

if ( ! function_exists( 'is_singular' ) ) {
    function is_singular(): bool {
        global $wp_mock_is_singular;
        return (bool) $wp_mock_is_singular;
    }
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin(): bool {
        global $wp_mock_is_admin;
        return (bool) $wp_mock_is_admin;
    }
}

if ( ! function_exists( 'is_preview' ) ) {
    function is_preview(): bool {
        global $wp_mock_is_preview;
        return (bool) $wp_mock_is_preview;
    }
}

if ( ! function_exists( 'is_feed' ) ) {
    function is_feed(): bool {
        global $wp_mock_is_feed;
        return (bool) $wp_mock_is_feed;
    }
}

if ( ! function_exists( 'is_embed' ) ) {
    function is_embed(): bool {
        global $wp_mock_is_embed;
        return (bool) $wp_mock_is_embed;
    }
}

if ( ! function_exists( 'is_robots' ) ) {
    function is_robots(): bool {
        global $wp_mock_is_robots;
        return (bool) $wp_mock_is_robots;
    }
}

if ( ! function_exists( 'is_trackback' ) ) {
    function is_trackback(): bool {
        global $wp_mock_is_trackback;
        return (bool) $wp_mock_is_trackback;
    }
}

// Option mocks system
global $wp_mock_options;
$wp_mock_options = [];

if ( ! function_exists( 'get_option' ) ) {
    function get_option( string $option, $default = false ) {
        global $wp_mock_options;
        return isset( $wp_mock_options[ $option ] ) ? $wp_mock_options[ $option ] : $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( string $option, $value, $autoload = null ): bool {
        global $wp_mock_options;
        $wp_mock_options[ $option ] = $value;
        return true;
    }
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
