<?php
/**
 * SEOSI\Core\AnalyzerRegistry
 * 
 * Central registry for dynamically registering and fetching SEO analyzers.
 * Ensures strict compliance with the Open/Closed Principle (OCP).
 */

namespace SEOSI\Core;

use SEOSI\Core\Contracts\AnalyzerInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

class AnalyzerRegistry {

    /**
     * Map of analyzer keys to their class names.
     *
     * @var string[]
     */
    private static array $analyzers = [];

    /**
     * Register an analyzer.
     *
     * @param string $key   Unique identifier for the analyzer.
     * @param string $class Fully qualified class name.
     * @return void
     */
    public static function register( string $key, string $class ): void {
        if ( ! class_exists( $class ) ) {
            return;
        }

        if ( is_subclass_of( $class, AnalyzerInterface::class ) ) {
            self::$analyzers[ $key ] = $class;
        } else {
            error_log( sprintf( '[SEOSI] Class %s must implement AnalyzerInterface to be registered.', $class ) );
        }
    }

    /**
     * Retrieve all registered analyzers.
     *
     * @return string[] Array of FQCN of registered analyzers.
     */
    public static function get_all(): array {
        return self::$analyzers;
    }
}
