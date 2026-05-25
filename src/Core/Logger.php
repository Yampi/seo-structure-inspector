<?php
/**
 * SEOSI\Core\Logger
 * Simple logging system for debugging and monitoring.
 * Logs to WordPress debug.log when WP_DEBUG is enabled.
 */

namespace SEOSI\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Logger {

    const LOG_PREFIX = 'SEOSI: ';

    /**
     * Log an info message.
     *
     * @param string $message Message to log.
     * @param array  $context Optional context data.
     */
    public static function info( string $message, array $context = [] ): void {
        self::log( 'INFO', $message, $context );
    }

    /**
     * Log a warning message.
     *
     * @param string $message Message to log.
     * @param array  $context Optional context data.
     */
    public static function warning( string $message, array $context = [] ): void {
        self::log( 'WARNING', $message, $context );
    }

    /**
     * Log an error message.
     *
     * @param string $message Message to log.
     * @param array  $context Optional context data.
     */
    public static function error( string $message, array $context = [] ): void {
        self::log( 'ERROR', $message, $context );
    }

    /**
     * Log a debug message.
     *
     * @param string $message Message to log.
     * @param array  $context Optional context data.
     */
    public static function debug( string $message, array $context = [] ): void {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            self::log( 'DEBUG', $message, $context );
        }
    }

    /**
     * Core logging method.
     *
     * @param string $level   Log level (INFO, WARNING, ERROR, DEBUG).
     * @param string $message Message to log.
     * @param array  $context Optional context data.
     */
    private static function log( string $level, string $message, array $context = [] ): void {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }

        $timestamp = current_time( 'Y-m-d H:i:s' );
        $prefix    = self::LOG_PREFIX . "[{$level}] [{$timestamp}]";
        $log_entry = $prefix . ' ' . $message;

        if ( ! empty( $context ) ) {
            $log_entry .= ' ' . json_encode( $context );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( $log_entry );
        }
    }

    /**
     * Log analysis start.
     *
     * @param string $url URL being analyzed.
     */
    public static function log_analysis_start( string $url ): void {
        self::info( 'Analysis started', [ 'url' => $url ] );
    }

    /**
     * Log analysis completion.
     *
     * @param string $url     URL analyzed.
     * @param int    $score   Global score.
     * @param float  $duration Duration in seconds.
     */
    public static function log_analysis_complete( string $url, int $score, float $duration ): void {
        self::info( 'Analysis completed', [
            'url'      => $url,
            'score'    => $score,
            'duration' => round( $duration, 2 ) . 's',
        ] );
    }

    /**
     * Log batch job creation.
     *
     * @param string $job_id  Job ID.
     * @param int    $url_count Number of URLs.
     */
    public static function log_batch_created( string $job_id, int $url_count ): void {
        self::info( 'Batch job created', [ 'job_id' => $job_id, 'url_count' => $url_count ] );
    }

    /**
     * Log batch job completion.
     *
     * @param string $job_id Job ID.
     * @param int    $completed Number of completed URLs.
     */
    public static function log_batch_completed( string $job_id, int $completed ): void {
        self::info( 'Batch job completed', [ 'job_id' => $job_id, 'completed' => $completed ] );
    }
}
