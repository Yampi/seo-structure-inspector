<?php
/**
 * BaloaStructureAuditorSEO\Admin\MetaBox
 * Meta box â€” appears inside the post/page editor.
 */

namespace BaloaStructureAuditorSEO\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class MetaBox {

    public static function register_hooks(): void {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_box' ] );
        add_action( 'save_post', [ __CLASS__, 'save_keyword' ] );
    }

    public static function add_meta_box(): void {
        $screens = [ 'post', 'page' ];
        foreach ( $screens as $screen ) {
            add_meta_box(
                'baloa-meta-box',
                'â¬¡ Baloa Structure Auditor for SEO',
                [ __CLASS__, 'render' ],
                $screen,
                'normal',
                'high'
            );
        }
    }

    public static function render( \WP_Post $post ): void {
        $permalink = get_permalink( $post->ID ) ?: '';
        $keyword   = get_post_meta( $post->ID, '_baloa_keyword', true );

        \BaloaStructureAuditorSEO\Core\ViewRenderer::render_echo( 'meta-box', compact( 'permalink', 'keyword' ) );
    }

    public static function save_keyword( int $post_id ): void {
        $raw_nonce = isset( $_POST['baloa_structure_auditor_seo_keyword_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['baloa_structure_auditor_seo_keyword_nonce'] ) ) : '';
        if (
            empty( $raw_nonce ) ||
            ! wp_verify_nonce( $raw_nonce, 'baloa_structure_auditor_seo_save_keyword' )
        ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $keyword     = isset( $_POST['baloa_structure_auditor_seo_keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['baloa_structure_auditor_seo_keyword'] ) ) : '';
        update_post_meta( $post_id, '_baloa_keyword', $keyword );
    }
}
