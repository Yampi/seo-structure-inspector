jQuery(document).ready(function($) {
    const $searchInput = $('#baloa-glossary-search');
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
