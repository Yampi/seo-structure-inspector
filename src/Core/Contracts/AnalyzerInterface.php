<?php
/**
 * SEOSI\Core\Contracts\AnalyzerInterface
 * 
 * Common contract for all analyzer modules.
 */

namespace SEOSI\Core\Contracts;

if ( ! defined( 'ABSPATH' ) ) exit;

interface AnalyzerInterface {

    /**
     * Run the analysis.
     *
     * @param string $html     HTML content of the page.
     * @param string $url      URL of the page.
     * @param array  $context  Optional context parameters.
     * @return array|\SEOSI\Core\DTO\ModuleResult Standard analysis result.
     */
    public static function analyze( string $html, string $url = '', array $context = [] ): array|\SEOSI\Core\DTO\ModuleResult;
}
