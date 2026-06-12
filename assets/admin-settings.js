jQuery(document).ready(function($) {
    var sections = ['license', 'api', 'analysis', 'ui'];
    var form = $('.baloa-settings-form');
    
    // Obtener los elementos hijos del formulario (excepto inputs ocultos de settings_fields y el submit)
    var children = form.children().not('input[type="hidden"], p.submit');
    
    // Agrupar dinámicamente los H2 y sus tablas/párrafos asociados en divs de contenido de pestaña
    var currentGroup = null;
    var groupIndex = 0;
    
    children.each(function() {
        var el = $(this);
        if (el.is('h2')) {
            var sectionKey = sections[groupIndex] || 'section-' + groupIndex;
            currentGroup = $('<div class="baloa-tab-content" id="tab-baloa-settings-' + sectionKey + '"></div>');
            // Insertar el contenedor antes del H2 actual
            el.before(currentGroup);
            groupIndex++;
        }
        if (currentGroup) {
            currentGroup.append(el);
        }
    });
    
    // Inicializar el estado de visibilidad
    $('.baloa-tab-content').hide();
    $('#tab-baloa-settings-license').show();
    
    // Manejador del click de pestañas
    $('.baloa-tab-btn').on('click', function(e) {
        e.preventDefault();
        var tabKey = $(this).data('tab');
        
        // Alternar clases activas
        $('.baloa-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Animación y visualización del bloque correspondiente
        $('.baloa-tab-content').hide();
        $('#tab-baloa-settings-' + tabKey).fadeIn(150);
    });
});
