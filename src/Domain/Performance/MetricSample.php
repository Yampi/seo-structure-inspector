<?php
/**
 * BaloaStructureAuditorSEO\Domain\Performance\MetricSample
 *
 * Value Object representing a Core Web Vitals telemetry sample.
 */

declare(strict_types=1);

namespace BaloaStructureAuditorSEO\Domain\Performance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MetricSample {

    private string $name;
    private float $value;
    private string $url;

    private const ALLOWED_METRICS = [ 'LCP', 'FID', 'CLS', 'INP', 'FCP', 'TTFB' ];

    /**
     * Constructor.
     *
     * @param string $name  Metric identifier (e.g. 'LCP', 'CLS').
     * @param float  $value Metric value.
     * @param string $url   The page URL where the metric was collected.
     * @throws \InvalidArgumentException if parameters are invalid.
     */
    public function __construct( string $name, float $value, string $url ) {
        $this->name  = $this->validate_name( $name );
        $this->value = $this->validate_value( $name, $value );
        $this->url   = $this->validate_url( $url );
    }

    /**
     * Get metric name.
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get metric value.
     */
    public function get_value(): float {
        return $this->value;
    }

    /**
     * Get page URL.
     */
    public function get_url(): string {
        return $this->url;
    }

    private function validate_name( string $name ): string {
        $name = strtoupper( trim( $name ) );
        if ( ! in_array( $name, self::ALLOWED_METRICS, true ) ) {
            throw new \InvalidArgumentException( "Metrica no permitida: " . esc_html( $name ) );
        }
        return $name;
    }

    private function validate_value( string $name, float $value ): float {
        if ( $value < 0 ) {
            throw new \InvalidArgumentException( "El valor de la metrica no puede ser negativo." );
        }

        // Limit values to prevent overflows or malicious inputs
        if ( $name === 'CLS' && $value > 50.0 ) {
            return 50.0;
        }

        if ( $name !== 'CLS' && $value > 300000.0 ) {
            return 300000.0;
        }

        return $value;
    }

    private function validate_url( string $url ): string {
        $url = trim( $url );
        if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
            throw new \InvalidArgumentException( "URL invalida." );
        }
        return $url;
    }
}
