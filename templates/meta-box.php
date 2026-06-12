<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Template: Meta Box - Modern Design
 * @var string $permalink
 * @var string $keyword
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="baloa-metabox">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⬇ Baloa Structure Auditor for SEO</h3>
        </div>
        <div class="baloa-form-row">
            <input
                type="text"
                id="baloa-keyword-input"
                name="baloa_structure_auditor_seo_keyword"
                class="baloa-input baloa-input--kw"
                placeholder="Palabra clave objetivo"
                value="<?php echo esc_attr( $keyword ); ?>"
                autocomplete="off"
            />
            <button
                id="baloa-analyze-btn"
                class="baloa-btn"
                data-url="<?php echo esc_attr( $permalink ); ?>"
                <?php disabled( ! $permalink ); ?>
            >
                <?php echo $permalink ? esc_html__( 'Analizar', 'baloa-structure-auditor-seo' ) : esc_html__( 'Guarda primero el post', 'baloa-structure-auditor-seo' ); ?>
            </button>
        </div>

        <div id="baloa-results" class="baloa-results" style="display:none;"></div>
        <div id="baloa-loading" class="baloa-loading" style="display:none;">
            <span class="baloa-spinner"></span> Analizando...
        </div>

        <!-- Semantic Schema Builder UI -->
        <div class="baloa-schema-builder-section" style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 16px;">
            <h4 style="margin: 0 0 8px 0; font-family: var(--font-main); font-weight: 600;">Constructor de Esquemas Semánticos (AEO)</h4>
            <p style="font-size: 12px; color: #666; margin: 0 0 8px 0;">Ingresa tu marcado Schema.org JSON-LD (ej. FAQPage o LocalBusiness) para validación automática.</p>
            <textarea 
                id="baloa-schema-json" 
                class="baloa-input" 
                style="width: 100%; height: 120px; font-family: monospace; font-size: 11px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background: #fafafa; color: #333;" 
                placeholder='[{"@type": "LocalBusiness", "name": "Mi negocio"}]'
            ><?php 
                $post_id = get_the_ID();
                $schema_data = get_post_meta( $post_id, '_baloa_json_ld_schema', true ) ?: [];
                echo esc_textarea( json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ); 
            ?></textarea>
            <button 
                type="button" 
                id="baloa-save-schema-btn" 
                class="baloa-btn" 
                style="margin-top: 8px; width: 100%; text-align: center;"
            >
                Validar y Guardar Esquema
            </button>
            <div id="baloa-schema-feedback" style="margin-top: 8px; font-size: 12px; font-weight: 500;"></div>
        </div>
    </div>
</div>
<?php wp_nonce_field( 'baloa_structure_auditor_seo_save_keyword', 'baloa_structure_auditor_seo_keyword_nonce' ); ?>
