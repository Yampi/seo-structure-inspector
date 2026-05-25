<?php
/**
 * Template: Glossary Page - Premium Dark Serif Theme
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories = \SEOSI\Core\Bookman::get_categories();
$terms      = \SEOSI\Core\Bookman::get_terms();
$options    = get_option( 'seosi_options', [] );
$theme      = $options['ui_theme'] ?? 'dark';
?>

<div class="seoi-dashboard-root seosi-glossary-root" data-theme="<?php echo esc_attr( $theme ); ?>">
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
                <span>Glossary</span>
            </div>
        </div>

        <a class="nav-item active" href="#" data-glossary-cat="all">
            <span class="nav-left"><span class="nav-icon">📖</span> Todos los campos</span>
        </a>
        
        <div class="nav-divider"></div>

        <?php foreach ( $categories as $slug => $cat ) : ?>
            <a class="nav-item" href="#" data-glossary-cat="<?php echo esc_attr( $slug ); ?>">
                <span class="nav-left"><span class="nav-icon"><?php echo esc_html( $cat['icon'] ); ?></span> <?php echo esc_html( $cat['name'] ); ?></span>
                <span class="score-badge" style="background: rgba(255,255,255,0.06); font-family: monospace; font-size: 11px;">
                    <?php 
                    $count = count( array_filter( $terms, function($t) use ($slug) { return $t['category'] === $slug; } ) );
                    echo esc_html( $count );
                    ?>
                </span>
            </a>
        <?php endforeach; ?>

        <div class="nav-divider"></div>

        <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-structure-inspector' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">📊</span> Panel de Control</span>
        </a>
        <a class="nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=seosi-settings' ) ); ?>">
            <span class="nav-left"><span class="nav-icon">⚙️</span> Configuración</span>
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- TOPBAR SEARCH -->
        <header class="topbar seosi-glossary-topbar">
            <div class="url-input-wrap seosi-glossary-search-wrap">
                <input id="seosi-glossary-search" class="url-input seosi-glossary-search-input" type="text" placeholder="🔍 Buscar término, palabra clave o fuente (ej. alt, canonical, H1...)" />
            </div>
            <div class="topbar-meta seosi-glossary-topbar-meta">
                <?php echo esc_html( count( $terms ) ); ?> campos auditados
            </div>
        </header>

        <div class="content seosi-glossary-content">
            <!-- HEADER HERO -->
            <div class="glossary-hero seosi-glossary-hero">
                <h1 class="seosi-glossary-hero-title">Glosario Técnico de Inspección</h1>
                <p class="seosi-glossary-hero-desc">
                    Explora el significado riguroso de cada métrica y prueba que ejecuta nuestro analizador web. Consulta fuentes oficiales del sector y revisa ejemplos de maquetación correctos para mejorar tu visibilidad en buscadores y sistemas de IA.
                </p>
            </div>

            <!-- TERMS GRID -->
            <div class="glossary-grid seosi-glossary-grid" id="seosi-glossary-grid">
                <?php foreach ( $terms as $id => $term ) : ?>
                    <div class="glossary-card seosi-glossary-card" 
                         id="term-<?php echo esc_attr( $id ); ?>" 
                         data-id="<?php echo esc_attr( $id ); ?>"
                         data-category="<?php echo esc_attr( $term['category'] ); ?>"
                         data-search-content="<?php echo esc_attr( strtolower( $term['name'] . ' ' . $term['short_definition'] . ' ' . $term['full_definition'] . ' ' . $term['source_name'] . ' ' . $id ) ); ?>">
                        
                        <!-- CARD HEADER -->
                        <div class="card-head seosi-glossary-card-head">
                            <div class="seosi-glossary-card-head-left">
                                <span class="seosi-glossary-card-icon">
                                    <?php echo esc_html( $categories[$term['category']]['icon'] ?? '⚙️' ); ?>
                                </span>
                                <div>
                                    <h2 class="seosi-glossary-card-title"><?php echo esc_html( $term['name'] ); ?></h2>
                                    <span class="seosi-glossary-card-id">ID: <?php echo esc_html( $id ); ?></span>
                                </div>
                            </div>
                            
                            <div class="seosi-glossary-card-head-right">
                                <span class="seosi-glossary-cat-badge">
                                    <?php echo esc_html( $categories[$term['category']]['name'] ); ?>
                                </span>
                                <?php if ( ! empty( $term['source_name'] ) ) : ?>
                                    <a href="<?php echo esc_url( $term['source_url'] ); ?>" target="_blank" class="source-badge seosi-glossary-source-badge">
                                        Fuente: <?php echo esc_html( $term['source_name'] ); ?> ↗
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- CARD BODY -->
                        <div class="card-body-wrap seosi-glossary-card-body">
                            <!-- DEFINITIONS -->
                            <div class="definition-block">
                                <h4 class="seosi-glossary-section-label">Definición y Concepto</h4>
                                <div class="seosi-bookman-text">
                                    <strong><?php echo esc_html( $term['short_definition'] ); ?></strong>
                                    <p class="seosi-glossary-def-p"><?php echo esc_html( $term['full_definition'] ); ?></p>
                                </div>
                            </div>

                            <div class="why-block seosi-glossary-why-block">
                                <h4 class="seosi-glossary-why-label">¿Por qué es vital para SEO / IA?</h4>
                                <p class="seosi-glossary-why-text">
                                    <?php echo esc_html( $term['why_it_matters'] ); ?>
                                </p>
                            </div>

                            <!-- VISUAL EXAMPLES (COLLAPSIBLE) -->
                            <div class="examples-collapsible seosi-glossary-examples-wrap">
                                <button type="button" class="btn-toggle-examples seosi-glossary-toggle-btn">
                                    <span>👁️ Ver Ejemplos Código (Paso vs Fallo)</span>
                                </button>

                                <div class="examples-panel seosi-glossary-examples-panel">
                                    <!-- BAD EXAMPLE -->
                                    <div class="seosi-glossary-example-bad-wrap">
                                        <div class="seosi-glossary-example-bad-label">
                                            ❌ CÓDIGO INCORRECTO / MEJORABLE
                                        </div>
                                        <pre class="seosi-glossary-code-bad"><?php echo esc_html( $term['example_bad'] ); ?></pre>
                                    </div>

                                    <!-- GOOD EXAMPLE -->
                                    <div class="seosi-glossary-example-good-wrap">
                                        <div class="seosi-glossary-example-good-label">
                                            ✅ ESTRUCTURA CORRECTA / OPTIMIZADA
                                        </div>
                                        <pre class="seosi-glossary-code-good"><?php echo esc_html( $term['example_good'] ); ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- NO RESULTS STATE -->
            <div id="glossary-no-results" class="seosi-glossary-no-results">
                <span class="seosi-glossary-no-results-icon">🔍</span>
                <h3 class="seosi-glossary-no-results-title">No se encontraron términos</h3>
                <p class="seosi-glossary-no-results-desc">Prueba con otra palabra clave o selecciona otra categoría en el menú lateral.</p>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const $searchInput = $('#seosi-glossary-search');
    const $cards = $('.glossary-card');
    const $noResults = $('#glossary-no-results');
    let currentCategory = 'all';

    // 1. FILTER BY SEARCH & CATEGORY
    function filterGlossary() {
        const query = $searchInput.val().trim().toLowerCase();
        let visibleCount = 0;

        $cards.each(function() {
            const $card = $(this);
            const cardCategory = $card.data('category');
            const searchContent = $card.data('search-content');
            
            const matchesCat = (currentCategory === 'all' || cardCategory === currentCategory);
            const matchesSearch = (!query || searchContent.includes(query));

            if (matchesCat && matchesSearch) {
                $card.show();
                visibleCount++;
            } else {
                $card.hide();
            }
        });

        if (visibleCount === 0) {
            $noResults.show();
        } else {
            $noResults.hide();
        }
    }

    // 2. SEARCH INPUT EVENT
    $searchInput.on('input', function() {
        filterGlossary();
    });

    // 3. CATEGORY SWITCHING
    $('[data-glossary-cat]').on('click', function(e) {
        e.preventDefault();
        $('[data-glossary-cat]').removeClass('active');
        $(this).addClass('active');

        currentCategory = $(this).data('glossary-cat');
        filterGlossary();
    });

    // 4. COLLAPSIBLE EXAMPLES
    $('.btn-toggle-examples').on('click', function() {
        const $btn = $(this);
        const $panel = $btn.next('.examples-panel');
        
        $panel.slideToggle(200, function() {
            if ($panel.is(':visible')) {
                $btn.find('span').text('👁️ Ocultar Ejemplos');
                $btn.addClass('active').css('border-color', 'rgba(79, 142, 247, 0.4)');
            } else {
                $btn.find('span').text('👁️ Ver Ejemplos Código (Paso vs Fallo)');
                $btn.removeClass('active').css('border-color', 'rgba(255,255,255,0.1)');
            }
        });
    });

    // 5. HASHTAG NAVIGATION (DEEP LINK ROUTER)
    function checkHashLink() {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#term-')) {
            const targetId = hash;
            const $targetCard = $(targetId);
            
            if ($targetCard.length) {
                // Remove all filtering so target is visible
                $('[data-glossary-cat]').removeClass('active');
                $('[data-glossary-cat="all"]').addClass('active');
                currentCategory = 'all';
                $searchInput.val('');
                filterGlossary();

                // Smooth scroll to target card
                $('html, body, .content').animate({
                    scrollTop: $targetCard.offset().top - $('.topbar').outerHeight() - 40
                }, 500);

                // Highlight animation
                $targetCard.css({
                    'border-color': '#4f8ef7',
                    'box-shadow': '0 0 16px rgba(79, 142, 247, 0.2)',
                    'transform': 'translateY(-2px)'
                });

                // Auto expand examples panel
                $targetCard.find('.examples-panel').show();
                $targetCard.find('.btn-toggle-examples span').text('👁️ Ocultar Ejemplos');
                
                // Dim down after a few seconds
                setTimeout(function() {
                    $targetCard.css({
                        'border-color': 'rgba(255,255,255,0.05)',
                        'box-shadow': 'none',
                        'transform': 'none'
                    });
                }, 3000);
            }
        }
    }

    // Run on startup
    setTimeout(checkHashLink, 200);

    // Also run if hash changes without reloading
    $(window).on('hashchange', function() {
        checkHashLink();
    });
});
</script>
