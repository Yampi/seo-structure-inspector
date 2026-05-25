<?php
/**
 * SEOSI\Templates\ReversionPage
 * Reversion / Rollback Control Panel Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch user preferred UI theme for consistency
$options = get_option( 'seosi_options', [] );
$theme   = $options['ui_theme'] ?? 'dark';
?>
<div class="seoi-dashboard-root" data-theme="<?php echo esc_attr( $theme ); ?>">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L20 7V17L12 22L4 17V7L12 2Z" stroke="white" stroke-width="1.5" fill="url(#seosi-logo-grad)"/>
                    <defs>
                        <linearGradient id="seosi-logo-grad" x1="4" y1="2" x2="20" y2="22" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#4f8ef7"/>
                            <stop offset="1" stop-color="#9b72e8"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="logo-text">
                <strong>SEO</strong>
                <span>Structure Inspector</span>
            </div>
        </div>

        <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-structure-inspector' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">📊</span> <?php esc_html_e( 'Panel de Control', 'seo-si' ); ?></span>
        </a>
        <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=seosi-glossary' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">📖</span> <?php esc_html_e( 'Glosario', 'seo-si' ); ?></span>
        </a>
        <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=seosi-settings' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">⚙️</span> <?php esc_html_e( 'Configuración', 'seo-si' ); ?></span>
        </a>
        
        <div class="nav-divider"></div>

        <a class="nav-item active" href="<?php echo esc_url( admin_url( 'admin.php?page=seosi-reversion' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">🛡️</span> <?php esc_html_e( 'Control de Cambios', 'seo-si' ); ?></span>
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main">
        <header class="topbar">
            <h2 style="font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; margin: 0; color: var(--text-primary);">
                <?php esc_html_e( 'Control de Cambios y Reversión de Mejoras', 'seo-si' ); ?>
            </h2>
        </header>

        <div class="content" style="padding: 24px; overflow-y: auto; max-height: calc(100vh - 80px);">
            <div class="seosi-reversion-container" style="max-width: 1000px; margin: 0 auto;">
                
                <!-- Information Alert Box -->
                <div class="seosi-alert-card info-card" style="margin-bottom: 24px; background: rgba(79, 142, 247, 0.08); border-left: 4px solid var(--accent-purple); padding: 18px; border-radius: var(--radius-sm);">
                    <p style="margin: 0; color: var(--text-secondary); font-size: 14px; line-height: 1.6;">
                        <strong><?php esc_html_e( '¿Cómo funciona?', 'seo-si' ); ?></strong> <?php esc_html_e( 'Esta herramienta te permite identificar cada optimización SEO aplicada de forma automática o manual en tu base de datos (meta descripciones, esquemas estructurados, resúmenes TL;DR, etc.). Desde aquí puedes deshacer los cambios de forma individual por página, o realizar una purga total para devolver tu sitio a su estado original si encuentras algún conflicto o error de compatibilidad.', 'seo-si' ); ?>
                    </p>
                </div>

                <!-- Stats Overview Deck -->
                <div class="dashboard-grid-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="stat-card" style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700;"><?php esc_html_e( 'Páginas con Metadatos Inyectados', 'seo-si' ); ?></h4>
                            <div class="stat-value" id="stat-posts" style="font-size: 28px; font-weight: 800; color: var(--text-primary);">0</div>
                        </div>
                        <p style="margin: 15px 0 0 0; font-size: 12px; color: var(--text-muted);"><?php esc_html_e( 'Entradas y páginas con optimizaciones guardadas.', 'seo-si' ); ?></p>
                    </div>

                    <div class="stat-card" style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700;"><?php esc_html_e( 'URLs con Anulaciones Globales', 'seo-si' ); ?></h4>
                            <div class="stat-value" id="stat-urls" style="font-size: 28px; font-weight: 800; color: var(--text-primary);">0</div>
                        </div>
                        <p style="margin: 15px 0 0 0; font-size: 12px; color: var(--text-muted);"><?php esc_html_e( 'URLs personalizadas externamente.', 'seo-si' ); ?></p>
                    </div>

                    <div class="stat-card" style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700;"><?php esc_html_e( 'Correcciones Estructurales', 'seo-si' ); ?></h4>
                            <div class="stat-value" id="stat-structural" style="font-size: 18px; font-weight: 700; color: var(--accent-orange); margin-top: 5px;"><?php esc_html_e( 'Cargando...', 'seo-si' ); ?></div>
                        </div>
                        <p style="margin: 15px 0 0 0; font-size: 12px; color: var(--text-muted);"><?php esc_html_e( 'Reescritura semántica HTML dinámica.', 'seo-si' ); ?></p>
                    </div>
                </div>

                <!-- Main Section: Granular Applied Fixes Table -->
                <div class="card" style="background: var(--bg-card); border-radius: var(--radius-sm); border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                        <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.03em;"><?php esc_html_e( 'Registro de Correcciones Activas', 'seo-si' ); ?></h3>
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-refresh" style="cursor: pointer; padding: 6px 14px; font-size: 12px; font-family: var(--font-main); background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: var(--radius-xs); transition: all 0.2s;">🔄 <?php esc_html_e( 'Actualizar Registro', 'seo-si' ); ?></button>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="widefat fixed striped" style="width: 100%; border-collapse: collapse; text-align: left;" id="table-fixes">
                            <thead>
                                <tr style="border-bottom: 1.5px solid var(--border-color);">
                                    <th style="padding: 12px; color: var(--text-muted); font-size: 12px; font-weight: 700; width: 130px;"><?php esc_html_e( 'Ámbito', 'seo-si' ); ?></th>
                                    <th style="padding: 12px; color: var(--text-muted); font-size: 12px; font-weight: 700;"><?php esc_html_e( 'Destino / URL / Título del Post', 'seo-si' ); ?></th>
                                    <th style="padding: 12px; color: var(--text-muted); font-size: 12px; font-weight: 700;"><?php esc_html_e( 'Ajustes Guardados', 'seo-si' ); ?></th>
                                    <th style="padding: 12px; color: var(--text-muted); font-size: 12px; font-weight: 700; width: 110px; text-align: right;"><?php esc_html_e( 'Acciones', 'seo-si' ); ?></th>
                                </tr>
                            </thead>
                            <tbody id="table-body-fixes">
                                <tr>
                                    <td colspan="4" style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13px;"><?php esc_html_e( 'Cargando listado de correcciones...', 'seo-si' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Danger Zone: Bulk Purge and Reset Panel -->
                <div class="card critical-card" style="background: linear-gradient(180deg, var(--bg-card) 0%, rgba(239, 68, 68, 0.02) 100%); border-radius: var(--radius); border: 1px solid rgba(239, 68, 68, 0.35); padding: 28px; border-left: 5px solid var(--red); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05), 0 0 12px rgba(239, 68, 68, 0.05); margin-top: 32px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 700; color: var(--red); display: flex; align-items: center; gap: 8px; font-family: var(--font-main);">
                        <span class="dashicons dashicons-warning" style="font-size: 20px; width: 20px; height: 20px; color: var(--red);"></span>
                        <?php esc_html_e( 'Zona de Peligro: Restablecimiento y Purga Completa', 'seo-si' ); ?>
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 13.5px; line-height: 1.6; margin-bottom: 24px; font-family: var(--font-main);">
                        <?php esc_html_e( 'Si deseas revertir todas las optimizaciones de SEO aplicadas y restaurar el sitio a su estado original, esta herramienta eliminará de forma irreversible todas las correcciones activas, esquemas estructurados, resúmenes TL;DR y meta descripciones inyectadas. Tu tema y base de datos quedarán 100% libres de modificaciones aplicadas por el plugin. (Nota: Esta opción no desinstala ni desactiva el plugin, únicamente purga los datos optimizados y revierte los cambios aplicados).', 'seo-si' ); ?>
                    </p>

                    <!-- History Purge Checkbox -->
                    <div style="background: rgba(239, 68, 68, 0.01); border: 1px solid var(--border); padding: 16px 20px; border-radius: var(--radius-sm); margin-bottom: 24px; transition: all 0.2s ease;" onmouseover="this.style.background='rgba(239, 68, 68, 0.03)'; this.style.borderColor='rgba(239, 68, 68, 0.2)';" onmouseout="this.style.background='rgba(239, 68, 68, 0.01)'; this.style.borderColor='var(--border)';">
                        <label style="display: flex; align-items: center; color: var(--text-primary); font-size: 13.5px; cursor: pointer; font-weight: 600; width: 100%; user-select: none;">
                            <input type="checkbox" id="check-purge-history" style="margin-right: 12px; cursor: pointer; transform: scale(1.1); margin-top: 0;">
                            <?php esc_html_e( 'Eliminar también el Historial de Auditorías e Informes pasados (Snapshots del Auditor)', 'seo-si' ); ?>
                        </label>
                    </div>

                    <!-- Secure Text-Confirm Form -->
                    <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
                        <div style="position: relative; flex: 1; min-width: 280px; max-width: 400px;">
                            <input type="text" id="input-confirm-purge" placeholder="<?php esc_attr_e( 'Escribe PURGAR para confirmar', 'seo-si' ); ?>" style="background: var(--bg-secondary); border: 1.5px solid var(--border); color: var(--text-primary); padding: 12px 16px; border-radius: var(--radius-sm); outline: none; font-size: 13.5px; font-family: var(--font-main); width: 100%; transition: all 0.2s ease; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);" onfocus="this.style.borderColor='var(--red)'; this.style.boxShadow='0 0 0 3px rgba(239, 68, 68, 0.15), inset 0 2px 4px rgba(0,0,0,0.02)';" onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='inset 0 2px 4px rgba(0,0,0,0.02)';">
                        </div>
                        <button type="button" class="btn btn-danger" id="btn-purge-all" style="background: var(--red); color: #fff; padding: 12px 26px; font-weight: 700; font-size: 13.5px; border: none; border-radius: var(--radius-sm); cursor: not-allowed; font-family: var(--font-main); transition: all 0.2s ease; opacity: 0.4; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);" disabled>
                            <span class="dashicons dashicons-trash" style="font-size: 18px; width: 18px; height: 18px;"></span>
                            <?php esc_html_e( 'Purgar Todas las Correcciones', 'seo-si' ); ?>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var nonce = '<?php echo wp_create_nonce( "seosi_nonce" ); ?>';

    function loadFixes() {
        $('#table-body-fixes').html('<tr><td colspan="4" style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13px;">🔄 <?php esc_html_e( 'Escaneando cambios activos...', 'seo-si' ); ?></td></tr>');
        
        $.post(ajaxurl, {
            action: 'seosi_get_applied_fixes',
            nonce: nonce
        }, function(res) {
            if (res.success) {
                var summary = res.data.summary;
                var details = res.data.details;

                // Update Overview counters
                $('#stat-posts').text(summary.posts_modified);
                $('#stat-urls').text(summary.url_overrides);
                
                if (summary.structural_active) {
                    $('#stat-structural').text('<?php esc_html_e( 'Activo (Frontend)', 'seo-si' ); ?>').css('color', 'var(--accent-green)');
                } else {
                    $('#stat-structural').text('<?php esc_html_e( 'Inactivo', 'seo-si' ); ?>').css('color', 'var(--text-muted)');
                }

                // Render detail table content
                if (details.length === 0) {
                    $('#table-body-fixes').html('<tr><td colspan="4" style="padding: 32px; text-align: center; color: var(--text-muted); font-size: 13px;">🎉 <?php esc_html_e( 'No hay correcciones o anulaciones activas en la base de datos.', 'seo-si' ); ?></td></tr>');
                    return;
                }

                var html = '';
                details.forEach(function(item) {
                    var typeLabel = item.type === 'url_override' ? '<?php esc_html_e( 'URL Override', 'seo-si' ); ?>' : '<?php esc_html_e( 'Post Meta', 'seo-si' ); ?>';
                    var typeBadgeClass = item.type === 'url_override' ? 'background: rgba(155, 114, 232, 0.12); color: var(--accent-purple); border: 1px solid rgba(155, 114, 232, 0.2);' : 'background: rgba(79, 142, 247, 0.12); color: var(--accent-blue); border: 1px solid rgba(79, 142, 247, 0.2);';
                    
                    // Format active key details recursively
                    var keysFormatted = item.details.map(function(k) {
                        var cleanKey = k.replace('_seosi_', '');
                        return '<code style="font-size: 11px; margin-right: 4px; margin-bottom: 4px; display: inline-block; background: rgba(255,255,255,0.06); border: 1px solid var(--border-color); padding: 1px 5px; border-radius: 4px; color: var(--text-secondary);">' + cleanKey + '</code>';
                    }).join(' ');

                    html += '<tr style="border-bottom: 1px solid var(--border-color);">';
                    html += '<td style="padding: 12px 8px;"><span style="display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; ' + typeBadgeClass + '">' + typeLabel + '</span></td>';
                    html += '<td style="padding: 12px 8px; font-weight: 600; color: var(--text-primary); font-size: 13px; word-break: break-all;">' + item.target + '</td>';
                    html += '<td style="padding: 12px 8px; line-height: 1.6;">' + keysFormatted + '</td>';
                    html += '<td style="padding: 12px 8px; text-align: right;">';
                    html += '<button type="button" class="btn btn-secondary btn-sm btn-revert" data-type="' + item.type + '" data-target="' + item.target + '" data-postid="' + item.post_id + '" style="cursor: pointer; padding: 4px 10px; font-size: 11px; font-family: var(--font-main); background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: var(--radius-xs); transition: all 0.2s;"><?php esc_html_e( 'Deshacer', 'seo-si' ); ?></button>';
                    html += '</td>';
                    html += '</tr>';
                });

                $('#table-body-fixes').html(html);
            } else {
                $('#table-body-fixes').html('<tr><td colspan="4" style="padding: 24px; text-align: center; color: var(--accent-red); font-size: 13px;">❌ ' + res.data.message + '</td></tr>');
            }
        }).fail(function() {
            $('#table-body-fixes').html('<tr><td colspan="4" style="padding: 24px; text-align: center; color: var(--accent-red); font-size: 13px;">❌ <?php esc_html_e( 'Error de comunicación con el servidor.', 'seo-si' ); ?></td></tr>');
        });
    }

    // Load initial data list
    loadFixes();

    $('#btn-refresh').on('click', function() {
        loadFixes();
    });

    // Revert single page/URL optimization
    $(document).on('click', '.btn-revert', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var type = $btn.data('type');
        var target = $btn.data('target');
        var postid = $btn.data('postid');

        if (!confirm('<?php esc_html_e( '¿Estás seguro de que deseas eliminar permanentemente todas las mejoras de SEO inyectadas en "', 'seo-si' ); ?>' + target + '"?')) {
            return;
        }

        $btn.text('<?php esc_html_e( 'Eliminando...', 'seo-si' ); ?>').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'seosi_revert_single_fix',
            type: type,
            target: target,
            post_id: postid,
            nonce: nonce
        }, function(res) {
            if (res.success) {
                loadFixes();
            } else {
                alert('<?php esc_html_e( 'Error:', 'seo-si' ); ?> ' + res.data.message);
                $btn.text('<?php esc_html_e( 'Deshacer', 'seo-si' ); ?>').prop('disabled', false);
            }
        }).fail(function() {
            alert('<?php esc_html_e( 'Error de conexión.', 'seo-si' ); ?>');
            $btn.text('<?php esc_html_e( 'Deshacer', 'seo-si' ); ?>').prop('disabled', false);
        });
    });

    // Enable/Disable bulk purge button based on confirm code text match
    $('#input-confirm-purge').on('input', function() {
        var val = $(this).val().trim().toLowerCase();
        if (val === 'purgar') {
            $('#btn-purge-all').prop('disabled', false).css({
                'cursor': 'pointer',
                'opacity': '1'
            });
        } else {
            $('#btn-purge-all').prop('disabled', true).css({
                'cursor': 'not-allowed',
                'opacity': '0.4'
            });
        }
    });

    // Execute Bulk Reset / Recovery operation
    $('#btn-purge-all').on('click', function(e) {
        e.preventDefault();
        var confirmText = $('#input-confirm-purge').val().trim();
        var purgeHistory = $('#check-purge-history').is(':checked');

        if (confirmText.toLowerCase() !== 'purgar') {
            return;
        }

        if (!confirm('⚠️ <?php esc_html_e( '¡ADVERTENCIA CRÍTICA!\n\nEstás a punto de eliminar de forma masiva y definitiva ABSOLUTAMENTE TODAS las correcciones de SEO aplicadas en este sitio.\n\nEsta acción no se puede deshacer y devolverá la base de datos a su estado original predeterminado. ¿Deseas continuar?', 'seo-si' ); ?>')) {
            return;
        }

        var $btn = $(this);
        $btn.text('<?php esc_html_e( 'Purgando base de datos...', 'seo-si' ); ?>').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'seosi_revert_all_fixes',
            confirm_code: confirmText,
            purge_history: purgeHistory,
            nonce: nonce
        }, function(res) {
            if (res.success) {
                alert(res.data.message);
                $('#input-confirm-purge').val('');
                $btn.text('⚠️ <?php esc_html_e( 'Purgar Todas las Correcciones', 'seo-si' ); ?>').prop('disabled', true).css('opacity', '0.5');
                loadFixes();
            } else {
                alert('<?php esc_html_e( 'Error:', 'seo-si' ); ?> ' + res.data.message);
                $btn.text('⚠️ <?php esc_html_e( 'Purgar Todas las Correcciones', 'seo-si' ); ?>').prop('disabled', false);
            }
        }).fail(function() {
            alert('<?php esc_html_e( 'Error de comunicación durante la purga.', 'seo-si' ); ?>');
            $btn.text('⚠️ <?php esc_html_e( 'Purgar Todas las Correcciones', 'seo-si' ); ?>').prop('disabled', false);
        });
    });
});
</script>
