<?php
/**
 * Template: Glossary Page - Premium Dark Serif Theme
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$baloa_structure_auditor_seo_categories = \BaloaStructureAuditorSEO\Core\Bookman::get_categories();
$baloa_structure_auditor_seo_terms      = \BaloaStructureAuditorSEO\Core\Bookman::get_terms();
$baloa_structure_auditor_seo_options    = get_option( 'baloa_structure_auditor_seo_options', [] );
$baloa_structure_auditor_seo_theme      = $baloa_structure_auditor_seo_options['ui_theme'] ?? 'dark';
?>

<div class="seoi-dashboard-root baloa-glossary-root" data-theme="<?php echo esc_attr( $baloa_structure_auditor_seo_theme ); ?>">
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
                <strong>SEO</strong>
                <span>Glossary</span>
            </div>
        </div>

        <a class="nav-item active" href="#" data-glossary-cat="all">
            <span class="nav-left"><span class="nav-icon">📖</span> Todos los campos</span>
        </a>
        
        <div class="nav-divider"></div>

        <?php foreach ( $baloa_structure_auditor_seo_categories as $baloa_structure_auditor_seo_slug => $cat ) : ?>
            <a class="nav-item" href="#" data-glossary-cat="<?php echo esc_attr( $baloa_structure_auditor_seo_slug ); ?>">
                <span class="nav-left"><span class="nav-icon"><?php echo esc_html( $cat['icon'] ); ?></span> <?php echo esc_html( $cat['name'] ); ?></span>
                <span class="score-badge" style="background: rgba(255,255,255,0.06); font-family: monospace; font-size: 11px;">
                    <?php 
                    $baloa_structure_auditor_seo_count = count( array_filter( $baloa_structure_auditor_seo_terms, function($t) use ($baloa_structure_auditor_seo_slug) { return $t['category'] === $baloa_structure_auditor_seo_slug; } ) );
                    echo esc_html( $baloa_structure_auditor_seo_count );
                    ?>
                </span>
            </a>
        <?php endforeach; ?>

        <div class="nav-divider"></div>

        <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=baloa-structure-auditor-seo' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">📊</span> Panel de Control</span>
        </a>
        <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=baloa-settings' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">⚙️</span> Configuración</span>
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- TOPBAR SEARCH -->
        <header class="topbar baloa-glossary-topbar">
            <div class="url-input-wrap baloa-glossary-search-wrap">
                <input id="baloa-glossary-search" class="url-input baloa-glossary-search-input" type="text" placeholder="🔍 Buscar término, palabra clave o fuente (ej. alt, canonical, H1...)" />
            </div>
            <div class="topbar-meta baloa-glossary-topbar-meta">
                <?php echo esc_html( count( $baloa_structure_auditor_seo_terms ) ); ?> campos auditados
            </div>
        </header>

        <div class="content baloa-glossary-content">
            <!-- HEADER HERO -->
            <div class="glossary-hero baloa-glossary-hero">
                <h1 class="baloa-glossary-hero-title">Glosario Técnico de Inspección</h1>
                <p class="baloa-glossary-hero-desc">
                    Explora el significado riguroso de cada métrica y prueba que ejecuta nuestro analizador web. Consulta fuentes oficiales del sector y revisa ejemplos de maquetación correctos para mejorar tu visibilidad en buscadores y sistemas de IA.
                </p>
            </div>

            <!-- TERMS GRID -->
            <div class="glossary-grid baloa-glossary-grid" id="baloa-glossary-grid">
                <?php foreach ( $baloa_structure_auditor_seo_terms as $id => $term ) : ?>
                    <div class="glossary-card baloa-glossary-card" 
                         id="term-<?php echo esc_attr( $id ); ?>" 
                         data-id="<?php echo esc_attr( $id ); ?>"
                         data-category="<?php echo esc_attr( $term['category'] ); ?>"
                         data-search-content="<?php echo esc_attr( strtolower( $term['name'] . ' ' . $term['short_definition'] . ' ' . $term['full_definition'] . ' ' . $term['source_name'] . ' ' . $id ) ); ?>">
                        
                        <!-- CARD HEADER -->
                        <div class="card-head baloa-glossary-card-head">
                            <div class="baloa-glossary-card-head-left">
                                <span class="baloa-glossary-card-icon">
                                    <?php echo esc_html( $baloa_structure_auditor_seo_categories[$term['category']]['icon'] ?? '⚙️' ); ?>
                                </span>
                                <div>
                                    <h2 class="baloa-glossary-card-title"><?php echo esc_html( $term['name'] ); ?></h2>
                                    <span class="baloa-glossary-card-id">ID: <?php echo esc_html( $id ); ?></span>
                                </div>
                            </div>
                            
                            <div class="baloa-glossary-card-head-right">
                                <span class="baloa-glossary-cat-badge">
                                    <?php echo esc_html( $baloa_structure_auditor_seo_categories[$term['category']]['name'] ); ?>
                                </span>
                                <?php if ( ! empty( $term['source_name'] ) ) : ?>
                                    <a href="<?php echo esc_url( $term['source_url'] ); ?>" target="_blank" class="source-badge baloa-glossary-source-badge">
                                        Fuente: <?php echo esc_html( $term['source_name'] ); ?> ↗
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- CARD BODY -->
                        <div class="card-body-wrap baloa-glossary-card-body">
                            <!-- DEFINITIONS -->
                            <div class="definition-block">
                                <h4 class="baloa-glossary-section-label">Definición y Concepto</h4>
                                <div class="seosi-bookman-text">
                                    <strong><?php echo esc_html( $term['short_definition'] ); ?></strong>
                                    <p class="baloa-glossary-def-p"><?php echo esc_html( $term['full_definition'] ); ?></p>
                                </div>
                            </div>

                            <div class="why-block baloa-glossary-why-block">
                                <h4 class="baloa-glossary-why-label">¿Por qué es vital para SEO / IA?</h4>
                                <p class="baloa-glossary-why-text">
                                    <?php echo esc_html( $term['why_it_matters'] ); ?>
                                </p>
                            </div>

                            <!-- VISUAL EXAMPLES (COLLAPSIBLE) -->
                            <div class="examples-collapsible baloa-glossary-examples-wrap">
                                <button type="button" class="btn-toggle-examples baloa-glossary-toggle-btn">
                                    <span>👁️ Ver Ejemplos Código (Paso vs Fallo)</span>
                                </button>

                                <div class="examples-panel baloa-glossary-examples-panel">
                                    <!-- BAD EXAMPLE -->
                                    <div class="baloa-glossary-example-bad-wrap">
                                        <div class="baloa-glossary-example-bad-label">
                                            ❌ CÓDIGO INCORRECTO / MEJORABLE
                                        </div>
                                        <pre class="baloa-glossary-code-bad"><?php echo esc_html( $term['example_bad'] ); ?></pre>
                                    </div>

                                    <!-- GOOD EXAMPLE -->
                                    <div class="baloa-glossary-example-good-wrap">
                                        <div class="baloa-glossary-example-good-label">
                                            ✅ ESTRUCTURA CORRECTA / OPTIMIZADA
                                        </div>
                                        <pre class="baloa-glossary-code-good"><?php echo esc_html( $term['example_good'] ); ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- NO RESULTS STATE -->
            <div id="glossary-no-results" class="baloa-glossary-no-results">
                <span class="baloa-glossary-no-results-icon">🔍</span>
                <h3 class="baloa-glossary-no-results-title">No se encontraron términos</h3>
                <p class="baloa-glossary-no-results-desc">Prueba con otra palabra clave o selecciona otra categoría en el menú lateral.</p>
            </div>
        </div>
    </div>
</div>
