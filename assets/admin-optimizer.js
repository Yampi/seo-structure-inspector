/* global BALOA, jQuery */
jQuery(function ($) {
  if (typeof window.BALOA_Admin === 'undefined') return;

  const admin = window.BALOA_Admin;

  admin.renderWpPostsList = function (isLocal) {
    const items = admin.state.wpActiveTab === 'posts' ? admin.state.wpPosts : admin.state.wpPages;
    $('#baloa-qw-count').text(items.length);

    if (items.length === 0) {
      $('#baloa-qw-list').html('<div style="color:var(--text-muted); font-size:13px">Sin contenido reciente encontrado.</div>');
      return;
    }

    const html = items.map((item, index) => {
      const score = item.score;
      let scoreHtml = '';
      let scoreClass = 'score-none';
      
      if (score === undefined || score === null) {
        scoreHtml = '<span class="wp-post-score score-none">⏳</span>';
      } else {
        if (score >= 80) scoreClass = 'score-good';
        else if (score >= 40) scoreClass = 'score-mid';
        else scoreClass = 'score-bad';
        scoreHtml = '<span class="wp-post-score ' + scoreClass + '">' + score + '</span>';
      }

      let fixableCount = 0;
      if (item.problems) {
        item.problems.forEach(p => {
          if (p.supports_autofix === true) fixableCount++;
        });
      }

      let actionBtn = '';
      if (typeof BALOA !== 'undefined' && BALOA.is_premium) {
        if (score === undefined || score === null) {
          actionBtn = '<button type="button" class="wp-post-btn-autofix" disabled style="opacity: 0.5;">Analizando...</button>';
        } else {
          if (isLocal) {
            if (fixableCount > 0) {
              actionBtn = '<button type="button" class="wp-post-btn-autofix btn-trigger-wp-autofix" data-index="' + index + '" data-type="' + admin.state.wpActiveTab + '">Auto-fix (' + fixableCount + ')</button>';
            } else {
              actionBtn = '<button type="button" class="wp-post-btn-autofix" disabled style="background: rgba(34, 197, 94, 0.15); color: var(--green); border: 1px solid rgba(34, 197, 94, 0.25);">✓ Optimizado</button>';
            }
          } else {
            actionBtn = '<span style="font-size:11px; color:var(--text-muted);">Externo (Solo lectura)</span>';
          }
        }
      } else {
        if (score === undefined || score === null) {
          actionBtn = '<span style="font-size:11px; color:var(--text-muted);">Analizando...</span>';
        } else if (!isLocal) {
          actionBtn = '<span style="font-size:11px; color:var(--text-muted);">Externo</span>';
        }
      }

      return '<div class="wp-post-item">' +
               '<div class="wp-post-title" title="' + admin.escHtml(item.title) + '">' + admin.escHtml(item.title) + '</div>' +
               '<div class="wp-post-meta">' +
                 scoreHtml +
                 actionBtn +
               '</div>' +
             '</div>';
    });

    $('#baloa-qw-list').html(html.join(''));
  };

  admin.lazyAnalyzeWpContent = function (isLocal) {
    const queue = [];
    admin.state.wpPosts.forEach((item, index) => {
      queue.push({ type: 'posts', index: index, item: item });
    });
    admin.state.wpPages.forEach((item, index) => {
      queue.push({ type: 'pages', index: index, item: item });
    });

    const pending = [];
    const oneHour = 60 * 60 * 1000;

    queue.forEach(q => {
      const cacheKey = 'baloa_structure_auditor_seo_wp_cache_' + encodeURIComponent(q.item.url);
      try {
        const cacheStr = localStorage.getItem(cacheKey);
        if (cacheStr) {
          const cache = JSON.parse(cacheStr);
          if (cache && (Date.now() - cache.timestamp < oneHour)) {
            q.item.score = cache.score;
            q.item.problems = cache.problems;
            q.item.results = cache.results;
            return;
          }
        }
      } catch (e) {
        console.error('Error reading wp cache:', e);
      }
      pending.push(q);
    });

    admin.renderWpPostsList(isLocal);
    admin.updateWpSummaryKpi();

    if (pending.length === 0) return;

    let currentIdx = 0;
    function processNextPending() {
      if (currentIdx >= pending.length) {
        admin.updateWpSummaryKpi();
        return;
      }

      const q = pending[currentIdx];
      
      $.ajax({
        url: BALOA.ajax_url,
        method: 'POST',
        data: {
          action: 'baloa_structure_auditor_seo_analyze',
          nonce: BALOA.nonce,
          url: q.item.url,
          keyword: ''
        },
        success: function(res) {
          if (res.success && res.data) {
            const problems = [];
            const adaptedData = admin.adaptApiResponse(res.data);
            admin.MODULE_KEYS.forEach(k => {
              if (adaptedData[k] && adaptedData[k].checks) {
                adaptedData[k].checks.forEach(c => {
                  if (c.severity !== 'pass') {
                    problems.push(Object.assign({}, c, { module: k }));
                  }
                });
              }
            });

            q.item.score = res.data.global_score ?? 0;
            q.item.problems = problems;
            q.item.results = res.data;

            const cacheKey = 'baloa_structure_auditor_seo_wp_cache_' + encodeURIComponent(q.item.url);
            try {
              localStorage.setItem(cacheKey, JSON.stringify({
                timestamp: Date.now(),
                score: q.item.score,
                problems: problems,
                results: res.data
              }));
            } catch(e) {}

            admin.renderWpPostsList(isLocal);
            admin.updateWpSummaryKpi();
          }
        },
        error: function() {
          q.item.score = 0;
          q.item.problems = [];
          admin.renderWpPostsList(isLocal);
        },
        complete: function() {
          currentIdx++;
          setTimeout(processNextPending, 800);
        }
      });
    }

    processNextPending();
  };

  admin.updateWpSummaryKpi = function () {
    let sum = 0;
    let count = 0;
    admin.state.wpPosts.concat(admin.state.wpPages).forEach(item => {
      if (item.score !== undefined && item.score !== null) {
        sum += Number(item.score);
        count++;
      }
    });

    if (count === 0) {
      $('#baloa-wp-score-avg').text('—');
      $('#baloa-wp-status-text').text('Analizando contenido...').css('color', 'var(--text-muted)');
      return;
    }

    const avg = Math.round(sum / count);
    $('#baloa-wp-score-avg').text(avg);
    
    const tier = admin.scoreTier(avg);
    $('#baloa-wp-score-avg').css('color', tier.color);
    $('#baloa-wp-status-text').text(tier.label).css('color', tier.color);
  };

  admin.refreshWpItem = function (url) {
    const item = admin.state.wpPosts.concat(admin.state.wpPages).find(p => p.url === url);
    if (!item) return;

    item.score = null;
    item.problems = null;
    item.results = null;
    admin.renderWpPostsList(admin.state.wpIsLocal);
    admin.updateWpSummaryKpi();

    $.ajax({
      url: BALOA.ajax_url,
      method: 'POST',
      data: {
        action: 'baloa_structure_auditor_seo_analyze',
        nonce: BALOA.nonce,
        url: url,
        keyword: ''
      },
      success: function(res) {
        if (res.success && res.data) {
          const problems = [];
          const adaptedData = admin.adaptApiResponse(res.data);
          admin.MODULE_KEYS.forEach(k => {
            if (adaptedData[k] && adaptedData[k].checks) {
              adaptedData[k].checks.forEach(c => {
                if (c.severity !== 'pass') {
                  problems.push(Object.assign({}, c, { module: k }));
                }
              });
            }
          });

          item.score = res.data.global_score ?? 0;
          item.problems = problems;
          item.results = res.data;

          const cacheKey = 'baloa_structure_auditor_seo_wp_cache_' + encodeURIComponent(url);
          try {
            localStorage.setItem(cacheKey, JSON.stringify({
              timestamp: Date.now(),
              score: item.score,
              problems: problems,
              results: res.data
            }));
          } catch(e) {}

          admin.renderWpPostsList(admin.state.wpIsLocal);
          admin.updateWpSummaryKpi();
        }
      }
    });
  };

  // Bind Events for Optimizer
  $(document).on('click', '.wp-toggle-btn', function(e) {
    e.preventDefault();
    $('.wp-toggle-btn').removeClass('active');
    $(this).addClass('active');
    admin.state.wpActiveTab = $(this).data('type');
    admin.renderWpPostsList(admin.state.wpIsLocal);
  });

  $(document).on('click', '.btn-trigger-wp-autofix', function(e) {
    e.preventDefault();
    const $btn = $(this);
    const idx = $btn.data('index');
    const type = $btn.data('type');
    const item = type === 'posts' ? admin.state.wpPosts[idx] : admin.state.wpPages[idx];
    if (!item) return;

    const problems = item.problems || [];
    const autoFixes = [];
    const manualFixes = [];

    problems.forEach(p => {
      if (p.supports_autofix === true) {
        if (p.id === 'llms_txt_present' || p.id === 'llms_full_txt_present') {
          manualFixes.push(p);
        } else {
          autoFixes.push(p);
        }
      }
    });

    if (autoFixes.length > 0) {
      $btn.prop('disabled', true).css('opacity', '0.7');
      
      const fixesData = autoFixes.map(f => ({
        id: f.id,
        module: f.module,
        url: item.url
      }));

      if (typeof admin.executeSequentialAutofixes === 'function') {
        admin.executeSequentialAutofixes(
          fixesData,
          function(current, total) {
            $btn.text('⏳ Reparando (' + current + '/' + total + ')...');
          },
          function(result) {
            const cacheKey = 'baloa_structure_auditor_seo_wp_cache_' + encodeURIComponent(item.url);
            localStorage.removeItem(cacheKey);

            admin.refreshWpItem(item.url);

            if (manualFixes.length > 0 && typeof admin.openWpManualFixesModal === 'function') {
              admin.openWpManualFixesModal(item, manualFixes, true);
            } else {
              $btn.text('✓ Optimizado').css({
                'background': 'rgba(34, 197, 94, 0.15)',
                'color': 'var(--green)',
                'border': '1px solid rgba(34, 197, 94, 0.25)'
              });
            }
          }
        );
      }
    } else if (manualFixes.length > 0 && typeof admin.openWpManualFixesModal === 'function') {
      admin.openWpManualFixesModal(item, manualFixes, false);
    }
  });
});
