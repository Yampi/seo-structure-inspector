jQuery(document).ready(function($) {
    var nonce = BALOA.nonce;
    var strings = BALOA.reversion || {
        scanning_fixes: 'Escaneando cambios activos...',
        structural_active: 'Activo (Frontend)',
        structural_inactive: 'Inactivo',
        no_fixes: 'No hay correcciones o anulaciones activas en la base de datos.',
        url_override: 'URL Override',
        post_meta: 'Post Meta',
        undo: 'Deshacer',
        communication_error: 'Error de comunicación con el servidor.',
        confirm_single_revert: '¿Estás seguro de que deseas eliminar permanentemente todas las mejoras de SEO inyectadas en "',
        deleting: 'Eliminando...',
        connection_error: 'Error de conexión.',
        confirm_bulk_purge: '¡ADVERTENCIA CRÍTICA!\n\nEstás a punto de eliminar de forma masiva y definitiva ABSOLUTAMENTE TODAS las correcciones de SEO aplicadas en este sitio.\n\nEsta acción no se puede deshacer y devolverá la base de datos a su estado original predeterminado. ¿Deseas continuar?',
        purging_db: 'Purgando base de datos...',
        purge_all_btn: 'Purgar Todas las Correcciones'
    };

    function loadFixes() {
        $('#table-body-fixes').html('<tr><td colspan="4" style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13px;">🔄 ' + strings.scanning_fixes + '</td></tr>');
        
        $.post(ajaxurl, {
            action: 'baloa_structure_auditor_seo_get_applied_fixes',
            nonce: nonce
        }, function(res) {
            if (res.success) {
                var summary = res.data.summary;
                var details = res.data.details;

                // Update Overview counters
                $('#stat-posts').text(summary.posts_modified);
                $('#stat-urls').text(summary.url_overrides);
                
                if (summary.structural_active) {
                    $('#stat-structural').text(strings.structural_active).css('color', 'var(--accent-green)');
                } else {
                    $('#stat-structural').text(strings.structural_inactive).css('color', 'var(--text-muted)');
                }

                // Render detail table content
                if (details.length === 0) {
                    $('#table-body-fixes').html('<tr><td colspan="4" style="padding: 32px; text-align: center; color: var(--text-muted); font-size: 13px;">🎉 ' + strings.no_fixes + '</td></tr>');
                    return;
                }

                var html = '';
                details.forEach(function(item) {
                    var typeLabel = item.type === 'url_override' ? strings.url_override : strings.post_meta;
                    var typeBadgeClass = item.type === 'url_override' ? 'background: rgba(155, 114, 232, 0.12); color: var(--accent-purple); border: 1px solid rgba(155, 114, 232, 0.2);' : 'background: rgba(79, 142, 247, 0.12); color: var(--accent-blue); border: 1px solid rgba(79, 142, 247, 0.2);';
                    
                    // Format active key details recursively
                    var keysFormatted = item.details.map(function(k) {
                        var cleanKey = k.replace('_bsa_', '');
                        return '<code style="font-size: 11px; margin-right: 4px; margin-bottom: 4px; display: inline-block; background: rgba(255,255,255,0.06); border: 1px solid var(--border-color); padding: 1px 5px; border-radius: 4px; color: var(--text-secondary);">' + cleanKey + '</code>';
                    }).join(' ');

                    html += '<tr style="border-bottom: 1px solid var(--border-color);">';
                    html += '<td style="padding: 12px 8px;"><span style="display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; ' + typeBadgeClass + '">' + typeLabel + '</span></td>';
                    html += '<td style="padding: 12px 8px; font-weight: 600; color: var(--text-primary); font-size: 13px; word-break: break-all;">' + item.target + '</td>';
                    html += '<td style="padding: 12px 8px; line-height: 1.6;">' + keysFormatted + '</td>';
                    html += '<td style="padding: 12px 8px; text-align: right;">';
                    html += '<button type="button" class="btn btn-secondary btn-sm btn-revert" data-type="' + item.type + '" data-target="' + item.target + '" data-postid="' + item.post_id + '" style="cursor: pointer; padding: 4px 10px; font-size: 11px; font-family: var(--font-main); background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: var(--radius-xs); transition: all 0.2s;">' + strings.undo + '</button>';
                    html += '</td>';
                    html += '</tr>';
                });

                $('#table-body-fixes').html(html);
            } else {
                $('#table-body-fixes').html('<tr><td colspan="4" style="padding: 24px; text-align: center; color: var(--accent-red); font-size: 13px;">❌ ' + res.data.message + '</td></tr>');
            }
        }).fail(function() {
            $('#table-body-fixes').html('<tr><td colspan="4" style="padding: 24px; text-align: center; color: var(--accent-red); font-size: 13px;">❌ ' + strings.communication_error + '</td></tr>');
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

        if (!confirm(strings.confirm_single_revert + target + '"?')) {
            return;
        }

        $btn.text(strings.deleting).prop('disabled', true);

        $.post(ajaxurl, {
            action: 'baloa_structure_auditor_seo_revert_single_fix',
            type: type,
            target: target,
            post_id: postid,
            nonce: nonce
        }, function(res) {
            if (res.success) {
                loadFixes();
            } else {
                alert('Error: ' + res.data.message);
                $btn.text(strings.undo).prop('disabled', false);
            }
        }).fail(function() {
            alert(strings.connection_error);
            $btn.text(strings.undo).prop('disabled', false);
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

        if (!confirm(strings.confirm_bulk_purge)) {
            return;
        }

        var $btn = $(this);
        $btn.text(strings.purging_db).prop('disabled', true);

        $.post(ajaxurl, {
            action: 'baloa_structure_auditor_seo_revert_all_fixes',
            confirm_code: confirmText,
            purge_history: purgeHistory,
            nonce: nonce
        }, function(res) {
            if (res.success) {
                alert(res.data.message);
                $('#input-confirm-purge').val('');
                $btn.text(strings.purge_all_btn).prop('disabled', true).css('opacity', '0.5');
                loadFixes();
            } else {
                alert('Error: ' + res.data.message);
                $btn.text(strings.purge_all_btn).prop('disabled', false);
            }
        }).fail(function() {
            alert(strings.connection_error);
            $btn.text(strings.purge_all_btn).prop('disabled', false);
        });
    });
});
