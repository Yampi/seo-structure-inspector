<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * BaloaStructureAuditorSEO\Templates\ReversionPage
 * Reversion / Rollback Control Panel Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch user preferred UI theme for consistency
$bsa_options = get_option( 'baloa_structure_auditor_seo_options', [] );
$bsa_theme   = $bsa_options['ui_theme'] ?? 'dark';
?>
<div class="seoi-dashboard-root" data-theme="<?php echo esc_attr( $bsa_theme ); ?>">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon" aria-hidden="true">
                <svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Borde Hexagonal Blanco -->
                    <path d="M12 2.5L21.5 8V16L12 21.5L2.5 16V8L12 2.5Z" fill="#111422" stroke="#ffffff" stroke-width="1.5" stroke-linejoin="round" />
                    
                    <!-- Detalle en el vértice inferior del hexágono -->
                    <circle cx="12" cy="21.5" r="0.6" fill="#ffffff" />
                    
                    <!-- Orejas del Búho -->
                    <path d="M7.5 8.5L4.5 5L9 6.5" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8" stroke-linejoin="round" />
                    <path d="M16.5 8.5L19.5 5L15 6.5" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8" stroke-linejoin="round" />
                    
                    <!-- Cuerpo/Cabeza Circular del Búho -->
                    <circle cx="12" cy="13.5" r="6.2" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8" />
                    
                    <!-- Ojos Grandes -->
                    <circle cx="9.3" cy="12.5" r="2.2" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8" />
                    <circle cx="14.7" cy="12.5" r="2.2" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8" />
                    
                    <!-- Pupilas Celestes Circulares -->
                    <circle cx="9.3" cy="12.5" r="1.1" fill="#06b6d4" />
                    <circle cx="14.7" cy="12.5" r="1.1" fill="#06b6d4" />
                    
                    <!-- Pico (Naranja) -->
                    <path d="M11.2 13.2H12.8L12 14.6Z" fill="#f59e0b" />
                    
                    <defs>
                        <linearGradient id="baloa-logo-grad" x1="4" y1="2" x2="20" y2="22" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#4f8ef7"/>
                            <stop offset="1" stop-color="#9b72e8"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="logo-text">
                <strong>BALOA</strong>
                <span>Structure Auditor</span>
            </div>
        </div>

        <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=baloa-structure-auditor-seo' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">📊</span> <?php esc_html_e( 'Panel de Control', 'baloa-structure-auditor-seo' ); ?></span>
        </a>
        <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=baloa-glossary' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">📖</span> <?php esc_html_e( 'Glosario', 'baloa-structure-auditor-seo' ); ?></span>
        </a>
        <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=baloa-settings' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">⚙️</span> <?php esc_html_e( 'Configuración', 'baloa-structure-auditor-seo' ); ?></span>
        </a>
        
        <div class="nav-divider"></div>

        <a class="nav-item active" href="<?php echo esc_url( admin_url( 'admin.php?page=baloa-reversion' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">🛡️</span> <?php esc_html_e( 'Control de Cambios', 'baloa-structure-auditor-seo' ); ?></span>
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main">
        <header class="topbar">
            <h2 style="font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; margin: 0; color: var(--text-primary);">
                <?php esc_html_e( 'Control de Cambios y Reversión de Mejoras', 'baloa-structure-auditor-seo' ); ?>
            </h2>
        </header>

        <div class="content" style="padding: 24px; overflow-y: auto; max-height: calc(100vh - 80px);">
            <div class="baloa-reversion-container" style="max-width: 1000px; margin: 0 auto;">
                
                <!-- Information Alert Box -->
                <div class="baloa-alert-card info-card" style="margin-bottom: 24px; background: rgba(79, 142, 247, 0.08); border-left: 4px solid var(--accent-purple); padding: 18px; border-radius: var(--radius-sm);">
                    <p style="margin: 0; color: var(--text-secondary); font-size: 14px; line-height: 1.6;">
                        <strong><?php esc_html_e( '¿Cómo funciona?', 'baloa-structure-auditor-seo' ); ?></strong> <?php esc_html_e( 'Esta herramienta te permite identificar cada optimización SEO aplicada de forma automática o manual en tu base de datos (meta descripciones, esquemas estructurados, resúmenes TL;DR, etc.). Desde aquí puedes deshacer los cambios de forma individual por página, o realizar una purga total para devolver tu sitio a su estado original si encuentras algún conflicto o error de compatibilidad.', 'baloa-structure-auditor-seo' ); ?>
                    </p>
                </div>

                <!-- Stats Overview Deck -->
                <div class="dashboard-grid-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="stat-card" style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700;"><?php esc_html_e( 'Páginas con Metadatos Inyectados', 'baloa-structure-auditor-seo' ); ?></h4>
                            <div class="stat-value" id="stat-posts" style="font-size: 28px; font-weight: 800; color: var(--text-primary);">0</div>
                        </div>
                        <p style="margin: 15px 0 0 0; font-size: 12px; color: var(--text-muted);"><?php esc_html_e( 'Entradas y páginas con optimizaciones guardadas.', 'baloa-structure-auditor-seo' ); ?></p>
                    </div>

                    <div class="stat-card" style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700;"><?php esc_html_e( 'URLs con Anulaciones Globales', 'baloa-structure-auditor-seo' ); ?></h4>
                            <div class="stat-value" id="stat-urls" style="font-size: 28px; font-weight: 800; color: var(--text-primary);">0</div>
                        </div>
                        <p style="margin: 15px 0 0 0; font-size: 12px; color: var(--text-muted);"><?php esc_html_e( 'URLs personalizadas externamente.', 'baloa-structure-auditor-seo' ); ?></p>
                    </div>

                    <div class="stat-card" style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700;"><?php esc_html_e( 'Correcciones Estructurales', 'baloa-structure-auditor-seo' ); ?></h4>
                            <div class="stat-value" id="stat-structural" style="font-size: 18px; font-weight: 700; color: var(--accent-orange); margin-top: 5px;"><?php esc_html_e( 'Cargando...', 'baloa-structure-auditor-seo' ); ?></div>
                        </div>
                        <p style="margin: 15px 0 0 0; font-size: 12px; color: var(--text-muted);"><?php esc_html_e( 'Reescritura semántica HTML dinámica.', 'baloa-structure-auditor-seo' ); ?></p>
                    </div>
                </div>

                <!-- Main Section: Granular Applied Fixes Table -->
                <div class="card" style="background: var(--bg-card); border-radius: var(--radius-sm); border: 1px solid var(--border-color); padding: 24px; margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                        <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.03em;"><?php esc_html_e( 'Registro de Correcciones Activas', 'baloa-structure-auditor-seo' ); ?></h3>
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-refresh" style="cursor: pointer; padding: 6px 14px; font-size: 12px; font-family: var(--font-main); background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: var(--radius-xs); transition: all 0.2s;">🔄 <?php esc_html_e( 'Actualizar Registro', 'baloa-structure-auditor-seo' ); ?></button>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="widefat fixed striped" style="width: 100%; border-collapse: collapse; text-align: left;" id="table-fixes">
                            <thead>
                                <tr style="border-bottom: 1.5px solid var(--border-color);">
                                    <th style="padding: 12px; color: var(--text-muted); font-size: 12px; font-weight: 700; width: 130px;"><?php esc_html_e( 'Ámbito', 'baloa-structure-auditor-seo' ); ?></th>
                                    <th style="padding: 12px; color: var(--text-muted); font-size: 12px; font-weight: 700;"><?php esc_html_e( 'Destino / URL / Título del Post', 'baloa-structure-auditor-seo' ); ?></th>
                                    <th style="padding: 12px; color: var(--text-muted); font-size: 12px; font-weight: 700;"><?php esc_html_e( 'Ajustes Guardados', 'baloa-structure-auditor-seo' ); ?></th>
                                    <th style="padding: 12px; color: var(--text-muted); font-size: 12px; font-weight: 700; width: 110px; text-align: right;"><?php esc_html_e( 'Acciones', 'baloa-structure-auditor-seo' ); ?></th>
                                </tr>
                            </thead>
                            <tbody id="table-body-fixes">
                                <tr>
                                    <td colspan="4" style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13px;"><?php esc_html_e( 'Cargando listado de correcciones...', 'baloa-structure-auditor-seo' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Danger Zone: Bulk Purge and Reset Panel -->
                <div class="card critical-card" style="background: linear-gradient(180deg, var(--bg-card) 0%, rgba(239, 68, 68, 0.02) 100%); border-radius: var(--radius); border: 1px solid rgba(239, 68, 68, 0.35); padding: 28px; border-left: 5px solid var(--red); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05), 0 0 12px rgba(239, 68, 68, 0.05); margin-top: 32px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 700; color: var(--red); display: flex; align-items: center; gap: 8px; font-family: var(--font-main);">
                        <span class="dashicons dashicons-warning" style="font-size: 20px; width: 20px; height: 20px; color: var(--red);"></span>
                        <?php esc_html_e( 'Zona de Peligro: Restablecimiento y Purga Completa', 'baloa-structure-auditor-seo' ); ?>
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 13.5px; line-height: 1.6; margin-bottom: 24px; font-family: var(--font-main);">
                        <?php esc_html_e( 'Si deseas revertir todas las optimizaciones de SEO aplicadas y restaurar el sitio a su estado original, esta herramienta eliminará de forma irreversible todas las correcciones activas, esquemas estructurados, resúmenes TL;DR y meta descripciones inyectadas. Tu tema y base de datos quedarán 100% libres de modificaciones aplicadas por el plugin. (Nota: Esta opción no desinstala ni desactiva el plugin, únicamente purga los datos optimizados y revierte los cambios aplicados).', 'baloa-structure-auditor-seo' ); ?>
                    </p>

                    <!-- History Purge Checkbox -->
                    <div style="background: rgba(239, 68, 68, 0.01); border: 1px solid var(--border); padding: 16px 20px; border-radius: var(--radius-sm); margin-bottom: 24px; transition: all 0.2s ease;" onmouseover="this.style.background='rgba(239, 68, 68, 0.03)'; this.style.borderColor='rgba(239, 68, 68, 0.2)';" onmouseout="this.style.background='rgba(239, 68, 68, 0.01)'; this.style.borderColor='var(--border)';">
                        <label style="display: flex; align-items: center; color: var(--text-primary); font-size: 13.5px; cursor: pointer; font-weight: 600; width: 100%; user-select: none;">
                             <input type="checkbox" id="check-purge-history" style="margin-right: 12px; cursor: pointer; transform: scale(1.1); margin-top: 0;">
                             <?php esc_html_e( 'Eliminar también el Historial de Auditorías e Informes pasados (Snapshots del Auditor)', 'baloa-structure-auditor-seo' ); ?>
                        </label>
                    </div>

                    <!-- Secure Text-Confirm Form -->
                    <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
                        <div style="position: relative; flex: 1; min-width: 280px; max-width: 400px;">
                            <input type="text" id="input-confirm-purge" placeholder="<?php esc_attr_e( 'Escribe PURGAR para confirmar', 'baloa-structure-auditor-seo' ); ?>" style="background: var(--bg-secondary); border: 1.5px solid var(--border); color: var(--text-primary); padding: 12px 16px; border-radius: var(--radius-sm); outline: none; font-size: 13.5px; font-family: var(--font-main); width: 100%; transition: all 0.2s ease; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);" onfocus="this.style.borderColor='var(--red)'; this.style.boxShadow='0 0 0 3px rgba(239, 68, 68, 0.15), inset 0 2px 4px rgba(0,0,0,0.02)';" onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='inset 0 2px 4px rgba(0,0,0,0.02)';">
                        </div>
                        <button type="button" class="btn btn-danger" id="btn-purge-all" style="background: var(--red); color: #fff; padding: 12px 26px; font-weight: 700; font-size: 13.5px; border: none; border-radius: var(--radius-sm); cursor: not-allowed; font-family: var(--font-main); transition: all 0.2s ease; opacity: 0.4; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);" disabled>
                            <span class="dashicons dashicons-trash" style="font-size: 18px; width: 18px; height: 18px;"></span>
                            <?php esc_html_e( 'Purgar Todas las Correcciones', 'baloa-structure-auditor-seo' ); ?>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
