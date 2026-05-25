<?php
/**
 * Template: Meta Box - Modern Design
 * @var string $permalink
 * @var string $keyword
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="seosi-metabox">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⬡ SEO Structure Inspector</h3>
        </div>
        <div class="seosi-form-row">
            <input
                type="text"
                id="seosi-keyword-input"
                name="seosi_keyword"
                class="seosi-input seosi-input--kw"
                placeholder="Palabra clave objetivo"
                value="<?php echo esc_attr( $keyword ); ?>"
                autocomplete="off"
            />
            <button
                id="seosi-analyze-btn"
                class="seosi-btn"
                data-url="<?php echo esc_attr( $permalink ); ?>"
                <?php echo $permalink ? '' : 'disabled'; ?>
            >
                <?php echo $permalink ? 'Analizar' : 'Guarda primero el post'; ?>
            </button>
        </div>

        <div id="seosi-results" class="seosi-results" style="display:none;"></div>
        <div id="seosi-loading" class="seosi-loading" style="display:none;">
            <span class="seosi-spinner"></span> Analizando…
        </div>
    </div>
</div>
<?php wp_nonce_field( 'seosi_save_keyword', 'seosi_keyword_nonce' ); ?>
