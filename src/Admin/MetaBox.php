<?php
/**
 * SEOSI\Admin\MetaBox
 * Meta box — appears inside the post/page editor.
 */

namespace SEOSI\Admin;

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
                'seosi-meta-box',
                '⬡ SEO Structure Inspector',
                [ __CLASS__, 'render' ],
                $screen,
                'normal',
                'high'
            );
        }
    }

    public static function render( \WP_Post $post ): void {
        $permalink = get_permalink( $post->ID ) ?: '';
        $keyword   = get_post_meta( $post->ID, '_seosi_keyword', true );

        \SEOSI\Core\ViewRenderer::render_echo( 'meta-box', compact( 'permalink', 'keyword' ) );
    }

    public static function save_keyword( int $post_id ): void {
        if (
            ! isset( $_POST['seosi_keyword_nonce'] ) ||
            ! wp_verify_nonce( $_POST['seosi_keyword_nonce'], 'seosi_save_keyword' )
        ) return;

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $keyword = sanitize_text_field( $_POST['seosi_keyword'] ?? '' );
        update_post_meta( $post_id, '_seosi_keyword', $keyword );
    }
}
