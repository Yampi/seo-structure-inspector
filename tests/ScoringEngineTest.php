<?php
/**
 * Tests for SEOSI\Core\ScoringEngine
 */

namespace BaloaStructureAuditorSEO\Tests;

use PHPUnit\Framework\TestCase;
use BaloaStructureAuditorSEO\Core\ScoringEngine;

class ScoringEngineTest extends TestCase {

    public function test_calculate_score_all_pass(): void {
        $checks = [
            [ 'severity' => 'pass' ],
            [ 'severity' => 'pass' ],
            [ 'severity' => 'pass' ],
        ];

        $score = ScoringEngine::calculate_score( $checks );
        $this->assertEquals( 100, $score );
    }

    public function test_calculate_score_all_error(): void {
        $checks = [
            [ 'severity' => 'error' ],
            [ 'severity' => 'error' ],
            [ 'severity' => 'error' ],
        ];

        $score = ScoringEngine::calculate_score( $checks );
        $this->assertEquals( 0, $score );
    }

    public function test_calculate_score_mixed(): void {
        $checks = [
            [ 'severity' => 'pass' ],
            [ 'severity' => 'pass' ],
            [ 'severity' => 'error' ],
        ];

        $score = ScoringEngine::calculate_score( $checks );
        // 2 pass (2 points) / (2 passes * 1 + 1 error * 2) = 2/4 = 50
        $this->assertEquals( 50, $score );
    }

    public function test_split_checks(): void {
        $checks = [
            [ 'severity' => 'error',   'message' => 'Error 1' ],
            [ 'severity' => 'warning', 'message' => 'Warning 1' ],
            [ 'severity' => 'pass',    'message' => 'Pass 1' ],
        ];

        $result = ScoringEngine::split_checks( $checks );

        $this->assertEquals( [ 'Error 1' ], $result['issues'] );
        $this->assertEquals( [ 'Warning 1' ], $result['warnings'] );
        $this->assertEquals( [ 'Pass 1' ], $result['passed'] );
    }

    public function test_build_result(): void {
        $checks = [
            [ 'id' => 'test', 'severity' => 'pass', 'message' => 'Good' ],
        ];
        $details = [ 'custom' => 'data' ];

        $result = ScoringEngine::build_result( $checks, $details );

        $this->assertEquals( 100, $result->score );
        $this->assertNotEmpty( $result->checks );
        $this->assertEquals( [ 'Good' ], $result->passed );
        $this->assertEquals( $details, $result->details );
    }

    public function test_validate_check_valid(): void {
        $check = [
            'id'       => 'test_check',
            'severity' => 'pass',
            'category' => 'seo',
            'message'  => 'Test message',
        ];

        $this->assertTrue( ScoringEngine::validate_check( $check ) );
    }

    public function test_validate_check_missing_id(): void {
        $check = [
            'severity' => 'pass',
            'category' => 'seo',
            'message'  => 'Test message',
        ];

        $this->assertFalse( ScoringEngine::validate_check( $check ) );
    }

    public function test_validate_check_invalid_severity(): void {
        $check = [
            'id'       => 'test_check',
            'severity' => 'invalid',
            'category' => 'seo',
            'message'  => 'Test message',
        ];

        $this->assertFalse( ScoringEngine::validate_check( $check ) );
    }
}
