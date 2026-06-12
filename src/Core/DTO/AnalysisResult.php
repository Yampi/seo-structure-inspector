<?php
/**
 * BaloaStructureAuditorSEO\Core\DTO\AnalysisResult
 * 
 * DTO for the complete analysis result.
 * Aggregates all module results with metadata.
 */

namespace BaloaStructureAuditorSEO\Core\DTO;

use BaloaStructureAuditorSEO\Core\DTO\ModuleResult;

if ( ! defined( 'ABSPATH' ) ) exit;

readonly class AnalysisResult {

    public string $url;
    public int $globalScore;
    public ?ModuleResult $html;
    public ?ModuleResult $keyword;
    public ?ModuleResult $schema;
    public ?ModuleResult $readability;
    public ?ModuleResult $metatags;
    public ?ModuleResult $llms;
    public ?ModuleResult $aeo;
    public ?ModuleResult $geo;
    public ?ModuleResult $cwv;
    public ?ModuleResult $cwvMobile;
    public ?ModuleResult $cwvDesktop;
    public ?ModuleResult $links;
    public ?ModuleResult $images;
    public string $strategy;
    public int $analyzedAt;

    public function __construct(
        string $url,
        int $globalScore,
        ?ModuleResult $html,
        ?ModuleResult $keyword,
        ?ModuleResult $schema,
        ?ModuleResult $readability,
        ?ModuleResult $metatags,
        ?ModuleResult $llms,
        ?ModuleResult $aeo,
        ?ModuleResult $geo,
        ?ModuleResult $cwv,
        ?ModuleResult $cwvMobile,
        ?ModuleResult $cwvDesktop,
        ?ModuleResult $links,
        ?ModuleResult $images,
        string $strategy,
        int $analyzedAt
    ) {
        $this->url         = $url;
        $this->globalScore = $globalScore;
        $this->html        = $html;
        $this->keyword     = $keyword;
        $this->schema      = $schema;
        $this->readability = $readability;
        $this->metatags    = $metatags;
        $this->llms        = $llms;
        $this->aeo         = $aeo;
        $this->geo         = $geo;
        $this->cwv         = $cwv;
        $this->cwvMobile   = $cwvMobile;
        $this->cwvDesktop  = $cwvDesktop;
        $this->links       = $links;
        $this->images      = $images;
        $this->strategy    = $strategy;
        $this->analyzedAt = $analyzedAt;
    }

    /**
     * Convert to array for compatibility with existing JS frontend.
     *
     * @return array
     */
    public function toArray(): array {
        $result = [
            'url'         => $this->url,
            'global_score' => $this->globalScore,
            'strategy'    => $this->strategy,
            'analyzed_at' => $this->analyzedAt,
        ];

        // Add module results (only if not null)
        if ( $this->html !== null ) {
            $result['html'] = $this->html->toArray();
        }
        if ( $this->keyword !== null ) {
            $result['keyword'] = $this->keyword->toArray();
        }
        if ( $this->schema !== null ) {
            $result['schema'] = $this->schema->toArray();
        }
        if ( $this->readability !== null ) {
            $result['readability'] = $this->readability->toArray();
        }
        if ( $this->metatags !== null ) {
            $result['metatags'] = $this->metatags->toArray();
        }
        if ( $this->llms !== null ) {
            $result['llms'] = $this->llms->toArray();
        }
        if ( $this->aeo !== null ) {
            $result['aeo'] = $this->aeo->toArray();
        }
        if ( $this->geo !== null ) {
            $result['geo'] = $this->geo->toArray();
        }
        if ( $this->cwv !== null ) {
            $result['cwv'] = $this->cwv->toArray();
        }
        if ( $this->cwvMobile !== null ) {
            $result['cwv_mobile'] = $this->cwvMobile->toArray();
        }
        if ( $this->cwvDesktop !== null ) {
            $result['cwv_desktop'] = $this->cwvDesktop->toArray();
        }
        if ( $this->links !== null ) {
            $result['links'] = $this->links->toArray();
        }
        if ( $this->images !== null ) {
            $result['images'] = $this->images->toArray();
        }

        return $result;
    }

    /**
     * Create AnalysisResult from array (for reconstruction from transients/post meta).
     *
     * @param array $data
     * @return self
     */
    public static function fromArray( array $data ): self {
        return new self(
            url:         $data['url'] ?? '',
            globalScore: $data['global_score'] ?? 0,
            html:        isset( $data['html'] ) ? ModuleResult::fromArray( $data['html'] ) : null,
            keyword:     isset( $data['keyword'] ) ? ModuleResult::fromArray( $data['keyword'] ) : null,
            schema:      isset( $data['schema'] ) ? ModuleResult::fromArray( $data['schema'] ) : null,
            readability: isset( $data['readability'] ) ? ModuleResult::fromArray( $data['readability'] ) : null,
            metatags:    isset( $data['metatags'] ) ? ModuleResult::fromArray( $data['metatags'] ) : null,
            llms:        isset( $data['llms'] ) ? ModuleResult::fromArray( $data['llms'] ) : null,
            aeo:         isset( $data['aeo'] ) ? ModuleResult::fromArray( $data['aeo'] ) : null,
            geo:         isset( $data['geo'] ) ? ModuleResult::fromArray( $data['geo'] ) : null,
            cwv:         isset( $data['cwv'] ) ? ModuleResult::fromArray( $data['cwv'] ) : null,
            cwvMobile:   isset( $data['cwv_mobile'] ) ? ModuleResult::fromArray( $data['cwv_mobile'] ) : null,
            cwvDesktop:  isset( $data['cwv_desktop'] ) ? ModuleResult::fromArray( $data['cwv_desktop'] ) : null,
            links:       isset( $data['links'] ) ? ModuleResult::fromArray( $data['links'] ) : null,
            images:      isset( $data['images'] ) ? ModuleResult::fromArray( $data['images'] ) : null,
            strategy:    $data['strategy'] ?? 'direct',
            analyzedAt:  $data['analyzed_at'] ?? time(),
        );
    }
}
