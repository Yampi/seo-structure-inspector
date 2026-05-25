<?php

namespace SEOSI\Tests;

use PHPUnit\Framework\TestCase;
use SEOSI\Core\CheckPresenter;

class CheckPresenterTest extends TestCase {
    public function test_apply_en_enriches_known_check(): void {
        $checks = [
            [
                'id'       => 'single_h1',
                'severity' => 'pass',
                'category' => 'seo',
                'context'  => [ 'count' => 1, 'value' => 'Hello World' ],
            ],
        ];

        $out = CheckPresenter::apply_en( $checks );

        $this->assertNotEmpty( $out[0]['message'] );
        $this->assertStringContainsString( 'un único H1', $out[0]['message'] );
        $this->assertEquals( 'Encabezado principal único (<h1>)', $out[0]['title'] );
    }

    public function test_apply_en_sets_recommendation_when_how_exists(): void {
        $checks = [
            [
                'id'       => 'meta_title',
                'severity' => 'error',
                'category' => 'seo',
                'context'  => [ 'length' => 0, 'value' => '' ],
            ],
        ];

        $out = CheckPresenter::apply_en( $checks );

        $this->assertNotEmpty( $out[0]['recommendation'] );
        $this->assertStringContainsString( 'títulos atractivos', $out[0]['recommendation'] );
    }

    public function test_apply_en_leaves_unknown_checks_unchanged(): void {
        $checks = [
            [
                'id'       => 'unknown_check',
                'severity' => 'warning',
                'category' => 'seo',
                'message'  => 'Original message',
            ],
        ];

        $out = CheckPresenter::apply_en( $checks );
        $this->assertEquals( 'Original message', $out[0]['message'] );
    }
}

