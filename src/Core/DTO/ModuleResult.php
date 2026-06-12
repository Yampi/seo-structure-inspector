<?php
/**
 * BaloaStructureAuditorSEO\Core\DTO\ModuleResult
 * 
 * DTO for the result of a single analysis module.
 * Provides type safety and autocompletion for module results.
 */

namespace BaloaStructureAuditorSEO\Core\DTO;

if ( ! defined( 'ABSPATH' ) ) exit;

readonly class ModuleResult {

    public int $score;
    public array $checks;
    public array $issues;
    public array $warnings;
    public array $passed;
    public array $details;
    public bool $skipped;
    public ?string $error;

    public function __construct(
        int $score,
        array $checks,
        array $issues,
        array $warnings,
        array $passed,
        array $details,
        bool $skipped = false,
        ?string $error = null
    ) {
        $this->score    = $score;
        $this->checks   = $checks;
        $this->issues   = $issues;
        $this->warnings = $warnings;
        $this->passed   = $passed;
        $this->details  = $details;
        $this->skipped  = $skipped;
        $this->error    = $error;
    }

    /**
     * Convert to array for compatibility with existing JS frontend.
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'score'    => $this->score,
            'issues'   => $this->issues,
            'warnings' => $this->warnings,
            'passed'   => $this->passed,
            'checks'   => $this->checks,
            'details'  => $this->details,
            'skipped'  => $this->skipped,
            'error'    => $this->error,
        ];
    }

    /**
     * Create ModuleResult from array (for reconstruction from transients/post meta).
     *
     * @param array $data
     * @return self
     */
    public static function fromArray( array $data ): self {
        return new self(
            score:    $data['score'] ?? 0,
            checks:   $data['checks'] ?? [],
            issues:   $data['issues'] ?? [],
            warnings: $data['warnings'] ?? [],
            passed:   $data['passed'] ?? [],
            details:  $data['details'] ?? [],
            skipped:  $data['skipped'] ?? false,
            error:    $data['error'] ?? null,
        );
    }

    /**
     * Create a skipped result (when module is disabled).
     *
     * @return self
     */
    public static function skipped(): self {
        return new self(
            score: 0,
            checks: [],
            issues: [],
            warnings: [],
            passed: [],
            details: [],
            skipped: true,
            error: null,
        );
    }

    /**
     * Create an error result (when module fails to execute).
     *
     * @param string $error
     * @return self
     */
    public static function error( string $error ): self {
        return new self(
            score: 0,
            checks: [],
            issues: [],
            warnings: [],
            passed: [],
            details: [],
            skipped: false,
            error: $error,
        );
    }
}
