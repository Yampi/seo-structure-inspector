<?php
/**
 * SEOSI\Free\Analyzers\KeywordAnalyzer
 *
 * Orchestrator for keyword analysis.
 * Delegates to specialized sub-checks for density and position checks.
 *
 * Migrated to SEOSI\Core\ScoringEngine in v0.3.0.
 *
 * @package SEO_Structure_Inspector
 * @since   0.1.0
 */

namespace SEOSI\Free\Analyzers;

use SEOSI\Core\ScoringEngine;
use SEOSI\Core\BaseAnalyzer;
use SEOSI\Analyzers\Keyword\DensityCheck;
use SEOSI\Analyzers\Keyword\PositionCheck;

if ( ! defined( 'ABSPATH' ) ) exit;

class KeywordAnalyzer extends BaseAnalyzer {

    /**
     * @param string $html    Full page HTML.
     * @param string $keyword Target keyword or phrase.
     * @return array Standard module result via ScoringEngine::build_result().
     */
    public static function analyze( string $html, string $keyword ): array|\SEOSI\Core\DTO\ModuleResult {
        if ( empty( trim( $keyword ) ) ) {
            return [ 'error' => 'Keyword vacía.' ];
        }

        $dom   = self::load_dom( $html );
        $xpath = new \DOMXPath( $dom );
        $kw    = mb_strtolower( trim( $keyword ) );

        $checks  = [];
        $details = [ 'keyword' => $keyword ];

        // ── Position checks ─────────────────────────────────────────────────────
        $checks[] = PositionCheck::check_title( $xpath, $kw );
        $checks[] = PositionCheck::check_meta_description( $xpath, $kw );
        $checks[] = PositionCheck::check_h1( $dom, $kw );
        $checks[] = PositionCheck::check_first_paragraph( $dom, $kw );
        $checks[] = PositionCheck::check_h2( $dom, $kw );
        $checks[] = PositionCheck::check_url( $xpath, $kw );

        // ── Density check ───────────────────────────────────────────────────────
        $body_node = $dom->getElementsByTagName( 'body' )->item(0);
        $body_text = $body_node
            ? mb_strtolower( $body_node->textContent )
            : mb_strtolower( strip_tags( $html ) );
        $density   = DensityCheck::calculate( $body_text, $kw );

        $details['density'] = $density;
        $checks[] = DensityCheck::build_check( $density );

        return ScoringEngine::build_result( $checks, $details );
    }
}
