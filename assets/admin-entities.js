/* global BALOA, jQuery */
jQuery(function ($) {
  if (typeof window.BALOA_Admin === 'undefined') return;

  const admin = window.BALOA_Admin;

  // Custom click handler on Sidebar Nav Item for "entities"
  $('.nav-item[data-module="entities"]').on('click', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    $('.nav-item').removeClass('active');
    $(this).addClass('active');
    $('#view-sitemap').hide();
    $('#view-recommendations').hide();
    $('#view-resumen').hide();
    $('#view-entities').fadeIn('fast');
    admin.state.activeModule = 'entities';
    
    admin.renderEntitiesMap();
  });

  admin.renderEntitiesMap = function () {
    const $container = $('#baloa-entities-map-container');
    if (!$container.length) return;

    const res = admin.state.lastDashboardResult;
    if (!res) {
      $container.html('<div style="text-align:center; padding:40px; color:var(--text-muted);">Realiza un análisis primero para ver el mapa de autoridad temática.</div>');
      return;
    }

    const keyword = res.keyword || {};
    const details = keyword.details || {};
    const entities = details.entities || {};

    if (!entities.niche) {
      $container.html('<div style="text-align:center; padding:40px; color:var(--text-muted);">No se detectaron datos de entidades semánticas para esta página.</div>');
      return;
    }

    const present = entities.present || [];
    const missing = entities.missing || [];
    const nicheName = entities.niche_name || 'Nicho Desconocido';
    const coveragePct = Math.round((entities.coverage || 0) * 100);

    let html = '<div class="entities-summary-card" style="margin-bottom:20px; padding:20px; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); display:flex; justify-content:space-between; align-items:center;">' +
                 '<div>' +
                   '<h3 style="margin:0 0 4px 0; font-size:18px; font-weight:800; color:var(--text-primary);">Mapa de Autoridad Temática</h3>' +
                   '<p style="margin:0; font-size:13px; color:var(--text-muted);">Nicho Detectado: <strong style="color:var(--primary);">' + admin.escHtml(nicheName) + '</strong></p>' +
                 '</div>' +
                 '<div style="display:flex; align-items:center; gap:12px;">' +
                   '<div style="text-align:right;">' +
                     '<div style="font-size:20px; font-weight:800; color:' + (coveragePct >= 60 ? 'var(--green)' : 'var(--orange)') + ';">' + coveragePct + '%</div>' +
                     '<div style="font-size:11px; color:var(--text-muted);">Cobertura Semántica</div>' +
                   '</div>' +
                 '</div>' +
               '</div>' +
               '<div class="svg-wrapper" style="position:relative;">' +
                 '<svg id="baloa-entities-svg" width="100%" height="480" viewBox="0 0 600 400" style="background:#111422; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; display:block; margin:0 auto;"></svg>' +
               '</div>';
    $container.html(html);

    const svg = $('#baloa-entities-svg');
    const center = { x: 300, y: 200 };
    const radius = 135;
    
    const allNodes = [];
    present.forEach(t => allNodes.push({ name: t, type: 'present' }));
    missing.forEach(t => allNodes.push({ name: t, type: 'missing' }));

    let svgContent = '';
    const totalNodes = allNodes.length;

    // Draw lines (edges)
    allNodes.forEach((node, i) => {
      const angle = (2 * Math.PI * i) / totalNodes;
      const x = center.x + radius * Math.cos(angle);
      const y = center.y + radius * Math.sin(angle);
      const color = node.type === 'present' ? 'rgba(16, 185, 129, 0.4)' : 'rgba(239, 68, 68, 0.3)';
      const strokeDash = node.type === 'present' ? '' : 'stroke-dasharray="3,3"';
      svgContent += '<line x1="' + center.x + '" y1="' + center.y + '" x2="' + x + '" y2="' + y + '" stroke="' + color + '" stroke-width="1.5" ' + strokeDash + ' />';
    });

    // Draw Center Node (Niche name)
    const shortNiche = nicheName.length > 15 ? nicheName.substring(0, 12) + '...' : nicheName;
    svgContent += '<g>' +
                    '<circle cx="' + center.x + '" cy="' + center.y + '" r="48" fill="#1e293b" stroke="#06b6d4" stroke-width="3" style="filter: drop-shadow(0 0 8px rgba(6, 182, 212, 0.3));" />' +
                    '<text x="' + center.x + '" y="' + center.y + '" fill="#fff" font-size="10" font-weight="bold" text-anchor="middle" dominant-baseline="middle">' + admin.escHtml(shortNiche) + '</text>' +
                  '</g>';

    // Draw Outer Nodes
    allNodes.forEach((node, i) => {
      const angle = (2 * Math.PI * i) / totalNodes;
      const x = center.x + radius * Math.cos(angle);
      const y = center.y + radius * Math.sin(angle);

      const isPresent = node.type === 'present';
      const bgColor = isPresent ? '#10b981' : '#1e1e2e';
      const strokeColor = isPresent ? '#34d399' : '#ef4444';
      const textColor = isPresent ? '#fff' : '#9ca3af';
      const strokeWidth = isPresent ? '2' : '1.5';
      const strokeDash = isPresent ? '' : 'stroke-dasharray="2,2"';
      const shortName = node.name.length > 10 ? node.name.substring(0, 8) + '..' : node.name;

      svgContent += '<g title="' + admin.escHtml(node.name) + '">' +
                      '<circle cx="' + x + '" cy="' + y + '" r="24" fill="' + bgColor + '" stroke="' + strokeColor + '" stroke-width="' + strokeWidth + '" ' + strokeDash + ' />' +
                      '<text x="' + x + '" y="' + y + '" fill="' + textColor + '" font-size="8" font-weight="bold" text-anchor="middle" dominant-baseline="middle">' + admin.escHtml(shortName) + '</text>' +
                    '</g>';
    });

    svg.html(svgContent);
  };
});
