jQuery(document).ready(function($) {
    if (typeof bsaSchemaBuilder === 'undefined') return;

    console.log('Baloa Schema Builder Initialized for post: ' + bsaSchemaBuilder.post_id);

    /**
     * Sends schema nodes to the backend via AJAX for validation and persistence.
     *
     * @param {Array} nodes Schema.org structured data nodes.
     * @returns {Promise} jQuery AJAX promise.
     */
    window.bsaSaveSchema = function(nodes) {
        return $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'baloa_structure_auditor_seo_save_schema',
                nonce: bsaSchemaBuilder.nonce,
                post_id: bsaSchemaBuilder.post_id,
                schema_nodes: JSON.stringify(nodes)
            }
        });
    };

    $('#baloa-save-schema-btn').on('click', function(e) {
        e.preventDefault();
        var val = $('#baloa-schema-json').val().trim();
        if (val === '') {
            val = '[]';
        }
        var nodes;
        try {
            nodes = JSON.parse(val);
        } catch (err) {
            $('#baloa-schema-feedback').html('<span style="color:#ef4444;">Error: JSON inválido. Revisa la sintaxis.</span>');
            return;
        }

        if (!Array.isArray(nodes)) {
            nodes = [nodes];
        }

        $('#baloa-schema-feedback').html('<span style="color:#3b82f6;">Validando y guardando esquema...</span>');
        
        window.bsaSaveSchema(nodes).done(function(res) {
            if (res.success) {
                $('#baloa-schema-feedback').html('<span style="color:#22c55e;">✓ Esquema guardado y validado correctamente.</span>');
            } else {
                var msg = res.data && res.data.message ? res.data.message : 'Error al validar el esquema.';
                $('#baloa-schema-feedback').html('<span style="color:#ef4444;">✗ ' + msg + '</span>');
            }
        }).fail(function(xhr) {
            var errObj = xhr.responseJSON;
            var msg = errObj && errObj.data && errObj.data.message ? errObj.data.message : 'Error de comunicación con el servidor.';
            $('#baloa-schema-feedback').html('<span style="color:#ef4444;">✗ ' + msg + '</span>');
        });
    });
});
