<?php
/**
 * BaloaStructureAuditorSEO\Core\ReadinessChecker
 * 
 * Checks system readiness for plugin deployment.
 * Verifies PHP/WordPress requirements, security, and functionality.
 */

namespace BaloaStructureAuditorSEO\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class ReadinessChecker {

    /**
     * Run all readiness checks and return results.
     *
     * @return array Check results with status and messages.
     */
    public static function run(): array {
        $results = [];

        // PHP and WordPress checks
        $results['php_version'] = self::check_php_version();
        $results['wp_version'] = self::check_wp_version();
        $results['memory_limit'] = self::check_memory_limit();
        $results['max_execution_time'] = self::check_max_execution_time();

        // Plugin configuration checks
        $results['text_domain'] = self::check_text_domain();
        $results['translation_files'] = self::check_translation_files();
        $results['uninstall_exists'] = self::check_uninstall_exists();
        $results['readme_exists'] = self::check_readme_exists();

        // Security checks
        $results['ssl_verify'] = self::check_ssl_verify();
        $results['no_debug_outputs'] = self::check_no_debug_outputs();
        $results['ajax_nonce'] = self::check_ajax_nonce();
        $results['template_escaping'] = self::check_template_escaping();

        // Functionality checks
        $results['cron_registered'] = self::check_cron_registered();

        return $results;
    }

    /**
     * Check PHP version >= 8.1
     */
    private static function check_php_version(): array {
        $required = '8.1';
        $current = PHP_VERSION;
        $passed = version_compare( $current, $required, '>=' );

        return [
            'status' => $passed ? 'pass' : 'fail',
            'message' => sprintf( 'PHP %s (required: %s)', $current, $required ),
        ];
    }

    /**
     * Check WordPress version >= 6.0
     */
    private static function check_wp_version(): array {
        $required = '6.0';
        $current = get_bloginfo( 'version' );
        $passed = version_compare( $current, $required, '>=' );

        return [
            'status' => $passed ? 'pass' : 'fail',
            'message' => sprintf( 'WordPress %s (required: %s)', $current, $required ),
        ];
    }

    /**
     * Check memory limit >= 128MB
     */
    private static function check_memory_limit(): array {
        $limit = ini_get( 'memory_limit' );
        $limit_bytes = self::convert_to_bytes( $limit );
        $required = 128 * 1024 * 1024; // 128MB
        $passed = $limit_bytes >= $required;

        return [
            'status' => $passed ? 'pass' : 'warn',
            'message' => sprintf( 'Memory limit: %s (recommended: 128M)', $limit ),
        ];
    }

    /**
     * Check max execution time >= 30s
     */
    private static function check_max_execution_time(): array {
        $time = ini_get( 'max_execution_time' );
        $passed = $time == 0 || $time >= 30; // 0 means unlimited

        return [
            'status' => $passed ? 'pass' : 'warn',
            'message' => sprintf( 'Max execution time: %ss (recommended: 30s)', $time ),
        ];
    }

    /**
     * Check text domain is loaded
     */
    private static function check_text_domain(): array {
        $loaded = is_textdomain_loaded( 'baloa-structure-auditor-seo' );
        
        return [
            'status' => $loaded ? 'pass' : 'warn',
            'message' => $loaded ? 'Text domain loaded' : 'Text domain not loaded',
        ];
    }

    /**
     * Check translation files exist
     */
    private static function check_translation_files(): array {
        $lang_dir = BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'languages/';
        $exists = is_dir( $lang_dir );

        return [
            'status' => $exists ? 'pass' : 'warn',
            'message' => $exists ? 'Languages directory exists' : 'Languages directory missing',
        ];
    }

    /**
     * Check uninstall.php exists
     */
    private static function check_uninstall_exists(): array {
        $exists = file_exists( BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'uninstall.php' );

        return [
            'status' => $exists ? 'pass' : 'fail',
            'message' => $exists ? 'uninstall.php exists' : 'uninstall.php missing',
        ];
    }

    /**
     * Check readme.txt exists
     */
    private static function check_readme_exists(): array {
        $exists = file_exists( BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'readme.txt' );

        return [
            'status' => $exists ? 'pass' : 'fail',
            'message' => $exists ? 'readme.txt exists' : 'readme.txt missing',
        ];
    }

    /**
     * Check sslverify is true in wp_remote_get calls
     */
    private static function check_ssl_verify(): array {
        $fetcher_file = BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Services/FetcherService.php';
        $content = file_get_contents( $fetcher_file );
        $has_sslverify = strpos( $content, "'sslverify'" ) !== false;

        return [
            'status' => $has_sslverify ? 'pass' : 'fail',
            'message' => $has_sslverify ? 'SSL verification enabled' : 'SSL verification not found',
        ];
    }

    /**
     * Check for var_dump, print_r, error_log in code
     */
    private static function check_no_debug_outputs(): array {
        $src_dir = BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/';
        $found = false;

        $iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $src_dir ) );
        foreach ( $iterator as $file ) {
            if ( $file->isFile() && $file->getExtension() === 'php' ) {
                $content = file_get_contents( $file->getPathname() );
                if ( preg_match( '/\b(var_dump|print_r|error_log)\s*\(/', $content ) ) {
                    $found = true;
                    break;
                }
            }
        }

        return [
            'status' => ! $found ? 'pass' : 'fail',
            'message' => $found ? 'Debug outputs found in code' : 'No debug outputs found',
        ];
    }

    /**
     * Check AJAX handlers have nonce verification
     */
    private static function check_ajax_nonce(): array {
        $handlers_file = BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'src/Ajax/Handlers.php';
        $content = file_get_contents( $handlers_file );
        $has_nonce = strpos( $content, 'check_ajax_referer' ) !== false;

        return [
            'status' => $has_nonce ? 'pass' : 'fail',
            'message' => $has_nonce ? 'AJAX nonce verification present' : 'AJAX nonce verification missing',
        ];
    }

    /**
     * Check templates escape outputs
     */
    private static function check_template_escaping(): array {
        $templates_dir = BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'templates/';
        $escaped = true;

        if ( is_dir( $templates_dir ) ) {
            $files = glob( $templates_dir . '*.php' );
            foreach ( $files as $file ) {
                $content = file_get_contents( $file );
                if ( preg_match( '/<\?php\s+echo\s+\$[^;]+;/', $content ) ) {
                    $escaped = false;
                    break;
                }
            }
        }

        return [
            'status' => $escaped ? 'pass' : 'warn',
            'message' => $escaped ? 'Templates use escaping' : 'Templates may need escaping review',
        ];
    }

    /**
     * Check WP-Cron event is registered
     */
    private static function check_cron_registered(): array {
        $hook = \BaloaStructureAuditorSEO\Services\SchedulerService::CRON_HOOK;
        $scheduled = wp_next_scheduled( $hook );

        return [
            'status' => $scheduled ? 'pass' : 'warn',
            'message' => $scheduled ? 'WP-Cron event registered' : 'WP-Cron event not registered (will register on activation)',
        ];
    }

    /**
     * Convert memory limit string to bytes
     */
    private static function convert_to_bytes( $value ): int {
        $value = trim( $value );
        $last = strtolower( $value[ strlen( $value ) - 1 ] );
        $value = (int) $value;

        switch ( $last ) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}
