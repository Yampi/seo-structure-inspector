<?php

namespace SEOSI\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class PoMoCompiler {
    public static function ensure_compiled( string $textdomain, string $po_path, string $locale ): void {
        if ( ! file_exists( $po_path ) ) return;

        $mo_name = $textdomain . '-' . $locale . '.mo';
        $target_dir = defined( 'WP_LANG_DIR' ) ? ( WP_LANG_DIR . '/plugins' ) : '';
        if ( $target_dir === '' ) return;

        if ( ! is_dir( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
        }

        $mo_path = rtrim( $target_dir, '/\\' ) . DIRECTORY_SEPARATOR . $mo_name;

        $po_mtime = (int) @filemtime( $po_path );
        $mo_mtime = (int) @filemtime( $mo_path );
        if ( $mo_mtime >= $po_mtime && $mo_mtime > 0 ) return;

        $map = self::parse_po( $po_path );
        if ( empty( $map ) ) return;

        $mo = self::build_mo( $map );
        if ( $mo === '' ) return;

        @file_put_contents( $mo_path, $mo );
    }

    private static function parse_po( string $path ): array {
        $lines = @file( $path, FILE_IGNORE_NEW_LINES );
        if ( ! is_array( $lines ) ) return [];

        $entries = [];
        $msgid = null;
        $msgstr = null;
        $state = null;

        foreach ( $lines as $line ) {
            $line = (string) $line;

            if ( $line === '' ) {
                if ( $msgid !== null && $msgstr !== null ) {
                    $entries[ $msgid ] = $msgstr;
                }
                $msgid = null;
                $msgstr = null;
                $state = null;
                continue;
            }

            if ( str_starts_with( $line, '#~' ) ) continue;
            if ( str_starts_with( $line, '#' ) ) continue;

            if ( str_starts_with( $line, 'msgid "' ) ) {
                $msgid = self::po_unquote( substr( $line, 6 ) );
                $state = 'msgid';
                continue;
            }

            if ( str_starts_with( $line, 'msgstr "' ) ) {
                $msgstr = self::po_unquote( substr( $line, 7 ) );
                $state = 'msgstr';
                continue;
            }

            if ( $state === 'msgid' && str_starts_with( $line, '"' ) ) {
                $msgid .= self::po_unquote( $line );
                continue;
            }

            if ( $state === 'msgstr' && str_starts_with( $line, '"' ) ) {
                $msgstr .= self::po_unquote( $line );
                continue;
            }
        }

        if ( $msgid !== null && $msgstr !== null ) {
            $entries[ $msgid ] = $msgstr;
        }

        return $entries;
    }

    private static function po_unquote( string $s ): string {
        $s = trim( $s );
        if ( $s === '' ) return '';
        if ( $s[0] === '"' ) $s = substr( $s, 1 );
        if ( $s !== '' && substr( $s, -1 ) === '"' ) $s = substr( $s, 0, -1 );
        return stripcslashes( $s );
    }

    private static function build_mo( array $entries ): string {
        ksort( $entries, SORT_STRING );

        $ids = [];
        $strs = [];
        foreach ( $entries as $id => $str ) {
            $ids[] = (string) $id;
            $strs[] = (string) $str;
        }

        $count = count( $ids );
        $header_size = 28;
        $orig_table_offset = $header_size;
        $trans_table_offset = $orig_table_offset + ( $count * 8 );
        $hash_offset = $trans_table_offset + ( $count * 8 );
        $current_offset = $hash_offset;

        $orig_table = '';
        $trans_table = '';
        $orig_strings = '';
        $trans_strings = '';

        $orig_offsets = [];
        foreach ( $ids as $id ) {
            $len = strlen( $id );
            $orig_offsets[] = [ $len, $current_offset ];
            $orig_strings .= $id . "\0";
            $current_offset += $len + 1;
        }

        $trans_offsets = [];
        foreach ( $strs as $str ) {
            $len = strlen( $str );
            $trans_offsets[] = [ $len, $current_offset ];
            $trans_strings .= $str . "\0";
            $current_offset += $len + 1;
        }

        foreach ( $orig_offsets as [ $len, $off ] ) {
            $orig_table .= pack( 'V2', $len, $off );
        }
        foreach ( $trans_offsets as [ $len, $off ] ) {
            $trans_table .= pack( 'V2', $len, $off );
        }

        $out = '';
        $out .= pack( 'V', 0x950412de );
        $out .= pack( 'V', 0 );
        $out .= pack( 'V', $count );
        $out .= pack( 'V', $orig_table_offset );
        $out .= pack( 'V', $trans_table_offset );
        $out .= pack( 'V', 0 );
        $out .= pack( 'V', 0 );

        $out .= $orig_table;
        $out .= $trans_table;
        $out .= $orig_strings;
        $out .= $trans_strings;

        return $out;
    }
}

