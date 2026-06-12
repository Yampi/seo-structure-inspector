<?php
/**
 * BaloaStructureAuditorSEO\Core\Contracts\AnalyzerInterface
 * 
 * Common contract for all analyzer modules.
 */

namespace BaloaStructureAuditorSEO\Core\Contracts;

if ( ! defined( 'ABSPATH' ) ) exit;

interface AnalyzerInterface {

    /**
     * Run the analysis.
     *
     * @param string $html     HTML content of the page.
     * @param string $url      URL of the page.
     * @param array  $context  Optional context parameters.
     * @return array|\BaloaStructureAuditorSEO\Core\DTO\ModuleResult Standard analysis result.
     */
    public static function analyze( string $html, string $url = '', array $context = [] ): array|\BaloaStructureAuditorSEO\Core\DTO\ModuleResult;
}
