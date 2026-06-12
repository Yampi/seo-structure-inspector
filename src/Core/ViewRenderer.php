<?php
/**
 * BaloaStructureAuditorSEO\Core\ViewRenderer
 * Handles template rendering for admin components.
 */

namespace BaloaStructureAuditorSEO\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class ViewRenderer {

    /**
     * Render a template file.
     *
     * @param string $template Template name (without .php extension).
     * @param array  $data     Data to pass to the template.
     * @return string Rendered template.
     */
    public static function render( string $template, array $data = [] ): string {
        $template_path = BALOA_STRUCTURE_AUDITOR_SEO_DIR . 'templates/' . $template . '.php';

        if ( ! file_exists( $template_path ) ) {
            return '';
        }

        extract( $data );
        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Render a template and echo it.
     *
     * @param string $template Template name.
     * @param array  $data     Data to pass to the template.
     */
    public static function render_echo( string $template, array $data = [] ): void {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::render( $template, $data );
    }
}
