<?php
/**
 * BaloaStructureAuditorSEO\Free\Analyzers\KeywordAnalyzer
 *
 * Orchestrator for keyword analysis.
 * Delegates to specialized sub-checks for density and position checks.
 *
 * Migrated to BaloaStructureAuditorSEO\Core\ScoringEngine in v0.3.0.
 *
 * @package SEO_Structure_Inspector
 * @since   0.1.0
 */

namespace BaloaStructureAuditorSEO\Free\Analyzers;

use BaloaStructureAuditorSEO\Core\ScoringEngine;
use BaloaStructureAuditorSEO\Core\BaseAnalyzer;
use BaloaStructureAuditorSEO\Analyzers\Keyword\DensityCheck;
use BaloaStructureAuditorSEO\Analyzers\Keyword\PositionCheck;

if ( ! defined( 'ABSPATH' ) ) exit;

class KeywordAnalyzer extends BaseAnalyzer {

    /**
     * @param string $html    Full page HTML.
     * @param string $keyword Target keyword or phrase.
     * @return array Standard module result via ScoringEngine::build_result().
     */
    public static function analyze( string $html, string $keyword ): array|\BaloaStructureAuditorSEO\Core\DTO\ModuleResult {
        if ( empty( trim( $keyword ) ) ) {
            return [ 'error' => 'Keyword vacía.' ];
        }

        $dom   = self::load_dom( $html );
        $xpath = new \DOMXPath( $dom );
        $kw    = mb_strtolower( trim( $keyword ) );

        $checks  = [];
        $details = [ 'keyword' => $keyword ];

        // â”€â”€ Position checks â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $checks[] = PositionCheck::check_title( $xpath, $kw );
        $checks[] = PositionCheck::check_meta_description( $xpath, $kw );
        $checks[] = PositionCheck::check_h1( $dom, $kw );
        $checks[] = PositionCheck::check_first_paragraph( $dom, $kw );
        $checks[] = PositionCheck::check_h2( $dom, $kw );
        $checks[] = PositionCheck::check_url( $xpath, $kw );

        // â”€â”€ Density check â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $body_node = $dom->getElementsByTagName( 'body' )->item(0);
        $body_text = $body_node
            ? mb_strtolower( $body_node->textContent )
            : mb_strtolower( wp_strip_all_tags( $html ) );
        $density   = DensityCheck::calculate( $body_text, $kw );

        $details['density'] = $density;
        $checks[] = DensityCheck::build_check( $density );

        return ScoringEngine::build_result( $checks, $details );
    }
}
