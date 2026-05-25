<?php

namespace SEOSI\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class CheckPresenter {
    public static function apply_en( array $checks ): array {
        return self::apply( $checks, static fn( string $s ): string => $s );
    }

    public static function apply_i18n( array $checks ): array {
        return self::apply( $checks, static fn( string $s ): string => __( $s, 'seo-si' ) );
    }

    public static function apply_with( array $checks, callable $t ): array {
        return self::apply( $checks, $t );
    }

    private static function apply( array $checks, callable $t ): array {
        $out = [];

        foreach ( $checks as $check ) {
            if ( ! is_array( $check ) ) continue;

            $presented = CheckCatalog::present( $check, $t );
            if ( $presented === null ) {
                $out[] = $check;
                continue;
            }

            $how = is_array( $presented['how'] ?? null ) ? $presented['how'] : [];

            $enriched = $check;
            $enriched['title']   = $presented['title'] ?? ( $enriched['title'] ?? '' );
            $enriched['problem'] = $presented['problem'] ?? ( $enriched['problem'] ?? '' );
            $enriched['why']     = $presented['why'] ?? ( $enriched['why'] ?? '' );
            $enriched['how']     = $how;

            if ( empty( $enriched['message'] ) && ! empty( $enriched['problem'] ) ) {
                $enriched['message'] = $enriched['problem'];
            }

            if ( empty( $enriched['recommendation'] ) && ! empty( $how ) ) {
                $enriched['recommendation'] = implode( ' ', array_values( $how ) );
            }

            $out[] = $enriched;
        }

        return $out;
    }
}
